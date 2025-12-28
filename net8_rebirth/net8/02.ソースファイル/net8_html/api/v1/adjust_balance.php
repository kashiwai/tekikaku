<?php
/**
 * NET8 SDK API - Adjust Balance Endpoint
 * Case 2 対応: ユーザー残高を相対値で調整（正の値で加算、負の値で減算）
 *
 * @endpoint POST /api/v1/adjust_balance.php
 * @param string userId - ユーザーID（必須）
 * @param int amount - 調整額（正の値で加算、負の値で減算）
 * @param string reason - 調整理由（オプション）
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'METHOD_NOT_ALLOWED',
        'message' => 'Only POST method is allowed'
    ]);
    exit;
}

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

$userId = $input['userId'] ?? null;
$amount = $input['amount'] ?? null;
$reason = $input['reason'] ?? 'adjustment';

if (empty($userId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'MISSING_USER_ID',
        'message' => 'userId is required'
    ]);
    exit;
}

if (!is_numeric($amount) || $amount == 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'INVALID_AMOUNT',
        'message' => 'amount must be a non-zero number'
    ]);
    exit;
}

$amount = (int)$amount;

try {
    $pdo = get_db_connection();

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $pdo->beginTransaction();

    // 既存残高取得
    $stmt = $pdo->prepare("SELECT balance FROM user_balances WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $oldBalance = $row ? (int)$row['balance'] : 0;

    $newBalance = $oldBalance + $amount;

    // 残高不足チェック（減算時）
    if ($newBalance < 0) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'INSUFFICIENT_BALANCE',
            'message' => "Insufficient balance. Current: {$oldBalance}, Requested: {$amount}"
        ]);
        exit;
    }

    // user_balances 更新
    if ($row) {
        $stmt = $pdo->prepare("
            UPDATE user_balances
            SET balance = balance + ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$amount, $userId]);
    } else {
        // 新規ユーザー（加算のみ）
        if ($amount < 0) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'USER_NOT_FOUND',
                'message' => 'Cannot subtract from non-existent user'
            ]);
            exit;
        }

        $stmt = $pdo->prepare("
            INSERT INTO user_balances (user_id, balance, created_at, updated_at)
            VALUES (?, ?, NOW(), NOW())
        ");
        $stmt->execute([$userId, $amount]);
    }

    // mst_member.point 同期
    $stmt = $pdo->prepare("SELECT member_no FROM mst_member WHERE login_id = ?");
    $stmt->execute([$userId]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE mst_member SET point = point + ? WHERE login_id = ?");
        $stmt->execute([$amount, $userId]);
    }

    // トランザクション履歴
    try {
        $stmt = $pdo->prepare("
            INSERT INTO point_transactions
            (user_id, type, amount, old_balance, new_balance, reason, created_at)
            VALUES (?, 'adjustment', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $amount, $oldBalance, $newBalance, $reason]);
    } catch (PDOException $e) {
        error_log("adjust_balance.php: point_transactions table not found, skipping transaction log");
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'userId' => $userId,
        'oldBalance' => $oldBalance,
        'adjustment' => $amount,
        'newBalance' => $newBalance,
        'timestamp' => date('c')
    ]);

} catch (PDOException $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log("adjust_balance.php error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to adjust balance'
    ]);
}
