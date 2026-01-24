<?php
/**
 * Database Import Script
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

    echo "<h1>Database Import Script</h1>";
    echo "<p>Starting database import...</p>";

    // SQLファイルの内容（インライン）
    $sql = <<<'SQL'
-- ここにSQLダンプの内容を貼り付ける
-- 一旦、必要最小限のテーブルだけ作成
CREATE TABLE IF NOT EXISTS `mst_cameralist` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Windows PC用のレコードを挿入
INSERT INTO `mst_cameralist`
(`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
VALUES
('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c', 0, 0, 1, NOW())
ON DUPLICATE KEY UPDATE
license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
SQL;

    // SQLを実行
    $statements = explode(';', $sql);
    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }

        try {
            $DB->query($statement);
            $success_count++;
            echo "<p style='color:green;'>✓ Statement executed successfully</p>";
        } catch (Exception $e) {
            $error_count++;
            echo "<p style='color:red;'>✗ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "<h2>Import Summary</h2>";
    echo "<p><strong>Success:</strong> {$success_count} statements</p>";
    echo "<p><strong>Errors:</strong> {$error_count} statements</p>";

    // 確認
    $row = $DB->getRow("SELECT * FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");
    if ($row) {
        echo "<h2>✅ Verification</h2>";
        echo "<p><strong>MAC Address:</strong> {$row['mac_address']}</p>";
        echo "<p><strong>license_cd:</strong> {$row['license_cd']}</p>";
        echo "<p><strong>Status:</strong> Database ready!</p>";

        echo "<h3>Next Steps:</h3>";
        echo "<ol>";
        echo "<li>このスクリプトファイル (import_db.php) を削除してください</li>";
        echo "<li>Windows側で <code>slotserver.exe -c COM4</code> を実行してください</li>";
        echo "</ol>";
    } else {
        echo "<h2 style='color:red;'>❌ Verification Failed</h2>";
        echo "<p>Data was not inserted properly.</p>";
    }

} catch (Exception $e) {
    echo "<h2 style='color:red;'>Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
