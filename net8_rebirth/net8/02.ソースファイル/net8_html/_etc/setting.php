<?php
/**
 * NET8 Database Connection Settings
 *
 * このファイルはデータベース接続設定です
 * - Railway環境: 環境変数から自動的に読み込み
 * - ローカル環境: .env.railway ファイルから読み込み
 */

// ==========================================
// .env ファイル読み込み（ローカル環境のみ）
// ==========================================
if (!getenv('RAILWAY_ENVIRONMENT')) {
    // Railway以外の環境では .env.railway を読み込む
    $env_file = __DIR__ . '/../../../.env.railway';

    if (file_exists($env_file)) {
        $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // コメント行をスキップ
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // KEY=VALUE 形式をパース
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // 環境変数が未設定の場合のみ設定
                if (!getenv($key)) {
                    putenv("$key=$value");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

// データベース接続設定
define('DB_HOST', getenv('DB_HOST') ?: 'db');
define('DB_NAME', getenv('DB_NAME') ?: 'net8_dev');
define('DB_USER', getenv('DB_USER') ?: 'net8user');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: 'net8pass');
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
define('SIGNALING_PORT', getenv('SIGNALING_PORT') ?: '59000');
define('SIGNALING_KEY', getenv('SIGNALING_KEY') ?: 'peerjs');
define('SIGNALING_PATH', getenv('SIGNALING_PATH') ?: '/');

// STUN/TURNサーバ設定（将来の実装用）
define('STUN_SERVER', getenv('STUN_SERVER') ?: 'stun:stun.l.google.com:19302');
define('TURN_SERVER', getenv('TURN_SERVER') ?: '');
define('TURN_USERNAME', getenv('TURN_USERNAME') ?: '');
define('TURN_CREDENTIAL', getenv('TURN_CREDENTIAL') ?: '');

// エラーログ設定
define('ERROR_LOG_PATH', '/var/www/html/_sys/log/error.log');
define('ACCESS_LOG_PATH', '/var/www/html/_sys/log/access.log');
