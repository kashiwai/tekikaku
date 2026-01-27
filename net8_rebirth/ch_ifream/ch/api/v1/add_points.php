<?php
/**
 * NET8 SDK API - Add Points Endpoint
 * Version: 1.0.0
 * Created: 2025-11-21
 *
 * ゲームプレイ中にポイントを追加するAPI
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

if (!isset($input['sessionId']) || !isset($input['amount'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_PARAMETERS',
        'message' => 'sessionId and amount are required'
    ]);
    exit;
}

$sessionId = $input['sessionId'];
$amount = (int)$input['amount'];
$description = $input['description'] ?? 'Bonus points during gameplay';

if ($amount <= 0) {
    http_response_code(400);
    echo json_encode([
        'error' => 'INVALID_AMOUNT',
        'message' => 'Amount must be positive'
    ]);
    exit;
}

try {
    $pdo = get_db_connection();

    // APIキー認証
    $apiKeyId = null;

    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        // JWT形式の場合
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
            }
        } else {
            // 直接APIキーの場合
            $apiKeyStmt = $pdo->prepare("SELECT id FROM api_keys WHERE key_value = :key_value AND is_active = 1");
            $apiKeyStmt->execute(['key_value' => $token]);
            $apiKeyData = $apiKeyStmt->fetch(PDO::FETCH_ASSOC);

            if ($apiKeyData) {
                $apiKeyId = $apiKeyData['id'];
            } else {
                http_response_code(401);
                echo json_encode([
                    'error' => 'INVALID_API_KEY',
                    'message' => 'Invalid API key'
                ]);
                exit;
            }
        }
    }

    // ゲームセッション情報を取得
    $stmt = $pdo->prepare("
        SELECT id, session_id, user_id, api_key_id, machine_no, status
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

    // APIキーIDの検証
    if ($apiKeyId && $session['api_key_id'] != $apiKeyId) {
        http_response_code(403);
        echo json_encode([
            'error' => 'API_KEY_MISMATCH',
            'message' => 'API key does not match the session'
        ]);
        exit;
    }

    // ゲームが進行中か確認
    if ($session['status'] !== 'playing') {
        http_response_code(409);
        echo json_encode([
            'error' => 'SESSION_NOT_ACTIVE',
            'message' => 'Game session is not active',
            'status' => $session['status']
        ]);
        exit;
    }

    if (!$session['user_id']) {
        http_response_code(400);
        echo json_encode([
            'error' => 'NO_USER_ASSOCIATED',
            'message' => 'No user associated with this session'
        ]);
        exit;
    }

    // ポイント追加（user_balances）
    $pdo->beginTransaction();

    try {
        // 現在の残高を取得（FOR UPDATE でロック）
        $stmt = $pdo->prepare("
            SELECT balance
            FROM user_balances
            WHERE user_id = :user_id
            FOR UPDATE
        ");

        $stmt->execute(['user_id' => $session['user_id']]);
        $balance = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$balance) {
            throw new Exception('User balance not found');
        }

        $balanceBefore = $balance['balance'];
        $balanceAfter = $balanceBefore + $amount;

        // 残高を更新
        $stmt = $pdo->prepare("
            UPDATE user_balances
            SET balance = :balance,
                last_transaction_at = NOW()
            WHERE user_id = :user_id
        ");

        $stmt->execute([
            'balance' => $balanceAfter,
            'user_id' => $session['user_id']
        ]);

        // 取引履歴を記録
        $transactionId = 'txn_' . uniqid() . '_' . time();

        $stmt = $pdo->prepare("
            INSERT INTO point_transactions
            (user_id, transaction_id, type, amount, balance_before, balance_after, game_session_id, description)
            VALUES
            (:user_id, :transaction_id, 'bonus', :amount, :balance_before, :balance_after, :game_session_id, :description)
        ");

        $stmt->execute([
            'user_id' => $session['user_id'],
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'game_session_id' => $sessionId,
            'description' => $description
        ]);

        $pdo->commit();

        // 成功レスポンス
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'sessionId' => $sessionId,
            'transaction' => [
                'id' => $transactionId,
                'amount' => $amount,
                'balanceBefore' => $balanceBefore,
                'balanceAfter' => $balanceAfter
            ],
            'message' => 'Points added successfully'
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Add Points API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to add points: ' . $e->getMessage()
    ]);
}
