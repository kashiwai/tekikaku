<?php
/**
 * NET8 SDK API - Game Bet Event Endpoint
 * Version: 1.0.0
 * Created: 2026-01-23
 *
 * Purpose: リアルタイムベットイベント処理
 * - ベット金額をgame_sessionsに記録
 * - total_betsを累計
 * - コールバック送信（game.bet）
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
$betAmount = isset($input['betAmount']) ? (int)$input['betAmount'] : 0;
$creditBefore = isset($input['creditBefore']) ? (int)$input['creditBefore'] : 0;
$creditAfter = isset($input['creditAfter']) ? (int)$input['creditAfter'] : 0;

error_log("🎲 Game bet event: sessionId={$sessionId}, betAmount={$betAmount}, creditBefore={$creditBefore}, creditAfter={$creditAfter}");

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
            total_bets,
            total_wins,
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

    // total_betsを累計
    $newTotalBets = ((int)$session['total_bets']) + $betAmount;

    // game_sessionsを更新
    $updateStmt = $pdo->prepare("
        UPDATE game_sessions
        SET total_bets = :total_bets,
            updated_at = NOW()
        WHERE session_id = :session_id
    ");

    $updateStmt->execute([
        'total_bets' => $newTotalBets,
        'session_id' => $sessionId
    ]);

    error_log("💰 Updated total_bets: {$newTotalBets} (previous: {$session['total_bets']}, added: {$betAmount})");

    // コールバック送信（リアルタイム）
    if ($session['callback_url'] && $session['callback_secret']) {
        $betData = [
            'betAmount' => $betAmount,
            'creditBefore' => $creditBefore,
            'creditAfter' => $creditAfter,
            'totalBets' => $newTotalBets
        ];

        $callbackData = buildBetCallbackData($session, $betData);

        $callbackResult = sendRealtimeCallback(
            $session['callback_url'],
            $session['callback_secret'],
            'game.bet',
            $callbackData,
            3 // リアルタイムは3回まで
        );

        if ($callbackResult['success']) {
            error_log("✅ game.bet callback succeeded: {$session['callback_url']}");
        } else {
            error_log("⚠️ game.bet callback failed: {$session['callback_url']}, error: " . ($callbackResult['error'] ?? 'unknown'));
        }
    }

    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'betAmount' => $betAmount,
        'totalBets' => $newTotalBets,
        'callbackSent' => !empty($session['callback_url'])
    ]);

} catch (Exception $e) {
    error_log('Game Bet API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to process bet event: ' . $e->getMessage()
    ]);
}
