-- Migration: Add callback support to game_sessions
-- Date: 2026-01-07
-- Purpose: サーバー間コールバック機能（セキュリティ強化）

-- 1. game_sessionsテーブルにコールバック関連カラム追加
ALTER TABLE game_sessions
ADD COLUMN callback_url VARCHAR(512) NULL COMMENT 'コールバック先URL（HTTPS必須）',
ADD COLUMN callback_secret VARCHAR(256) NULL COMMENT 'Webhook署名検証用秘密鍵',
ADD COLUMN callback_status ENUM('pending', 'success', 'failed', 'skipped') DEFAULT 'pending' COMMENT 'コールバック状態',
ADD COLUMN callback_attempts INT DEFAULT 0 COMMENT 'コールバック試行回数',
ADD COLUMN callback_last_error TEXT NULL COMMENT 'コールバック最終エラー',
ADD COLUMN callback_completed_at DATETIME NULL COMMENT 'コールバック完了日時';

-- 2. インデックス追加（コールバック失敗検索用）
CREATE INDEX idx_callback_status ON game_sessions(callback_status, callback_attempts);

-- 3. コメント追加
ALTER TABLE game_sessions COMMENT = 'ゲームセッション（コールバック機能追加 2026-01-07）';

-- Rollback用SQL（問題があった場合）
-- ALTER TABLE game_sessions
-- DROP COLUMN callback_url,
-- DROP COLUMN callback_secret,
-- DROP COLUMN callback_status,
-- DROP COLUMN callback_attempts,
-- DROP COLUMN callback_last_error,
-- DROP COLUMN callback_completed_at;
-- DROP INDEX idx_callback_status ON game_sessions;
