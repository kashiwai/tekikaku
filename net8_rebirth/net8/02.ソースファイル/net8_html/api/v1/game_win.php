<?php
/**
 * NET8 SDK API - Game Win Event Endpoint
 * Version: 1.0.0
 * Created: 2026-01-23
 *
 * Purpose: リアルタイム勝利イベント処理
 * - 勝利金額をgame_sessionsに記録
 * - total_winsを累計
 * - コールバック送信（game.win）
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
$winAmount = isset($input['winAmount']) ? (int)$input['winAmount'] : 0;
$winType = isset($input['winType']) ? $input['winType'] : 'normal'; // normal, bonus, jackpot
$creditBefore = isset($input['creditBefore']) ? (int)$input['creditBefore'] : 0;
$creditAfter = isset($input['creditAfter']) ? (int)$input['creditAfter'] : 0;

error_log("🎉 Game win event: sessionId={$sessionId}, winAmount={$winAmount}, winType={$winType}, creditBefore={$creditBefore}, creditAfter={$creditAfter}");

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

    // total_winsを累計
    $newTotalWins = ((int)$session['total_wins']) + $winAmount;

    // game_sessionsを更新
    $updateStmt = $pdo->prepare("
        UPDATE game_sessions
        SET total_wins = :total_wins,
            updated_at = NOW()
        WHERE session_id = :session_id
    ");

    $updateStmt->execute([
        'total_wins' => $newTotalWins,
        'session_id' => $sessionId
    ]);

    error_log("🎊 Updated total_wins: {$newTotalWins} (previous: {$session['total_wins']}, added: {$winAmount})");

    // コールバック送信（リアルタイム）
    if ($session['callback_url'] && $session['callback_secret']) {
        $winData = [
            'winAmount' => $winAmount,
            'winType' => $winType,
            'creditBefore' => $creditBefore,
            'creditAfter' => $creditAfter,
            'totalWins' => $newTotalWins
        ];

        $callbackData = buildWinCallbackData($session, $winData);

        $callbackResult = sendRealtimeCallback(
            $session['callback_url'],
            $session['callback_secret'],
            'game.win',
            $callbackData,
            3 // リアルタイムは3回まで
        );

        if ($callbackResult['success']) {
            error_log("✅ game.win callback succeeded: {$session['callback_url']}");
        } else {
            error_log("⚠️ game.win callback failed: {$session['callback_url']}, error: " . ($callbackResult['error'] ?? 'unknown'));
        }
    }

    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'winAmount' => $winAmount,
        'winType' => $winType,
        'totalWins' => $newTotalWins,
        'callbackSent' => !empty($session['callback_url'])
    ]);

} catch (Exception $e) {
    error_log('Game Win API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to process win event: ' . $e->getMessage()
    ]);
}
