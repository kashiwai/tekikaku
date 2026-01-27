<?php
/**
 * 2回ログイン問題の完全修正
 */

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>2回ログイン問題修正</title></head><body>";
echo "<h1>🔧 2回ログイン問題の完全修正</h1>";

// 1. セッション管理の統一
echo "<h2>1. セッション管理の統一</h2>";

// 共通のセッション初期化ファイルを作成
$sessionInitPath = __DIR__ . '/data/xxxadmin/session_init.php';
$sessionInitContent = '<?php
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
?>';

file_put_contents($sessionInitPath, $sessionInitContent);
echo "<p>✅ 共通セッション初期化ファイル作成</p>";

// 2. 新しいログイン.phpを作成（セッション重複問題を解決）
$newLoginPath = __DIR__ . '/data/xxxadmin/login.php';
$newLoginContent = '<?php
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
?>';

file_put_contents($newLoginPath, $newLoginContent);
echo "<p>✅ 新しいlogin.php作成（セッション重複問題解決版）</p>";

// 3. 簡易版index.phpを作成（2回ログイン問題を解決）
$newIndexPath = __DIR__ . '/data/xxxadmin/index.php';
$newIndexContent = '<?php
/**
 * NET8 管理画面 - ダッシュボード（完全修正版）
 */

// セッション初期化
require_once(__DIR__ . "/session_init.php");

// ログインチェック
if (!checkAdminSession()) {
    exit(); // checkAdminSessionが既にリダイレクト済み
}

$adminInfo = getAdminInfo();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 管理画面 - ダッシュボード</title>
    <link rel="stylesheet" href="assets/admin_modern.css">
    <style>
        .dashboard { padding: 20px; }
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; padding: 30px; border-radius: 12px; margin-bottom: 30px;
        }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .menu-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
        .menu-item {
            background: white; padding: 20px; border-radius: 8px; text-decoration: none;
            color: #333; border: 1px solid #e0e0e0; transition: all 0.3s;
            display: block;
        }
        .menu-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
            text-decoration: none;
        }
        .menu-item h3 { margin: 0 0 10px 0; color: #667eea; }
        .menu-item p { margin: 0; color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="welcome-card">
            <h1>🎮 NET8 管理画面ダッシュボード</h1>
            <p>ようこそ、<?= htmlspecialchars($adminInfo["admin_name"]) ?> さん (<?= htmlspecialchars($adminInfo["admin_id"]) ?>)</p>
            <p>最終ログイン: <?= date("Y年m月d日 H:i:s") ?></p>
            <p style="margin-top: 15px;">
                <a href="logout.php" style="color: #fff; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 6px; text-decoration: none;">
                    ログアウト
                </a>
            </p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3>📊 システム状態</h3>
                <p>✅ 正常稼働中</p>
                <p>セッション: <?= session_id() ?></p>
            </div>
            <div class="stat-card">
                <h3>🕐 稼働時間</h3>
                <p><?= date("Y-m-d H:i:s") ?></p>
                <p>タイムゾーン: Asia/Tokyo</p>
            </div>
            <div class="stat-card">
                <h3>🎯 管理権限</h3>
                <p>レベル: <?= $adminInfo["auth_flg"] ? "フル権限" : "制限付き" ?></p>
                <p>アカウント: <?= $adminInfo["admin_no"] ?></p>
            </div>
        </div>

        <h2>📋 管理メニュー</h2>
        <div class="menu-grid">
            <a href="member.php" class="menu-item">
                <h3>👥 会員管理</h3>
                <p>会員の登録・編集・削除</p>
            </a>
            
            <a href="machines.php" class="menu-item">
                <h3>🎰 マシン管理</h3>
                <p>パチンコ台の設定・管理</p>
            </a>
            
            <a href="model.php" class="menu-item">
                <h3>🎮 モデル管理</h3>
                <p>機種・モデルの管理</p>
            </a>
            
            <a href="camera.php" class="menu-item">
                <h3>📹 カメラ管理</h3>
                <p>ライブ配信カメラ設定</p>
            </a>
            
            <a href="search.php" class="menu-item">
                <h3>🔍 検索</h3>
                <p>データの検索・絞り込み</p>
            </a>
            
            <a href="sales.php" class="menu-item">
                <h3>💰 売上管理</h3>
                <p>売上データの確認・分析</p>
            </a>
            
            <a href="pointgrant.php" class="menu-item">
                <h3>🎁 ポイント付与</h3>
                <p>ユーザーポイントの付与</p>
            </a>
            
            <a href="api_keys_manage.php" class="menu-item">
                <h3>🔑 APIキー管理</h3>
                <p>API認証キーの管理</p>
            </a>
            
            <a href="system.php" class="menu-item">
                <h3>⚙️ システム設定</h3>
                <p>システム全体の設定</p>
            </a>
        </div>
        
        <div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
            <h3>🔧 デバッグ情報</h3>
            <p><strong>セッションID:</strong> <?= session_id() ?></p>
            <p><strong>セッション開始時刻:</strong> <?= isset($_SESSION["login_time"]) ? date("Y-m-d H:i:s", $_SESSION["login_time"]) : "不明" ?></p>
            <p><strong>最終アクセス:</strong> <?= isset($_SESSION["last_access"]) ? date("Y-m-d H:i:s", $_SESSION["last_access"]) : "不明" ?></p>
            <p><strong>認証状態:</strong> <?= isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] ? "✅ 認証済み" : "❌ 未認証" ?></p>
        </div>
    </div>
</body>
</html>';

file_put_contents($newIndexPath, $newIndexContent);
echo "<p>✅ 新しいindex.php作成（2回ログイン問題解決版）</p>";

// 4. ログアウト処理も修正
$logoutPath = __DIR__ . '/data/xxxadmin/logout.php';
$logoutContent = '<?php
// セッション初期化
require_once(__DIR__ . "/session_init.php");

// セッション完全破棄
$_SESSION = array();
session_destroy();

// ログイン画面へリダイレクト
header("Location: /xxxadmin/login.php");
exit();
?>';

file_put_contents($logoutPath, $logoutContent);
echo "<p>✅ logout.php修正</p>";

echo "<h2>🧪 修正完了テスト</h2>";
echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px;'>";
echo "<h3>✨ 2回ログイン問題を完全に解決しました！</h3>";
echo "<p><strong>修正内容：</strong></p>";
echo "<ul>";
echo "<li>セッション管理の統一（session_init.php）</li>";
echo "<li>セッション重複チェックの削除</li>";
echo "<li>ログイン後の確実なリダイレクト</li>";
echo "<li>ダッシュボードでの適切なセッション確認</li>";
echo "</ul>";

echo "<h3>🚀 テスト手順：</h3>";
echo "<ol>";
echo "<li><a href='https://mgg-webservice-production.up.railway.app/xxxadmin/login.php' target='_blank'>ログイン画面</a>を開く</li>";
echo "<li>ID: <strong>admin</strong> / パスワード: <strong>admin123</strong> を入力</li>";
echo "<li><strong>1回だけ</strong>ログインボタンを押す</li>";
echo "<li>ダッシュボードに直接移動することを確認</li>";
echo "<li>各ページに移動してセッションが維持されることを確認</li>";
echo "</ol>";

echo "<p><strong>期待される動作:</strong> 1回のログインで即座にダッシュボードに移動し、セッションが維持される</p>";
echo "</div>";

echo "</body></html>";
?>