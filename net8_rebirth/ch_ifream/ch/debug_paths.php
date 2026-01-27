<?php
echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Path Debug</title></head><body>";
echo "<h1>Railway Debug - Path情報</h1>";
echo "<p>現在時刻: " . date('Y-m-d H:i:s') . "</p>";

echo "<h2>📁 ディレクトリ構造</h2>";
echo "<pre>";
system('ls -la /var/www/html/ 2>&1');
echo "</pre>";

echo "<h2>📁 data/xxxadmin ディレクトリ</h2>";
echo "<pre>";
system('ls -la /var/www/html/data/xxxadmin/ 2>&1');
echo "</pre>";

echo "<h2>🔍 login.phpファイル探索</h2>";
echo "<pre>";
system('find /var/www/html -name "login.php" -type f 2>&1');
echo "</pre>";

echo "<h2>🌐 現在のDocument Root</h2>";
echo "<p>" . $_SERVER['DOCUMENT_ROOT'] . "</p>";

echo "<h2>📝 PHP情報</h2>";
echo "<p>現在のスクリプト: " . __FILE__ . "</p>";
echo "<p>現在のディレクトリ: " . getcwd() . "</p>";

echo "<h2>🔗 URL構造推定</h2>";
echo "<p>推定管理画面URL: <a href='/data/xxxadmin/login.php'>/data/xxxadmin/login.php</a></p>";

echo "</body></html>";
?>