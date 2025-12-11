<?php
/**
 * NET8 SDK API Keys Setup Script (Direct SQL Execution)
 * Run this once to create API keys tables and insert demo data
 */

require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>NET8 SDK - Database Setup (Direct)</h1>";
echo "<pre>";

try {
    // PDO接続取得
    $pdo = get_db_connection();
    echo "✅ Database connection successful\n\n";

    // SQL statements defined directly in PHP
    $sql_statements = [
        // Create api_keys table
        "CREATE TABLE IF NOT EXISTS `api_keys` (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

        // Create api_usage_logs table
        "CREATE TABLE IF NOT EXISTS `api_usage_logs` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `api_key_id` INT(10) UNSIGNED NOT NULL,
          `endpoint` VARCHAR(255) NOT NULL,
          `method` VARCHAR(10) NOT NULL,
          `status_code` INT(10) UNSIGNED NULL,
          `response_time_ms` INT(10) UNSIGNED NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` VARCHAR(512) NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_api_key_id` (`api_key_id`),
          KEY `idx_created_at` (`created_at`),
          FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8",

        // Insert demo API key
        "INSERT IGNORE INTO `api_keys` (
          `key_value`, `key_type`, `name`, `environment`, `rate_limit`, `is_active`
        ) VALUES (
          'pk_demo_12345', 'public', 'Demo API Key', 'test', 10000, 1
        )",

        // Insert production sample API key
        "INSERT IGNORE INTO `api_keys` (
          `key_value`, `key_type`, `name`, `environment`, `rate_limit`, `is_active`
        ) VALUES (
          'pk_live_abcdef123456', 'public', 'Production API Key 1', 'live', 100000, 1
        )"
    ];

    echo "🔧 Executing " . count($sql_statements) . " SQL statements...\n\n";

    // Execute each statement
    $executed = 0;
    foreach ($sql_statements as $index => $sql) {
        try {
            $affected = $pdo->exec($sql);
            $executed++;

            $first_line = strtok($sql, "\n");
            echo "  ✓ Statement " . ($index + 1) . ": " . substr(trim($first_line), 0, 80) . "... (affected: $affected)\n";

        } catch (PDOException $e) {
            $error_msg = $e->getMessage();

            if (strpos($error_msg, 'already exists') !== false) {
                echo "  ⚠ Statement " . ($index + 1) . ": Table already exists (skipping)\n";
                $executed++;
            } else if (strpos($error_msg, 'Duplicate entry') !== false) {
                echo "  ⚠ Statement " . ($index + 1) . ": Duplicate entry (skipping)\n";
                $executed++;
            } else {
                echo "  ❌ Statement " . ($index + 1) . " ERROR: " . $error_msg . "\n";
                throw $e;
            }
        }
    }

    echo "\n✅ Total executed: $executed statements\n\n";

    // Verify tables
    echo "📊 Verifying tables...\n\n";
    $tables = $pdo->query("SHOW TABLES LIKE 'api_%'")->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "  ⚠ No API tables found\n";
    } else {
        echo "  Created tables: " . implode(', ', $tables) . "\n";
    }

    // Show API keys
    echo "\n🔑 API Keys:\n\n";
    $keys = $pdo->query("SELECT id, key_value, name, environment, is_active, created_at FROM api_keys ORDER BY id")->fetchAll();

    if (empty($keys)) {
        echo "  ⚠ No API keys found\n";
    } else {
        foreach ($keys as $key) {
            $status = $key['is_active'] ? '✅' : '❌';
            $created = date('Y-m-d H:i', strtotime($key['created_at']));
            echo "  $status [{$key['environment']}] {$key['key_value']} - {$key['name']} (created: $created)\n";
        }
    }

    echo "\n\n";
    echo "🎉 Setup complete!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test API: https://mgg-webservice-production.up.railway.app/api/v1/models.php\n";
    echo "2. Test Auth: https://mgg-webservice-production.up.railway.app/api/v1/auth.php\n";
    echo "3. Demo Page: https://mgg-webservice-production.up.railway.app/sdk/demo.html\n";
    echo "4. Admin: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php\n";

} catch (Exception $e) {
    echo "\n\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>