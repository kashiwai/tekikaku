<?php
/**
 * NET8 SDK API - List Users Endpoint
 * Case 5 対応: ユーザー一覧取得
 *
 * @endpoint GET /api/v1/list_users.php
 * @param string prefix - ユーザーIDプレフィックス（例: kr_net8_）
 * @param bool hasBalance - true: 残高があるユーザーのみ
 * @param int limit - 取得件数（デフォルト: 100、最大1000）
 * @param int offset - オフセット（デフォルト: 0）
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'METHOD_NOT_ALLOWED',
        'message' => 'Only GET method is allowed'
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

// クエリパラメータ取得
$prefix = $_GET['prefix'] ?? '';
$hasBalance = isset($_GET['hasBalance']) && $_GET['hasBalance'] === 'true';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

if ($limit > 1000) {
    $limit = 1000; // 最大1000件
}

try {
    $pdo = get_db_connection();

    // クエリ構築
    $query = "
        SELECT
            ub.user_id,
            ub.balance,
            ub.created_at,
            ub.updated_at,
            COUNT(DISTINCT gs.session_id) as total_games,
            MAX(gs.start_time) as last_played_at,
            COALESCE(SUM(gs.points_consumed), 0) as total_consumed,
            COALESCE(SUM(gs.points_won), 0) as total_won
        FROM user_balances ub
        LEFT JOIN game_sessions gs ON ub.user_id = gs.user_id
        WHERE 1=1
    ";

    $params = [];

    if (!empty($prefix)) {
        $query .= " AND ub.user_id LIKE ?";
        $params[] = $prefix . '%';
    }

    if ($hasBalance) {
        $query .= " AND ub.balance > 0";
    }

    $query .= " GROUP BY ub.user_id";
    $query .= " ORDER BY ub.updated_at DESC";
    $query .= " LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 総件数取得
    $countQuery = "SELECT COUNT(DISTINCT user_id) as total FROM user_balances WHERE 1=1";
    $countParams = [];

    if (!empty($prefix)) {
        $countQuery .= " AND user_id LIKE ?";
        $countParams[] = $prefix . '%';
    }

    if ($hasBalance) {
        $countQuery .= " AND balance > 0";
    }

    $stmt = $pdo->prepare($countQuery);
    $stmt->execute($countParams);
    $totalCount = $stmt->fetchColumn();

    // レスポンス
    echo json_encode([
        'success' => true,
        'total' => (int)$totalCount,
        'limit' => $limit,
        'offset' => $offset,
        'count' => count($users),
        'users' => $users
    ]);

} catch (PDOException $e) {
    error_log("❌ list_users.php DATABASE ERROR:");
    error_log("  Message: " . $e->getMessage());
    error_log("  Code: " . $e->getCode());
    error_log("  File: " . $e->getFile());
    error_log("  Line: " . $e->getLine());
    error_log("  Stack trace: " . $e->getTraceAsString());
    error_log("  Parameters: prefix={$prefix}, hasBalance=" . ($hasBalance ? 'true' : 'false') . ", limit={$limit}, offset={$offset}");

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Failed to fetch users',
        'debug' => [
            'error_message' => $e->getMessage(),
            'error_code' => $e->getCode()
        ]
    ]);
} catch (Exception $e) {
    error_log("❌ list_users.php GENERAL ERROR:");
    error_log("  Message: " . $e->getMessage());
    error_log("  Stack trace: " . $e->getTraceAsString());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => $e->getMessage()
    ]);
}
