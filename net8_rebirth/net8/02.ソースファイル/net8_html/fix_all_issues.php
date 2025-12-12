<?php
/**
 * 全問題修正スクリプト
 * 1. CSSのMIME typeエラー
 * 2. ログイン認証問題
 * 3. セッション管理問題
 */

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>全問題修正</title></head><body>";
echo "<h1>🔧 NET8 全問題修正スクリプト</h1>";

// 1. .htaccessファイルでCSS配信を修正
$htaccessPath = __DIR__ . '/data/xxxadmin/.htaccess';
$htaccessContent = '# NET8 Admin Panel - Static Files Configuration

# CSS and JS files MIME type fix
<FilesMatch "\.(css)$">
    Header set Content-Type "text/css; charset=utf-8"
</FilesMatch>

<FilesMatch "\.(js)$">
    Header set Content-Type "application/javascript; charset=utf-8"
</FilesMatch>

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>

# Enable compression for CSS and JS
<IfModule mod_deflate.c>
    <FilesMatch "\.(css|js)$">
        SetOutputFilter DEFLATE
    </FilesMatch>
</IfModule>
';

file_put_contents($htaccessPath, $htaccessContent);
echo "<p>✅ .htaccess作成: CSS MIME type修正</p>";

// 2. 完全に修正されたログイン.phpを作成
$loginPath = __DIR__ . '/data/xxxadmin/login.php';
$loginContent = '<?php
/**
 * NET8 管理画面 - ログイン（完全修正版）
 */

// セッション開始（最優先）
if (session_status() == PHP_SESSION_NONE) {
    session_name("NET8ADMIN");
    session_start();
}

// DB接続情報
define("DB_HOST", "136.116.70.86");
define("DB_USER", "net8tech001");
define("DB_PASS", "Nene11091108!!");
define("DB_NAME", "net8_dev");
define("URL_ADMIN", "/xxxadmin/");

// メイン処理
main();

function main() {
    // POST処理
    $action = $_POST["M"] ?? "";
    
    if ($action === "proc") {
        // ログイン処理
        ProcLogin();
    } else {
        // ログイン画面表示
        DispLogin();
    }
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
            width: 100%; max-width: 420px; padding: 48px 40px; animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo { text-align: center; margin-bottom: 32px; }
        .logo-icon { font-size: 64px; margin-bottom: 16px; }
        .logo-text {
            font-size: 28px; font-weight: 700;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;
        }
        .login-title { text-align: center; color: #0f172a; font-size: 20px; font-weight: 600; margin-bottom: 32px; }
        .error-message {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 8px;
            margin-bottom: 24px; font-size: 14px; display: flex; align-items: center; gap: 8px;
        }
        .form-group { margin-bottom: 24px; }
        .form-label {
            display: block; color: #475569; font-size: 14px; font-weight: 600; margin-bottom: 8px;
        }
        .input-wrapper { position: relative; }
        .input-icon {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            font-size: 18px; color: #94a3b8;
        }
        .form-input {
            width: 100%; padding: 12px 16px 12px 48px; border: 2px solid #e2e8f0;
            border-radius: 8px; font-size: 15px; transition: all 0.2s; background: #f8fafc;
        }
        .form-input:focus {
            outline: none; border-color: #667eea; background: white;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
        }
        .login-button {
            width: 100%; padding: 14px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        .login-button:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(102, 126, 234, 0.4); }
        .footer-text { text-align: center; margin-top: 24px; color: #64748b; font-size: 13px; }
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
        <div class="error-message">
            <span>⚠️</span>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <input type="hidden" name="M" value="proc">
            
            <div class="form-group">
                <label class="form-label" for="admin_id">管理者ID</label>
                <div class="input-wrapper">
                    <span class="input-icon">👤</span>
                    <input type="text" id="admin_id" name="ID" class="form-input"
                           placeholder="管理者IDを入力" value="<?= $id ?>" required autofocus>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="admin_pass">パスワード</label>
                <div class="input-wrapper">
                    <span class="input-icon">🔒</span>
                    <input type="password" id="admin_pass" name="PASS" class="form-input"
                           placeholder="パスワードを入力" required>
                </div>
            </div>

            <button type="submit" class="login-button">ログイン</button>
        </form>

        <p class="footer-text">© 2025 NET8 Management System</p>
    </div>
</body>
</html>
<?php
}

function ProcLogin() {
    $admin_id = $_POST["ID"] ?? "";
    $admin_pass = $_POST["PASS"] ?? "";
    
    // 入力チェック
    if (empty($admin_id) || empty($admin_pass)) {
        DispLogin("IDとパスワードを入力してください");
        return;
    }
    
    // DB接続
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        DispLogin("システムエラーが発生しました");
        return;
    }
    
    // 管理者認証
    $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg 
            FROM mst_admin 
            WHERE admin_id = ? AND del_flg = 0 LIMIT 1";
    
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("s", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // パスワード検証
        if (password_verify($admin_pass, $row["admin_pass"])) {
            // ログイン成功
            $_SESSION["AdminInfo"] = $row;
            $_SESSION["login_time"] = time();
            $_SESSION["last_access"] = time();
            
            // 最終ログイン更新
            $updateSql = "UPDATE mst_admin SET login_dt = NOW() WHERE admin_no = ?";
            $updateStmt = $mysqli->prepare($updateSql);
            $updateStmt->bind_param("i", $row["admin_no"]);
            $updateStmt->execute();
            $updateStmt->close();
            
            $stmt->close();
            $mysqli->close();
            
            // ダッシュボードへ
            header("Location: " . URL_ADMIN . "index.php");
            exit();
        }
    }
    
    $stmt->close();
    $mysqli->close();
    
    DispLogin("ログインIDまたはパスワードが正しくありません");
}
?>';

file_put_contents($loginPath, $loginContent);
echo "<p>✅ login.php完全修正版を作成</p>";

// 3. 管理画面の基本構造を確認・修正
echo "<h2>🔍 管理画面ページの確認</h2>";

$checkPages = [
    'index.php' => 'ダッシュボード',
    'member.php' => '会員管理', 
    'machines.php' => 'マシン管理',
    'search.php' => '検索'
];

$adminDir = __DIR__ . '/data/xxxadmin/';
foreach ($checkPages as $page => $name) {
    $pagePath = $adminDir . $page;
    if (file_exists($pagePath)) {
        echo "<p>✅ $name ($page) 存在</p>";
        
        // 各ページの冒頭にセッション確認を追加
        $content = file_get_contents($pagePath);
        
        // セッション確認コードを追加（まだ追加されていない場合）
        if (strpos($content, "session_start()") === false && $page !== "login.php") {
            $sessionCheck = "<?php
// セッション確認
if (session_status() == PHP_SESSION_NONE) {
    session_name('NET8ADMIN');
    session_start();
}

// ログイン確認
if (!isset(\$_SESSION['AdminInfo'])) {
    header('Location: /xxxadmin/login.php');
    exit();
}

// セッション更新
\$_SESSION['last_access'] = time();
?>";
            
            // <?php の直後に挿入
            $content = preg_replace('/^(<\?php)/', "$1\n$sessionCheck", $content);
            //file_put_contents($pagePath, $content);
            echo "<span style='color:orange;'> (セッション確認追加予定)</span>";
        }
    } else {
        echo "<p>❌ $name ($page) 未存在</p>";
    }
}

// 4. テスト用の簡易index.phpを作成
$indexPath = $adminDir . 'index_simple.php';
$indexContent = '<?php
// セッション確認
if (session_status() == PHP_SESSION_NONE) {
    session_name("NET8ADMIN");
    session_start();
}

// ログイン確認
if (!isset($_SESSION["AdminInfo"])) {
    header("Location: /xxxadmin/login.php");
    exit();
}

$adminInfo = $_SESSION["AdminInfo"];
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>NET8 管理画面 - ダッシュボード</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
        .header { background: #667eea; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .welcome { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .menu { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .menu-item {
            background: white; padding: 20px; border-radius: 8px; text-decoration: none;
            color: #333; border: 1px solid #ddd; transition: all 0.3s;
        }
        .menu-item:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .logout { color: #dc3545; text-decoration: none; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎮 NET8 管理画面</h1>
        <p>ログイン中: <?= htmlspecialchars($adminInfo["admin_name"]) ?> (<?= htmlspecialchars($adminInfo["admin_id"]) ?>)</p>
        <p><a href="logout.php" class="logout">ログアウト</a></p>
    </div>

    <div class="welcome">
        <h2>✅ ログイン成功！</h2>
        <p>セッションが正常に維持されています。</p>
        <p>最終アクセス: <?= date("Y-m-d H:i:s") ?></p>
    </div>

    <div class="menu">
        <a href="member.php" class="menu-item">
            <h3>👥 会員管理</h3>
            <p>会員の登録・編集・削除</p>
        </a>
        
        <a href="machines.php" class="menu-item">
            <h3>🎰 マシン管理</h3>
            <p>パチンコ台の管理</p>
        </a>
        
        <a href="search.php" class="menu-item">
            <h3>🔍 検索</h3>
            <p>データの検索・絞り込み</p>
        </a>
        
        <a href="api_keys_manage.php" class="menu-item">
            <h3>🔑 APIキー管理</h3>
            <p>API認証キーの管理</p>
        </a>
        
        <a href="pointgrant.php" class="menu-item">
            <h3>🎁 ポイント付与</h3>
            <p>ユーザーポイントの付与</p>
        </a>
        
        <a href="sales.php" class="menu-item">
            <h3>💰 売上管理</h3>
            <p>売上データの確認</p>
        </a>
    </div>
</body>
</html>';

file_put_contents($indexPath, $indexContent);
echo "<p>✅ テスト用ダッシュボード (index_simple.php) 作成</p>";

// 5. ログアウトページの修正
$logoutPath = $adminDir . 'logout.php';
$logoutContent = '<?php
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
?>';

file_put_contents($logoutPath, $logoutContent);
echo "<p>✅ logout.php修正</p>";

echo "<h2>🚀 テスト手順</h2>";
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px;'>";
echo "<h3>修正完了！以下の順序でテストしてください：</h3>";
echo "<ol>";
echo "<li><strong>ログイン:</strong> <a href='https://mgg-webservice-production.up.railway.app/xxxadmin/login.php' target='_blank'>ログイン画面</a>";
echo "<br>ID: <strong>admin</strong> / パスワード: <strong>admin123</strong></li>";
echo "<li><strong>ダッシュボード:</strong> <a href='https://mgg-webservice-production.up.railway.app/xxxadmin/index_simple.php' target='_blank'>簡易ダッシュボード</a></li>";
echo "<li><strong>セッション確認:</strong> 各ページに移動してセッションが維持されるか確認</li>";
echo "<li><strong>元のダッシュボード:</strong> <a href='https://mgg-webservice-production.up.railway.app/xxxadmin/index.php' target='_blank'>通常のダッシュボード</a></li>";
echo "</ol>";
echo "<p><strong>注意:</strong> CSSエラーが解消され、ログイン認証も安定しているはずです。</p>";
echo "</div>";

echo "</body></html>";
?>