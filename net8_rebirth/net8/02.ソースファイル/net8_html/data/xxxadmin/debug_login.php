<?php
/**
 * ログインデバッグ（一時ファイル - 使用後削除）
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Login Debug</h1>";

echo "<h2>0. BEFORE require_files_admin</h2>";
echo "<p>session_status(): " . session_status() . " (0=disabled, 1=none, 2=active)</p>";
echo "<p>session_name(): " . session_name() . "</p>";
echo "<p>session_id(): " . session_id() . "</p>";

require_once('../../_etc/require_files_admin.php');

echo "<h2>0.5 AFTER require_files_admin</h2>";
echo "<p>session_status(): " . session_status() . " (0=disabled, 1=none, 2=active)</p>";
echo "<p>session_name(): " . session_name() . "</p>";
echo "<p>session_id(): " . session_id() . "</p>";

$admin_id = 'admin';
$admin_pass = 'admin123';

echo "<h2>1. DB認証テスト</h2>";

$host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
$user = defined("DB_USER") ? DB_USER : "net8tech001";
$pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
$name = defined("DB_NAME") ? DB_NAME : "net8_dev";

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    die("DB Error: " . $mysqli->connect_error);
}

$sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg, deny_menu
        FROM mst_admin WHERE admin_id = ? AND del_flg = 0 LIMIT 1";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $admin_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo "<p style='color:green;'>User found: " . htmlspecialchars($row['admin_name']) . "</p>";

    if (password_verify($admin_pass, $row["admin_pass"])) {
        echo "<p style='color:green;'>Password: OK</p>";

        echo "<h2>2. Session Creation</h2>";

        $sessionSec = defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN : 3600;
        $sessionSid = defined('SESSION_SID_ADMIN') ? SESSION_SID_ADMIN : 'NET8ADMIN';
        $domain = defined('DOMAIN') ? DOMAIN : $_SERVER["SERVER_NAME"];
        $loginUrl = URL_ADMIN . 'login.php';

        echo "<p>Session Name: " . $sessionSid . "</p>";
        echo "<p>Domain: " . $domain . "</p>";
        echo "<p>session_name() BEFORE SmartSession: " . session_name() . "</p>";

        $session = new SmartSession($loginUrl, $sessionSec, $sessionSid, $domain, false);
        echo "<p>SmartSession created</p>";

        echo "<p>session_name() AFTER SmartSession construct: " . session_name() . "</p>";

        $startResult = $session->start(true);
        echo "<p>start(true) result: " . ($startResult ? 'true' : 'false') . "</p>";
        echo "<p>session_name() AFTER start: " . session_name() . "</p>";
        echo "<p>session_id(): " . session_id() . "</p>";

        $session->AdminInfo = $row;
        echo "<p style='color:green;'>AdminInfo saved</p>";

        echo "<h2>3. Session Check</h2>";
        echo "<p>isset AdminInfo: " . (isset($session->AdminInfo) ? 'YES' : 'NO') . "</p>";

        echo "<h3>_SESSION:</h3>";
        echo "<pre>" . print_r($_SESSION, true) . "</pre>";

        echo "<h3>Cookies:</h3>";
        echo "<pre>" . print_r($_COOKIE, true) . "</pre>";

        echo "<h2>4. Next</h2>";
        echo "<p><a href='debug_session.php'>Check debug_session.php</a></p>";

    } else {
        echo "<p style='color:red;'>Password: NG</p>";
    }
} else {
    echo "<p style='color:red;'>User not found</p>";
}

$stmt->close();
$mysqli->close();
?>
