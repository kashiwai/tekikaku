<?php
/**
 * NET8 管理画面 - ログイン（完全修正版 v2）
 */

// セッション初期化
require_once(__DIR__ . "/session_init.php");

// 既にログインしている場合はダッシュボードへ
if (isLoggedIn()) {
    header("Location: /xxxadmin/index.php");
    exit();
}

// DB接続情報
define("DB_HOST", "136.116.70.86");
define("DB_USER", "net8tech001");
define("DB_PASS", "Nene11091108!!");
define("DB_NAME", "net8_dev");

// メイン処理
$action = $_POST["M"] ?? "";
if ($action === "proc") {
    ProcLogin();
} else {
    DispLogin();
}

function DispLogin($message = "") {
    $id = htmlspecialchars($_POST["ID"] ?? "");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ログイン</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px;
        }
        .login-container {
            background: white; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%; max-width: 420px; padding: 48px 40px;
        }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon { font-size: 64px; margin-bottom: 16px; }
        .logo-text { font-size: 28px; font-weight: 700; color: #667eea; }
        .login-title { text-align: center; color: #0f172a; font-size: 20px; font-weight: 600; margin-bottom: 32px; }
        .error-message {
            background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;
        }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; color: #475569; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-input {
            width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 8px;
            font-size: 15px; transition: all 0.2s; background: #f8fafc;
        }
        .form-input:focus { outline: none; border-color: #667eea; background: white; }
        .login-button {
            width: 100%; padding: 14px; background: #667eea; color: white; border: none;
            border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer;
        }
        .login-button:hover { background: #4f46e5; }
        .debug { background: #f0f9ff; padding: 15px; margin-top: 20px; border-radius: 8px; font-size: 12px; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🎮</div>
            <div class="logo-text">NET8 Admin</div>
        </div>
        <h1 class="login-title">管理画面ログイン</h1>
        
        <?php if (!empty($message)): ?>
        <div class="error-message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="M" value="proc">
            
            <div class="form-group">
                <label class="form-label">管理者ID</label>
                <input type="text" name="ID" class="form-input" value="<?= $id ?>" 
                       placeholder="admin" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">パスワード</label>
                <input type="password" name="PASS" class="form-input" 
                       placeholder="admin123" required>
            </div>

            <button type="submit" class="login-button">ログイン</button>
        </form>
        
        <div class="debug">
            <strong>テストアカウント:</strong> admin / admin123<br>
            <strong>セッション状態:</strong> <?= session_id() ? "開始済み" : "未開始" ?><br>
            <strong>現在時刻:</strong> <?= date("Y-m-d H:i:s") ?>
        </div>
    </div>
</body>
</html>
<?php
}

function ProcLogin() {
    $admin_id = trim($_POST["ID"] ?? "");
    $admin_pass = trim($_POST["PASS"] ?? "");
    
    if (empty($admin_id) || empty($admin_pass)) {
        DispLogin("IDとパスワードを入力してください");
        return;
    }
    
    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($mysqli->connect_error) {
            throw new Exception("DB接続エラー");
        }
        
        $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg 
                FROM mst_admin 
                WHERE admin_id = ? AND del_flg = 0 LIMIT 1";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($admin_pass, $row["admin_pass"])) {
                // セッション完全初期化
                session_regenerate_id(true);
                
                // ログイン情報を保存
                $_SESSION["AdminInfo"] = $row;
                $_SESSION["login_time"] = time();
                $_SESSION["last_access"] = time();
                $_SESSION["authenticated"] = true;
                
                // 最終ログイン更新
                $updateSql = "UPDATE mst_admin SET login_dt = NOW() WHERE admin_no = ?";
                $updateStmt = $mysqli->prepare($updateSql);
                $updateStmt->bind_param("i", $row["admin_no"]);
                $updateStmt->execute();
                $updateStmt->close();
                
                $stmt->close();
                $mysqli->close();
                
                // 必ずindex.phpへリダイレクト
                header("Location: /xxxadmin/index.php");
                exit();
            }
        }
        
        $stmt->close();
        $mysqli->close();
        
    } catch (Exception $e) {
        DispLogin("システムエラーが発生しました: " . $e->getMessage());
        return;
    }
    
    DispLogin("ログインIDまたはパスワードが正しくありません");
}
?>