<?php
/**
 * NET8 Require Files - Admin
 *
 * 管理画面用共通インクルードファイル
 * 管理画面の全PHPファイルから読み込まれる基本設定とライブラリ
 */

// エラー報告設定（一時的に全エラー表示を有効化）
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

// セッション設定（最初に実行）
// NOTE: SmartSessionクラスがセッション管理を行うため、ここではsession_start()を呼ばない
// 2つのセッション管理システムの競合を防ぐためコメントアウト
// if (session_status() === PHP_SESSION_NONE) {
//     session_name(defined('SESSION_NAME') ? SESSION_NAME : 'NET8_ADMIN_SESSION');
//     @session_start(); // @でエラー抑制
// }

// 設定ファイル読み込み
require_once(__DIR__ . '/setting.php');          // データベース接続設定
require_once(__DIR__ . '/setting_base.php');     // サイト基本設定
require_once(__DIR__ . '/license.php');          // ライセンス設定
require_once(__DIR__ . '/messages.php');         // メッセージ定義

// アプリケーション定数定義
// 管理画面用HTMLテンプレートディレクトリ
if (!defined('DIR_HTML_ADMIN')) {
    $lang = defined('FOLDER_LANG') ? FOLDER_LANG : 'ja';
    define('DIR_HTML_ADMIN', __DIR__ . '/../_html/' . $lang . '/admin/');
}
if (!defined('DIR_HTML')) {
    define('DIR_HTML', DIR_HTML_ADMIN); // 互換性のため
}
if (!defined('TYPE_PC')) define('TYPE_PC', 0);                            // PCアクセスタイプ
if (!defined('TYPE_SMART_PHONE')) define('TYPE_SMART_PHONE', 1);         // スマートフォンアクセスタイプ
if (!defined('TYPE_MOBILE')) define('TYPE_MOBILE', 2);                    // モバイルアクセスタイプ

// 管理画面URL設定
if (!defined('URL_ADMIN')) {
    define('URL_ADMIN', defined('SITE_URL') ? SITE_URL . 'xxxadmin/' : 'https://mgg-webservice-production.up.railway.app/xxxadmin/');
}

// 管理画面セッション設定
if (!defined('SESSION_SEC_ADMIN')) {
    define('SESSION_SEC_ADMIN', 1440);  // セッション継続時間（秒） = 24分
}
if (!defined('SESSION_SID_ADMIN')) {
    define('SESSION_SID_ADMIN', 'ADMIN_SID');  // セッションID名
}

// 管理画面表示設定
if (!defined('ADMIN_LIST_ROWMAX')) {
    define('ADMIN_LIST_ROWMAX', 50);  // 管理画面リスト表示の最大行数
}

// メニュー権限設定（空配列で初期化、必要に応じて設定ファイルで上書き）
if (!isset($GLOBALS["AuthMenuID"])) {
    $GLOBALS["AuthMenuID"] = array(
        // メニューIDと権限レベルのマッピング
        // 例: 'user' => 1, 'system' => 9
    );
}

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

    // NetDB（TemplateAdminに必要）
    $sys_dir . 'NetDB.php',

    // TemplateAdmin（管理画面用）
    $sys_dir . 'TemplateAdmin.php',

    // その他のライブラリ
    $lib_dir . 'SmartAutoCheck.php',
    $lib_dir . 'SmartChecker.php',

    // システム関数
    $sys_dir . 'RefTimeFunc.php',
];

foreach ($required_libs as $lib) {
    if (file_exists($lib)) {
        require_once($lib);
    } else {
        // ライブラリが存在しない場合はエラーログに記録
        error_log('Required library not found: ' . $lib);
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

// 管理画面用：管理者ログインチェック関数
if (!function_exists('check_admin_login')) {
    function check_admin_login($redirect_to_login = true) {
        if (!isset($_SESSION['admin_no']) || empty($_SESSION['admin_no'])) {
            if ($redirect_to_login) {
                redirect('login.php');
            }
            return false;
        }
        return true;
    }
}

// 管理画面用：権限チェック関数
if (!function_exists('check_admin_auth')) {
    function check_admin_auth($required_auth = 0) {
        if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] < $required_auth) {
            return false;
        }
        return true;
    }
}
