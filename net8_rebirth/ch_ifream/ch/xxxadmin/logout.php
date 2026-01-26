<?php
/**
 * NET8 管理画面 - ログアウト（SmartSession統合版）
 * Updated: 2025-12-16
 */

// 基本設定ファイル読み込み
require_once('../../_etc/require_files_admin.php');

// 定数設定（未定義の場合のみ）
if (!defined("URL_ADMIN")) {
    define("URL_ADMIN", "/xxxadmin/");
}

// SmartSessionを使用してセッション破棄
$sessionSec = defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 3600;
$sessionSid = defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NET8ADMIN';
$domain = defined('DOMAIN') ? DOMAIN : $_SERVER["SERVER_NAME"];
$loginUrl = URL_ADMIN . 'login.php';

// SmartSessionインスタンス作成
$session = new SmartSession(
    $loginUrl,
    $sessionSec,
    $sessionSid,
    $domain,
    false  // リダイレクトはclearメソッドで行う
);

// セッション開始（既存セッションに接続）
$session->start();

// セッションクリア＆リダイレクト
$session->clear(true);  // true = ログイン画面へリダイレクト
?>
