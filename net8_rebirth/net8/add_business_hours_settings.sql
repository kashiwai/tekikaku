-- ===================================================================
-- NET8 営業時間設定追加マイグレーション
-- ===================================================================
-- 作成日: 2025-11-19
-- 目的: mst_settingテーブルに営業時間関連の設定を追加
--       setting_base.phpのハードコードをDB管理に移行
-- ===================================================================

-- 既存の営業時間設定データを確認（念のため）
SELECT setting_no, setting_key, setting_val
FROM mst_setting
WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
  AND del_flg = 0;

-- ===================================================================
-- 営業時間設定の追加
-- ===================================================================

-- 1. 営業開始時刻
INSERT INTO `mst_setting` (
  `setting_no`,
  `setting_type`,
  `setting_name`,
  `setting_key`,
  `setting_format`,
  `setting_val`,
  `remarks`,
  `del_flg`,
  `del_no`,
  `del_dt`,
  `add_no`,
  `add_dt`,
  `upd_no`,
  `upd_dt`
) VALUES (
  2,                                    -- setting_no (AUTO_INCREMENT、2を指定)
  3,                                    -- setting_type (3 = システム設定)
  '営業開始時刻',                       -- setting_name
  'GLOBAL_OPEN_TIME',                   -- setting_key
  1,                                    -- setting_format (1 = 文字列)
  '10:00',                              -- setting_val (デフォルト10:00)
  '営業開始時刻を HH:MM 形式で設定します。\r\nこの時刻以降、ユーザーは台でプレイ可能になります。\r\n例: 10:00',
  0,                                    -- del_flg (0 = 有効)
  NULL,                                 -- del_no
  NULL,                                 -- del_dt
  1,                                    -- add_no (管理者ID)
  NOW(),                                -- add_dt
  1,                                    -- upd_no
  NOW()                                 -- upd_dt
)
ON DUPLICATE KEY UPDATE
  setting_val = VALUES(setting_val),
  upd_no = 1,
  upd_dt = NOW();

-- 2. 営業終了時刻
INSERT INTO `mst_setting` (
  `setting_no`,
  `setting_type`,
  `setting_name`,
  `setting_key`,
  `setting_format`,
  `setting_val`,
  `remarks`,
  `del_flg`,
  `del_no`,
  `del_dt`,
  `add_no`,
  `add_dt`,
  `upd_no`,
  `upd_dt`
) VALUES (
  3,                                    -- setting_no
  3,                                    -- setting_type (3 = システム設定)
  '営業終了時刻',                       -- setting_name
  'GLOBAL_CLOSE_TIME',                  -- setting_key
  1,                                    -- setting_format (1 = 文字列)
  '22:00',                              -- setting_val (デフォルト22:00)
  '営業終了時刻を HH:MM 形式で設定します。\r\nこの時刻以降、新規プレイは開始できません。\r\n例: 22:00',
  0,                                    -- del_flg
  NULL,                                 -- del_no
  NULL,                                 -- del_dt
  1,                                    -- add_no
  NOW(),                                -- add_dt
  1,                                    -- upd_no
  NOW()                                 -- upd_dt
)
ON DUPLICATE KEY UPDATE
  setting_val = VALUES(setting_val),
  upd_no = 1,
  upd_dt = NOW();

-- 3. 基準時刻（日跨ぎ判定用）
INSERT INTO `mst_setting` (
  `setting_no`,
  `setting_type`,
  `setting_name`,
  `setting_key`,
  `setting_format`,
  `setting_val`,
  `remarks`,
  `del_flg`,
  `del_no`,
  `del_dt`,
  `add_no`,
  `add_dt`,
  `upd_no`,
  `upd_dt`
) VALUES (
  4,                                    -- setting_no
  3,                                    -- setting_type (3 = システム設定)
  '基準時刻（日跨ぎ判定）',             -- setting_name
  'REFERENCE_TIME',                     -- setting_key
  1,                                    -- setting_format (1 = 文字列)
  '04:00',                              -- setting_val (デフォルト04:00)
  '日付の基準時刻を HH:MM 形式で設定します。\r\nこの時刻より前は前日として扱われます。\r\n例: 04:00 (午前4時より前は前日扱い)',
  0,                                    -- del_flg
  NULL,                                 -- del_no
  NULL,                                 -- del_dt
  1,                                    -- add_no
  NOW(),                                -- add_dt
  1,                                    -- upd_no
  NOW()                                 -- upd_dt
)
ON DUPLICATE KEY UPDATE
  setting_val = VALUES(setting_val),
  upd_no = 1,
  upd_dt = NOW();

-- ===================================================================
-- 確認クエリ
-- ===================================================================

-- 追加された営業時間設定を確認
SELECT
  setting_no,
  setting_type,
  setting_name,
  setting_key,
  setting_val,
  remarks,
  add_dt,
  upd_dt
FROM mst_setting
WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
  AND del_flg = 0
ORDER BY setting_no;

-- 全設定の確認
SELECT COUNT(*) as total_settings FROM mst_setting WHERE del_flg = 0;

-- ===================================================================
-- 実行結果の期待値
-- ===================================================================
-- setting_no | setting_key       | setting_val | setting_name
-- -----------|-------------------|-------------|---------------------------
-- 2          | GLOBAL_OPEN_TIME  | 10:00       | 営業開始時刻
-- 3          | GLOBAL_CLOSE_TIME | 22:00       | 営業終了時刻
-- 4          | REFERENCE_TIME    | 04:00       | 基準時刻（日跨ぎ判定）
-- ===================================================================
