<?php
/**
 * Railway DB接続情報を表示
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

echo "<h1>Railway DB接続情報</h1>";
echo "<pre>";
echo "Host: $db_host\n";
echo "Database: $db_name\n";
echo "User: $db_user\n";
echo "Password: $db_password\n";
echo "\n";
echo "接続コマンド:\n";
echo "mysql -h $db_host -u $db_user -p'$db_password' $db_name\n";
echo "</pre>";
?>
