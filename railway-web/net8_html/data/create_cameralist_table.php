<?php
/**
 * Create mst_cameralist Table with license_cd
 *
 * このスクリプトは一度だけ実行し、完了後は削除してください
 */

// セキュリティ: 実行キーが必要
$EXEC_KEY = 'create_table_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Create mst_cameralist Table</h1>";

    // Step 1: テーブルを削除（存在する場合）
    echo "<h2>Step 1: Drop existing table (if exists)</h2>";

    try {
        $DB->query("DROP TABLE IF EXISTS mst_cameralist");
        echo "<p style='color:green;'>✓ Dropped existing table (if any)</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Step 2: テーブルを作成（license_cdカラム付き）
    echo "<h2>Step 2: Create mst_cameralist table with license_cd column</h2>";

    $create_table = "CREATE TABLE `mst_cameralist` (
      `mac_address` varchar(17) NOT NULL,
      `state` int(10) unsigned DEFAULT NULL,
      `camera_no` int(10) unsigned DEFAULT NULL,
      `system_name` varchar(64) DEFAULT NULL,
      `ip_address` varchar(17) DEFAULT NULL,
      `identifing_number` varchar(32) DEFAULT NULL,
      `product_name` varchar(128) DEFAULT NULL,
      `cpu_name` varchar(64) DEFAULT NULL,
      `core` int(10) unsigned DEFAULT NULL,
      `uuid` varchar(64) DEFAULT NULL,
      `license_id` varchar(128) DEFAULT NULL,
      `license_cd` varchar(100) DEFAULT NULL,
      `add_no` int(10) unsigned DEFAULT NULL,
      `add_dt` datetime DEFAULT NULL,
      `upd_no` int(10) unsigned DEFAULT NULL,
      `upd_dt` datetime DEFAULT NULL,
      `del_flg` tinyint(4) NOT NULL DEFAULT '0',
      `del_no` int(10) unsigned DEFAULT NULL,
      `del_dt` datetime DEFAULT NULL,
      PRIMARY KEY (`mac_address`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $result = $DB->query($create_table);

    if ($result !== false) {
        echo "<p style='color:green;'>✓ Table created successfully with license_cd column</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to create table</p>";
        throw new Exception("Failed to create mst_cameralist table");
    }

    // Step 3: Windows PCレコードを挿入
    echo "<h2>Step 3: Insert Windows PC entry</h2>";

    $insert_sql = "INSERT INTO `mst_cameralist`
    (`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
    VALUES
    ('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c', 0, 0, 1, NOW())";

    $result = $DB->query($insert_sql);

    if ($result !== false) {
        echo "<p style='color:green;'>✓ Windows PC entry inserted successfully</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to insert Windows PC entry</p>";
    }

    // Step 4: 検証
    echo "<h2>Step 4: Verification</h2>";

    $row = $DB->getRow("SELECT * FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");

    if ($row) {
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:green;margin-top:0;'>✅ Success!</h3>";
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>Camera No:</strong> {$row['camera_no']}</p>";
        echo "<p><strong>License ID:</strong> {$row['license_id']}</p>";
        echo "<p><strong>License CD:</strong> {$row['license_cd']}</p>";
        echo "<p><strong>State:</strong> {$row['state']}</p>";
        echo "<p><strong>Status:</strong> Database ready!</p>";
        echo "</div>";

        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Windows側で <code>cd C:\\serverset</code> を実行</li>";
        echo "<li><code>.\\slotserver.exe -c COM4</code> を実行</li>";
        echo "<li>接続が成功したら、このスクリプトファイルを削除してください</li>";
        echo "</ol>";
    } else {
        echo "<p style='color:red;'>❌ Windows PC entry not found</p>";
    }

    // Step 5: テーブル構造を表示
    echo "<h2>Step 5: Table Structure</h2>";
    $columns = $DB->query("SHOW COLUMNS FROM mst_cameralist");
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($col = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h2 style='color:red;margin-top:0;'>Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
