-- NET8 SDK E2E Test History Table
-- Version: 1.0.0
-- Created: 2025-11-10

-- E2Eテスト履歴テーブル
CREATE TABLE IF NOT EXISTS `sdk_e2e_test_history` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_run_id` VARCHAR(100) NOT NULL,
  `api_key_id` INT(10) UNSIGNED NULL,
  `test_type` VARCHAR(50) NOT NULL DEFAULT 'full',
  `overall_status` VARCHAR(20) NOT NULL,
  `total_tests` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `passed_tests` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `failed_tests` INT(10) UNSIGNED NOT NULL DEFAULT 0,
  `total_duration_ms` INT(10) UNSIGNED NULL,
  `test_details` TEXT NULL,
  `errors` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test_run_id` (`test_run_id`),
  KEY `idx_api_key_id` (`api_key_id`),
  KEY `idx_overall_status` (`overall_status`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 個別テストステップ記録テーブル
CREATE TABLE IF NOT EXISTS `sdk_e2e_test_steps` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `test_run_id` VARCHAR(100) NOT NULL,
  `step_name` VARCHAR(100) NOT NULL,
  `step_order` INT(10) UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `response_time_ms` INT(10) UNSIGNED NULL,
  `request_data` TEXT NULL,
  `response_data` TEXT NULL,
  `error_message` TEXT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_test_run_id` (`test_run_id`),
  KEY `idx_step_name` (`step_name`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- テストダミー企業用のAPIキー挿入
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`,
  `expires_at`
) VALUES (
  'pk_test_dummy_partner_2025',
  'public',
  'テストパートナー株式会社 - test',
  'test',
  100000,
  1,
  DATE_ADD(NOW(), INTERVAL 10 YEAR)
) ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 確認
SELECT 'SDK E2E Test tables created successfully' AS status;
SELECT * FROM api_keys WHERE key_value = 'pk_test_dummy_partner_2025';
