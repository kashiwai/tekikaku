-- =====================================================
-- NET8 SDK Extension Database Schema
-- Version: 1.0.0
-- Created: 2025-11-18
-- Purpose: SDK機能拡張（ユーザー管理、ポイント管理、履歴管理）
-- =====================================================

-- 1. api_keysテーブル拡張（パートナードメイン管理）
-- =====================================================
ALTER TABLE api_keys
ADD COLUMN allowed_domains TEXT COMMENT 'iFrame埋め込み許可ドメイン（JSON配列形式）' AFTER environment;

-- 既存データの初期化（必要に応じて）
UPDATE api_keys
SET allowed_domains = '[]'
WHERE allowed_domains IS NULL;

-- =====================================================
-- 2. usersテーブル（SDKユーザー管理）
-- =====================================================
CREATE TABLE IF NOT EXISTS sdk_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    partner_user_id VARCHAR(255) NOT NULL COMMENT 'パートナー側のユーザーID',
    api_key_id INT NOT NULL COMMENT '紐づくAPIキーID',
    email VARCHAR(255) DEFAULT NULL COMMENT 'メールアドレス（オプション）',
    username VARCHAR(100) DEFAULT NULL COMMENT 'ユーザー名（オプション）',
    metadata JSON DEFAULT NULL COMMENT 'パートナー側の追加データ',
    is_active TINYINT(1) DEFAULT 1 COMMENT '有効フラグ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_partner_user (api_key_id, partner_user_id),
    INDEX idx_partner_user_id (partner_user_id),
    INDEX idx_api_key_id (api_key_id),
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='SDK経由で登録されたパートナーユーザー';

-- =====================================================
-- 3. user_balancesテーブル（ユーザーポイント残高）
-- =====================================================
CREATE TABLE IF NOT EXISTS user_balances (
    user_id INT PRIMARY KEY,
    balance INT NOT NULL DEFAULT 0 COMMENT 'ポイント残高',
    total_deposited INT NOT NULL DEFAULT 0 COMMENT '累計チャージ額',
    total_consumed INT NOT NULL DEFAULT 0 COMMENT '累計消費額',
    total_won INT NOT NULL DEFAULT 0 COMMENT '累計獲得額',
    last_transaction_at TIMESTAMP NULL COMMENT '最終取引日時',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES sdk_users(id) ON DELETE CASCADE,
    CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ユーザーポイント残高管理';

-- =====================================================
-- 4. point_transactionsテーブル（ポイント取引履歴）
-- =====================================================
CREATE TABLE IF NOT EXISTS point_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL COMMENT '取引ID（一意）',
    type ENUM('deposit', 'consume', 'payout', 'refund', 'adjust') NOT NULL COMMENT '取引種別',
    amount INT NOT NULL COMMENT '金額（正負あり）',
    balance_before INT NOT NULL COMMENT '取引前残高',
    balance_after INT NOT NULL COMMENT '取引後残高',
    game_session_id VARCHAR(255) DEFAULT NULL COMMENT '関連ゲームセッションID',
    reference_id VARCHAR(255) DEFAULT NULL COMMENT '外部参照ID（決済IDなど）',
    description TEXT DEFAULT NULL COMMENT '取引説明',
    metadata JSON DEFAULT NULL COMMENT '追加データ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_game_session (game_session_id),
    INDEX idx_created_at (created_at),
    INDEX idx_type (type),
    FOREIGN KEY (user_id) REFERENCES sdk_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ポイント取引履歴';

-- =====================================================
-- 5. game_sessionsテーブル（ゲームセッション履歴）
-- =====================================================
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'セッションID',
    user_id INT NOT NULL COMMENT 'ユーザーID',
    api_key_id INT NOT NULL COMMENT 'APIキーID',
    machine_no INT DEFAULT NULL COMMENT 'マシン番号',
    model_cd VARCHAR(50) NOT NULL COMMENT '機種コード',
    model_name VARCHAR(100) DEFAULT NULL COMMENT '機種名',

    -- ゲーム情報
    points_consumed INT DEFAULT 0 COMMENT '消費ポイント',
    points_won INT DEFAULT 0 COMMENT '獲得ポイント',
    play_duration INT DEFAULT NULL COMMENT 'プレイ時間（秒）',

    -- ゲーム結果
    result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout') DEFAULT 'playing' COMMENT 'ゲーム結果',
    result_data JSON DEFAULT NULL COMMENT '詳細結果データ',

    -- タイムスタンプ
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,

    -- ステータス
    status ENUM('pending', 'playing', 'completed', 'error', 'cancelled') DEFAULT 'pending' COMMENT 'セッション状態',
    error_message TEXT DEFAULT NULL COMMENT 'エラーメッセージ',

    -- メタデータ
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IPアドレス',
    user_agent TEXT DEFAULT NULL COMMENT 'ユーザーエージェント',
    referer_domain VARCHAR(255) DEFAULT NULL COMMENT '参照元ドメイン',
    metadata JSON DEFAULT NULL COMMENT '追加データ',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_user_id (user_id),
    INDEX idx_api_key_id (api_key_id),
    INDEX idx_session_id (session_id),
    INDEX idx_started_at (started_at),
    INDEX idx_status (status),
    INDEX idx_model_cd (model_cd),
    FOREIGN KEY (user_id) REFERENCES sdk_users(id) ON DELETE CASCADE,
    FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='ゲームセッション履歴';

-- =====================================================
-- 6. デモデータ挿入（テスト用）
-- =====================================================

-- デモユーザー作成（api_key_id=1と仮定）
INSERT INTO sdk_users (partner_user_id, api_key_id, email, username, metadata)
VALUES
    ('demo_user_001', 1, 'demo1@example.com', 'DemoUser1', '{"tier": "gold", "region": "jp"}'),
    ('demo_user_002', 1, 'demo2@example.com', 'DemoUser2', '{"tier": "silver", "region": "jp"}')
ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP;

-- デモユーザーの残高初期化
INSERT INTO user_balances (user_id, balance, total_deposited)
SELECT id, 10000, 10000 FROM sdk_users WHERE partner_user_id IN ('demo_user_001', 'demo_user_002')
ON DUPLICATE KEY UPDATE balance = 10000, total_deposited = 10000;

-- =====================================================
-- 7. 初期設定完了確認
-- =====================================================
SELECT
    'SDK Extension Schema Created Successfully' AS status,
    (SELECT COUNT(*) FROM sdk_users) AS demo_users_count,
    (SELECT COUNT(*) FROM user_balances) AS balances_count;

-- =====================================================
-- 8. テーブル一覧確認
-- =====================================================
SHOW TABLES LIKE 'sdk_%';
SHOW TABLES LIKE '%transaction%';
SHOW TABLES LIKE '%session%';
