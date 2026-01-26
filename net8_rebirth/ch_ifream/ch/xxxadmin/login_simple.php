<?php
/**
 * 最もシンプルなログインシステム
 */

// エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション設定
ini_set("session.save_path", "/tmp");
session_name("NET8ADMIN");
session_start();

// DB接続情報
$DB_HOST = "136.116.70.86";
$DB_USER = "net8tech001";
$DB_PASS = "Nene11091108!!";
$DB_NAME = "net8_dev";

$message = "";

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $admin_id = trim($_POST['admin_id'] ?? '');
    $admin_pass = trim($_POST['admin_pass'] ?? '');
    
    if (!empty($admin_id) && !empty($admin_pass)) {
        try {
            $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
            
            if (!$mysqli->connect_error) {
                $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg 
                        FROM mst_admin 
                        WHERE admin_id = ? AND del_flg = 0 LIMIT 1";
                
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("s", $admin_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($admin_pass, $row['admin_pass'])) {
                        // ログイン成功
                        $_SESSION['NET8_ADMIN'] = $row;
                        $_SESSION['LOGIN_TIME'] = time();
                        
                        $message = "ログイン成功！ダッシュボードへ移動します...";
                        
                        // JavaScriptでリダイレクト
                        echo "<script>setTimeout(function(){ window.location.href = 'dashboard_simple.php'; }, 2000);</script>";
                    } else {
                        $message = "パスワードが正しくありません";
                    }
                } else {
                    $message = "管理者IDが見つかりません";
                }
                
                $stmt->close();
            } else {
                $message = "データベース接続エラー";
            }
            
            $mysqli->close();
            
        } catch (Exception $e) {
            $message = "エラー: " . $e->getMessage();
        }
    } else {
        $message = "IDとパスワードを入力してください";
    }
}

// ログアウト処理
if (isset($_GET['logout'])) {
    session_destroy();
    $message = "ログアウトしました";
}

// 現在の状態確認
$isLoggedIn = isset($_SESSION['NET8_ADMIN']);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 シンプルログイン</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh; display: flex; align-items: center; justify-content: center;
        }
        .container { 
            background: white; padding: 40px; border-radius: 10px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 400px; width: 100%;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo-icon { font-size: 48px; }
        .title { text-align: center; color: #333; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #555; margin-bottom: 5px; font-weight: bold; }
        .form-group input { 
            width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; 
            font-size: 16px; transition: border-color 0.3s;
        }
        .form-group input:focus { border-color: #667eea; outline: none; }
        .btn { 
            width: 100%; padding: 12px; background: #667eea; color: white; 
            border: none; border-radius: 5px; font-size: 16px; cursor: pointer;
            font-weight: bold; transition: background 0.3s;
        }
        .btn:hover { background: #5a67d8; }
        .message { 
            padding: 10px; margin: 15px 0; border-radius: 5px; text-align: center;
        }
        .message.success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .status { 
            background: #e3f2fd; padding: 15px; border-radius: 5px; 
            margin-top: 20px; font-size: 14px;
        }
        .login-info { 
            background: #fff3cd; padding: 15px; border-radius: 5px; 
            margin-bottom: 20px; border: 1px solid #ffeaa7;
        }
        .dashboard-link {
            display: block; text-align: center; margin-top: 15px;
            color: #667eea; text-decoration: none; font-weight: bold;
        }
        .dashboard-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <div class="logo-icon">🎮</div>
            <h1 class="title">NET8 Admin</h1>
        </div>

        <?php if ($message): ?>
        <div class="message <?= strpos($message, '成功') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
        <?php endif; ?>

        <?php if ($isLoggedIn): ?>
            <!-- ログイン済み -->
            <div class="message success">
                ✅ ログイン中: <?= htmlspecialchars($_SESSION['NET8_ADMIN']['admin_name']) ?>
            </div>
            <a href="dashboard_simple.php" class="dashboard-link">📊 ダッシュボードへ</a>
            <a href="?logout=1" class="dashboard-link" style="color: #dc3545;">🚪 ログアウト</a>
            
            <div class="status">
                <strong>セッション情報:</strong><br>
                セッションID: <?= session_id() ?><br>
                ログイン時刻: <?= date('Y-m-d H:i:s', $_SESSION['LOGIN_TIME']) ?><br>
                管理者ID: <?= htmlspecialchars($_SESSION['NET8_ADMIN']['admin_id']) ?>
            </div>
        <?php else: ?>
            <!-- ログインフォーム -->
            <div class="login-info">
                <strong>テストアカウント:</strong><br>
                ID: admin<br>
                パスワード: admin123
            </div>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="admin_id">管理者ID:</label>
                    <input type="text" id="admin_id" name="admin_id" value="admin" required>
                </div>
                
                <div class="form-group">
                    <label for="admin_pass">パスワード:</label>
                    <input type="password" id="admin_pass" name="admin_pass" value="admin123" required>
                </div>
                
                <button type="submit" name="login" class="btn">ログイン</button>
            </form>
            
            <div class="status">
                <strong>システム状態:</strong><br>
                セッションID: <?= session_id() ?><br>
                セッション保存パス: <?= ini_get('session.save_path') ?><br>
                現在時刻: <?= date('Y-m-d H:i:s') ?><br>
                PHPバージョン: <?= PHP_VERSION ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>