<?php
/**
 * SmartDB Connection Test
 */

require_once('./_etc/setting.php');
require_once('./_lib/SmartDB.php');

header('Content-Type: text/html; charset=utf-8');

echo "<h1>SmartDB Connection Test</h1>";

echo "<h2>DB_DSN:</h2>";
echo "<pre>" . DB_DSN . "</pre>";

try {
    $db = new SmartDB(DB_DSN);

    echo "<p style='color: green;'>✅ SmartDB connection successful!</p>";

    // Test query to check tables
    echo "<h2>Checking dat_client_message table:</h2>";

    $sql = "SHOW TABLES LIKE 'dat_client_message'";
    $result = $db->query($sql);

    if ($result) {
        $rows = $db->fetchAll($result);
        if (count($rows) > 0) {
            echo "<p style='color: green; font-size: 20px;'>✅ dat_client_message table EXISTS via SmartDB!</p>";

            // Try to describe the table
            $sql2 = "DESCRIBE dat_client_message";
            $result2 = $db->query($sql2);
            $columns = $db->fetchAll($result2);

            echo "<h3>Table Structure:</h3>";
            echo "<pre>";
            print_r($columns);
            echo "</pre>";
        } else {
            echo "<p style='color: red; font-size: 20px;'>❌ dat_client_message table DOES NOT EXIST via SmartDB!</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Query failed!</p>";
    }

    // Show all tables
    echo "<h3>All Tables:</h3>";
    $sql3 = "SHOW TABLES";
    $result3 = $db->query($sql3);
    $tables = $db->fetchAll($result3);
    echo "<pre>";
    print_r($tables);
    echo "</pre>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error:</p>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
