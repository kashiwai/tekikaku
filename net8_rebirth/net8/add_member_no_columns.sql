-- SDK v1.1.0: パートナーユーザーとNET8ユーザーの紐づけ用カラム追加
-- 実行日時: 2025-11-22

-- 1. sdk_usersテーブルにmember_noカラムを追加（NET8側のユーザーとの紐づけ）
ALTER TABLE sdk_users
ADD COLUMN member_no INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ',
ADD INDEX idx_member_no (member_no);

-- 2. game_sessionsテーブルにmember_noカラムを追加
ALTER TABLE game_sessions
ADD COLUMN member_no INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ',
ADD INDEX idx_member_no (member_no);

-- 確認用クエリ
SELECT
    'sdk_users' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'sdk_users'
AND COLUMN_NAME = 'member_no'

UNION ALL

SELECT
    'game_sessions' as table_name,
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'game_sessions'
AND COLUMN_NAME = 'member_no';
