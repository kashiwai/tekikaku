-- 北斗の拳（4号機スロット）機種登録SQL
-- デプロイ後に実行してください

-- 機種データ挿入
INSERT INTO `mst_model` (
    `category`,
    `model_cd`,
    `model_name`,
    `model_roman`,
    `type_no`,
    `unit_no`,
    `maker_no`,
    `renchan_games`,
    `tenjo_games`,
    `image_list`,
    `image_detail`,
    `image_reel`,
    `prizeball_data`,
    `layout_data`,
    `setting_list`,
    `remarks`,
    `del_flg`,
    `add_no`,
    `add_dt`,
    `upd_no`,
    `upd_dt`
) VALUES (
    2,                              -- category: 2=スロット
    'HOKUTO4GO',                    -- model_cd: 機種コード
    '北斗の拳',                     -- model_name: 機種名
    'Fist of the North Star',      -- model_roman: 機種名（英語）
    5,                              -- type_no: 5=AT (スロットタイプ)
    4,                              -- unit_no: 4=4号機
    1,                              -- maker_no: メーカー番号（既存のメーカーNo.1を使用）
    0,                              -- renchan_games: 連チャンゲーム数
    1280,                           -- tenjo_games: 天井ゲーム数
    '',                             -- image_list: リスト画像（後で追加）
    '',                             -- image_detail: 詳細画像（後で追加）
    '',                             -- image_reel: リール画像（後で追加）
    '',                             -- prizeball_data: 賞球データ（スロットは不要）
    '{"video_portrait":0,"video_mode":4,"drum":0,"bonus_push":[],"version":2,"hide":[]}',  -- layout_data
    '',                             -- setting_list: 設定リスト
    '4号機北斗の拳',                -- remarks: 備考
    0,                              -- del_flg: 削除フラグ（0=有効）
    1,                              -- add_no: 登録者番号（管理者No.1）
    NOW(),                          -- add_dt: 登録日時
    1,                              -- upd_no: 更新者番号
    NOW()                           -- upd_dt: 更新日時
);

-- 登録確認
SELECT
    model_no,
    model_cd,
    model_name,
    model_roman,
    tenjo_games
FROM mst_model
WHERE model_cd = 'HOKUTO4GO';
