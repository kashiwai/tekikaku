-- ================================================================
-- machine_no=1 の完全な状態確認SQL
-- ================================================================

-- 【クエリ1】machine_no=1の基本情報
SELECT
    dm.machine_no AS '機械番号',
    dm.machine_cd AS '機械コード',
    mm.model_name AS '機種名',
    dm.signaling_id AS 'Signaling ID',
    dm.machine_status AS 'ステータス (0=正常)',
    dm.del_flg AS '削除フラグ (0=有効)',
    dm.camera_no AS 'カメラ番号',
    dm.release_date AS '稼働開始日',
    dm.end_date AS '稼働終了日'
FROM dat_machine dm
LEFT JOIN mst_model mm ON mm.model_no = dm.model_no
WHERE dm.machine_no = 1;

-- ================================================================
-- 【クエリ2】カメラの登録状態
-- ================================================================
SELECT
    mc.camera_no AS 'カメラ番号',
    mc.camera_mac AS 'カメラMAC',
    mc.camera_name AS 'カメラ名',
    mc.del_flg AS '削除フラグ (0=有効)'
FROM mst_camera mc
WHERE mc.camera_no = (SELECT camera_no FROM dat_machine WHERE machine_no = 1);

-- ================================================================
-- 【クエリ3】カメラリストの登録状態（ライセンス）
-- ================================================================
SELECT
    mcl.mac_address AS 'MACアドレス',
    mcl.camera_no AS 'カメラ番号',
    mcl.state AS 'ステータス',
    mcl.license_id AS 'ライセンスID',
    mcl.del_flg AS '削除フラグ (0=有効)'
FROM mst_cameralist mcl
WHERE mcl.camera_no = (SELECT camera_no FROM dat_machine WHERE machine_no = 1);

-- ================================================================
-- 【クエリ4】稼働日付の確認
-- ================================================================
SELECT
    dm.machine_no AS '機械番号',
    dm.release_date AS '稼働開始日',
    dm.end_date AS '稼働終了日',
    CURDATE() AS '今日の日付',
    CASE
        WHEN CURDATE() < dm.release_date THEN '未稼働（開始日前）'
        WHEN CURDATE() > dm.end_date THEN '稼働終了'
        ELSE '稼働中'
    END AS '稼働状態'
FROM dat_machine dm
WHERE dm.machine_no = 1;

-- ================================================================
-- 【クエリ5】全機械の稼働状態一覧
-- ================================================================
SELECT
    dm.machine_no AS '機械番号',
    dm.machine_cd AS '機械コード',
    mm.model_name AS '機種名',
    dm.machine_status AS 'ステータス',
    dm.del_flg AS '削除フラグ',
    CASE
        WHEN dm.del_flg = 1 THEN '削除済み'
        WHEN CURDATE() < dm.release_date THEN '未稼働'
        WHEN CURDATE() > dm.end_date THEN '稼働終了'
        WHEN dm.machine_status != 0 THEN 'エラー状態'
        ELSE '稼働中'
    END AS '表示状態'
FROM dat_machine dm
LEFT JOIN mst_model mm ON mm.model_no = dm.model_no
ORDER BY dm.machine_no;

-- ================================================================
-- 【修正SQL】もし問題があった場合の修正例
-- ================================================================

-- machine_statusを正常に戻す
-- UPDATE dat_machine SET machine_status = 0 WHERE machine_no = 1;

-- del_flgを有効に戻す
-- UPDATE dat_machine SET del_flg = 0 WHERE machine_no = 1;

-- 稼働日付を現在に設定
-- UPDATE dat_machine
-- SET release_date = CURDATE(), end_date = '2099-12-31'
-- WHERE machine_no = 1;

-- ================================================================
