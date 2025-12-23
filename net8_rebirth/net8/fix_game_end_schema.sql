-- =====================================================
-- NET8 game_end.php データベーススキーマ修正
-- 作成日: 2025-12-23
-- 目的: SQLSTATE[22001] および SQLSTATE[01000] エラー修正
-- =====================================================

-- 現在のスキーマ確認
SELECT 'Before Modification:' as status;
SHOW COLUMNS FROM his_play WHERE Field = 'out_action_type';
SHOW COLUMNS FROM game_sessions WHERE Field = 'result';

-- =====================================================
-- 1. his_play.out_action_type カラム拡張
-- =====================================================
-- 変更前: char(2)
-- 変更後: varchar(20)
-- 理由: 'sdk_end' (7文字) が格納できるようにする

ALTER TABLE his_play
MODIFY COLUMN out_action_type VARCHAR(20) DEFAULT NULL
COMMENT 'アクション種別（SDK対応: sdk_end等）';

-- =====================================================
-- 2. game_sessions.result ENUM値追加
-- =====================================================
-- 変更前: ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout')
-- 変更後: 上記 + 'completed', 'cancelled' を追加
-- 理由: game_end.php で 'completed' と 'cancelled' を使用している

ALTER TABLE game_sessions
MODIFY COLUMN result ENUM(
    'playing',
    'win',
    'lose',
    'draw',
    'error',
    'timeout',
    'completed',   -- 新規追加
    'cancelled'    -- 新規追加
) DEFAULT 'playing'
COMMENT 'ゲーム結果（completed/cancelled追加）';

-- =====================================================
-- 修正後の確認
-- =====================================================
SELECT 'After Modification:' as status;
SHOW COLUMNS FROM his_play WHERE Field = 'out_action_type';
SHOW COLUMNS FROM game_sessions WHERE Field = 'result';

-- =====================================================
-- テストデータで動作確認（オプション）
-- =====================================================
-- 以下はテスト用（実行は任意）
-- INSERT INTO his_play (machine_no, start_dt, end_dt, member_no, out_action_type)
-- VALUES (1, NOW(), NOW(), 1, 'sdk_end');

-- SELECT * FROM his_play WHERE out_action_type = 'sdk_end';

-- =====================================================
-- 完了メッセージ
-- =====================================================
SELECT
    'Schema modification completed successfully!' as status,
    NOW() as executed_at;
