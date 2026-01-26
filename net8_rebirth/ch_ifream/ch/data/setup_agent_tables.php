<?php
/**
 * Agent System Database Setup
 * テーブル作成スクリプト
 */

// DB接続設定
require_once(__DIR__ . '/../_etc/setting.php');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "DB接続成功\n";
} catch (PDOException $e) {
    die("DB接続失敗: " . $e->getMessage() . "\n");
}

$sqls = [
    // Agent登録テーブル
    "agents" => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='エージェント一覧'
    ",

    // コマンドキューテーブル
    "command_queue" => "
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
            INDEX idx_status_priority (status, priority DESC, created_at ASC)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='コマンドキュー'
    ",

    // コマンド実行結果テーブル
    "command_results" => "
        CREATE TABLE IF NOT EXISTS command_results (
            id INT AUTO_INCREMENT PRIMARY KEY,
            command_id INT DEFAULT NULL COMMENT 'コマンドキューID',
            agent_id VARCHAR(50) NOT NULL COMMENT 'エージェントID',
            output LONGTEXT COMMENT '実行結果',
            exit_code INT DEFAULT NULL COMMENT '終了コード',
            execution_time_ms INT DEFAULT NULL COMMENT '実行時間(ms)',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT '結果受信日時',
            INDEX idx_agent_id (agent_id),
            INDEX idx_command_id (command_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='コマンド実行結果'
    ",

    // API Key管理テーブル
    "api_keys" => "
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='APIキー管理'
    ",
];

// テーブル作成
foreach ($sqls as $table => $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ テーブル '$table' 作成成功\n";
    } catch (PDOException $e) {
        echo "✗ テーブル '$table' 作成失敗: " . $e->getMessage() . "\n";
    }
}

// 初期APIキー挿入
$initialKeys = [
    ['agent_dev_key_2024', 'Development Agent Key', 'agent'],
    ['admin_dev_key_2024', 'Development Admin Key', 'admin'],
    ['ai_dev_key_2024', 'Development AI Key', 'ai'],
];

echo "\n初期APIキー挿入:\n";
foreach ($initialKeys as $key) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO api_keys (api_key, name, role) VALUES (?, ?, ?)");
        $stmt->execute($key);
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            echo "✓ APIキー '{$key[0]}' 挿入成功\n";
        } else {
            echo "- APIキー '{$key[0]}' 既存\n";
        }
    } catch (PDOException $e) {
        echo "✗ APIキー '{$key[0]}' 挿入失敗: " . $e->getMessage() . "\n";
    }
}

// dat_machine にカラム追加（存在しない場合）
echo "\ndat_machine テーブル更新:\n";
$alterSqls = [
    "ALTER TABLE dat_machine ADD COLUMN IF NOT EXISTS mac_address VARCHAR(20) DEFAULT NULL COMMENT 'MACアドレス'",
    "ALTER TABLE dat_machine ADD COLUMN IF NOT EXISTS last_report DATETIME DEFAULT NULL COMMENT '最終報告日時'",
];

foreach ($alterSqls as $sql) {
    try {
        $pdo->exec($sql);
        echo "✓ dat_machine カラム追加成功\n";
    } catch (PDOException $e) {
        // カラムが既に存在する場合のエラーは無視
        if (strpos($e->getMessage(), 'Duplicate column') === false) {
            echo "- dat_machine: " . $e->getMessage() . "\n";
        } else {
            echo "- カラム既存\n";
        }
    }
}

echo "\n完了！\n";
