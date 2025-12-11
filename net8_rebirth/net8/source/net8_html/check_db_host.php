<?php
/**
 * データベース接続先確認
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/_etc/setting.php');

echo "<h1>データベース接続先確認</h1>";

echo "<h2>環境変数</h2>";
echo "<pre>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASSWORD: " . (DB_PASSWORD ? "****" : "未設定") . "\n";
echo "</pre>";

echo "<h2>実際の接続確認</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD
    );

    // MySQLバージョン確認
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    echo "<p>✅ 接続成功</p>";
    echo "<p><strong>MySQLバージョン:</strong> $version</p>";

    // ホスト情報
    $host_info = $pdo->query("SELECT @@hostname")->fetchColumn();
    echo "<p><strong>ホスト名:</strong> $host_info</p>";

    // テーブル数
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<p><strong>テーブル数:</strong> " . count($tables) . "</p>";

} catch (PDOException $e) {
    echo "<p>❌ 接続失敗: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
