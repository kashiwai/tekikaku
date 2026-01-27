<?php
/**
 * 最簡単ログイン（問題解決用）
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション設定
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 0); // HTTPSでない場合は0
session_name("NET8ADMIN");
session_start();

// DB接続情報
$db_host = "136.116.70.86";
$db_user = "net8tech001";
$db_pass = "Nene11091108!!";
$db_name = "net8_dev";

function main() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    $action = $_POST["M"] ?? "";
    
    if ($action === "proc") {
        // ログイン処理
        $admin_id = $_POST["ID"] ?? "";
        $admin_pass = $_POST["PASS"] ?? "";
        
        if (empty($admin_id) || empty($admin_pass)) {
            showLogin("IDとパスワードを入力してください");
            return;
        }
        
        // DB接続
        $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($mysqli->connect_error) {
            showLogin("データベース接続エラー: " . $mysqli->connect_error);
            return;
        }
        
        // 管理者認証
        $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg 
                FROM mst_admin 
                WHERE admin_id = ? AND del_flg = 0 LIMIT 1";
        
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            showLogin("SQL準備エラー: " . $mysqli->error);
            return;
        }
        
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($admin_pass, $row["admin_pass"])) {
                // ログイン成功
                
                // セッション再生成（セキュリティ強化）
                session_regenerate_id(true);
                
                // セッション情報設定
                $_SESSION["AdminInfo"] = [
                    'admin_no' => $row["admin_no"],
                    'admin_id' => $row["admin_id"],
                    'admin_name' => $row["admin_name"],
                    'auth_flg' => $row["auth_flg"]
                ];
                $_SESSION["login_time"] = time();
                $_SESSION["last_access"] = time();
                $_SESSION["authenticated"] = true;
                
                // セッション保存を強制
                session_write_close();
                
                // 最終ログイン更新
                $updateSql = "UPDATE mst_admin SET login_dt = NOW() WHERE admin_no = ?";
                $updateStmt = $mysqli->prepare($updateSql);
                $updateStmt->bind_param("i", $row["admin_no"]);
                $updateStmt->execute();
                $updateStmt->close();
                
                $stmt->close();
                $mysqli->close();
                
                // ダッシュボードへ（絶対URL使用）
                header("Location: https://mgg-webservice-production.up.railway.app/xxxadmin/dashboard_simple.php");
                exit();
            }
        }
        
        $stmt->close();
        $mysqli->close();
        showLogin("ログインIDまたはパスワードが正しくありません");
    } else {
        // ログイン画面表示
        showLogin();
    }
}

function showLogin($message = "") {
    $id = htmlspecialchars($_POST["ID"] ?? "");
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ログイン（修正版）</title>
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
        .logo-text {
            font-size: 28px; font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }
        .login-title { text-align: center; color: #0f172a; font-size: 20px; font-weight: 600; margin-bottom: 32px; }
        .error-message {
            background: #fee2e2; border-left: 4px solid #ef4444; color: #991b1b;
            padding: 12px 16px; border-radius: 8px; margin-bottom: 24px; font-size: 14px;
        }
        .form-group { margin-bottom: 24px; }
        .form-label { display: block; color: #475569; font-size: 14px; font-weight: 600; margin-bottom: 8px; }
        .form-input {
            width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0;
            border-radius: 8px; font-size: 15px; background: #f8fafc;
        }
        .form-input:focus { outline: none; border-color: #667eea; background: white; }
        .login-button {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: all 0.3s;
        }
        .login-button:hover { transform: translateY(-2px); }
        .debug { margin-top: 20px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <div class="logo-icon">🎮</div>
            <div class="logo-text">NET8 Admin</div>
        </div>
        <h1 class="login-title">管理画面ログイン（修正版）</h1>
        
        <?php if (!empty($message)): ?>
        <div class="error-message">⚠️ <?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST" action="login_simple_fix.php">
            <input type="hidden" name="M" value="proc">
            
            <div class="form-group">
                <label class="form-label" for="admin_id">管理者ID</label>
                <input type="text" id="admin_id" name="ID" class="form-input"
                       placeholder="管理者IDを入力" value="<?= $id ?>" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label" for="admin_pass">パスワード</label>
                <input type="password" id="admin_pass" name="PASS" class="form-input"
                       placeholder="パスワードを入力" required>
            </div>

            <button type="submit" class="login-button">ログイン</button>
        </form>
        
        <div class="debug">
            <p>セッションID: <?= session_id() ?></p>
            <p>セッション名: <?= session_name() ?></p>
            <p>現在時刻: <?= date('Y-m-d H:i:s') ?></p>
        </div>
    </div>
</body>
</html>
<?php
}

main();
?>