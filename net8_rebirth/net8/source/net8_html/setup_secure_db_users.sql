-- =====================================================
-- NET8 データベースセキュリティ設定
-- 実行日: 2025-11-07
-- 目的: 最小権限の原則に基づいたDBユーザー作成
-- =====================================================

-- ステップ1: 既存ユーザーの確認
-- まず、現在のユーザーを確認します
SELECT '=== 現在のnet8関連ユーザー ===';
SELECT user, host, plugin FROM mysql.user WHERE user LIKE 'net8%' OR user LIKE '%app%';

-- ステップ2: 新しいセキュアユーザーの作成
-- ⚠️ パスワードは必ず変更してください！

-- アプリケーション用ユーザー（本番用）
-- 必要最小限の権限: SELECT, INSERT, UPDATE, EXECUTE
SELECT '=== アプリケーション用ユーザー作成 ===';
CREATE USER IF NOT EXISTS 'net8_app_secure'@'%'
IDENTIFIED BY 'Secure2025!Net8@App#ChangeME';

-- 権限付与
GRANT SELECT, INSERT, UPDATE ON net8_dev.* TO 'net8_app_secure'@'%';
GRANT EXECUTE ON net8_dev.* TO 'net8_app_secure'@'%';

-- 読み取り専用ユーザー（レポート/分析用）
SELECT '=== 読み取り専用ユーザー作成 ===';
CREATE USER IF NOT EXISTS 'net8_readonly'@'%'
IDENTIFIED BY 'ReadOnly2025!Net8@View#ChangeME';

-- 権限付与
GRANT SELECT ON net8_dev.* TO 'net8_readonly'@'%';

-- 管理用ユーザー（フル権限・緊急時のみ使用）
SELECT '=== 管理用ユーザー作成 ===';
CREATE USER IF NOT EXISTS 'net8_admin'@'%'
IDENTIFIED BY 'Admin2025!Net8@Mgmt#ChangeME';

-- 管理権限付与（DROPとGRANTを除く）
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX ON net8_dev.* TO 'net8_admin'@'%';

-- 権限を反映
FLUSH PRIVILEGES;

-- ステップ3: 作成したユーザーの権限確認
SELECT '=== 新しいユーザーの権限確認 ===';
SHOW GRANTS FOR 'net8_app_secure'@'%';
SHOW GRANTS FOR 'net8_readonly'@'%';
SHOW GRANTS FOR 'net8_admin'@'%';

-- ステップ4: 古いユーザーの権限確認（削除前）
SELECT '=== 古いユーザーの権限 ===';
-- 古いユーザーが存在する場合のみ実行
-- SHOW GRANTS FOR 'net8tech001'@'%';
-- SHOW GRANTS FOR 'net8user'@'%';

-- =====================================================
-- ⚠️ 重要: 以下のコマンドは新しいユーザーでの接続確認後に実行してください
-- =====================================================

-- ステップ5: 古いユーザーの削除（コメントアウト）
-- 新しいユーザーでアプリが正常動作することを確認してから実行

-- 既存ユーザーを削除
-- DROP USER IF EXISTS 'net8tech001'@'%';
-- DROP USER IF EXISTS 'net8user'@'%';

-- または、権限を剥奪して一時的に残す
-- REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'net8tech001'@'%';
-- REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'net8user'@'%';
-- FLUSH PRIVILEGES;

-- =====================================================
-- 完了！
-- =====================================================

SELECT '=== セットアップ完了 ===';
SELECT 'アプリケーション設定を更新してください:';
SELECT 'DB_USER=net8_app_secure';
SELECT 'DB_PASSWORD=[設定したパスワード]';

-- 最終確認: すべてのnet8ユーザーとその権限
SELECT '=== 最終ユーザー一覧 ===';
SELECT user, host,
       CONCAT('SHOW GRANTS FOR ''', user, '''@''', host, ''';') as show_grants_command
FROM mysql.user
WHERE user LIKE 'net8%'
ORDER BY user;
