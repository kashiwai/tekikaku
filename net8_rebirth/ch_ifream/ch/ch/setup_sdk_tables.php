<?php
/**
 * SDK テーブルセットアップスクリプト
 * SDK v1.1.0 テーブル作成
 */

header('Content-Type: text/plain; charset=utf-8');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "🚀 SDK v1.1.0 テーブルセットアップ開始...\n\n";

    // 1. api_keysテーブル作成
    echo "📋 api_keysテーブル作成中...\n";
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
    echo "✅ api_keysテーブル作成完了\n\n";

    // 2. デモ用APIキー挿入
    echo "🔑 デモ用APIキー作成中...\n";
    $stmt = $pdo->prepare("
        INSERT IGNORE INTO `api_keys` (
          `key_value`,
          `key_type`,
          `name`,
          `environment`,
          `rate_limit`,
          `is_active`
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute(['pk_demo_12345', 'public', 'Demo API Key', 'test', 10000, 1]);
    $stmt->execute(['pk_test_demo_2025', 'public', 'Test Demo 2025', 'test', 10000, 1]);
    echo "✅ デモ用APIキー作成完了\n\n";

    // 3. 作成されたAPIキーを確認
    $stmt = $pdo->query("SELECT id, key_value, name, environment, is_active FROM api_keys");
    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 作成されたAPIキー:\n";
    foreach ($keys as $key) {
        echo "  - ID: {$key['id']}, Key: {$key['key_value']}, Name: {$key['name']}, Env: {$key['environment']}\n";
    }
    echo "\n";

    echo "✅ SDKテーブルセットアップ完了！\n";
    echo "\n📝 次のAPIキーを使用してテストできます:\n";
    echo "  - pk_demo_12345\n";
    echo "  - pk_test_demo_2025\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
