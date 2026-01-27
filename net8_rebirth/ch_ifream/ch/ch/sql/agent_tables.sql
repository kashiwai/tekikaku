-- ============================================
-- Windows PowerShell Agent Control System
-- Database Tables
-- ============================================

-- Agent登録テーブル
CREATE TABLE IF NOT EXISTS agents (
    agent_id VARCHAR(50) PRIMARY KEY COMMENT 'エージェントID (例: CAMERA-001-0068)',
    hostname VARCHAR(100) DEFAULT NULL COMMENT 'ホスト名',
    ip_address VARCHAR(45) DEFAULT NULL COMMENT 'IPアドレス',
    mac_address VARCHAR(20) DEFAULT NULL COMMENT 'MACアドレス',
    last_seen DATETIME DEFAULT NULL COMMENT '最終通信日時',
    status ENUM('online', 'offline', 'unknown') DEFAULT 'unknown' COMMENT '状態',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_last_seen (last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='エージェント一覧';

-- コマンドキューテーブル
CREATE TABLE IF NOT EXISTS command_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    agent_id VARCHAR(50) NOT NULL COMMENT 'エージェントID',
    command TEXT NOT NULL COMMENT '実行コマンド',
    status ENUM('pending', 'sent', 'done', 'error', 'timeout') DEFAULT 'pending' COMMENT '状態',
    priority INT DEFAULT 0 COMMENT '優先度 (高いほど優先)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '登録日時',
    sent_at DATETIME DEFAULT NULL COMMENT '送信日時',
    completed_at DATETIME DEFAULT NULL COMMENT '完了日時',
    created_by VARCHAR(50) DEFAULT NULL COMMENT '登録者 (admin/api/ai)',
    INDEX idx_agent_status (agent_id, status),
    INDEX idx_status_priority (status, priority DESC, created_at ASC),
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='コマンドキュー';

-- コマンド実行結果テーブル
CREATE TABLE IF NOT EXISTS command_results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    command_id INT NOT NULL COMMENT 'コマンドキューID',
    agent_id VARCHAR(50) NOT NULL COMMENT 'エージェントID',
    output LONGTEXT COMMENT '実行結果',
    exit_code INT DEFAULT NULL COMMENT '終了コード',
    execution_time_ms INT DEFAULT NULL COMMENT '実行時間(ms)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '結果受信日時',
    INDEX idx_agent_id (agent_id),
    INDEX idx_command_id (command_id),
    FOREIGN KEY (command_id) REFERENCES command_queue(id) ON DELETE CASCADE,
    FOREIGN KEY (agent_id) REFERENCES agents(agent_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='コマンド実行結果';

-- API Key管理テーブル（既存がなければ）
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    api_key VARCHAR(64) NOT NULL UNIQUE COMMENT 'APIキー',
    name VARCHAR(100) NOT NULL COMMENT 'キー名称',
    role ENUM('agent', 'admin', 'ai') DEFAULT 'agent' COMMENT '権限',
    is_active TINYINT(1) DEFAULT 1 COMMENT '有効フラグ',
    last_used_at DATETIME DEFAULT NULL COMMENT '最終使用日時',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_api_key (api_key),
    INDEX idx_role_active (role, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='APIキー管理';

-- 初期APIキー（開発用）
INSERT IGNORE INTO api_keys (api_key, name, role) VALUES
('agent_dev_key_2024', 'Development Agent Key', 'agent'),
('admin_dev_key_2024', 'Development Admin Key', 'admin'),
('ai_dev_key_2024', 'Development AI Key', 'ai');
