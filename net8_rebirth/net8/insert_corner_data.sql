-- コーナーデータ登録
-- mst_corner テーブルにパチスロと新台を登録

INSERT INTO mst_corner (corner_no, corner_name, corner_roman, del_flg, ins_dt, upd_dt) VALUES
(1, 'パチスロ', 'pachislot', 0, NOW(), NOW()),
(2, '新台', 'shindai', 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE
  corner_name = VALUES(corner_name),
  corner_roman = VALUES(corner_roman),
  upd_dt = NOW();
