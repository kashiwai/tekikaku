-- ミリオンゴッド～神々の凱旋～を機種マスタに登録
-- メーカー: ユニバーサルエンターテインメント (maker_no: 86)

-- まず既存のミリオンゴッドを確認
SELECT model_no, model_name, maker_no, image_list
FROM mst_model
WHERE model_name LIKE '%ミリオンゴッド%';

-- ミリオンゴッド～神々の凱旋～を登録
INSERT INTO mst_model (
    model_name,
    model_roman,
    maker_no,
    image_list,
    add_dt,
    upd_dt
) VALUES (
    'ミリオンゴッド～神々の凱旋～',
    'MILLION GOD KAMIGAMI NO GAISEN',
    86,
    'milliongod_gaisen.jpg',
    NOW(),
    NOW()
) ON DUPLICATE KEY UPDATE
    model_name = 'ミリオンゴッド～神々の凱旋～',
    model_roman = 'MILLION GOD KAMIGAMI NO GAISEN',
    maker_no = 86,
    upd_dt = NOW();

-- 登録確認
SELECT
    model_no,
    model_name,
    model_roman,
    maker_no,
    image_list,
    add_dt
FROM mst_model
WHERE model_name LIKE '%ミリオンゴッド%';

-- 全機種一覧
SELECT model_no, model_name, maker_no FROM mst_model ORDER BY model_no;
