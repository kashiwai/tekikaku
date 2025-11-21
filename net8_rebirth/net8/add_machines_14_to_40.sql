-- ================================================================
-- 機械14台目～40台目を一括登録
-- 現在13台 → 40台に増やす
-- ================================================================

-- 実行前確認：現在の最大machine_no
SELECT MAX(machine_no) AS '現在の最大台数' FROM dat_machine;

-- ================================================================
-- 14台目～40台目を登録（27台追加）
-- ================================================================

-- 注意：実際の値は環境に合わせて調整してください
-- - model_no: 機種番号（mst_modelから選択）
-- - signaling_id: Signaling Server ID（環境に合わせて設定）
-- - owner_no: オーナー番号（デフォルト1）
-- - corner_no: コーナー番号（デフォルト1）

-- 台14-20の登録
INSERT INTO dat_machine (
    machine_no,
    machine_cd,
    model_no,
    signaling_id,
    machine_status,
    del_flg,
    release_date,
    end_date,
    owner_no,
    corner_no,
    camera_no,
    token,
    add_no,
    add_dt
) VALUES
(14, 'M014', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m014_', LOWER(MD5(CONCAT('machine_14', NOW())))), 1, NOW()),
(15, 'M015', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m015_', LOWER(MD5(CONCAT('machine_15', NOW())))), 1, NOW()),
(16, 'M016', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m016_', LOWER(MD5(CONCAT('machine_16', NOW())))), 1, NOW()),
(17, 'M017', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m017_', LOWER(MD5(CONCAT('machine_17', NOW())))), 1, NOW()),
(18, 'M018', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m018_', LOWER(MD5(CONCAT('machine_18', NOW())))), 1, NOW()),
(19, 'M019', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m019_', LOWER(MD5(CONCAT('machine_19', NOW())))), 1, NOW()),
(20, 'M020', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m020_', LOWER(MD5(CONCAT('machine_20', NOW())))), 1, NOW());

-- 台21-30の登録
INSERT INTO dat_machine (
    machine_no, machine_cd, model_no, signaling_id, machine_status, del_flg,
    release_date, end_date, owner_no, corner_no, camera_no, token, add_no, add_dt
) VALUES
(21, 'M021', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m021_', LOWER(MD5(CONCAT('machine_21', NOW())))), 1, NOW()),
(22, 'M022', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m022_', LOWER(MD5(CONCAT('machine_22', NOW())))), 1, NOW()),
(23, 'M023', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m023_', LOWER(MD5(CONCAT('machine_23', NOW())))), 1, NOW()),
(24, 'M024', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m024_', LOWER(MD5(CONCAT('machine_24', NOW())))), 1, NOW()),
(25, 'M025', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m025_', LOWER(MD5(CONCAT('machine_25', NOW())))), 1, NOW()),
(26, 'M026', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m026_', LOWER(MD5(CONCAT('machine_26', NOW())))), 1, NOW()),
(27, 'M027', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m027_', LOWER(MD5(CONCAT('machine_27', NOW())))), 1, NOW()),
(28, 'M028', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m028_', LOWER(MD5(CONCAT('machine_28', NOW())))), 1, NOW()),
(29, 'M029', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m029_', LOWER(MD5(CONCAT('machine_29', NOW())))), 1, NOW()),
(30, 'M030', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m030_', LOWER(MD5(CONCAT('machine_30', NOW())))), 1, NOW());

-- 台31-40の登録
INSERT INTO dat_machine (
    machine_no, machine_cd, model_no, signaling_id, machine_status, del_flg,
    release_date, end_date, owner_no, corner_no, camera_no, token, add_no, add_dt
) VALUES
(31, 'M031', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m031_', LOWER(MD5(CONCAT('machine_31', NOW())))), 1, NOW()),
(32, 'M032', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m032_', LOWER(MD5(CONCAT('machine_32', NOW())))), 1, NOW()),
(33, 'M033', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m033_', LOWER(MD5(CONCAT('machine_33', NOW())))), 1, NOW()),
(34, 'M034', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m034_', LOWER(MD5(CONCAT('machine_34', NOW())))), 1, NOW()),
(35, 'M035', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m035_', LOWER(MD5(CONCAT('machine_35', NOW())))), 1, NOW()),
(36, 'M036', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m036_', LOWER(MD5(CONCAT('machine_36', NOW())))), 1, NOW()),
(37, 'M037', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m037_', LOWER(MD5(CONCAT('machine_37', NOW())))), 1, NOW()),
(38, 'M038', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m038_', LOWER(MD5(CONCAT('machine_38', NOW())))), 1, NOW()),
(39, 'M039', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m039_', LOWER(MD5(CONCAT('machine_39', NOW())))), 1, NOW()),
(40, 'M040', 1, 1, 0, 0, CURDATE(), '2099-12-31', 1, 1, 0, CONCAT('net8_m040_', LOWER(MD5(CONCAT('machine_40', NOW())))), 1, NOW());

-- lnk_machineにも追加（接続管理用）
INSERT INTO lnk_machine (machine_no, member_no, assign_flg, onetime_id, start_dt, exit_flg)
SELECT machine_no, 0, 0, '', NOW(), 0
FROM dat_machine
WHERE machine_no BETWEEN 14 AND 40
ON DUPLICATE KEY UPDATE assign_flg = assign_flg; -- 既存なら何もしない

-- ================================================================
-- 確認SQL：登録後の確認
-- ================================================================
SELECT
    COUNT(*) AS '登録台数',
    MIN(machine_no) AS '最小番号',
    MAX(machine_no) AS '最大番号'
FROM dat_machine
WHERE del_flg = 0;

-- 全台一覧表示
SELECT
    machine_no AS '台番号',
    machine_cd AS '機械コード',
    machine_status AS 'ステータス',
    camera_no AS 'カメラNo',
    LEFT(token, 20) AS 'トークン（先頭20文字）',
    del_flg AS '削除フラグ'
FROM dat_machine
ORDER BY machine_no;

-- ================================================================
-- 注意事項
-- ================================================================
-- 1. model_no: 実際の機種番号に変更してください
-- 2. signaling_id: 環境に合わせて設定してください
-- 3. token: 自動生成されます（32文字のMD5ハッシュ）
-- 4. カメラNoは後から machine_control.php で設定可能
-- ================================================================
