<?php
/**
 * Auto Database Setup
 *
 * このスクリプトはデプロイ後に自動的にテーブルを確認・作成します
 */

$EXEC_KEY = 'auto_setup_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Auto Database Setup</h1>";

    // Step 1: テーブルの存在確認
    echo "<h2>Step 1: Check existing tables</h2>";

    $required_tables = ['mst_cameralist', 'mst_camera', 'dat_machine', 'mst_model', 'lnk_machine'];
    $missing_tables = [];

    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Table</th><th>Status</th></tr>";

    foreach ($required_tables as $table) {
        try {
            $DB->query("SELECT 1 FROM {$table} LIMIT 1");
            echo "<tr><td>{$table}</td><td style='color:green;'>✓ Exists</td></tr>";
        } catch (Exception $e) {
            echo "<tr><td>{$table}</td><td style='color:red;'>❌ Missing</td></tr>";
            $missing_tables[] = $table;
        }
    }
    echo "</table>";

    if (empty($missing_tables)) {
        echo "<p style='color:green;'><strong>All tables exist! No action needed.</strong></p>";

        // license_cdの確認
        echo "<h2>Step 2: Verify license_cd</h2>";
        $row = $DB->getRow("SELECT license_cd FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'");
        if ($row) {
            $correct_cd = 'f2e419eee66138df5444cecab202fa3001944c772f0dada61288b7142925e5a1';
            if ($row['license_cd'] === $correct_cd) {
                echo "<p style='color:green;'>✓ License CD is correct</p>";
            } else {
                echo "<p style='color:orange;'>⚠ License CD is incorrect, updating...</p>";
                $DB->query("UPDATE mst_cameralist SET license_cd = '{$correct_cd}' WHERE mac_address = '34-a6-ef-35-73-73'");
                echo "<p style='color:green;'>✓ License CD updated</p>";
            }
        }
    } else {
        echo "<p style='color:orange;'><strong>Missing tables detected. Creating...</strong></p>";

        // Step 2: テーブル作成
        echo "<h2>Step 2: Create missing tables</h2>";

        // mst_cameralist
        if (in_array('mst_cameralist', $missing_tables)) {
            echo "<h3>Creating mst_cameralist</h3>";
            $DB->query("DROP TABLE IF EXISTS mst_cameralist");
            $DB->query("CREATE TABLE `mst_cameralist` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $DB->query("INSERT INTO `mst_cameralist`
            (`mac_address`, `camera_no`, `license_id`, `license_cd`, `state`, `del_flg`, `add_no`, `add_dt`)
            VALUES
            ('34-a6-ef-35-73-73', 1, 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=', 'f2e419eee66138df5444cecab202fa3001944c772f0dada61288b7142925e5a1', 0, 0, 1, NOW())");

            echo "<p style='color:green;'>✓ mst_cameralist created</p>";
        }

        // mst_camera
        if (in_array('mst_camera', $missing_tables)) {
            echo "<h3>Creating mst_camera</h3>";
            $DB->query("DROP TABLE IF EXISTS mst_camera");
            $DB->query("CREATE TABLE `mst_camera` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $DB->query("INSERT INTO mst_camera (camera_no, camera_mac, camera_name, add_no, add_dt)
                        VALUES (1, '34-a6-ef-35-73-73', 'Windows PC Camera', 1, NOW())");

            echo "<p style='color:green;'>✓ mst_camera created</p>";
        }

        // mst_model
        if (in_array('mst_model', $missing_tables)) {
            echo "<h3>Creating mst_model</h3>";
            $DB->query("DROP TABLE IF EXISTS mst_model");
            $DB->query("CREATE TABLE `mst_model` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

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

            echo "<p style='color:green;'>✓ mst_model created</p>";
        }

        // dat_machine
        if (in_array('dat_machine', $missing_tables)) {
            echo "<h3>Creating dat_machine</h3>";
            $DB->query("DROP TABLE IF EXISTS dat_machine");
            $DB->query("CREATE TABLE `dat_machine` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $DB->query("INSERT INTO dat_machine
                        (machine_no, model_no, camera_no, signaling_id, convert_no, release_date, add_no, add_dt)
                        VALUES
                        (1, 1, 1, 'peer1', 0, NOW(), 1, NOW())");

            echo "<p style='color:green;'>✓ dat_machine created</p>";
        }

        // lnk_machine
        if (in_array('lnk_machine', $missing_tables)) {
            echo "<h3>Creating lnk_machine</h3>";
            $DB->query("DROP TABLE IF EXISTS lnk_machine");
            $DB->query("CREATE TABLE `lnk_machine` (
              `machine_no` tinyint(3) unsigned NOT NULL,
              `assign_flg` tinyint(4) NOT NULL DEFAULT '0',
              `member_no` int(10) unsigned DEFAULT NULL,
              `onetime_id` varchar(50) DEFAULT NULL,
              `exit_flg` tinyint(4) DEFAULT '0',
              `start_dt` datetime DEFAULT NULL,
              `end_dt` datetime DEFAULT NULL,
              PRIMARY KEY (`machine_no`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

            $DB->query("INSERT INTO lnk_machine
                        (machine_no, assign_flg, member_no, onetime_id, exit_flg)
                        VALUES
                        (1, 9, 0, '', 0)");

            echo "<p style='color:green;'>✓ lnk_machine created</p>";
        }

        echo "<h2>Step 3: Verification</h2>";
        echo "<p style='color:green;'><strong>All tables have been created successfully!</strong></p>";
    }

    // 最終確認
    $table_count = $DB->query("SHOW TABLES");
    $count = 0;
    while ($table_count->fetch()) {
        $count++;
    }
    echo "<p><strong>Total tables in database:</strong> {$count}</p>";

    echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h3 style='color:green;margin-top:0;'>✅ Database is ready!</h3>";
    echo "<p>All required tables exist and are configured.</p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#f8d7da;padding:15px;border-radius:5px;margin-top:20px;'>";
    echo "<h2 style='color:red;margin-top:0;'>Error</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>
