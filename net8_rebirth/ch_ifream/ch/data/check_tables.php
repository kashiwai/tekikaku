<?php
/**
 * Check Database Tables
 */

$EXEC_KEY = 'check_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Database Tables Check</h1>";

    // 必要なテーブルのリスト
    $required_tables = [
        'mst_cameralist',
        'mst_camera',
        'dat_machine',
        'mst_model'
    ];

    echo "<h2>Required Tables Status</h2>";
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";

    foreach ($required_tables as $table) {
        try {
            $count = $DB->getRow("SELECT COUNT(*) as cnt FROM {$table}");
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td style='color:green;'>✓ Exists</td>";
            echo "<td>{$count['cnt']}</td>";
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td>{$table}</td>";
            echo "<td style='color:red;'>❌ Not Found</td>";
            echo "<td>-</td>";
            echo "</tr>";
        }
    }
    echo "</table>";

    // mst_cameralistのレコード詳細
    echo "<h2>mst_cameralist Records</h2>";
    try {
        $rows = $DB->query("SELECT * FROM mst_cameralist");
        if ($rows) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            echo "<tr><th>MAC Address</th><th>Camera No</th><th>License ID</th><th>License CD</th><th>State</th></tr>";
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>{$row['mac_address']}</td>";
                echo "<td>{$row['camera_no']}</td>";
                echo "<td>" . substr($row['license_id'], 0, 20) . "...</td>";
                echo "<td>" . substr($row['license_cd'], 0, 20) . "...</td>";
                echo "<td>{$row['state']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // 全テーブル一覧
    echo "<h2>All Tables in Database</h2>";
    $tables = $DB->query("SHOW TABLES");
    echo "<ul>";
    $count = 0;
    while ($table = $tables->fetch(PDO::FETCH_NUM)) {
        echo "<li>{$table[0]}</li>";
        $count++;
    }
    echo "</ul>";
    echo "<p><strong>Total tables: {$count}</strong></p>";

} catch (Exception $e) {
    echo "<p style='color:red;'><strong>EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
