<?php
/**
 * NET8 SDK API - Game End Endpoint
 * Version: 1.0.0
 * Created: 2025-11-18
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
require_once(__DIR__ . '/helpers/user_helper.php');

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

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header required'
    ]);
    exit;
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
$result = $input['result'] ?? 'completed'; // win, lose, draw, error, cancelled
$pointsWon = isset($input['pointsWon']) ? (int)$input['pointsWon'] : 0;
$resultData = $input['resultData'] ?? null;

try {
    $pdo = get_db_connection();

    // ゲームセッション情報を取得
    $stmt = $pdo->prepare("
        SELECT
            id,
            session_id,
            user_id,
            api_key_id,
            machine_no,
            model_cd,
            points_consumed,
            status,
            started_at
        FROM game_sessions
        WHERE session_id = :session_id
    ");

    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'error' => 'SESSION_NOT_FOUND',
            'message' => 'Game session not found'
        ]);
        exit;
    }

    // 既に終了している場合
    if ($session['status'] === 'completed' || $session['status'] === 'cancelled') {
        http_response_code(409);
        echo json_encode([
            'error' => 'SESSION_ALREADY_ENDED',
            'message' => 'Game session already ended',
            'status' => $session['status']
        ]);
        exit;
    }

    // プレイ時間を計算
    $startedAt = new DateTime($session['started_at']);
    $endedAt = new DateTime();
    $playDuration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

    // ゲームセッションを更新
    $stmt = $pdo->prepare("
        UPDATE game_sessions
        SET
            ended_at = NOW(),
            status = :status,
            result = :result,
            points_won = :points_won,
            play_duration = :play_duration,
            result_data = :result_data
        WHERE session_id = :session_id
    ");

    $status = ($result === 'error' || $result === 'cancelled') ? $result : 'completed';

    $stmt->execute([
        'status' => $status,
        'result' => $result,
        'points_won' => $pointsWon,
        'play_duration' => $playDuration,
        'result_data' => $resultData ? json_encode($resultData) : null,
        'session_id' => $sessionId
    ]);

    // ポイント払い出し（勝利時）
    $newBalance = null;
    $transaction = null;

    if ($session['user_id'] && $pointsWon > 0) {
        try {
            $transaction = payoutPoints(
                $pdo,
                $session['user_id'],
                $pointsWon,
                $sessionId
            );

            // 最新の残高を取得
            $userBalance = getUserBalance($pdo, $session['user_id']);
            $newBalance = $userBalance['balance'];

        } catch (Exception $e) {
            error_log('Payout failed: ' . $e->getMessage());
            // ペイアウト失敗してもゲーム終了は記録
        }
    } else if ($session['user_id']) {
        // 残高取得のみ
        $userBalance = getUserBalance($pdo, $session['user_id']);
        $newBalance = $userBalance['balance'] ?? null;
    }

    // 成功レスポンス
    $response = [
        'success' => true,
        'sessionId' => $sessionId,
        'result' => $result,
        'pointsConsumed' => $session['points_consumed'],
        'pointsWon' => $pointsWon,
        'netProfit' => $pointsWon - $session['points_consumed'],
        'playDuration' => $playDuration,
        'endedAt' => $endedAt->format('Y-m-d H:i:s')
    ];

    // 残高情報を追加
    if ($newBalance !== null) {
        $response['newBalance'] = $newBalance;
    }

    // 取引情報を追加
    if ($transaction) {
        $response['transaction'] = [
            'id' => $transaction['transaction_id'],
            'amount' => $transaction['amount'],
            'balanceBefore' => $transaction['balance_before'],
            'balanceAfter' => $transaction['balance_after']
        ];
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Game End API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to end game: ' . $e->getMessage()
    ]);
}
