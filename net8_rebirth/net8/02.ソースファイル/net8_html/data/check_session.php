<?php
/*
 * check_session.php
 * セッション情報確認用デバッグページ
 */

// インクルード
require_once('../_etc/require_files.php');

// ユーザ系表示コントロールのインスタンス生成
$template = new TemplateUser(false);

echo "<h1>セッション情報確認</h1>";
echo "<pre>";

echo "=== セッション状態 ===\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NONE') . "\n";
echo "Session ID: " . session_id() . "\n\n";

echo "=== $_SESSION の内容 ===\n";
print_r($_SESSION);

echo "\n=== template->Session の内容 ===\n";
if (isset($template->Session)) {
    echo "Session Object exists: YES\n";
    if (isset($template->Session->UserInfo)) {
        echo "UserInfo exists: YES\n";
        print_r($template->Session->UserInfo);
    } else {
        echo "UserInfo exists: NO\n";
    }
} else {
    echo "Session Object exists: NO\n";
}

echo "\n=== Cookie 情報 ===\n";
print_r($_COOKIE);

echo "\n=== URL_SSL_SITE 設定 ===\n";
echo "URL_SSL_SITE: " . URL_SSL_SITE . "\n";
echo "DOMAIN: " . DOMAIN . "\n";
echo "SESSION_SID: " . SESSION_SID . "\n";

echo "</pre>";
