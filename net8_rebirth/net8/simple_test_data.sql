-- 簡易テストデータ（カラム名を動的に確認せずに安全に投入）
USE net8_dev;

-- メーカーマスタ（既存データがあれば更新、なければ挿入）
INSERT IGNORE INTO mst_maker (maker_no, maker_name, maker_roman, pachi_flg, slot_flg, disp_flg, del_flg) VALUES
(1, 'サンセイ', 'SANSEI', 1, 0, 1, 0),
(2, '三洋', 'SANYO', 1, 0, 1, 0),
(3, 'SANKYO', 'SANKYO', 1, 0, 1, 0);

-- 現在のデータ件数を確認
SELECT 'メーカー:', COUNT(*) as count FROM mst_maker WHERE del_flg = 0;
SELECT '管理者:', COUNT(*) as count FROM mst_admin WHERE del_flg = 0;
SELECT '会員:', COUNT(*) as count FROM mst_member WHERE del_flg = 0;
SELECT '実機:', COUNT(*) as count FROM mst_machine WHERE del_flg = 0;

SELECT '✅ 基本テストデータの確認が完了しました！' AS status;
