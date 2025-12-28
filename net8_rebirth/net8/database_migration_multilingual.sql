-- ====================================================================
-- NET8 データベース多言語化マイグレーション
-- 作成日: 2025-12-28
-- 対象: mst_model テーブル
-- 目的: ゲーム機種名・説明文の多言語対応（日本語・韓国語・英語）
-- ====================================================================

-- 1. mst_model テーブルに多言語カラム追加
ALTER TABLE mst_model
ADD COLUMN model_name_ja VARCHAR(200) COMMENT '機種名（日本語）' AFTER model_name,
ADD COLUMN model_name_ko VARCHAR(200) COMMENT '機種名（韓国語）' AFTER model_name_ja,
ADD COLUMN model_name_en VARCHAR(200) COMMENT '機種名（英語）' AFTER model_name_ko,
ADD COLUMN description_ja TEXT COMMENT '説明（日本語）' AFTER model_name_en,
ADD COLUMN description_ko TEXT COMMENT '説明（韓国語）' AFTER description_ja,
ADD COLUMN description_en TEXT COMMENT '説明（英語）' AFTER description_ko;

-- 2. 既存のmodel_nameデータをmodel_name_jaに移行
UPDATE mst_model
SET model_name_ja = model_name
WHERE model_name_ja IS NULL OR model_name_ja = '';

-- 3. 確認クエリ（実行後に確認用）
-- SELECT model_no, model_cd, model_name, model_name_ja, model_name_ko, model_name_en
-- FROM mst_model
-- LIMIT 10;

-- ====================================================================
-- 注意事項:
-- 1. このマイグレーションを実行後、model_name_ko と model_name_en は NULL です
-- 2. 翻訳データは別途投入が必要です（update_translations.sql を参照）
-- 3. 既存のmodel_nameカラムは後方互換性のため残します
-- ====================================================================
