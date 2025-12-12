<?php
// セッション初期化
require_once(__DIR__ . "/session_init.php");

// セッション完全破棄
$_SESSION = array();
session_destroy();

// ログイン画面へリダイレクト
header("Location: /xxxadmin/login.php");
exit();
?>