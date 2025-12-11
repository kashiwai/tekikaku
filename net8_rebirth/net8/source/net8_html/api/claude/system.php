<?php
/**
 * NET8 Claude Code API - System Settings
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/system/settings      - システム設定取得
 * PUT  /api/claude/system/settings      - システム設定更新
 * GET  /api/claude/system/hours         - 営業時間取得
 * PUT  /api/claude/system/hours         - 営業時間更新
 * GET  /api/claude/system/health        - ヘルスチェック
 * GET  /api/claude/system/logs          - システムログ
 * POST /api/claude/system/maintenance   - メンテナンスモード切替
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, OPTIONS');
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

    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $pathInfo));
    $action = array_values($pathParts)[0] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    // health はパブリック
    if ($action === 'health' && $method === 'GET') {
        getHealthCheck($pdo);
    } else {
        $auth = new ClaudeAuth($pdo);
        $authData = $auth->requireAuth();

        switch ($action) {
            case 'settings':
                if ($method === 'GET') {
                    getSystemSettings($pdo);
                } elseif ($method === 'PUT') {
                    updateSystemSettings($pdo);
                } else {
                    ApiResponse::methodNotAllowed(['GET', 'PUT']);
                }
                break;
            case 'hours':
                if ($method === 'GET') {
                    getBusinessHours($pdo);
                } elseif ($method === 'PUT') {
                    updateBusinessHours($pdo);
                } else {
                    ApiResponse::methodNotAllowed(['GET', 'PUT']);
                }
                break;
            case 'logs':
                if ($method === 'GET') {
                    getSystemLogs($pdo);
                } else {
                    ApiResponse::methodNotAllowed(['GET']);
                }
                break;
            case 'maintenance':
                if ($method === 'POST') {
                    toggleMaintenance($pdo);
                } else {
                    ApiResponse::methodNotAllowed(['POST']);
                }
                break;
            default:
                ApiResponse::notFound('システムエンドポイントが見つかりません');
        }
    }

} catch (Exception $e) {
    error_log('Claude System API Error: ' . $e->getMessage());
    ApiResponse::serverError('システム処理中にエラーが発生しました');
}

/**
 * ヘルスチェック（パブリック）
 */
function getHealthCheck($pdo) {
    $status = 'healthy';
    $checks = [];

    // DB接続チェック
    try {
        $stmt = $pdo->query('SELECT 1');
        $checks['database'] = ['status' => 'ok', 'message' => '接続正常'];
    } catch (Exception $e) {
        $status = 'unhealthy';
        $checks['database'] = ['status' => 'error', 'message' => '接続失敗'];
    }

    // テーブル存在チェック
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM mst_member LIMIT 1');
        $checks['tables'] = ['status' => 'ok', 'message' => 'テーブル正常'];
    } catch (Exception $e) {
        $status = 'degraded';
        $checks['tables'] = ['status' => 'warning', 'message' => 'テーブルアクセス問題'];
    }

    // アクティブセッションチェック
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM his_gamesession WHERE status = 'playing'");
        $activeSessions = $stmt->fetchColumn();
        $checks['sessions'] = ['status' => 'ok', 'active_count' => (int)$activeSessions];
    } catch (Exception $e) {
        $checks['sessions'] = ['status' => 'unknown'];
    }

    ApiResponse::success([
        'status' => $status,
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
        'checks' => $checks
    ]);
}

/**
 * システム設定取得
 */
function getSystemSettings($pdo) {
    // mst_settingからシステム設定取得
    $sql = "SELECT setting_key, setting_value, setting_type, description FROM mst_setting ORDER BY setting_key";

    try {
        $stmt = $pdo->query($sql);
        $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // テーブルがない場合はデフォルト値を返す
        $settings = getDefaultSettings();
    }

    // 設定をキーでグループ化
    $grouped = [];
    foreach ($settings as $setting) {
        $grouped[$setting['setting_key']] = [
            'value' => castSettingValue($setting['setting_value'], $setting['setting_type'] ?? 'string'),
            'type' => $setting['setting_type'] ?? 'string',
            'description' => $setting['description'] ?? ''
        ];
    }

    ApiResponse::success($grouped);
}

/**
 * システム設定更新
 */
function updateSystemSettings($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    if (empty($input)) {
        ApiResponse::error('更新する設定がありません', 400);
    }

    $updated = [];

    foreach ($input as $key => $value) {
        // 存在チェック
        $sql = "SELECT * FROM mst_setting WHERE setting_key = :key";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['key' => $key]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // 更新
            $sql = "UPDATE mst_setting SET setting_value = :value, upd_dt = NOW() WHERE setting_key = :key";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['value' => is_array($value) ? json_encode($value) : (string)$value, 'key' => $key]);
        } else {
            // 新規追加
            $sql = "INSERT INTO mst_setting (setting_key, setting_value, setting_type, add_dt) VALUES (:key, :value, :type, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                'key' => $key,
                'value' => is_array($value) ? json_encode($value) : (string)$value,
                'type' => is_numeric($value) ? 'number' : (is_bool($value) ? 'boolean' : 'string')
            ]);
        }

        $updated[$key] = $value;
    }

    ApiResponse::success($updated, 'システム設定を更新しました');
}

/**
 * 営業時間取得
 */
function getBusinessHours($pdo) {
    $sql = "SELECT * FROM mst_businessHours ORDER BY day_of_week";

    try {
        $stmt = $pdo->query($sql);
        $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // テーブルがない場合はデフォルト値を返す
        $hours = getDefaultBusinessHours();
    }

    $dayNames = ['日曜日', '月曜日', '火曜日', '水曜日', '木曜日', '金曜日', '土曜日'];

    foreach ($hours as &$h) {
        $h['day_name'] = $dayNames[$h['day_of_week']] ?? '';
    }

    ApiResponse::success($hours);
}

/**
 * 営業時間更新
 */
function updateBusinessHours($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('day_of_week', '曜日は必須です');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    $dayOfWeek = $input['day_of_week'];

    $sql = "SELECT * FROM mst_businessHours WHERE day_of_week = :day";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['day' => $dayOfWeek]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        $updates = [];
        $params = ['day' => $dayOfWeek];

        if (isset($input['open_time'])) {
            $updates[] = "open_time = :open_time";
            $params['open_time'] = $input['open_time'];
        }
        if (isset($input['close_time'])) {
            $updates[] = "close_time = :close_time";
            $params['close_time'] = $input['close_time'];
        }
        if (isset($input['is_closed'])) {
            $updates[] = "is_closed = :is_closed";
            $params['is_closed'] = $input['is_closed'] ? 1 : 0;
        }

        if (!empty($updates)) {
            $sql = "UPDATE mst_businessHours SET " . implode(', ', $updates) . ", upd_dt = NOW() WHERE day_of_week = :day";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        }
    } else {
        $sql = "INSERT INTO mst_businessHours (day_of_week, open_time, close_time, is_closed, add_dt)
                VALUES (:day, :open_time, :close_time, :is_closed, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'day' => $dayOfWeek,
            'open_time' => $input['open_time'] ?? '10:00:00',
            'close_time' => $input['close_time'] ?? '22:00:00',
            'is_closed' => isset($input['is_closed']) && $input['is_closed'] ? 1 : 0
        ]);
    }

    ApiResponse::success(null, '営業時間を更新しました');
}

/**
 * システムログ取得
 */
function getSystemLogs($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 50;
    $offset = ($page - 1) * $perPage;

    $level = $_GET['level'] ?? '';
    $startDate = $_GET['start_date'] ?? '';

    $where = "1=1";
    $params = [];

    if ($level !== '') {
        $where .= " AND level = :level";
        $params['level'] = $level;
    }
    if ($startDate !== '') {
        $where .= " AND created_at >= :start_date";
        $params['start_date'] = $startDate;
    }

    try {
        // カウント
        $countSql = "SELECT COUNT(*) FROM sys_log WHERE {$where}";
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // データ取得
        $sql = "SELECT * FROM sys_log WHERE {$where} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ApiResponse::list($logs, $total, $page, $perPage);
    } catch (Exception $e) {
        // テーブルがない場合は空を返す
        ApiResponse::list([], 0, $page, $perPage);
    }
}

/**
 * メンテナンスモード切替
 */
function toggleMaintenance($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $enabled = isset($input['enabled']) ? (bool)$input['enabled'] : null;
    $message = $input['message'] ?? 'システムメンテナンス中です。しばらくお待ちください。';

    if ($enabled === null) {
        // トグル
        $sql = "SELECT setting_value FROM mst_setting WHERE setting_key = 'maintenance_mode'";
        $stmt = $pdo->query($sql);
        $current = $stmt->fetchColumn();
        $enabled = !($current === '1' || $current === 'true');
    }

    // 更新
    $sql = "INSERT INTO mst_setting (setting_key, setting_value, setting_type, add_dt)
            VALUES ('maintenance_mode', :value, 'boolean', NOW())
            ON DUPLICATE KEY UPDATE setting_value = :value, upd_dt = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['value' => $enabled ? '1' : '0']);

    // メッセージ更新
    $sql = "INSERT INTO mst_setting (setting_key, setting_value, setting_type, add_dt)
            VALUES ('maintenance_message', :message, 'string', NOW())
            ON DUPLICATE KEY UPDATE setting_value = :message, upd_dt = NOW()";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['message' => $message]);

    ApiResponse::success([
        'maintenance_mode' => $enabled,
        'message' => $message
    ], $enabled ? 'メンテナンスモードを有効にしました' : 'メンテナンスモードを無効にしました');
}

// ヘルパー関数
function castSettingValue($value, $type) {
    switch ($type) {
        case 'number':
        case 'integer':
            return (int)$value;
        case 'float':
            return (float)$value;
        case 'boolean':
            return $value === '1' || $value === 'true' || $value === true;
        case 'json':
            return json_decode($value, true);
        default:
            return $value;
    }
}

function getDefaultSettings() {
    return [
        ['setting_key' => 'site_name', 'setting_value' => 'NET8', 'setting_type' => 'string', 'description' => 'サイト名'],
        ['setting_key' => 'maintenance_mode', 'setting_value' => '0', 'setting_type' => 'boolean', 'description' => 'メンテナンスモード'],
        ['setting_key' => 'max_session_time', 'setting_value' => '3600', 'setting_type' => 'number', 'description' => '最大セッション時間（秒）'],
        ['setting_key' => 'default_points', 'setting_value' => '1000', 'setting_type' => 'number', 'description' => 'デフォルトポイント']
    ];
}

function getDefaultBusinessHours() {
    $hours = [];
    for ($i = 0; $i <= 6; $i++) {
        $hours[] = [
            'day_of_week' => $i,
            'open_time' => '10:00:00',
            'close_time' => '22:00:00',
            'is_closed' => 0
        ];
    }
    return $hours;
}
