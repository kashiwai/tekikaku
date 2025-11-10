-- ================================================
-- MACアドレス認証デバッグSQL
-- Win側slotserverの認証が通らない原因を特定
-- ================================================
-- 対象MACアドレス: e0-51-d8-16-7d-e1
-- ライセンスID: IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=
-- ================================================

-- ❶ mst_cameralistの確認（最初の認証チェック）
SELECT
    'mst_cameralist' AS table_name,
    mac_address,
    license_id,
    camera_no,
    del_flg,
    CASE
        WHEN mac_address = 'e0-51-d8-16-7d-e1' THEN '✅ MAC一致'
        ELSE '❌ MAC不一致'
    END AS mac_check,
    CASE
        WHEN license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=' THEN '✅ License一致'
        ELSE '❌ License不一致'
    END AS license_check,
    CASE
        WHEN del_flg = 0 THEN '✅ 有効'
        ELSE '❌ 削除済み'
    END AS del_check
FROM mst_cameralist
WHERE mac_address LIKE '%e0-51-d8-16-7d-e1%'
   OR mac_address LIKE '%E0-51-D8-16-7D-E1%'
   OR mac_address LIKE '%e0:51:d8:16:7d:e1%'
ORDER BY mac_address;

-- 結果がない場合は全件確認
SELECT
    '【全MACアドレス一覧】' AS info,
    mac_address,
    LEFT(license_id, 20) AS license_id_preview,
    camera_no,
    del_flg
FROM mst_cameralist
ORDER BY add_dt DESC
LIMIT 10;

-- ❷ mst_cameraの確認（カメラ番号取得用）
SELECT
    'mst_camera' AS table_name,
    camera_no,
    camera_mac,
    camera_name,
    del_flg,
    CASE
        WHEN camera_mac = 'e0-51-d8-16-7d-e1' THEN '✅ MAC一致'
        ELSE '❌ MAC不一致'
    END AS mac_check
FROM mst_camera
WHERE camera_mac LIKE '%e0-51-d8-16-7d-e1%'
   OR camera_mac LIKE '%E0-51-D8-16-7D-E1%'
   OR camera_mac LIKE '%e0:51:d8:16:7d:e1%'
ORDER BY camera_no;

-- 結果がない場合は全件確認
SELECT
    '【全カメラ一覧】' AS info,
    camera_no,
    camera_mac,
    camera_name,
    del_flg
FROM mst_camera
WHERE del_flg = 0
ORDER BY camera_no
LIMIT 10;

-- ❸ dat_machineの確認（台とカメラの関連付け）
SELECT
    'dat_machine' AS table_name,
    dm.machine_no,
    dm.camera_no,
    dm.mac_address AS machine_mac_address,
    mc.camera_mac,
    mc.camera_name,
    CASE
        WHEN dm.camera_no IS NOT NULL THEN '✅ カメラ割当済'
        ELSE '❌ 未割当'
    END AS assignment_check
FROM dat_machine dm
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
WHERE mc.camera_mac LIKE '%e0-51-d8-16-7d-e1%'
   OR dm.mac_address LIKE '%e0-51-d8-16-7d-e1%'
ORDER BY dm.machine_no;

-- ================================================
-- 診断結果
-- ================================================

SELECT '【診断結果】' AS result;

-- mst_cameralistに登録があるか
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ mst_cameralistに登録あり'
        ELSE '❌ mst_cameralistに登録なし（要登録）'
    END AS step1_cameralist
FROM mst_cameralist
WHERE mac_address = 'e0-51-d8-16-7d-e1'
  AND del_flg = 0;

-- license_idも一致しているか
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ ライセンスIDも一致'
        ELSE '❌ ライセンスIDが不一致（要確認）'
    END AS step2_license
FROM mst_cameralist
WHERE mac_address = 'e0-51-d8-16-7d-e1'
  AND license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI='
  AND del_flg = 0;

-- mst_cameraに登録があるか
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ mst_cameraに登録あり'
        ELSE '❌ mst_cameraに登録なし（要登録）'
    END AS step3_camera
FROM mst_camera
WHERE camera_mac = 'e0-51-d8-16-7d-e1'
  AND del_flg = 0;

-- dat_machineに割り当てられているか
SELECT
    CASE
        WHEN COUNT(*) > 0 THEN '✅ 台に割当済み'
        ELSE '❌ 台に未割当（要割当）'
    END AS step4_machine
FROM dat_machine dm
JOIN mst_camera mc ON dm.camera_no = mc.camera_no
WHERE mc.camera_mac = 'e0-51-d8-16-7d-e1'
  AND mc.del_flg = 0;
