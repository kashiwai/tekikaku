-- マスタデータ投入スクリプト
-- Railway用

-- オーナーマスタ
INSERT INTO `mst_owner`
  (`owner_cd`, `owner_name`, `owner_nickname`, `owner_pref`, `mail`, `machine_count`, `remarks`, `dummy_flg`, `del_flg`, `add_dt`, `upd_dt`)
VALUES
  ('MGG001', 'MGGオーナー', 'MGG本店', 13, 'mgg@example.com', 0, 'メインオーナー', 0, 0, NOW(), NOW()),
  ('TEST001', 'テストオーナー', 'テスト店', 27, 'test@example.com', 0, 'テスト用オーナー', 0, 0, NOW(), NOW()),
  ('DEMO001', 'デモオーナー', 'デモ店', 13, 'demo@example.com', 0, 'デモ用オーナー', 0, 0, NOW(), NOW());

-- カメラマスタ（1-20のカメラを登録）
INSERT INTO `mst_camera`
  (`camera_mac`, `camera_name`, `del_flg`, `add_dt`, `upd_dt`)
VALUES
  ('00:00:00:00:00:01', 'カメラ001', 0, NOW(), NOW()),
  ('00:00:00:00:00:02', 'カメラ002', 0, NOW(), NOW()),
  ('00:00:00:00:00:03', 'カメラ003', 0, NOW(), NOW()),
  ('00:00:00:00:00:04', 'カメラ004', 0, NOW(), NOW()),
  ('00:00:00:00:00:05', 'カメラ005', 0, NOW(), NOW()),
  ('00:00:00:00:00:06', 'カメラ006', 0, NOW(), NOW()),
  ('00:00:00:00:00:07', 'カメラ007', 0, NOW(), NOW()),
  ('00:00:00:00:00:08', 'カメラ008', 0, NOW(), NOW()),
  ('00:00:00:00:00:09', 'カメラ009', 0, NOW(), NOW()),
  ('00:00:00:00:00:0A', 'カメラ010', 0, NOW(), NOW()),
  ('00:00:00:00:00:0B', 'カメラ011', 0, NOW(), NOW()),
  ('00:00:00:00:00:0C', 'カメラ012', 0, NOW(), NOW()),
  ('00:00:00:00:00:0D', 'カメラ013', 0, NOW(), NOW()),
  ('00:00:00:00:00:0E', 'カメラ014', 0, NOW(), NOW()),
  ('00:00:00:00:00:0F', 'カメラ015', 0, NOW(), NOW()),
  ('00:00:00:00:00:10', 'カメラ016', 0, NOW(), NOW()),
  ('00:00:00:00:00:11', 'カメラ017', 0, NOW(), NOW()),
  ('00:00:00:00:00:12', 'カメラ018', 0, NOW(), NOW()),
  ('00:00:00:00:00:13', 'カメラ019', 0, NOW(), NOW()),
  ('00:00:00:00:00:14', 'カメラ020', 0, NOW(), NOW());

-- 変換レートマスタ
INSERT INTO `mst_convertPoint`
  (`convert_name`, `point`, `credit`, `draw_point`, `del_flg`, `add_dt`, `upd_dt`)
VALUES
  ('1玉1円', 1, 1, 1, 0, NOW(), NOW()),
  ('1玉2円', 2, 1, 2, 0, NOW(), NOW()),
  ('1玉4円', 4, 1, 4, 0, NOW(), NOW()),
  ('2.5円スロット', 25, 10, 25, 0, NOW(), NOW()),
  ('5円スロット', 5, 1, 5, 0, NOW(), NOW()),
  ('20円スロット', 20, 1, 20, 0, NOW(), NOW());

-- 確認用SELECT
SELECT '=== mst_owner ===' as info;
SELECT owner_no, owner_nickname FROM mst_owner WHERE del_flg = 0;

SELECT '=== mst_camera ===' as info;
SELECT camera_no, camera_name FROM mst_camera WHERE del_flg = 0 LIMIT 10;

SELECT '=== mst_convertPoint ===' as info;
SELECT convert_no, convert_name, point, credit FROM mst_convertPoint WHERE del_flg = 0;
