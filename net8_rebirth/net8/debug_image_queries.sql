-- ================================================================
-- 画像表示問題調査用SQLクエリ集
-- ================================================================

-- 【クエリ1】機種マスタの画像設定確認（表示対象のみ）
-- 目的: トップページに表示される機種の画像ファイル名を確認
-- 注意: generation カラムは存在しません。unit_name を使用します
SELECT
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    mu.unit_name AS '世代（号機）',
    mm.image_list AS '画像ファイル名',
    mm.disp_flg AS '表示フラグ(1=表示)',
    mm.disp_order AS '表示順',
    mm.del_flg AS '削除フラグ(0=有効)'
FROM mst_model mm
LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
WHERE mm.del_flg = 0
ORDER BY mm.disp_order ASC;

-- ================================================================

-- 【クエリ2】画像ファイルが設定されていない機種を確認
-- 目的: image_list が NULL または空の機種を特定
SELECT
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    mu.unit_name AS '世代（号機）',
    mm.image_list AS '画像ファイル名',
    mm.disp_flg AS '表示フラグ'
FROM mst_model mm
LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
WHERE mm.del_flg = 0
  AND (mm.image_list IS NULL OR mm.image_list = '')
ORDER BY mm.disp_order ASC;

-- ================================================================

-- 【クエリ3】お知らせ情報の画像設定確認
-- 目的: トップページのお知らせに表示される画像を確認
SELECT
    dn.notice_no AS 'お知らせNo',
    dn.notice_name AS 'お知らせ名',
    dnl.title AS 'タイトル',
    dnl.top_image AS 'トップ画像',
    dn.start_dt AS '開始日',
    dn.end_dt AS '終了日',
    dn.disp_order AS '表示順',
    dn.del_flg AS '削除フラグ'
FROM dat_notice dn
INNER JOIN dat_notice_lang dnl
    ON dn.notice_no = dnl.notice_no
    AND dnl.lang = 'ja'
WHERE dn.del_flg <> 1
  AND dn.start_dt <= CURDATE()
  AND dn.end_dt >= CURDATE()
ORDER BY dn.disp_order ASC;

-- ================================================================

-- 【クエリ4】画像パス修正用UPDATE（実行前に確認）
-- 注意: 実際の画像ファイル名に合わせて修正してください
-- 例: 画像ファイル名が間違っている場合

-- 例1: hokuto4go の画像ファイル名を修正
-- UPDATE mst_model
-- SET image_list = 'hokuto4go.jpg'
-- WHERE model_cd = '該当の機種コード';

-- 例2: milliongod_gaisen の画像ファイル名を修正
-- UPDATE mst_model
-- SET image_list = 'milliongod_gaisen.jpg'
-- WHERE model_cd = '該当の機種コード';

-- 例3: zenigata の画像ファイル名を修正
-- UPDATE mst_model
-- SET image_list = 'zenigata.jpg'
-- WHERE model_cd = '該当の機種コード';

-- ================================================================

-- 【クエリ5】現在のトップページ表示対象機種（詳細版）
-- 目的: IndexページのSearchMachineBaseメソッドで取得される機種を確認
SELECT
    mm.model_cd AS '機種コード',
    mm.model_name AS '機種名',
    mu.unit_name AS '世代（号機）',
    mm.image_list AS '画像ファイル名',
    CONCAT('/data/img/model/', mm.image_list) AS '完全パス',
    mm.disp_flg AS '表示フラグ',
    mm.disp_order AS '表示順',
    COUNT(DISTINCT lm.machine_no) AS '設置台数'
FROM mst_model mm
LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg = 0
LEFT JOIN lnk_machine lm ON mm.model_cd = lm.model_cd AND lm.del_flg = 0
WHERE mm.del_flg = 0
  AND mm.disp_flg = 1
GROUP BY mm.model_cd, mm.model_name, mu.unit_name, mm.image_list, mm.disp_flg, mm.disp_order
ORDER BY mm.disp_order ASC;

-- ================================================================

-- 【クエリ6】実際にサーバーに存在する画像ファイル一覧
-- 注意: これはPHPで確認する必要があります
-- データベースからは確認できないため、以下のファイルパスを確認してください：
-- /var/www/html/data/img/model/hokuto4go.jpg
-- /var/www/html/data/img/model/milliongod_gaisen.jpg
-- /var/www/html/data/img/model/zenigata.jpg

-- ================================================================

-- 【クエリ7】コーナー情報の確認
-- 目的: トップページのコーナータブに表示される情報
SELECT
    corner_no AS 'コーナーNo',
    corner_name AS 'コーナー名',
    corner_roman AS 'コーナー英名',
    del_flg AS '削除フラグ'
FROM mst_corner
WHERE del_flg = 0
ORDER BY corner_no ASC;

-- ================================================================

-- 【確認ポイント】
-- 1. image_list カラムの値が実際のファイル名と一致しているか
-- 2. disp_flg = 1 になっているか（表示対象）
-- 3. del_flg = 0 になっているか（削除されていない）
-- 4. 画像ファイルが /data/img/model/ に存在するか
--
-- 【既知の画像ファイル】
-- - hokuto4go.jpg (26,467 bytes)
-- - milliongod_gaisen.jpg (313,566 bytes)
-- - zenigata.jpg (72,553 bytes)
--
-- ================================================================
