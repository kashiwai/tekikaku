-- ================================================================
-- lnk_machine テーブルの状態確認SQL
-- ================================================================
-- 視聴ページで「この台は現在稼働していない」と表示される原因を調査

-- 【クエリ1】lnk_machine テーブルの全データ
SELECT
    machine_no AS '機械番号',
    assign_flg AS '割当フラグ (0=未使用, 1=使用中, 9=稼働停止)',
    member_no AS '会員番号',
    onetime_id AS 'ワンタイムID',
    exit_flg AS '退出フラグ',
    start_dt AS '開始日時',
    end_dt AS '終了日時',
    CASE
        WHEN assign_flg = 9 THEN '稼働停止'
        WHEN assign_flg = 1 THEN '使用中'
        WHEN assign_flg = 0 THEN '空き'
        ELSE 'その他'
    END AS '状態'
FROM lnk_machine
ORDER BY machine_no;

-- ================================================================
-- 【クエリ2】machine_no=1 の詳細
-- ================================================================
SELECT
    machine_no AS '機械番号',
    assign_flg AS '割当フラグ',
    member_no AS '会員番号',
    start_dt AS '開始日時',
    end_dt AS '終了日時',
    CASE
        WHEN assign_flg = 9 THEN '⚠️ 稼働停止状態（これが原因）'
        WHEN machine_no IS NULL OR machine_no = '' THEN '⚠️ 機械番号が空（これが原因）'
        WHEN assign_flg = 0 THEN '✅ 空き状態（視聴可能）'
        WHEN assign_flg = 1 THEN '使用中'
        ELSE 'その他'
    END AS '判定'
FROM lnk_machine
WHERE machine_no = 1;

-- ================================================================
-- 【クエリ3】dat_machine と lnk_machine の対応確認
-- ================================================================
SELECT
    dm.machine_no AS 'dat_machine番号',
    dm.machine_cd AS '機械コード',
    lm.machine_no AS 'lnk_machine番号',
    lm.assign_flg AS '割当フラグ',
    CASE
        WHEN lm.machine_no IS NULL THEN '❌ lnk_machineに未登録'
        WHEN lm.assign_flg = 9 THEN '⚠️ 稼働停止'
        WHEN lm.assign_flg = 0 THEN '✅ 空き'
        WHEN lm.assign_flg = 1 THEN '使用中'
        ELSE 'その他'
    END AS '状態'
FROM dat_machine dm
LEFT JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
WHERE dm.del_flg = 0
ORDER BY dm.machine_no;

-- ================================================================
-- 【修正SQL】問題があった場合の修正
-- ================================================================

-- ケース1: assign_flg が 9 の場合 → 0 に変更（空き状態に）
-- UPDATE lnk_machine SET assign_flg = 0 WHERE machine_no = 1;

-- ケース2: machine_no が空の場合 → データを再作成
-- INSERT IGNORE INTO lnk_machine (machine_no, assign_flg, exit_flg)
-- VALUES (1, 0, 0);

-- ケース3: lnk_machine に存在しない場合 → 新規登録
-- INSERT IGNORE INTO lnk_machine (machine_no, assign_flg, member_no, exit_flg, start_dt, end_dt)
-- VALUES (1, 0, NULL, 0, NULL, NULL);

-- ================================================================
-- 【推奨】全マシンを空き状態にする（開発時）
-- ================================================================
-- UPDATE lnk_machine SET assign_flg = 0, member_no = NULL, exit_flg = 0;

-- ================================================================
