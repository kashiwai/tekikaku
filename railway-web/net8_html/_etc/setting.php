<?php
/**
 * NET8 Database Connection Settings (Docker Environment)
 *
 * このファイルはDocker環境用のデータベース接続設定です
 * 本番環境では.envファイルから環境変数を読み込んでください
 */

// データベース接続設定（Railway対応）
define('DB_HOST', getenv('DATABASE_HOST') ?: getenv('MYSQLHOST') ?: getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DATABASE_NAME') ?: getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'net8_dev');
define('DB_USER', getenv('DATABASE_USER') ?: getenv('MYSQLUSER') ?: getenv('DB_USER') ?: 'net8user');
define('DB_PASSWORD', getenv('DATABASE_PASSWORD') ?: getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: getenv('DB_PASSWORD') ?: 'net8pass');
define('DB_CHARSET', 'utf8mb4');

// データベースDSN（Data Source Name）
// PDO形式（get_db_connection関数用）
define('DB_DSN_PDO', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
// MDB2形式（SmartDBクラス用）
define('DB_DSN', 'mysql://' . DB_USER . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME);

// データベース接続オプション
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// シグナリングサーバ設定
define('SIGNALING_HOST', getenv('SIGNALING_HOST') ?: 'localhost');
define('SIGNALING_PORT', getenv('SIGNALING_PORT') ?: '443');
define('SIGNALING_KEY', getenv('PEERJS_KEY') ?: getenv('SIGNALING_KEY') ?: 'peerjs');
define('SIGNALING_PATH', getenv('SIGNALING_PATH') ?: '/');

// STUN/TURNサーバ設定（将来の実装用）
define('STUN_SERVER', getenv('STUN_SERVER') ?: 'stun:stun.l.google.com:19302');
define('TURN_SERVER', getenv('TURN_SERVER') ?: '');
define('TURN_USERNAME', getenv('TURN_USERNAME') ?: '');
define('TURN_CREDENTIAL', getenv('TURN_CREDENTIAL') ?: '');

// エラーログ設定
define('ERROR_LOG_PATH', '/var/www/html/_sys/log/error.log');
define('ACCESS_LOG_PATH', '/var/www/html/_sys/log/access.log');
