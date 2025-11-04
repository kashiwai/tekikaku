<?php
/**
 * NET8 Require Files
 *
 * 共通インクルードファイル
 * 全PHPファイルから読み込まれる基本設定とライブラリ
 */

// エラー報告設定
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);
ini_set('display_errors', defined('DEBUG_MODE') && DEBUG_MODE ? '1' : '0');

// セッション設定（最初に実行）
if (session_status() === PHP_SESSION_NONE) {
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'NET8_SESSION');
    @session_start(); // @でエラー抑制
}

// 設定ファイル読み込み
require_once(__DIR__ . '/setting.php');          // データベース接続設定
require_once(__DIR__ . '/setting_base.php');     // サイト基本設定
require_once(__DIR__ . '/license.php');          // ライセンス設定
require_once(__DIR__ . '/messages.php');         // メッセージ定義

// アプリケーション定数定義
// 言語フォルダを含むHTMLテンプレートディレクトリ
if (!defined('DIR_HTML')) {
    $lang = defined('FOLDER_LANG') ? FOLDER_LANG : 'ja';
    define('DIR_HTML', __DIR__ . '/../_html/' . $lang . '/');
}
if (!defined('TYPE_PC')) define('TYPE_PC', 0);                            // PCアクセスタイプ
if (!defined('TYPE_SMART_PHONE')) define('TYPE_SMART_PHONE', 1);         // スマートフォンアクセスタイプ
if (!defined('TYPE_MOBILE')) define('TYPE_MOBILE', 2);                    // モバイルアクセスタイプ

// 共通ライブラリ読み込み
$lib_dir = __DIR__ . '/../_lib/';
$sys_dir = __DIR__ . '/../_sys/';

// Core libraries - 依存関係を考慮した順序で読み込み
$required_libs = [
    // 基本ライブラリ（依存なし）
    $lib_dir . 'SmartDB.php',
    $lib_dir . 'SmartGeneral.php',
    $lib_dir . 'SmartSqlString.php',

    // テンプレートとセッション
    $lib_dir . 'SmartTemplate.php',
    $lib_dir . 'SmartSession.php',

    // NetDB（TemplateUserに必要）
    $sys_dir . 'NetDB.php',

    // TemplateUser（SmartTemplateに依存）
    $sys_dir . 'TemplateUser.php',

    // ポイント管理
    $sys_dir . 'PlayPoint.php',

    // その他のライブラリ（TemplateUserに依存する可能性あり）
    $lib_dir . 'SmartAutoCheck.php',
    $lib_dir . 'SmartChecker.php',

    // システム関数
    $sys_dir . 'RefTimeFunc.php',
    // SmartMailSend.phpは後で必要に応じて読み込む
    // $lib_dir . 'SmartMailSend.php',
];

foreach ($required_libs as $lib) {
    if (file_exists($lib)) {
        require_once($lib);
    }
}

// Load common.php if exists
if (file_exists($lib_dir . 'common.php')) {
    require_once($lib_dir . 'common.php');
}

// データベース接続関数
function get_db_connection() {
    try {
        $dsn = defined('DB_DSN_PDO') ? DB_DSN_PDO : 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD, DB_OPTIONS);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection error: ' . $e->getMessage());
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            die('Database connection failed: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please contact administrator.');
        }
    }
}

// 共通ヘルパー関数
if (!function_exists('get_self')) {
    function get_self() {
        return $_SERVER['PHP_SELF'] ?? '';
    }
}

if (!function_exists('h')) {
    function h($str) {
        return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}
