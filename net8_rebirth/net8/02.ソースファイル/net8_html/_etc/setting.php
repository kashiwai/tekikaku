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
// ⚠️ 重要: すべてのDB接続をGCP Cloud SQL (Railway MySQL) に統一
// Railway環境変数は使用せず、GCP DBに強制接続
define('DB_HOST', '136.116.70.86');                // GCP Cloud SQL IP（固定）
define('DB_PORT', '3306');                         // MySQLポート（固定）
define('DB_NAME', 'net8_dev');                     // データベース名（固定）
define('DB_USER', 'net8tech001');                  // DBユーザー（固定）
define('DB_PASSWORD', 'Nene11091108!!');          // DBパスワード（正しい値に更新）
define('DB_CHARSET', 'utf8mb4');

// データベースDSN（Data Source Name）
// PDO形式（get_db_connection関数用）
define('DB_DSN_PDO', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET);
// MDB2形式（SmartDBクラス用）- パスワードの特殊文字をURLエンコード
define('DB_DSN', 'mysql://' . urlencode(DB_USER) . ':' . urlencode(DB_PASSWORD) . '@' . DB_HOST . ':' . DB_PORT . '/' . DB_NAME);

// データベース接続オプション
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// シグナリングサーバ設定
define('SIGNALING_HOST', $_SERVER['SIGNALING_HOST'] ?? $_ENV['SIGNALING_HOST'] ?? getenv('SIGNALING_HOST') ?: 'mgg-signaling-production-c1bd.up.railway.app');
define('SIGNALING_PORT', $_SERVER['SIGNALING_PORT'] ?? $_ENV['SIGNALING_PORT'] ?? getenv('SIGNALING_PORT') ?: '443');
define('SIGNALING_KEY', $_SERVER['SIGNALING_KEY'] ?? $_ENV['SIGNALING_KEY'] ?? getenv('SIGNALING_KEY') ?: 'peerjs');
define('SIGNALING_PATH', $_SERVER['SIGNALING_PATH'] ?? $_ENV['SIGNALING_PATH'] ?? getenv('SIGNALING_PATH') ?: '/peerjs');

// STUN/TURNサーバ設定（将来の実装用）
define('STUN_SERVER', getenv('STUN_SERVER') ?: 'stun:stun.l.google.com:19302');
define('TURN_SERVER', getenv('TURN_SERVER') ?: '');
define('TURN_USERNAME', getenv('TURN_USERNAME') ?: '');
define('TURN_CREDENTIAL', getenv('TURN_CREDENTIAL') ?: '');

// エラーログ設定
define('ERROR_LOG_PATH', '/var/www/html/_sys/log/error.log');
define('ACCESS_LOG_PATH', '/var/www/html/_sys/log/access.log');

// WebRTC設定ファイルの読み込み
require_once(__DIR__ . '/webRTC_setting.php');

