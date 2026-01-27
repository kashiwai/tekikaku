<?php
/**
 * NET8 Claude Code API - API Keys Management
 * Version: 1.0.1
 * Created: 2025-12-12
 * Updated: 2025-12-12 - PHP 7.2 compatibility fix
 *
 * GET    /api/claude/api-keys          - APIキー一覧
 * POST   /api/claude/api-keys          - 新規発行
 * GET    /api/claude/api-keys/{id}     - 詳細取得
 * PUT    /api/claude/api-keys/{id}     - 更新
 * DELETE /api/claude/api-keys/{id}     - 削除
 * POST   /api/claude/api-keys/{id}/toggle     - 有効/無効切替
 * POST   /api/claude/api-keys/{id}/regenerate - キー再発行
 * GET    /api/claude/api-keys/{id}/usage      - 使用統計
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ClaudeAuth.php';
require_once __DIR__ . '/helpers/ApiValidator.php';
require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();
    $auth = new ClaudeAuth($pdo);

    // 認証チェック
    $authData = $auth->requireAuth();

    // パスパラメータ解析
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $pathInfo));
    $keyId = $pathParts[1] ?? null;
    $action = $pathParts[2] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    // ルーティング
    if ($keyId === null) {
        // /api/claude/api-keys
        if ($method === 'GET') {
            listApiKeys($pdo);
        } elseif ($method === 'POST') {
            createApiKey($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'POST']);
        }
    } elseif ($action === 'toggle' && $method === 'POST') {
        toggleApiKey($pdo, $keyId);
    } elseif ($action === 'regenerate' && $method === 'POST') {
        regenerateApiKey($pdo, $keyId);
    } elseif ($action === 'usage' && $method === 'GET') {
        getApiKeyUsage($pdo, $keyId);
    } else {
        // /api/claude/api-keys/{id}
        if ($method === 'GET') {
            getApiKey($pdo, $keyId);
        } elseif ($method === 'PUT') {
            updateApiKey($pdo, $keyId);
        } elseif ($method === 'DELETE') {
            deleteApiKey($pdo, $keyId);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT', 'DELETE']);
        }
    }

} catch (Exception $e) {
    error_log('Claude API Keys Error: ' . $e->getMessage());
    ApiResponse::serverError('APIキー処理中にエラーが発生しました');
}

/**
 * APIキー一覧取得
 */
function listApiKeys($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $environment = $_GET['environment'] ?? '';
    $isActive = $_GET['is_active'] ?? '';

    // カウント
    $countSql = "SELECT COUNT(*) FROM api_keys WHERE 1=1";
    $params = [];

    if ($environment !== '') {
        $countSql .= " AND environment = :environment";
        $params['environment'] = $environment;
    }
    if ($isActive !== '') {
        $countSql .= " AND is_active = :is_active";
        $params['is_active'] = $isActive;
    }

    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT id, key_value, key_type, name, environment, rate_limit,
                   is_active, last_used_at, created_at, expires_at
            FROM api_keys WHERE 1=1";

    if ($environment !== '') {
        $sql .= " AND environment = :environment";
    }
    if ($isActive !== '') {
        $sql .= " AND is_active = :is_active";
    }

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    $params['limit'] = $perPage;
    $params['offset'] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($key, $value, $type);
    }
    $stmt->execute();
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // キー値をマスク
    foreach ($keys as &$key) {
        $key['key_value_masked'] = substr($key['key_value'], 0, 12) . '...' . substr($key['key_value'], -4);
    }

    ApiResponse::list($keys, $total, $page, $perPage);
}

/**
 * APIキー新規発行
 */
function createApiKey($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('name', 'キー名は必須です')
              ->maxLength('name', 100, 'キー名は100文字以内で入力してください')
              ->in('environment', ['test', 'live'], '環境はtestまたはliveを指定してください')
              ->in('key_type', ['public', 'secret', 'claude'], 'キータイプが無効です');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    $name = $validator->get('name');
    $environment = $validator->get('environment', 'test');
    $keyType = $validator->get('key_type', 'public');
    $rateLimit = $validator->get('rate_limit', 1000);
    $expiresAt = $validator->get('expires_at');

    // キー生成 (PHP 7.4互換)
    switch ($keyType) {
        case 'claude':
            $prefix = 'ck_claude_';
            break;
        case 'secret':
            $prefix = 'sk_' . ($environment === 'live' ? 'live_' : 'test_');
            break;
        default:
            $prefix = 'pk_' . ($environment === 'live' ? 'live_' : 'test_');
    }
    $keyValue = $prefix . bin2hex(random_bytes(24));

    $sql = "INSERT INTO api_keys (key_value, key_type, name, environment, rate_limit, is_active, created_at, expires_at)
            VALUES (:key_value, :key_type, :name, :environment, :rate_limit, 1, NOW(), :expires_at)";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'key_value' => $keyValue,
        'key_type' => $keyType,
        'name' => $name,
        'environment' => $environment,
        'rate_limit' => $rateLimit,
        'expires_at' => $expiresAt
    ]);

    $id = $pdo->lastInsertId();

    ApiResponse::success([
        'id' => $id,
        'key_value' => $keyValue,
        'key_type' => $keyType,
        'name' => $name,
        'environment' => $environment,
        'rate_limit' => $rateLimit,
        'is_active' => true
    ], 'APIキーを発行しました', 201);
}

/**
 * APIキー詳細取得
 */
function getApiKey($pdo, $id) {
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    ApiResponse::success($key);
}

/**
 * APIキー更新
 */
function updateApiKey($pdo, $id) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // 存在チェック
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    $updates = [];
    $params = ['id' => $id];

    if (isset($input['name'])) {
        $updates[] = "name = :name";
        $params['name'] = $input['name'];
    }
    if (isset($input['rate_limit'])) {
        $updates[] = "rate_limit = :rate_limit";
        $params['rate_limit'] = (int)$input['rate_limit'];
    }
    if (isset($input['expires_at'])) {
        $updates[] = "expires_at = :expires_at";
        $params['expires_at'] = $input['expires_at'];
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $sql = "UPDATE api_keys SET " . implode(', ', $updates) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 更新後のデータ取得
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success($key, 'APIキーを更新しました');
}

/**
 * APIキー削除
 */
function deleteApiKey($pdo, $id) {
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    $sql = "DELETE FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);

    ApiResponse::success(null, 'APIキーを削除しました');
}

/**
 * APIキー有効/無効切替
 */
function toggleApiKey($pdo, $id) {
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    $newStatus = $key['is_active'] ? 0 : 1;
    $sql = "UPDATE api_keys SET is_active = :is_active WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['is_active' => $newStatus, 'id' => $id]);

    ApiResponse::success([
        'id' => $id,
        'is_active' => (bool)$newStatus
    ], $newStatus ? 'APIキーを有効化しました' : 'APIキーを無効化しました');
}

/**
 * APIキー再発行
 */
function regenerateApiKey($pdo, $id) {
    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    // 新しいキー生成
    $keyType = $key['key_type'] ?? 'public';
    $environment = $key['environment'];

    switch ($keyType) {
        case 'claude':
            $prefix = 'ck_claude_';
            break;
        case 'secret':
            $prefix = 'sk_' . ($environment === 'live' ? 'live_' : 'test_');
            break;
        default:
            $prefix = 'pk_' . ($environment === 'live' ? 'live_' : 'test_');
    }
    $newKeyValue = $prefix . bin2hex(random_bytes(24));

    $sql = "UPDATE api_keys SET key_value = :key_value WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['key_value' => $newKeyValue, 'id' => $id]);

    ApiResponse::success([
        'id' => $id,
        'key_value' => $newKeyValue,
        'name' => $key['name'],
        'environment' => $environment
    ], 'APIキーを再発行しました');
}

/**
 * APIキー使用統計取得
 */
function getApiKeyUsage($pdo, $id) {
    $days = isset($_GET['days']) ? min(90, max(1, (int)$_GET['days'])) : 7;

    $sql = "SELECT * FROM api_keys WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $key = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key) {
        ApiResponse::notFound('APIキーが見つかりません');
    }

    // 使用統計取得
    $sql = "SELECT
                DATE(created_at) as date,
                COUNT(*) as request_count,
                AVG(response_time_ms) as avg_response_time,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as error_count
            FROM api_usage_logs
            WHERE api_key_id = :id
              AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(created_at)
            ORDER BY date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('id', $id, PDO::PARAM_INT);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // サマリー
    $sql = "SELECT
                COUNT(*) as total_requests,
                AVG(response_time_ms) as avg_response_time,
                SUM(CASE WHEN status_code >= 400 THEN 1 ELSE 0 END) as total_errors
            FROM api_usage_logs
            WHERE api_key_id = :id
              AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('id', $id, PDO::PARAM_INT);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'api_key_id' => $id,
        'period_days' => $days,
        'summary' => $summary,
        'daily_stats' => $stats
    ]);
}
