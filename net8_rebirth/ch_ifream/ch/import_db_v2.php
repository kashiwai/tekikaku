<?php
/**
 * Database Import Script v2
 *
 * セキュリティ: 実行キーが必要
 */

$EXEC_KEY = 'import_db_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

set_time_limit(300); // 5分のタイムアウト

try {
    $DB = new NetDB();

    echo "<h1>Database Import Script v2</h1>";
    echo "<p>Starting database import...</p>";
    echo "<hr>";

    // Step 1: テーブル作成
    echo "<h2>Step 1: Creating table</h2>";
    $create_table = "CREATE TABLE IF NOT EXISTS `mst_cameralist` (
  `mac_address` varchar(20) NOT NULL,
  `camera_no` int(10) unsigned DEFAULT NULL,
  `license_id` varchar(200) DEFAULT NULL,
  `uuid` varchar(50) DEFAULT NULL,
  `identifing_number` varchar(50) DEFAULT NULL,
  `system_name` varchar(100) DEFAULT NULL,
  `product_name` varchar(100) DEFAULT NULL,
  `cpu_name` varchar(100) DEFAULT NULL,
  `core` int(11) DEFAULT NULL,
  `ip_address` varchar(20) DEFAULT NULL,
  `state` tinyint(4) NOT NULL DEFAULT '0',
  `license_cd` varchar(100) DEFAULT NULL,
  `del_flg` tinyint(4) NOT NULL DEFAULT '0',
  `del_no` int(10) unsigned DEFAULT NULL,
  `del_dt` datetime DEFAULT NULL,
  `add_no` int(10) unsigned DEFAULT NULL,
  `add_dt` datetime DEFAULT NULL,
  `upd_no` int(10) unsigned DEFAULT NULL,
  `upd_dt` datetime DEFAULT NULL,
  PRIMARY KEY (`mac_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    try {
        $DB->query($create_table);
        echo "<p style='color:green;'>✓ Table created successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Error creating table: " . htmlspecialchars($e->getMessage()) . "</p>";
        throw $e;
    }

    // Step 2: データ挿入
    echo "<h2>Step 2: Inserting data</h2>";
    $insert_data = "INSERT INTO `mst_cameralist`
(`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
VALUES
('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c', 0, 0, 1, NOW())
ON DUPLICATE KEY UPDATE
license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c',
camera_no = 1,
license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI='";

    try {
        $DB->query($insert_data);
        echo "<p style='color:green;'>✓ Data inserted successfully</p>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>✗ Error inserting data: " . htmlspecialchars($e->getMessage()) . "</p>";
        throw $e;
    }

    // Step 3: 検証
    echo "<h2>Step 3: Verification</h2>";
    $row = $DB->getRow("SELECT * FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");

    if ($row) {
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:green;margin-top:0;'>✅ Success!</h3>";
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>Camera No:</strong> {$row['camera_no']}</p>";
        echo "<p><strong>License CD:</strong> {$row['license_cd']}</p>";
        echo "<p><strong>Status:</strong> Database ready!</p>";
        echo "</div>";

        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>このスクリプトファイルとimport_db.phpを削除してください</li>";
        echo "<li>Windows側で <code>slotserver.exe -c COM4</code> を実行してください</li>";
        echo "</ol>";
    } else {
        echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:red;margin-top:0;'>❌ Verification Failed</h3>";
        echo "<p>Data was not found in the database.</p>";
        echo "</div>";
    }

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h2 style='color:red;margin-top:0;'>Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
