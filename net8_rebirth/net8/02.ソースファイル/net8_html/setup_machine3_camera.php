<?php
require_once('_etc/setting_base.php');
require_once('_etc/setting.php');
require_once('_lib/smartDB.php');

$DB = new SmartDB(DB_DSN);

// 既存のカメラ確認
echo "既存カメラ:\n";
$cameras = $DB->query("SELECT camera_no, camera_mac FROM mst_camera WHERE del_flg=0")->fetchAll(PDO::FETCH_ASSOC);
foreach($cameras as $c) echo "Camera {$c['camera_no']}: {$c['camera_mac']}\n";

// dat_machine確認
echo "\ndat_machine:\n";
$machines = $DB->query("SELECT machine_no, model_no, camera_no, signaling_id FROM dat_machine")->fetchAll(PDO::FETCH_ASSOC);
foreach($machines as $m) echo "Machine {$m['machine_no']}: model={$m['model_no']}, camera={$m['camera_no']}, signaling={$m['signaling_id']}\n";

// MACHINE-03登録実行
$DB->autoCommit(false);
$camera_no = 3;
$mac = "MACHINE-03-MAC";
$license = "LICENSE-003";

// mst_camera
$DB->query("INSERT INTO mst_camera (camera_no, camera_mac, add_no, add_dt, upd_no, upd_dt, del_flg)
    VALUES ({$camera_no}, '{$mac}', 1, NOW(), 1, NOW(), 0)
    ON DUPLICATE KEY UPDATE camera_mac='{$mac}', upd_dt=NOW()");

// mst_cameralist
$DB->query("INSERT INTO mst_cameralist (mac_address, license_id, add_no, add_dt, upd_no, upd_dt, del_flg)
    VALUES ('{$mac}', '{$license}', 1, NOW(), 1, NOW(), 0)
    ON DUPLICATE KEY UPDATE license_id='{$license}', upd_dt=NOW()");

// dat_machine
$DB->query("UPDATE dat_machine SET camera_no={$camera_no}, upd_dt=NOW() WHERE machine_no=3");

$DB->autoCommit(true);
echo "\n✅ 登録完了\n";
?>
