<?php
/**
 * SDK Extension Database Setup Script
 *
 * このスクリプトはSDK v1.1.0に必要なデータベーステーブルを作成します
 *
 * 使い方:
 * https://mgg-webservice-production.up.railway.app/data/setup_sdk_extension.php?key=setup_sdk_2025
 */

// セキュリティキーチェック
$EXEC_KEY = 'setup_sdk_2025';
if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    http_response_code(403);
    die('❌ Access denied. Invalid key.');
}

// 環境変数から接続情報を取得
require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>\n<html>\n<head>\n";
echo "<meta charset='UTF-8'>\n";
echo "<title>SDK Extension Setup</title>\n";
echo "<style>body{font-family:monospace;padding:20px;background:#1e1e1e;color:#d4d4d4}";
echo ".success{color:#4ec9b0}.error{color:#f48771}.warning{color:#dcdcaa}</style>\n";
echo "</head>\n<body>\n";

echo "<h1>🚀 NET8 SDK Extension v1.1.0 Setup</h1>\n";
echo "<p>データベースマイグレーションを実行します...</p>\n<hr>\n";

try {
    $pdo = get_db_connection();
    echo "<p class='success'>✅ データベース接続成功</p>\n";
    echo "<p>Host: " . htmlspecialchars(DB_HOST) . "</p>\n";
    echo "<p>Database: " . htmlspecialchars(DB_NAME) . "</p>\n";
    echo "<hr>\n";

    // SQLファイルの内容を直接埋め込み
    $sql_statements = "
-- SDK Users Table
CREATE TABLE IF NOT EXISTS sdk_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_key_id INT NOT NULL,
    partner_user_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    display_name VARCHAR(255) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_partner_user (api_key_id, partner_user_id),
    INDEX idx_api_key (api_key_id),
    INDEX idx_email (email),
    INDEX idx_last_login (last_login_at),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Balances Table
CREATE TABLE IF NOT EXISTS user_balances (
    user_id INT PRIMARY KEY,
    balance INT NOT NULL DEFAULT 0,
    total_deposited INT NOT NULL DEFAULT 0,
    total_consumed INT NOT NULL DEFAULT 0,
    total_won INT NOT NULL DEFAULT 0,
    total_withdrawn INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Point Transactions Table
CREATE TABLE IF NOT EXISTS point_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('deposit', 'consume', 'payout', 'refund', 'adjust') NOT NULL,
    amount INT NOT NULL,
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    game_session_id VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_session (game_session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Game Sessions Table
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    api_key_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    machine_no INT NOT NULL,
    model_cd VARCHAR(50) NOT NULL,
    model_name VARCHAR(255) DEFAULT NULL,
    points_consumed INT DEFAULT 0,
    points_won INT DEFAULT 0,
    play_duration INT DEFAULT 0,
    result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout', 'cancelled') DEFAULT 'playing',
    status ENUM('playing', 'completed', 'error', 'cancelled') DEFAULT 'playing',
    error_message TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_api_key (api_key_id),
    INDEX idx_user (user_id),
    INDEX idx_machine (machine_no),
    INDEX idx_model (model_cd),
    INDEX idx_status (status),
    INDEX idx_started (started_at),
    INDEX idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

";

    // SQLステートメントを個別に実行
    $success_count = 0;
    $skip_count = 0;

    // 1. sdk_users table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS sdk_users (
            id INT PRIMARY KEY AUTO_INCREMENT,
            api_key_id INT NOT NULL,
            partner_user_id VARCHAR(255) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            display_name VARCHAR(255) DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            last_login_at TIMESTAMP NULL DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            UNIQUE KEY uk_partner_user (api_key_id, partner_user_id),
            INDEX idx_api_key (api_key_id),
            INDEX idx_email (email),
            INDEX idx_last_login (last_login_at),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ テーブル作成: sdk_users</p>\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='warning'>⚠️ 既存: sdk_users</p>\n";
            $skip_count++;
        } else {
            echo "<p class='error'>❌ sdk_users エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // 2. user_balances table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_balances (
            user_id INT PRIMARY KEY,
            balance INT NOT NULL DEFAULT 0,
            total_deposited INT NOT NULL DEFAULT 0,
            total_consumed INT NOT NULL DEFAULT 0,
            total_won INT NOT NULL DEFAULT 0,
            total_withdrawn INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            CHECK (balance >= 0)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ テーブル作成: user_balances</p>\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='warning'>⚠️ 既存: user_balances</p>\n";
            $skip_count++;
        } else {
            echo "<p class='error'>❌ user_balances エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // 3. point_transactions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS point_transactions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            transaction_id VARCHAR(100) UNIQUE NOT NULL,
            type ENUM('deposit', 'consume', 'payout', 'refund', 'adjust') NOT NULL,
            amount INT NOT NULL,
            balance_before INT NOT NULL,
            balance_after INT NOT NULL,
            game_session_id VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_type (type),
            INDEX idx_session (game_session_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ テーブル作成: point_transactions</p>\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='warning'>⚠️ 既存: point_transactions</p>\n";
            $skip_count++;
        } else {
            echo "<p class='error'>❌ point_transactions エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // 4. game_sessions table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS game_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            session_id VARCHAR(255) UNIQUE NOT NULL,
            api_key_id INT NOT NULL,
            user_id INT DEFAULT NULL,
            machine_no INT NOT NULL,
            model_cd VARCHAR(50) NOT NULL,
            model_name VARCHAR(255) DEFAULT NULL,
            points_consumed INT DEFAULT 0,
            points_won INT DEFAULT 0,
            play_duration INT DEFAULT 0,
            result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout', 'cancelled') DEFAULT 'playing',
            status ENUM('playing', 'completed', 'error', 'cancelled') DEFAULT 'playing',
            error_message TEXT DEFAULT NULL,
            metadata JSON DEFAULT NULL,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ended_at TIMESTAMP NULL DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_session (session_id),
            INDEX idx_api_key (api_key_id),
            INDEX idx_user (user_id),
            INDEX idx_machine (machine_no),
            INDEX idx_model (model_cd),
            INDEX idx_status (status),
            INDEX idx_started (started_at),
            INDEX idx_result (result)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "<p class='success'>✅ テーブル作成: game_sessions</p>\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p class='warning'>⚠️ 既存: game_sessions</p>\n";
            $skip_count++;
        } else {
            echo "<p class='error'>❌ game_sessions エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // 5. Add allowed_domains column to api_keys
    try {
        $pdo->exec("ALTER TABLE api_keys ADD COLUMN allowed_domains JSON DEFAULT NULL COMMENT 'List of domains allowed to embed iframes (for X-Frame-Options)'");
        echo "<p class='success'>✅ api_keys.allowed_domains カラム追加</p>\n";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<p class='warning'>⚠️ カラム既存: allowed_domains</p>\n";
            $skip_count++;
        } else {
            echo "<p class='error'>❌ allowed_domains エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    }

    // Dummy loop for removed code
    if (false) {
        foreach ([] as $statement) {
            try {
                $pdo->exec($statement);

            // テーブル作成かカラム追加を判定
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`?(\w+)`?\s/i', $statement, $matches);
                $table_name = $matches[1] ?? 'unknown';
                echo "<p class='success'>✅ テーブル作成/確認: {$table_name}</p>\n";
                $success_count++;
            } elseif (stripos($statement, 'ALTER TABLE') !== false) {
                echo "<p class='success'>✅ api_keys.allowed_domains カラム追加/確認</p>\n";
                $success_count++;
            }
        } catch (PDOException $e) {
            // テーブルが既に存在する場合はスキップ
            if (strpos($e->getMessage(), 'already exists') !== false ||
                strpos($e->getMessage(), 'Duplicate column') !== false) {
                if (stripos($statement, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`?(\w+)`?\s/i', $statement, $matches);
                    $table_name = $matches[1] ?? 'unknown';
                    echo "<p class='warning'>⚠️ 既存: {$table_name}</p>\n";
                } else {
                    echo "<p class='warning'>⚠️ カラム既存: allowed_domains</p>\n";
                }
                $skip_count++;
            } else {
                echo "<p class='error'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        }
    }

    echo "<hr>\n";
    echo "<p><strong>実行結果:</strong></p>\n";
    echo "<p class='success'>✅ 成功: {$success_count} 項目</p>\n";
    echo "<p class='warning'>⚠️ スキップ: {$skip_count} 項目（既存）</p>\n";

    // テーブル存在確認
    echo "<hr>\n<h2>📊 テーブル確認</h2>\n";
    $tables = ['sdk_users', 'user_balances', 'point_transactions', 'game_sessions'];

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        $exists = $stmt->fetch();

        if ($exists) {
            $count_stmt = $pdo->query("SELECT COUNT(*) as cnt FROM {$table}");
            $count = $count_stmt->fetch()['cnt'];
            echo "<p class='success'>✅ {$table}: {$count} 件</p>\n";
        } else {
            echo "<p class='error'>❌ {$table}: 存在しません</p>\n";
        }
    }

    // api_keysのallowed_domainsカラム確認
    $stmt = $pdo->query("SHOW COLUMNS FROM api_keys LIKE 'allowed_domains'");
    $column = $stmt->fetch();
    if ($column) {
        echo "<p class='success'>✅ api_keys.allowed_domains: カラム存在</p>\n";
    } else {
        echo "<p class='error'>❌ api_keys.allowed_domains: カラム不足</p>\n";
    }

    echo "<hr>\n";
    echo "<h2>🎉 セットアップ完了！</h2>\n";
    echo "<p>SDK v1.1.0 のデータベーススキーマが準備できました。</p>\n";
    echo "<p>次のステップ:</p>\n";
    echo "<ol>\n";
    echo "<li>テストAPIキーの作成</li>\n";
    echo "<li>テストユーザーの作成</li>\n";
    echo "<li>SDKデモページでゲーム起動確認</li>\n";
    echo "</ol>\n";

} catch (Exception $e) {
    echo "<p class='error'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>\n";
}

echo "</body>\n</html>";
