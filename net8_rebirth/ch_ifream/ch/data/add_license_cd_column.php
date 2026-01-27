<?php
/**
 * Add license_cd Column to mst_cameralist
 *
 * このスクリプトは一度だけ実行し、完了後は削除してください
 */

// セキュリティ: 実行キーが必要
$EXEC_KEY = 'add_column_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Add license_cd Column Script</h1>";

    // Step 1: license_cdカラムの存在確認
    echo "<h2>Step 1: Check if license_cd column exists</h2>";

    $check_sql = "SHOW COLUMNS FROM mst_cameralist LIKE 'license_cd'";
    $column_exists = $DB->getRow($check_sql);

    if ($column_exists) {
        echo "<p style='color:green;'>✓ license_cd column already exists</p>";
    } else {
        echo "<p style='color:orange;'>⚠ license_cd column not found, adding...</p>";

        // Step 2: license_cdカラムを追加
        echo "<h2>Step 2: Add license_cd column</h2>";

        $alter_sql = "ALTER TABLE mst_cameralist ADD COLUMN license_cd varchar(100) DEFAULT NULL AFTER state";
        $result = $DB->query($alter_sql);

        if ($result) {
            echo "<p style='color:green;'>✓ license_cd column added successfully</p>";
        } else {
            echo "<p style='color:red;'>❌ Failed to add license_cd column</p>";
            throw new Exception("Failed to add license_cd column");
        }
    }

    // Step 3: Windows PCレコードの挿入・更新
    echo "<h2>Step 3: Configure Windows PC entry</h2>";

    $new_license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';

    // まず既存レコードを確認
    $check_record = $DB->getRow("SELECT mac_address FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");

    if ($check_record) {
        echo "<p>Record exists, updating...</p>";

        $update_sql = "UPDATE mst_cameralist
                      SET license_cd = '{$new_license_cd}',
                          camera_no = 1,
                          upd_no = 1,
                          upd_dt = NOW()
                      WHERE mac_address = '34-a6-ef-35-73-73'";

        $result = $DB->query($update_sql);
    } else {
        echo "<p>Record not found, inserting...</p>";

        $insert_sql = "INSERT INTO `mst_cameralist`
        (`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
        VALUES
        ('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '{$new_license_cd}', 0, 0, 1, NOW())";

        $result = $DB->query($insert_sql);
    }

    if ($result) {
        echo "<p style='color:green;'>✓ Windows PC entry configured successfully</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to configure Windows PC entry</p>";
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
        echo "<p><strong>Status:</strong> Database ready!</p>";
        echo "</div>";

        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>削除: このスクリプトファイル (add_license_cd_column.php)</li>";
        echo "<li>Windows側で <code>slotserver.exe -c COM4</code> を実行</li>";
        echo "</ol>";
    } else {
        echo "<p style='color:red;'>❌ Windows PC entry not found</p>";
    }

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h2 style='color:red;margin-top:0;'>Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
