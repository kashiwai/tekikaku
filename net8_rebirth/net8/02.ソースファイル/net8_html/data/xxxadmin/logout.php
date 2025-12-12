<?php
// ログアウト処理
if (session_status() == PHP_SESSION_NONE) {
    session_name("NET8ADMIN");
    session_start();
}

$_SESSION = array();
session_destroy();

// ログイン画面へリダイレクト
header("Location: /xxxadmin/login.php");
exit();
?>