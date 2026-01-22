<?php
/**
 * NET8 SDK API - Game Point Converted Event Endpoint
 * Version: 1.0.0
 * Created: 2026-01-23
 *
 * Purpose: リアルタイムポイント切替イベント処理
 * - クレジット→ポイント変換を記録
 * - コールバック送信（game.point_converted）
 */

header('Content-Type: application/json');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/require_files.php');
require_once(__DIR__ . '/helpers/callback_helper.php');

// 認証ヘッダー確認
$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['sessionId'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_SESSION_ID',
        'message' => 'Session ID is required'
    ]);
    exit;
}

$sessionId = $input['sessionId'];
$creditConverted = isset($input['creditConverted']) ? (int)$input['creditConverted'] : 0;
$pointsReceived = isset($input['pointsReceived']) ? (int)$input['pointsReceived'] : 0;
$conversionRate = isset($input['conversionRate']) ? (float)$input['conversionRate'] : 0;

error_log("💱 Point conversion event: sessionId={$sessionId}, creditConverted={$creditConverted}, pointsReceived={$pointsReceived}, rate={$conversionRate}");

try {
    $pdo = get_db_connection();

    // ゲームセッション情報を取得
    $stmt = $pdo->prepare("
        SELECT
            id,
            session_id,
            user_id,
            api_key_id,
            member_no,
            partner_user_id,
            machine_no,
            model_cd,
            status,
            callback_url,
            callback_secret,
            currency
        FROM game_sessions
        WHERE session_id = :session_id
        AND status = 'playing'
    ");

    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'error' => 'SESSION_NOT_FOUND',
            'message' => 'Active game session not found'
        ]);
        exit;
    }

    // コールバック送信（リアルタイム）
    if ($session['callback_url'] && $session['callback_secret']) {
        $conversionData = [
            'sessionId' => $session['session_id'],
            'memberNo' => $session['member_no'],
            'userId' => $session['partner_user_id'] ?? $session['user_id'],
            'modelId' => $session['model_cd'],
            'machineNo' => $session['machine_no'],

            'creditConverted' => $creditConverted,
            'pointsReceived' => $pointsReceived,
            'conversionRate' => $conversionRate,

            'timestamp' => date('c'), // ISO 8601
            'currency' => $session['currency'] ?? 'JPY'
        ];

        $callbackPayload = [
            'event' => 'game.point_converted',
            'timestamp' => time(),
            'data' => $conversionData
        ];

        $jsonPayload = json_encode($callbackPayload, JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $jsonPayload, $session['callback_secret']);
        $signatureHeader = "sha256={$signature}";

        $headers = [
            'Content-Type: application/json',
            'X-NET8-Signature: ' . $signatureHeader,
            'X-NET8-Timestamp: ' . time(),
            'X-NET8-Event: game.point_converted',
            'User-Agent: NET8-Callback/2.0'
        ];

        $ch = curl_init($session['callback_url']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("✅ game.point_converted callback succeeded: {$session['callback_url']}");
            $callbackSuccess = true;
        } else {
            error_log("⚠️ game.point_converted callback failed: {$session['callback_url']}, HTTP {$httpCode}, error: {$error}");
            $callbackSuccess = false;
        }
    } else {
        $callbackSuccess = false;
    }

    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'creditConverted' => $creditConverted,
        'pointsReceived' => $pointsReceived,
        'conversionRate' => $conversionRate,
        'callbackSent' => !empty($session['callback_url']),
        'callbackSuccess' => $callbackSuccess
    ]);

} catch (Exception $e) {
    error_log('Game Point Converted API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to process point conversion event: ' . $e->getMessage()
    ]);
}
