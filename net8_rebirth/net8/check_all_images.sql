-- ================================================================
-- 全ページ画像表示問題の完全調査SQL
-- ================================================================

-- 【重要】このSQLを実行して結果を共有してください
-- 目的: 画像が表示されない原因を特定する

-- ================================================================
-- 【クエリ1】機種マスタの全画像設定を確認
-- ================================================================
SELECT
    mm.model_no AS '機種No',
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    mu.unit_name AS '世代',
    mm.image_list AS '画像ファイル名',
    mm.image_detail AS '詳細画像',
    mm.image_reel AS 'リール画像',
    mm.disp_flg AS '表示(1=表示)',
    mm.disp_order AS '表示順',
    mm.del_flg AS '削除(0=有効)',
    CONCAT('/data/img/model/', mm.image_list) AS '画像の完全パス'
FROM mst_model mm
LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
WHERE mm.del_flg = 0
ORDER BY mm.disp_order ASC;

-- ================================================================
-- 【クエリ2】画像が未設定またはNULLの機種
-- ================================================================
SELECT
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    CASE
        WHEN mm.image_list IS NULL THEN 'NULL'
        WHEN mm.image_list = '' THEN '空文字'
        ELSE mm.image_list
    END AS '画像ファイル名の状態'
FROM mst_model mm
WHERE mm.del_flg = 0
  AND (mm.image_list IS NULL OR mm.image_list = '' OR mm.image_list = 'NULL');

-- ================================================================
-- 【クエリ3】実際に台として登録されている機種の画像設定
-- ================================================================
SELECT DISTINCT
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    mm.image_list AS '画像ファイル名',
    COUNT(DISTINCT dm.machine_no) AS '登録台数',
    CONCAT('/data/img/model/', mm.image_list) AS '完全パス'
FROM dat_machine dm
INNER JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg = 0
WHERE dm.del_flg = 0
GROUP BY mm.model_cd, mm.model_name, mm.image_list
ORDER BY mm.disp_order ASC;

-- ================================================================
-- 【クエリ4】お知らせの画像設定を確認
-- ================================================================
SELECT
    dn.notice_no AS 'お知らせNo',
    dn.notice_name AS 'お知らせ名',
    dnl.title AS 'タイトル',
    dnl.top_image AS 'トップ画像ファイル名',
    dn.start_dt AS '開始日',
    dn.end_dt AS '終了日',
    dn.disp_order AS '表示順'
FROM dat_notice dn
INNER JOIN dat_notice_lang dnl ON dn.notice_no = dnl.notice_no AND dnl.lang = 'ja'
WHERE dn.del_flg <> 1
  AND dn.start_dt <= CURDATE()
  AND dn.end_dt >= CURDATE()
ORDER BY dn.disp_order ASC;

-- ================================================================
-- 【既知の問題と解決策】
-- ================================================================

-- 問題1: image_list が NULL または空文字
-- → データベースに画像ファイル名を登録する必要があります

-- 問題2: ファイル名がDBに登録されているが、ファイルが存在しない
-- → 以下のファイルが /data/img/model/ に存在することを確認してください：
--   - hokuto4go.jpg
--   - milliongod_gaisen.jpg
--   - zenigata.jpg

-- 問題3: ファイル名が間違っている
-- → 例: DBに "hokuto.jpg" と登録されているが、実際は "hokuto4go.jpg"

-- ================================================================
-- 【修正用UPDATE文のテンプレート】
-- ================================================================

-- 実行前に必ずバックアップを取ってください！

-- 例1: 北斗の拳の画像を設定
-- UPDATE mst_model
-- SET image_list = 'hokuto4go.jpg'
-- WHERE model_cd = '該当の機種コード';

-- 例2: ミリオンゴッド凱旋の画像を設定
-- UPDATE mst_model
-- SET image_list = 'milliongod_gaisen.jpg'
-- WHERE model_cd = '該当の機種コード';

-- 例3: 銭形の画像を設定
-- UPDATE mst_model
-- SET image_list = 'zenigata.jpg'
-- WHERE model_cd = '該当の機種コード';

-- 例4: 全機種に一時的にダミー画像を設定（テスト用）
-- UPDATE mst_model
-- SET image_list = 'hokuto4go.jpg'
-- WHERE del_flg = 0
--   AND (image_list IS NULL OR image_list = '');

-- ================================================================
