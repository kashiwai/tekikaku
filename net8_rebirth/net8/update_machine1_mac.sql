-- ================================================
-- マシン1のMACアドレスを e0:51:d8:16:7d:e1 に更新
-- ================================================

-- 現在の状態確認
SELECT '【更新前の状態】' AS info;
SELECT
    dm.machine_no,
    dm.mac_address AS dat_machine_mac,
    dm.camera_no,
    mc.camera_mac AS mst_camera_mac,
    mc.camera_name,
    mcl.mac_address AS cameralist_mac,
    LEFT(mcl.license_id, 30) AS license_id_preview
FROM dat_machine dm
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
WHERE dm.machine_no = 1;

-- トランザクション開始
START TRANSACTION;

-- ❶ dat_machine.mac_address を更新
UPDATE dat_machine
SET mac_address = 'e0-51-d8-16-7d-e1'
WHERE machine_no = 1;

-- camera_noを取得（変数に格納）
SET @camera_no = (SELECT camera_no FROM dat_machine WHERE machine_no = 1);

-- ❷ mst_camera.camera_mac を更新
UPDATE mst_camera
SET camera_mac = 'e0-51-d8-16-7d-e1'
WHERE camera_no = @camera_no;

-- ❸ mst_cameralist を更新（既存レコードがある場合のみ）
-- 既存の古いMACアドレスのレコードを削除マーク
UPDATE mst_cameralist
SET del_flg = 1,
    del_dt = NOW()
WHERE camera_no = @camera_no
  AND mac_address != 'e0-51-d8-16-7d-e1';

-- 新しいMACアドレスのレコードが既に存在するか確認して更新、なければ何もしない
UPDATE mst_cameralist
SET camera_no = @camera_no,
    del_flg = 0,
    upd_dt = NOW()
WHERE mac_address = 'e0-51-d8-16-7d-e1';

-- コミット
COMMIT;

-- 更新後の状態確認
SELECT '【更新後の状態】' AS info;
SELECT
    dm.machine_no,
    dm.mac_address AS dat_machine_mac,
    dm.camera_no,
    mc.camera_mac AS mst_camera_mac,
    mc.camera_name,
    mcl.mac_address AS cameralist_mac,
    LEFT(mcl.license_id, 30) AS license_id_preview,
    mcl.state,
    mcl.del_flg
FROM dat_machine dm
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
WHERE dm.machine_no = 1;

-- 確認：mst_cameralistに新しいMACアドレスのレコードがあるか
SELECT '【mst_cameralist確認】' AS info;
SELECT
    mac_address,
    camera_no,
    LEFT(license_id, 30) AS license_id_preview,
    state,
    del_flg,
    add_dt
FROM mst_cameralist
WHERE mac_address = 'e0-51-d8-16-7d-e1';
