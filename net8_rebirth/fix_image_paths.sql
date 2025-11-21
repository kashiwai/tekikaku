-- 画像パス修正SQL
-- img/model/ プレフィックスを削除してファイル名のみにする

UPDATE mst_model
SET image_list = REPLACE(image_list, 'img/model/', '')
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
  AND image_list LIKE 'img/model/%';

-- 修正結果を確認
SELECT model_cd, model_name, image_list
FROM mst_model
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
ORDER BY model_no;
