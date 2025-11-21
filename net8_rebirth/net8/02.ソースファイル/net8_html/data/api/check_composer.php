<?php
/**
 * Composer インストール状態確認スクリプト
 */
header('Content-Type: application/json; charset=UTF-8');

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

// 1. composer.json 存在確認
$composer_json_path = '/var/www/html/composer.json';
$result['checks']['composer_json'] = [
    'path' => $composer_json_path,
    'exists' => file_exists($composer_json_path),
    'readable' => is_readable($composer_json_path)
];

if (file_exists($composer_json_path)) {
    $result['checks']['composer_json']['content'] = json_decode(file_get_contents($composer_json_path), true);
}

// 2. composer.lock 存在確認
$composer_lock_path = '/var/www/html/composer.lock';
$result['checks']['composer_lock'] = [
    'path' => $composer_lock_path,
    'exists' => file_exists($composer_lock_path)
];

// 3. vendor ディレクトリ存在確認
$vendor_path = '/var/www/html/vendor';
$result['checks']['vendor_directory'] = [
    'path' => $vendor_path,
    'exists' => file_exists($vendor_path),
    'is_dir' => is_dir($vendor_path)
];

if (is_dir($vendor_path)) {
    $result['checks']['vendor_directory']['contents'] = scandir($vendor_path);
}

// 4. vendor/autoload.php 存在確認
$autoload_path = '/var/www/html/vendor/autoload.php';
$result['checks']['autoload'] = [
    'path' => $autoload_path,
    'exists' => file_exists($autoload_path)
];

// 5. Google Cloud Storage パッケージ確認
$gcs_path = '/var/www/html/vendor/google/cloud-storage';
$result['checks']['gcs_package'] = [
    'path' => $gcs_path,
    'exists' => file_exists($gcs_path),
    'is_dir' => is_dir($gcs_path)
];

if (is_dir($gcs_path)) {
    // composer.json を確認してバージョン情報取得
    $gcs_composer_json = $gcs_path . '/composer.json';
    if (file_exists($gcs_composer_json)) {
        $gcs_info = json_decode(file_get_contents($gcs_composer_json), true);
        $result['checks']['gcs_package']['version'] = $gcs_info['version'] ?? 'unknown';
        $result['checks']['gcs_package']['name'] = $gcs_info['name'] ?? 'unknown';
    }
}

// 6. StorageClient クラス存在確認
$result['checks']['storage_client_class'] = [
    'available' => class_exists('Google\\Cloud\\Storage\\StorageClient')
];

// 7. PHP情報
$result['php_info'] = [
    'version' => PHP_VERSION,
    'include_path' => get_include_path()
];

// 8. 総合判定
$all_ok =
    $result['checks']['composer_json']['exists'] &&
    $result['checks']['vendor_directory']['exists'] &&
    $result['checks']['autoload']['exists'] &&
    $result['checks']['gcs_package']['exists'] &&
    $result['checks']['storage_client_class']['available'];

$result['status'] = $all_ok ? '✅ ALL OK' : '❌ MISSING COMPONENTS';

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
