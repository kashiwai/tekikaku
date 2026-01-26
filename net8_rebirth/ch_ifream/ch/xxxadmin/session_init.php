<?php
/**
 * 共通セッション初期化
 */
 
// セッション設定（Railway環境用）
ini_set("session.save_path", "/tmp");
ini_set("session.gc_maxlifetime", 3600);
ini_set("session.cookie_lifetime", 0);
ini_set("session.cookie_httponly", 1);

// セッション開始
if (session_status() == PHP_SESSION_NONE) {
    session_name("NET8ADMIN");
    session_start();
}

// セッション確認関数
function checkAdminSession($redirect = true) {
    if (!isset($_SESSION["AdminInfo"])) {
        if ($redirect) {
            header("Location: /xxxadmin/login.php");
            exit();
        }
        return false;
    }
    
    // セッション更新
    $_SESSION["last_access"] = time();
    return true;
}

// ログイン状態の確認
function isLoggedIn() {
    return isset($_SESSION["AdminInfo"]) && !empty($_SESSION["AdminInfo"]);
}

// 管理者情報の取得
function getAdminInfo() {
    return $_SESSION["AdminInfo"] ?? null;
}
?>