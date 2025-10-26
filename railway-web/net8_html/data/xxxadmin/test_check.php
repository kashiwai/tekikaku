<?php
// セッションチェックテスト
require_once('../../_etc/require_files_admin.php');

// セッション確認
session_name('ADMIN_SID');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo "<h1>Session Check Test</h1>";

echo "<h2>Session ID:</h2>";
echo "<pre>" . session_id() . "</pre>";

echo "<h2>Session Content:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// DB接続
$db = new NetDB();

echo "<h2>Admin Info from Session:</h2>";
if (isset($_SESSION['AdminInfo'])) {
    $adminInfo = $_SESSION['AdminInfo'];
    echo "<pre>";
    print_r($adminInfo);
    echo "</pre>";

    echo "<h2>Checking Admin:</h2>";

    // ArrayObjectから値を取得する方法をテスト
    echo "<h3>Method 1: Direct array access []</h3>";
    if (is_object($adminInfo)) {
        echo "<p>admin_id via []: " . (isset($adminInfo['admin_id']) ? htmlspecialchars($adminInfo['admin_id']) : "NOT SET") . "</p>";
        echo "<p>admin_pass via []: " . (isset($adminInfo['admin_pass']) ? htmlspecialchars(substr($adminInfo['admin_pass'], 0, 30)) : "NOT SET") . "...</p>";
    }

    echo "<h3>Method 2: getArrayCopy()</h3>";
    if (is_object($adminInfo) && method_exists($adminInfo, 'getArrayCopy')) {
        $arr = $adminInfo->getArrayCopy();
        echo "<p>admin_id via getArrayCopy(): " . (isset($arr['admin_id']) ? htmlspecialchars($arr['admin_id']) : "NOT SET") . "</p>";
        echo "<p>admin_pass via getArrayCopy(): " . (isset($arr['admin_pass']) ? htmlspecialchars(substr($arr['admin_pass'], 0, 30)) : "NOT SET") . "...</p>";
    }

    echo "<h3>Method 3: (array) cast</h3>";
    if (is_object($adminInfo)) {
        $arr = (array)$adminInfo;
        echo "<pre>";
        print_r($arr);
        echo "</pre>";
    }

    if (is_object($adminInfo) && isset($adminInfo['admin_id']) && isset($adminInfo['admin_pass'])) {
        $admin_id = $adminInfo['admin_id'];
        $admin_pass = $adminInfo['admin_pass'];

        echo "<p>ID: " . htmlspecialchars($admin_id) . "</p>";
        echo "<p>Pass (first 30 chars): " . htmlspecialchars(substr($admin_pass, 0, 30)) . "...</p>";

        // DB check
        $sql = "select admin_pass from mst_admin where admin_id = '" . $db->conv_sql($admin_id, FD_STR) . "' and del_flg = 0";
        echo "<p>SQL: " . htmlspecialchars($sql) . "</p>";

        $dbPass = $db->getOne($sql);
        echo "<p>DB Pass (first 30 chars): " . htmlspecialchars(substr($dbPass, 0, 30)) . "...</p>";

        echo "<p>Match: " . (($dbPass == $admin_pass) ? "YES ✅" : "NO ❌") . "</p>";

        $checkResult = $db->checkAdmin($admin_id, $admin_pass);
        echo "<p>checkAdmin() result: " . ($checkResult ? "TRUE ✅" : "FALSE ❌") . "</p>";
    } else {
        echo "<p>AdminInfo is not an object or missing required fields</p>";
        echo "<p>Type: " . gettype($adminInfo) . "</p>";
    }
} else {
    echo "<p>AdminInfo NOT SET in session ❌</p>";
}
?>
