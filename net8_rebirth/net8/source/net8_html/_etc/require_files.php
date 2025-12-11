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
// NOTE: SmartSessionクラスがセッション管理を行うため、ここではsession_start()を呼ばない
// 2つのセッション管理システムの競合を防ぐためコメントアウト
// if (session_status() === PHP_SESSION_NONE) {
//     session_name(defined('SESSION_NAME') ? SESSION_NAME : 'NET8_SESSION');
//     @session_start(); // @でエラー抑制
// }

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

// グローバル変数定義（search.phpなどで使用）
$GLOBALS["viewcountList"] = array(10, 20, 30, 50, 100);
$GLOBALS["orderTypeList"] = array(
    "mm.add_dt" => "登録日順",
    "mm.model_name" => "機種名順",
    "mm.maker_no" => "メーカー順"
);

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

// ===================================================================
// 営業時間設定をDBから読み込み
// ===================================================================
// NetDBクラスが読み込まれた後、mst_settingテーブルから営業時間設定を取得
// setting_base.phpの定数定義をオーバーライドするためグローバル変数に格納
// ===================================================================

$GLOBALS['RUNTIME_CONFIG'] = [];

try {
    if (class_exists('NetDB')) {
        $db = new NetDB();

        // 営業時間関連の設定を取得
        $sql = "SELECT setting_key, setting_val
                FROM mst_setting
                WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
                  AND del_flg = 0";

        $result = $db->query($sql);

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            $GLOBALS['RUNTIME_CONFIG'][$row['setting_key']] = $row['setting_val'];
        }

        // デバッグログ（必要に応じて有効化）
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log('[営業時間設定] DB読み込み完了: ' . json_encode($GLOBALS['RUNTIME_CONFIG']));
        }
    }
} catch (Exception $e) {
    // DB接続エラー時はsetting_base.phpの定数定義をフォールバックとして使用
    error_log('[営業時間設定] DB読み込みエラー（定数定義をフォールバックとして使用）: ' . $e->getMessage());
}

// ===================================================================
// 営業時間設定取得ヘルパー関数
// ===================================================================
// DBから取得した値を優先、なければ定数定義を使用
// ===================================================================

if (!function_exists('get_business_hours_config')) {
    /**
     * 営業時間設定を取得
     *
     * @param string $key 設定キー (GLOBAL_OPEN_TIME, GLOBAL_CLOSE_TIME, REFERENCE_TIME)
     * @return string 設定値
     */
    function get_business_hours_config($key) {
        // 1. DBから取得した値を優先
        if (isset($GLOBALS['RUNTIME_CONFIG'][$key])) {
            return $GLOBALS['RUNTIME_CONFIG'][$key];
        }

        // 2. 定数定義をフォールバック
        if (defined($key)) {
            return constant($key);
        }

        // 3. デフォルト値
        $defaults = [
            'GLOBAL_OPEN_TIME' => '10:00',
            'GLOBAL_CLOSE_TIME' => '22:00',
            'REFERENCE_TIME' => '04:00'
        ];

        return $defaults[$key] ?? '';
    }
}
