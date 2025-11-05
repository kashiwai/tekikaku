-- 機種画像をBLOBでDB保存するためのカラム追加
-- Railway環境ではファイルシステムが永続化されないため、画像はDBに保存する必要がある

ALTER TABLE `mst_model`
  ADD COLUMN `image_list_data` MEDIUMBLOB NULL COMMENT 'リスト画像データ（BLOB）' AFTER `image_list`,
  ADD COLUMN `image_detail_data` MEDIUMBLOB NULL COMMENT '詳細画像データ（BLOB）' AFTER `image_detail`,
  ADD COLUMN `image_reel_data` MEDIUMBLOB NULL COMMENT 'リール画像データ（BLOB）' AFTER `image_reel`;

-- MEDIUMBLOB: 最大16MB（機種画像に十分なサイズ）
-- LONGBLOB（最大4GB）も利用可能だが、MEDIUMBLOB で十分

-- 画像のMIMEタイプも保存すると便利
ALTER TABLE `mst_model`
  ADD COLUMN `image_list_mime` VARCHAR(50) NULL COMMENT 'リスト画像MIMEタイプ' AFTER `image_list_data`,
  ADD COLUMN `image_detail_mime` VARCHAR(50) NULL COMMENT '詳細画像MIMEタイプ' AFTER `image_detail_data`,
  ADD COLUMN `image_reel_mime` VARCHAR(50) NULL COMMENT 'リール画像MIMEタイプ' AFTER `image_reel_data`;
