-- おすすめカテゴリーマスターテーブル作成
-- 2025-12-28 作成
-- トップページのおすすめ機体セクションを管理画面から管理可能にする

CREATE TABLE IF NOT EXISTS `mst_recommended_category` (
  `category_no` int(11) NOT NULL AUTO_INCREMENT COMMENT 'カテゴリー番号',
  `category_name` varchar(100) NOT NULL COMMENT 'カテゴリー名（日本語）',
  `category_roman` varchar(100) DEFAULT NULL COMMENT 'カテゴリー名（英語）',
  `category_icon` varchar(50) DEFAULT NULL COMMENT 'アイコン（絵文字またはクラス名）',
  `link_url` varchar(255) DEFAULT NULL COMMENT 'リンク先URL（例: ./?CN=new）',
  `disp_order` int(11) DEFAULT 0 COMMENT '表示順序',
  `del_flg` tinyint(1) NOT NULL DEFAULT 0 COMMENT '削除フラグ（0:有効 1:削除）',
  `reg_no` int(11) DEFAULT NULL COMMENT '登録者番号',
  `reg_dt` datetime DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
  `upd_no` int(11) DEFAULT NULL COMMENT '更新者番号',
  `upd_dt` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',
  `del_no` int(11) DEFAULT NULL COMMENT '削除者番号',
  `del_dt` datetime DEFAULT NULL COMMENT '削除日時',
  PRIMARY KEY (`category_no`),
  KEY `idx_disp_order` (`disp_order`),
  KEY `idx_del_flg` (`del_flg`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='おすすめカテゴリーマスター';

-- 初期データ投入（現在のハードコード値をそのまま移行）
INSERT INTO `mst_recommended_category`
  (`category_name`, `category_roman`, `category_icon`, `link_url`, `disp_order`)
VALUES
  ('新台', 'New Machines', '🆕', './?CN=new', 1),
  ('パチスロ', 'Pachislot', '🎰', './', 2),
  ('パチンコ', 'Pachinko', '🎯', './', 3),
  ('人気機種', 'Popular', '⭐', './', 4),
  ('ジャックポット', 'Jackpot', '🏆', './', 5),
  ('クラシック', 'Classic', '🎮', './', 6);
