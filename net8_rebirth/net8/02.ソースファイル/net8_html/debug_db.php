<?php
/**
 * Database Connection Debug Script
 */

require_once('./_etc/setting.php');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>Database Connection Debug</h1>";

echo "<h2>Environment Variables:</h2>";
echo "<pre>";
echo "DB_HOST: " . DB_HOST . "\n";
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASSWORD: " . (DB_PASSWORD ? '[SET]' : '[NOT SET]') . "\n";
echo "\n";
echo "Raw Environment Variables:\n";
echo "MYSQL_HOST: " . (getenv('MYSQL_HOST') ?: '[NOT SET]') . "\n";
echo "MYSQLHOST: " . (getenv('MYSQLHOST') ?: '[NOT SET]') . "\n";
echo "RAILWAY_PRIVATE_DOMAIN: " . (getenv('RAILWAY_PRIVATE_DOMAIN') ?: '[NOT SET]') . "\n";
echo "</pre>";

echo "<h2>Connection Test:</h2>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "<p style='color: green;'>✅ Connection successful!</p>";

    // Check current database
    $stmt = $pdo->query("SELECT DATABASE() as current_db");
    $result = $stmt->fetch();
    echo "<p>Current Database: <strong>" . $result['current_db'] . "</strong></p>";

    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'dat_client_message'");
    $tables = $stmt->fetchAll();

    echo "<h3>Table Check:</h3>";
    if (count($tables) > 0) {
        echo "<p style='color: green;'>✅ dat_client_message table EXISTS</p>";

        // Show table structure
        $stmt = $pdo->query("DESCRIBE dat_client_message");
        $columns = $stmt->fetchAll();
        echo "<pre>";
        print_r($columns);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>❌ dat_client_message table DOES NOT EXIST</p>";

        // Show all tables
        echo "<h3>Available Tables:</h3>";
        $stmt = $pdo->query("SHOW TABLES");
        $all_tables = $stmt->fetchAll();
        echo "<pre>";
        print_r($all_tables);
        echo "</pre>";
    }

} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Connection failed!</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
