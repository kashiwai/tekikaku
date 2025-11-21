-- 画像パス修正SQL（img/model/を削除）
-- 実行前の状態確認
SELECT '=== 修正前の状態 ===' as status;
SELECT model_cd, model_name, image_list
FROM mst_model
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
ORDER BY model_no;

-- 修正実行
UPDATE mst_model
SET image_list = REPLACE(image_list, 'img/model/', '')
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
  AND image_list LIKE 'img/model/%';

-- 修正後の状態確認
SELECT '=== 修正後の状態 ===' as status;
SELECT model_cd, model_name, image_list
FROM mst_model
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
ORDER BY model_no;
