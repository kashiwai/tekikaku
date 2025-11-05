-- ================================================
-- NET8 AI Control System - Machine Management Table
-- マシン管理テーブル（40台のWindows PC管理用）
-- ================================================
-- 作成日: 2025-11-05
-- 環境: GCP Cloud SQL (136.116.70.86:3306 / net8_dev)
-- ================================================

-- 既存テーブルを削除（開発用・本番環境では要注意）
-- DROP TABLE IF EXISTS dat_machine;

-- マシン管理テーブル作成
CREATE TABLE IF NOT EXISTS dat_machine (
    -- 基本情報
    machine_no INT PRIMARY KEY COMMENT 'マシン番号（1-40）',
    name VARCHAR(50) NOT NULL COMMENT 'マシン名（MACHINE-01など）',
    camera_no INT NOT NULL COMMENT 'カメラ番号（1-40）',

    -- 接続情報
    signaling_id VARCHAR(50) UNIQUE NOT NULL COMMENT 'PeerJS ID（PEER001など）',
    ip_address VARCHAR(45) COMMENT 'IPアドレス',
    mac_address VARCHAR(17) COMMENT 'MACアドレス',
    chrome_rd_session_id VARCHAR(255) COMMENT 'Chrome Remote Desktop ID',

    -- セキュリティ
    token VARCHAR(255) NOT NULL COMMENT '認証トークン（MCP Server接続用）',

    -- ステータス管理
    status ENUM('online', 'offline', 'error') DEFAULT 'offline' COMMENT 'ステータス',
    last_heartbeat DATETIME COMMENT '最終接続時刻（死活監視用）',

    -- タイムスタンプ
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '作成日時',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新日時',

    -- インデックス
    INDEX idx_status (status),
    INDEX idx_signaling_id (signaling_id),
    INDEX idx_last_heartbeat (last_heartbeat)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='マシン管理テーブル（AI Control System）';

-- ================================================
-- 初期データ挿入（マシン1台目のみ）
-- ================================================

-- 初回のみINSERT（既に存在する場合は無視）
INSERT IGNORE INTO dat_machine (
    machine_no,
    name,
    camera_no,
    signaling_id,
    token,
    status
) VALUES (
    1,
    'MACHINE-01',
    1,
    'PEER001',
    CONCAT('net8_m001_', MD5(CONCAT(UNIX_TIMESTAMP(), RAND()))),
    'offline'
);

-- ================================================
-- テーブル作成確認
-- ================================================

SELECT
    '✅ dat_machine テーブル作成完了' AS status,
    COUNT(*) AS record_count,
    'マシン1台分のデータを挿入しました' AS message
FROM dat_machine;

-- ================================================
-- 作成されたデータの確認
-- ================================================

SELECT
    machine_no,
    name,
    camera_no,
    signaling_id,
    LEFT(token, 20) AS token_preview,
    status,
    created_at
FROM dat_machine
ORDER BY machine_no;
