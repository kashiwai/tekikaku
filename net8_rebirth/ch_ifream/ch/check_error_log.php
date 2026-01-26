<?php
/**
 * Apacheエラーログ確認
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 Apacheエラーログ確認</h1>";
echo "<hr>";

echo "<h2>📂 エラーログファイルの場所</h2>";
echo "<p>標準: /var/log/apache2/error.log</p>";

echo "<hr>";
echo "<h2>📋 最新のエラーログ（最後の50行）</h2>";

$error_log_path = '/var/log/apache2/error.log';

if (file_exists($error_log_path)) {
    echo "<pre>";
    system("tail -50 $error_log_path");
    echo "</pre>";
} else {
    echo "<p>❌ エラーログファイルが見つかりません: $error_log_path</p>";

    // 代替パスを試す
    $alt_paths = [
        '/var/log/httpd/error_log',
        '/var/log/apache2/error_log',
        '/usr/local/apache2/logs/error_log',
    ];

    echo "<h3>代替パスを確認中...</h3>";
    foreach ($alt_paths as $path) {
        if (file_exists($path)) {
            echo "<p>✅ 発見: $path</p>";
            echo "<pre>";
            system("tail -50 $path");
            echo "</pre>";
            break;
        }
    }
}

echo "<hr>";
echo "<h2>🔍 PHP設定</h2>";
echo "<p>display_errors: " . ini_get('display_errors') . "</p>";
echo "<p>error_reporting: " . error_reporting() . "</p>";
echo "<p>log_errors: " . ini_get('log_errors') . "</p>";
echo "<p>error_log: " . ini_get('error_log') . "</p>";
?>
