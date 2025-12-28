<?php
/**
 * NET8 SDK API - Set Balance Endpoint
 * Case 0, 1 対応: ユーザー残高を絶対値で設定
 *
 * @endpoint POST /api/v1/set_balance.php
 * @param string userId - ユーザーID（必須）
 * @param int balance - 新しい残高（必須、0以上）
 * @param string reason - 設定理由（オプション）
 */

header('Content-Type: application/json; charset=utf-8');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'METHOD_NOT_ALLOWED',
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/require_files.php');

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
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header required'
    ]);
    exit;
}

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_JSON',
        'message' => 'Invalid JSON format'
    ]);
    exit;
}

// パラメータ取得
$userId = $input['userId'] ?? null;
$balance = $input['balance'] ?? null;
$reason = $input['reason'] ?? 'manual_set';

// バリデーション
if (empty($userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_USER_ID',
        'message' => 'userId is required'
    ]);
    exit;
}

if (!is_numeric($balance)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_BALANCE',
        'message' => 'balance must be a number'
    ]);
    exit;
}

$balance = (int)$balance;

if ($balance < 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'NEGATIVE_BALANCE',
        'message' => 'balance must be >= 0'
    ]);
    exit;
}

// DB接続
try {
    $pdo = get_db_connection();

    // トランザクション開始前に既存のトランザクションをクリア
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $pdo->beginTransaction();

    // 既存残高取得
    $stmt = $pdo->prepare("SELECT balance FROM user_balances WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldBalance = $row ? (int)$row['balance'] : 0;

    // user_balances テーブル更新
    if ($row) {
        // 既存ユーザー: UPDATE
        $stmt = $pdo->prepare("
            UPDATE user_balances
            SET balance = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$balance, $userId]);
    } else {
        // 新規ユーザー: INSERT
        $stmt = $pdo->prepare("
            INSERT INTO user_balances (user_id, balance, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $balance]);
    }

    // mst_member.point も同期（存在する場合のみ）
    $stmt = $pdo->prepare("SELECT member_no FROM mst_member WHERE login_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE mst_member SET point = ? WHERE login_id = ?");
        $stmt->execute([$balance, $userId]);
    }

    // トランザクション履歴記録（テーブルが存在する場合のみ）
    try {
        $stmt = $pdo->prepare("
            INSERT INTO point_transactions
            (user_id, type, amount, old_balance, new_balance, reason, created_at)
            VALUES (?, 'set_balance', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $userId,
            $balance - $oldBalance,
            $oldBalance,
            $balance,
            $reason
        ]);
    } catch (PDOException $e) {
        // point_transactionsテーブルが存在しない場合はスキップ
        error_log("set_balance.php: point_transactions table not found, skipping transaction log");
    }

    $pdo->commit();

    // 成功レスポンス
    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'oldBalance' => $oldBalance,
        'newBalance' => $balance,
        'timestamp' => date('c')
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("set_balance.php error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to set balance'
    ]);
}
