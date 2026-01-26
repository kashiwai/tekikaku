-- =====================================================
-- NET8 データベースセキュリティ設定（実行可能版）
-- 生成日時: 2025-11-07
-- =====================================================

-- ステップ1: 新しいセキュアユーザーの作成

-- アプリケーション用ユーザー（本番用）
CREATE USER IF NOT EXISTS 'net8_app_secure'@'%'
IDENTIFIED BY 'ruAd29ddiIZq9EcVbrqKb9jv';

-- 権限付与（必要最小限）
GRANT SELECT, INSERT, UPDATE ON net8_dev.* TO 'net8_app_secure'@'%';
GRANT EXECUTE ON net8_dev.* TO 'net8_app_secure'@'%';

-- 読み取り専用ユーザー（レポート/分析用）
CREATE USER IF NOT EXISTS 'net8_readonly'@'%'
IDENTIFIED BY 'LcbgfWkl4GTrZk8X4Vhnwn92';

-- 権限付与
GRANT SELECT ON net8_dev.* TO 'net8_readonly'@'%';

-- 管理用ユーザー（緊急時のみ使用）
CREATE USER IF NOT EXISTS 'net8_admin'@'%'
IDENTIFIED BY 'Vm3i55gqDJd21x9kkE9ahiI6';

-- 管理権限付与
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON net8_dev.* TO 'net8_admin'@'%';

-- 権限を反映
FLUSH PRIVILEGES;

-- ステップ2: 作成したユーザーの権限確認
SHOW GRANTS FOR 'net8_app_secure'@'%';
SHOW GRANTS FOR 'net8_readonly'@'%';
SHOW GRANTS FOR 'net8_admin'@'%';

-- =====================================================
-- セットアップ完了！
-- 次のステップ:
-- 1. Railway環境変数を更新
--    DB_USER=net8_app_secure
--    DB_PASSWORD=ruAd29ddiIZq9EcVbrqKb9jv
--
-- 2. アプリケーションの動作確認
--
-- 3. 動作確認後、古いユーザーを削除:
--    DROP USER IF EXISTS 'net8tech001'@'%';
-- =====================================================



 data/xxxadmin/owner.php
     data/xxxadmin/signaling.php
	   data/xxxadmin/image_upload.php
     data/xxxadmin/machine_control.php
     data/xxxadmin/maker.php
     data/xxxadmin/index.php
     data/xxxadmin/purchase.php
  data/xxxadmin/address.php
     data/xxxadmin/streaming.php
     data/xxxadmin/pointgrant.php
	     data/xxxadmin/debug_session.php
     data/xxxadmin/logout.php
     data/xxxadmin/api_public/sessionAPI_admin.php



