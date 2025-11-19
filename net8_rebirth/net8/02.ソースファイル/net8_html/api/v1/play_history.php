<?php
/**
 * NET8 SDK API - Play History Endpoint
 * Version: 1.0.0
 * Created: 2025-11-18
 */

header('Content-Type: application/json');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
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

// クエリパラメータ取得
$userId = $_GET['userId'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$status = $_GET['status'] ?? null; // playing, completed, error, cancelled

// パラメータバリデーション
if ($limit > 100) {
    $limit = 100;
}
if ($limit < 1) {
    $limit = 20;
}

try {
    $pdo = get_db_connection();

    // JWT からapi_key_idを取得
    $apiKeyId = null;
    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $jwt = substr($authHeader, 7);
        $parts = explode('.', $jwt);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
            }
        }
    }

    if (!$apiKeyId) {
        http_response_code(401);
        echo json_encode([
            'error' => 'INVALID_TOKEN',
            'message' => 'Invalid authentication token'
        ]);
        exit;
    }

    // ユーザーIDを内部IDに変換（partner_user_idの場合）
    $internalUserId = null;
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM sdk_users
            WHERE api_key_id = :api_key_id
            AND partner_user_id = :partner_user_id
        ");

        $stmt->execute([
            'api_key_id' => $apiKeyId,
            'partner_user_id' => $userId
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $internalUserId = $user['id'];
        }
    }

    // クエリビルド
    $sql = "SELECT
                gs.id,
                gs.session_id,
                u.partner_user_id AS user_id,
                gs.machine_no,
                gs.model_cd,
                gs.model_name,
                gs.points_consumed,
                gs.points_won,
                (gs.points_won - gs.points_consumed) AS net_profit,
                gs.play_duration,
                gs.result,
                gs.status,
                gs.started_at,
                gs.ended_at,
                gs.created_at
            FROM game_sessions gs
            LEFT JOIN sdk_users u ON gs.user_id = u.id
            WHERE gs.api_key_id = :api_key_id";

    $params = ['api_key_id' => $apiKeyId];

    // ユーザーIDフィルター
    if ($internalUserId) {
        $sql .= " AND gs.user_id = :user_id";
        $params['user_id'] = $internalUserId;
    }

    // ステータスフィルター
    if ($status) {
        $sql .= " AND gs.status = :status";
        $params['status'] = $status;
    }

    $sql .= " ORDER BY gs.created_at DESC
              LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // パラメータをバインド
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 総件数を取得
    $countSql = "SELECT COUNT(*) as total
                 FROM game_sessions gs
                 WHERE gs.api_key_id = :api_key_id";

    $countParams = ['api_key_id' => $apiKeyId];

    if ($internalUserId) {
        $countSql .= " AND gs.user_id = :user_id";
        $countParams['user_id'] = $internalUserId;
    }

    if ($status) {
        $countSql .= " AND gs.status = :status";
        $countParams['status'] = $status;
    }

    $stmt = $pdo->prepare($countSql);
    $stmt->execute($countParams);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sessions,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ]
    ]);

} catch (Exception $e) {
    error_log('Play History API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to fetch play history'
    ]);
}
