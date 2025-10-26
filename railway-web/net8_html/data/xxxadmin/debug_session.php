<?php
// セッションデバッグ用ファイル
require_once('../../_etc/require_files_admin.php');

// セッション確認
session_name('ADMIN_SID');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

echo "<h1>Session Debug Info</h1>";
echo "<h2>Session ID:</h2>";
echo "<pre>" . session_id() . "</pre>";

echo "<h2>Session Name:</h2>";
echo "<pre>" . session_name() . "</pre>";

echo "<h2>Cookie ADMIN_SID:</h2>";
echo "<pre>" . (isset($_COOKIE['ADMIN_SID']) ? $_COOKIE['ADMIN_SID'] : 'NOT SET') . "</pre>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Session File:</h2>";
$session_file = '/tmp/sess_' . session_id();
echo "<pre>File: " . $session_file . "</pre>";
if (file_exists($session_file)) {
    echo "<pre>Content: " . file_get_contents($session_file) . "</pre>";
} else {
    echo "<pre>FILE NOT FOUND</pre>";
}

echo "<h2>All Session Files:</h2>";
echo "<pre>";
$files = glob('/tmp/sess_*');
foreach ($files as $file) {
    echo $file . " (" . filesize($file) . " bytes)\n";
    if (filesize($file) > 0) {
        echo "  Content: " . substr(file_get_contents($file), 0, 200) . "\n";
    }
}
echo "</pre>";
?>
