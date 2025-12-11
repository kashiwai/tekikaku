<?php
/**
 * SDK v1.1.0 完全セットアップスクリプト
 * 全てのSDKテーブルを作成・更新
 */

header('Content-Type: text/plain; charset=utf-8');

require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "🚀 SDK v1.1.0 完全セットアップ開始...\n\n";

    // 1. api_keysテーブル
    echo "=== 1. api_keysテーブル ===\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `api_keys` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(10) UNSIGNED NULL,
          `key_value` VARCHAR(100) NOT NULL UNIQUE,
          `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
          `name` VARCHAR(100) NULL,
          `environment` VARCHAR(20) NOT NULL DEFAULT 'test',
          `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000,
          `is_active` TINYINT(4) NOT NULL DEFAULT 1,
          `last_used_at` DATETIME NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `expires_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_key_value` (`key_value`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_active` (`is_active`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "✅ api_keysテーブル作成/確認完了\n\n";

    // 2. デモAPIキー挿入
    echo "=== 2. デモAPIキー作成 ===\n";
    $stmt = $pdo->prepare("INSERT IGNORE INTO `api_keys` (key_value, name, environment, is_active) VALUES (?, ?, ?, ?)");
    $stmt->execute(['pk_demo_12345', 'Demo API Key', 'test', 1]);
    echo "✅ デモAPIキー作成完了\n\n";

    // 3. sdk_usersテーブル
    echo "=== 3. sdk_usersテーブル ===\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `sdk_users` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `partner_user_id` VARCHAR(255) NOT NULL,
          `api_key_id` INT(10) UNSIGNED NOT NULL,
          `member_no` INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ',
          `email` VARCHAR(255) NULL,
          `username` VARCHAR(255) NULL,
          `metadata` TEXT NULL,
          `is_active` TINYINT(4) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_partner_user_api` (`partner_user_id`, `api_key_id`),
          KEY `idx_api_key_id` (`api_key_id`),
          KEY `idx_member_no` (`member_no`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "✅ sdk_usersテーブル作成/確認完了\n\n";

    // 4. user_balancesテーブル
    echo "=== 4. user_balancesテーブル ===\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `user_balances` (
          `user_id` INT(10) UNSIGNED NOT NULL,
          `balance` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_deposited` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_consumed` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_won` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `last_transaction_at` DATETIME NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "✅ user_balancesテーブル作成/確認完了\n\n";

    // 5. game_sessionsテーブル作成
    echo "=== 5. game_sessionsテーブル ===\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `game_sessions` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `session_id` VARCHAR(100) NOT NULL UNIQUE,
          `user_id` INT(10) UNSIGNED NULL,
          `api_key_id` INT(10) UNSIGNED NULL,
          `member_no` INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ',
          `partner_user_id` VARCHAR(255) NULL COMMENT 'パートナー側のユーザーID',
          `machine_no` INT(10) UNSIGNED NOT NULL,
          `model_cd` VARCHAR(50) NULL,
          `model_name` VARCHAR(255) NULL,
          `points_consumed` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `points_won` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
          `result` VARCHAR(50) NULL,
          `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ended_at` DATETIME NULL,
          `play_duration` INT(10) UNSIGNED NULL,
          `result_data` TEXT NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` VARCHAR(512) NULL,
          PRIMARY KEY (`id`),
          KEY `idx_session_id` (`session_id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_member_no` (`member_no`),
          KEY `idx_partner_user_id` (`partner_user_id`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "✅ game_sessionsテーブル作成/確認完了\n\n";

    // 6. point_transactionsテーブル
    echo "=== 6. point_transactionsテーブル ===\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `point_transactions` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(10) UNSIGNED NOT NULL,
          `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
          `type` VARCHAR(20) NOT NULL,
          `amount` INT(11) NOT NULL,
          `balance_before` INT(10) UNSIGNED NOT NULL,
          `balance_after` INT(10) UNSIGNED NOT NULL,
          `game_session_id` VARCHAR(100) NULL,
          `description` VARCHAR(512) NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_user_id` (`user_id`),
          KEY `idx_transaction_id` (`transaction_id`),
          KEY `idx_game_session_id` (`game_session_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
    ");
    echo "✅ point_transactionsテーブル作成/確認完了\n\n";

    echo "✅ SDK v1.1.0 完全セットアップ完了！\n\n";
    echo "📝 次のAPIキーが利用可能です:\n";
    echo "  - pk_demo_12345\n\n";

    echo "🎮 テストコマンド:\n";
    echo "curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \\\n";
    echo "  -H \"Authorization: Bearer pk_demo_12345\" \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -d '{\"modelId\": \"TEST_MODEL\", \"userId\": \"user123\"}'\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
