-- メンテナンスモード解除SQL
-- machine_no = 1 の台を通常モード(利用可能)に設定

-- 実行前の状態確認
SELECT 'Before Update:' as status;
SELECT machine_no, machine_status,
       CASE machine_status
           WHEN 0 THEN '準備中'
           WHEN 1 THEN '通常'
           WHEN 2 THEN 'メンテナンス中'
       END as status_name
FROM dat_machine WHERE machine_no = 1;

SELECT machine_no, assign_flg,
       CASE assign_flg
           WHEN 0 THEN '利用可能'
           WHEN 1 THEN '使用中'
           WHEN 9 THEN 'メンテナンス中'
       END as assign_name
FROM lnk_machine WHERE machine_no = 1;

-- 修正実行
-- dat_machine.machine_status を 1 (通常) に設定
UPDATE dat_machine SET machine_status = 1 WHERE machine_no = 1;

-- lnk_machine.assign_flg を 0 (利用可能) に設定
UPDATE lnk_machine SET assign_flg = 0, member_no = NULL WHERE machine_no = 1;

-- 実行後の状態確認
SELECT 'After Update:' as status;
SELECT machine_no, machine_status,
       CASE machine_status
           WHEN 0 THEN '準備中'
           WHEN 1 THEN '通常'
           WHEN 2 THEN 'メンテナンス中'
       END as status_name
FROM dat_machine WHERE machine_no = 1;

SELECT machine_no, assign_flg,
       CASE assign_flg
           WHEN 0 THEN '利用可能'
           WHEN 1 THEN '使用中'
           WHEN 9 THEN 'メンテナンス中'
       END as assign_name
FROM lnk_machine WHERE machine_no = 1;
