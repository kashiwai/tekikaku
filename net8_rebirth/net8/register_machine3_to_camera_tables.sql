-- ================================================
-- Register MACHINE-03 to camera tables
-- マシン3のMACアドレスをカメラテーブルに登録
-- ================================================

-- 1. mst_cameraテーブルに登録
INSERT INTO mst_camera (
    camera_mac,
    camera_name,
    del_flg,
    add_dt
) VALUES (
    'E0-51-D8-16-13-66',
    'CAMERA-03',
    0,
    NOW()
) ON DUPLICATE KEY UPDATE
    camera_name = 'CAMERA-03',
    upd_dt = NOW();

-- 2. mst_cameralistテーブルに登録
INSERT INTO mst_cameralist (
    mac_address,
    license_id,
    camera_no,
    del_flg,
    add_dt
) VALUES (
    'E0-51-D8-16-13-66',
    'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=',
    2,
    0,
    NOW()
) ON DUPLICATE KEY UPDATE
    license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=',
    camera_no = 2,
    upd_dt = NOW();

-- 確認
SELECT '✅ mst_camera 登録確認:' AS '';
SELECT * FROM mst_camera WHERE camera_mac = 'E0-51-D8-16-13-66';

SELECT '✅ mst_cameralist 登録確認:' AS '';
SELECT mac_address, license_id, camera_no, del_flg FROM mst_cameralist WHERE mac_address = 'E0-51-D8-16-13-66';
