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

    echo "<h1>License CD Update Script</h1>";
    echo "<h2>Step 1: 現在の値を確認</h2>";

    // 現在の値を確認
    $sql = "SELECT mac_address, license_cd FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'";
    $row = $DB->getRow($sql);

    if ($row) {
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>Current license_cd:</strong> {$row['license_cd']}</p>";
    } else {
        die("<p style='color:red;'>ERROR: MAC address not found in database</p>");
    }

    echo "<h2>Step 2: 値を更新</h2>";

    // 更新
    $new_license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
    $sql = "UPDATE mst_cameralist SET license_cd = '{$new_license_cd}' WHERE mac_address = '34-a6-ef-35-73-73'";

    $result = $DB->query($sql);

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
