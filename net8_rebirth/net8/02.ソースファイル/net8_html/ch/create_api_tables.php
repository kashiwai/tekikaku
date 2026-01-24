<?php
/**
 * Create API Required Tables (mst_camera, dat_machine, mst_model)
 *
 * このスクリプトは一度だけ実行し、完了後は削除してください
 */

// セキュリティ: 実行キーが必要
$EXEC_KEY = 'create_api_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

// データベース接続
require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Create API Required Tables</h1>";

    // Step 1: mst_camera テーブルを作成
    echo "<h2>Step 1: Create mst_camera table</h2>";

    $DB->query("DROP TABLE IF EXISTS mst_camera");

    $create_mst_camera = "CREATE TABLE `mst_camera` (
      `camera_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `camera_mac` varchar(17) NOT NULL,
      `camera_name` varchar(32) NOT NULL,
      `del_flg` tinyint(4) NOT NULL DEFAULT '0',
      `del_no` int(10) unsigned DEFAULT NULL,
      `del_dt` datetime DEFAULT NULL,
      `add_no` int(10) unsigned DEFAULT NULL,
      `add_dt` datetime DEFAULT NULL,
      `upd_no` int(10) unsigned DEFAULT NULL,
      `upd_dt` datetime DEFAULT NULL,
      PRIMARY KEY (`camera_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $DB->query($create_mst_camera);
    echo "<p style='color:green;'>✓ mst_camera table created</p>";

    // Windows PC camera entry
    $DB->query("INSERT INTO mst_camera (camera_no, camera_mac, camera_name, add_no, add_dt)
                VALUES (1, '34-a6-ef-35-73-73', 'Windows PC Camera', 1, NOW())");
    echo "<p style='color:green;'>✓ Camera entry inserted (camera_no=1)</p>";

    // Step 2: mst_model テーブルを作成
    echo "<h2>Step 2: Create mst_model table</h2>";

    $DB->query("DROP TABLE IF EXISTS mst_model");

    $create_mst_model = "CREATE TABLE `mst_model` (
      `model_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `category` tinyint(4) NOT NULL,
      `model_cd` varchar(20) NOT NULL,
      `model_name` varchar(50) NOT NULL,
      `model_roman` varchar(200) DEFAULT NULL,
      `maker_no` tinyint(3) unsigned NOT NULL,
      `type_no` tinyint(3) unsigned DEFAULT NULL,
      `unit_no` tinyint(3) unsigned DEFAULT NULL,
      `renchan_games` smallint(5) unsigned DEFAULT '0',
      `tenjo_games` smallint(5) unsigned DEFAULT '9999',
      `setting_list` varchar(50) DEFAULT NULL,
      `push_order_flg` tinyint(3) unsigned DEFAULT '0',
      `image_list` varchar(50) DEFAULT NULL,
      `image_detail` varchar(50) DEFAULT NULL,
      `image_reel` varchar(50) DEFAULT NULL,
      `prizeball_data` text,
      `layout_data` text,
      `remarks` text,
      `del_flg` tinyint(4) NOT NULL DEFAULT '0',
      `del_no` int(10) unsigned DEFAULT NULL,
      `del_dt` datetime DEFAULT NULL,
      `add_no` int(10) unsigned DEFAULT NULL,
      `add_dt` datetime DEFAULT NULL,
      `upd_no` int(10) unsigned DEFAULT NULL,
      `upd_dt` datetime DEFAULT NULL,
      PRIMARY KEY (`model_no`),
      KEY `INDEX1` (`category`),
      KEY `INDEX2` (`model_cd`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $DB->query($create_mst_model);
    echo "<p style='color:green;'>✓ mst_model table created</p>";

    // Minimal model data with layout_data and prizeball_data
    $layout_data = json_encode(array("version" => "1"));
    $prizeball_data = json_encode(array(
        "MAX" => 10,
        "MAX_RATE" => 100,
        "NAVEL" => 3,
        "TULIP" => 1,
        "ATTACKER1" => 15,
        "ATTACKER2" => 10
    ));

    $DB->query("INSERT INTO mst_model
                (model_no, category, model_cd, model_name, maker_no, renchan_games, tenjo_games, layout_data, prizeball_data, add_no, add_dt)
                VALUES
                (1, 1, 'TEST001', 'Test Model', 1, 0, 9999, '{$layout_data}', '{$prizeball_data}', 1, NOW())");
    echo "<p style='color:green;'>✓ Model entry inserted (model_no=1)</p>";

    // Step 3: dat_machine テーブルを作成
    echo "<h2>Step 3: Create dat_machine table</h2>";

    $DB->query("DROP TABLE IF EXISTS dat_machine");

    $create_dat_machine = "CREATE TABLE `dat_machine` (
      `machine_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
      `model_no` int(10) unsigned NOT NULL,
      `machine_cd` varchar(20) DEFAULT NULL,
      `owner_no` int(10) unsigned DEFAULT NULL,
      `camera_no` int(10) unsigned DEFAULT NULL,
      `signaling_id` varchar(10) NOT NULL,
      `convert_no` tinyint(4) unsigned NOT NULL,
      `release_date` date NOT NULL,
      `end_date` date NOT NULL DEFAULT '2099-12-31',
      `machine_corner` mediumtext,
      `real_setting` tinyint(4) DEFAULT '0',
      `upd_setting` tinyint(4) DEFAULT '0',
      `setting_upd_no` int(10) unsigned DEFAULT NULL,
      `setting_upd_dt` datetime DEFAULT NULL,
      `reboot_sw` int(10) unsigned DEFAULT '0',
      `reboot_dt` datetime DEFAULT NULL,
      `remarks` mediumtext,
      `machine_status` tinyint(4) NOT NULL DEFAULT '0',
      `del_flg` tinyint(4) NOT NULL DEFAULT '0',
      `del_no` int(10) unsigned DEFAULT NULL,
      `del_dt` datetime DEFAULT NULL,
      `add_no` int(10) unsigned DEFAULT NULL,
      `add_dt` datetime DEFAULT NULL,
      `upd_no` int(10) unsigned DEFAULT NULL,
      `upd_dt` datetime DEFAULT NULL,
      PRIMARY KEY (`machine_no`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8";

    $DB->query($create_dat_machine);
    echo "<p style='color:green;'>✓ dat_machine table created</p>";

    // Machine entry linking camera_no=1 and model_no=1
    $DB->query("INSERT INTO dat_machine
                (machine_no, model_no, camera_no, signaling_id, convert_no, release_date, add_no, add_dt)
                VALUES
                (1, 1, 1, 'peer1', 0, NOW(), 1, NOW())");
    echo "<p style='color:green;'>✓ Machine entry inserted (machine_no=1, camera_no=1, model_no=1)</p>";

    // Step 4: 検証
    echo "<h2>Step 4: Verification</h2>";

    $camera = $DB->getRow("SELECT * FROM mst_camera WHERE camera_no = 1");
    $model = $DB->getRow("SELECT * FROM mst_model WHERE model_no = 1");
    $machine = $DB->getRow("SELECT * FROM dat_machine WHERE machine_no = 1");

    if ($camera && $model && $machine) {
        echo "<div style='background:#d4edda;padding:15px;border-radius:5px;'>";
        echo "<h3 style='color:green;margin-top:0;'>✅ All Tables Created Successfully!</h3>";

        echo "<h4>Camera Entry:</h4>";
        echo "<p><strong>Camera No:</strong> {$camera['camera_no']}</p>";
        echo "<p><strong>Camera MAC:</strong> {$camera['camera_mac']}</p>";
        echo "<p><strong>Camera Name:</strong> {$camera['camera_name']}</p>";

        echo "<h4>Model Entry:</h4>";
        echo "<p><strong>Model No:</strong> {$model['model_no']}</p>";
        echo "<p><strong>Model Name:</strong> {$model['model_name']}</p>";
        echo "<p><strong>Category:</strong> {$model['category']}</p>";
        echo "<p><strong>Renchan Games:</strong> {$model['renchan_games']}</p>";
        echo "<p><strong>Tenjo Games:</strong> {$model['tenjo_games']}</p>";

        echo "<h4>Machine Entry:</h4>";
        echo "<p><strong>Machine No:</strong> {$machine['machine_no']}</p>";
        echo "<p><strong>Camera No:</strong> {$machine['camera_no']}</p>";
        echo "<p><strong>Model No:</strong> {$machine['model_no']}</p>";
        echo "<p><strong>Signaling ID:</strong> {$machine['signaling_id']}</p>";

        echo "<p><strong>Status:</strong> Database ready for API!</p>";
        echo "</div>";

        echo "<h2>Next Steps:</h2>";
        echo "<ol>";
        echo "<li>Windows側で <code>cd C:\\serverset</code> を実行</li>";
        echo "<li><code>.\\slotserver.exe -c COM4</code> を実行</li>";
        echo "<li>接続が成功したら、テストスクリプトを削除してください</li>";
        echo "</ol>";
    } else {
        echo "<p style='color:red;'>❌ Verification failed</p>";
    }

    // テーブル数を確認
    echo "<h2>Database Status</h2>";
    $tables = $DB->query("SHOW TABLES");
    $count = 0;
    echo "<ul>";
    while ($table = $tables->fetch(PDO::FETCH_NUM)) {
        echo "<li>{$table[0]}</li>";
        $count++;
    }
    echo "</ul>";
    echo "<p><strong>Total tables: {$count}</strong></p>";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h2 style='color:red;margin-top:0;'>Fatal Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>" . htmlspecialchars($e->getTraceAsString()) . "</p>";
    echo "</div>";
}
?>
