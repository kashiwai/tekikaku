-- =====================================================
-- NET8 SDK v1.1.0-beta データベーススキーマ修正
-- game_sessions.user_id を NULL許可に変更
-- =====================================================

-- 現在の外部キー制約を確認
SELECT
    CONSTRAINT_NAME,
    TABLE_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'game_sessions'
  AND COLUMN_NAME = 'user_id'
  AND CONSTRAINT_NAME != 'PRIMARY';

-- ステップ1: 外部キー制約を削除
ALTER TABLE game_sessions
DROP FOREIGN KEY game_sessions_ibfk_1;

-- ステップ2: user_id カラムをNULL許可に変更
ALTER TABLE game_sessions
MODIFY COLUMN user_id INT NULL COMMENT 'ユーザーID（オプション）';

-- ステップ3: 外部キー制約を再作成（ON DELETE CASCADEを維持）
ALTER TABLE game_sessions
ADD CONSTRAINT game_sessions_ibfk_1
FOREIGN KEY (user_id)
REFERENCES sdk_users(id)
ON DELETE CASCADE;

-- 確認: user_id カラムがNULL許可になっているか確認
DESCRIBE game_sessions;

-- 確認: 外部キー制約が再作成されているか確認
SHOW CREATE TABLE game_sessions;

-- =====================================================
-- テストデータ挿入（オプション）
-- =====================================================

-- user_id = NULL のレコードが挿入できることを確認
-- INSERT INTO game_sessions
-- (session_id, user_id, api_key_id, machine_no, model_cd, model_name, points_consumed, status)
-- VALUES
-- ('test_session_null_user', NULL, 1, 9999, 'HOKUTO4GO', '北斗の拳', 0, 'pending');

-- 確認後に削除
-- DELETE FROM game_sessions WHERE session_id = 'test_session_null_user';

-- =====================================================
-- 完了メッセージ
-- =====================================================
SELECT 'game_sessions.user_id をNULL許可に変更完了' AS status;
