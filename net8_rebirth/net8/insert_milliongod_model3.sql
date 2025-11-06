-- ================================================
-- 機種No.3「ミリオンゴッド4号機」登録SQL
-- ================================================

-- 機種登録（画像なし・後で追加）
INSERT INTO mst_model (
    model_no,
    category,
    model_cd,
    model_name,
    model_roman,
    type_no,
    unit_no,
    maker_no,
    renchan_games,
    tenjo_games,
    setting_list,
    image_list,
    image_detail,
    image_reel,
    prizeball_data,
    layout_data,
    remarks,
    add_no,
    add_dt,
    upd_no,
    upd_dt,
    del_flg
) VALUES (
    3,                                  -- model_no
    2,                                  -- category (2=スロット)
    'MILLIONGOD01',                     -- model_cd
    'ミリオンゴッド4号機',             -- model_name
    'MILLIONGOD',                       -- model_roman
    5,                                  -- type_no (5=タイプA)
    4,                                  -- unit_no (4=4号機)
    1,                                  -- maker_no (1=ユニバーサル系 ※要確認)
    0,                                  -- renchan_games
    9999,                               -- tenjo_games
    '',                                 -- setting_list
    '',                                 -- image_list（後で追加）
    '',                                 -- image_detail（後で追加）
    '',                                 -- image_reel（後で追加）
    '',                                 -- prizeball_data（スロットなので空）
    '{"video_portrait":0,"video_mode":4,"drum":0,"bonus_push":[{"label":"select","path":"noselect_bonus.png"}],"version":1,"hide":["changePanel"]}', -- layout_data
    '',                                 -- remarks
    1,                                  -- add_no
    NOW(),                              -- add_dt
    1,                                  -- upd_no
    NOW(),                              -- upd_dt
    0                                   -- del_flg
) ON DUPLICATE KEY UPDATE
    model_cd = 'MILLIONGOD01',
    model_name = 'ミリオンゴッド4号機',
    model_roman = 'MILLIONGOD',
    category = 2,
    type_no = 5,
    unit_no = 4,
    maker_no = 1,
    upd_dt = NOW();

-- 確認
SELECT 'ミリオンゴッド4号機 登録完了:' AS '';
SELECT model_no, model_cd, model_name, category, type_no, unit_no, maker_no
FROM mst_model
WHERE model_no = 3;
