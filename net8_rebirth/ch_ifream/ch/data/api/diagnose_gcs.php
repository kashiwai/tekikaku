<?php
/**
 * GCS診断スクリプト - 環境変数とGCS設定を確認
 */
header('Content-Type: application/json; charset=UTF-8');

$diagnostics = [];

// 1. 環境変数チェック
$diagnostics['env_vars'] = [
    'GCS_ENABLED' => getenv('GCS_ENABLED'),
    'GCS_PROJECT_ID' => getenv('GCS_PROJECT_ID'),
    'GCS_BUCKET_NAME' => getenv('GCS_BUCKET_NAME'),
    'GCS_KEY_JSON' => !empty(getenv('GCS_KEY_JSON')) ? '✅ SET (' . strlen(getenv('GCS_KEY_JSON')) . ' chars)' : '❌ NOT SET',
    'GCS_KEY_FILE' => getenv('GCS_KEY_FILE')
];

// 2. 定数チェック
require_once(__DIR__ . '/../../_etc/setting.php');
$diagnostics['constants'] = [
    'GCS_ENABLED' => defined('GCS_ENABLED') ? (GCS_ENABLED ? 'true' : 'false') : 'undefined',
    'GCS_PROJECT_ID' => defined('GCS_PROJECT_ID') ? GCS_PROJECT_ID : 'undefined',
    'GCS_BUCKET_NAME' => defined('GCS_BUCKET_NAME') ? GCS_BUCKET_NAME : 'undefined',
    'GCS_KEY_FILE' => defined('GCS_KEY_FILE') ? GCS_KEY_FILE : 'undefined'
];

// 3. Composerライブラリチェック
$autoloadPath = __DIR__ . '/../../../../vendor/autoload.php';
$diagnostics['composer'] = [
    'autoload_exists' => file_exists($autoloadPath) ? '✅ EXISTS' : '❌ NOT FOUND',
    'autoload_path' => $autoloadPath
];

// 4. CloudStorageHelperクラスチェック
$helperPath = __DIR__ . '/../../_sys/CloudStorageHelper.php';
$diagnostics['helper_class'] = [
    'file_exists' => file_exists($helperPath) ? '✅ EXISTS' : '❌ NOT FOUND',
    'file_path' => $helperPath
];

// 5. CloudStorageHelper初期化テスト
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}
if (file_exists($helperPath)) {
    require_once $helperPath;

    try {
        $gcs = new CloudStorageHelper();
        $diagnostics['gcs_init'] = [
            'success' => '✅ Class instantiated',
            'is_enabled' => $gcs->isEnabled() ? '✅ ENABLED' : '❌ DISABLED'
        ];
    } catch (Exception $e) {
        $diagnostics['gcs_init'] = [
            'success' => '❌ FAILED',
            'error' => $e->getMessage()
        ];
    }
} else {
    $diagnostics['gcs_init'] = [
        'success' => '❌ Cannot test - CloudStorageHelper.php not found'
    ];
}

// 6. データベースの画像パスチェック（サンプル5件）
try {
    $db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
    $db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
    $db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
    $db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->query("
        SELECT model_cd, model_name, image_list
        FROM mst_model
        WHERE del_flg = 0
        ORDER BY model_no
        LIMIT 10
    ");

    $diagnostics['database_images'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    $diagnostics['database_images'] = [
        'error' => $e->getMessage()
    ];
}

// 7. Railway環境検出
$diagnostics['environment'] = [
    'RAILWAY_ENVIRONMENT' => getenv('RAILWAY_ENVIRONMENT') ?: 'Not Railway',
    'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
