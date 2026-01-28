<?php
/**
 * NET8 API - Game Event Endpoint (Real-time bet/win tracking)
 * Version: 1.0.0
 * Created: 2026-01-08
 *
 * Purpose: リアルタイムゲームイベント受信 & 韓国チームへコールバック
 * - ベット発生時 (game.bet)
 * - 勝利発生時 (game.win)
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'METHOD_NOT_ALLOWED', 'message' => 'Only POST method is allowed']);
    exit;
}

require_once('../../_etc/require_files.php');
require_once(__DIR__ . '/helpers/callback_helper.php');

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

// 必須パラメータ
$sessionId = $input['sessionId'] ?? null;
$eventType = $input['eventType'] ?? null; // 'bet' or 'win'
$memberNo = $input['memberNo'] ?? null;

// イベント固有データ
$betAmount = isset($input['betAmount']) ? (int)$input['betAmount'] : null;
$winAmount = isset($input['winAmount']) ? (int)$input['winAmount'] : null;
$creditBefore = isset($input['creditBefore']) ? (int)$input['creditBefore'] : null;
$creditAfter = isset($input['creditAfter']) ? (int)$input['creditAfter'] : null;
$winType = $input['winType'] ?? 'normal'; // normal, bonus, jackpot

// パラメータ検証
if (!$sessionId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_SESSION_ID',
        'message' => 'sessionId is required'
    ]);
    exit;
}

if (!in_array($eventType, ['bet', 'win', 'round_end'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_EVENT_TYPE',
        'message' => 'eventType must be "bet", "win", or "round_end"'
    ]);
    exit;
}

// bet イベントの場合はbetAmountが必須
if ($eventType === 'bet' && $betAmount === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_BET_AMOUNT',
        'message' => 'betAmount is required for bet event'
    ]);
    exit;
}

// win イベントの場合はwinAmountが必須
if ($eventType === 'win' && $winAmount === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_WIN_AMOUNT',
        'message' => 'winAmount is required for win event'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();

    // セッション情報とコールバック設定を取得
    $stmt = $pdo->prepare("
        SELECT
            gs.session_id,
            gs.member_no,
            gs.user_id,
            gs.model_cd,
            gs.machine_no,
            gs.currency,
            gs.callback_url,
            gs.callback_secret,
            u.partner_user_id
        FROM game_sessions gs
        LEFT JOIN sdk_users u ON gs.user_id = u.id
        WHERE gs.session_id = :session_id
        AND gs.status IN ('playing', 'active')
    ");

    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'SESSION_NOT_FOUND',
            'message' => 'Game session not found or already ended'
        ]);
        exit;
    }

    // コールバック設定確認
    $callbackUrl = $session['callback_url'];
    $callbackSecret = $session['callback_secret'];

    if (!$callbackUrl || !$callbackSecret) {
        // コールバック設定なし → ログのみ記録して正常終了
        error_log("ℹ️ No callback configured for session {$sessionId}, event: {$eventType}");

        echo json_encode([
            'success' => true,
            'message' => 'Event recorded (no callback configured)',
            'sessionId' => $sessionId,
            'eventType' => $eventType
        ]);
        exit;
    }

    // イベントタイプ別にコールバックデータ構築
    if ($eventType === 'bet') {
        // ベットイベント
        $eventData = buildBetCallbackData($session, [
            'betAmount' => $betAmount,
            'creditBefore' => $creditBefore,
            'creditAfter' => $creditAfter,
            'totalBets' => 1 // TODO: セッション内累計を取得する場合はDBから取得
        ]);

        $callbackEventType = 'game.bet';

    } elseif ($eventType === 'win') {
        // 勝利イベント
        $eventData = buildWinCallbackData($session, [
            'winAmount' => $winAmount,
            'winType' => $winType,
            'creditBefore' => $creditBefore,
            'creditAfter' => $creditAfter,
            'totalWins' => 1 // TODO: セッション内累計を取得する場合はDBから取得
        ]);

        $callbackEventType = 'game.win';

    } elseif ($eventType === 'round_end') {
        // ラウンド終了イベント（スロット専用：毎スピン終了時）
        // 勝ち/負け両方で送信される
        $roundResult = $input['result'] ?? 'lose'; // 'win' or 'lose'

        $eventData = buildRoundEndCallbackData($session, [
            'result' => $roundResult,
            'betAmount' => $betAmount,
            'winAmount' => $winAmount ?? 0,
            'creditBefore' => $creditBefore,
            'creditAfter' => $creditAfter
        ]);

        $callbackEventType = 'game.round_end';
    }

    // 韓国チームへリアルタイムコールバック送信
    error_log("📡 Sending {$callbackEventType} callback for session {$sessionId}");

    $callbackResult = sendRealtimeCallback(
        $callbackUrl,
        $callbackSecret,
        $callbackEventType,
        $eventData,
        3 // 最大3回リトライ
    );

    // コールバック結果をログ記録
    if ($callbackResult['success']) {
        error_log("✅ {$callbackEventType} callback succeeded for session {$sessionId}");
    } else {
        error_log("❌ {$callbackEventType} callback failed for session {$sessionId}: " . ($callbackResult['error'] ?? 'unknown'));
    }

    // レスポンス（コールバック成否に関わらず200を返す = リトライさせない）
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Event processed',
        'sessionId' => $sessionId,
        'eventType' => $eventType,
        'callback' => [
            'sent' => $callbackResult['success'],
            'attempts' => $callbackResult['attempts'] ?? 0,
            'error' => $callbackResult['error'] ?? null
        ]
    ]);

} catch (Exception $e) {
    error_log("❌ Game event API error: " . $e->getMessage());
    error_log($e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to process game event'
    ]);
}
