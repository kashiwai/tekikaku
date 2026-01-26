<?php
/**
 * ファイルシステム確認
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 ファイルシステム確認</h1>";
echo "<hr>";

echo "<h2>📂 /var/www/html の内容</h2>";
echo "<pre>";
system('ls -la /var/www/html');
echo "</pre>";

echo "<hr>";
echo "<h2>📂 /var/www/html/data の内容</h2>";
echo "<pre>";
system('ls -la /var/www/html/data');
echo "</pre>";

echo "<hr>";
echo "<h2>📂 _etcディレクトリの存在確認</h2>";

$paths_to_check = [
    '/var/www/html/_etc',
    '/var/www/html/data/_etc',
];

foreach ($paths_to_check as $path) {
    if (is_dir($path)) {
        echo "<p>✅ <strong>$path</strong> が存在します</p>";
        echo "<pre>";
        system("ls -la $path");
        echo "</pre>";
    } else {
        echo "<p>❌ <strong>$path</strong> が存在しません</p>";
    }
}

echo "<hr>";
echo "<h2>🔍 require_files.phpの検索</h2>";
echo "<pre>";
system('find /var/www/html -name "require_files.php" 2>/dev/null');
echo "</pre>";

echo "<hr>";
echo "<h2>📦 Dockerイメージ情報</h2>";
echo "<p>ホスト名: " . gethostname() . "</p>";
echo "<p>PHP バージョン: " . phpversion() . "</p>";
?>
