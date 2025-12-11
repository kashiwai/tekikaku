-- NET8 SDK API Keys Management
-- Version: 1.0.0-beta
-- Created: 2025-11-06

-- APIキー管理テーブル
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- API使用ログテーブル
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- デモ用APIキーを挿入
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_demo_12345',
  'public',
  'Demo API Key',
  'test',
  10000,
  1
);

-- 本番用APIキーのサンプル
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_live_abcdef123456',
  'public',
  'Production API Key 1',
  'live',
  100000,
  1
);

-- 確認
SELECT * FROM api_keys;
