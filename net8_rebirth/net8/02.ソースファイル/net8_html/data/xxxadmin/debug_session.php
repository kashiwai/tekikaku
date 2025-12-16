<?php
/**
 * セッションデバッグ（一時ファイル - 使用後削除）
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Debug</h1>";

// 基本設定ファイル読み込み
require_once('../../_etc/require_files_admin.php');

echo "<h2>1. 定数確認</h2>";
echo "<table border='1' style='border-collapse:collapse;'>";
echo "<tr><th>定数</th><th>値</th></tr>";
echo "<tr><td>DOMAIN</td><td>" . (defined('DOMAIN') ? DOMAIN : 'NOT DEFINED') . "</td></tr>";
echo "<tr><td>SESSION_SID_ADMIN</td><td>" . (defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NOT DEFINED') . "</td></tr>";
echo "<tr><td>SESSION_SEC_ADMIN</td><td>" . (defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 'NOT DEFINED') . "</td></tr>";
echo "<tr><td>URL_ADMIN</td><td>" . (defined('URL_ADMIN') ? URL_ADMIN : 'NOT DEFINED') . "</td></tr>";
echo '<tr><td>$_SERVER[SERVER_NAME]</td><td>' . htmlspecialchars($_SERVER['SERVER_NAME']) . '</td></tr>';
echo "</table>";

echo "<h2>2. クッキー確認</h2>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";

echo "<h2>3. セッション状態（session_start前）</h2>";
echo "<p>session_status(): " . session_status() . " (1=disabled, 1=none, 2=active)</p>";

echo "<h2>4. SmartSession テスト</h2>";
try {
    $session = new SmartSession(
        URL_ADMIN . "login.php",
        defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 3600,
        defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NET8ADMIN',
        defined('DOMAIN') ? DOMAIN : $_SERVER["SERVER_NAME"],
        false
    );

    echo "<p>SmartSession instance created OK</p>";

    $firstSession = $session->start();
    echo "<p>start() result: " . ($firstSession ? 'true (new session)' : 'false (existing session)') . "</p>";

    echo "<h3>セッション変数</h3>";
    echo "<pre>" . print_r($_SESSION, true) . "</pre>";

    echo "<h3>AdminInfo チェック</h3>";
    if (isset($session->AdminInfo)) {
        echo "<p style='color:green;'>AdminInfo EXISTS</p>";
        $adminInfoObj = $session->AdminInfo;
        if (is_object($adminInfoObj) && method_exists($adminInfoObj, 'getArrayCopy')) {
            $adminInfo = $adminInfoObj->getArrayCopy();
        } else {
            $adminInfo = (array)$adminInfoObj;
        }
        echo "<pre>" . print_r($adminInfo, true) . "</pre>";

        echo "<h3>DB checkAdmin テスト</h3>";
        if (isset($adminInfo['admin_id']) && isset($adminInfo['admin_pass'])) {
            $db = new NetDB();
            $checkRet = $db->checkAdmin($adminInfo['admin_id'], $adminInfo['admin_pass']);
            echo "<p>checkAdmin result: " . ($checkRet ? '<span style="color:green;">TRUE</span>' : '<span style="color:red;">FALSE</span>') . "</p>";
        } else {
            echo "<p style='color:red;'>admin_id or admin_pass not set in session</p>";
        }
    } else {
        echo "<p style='color:red;'>AdminInfo NOT SET - you need to login first</p>";
        echo "<p><a href='login.php'>Go to Login Page</a></p>";
    }

    echo "<h3>session_domain チェック</h3>";
    echo "<p>session_domain in session: " . (isset($session->session_domain) ? $session->session_domain : 'NOT SET') . "</p>";
    echo "<p>Expected DOMAIN: " . DOMAIN . "</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>5. TemplateAdmin 読み込みテスト</h2>";
try {
    echo "<p>Attempting to load TemplateAdmin...</p>";
    ob_start();
    $template = new TemplateAdmin(true, false, false);
    $output = ob_get_clean();
    echo "<p style='color:green;'>TemplateAdmin loaded successfully!</p>";
} catch (Error $e) {
    echo "<p style='color:red;'>PHP Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Exception $e) {
    echo "<p style='color:red;'>Exception: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
