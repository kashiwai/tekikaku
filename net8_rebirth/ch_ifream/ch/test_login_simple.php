<?php
/**
 * シンプルなログインテスト
 * セッション管理の問題を診断
 */

// セッションを最初に開始（ヘッダ出力前）
session_name('NET8ADMIN');
session_start();

// データベース接続情報
$DB_HOST = '136.116.70.86';
$DB_USER = 'net8tech001';
$DB_PASS = 'Nene11091108!!';
$DB_NAME = 'net8_dev';

$message = '';
$action = $_GET['action'] ?? '';

// ログアウト処理
if ($action === 'logout') {
    $_SESSION = array();
    session_destroy();
    $message = 'ログアウトしました';
}

// ログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $admin_id = $_POST['admin_id'] ?? '';
    $admin_pass = $_POST['admin_pass'] ?? '';
    
    // DB接続
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    
    if ($mysqli->connect_error) {
        $message = 'データベース接続エラー: ' . $mysqli->connect_error;
    } else {
        // 管理者認証
        $sql = "SELECT admin_no, admin_id, admin_name, auth_flg 
                FROM mst_admin 
                WHERE admin_id = ? AND admin_pass = ? AND del_flg = 0";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ss", $admin_id, $admin_pass);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // ログイン成功
            $_SESSION['AdminInfo'] = $row;
            $_SESSION['login_time'] = time();
            $_SESSION['last_access'] = time();
            $message = 'ログイン成功！';
        } else {
            $message = 'ログイン失敗: IDまたはパスワードが正しくありません';
        }
        
        $stmt->close();
        $mysqli->close();
    }
}

// セッションチェック
$isLoggedIn = isset($_SESSION['AdminInfo']);
$sessionTimeout = false;

if ($isLoggedIn) {
    // タイムアウトチェック（3600秒 = 1時間）
    if (isset($_SESSION['last_access'])) {
        $elapsed = time() - $_SESSION['last_access'];
        if ($elapsed > 3600) {
            $sessionTimeout = true;
            $_SESSION = array();
            session_destroy();
            $message = 'セッションタイムアウトしました';
            $isLoggedIn = false;
        } else {
            $_SESSION['last_access'] = time();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 ログインテスト</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        .status {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            color: #555;
            margin-bottom: 8px;
            font-weight: 600;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e1e1e1;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
        }
        .session-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .session-info h3 {
            color: #333;
            margin-bottom: 10px;
        }
        .session-info p {
            color: #666;
            line-height: 1.6;
            margin: 5px 0;
        }
        code {
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background: #f4f4f4;
            border-radius: 6px;
        }
        .debug-info pre {
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔐 NET8 ログインテスト</h1>
            
            <?php if ($message): ?>
                <div class="status <?= strpos($message, '成功') !== false ? 'success' : (strpos($message, '失敗') !== false ? 'error' : 'warning') ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($isLoggedIn): ?>
                <!-- ログイン済み -->
                <div class="status success">
                    ✅ ログイン中
                </div>
                
                <div class="session-info">
                    <h3>セッション情報</h3>
                    <p><strong>管理者ID:</strong> <code><?= htmlspecialchars($_SESSION['AdminInfo']['admin_id']) ?></code></p>
                    <p><strong>管理者名:</strong> <?= htmlspecialchars($_SESSION['AdminInfo']['admin_name']) ?></p>
                    <p><strong>ログイン時刻:</strong> <?= date('Y-m-d H:i:s', $_SESSION['login_time']) ?></p>
                    <p><strong>最終アクセス:</strong> <?= date('Y-m-d H:i:s', $_SESSION['last_access']) ?></p>
                    <p><strong>残り時間:</strong> <?= (3600 - (time() - $_SESSION['last_access'])) ?> 秒</p>
                    <p><strong>セッションID:</strong> <code><?= session_id() ?></code></p>
                </div>
                
                <div style="margin-top: 20px;">
                    <a href="?action=logout">
                        <button type="button">ログアウト</button>
                    </a>
                </div>
                
                <div style="margin-top: 10px; text-align: center;">
                    <a href="/xxxadmin/index.php">管理画面へ移動 →</a>
                </div>
                
            <?php else: ?>
                <!-- ログインフォーム -->
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="admin_id">管理者ID</label>
                        <input type="text" id="admin_id" name="admin_id" required autofocus placeholder="例: testadmin">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_pass">パスワード</label>
                        <input type="password" id="admin_pass" name="admin_pass" required placeholder="例: testpass123">
                    </div>
                    
                    <button type="submit" name="login" value="1">ログイン</button>
                </form>
                
                <div class="status info" style="margin-top: 20px;">
                    <strong>テストアカウント:</strong><br>
                    ID: testadmin<br>
                    パスワード: testpass123
                </div>
            <?php endif; ?>
        </div>
        
        <!-- デバッグ情報 -->
        <div class="card">
            <h2>🔍 デバッグ情報</h2>
            <div class="debug-info">
                <h3>PHPセッション設定</h3>
                <p>セッション名: <code><?= session_name() ?></code></p>
                <p>セッションID: <code><?= session_id() ?: '(未開始)' ?></code></p>
                <p>セッション保存パス: <code><?= ini_get('session.save_path') ?: '(デフォルト)' ?></code></p>
                <p>セッションGC最大生存時間: <code><?= ini_get('session.gc_maxlifetime') ?></code> 秒</p>
                
                <h3 style="margin-top: 15px;">セッション変数</h3>
                <pre><?= htmlspecialchars(print_r($_SESSION, true)) ?></pre>
                
                <h3 style="margin-top: 15px;">クッキー情報</h3>
                <pre><?= htmlspecialchars(print_r($_COOKIE, true)) ?></pre>
            </div>
        </div>
    </div>
</body>
</html>