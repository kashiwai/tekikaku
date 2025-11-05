-- Railway DB: mst_modelのcategoryを2（スロット）に変更

UPDATE mst_model
SET category = 2
WHERE model_no = 1;

-- 確認
SELECT model_no, model_name, category FROM mst_model WHERE model_no = 1;
