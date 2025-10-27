<?php
/**
 * License CD Update Script
 *
 * このスクリプトは一度だけ実行し、完了後は削除してください
 */

// セキュリティ: 実行キーが必要
$EXEC_KEY = 'update_license_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Database Setup & License CD Update Script</h1>";

    // Step 0: テーブルが存在するか確認し、なければ作成
    echo "<h2>Step 0: テーブル確認・作成</h2>";
    try {
        $check = $DB->query("SELECT 1 FROM mst_cameralist LIMIT 1");
        echo "<p style='color:green;'>✓ Table exists</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠ Table not found, creating...</p>";

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

        $DB->query($create_table);
        echo "<p style='color:green;'>✓ Table created successfully</p>";
    }

    echo "<h2>Step 1: 現在の値を確認</h2>";

    // 現在の値を確認
    $sql = "SELECT mac_address, license_cd FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'";
    $row = $DB->getRow($sql);

    if ($row) {
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>Current license_cd:</strong> {$row['license_cd']}</p>";

        echo "<h2>Step 2: 値を更新</h2>";

        // 更新
        $new_license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
        $sql = "UPDATE mst_cameralist SET license_cd = '{$new_license_cd}' WHERE mac_address = '34-a6-ef-35-73-73'";

        $result = $DB->query($sql);
    } else {
        echo "<p style='color:orange;'>⚠ MAC address not found, inserting new record...</p>";

        echo "<h2>Step 2: 新規レコード挿入</h2>";

        $new_license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
        $sql = "INSERT INTO `mst_cameralist`
        (`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
        VALUES
        ('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '{$new_license_cd}', 0, 0, 1, NOW())";

        $result = $DB->query($sql);
    }

    if ($result) {
        echo "<p style='color:green;'><strong>SUCCESS:</strong> license_cd updated</p>";
    } else {
        echo "<p style='color:red;'><strong>ERROR:</strong> Update failed</p>";
    }

    echo "<h2>Step 3: 更新後の値を確認</h2>";

    // 更新後の値を確認
    $sql = "SELECT mac_address, license_cd FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'";
    $row = $DB->getRow($sql);

    if ($row) {
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>New license_cd:</strong> {$row['license_cd']}</p>";

        if ($row['license_cd'] === $new_license_cd) {
            echo "<h3 style='color:green;'>✅ 更新成功！</h3>";
            echo "<p><strong>次のステップ:</strong></p>";
            echo "<ol>";
            echo "<li>このスクリプトファイル (update_license.php) を削除してください</li>";
            echo "<li>Windows側で <code>slotserver.exe -c COM4</code> を再実行してください</li>";
            echo "</ol>";
        } else {
            echo "<h3 style='color:red;'>❌ 更新失敗</h3>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red;'><strong>EXCEPTION:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
