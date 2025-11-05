-- NET8 テストデータ投入SQL
-- パチンコ管理システム用サンプルデータ

USE net8_dev;

-- メーカーマスタ
INSERT INTO mst_maker (maker_no, maker_name, maker_roman, pachi_flg, slot_flg, disp_flg, del_flg, add_dt, upd_dt) VALUES
(1, 'サンセイ', 'SANSEI', 1, 0, 1, 0, NOW(), NOW()),
(2, '三洋', 'SANYO', 1, 0, 1, 0, NOW(), NOW()),
(3, 'SANKYO', 'SANKYO', 1, 0, 1, 0, NOW(), NOW()),
(4, '平和', 'HEIWA', 1, 0, 1, 0, NOW(), NOW()),
(5, '藤商事', 'FUJISHOJI', 1, 0, 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE maker_name=VALUES(maker_name), upd_dt=NOW();

-- 機種マスタ
INSERT INTO mst_model (model_no, maker_no, model_name, model_ver, del_flg, create_dt, update_dt) VALUES
(1, 1, '新世紀エヴァンゲリオン', '15', 0, NOW(), NOW()),
(2, 2, '海物語', '4', 0, NOW(), NOW()),
(3, 3, 'フィーバー真花月', '2', 0, NOW(), NOW()),
(4, 4, 'CR大工の源さん', '超韋駄天', 0, NOW(), NOW()),
(5, 5, 'キングパルサー', 'DOT', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE model_name=VALUES(model_name), update_dt=NOW();

-- オーナーマスタ
INSERT INTO mst_owner (owner_no, owner_name, zip, pref, address, tel, del_flg, create_dt, update_dt) VALUES
(1, '本店', '100-0001', '東京都', '千代田区千代田1-1', '03-1234-5678', 0, NOW(), NOW()),
(2, '新宿店', '160-0022', '東京都', '新宿区新宿3-1-1', '03-2345-6789', 0, NOW(), NOW()),
(3, '渋谷店', '150-0002', '東京都', '渋谷区渋谷1-1-1', '03-3456-7890', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE owner_name=VALUES(owner_name), update_dt=NOW();

-- コーナーマスタ
INSERT INTO mst_corner (corner_no, owner_no, corner_name, del_flg, create_dt, update_dt) VALUES
(1, 1, 'Aコーナー', 0, NOW(), NOW()),
(2, 1, 'Bコーナー', 0, NOW(), NOW()),
(3, 2, 'メインコーナー', 0, NOW(), NOW()),
(4, 3, 'エントランス', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE corner_name=VALUES(corner_name), update_dt=NOW();

-- 実機マスタ
INSERT INTO mst_machine (machine_no, owner_no, corner_no, model_no, machine_name, machine_status, del_flg, create_dt, update_dt) VALUES
(1, 1, 1, 1, 'エヴァ1号機', 1, 0, NOW(), NOW()),
(2, 1, 1, 1, 'エヴァ2号機', 1, 0, NOW(), NOW()),
(3, 1, 2, 2, '海物語1号機', 1, 0, NOW(), NOW()),
(4, 2, 3, 3, '花月1号機', 1, 0, NOW(), NOW()),
(5, 3, 4, 4, '源さん1号機', 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE machine_name=VALUES(machine_name), update_dt=NOW();

-- 会員マスタ（テストユーザー）
INSERT INTO mst_member (member_no, member_id, member_pass, member_name, member_name_kana, nickname, sex, birthday, zip, pref, address, tel, mail, point, del_flg, member_state, create_dt, update_dt) VALUES
(1, 'test001', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', '山田太郎', 'ヤマダタロウ', 'やまちゃん', 1, '1990-01-01', '100-0001', '東京都', '千代田区千代田1-1-1', '090-1234-5678', 'test001@example.com', 1000, 0, 0, NOW(), NOW()),
(2, 'test002', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', '佐藤花子', 'サトウハナコ', 'はなちゃん', 2, '1992-05-15', '160-0022', '東京都', '新宿区新宿2-2-2', '090-2345-6789', 'test002@example.com', 2500, 0, 0, NOW(), NOW()),
(3, 'test003', '$2y$10$iW5hIS4W1jCnMdqmZTRfSOVltxtnLWVX2H2E3MbAy.KLxCuPz6A1m', '鈴木一郎', 'スズキイチロウ', 'いっちゃん', 1, '1985-12-25', '150-0002', '東京都', '渋谷区渋谷3-3-3', '090-3456-7890', 'test003@example.com', 500, 0, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE member_name=VALUES(member_name), update_dt=NOW();

-- プレイ履歴（サンプル）
INSERT INTO his_play (play_no, member_no, machine_no, play_date, play_time, play_result, play_point, del_flg, create_dt) VALUES
(1, 1, 1, CURDATE(), '10:30:00', 1, 100, 0, NOW()),
(2, 1, 1, CURDATE(), '11:00:00', 0, -50, 0, NOW()),
(3, 2, 3, CURDATE(), '14:20:00', 1, 200, 0, NOW()),
(4, 3, 5, CURDATE(), '16:45:00', 1, 150, 0, NOW())
ON DUPLICATE KEY UPDATE play_point=VALUES(play_point);

-- ポイント履歴
INSERT INTO his_point (point_no, member_no, point_type, point_value, point_memo, del_flg, create_dt) VALUES
(1, 1, 1, 1000, '新規登録ボーナス', 0, NOW()),
(2, 2, 1, 1000, '新規登録ボーナス', 0, NOW()),
(3, 3, 1, 1000, '新規登録ボーナス', 0, NOW()),
(4, 1, 2, 100, 'プレイ勝利', 0, NOW()),
(5, 2, 2, 200, 'プレイ勝利', 0, NOW())
ON DUPLICATE KEY UPDATE point_value=VALUES(point_value);

-- 商品マスタ
INSERT INTO mst_goods (goods_no, goods_name, goods_price, goods_point, goods_stock, goods_status, del_flg, create_dt, update_dt) VALUES
(1, 'オリジナルTシャツ', 0, 500, 100, 1, 0, NOW(), NOW()),
(2, 'マグカップ', 0, 300, 50, 1, 0, NOW(), NOW()),
(3, 'キーホルダー', 0, 100, 200, 1, 0, NOW(), NOW()),
(4, 'ステッカーセット', 0, 50, 500, 1, 0, NOW(), NOW()),
(5, 'ポスター', 0, 200, 80, 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE goods_name=VALUES(goods_name), update_dt=NOW();

-- ギフトマスタ
INSERT INTO mst_gift (gift_no, gift_name, gift_point, gift_stock, gift_status, del_flg, create_dt, update_dt) VALUES
(1, 'Amazonギフト券 500円', 500, 1000, 1, 0, NOW(), NOW()),
(2, 'Amazonギフト券 1000円', 1000, 500, 1, 0, NOW(), NOW()),
(3, 'QUOカード 500円', 500, 800, 1, 0, NOW(), NOW()),
(4, 'iTunesカード 1000円', 1000, 300, 1, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE gift_name=VALUES(gift_name), update_dt=NOW();

-- 設定マスタ
INSERT INTO mst_setting (setting_key, setting_value, setting_memo, del_flg, create_dt, update_dt) VALUES
('SITE_NAME', 'NET8パチンコ管理システム', 'サイト名', 0, NOW(), NOW()),
('POINT_RATE', '100', 'ポイント交換レート', 0, NOW(), NOW()),
('MAINTENANCE_MODE', '0', 'メンテナンスモード(0:通常, 1:メンテ中)', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), update_dt=NOW();

SELECT '✅ テストデータの投入が完了しました！' AS status;
