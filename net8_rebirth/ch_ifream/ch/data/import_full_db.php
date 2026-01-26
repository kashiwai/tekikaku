<?php
/**
 * Full Database Import Script
 *
 * セキュリティ: 実行キーが必要
 */

$EXEC_KEY = 'import_db_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

set_time_limit(600); // 10分のタイムアウト

try {
    $DB = new NetDB();

    echo "<h1>Full Database Import Script</h1>";
    echo "<p>This will import the complete database schema and initial data.</p>";
    echo "<hr>";

    // Step 1: テーブル構造をインポート
    echo "<h2>Step 1: Importing table structures</h2>";
    $create_sql = file_get_contents(__DIR__ . '/net8_create.sql');

    if ($create_sql === false) {
        throw new Exception("Could not read net8_create.sql");
    }

    echo "<p>File size: " . strlen($create_sql) . " bytes</p>";
    echo "<p>Executing SQL...</p>";

    // SQLを実行（複数のステートメント）
    $statements = explode(';', $create_sql);
    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*!') === 0) {
            continue;
        }

        try {
            $DB->query($statement);
            $success_count++;
        } catch (Exception $e) {
            $error_count++;
            echo "<p style='color:orange;'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "<p style='color:green;'>✓ Structure import completed: {$success_count} statements executed, {$error_count} warnings</p>";

    // Step 2: 初期データをインポート
    echo "<h2>Step 2: Importing initial data</h2>";
    $init_sql = file_get_contents(__DIR__ . '/net8_init.sql');

    if ($init_sql === false) {
        throw new Exception("Could not read net8_init.sql");
    }

    echo "<p>File size: " . strlen($init_sql) . " bytes</p>";
    echo "<p>Executing SQL...</p>";

    $statements = explode(';', $init_sql);
    $success_count = 0;
    $error_count = 0;

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '/*!') === 0) {
            continue;
        }

        try {
            $DB->query($statement);
            $success_count++;
        } catch (Exception $e) {
            $error_count++;
            echo "<p style='color:orange;'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    echo "<p style='color:green;'>✓ Data import completed: {$success_count} statements executed, {$error_count} warnings</p>";

    // Step 3: Windows PC用のレコードを挿入・更新
    echo "<h2>Step 3: Configuring Windows PC entry</h2>";

    $update_sql = "INSERT INTO `mst_cameralist`
    (`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
    VALUES
    ('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c', 0, 0, 1, NOW())
    ON DUPLICATE KEY UPDATE
    license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c',
    camera_no = 1";

    try {
        $DB->query($update_sql);
        echo "<p style='color:green;'>✓ Windows PC entry updated</p>";
    } catch (Exception $e) {
        echo "<p style='color:orange;'>⚠ Warning: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // Step 4: 検証
    echo "<h2>Step 4: Verification</h2>";

    $table_count = $DB->getRow("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = 'net8_dev'");
    echo "<p><strong>Total tables:</strong> {$table_count['cnt']}</p>";

    $cameralist = $DB->getRow("SELECT * FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");
    if ($cameralist) {
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:green;margin-top:0;'>✅ Success!</h3>";
        echo "<p><strong>MAC Address:</strong> {$cameralist['mac_address']}</p>";
        echo "<p><strong>Camera No:</strong> {$cameralist['camera_no']}</p>";
        echo "<p><strong>License ID:</strong> {$cameralist['license_id']}</p>";
        echo "<p><strong>Status:</strong> Database ready!</p>";
        echo "</div>";

        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>削除: このスクリプトファイル、net8_create.sql、net8_init.sql</li>";
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
