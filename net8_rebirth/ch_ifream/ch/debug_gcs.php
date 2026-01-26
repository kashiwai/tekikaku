<?php
/**
 * GCS デバッグ用スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=== GCS Debug Info ===\n\n";

// 1. 設定ファイルの読み込み
echo "1. Loading settings...\n";
try {
    require_once('_etc/require_files.php');
    echo "   Settings loaded OK\n";
} catch (Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 2. GCS_ENABLED確認
echo "\n2. GCS_ENABLED check...\n";
echo "   GCS_ENABLED defined: " . (defined('GCS_ENABLED') ? 'YES' : 'NO') . "\n";
if (defined('GCS_ENABLED')) {
    echo "   GCS_ENABLED value: " . (GCS_ENABLED ? 'true' : 'false') . "\n";
}

// 3. 環境変数確認
echo "\n3. Environment variables...\n";
echo "   GCS_ENABLED env: " . (getenv('GCS_ENABLED') ?: 'not set') . "\n";
echo "   GCS_BUCKET_NAME env: " . (getenv('GCS_BUCKET_NAME') ?: 'not set') . "\n";
echo "   GCS_PROJECT_ID env: " . (getenv('GCS_PROJECT_ID') ?: 'not set') . "\n";
echo "   GCS_KEY_JSON env: " . (getenv('GCS_KEY_JSON') ? 'SET (length: ' . strlen(getenv('GCS_KEY_JSON')) . ')' : 'not set') . "\n";
echo "   GCS_KEY_FILE env: " . (getenv('GCS_KEY_FILE') ?: 'not set') . "\n";

// 4. vendor/autoload.php確認
echo "\n4. Composer autoload check...\n";
$autoloadPath = __DIR__ . '/vendor/autoload.php';
echo "   Path: $autoloadPath\n";
echo "   Exists: " . (file_exists($autoloadPath) ? 'YES' : 'NO') . "\n";

// 5. CloudStorageHelper読み込み
echo "\n5. CloudStorageHelper loading...\n";
$gcsHelperPath = __DIR__ . '/_sys/CloudStorageHelper.php';
echo "   Path: $gcsHelperPath\n";
echo "   Exists: " . (file_exists($gcsHelperPath) ? 'YES' : 'NO') . "\n";

if (file_exists($gcsHelperPath)) {
    try {
        require_once($gcsHelperPath);
        echo "   Loaded OK\n";
    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
}

// 6. CloudStorageHelperクラス確認
echo "\n6. CloudStorageHelper class check...\n";
echo "   Class exists: " . (class_exists('CloudStorageHelper') ? 'YES' : 'NO') . "\n";

// 7. CloudStorageHelper初期化
echo "\n7. CloudStorageHelper initialization...\n";
if (class_exists('CloudStorageHelper')) {
    try {
        $gcs = new CloudStorageHelper();
        echo "   Instance created OK\n";
        echo "   isEnabled(): " . ($gcs->isEnabled() ? 'true' : 'false') . "\n";
    } catch (Exception $e) {
        echo "   ERROR: " . $e->getMessage() . "\n";
    }
}

// 8. DIR_IMG_NOTICE確認
echo "\n8. DIR_IMG_NOTICE check...\n";
echo "   DIR_IMG_NOTICE defined: " . (defined('DIR_IMG_NOTICE') ? 'YES' : 'NO') . "\n";
if (defined('DIR_IMG_NOTICE')) {
    echo "   DIR_IMG_NOTICE value: " . DIR_IMG_NOTICE . "\n";
    echo "   Directory exists: " . (is_dir(DIR_IMG_NOTICE) ? 'YES' : 'NO') . "\n";
    echo "   Directory writable: " . (is_writable(DIR_IMG_NOTICE) ? 'YES' : 'NO') . "\n";
}

// 9. Google Cloud Storage SDKクラス確認
echo "\n9. Google Cloud Storage SDK check...\n";
echo "   StorageClient class exists: " . (class_exists('Google\Cloud\Storage\StorageClient') ? 'YES' : 'NO') . "\n";

// 10. PHP情報
echo "\n10. PHP info...\n";
echo "   PHP version: " . phpversion() . "\n";
echo "   Memory limit: " . ini_get('memory_limit') . "\n";
echo "   Upload max filesize: " . ini_get('upload_max_filesize') . "\n";
echo "   Post max size: " . ini_get('post_max_size') . "\n";

echo "\n=== End Debug Info ===\n";
echo "</pre>";
?>
