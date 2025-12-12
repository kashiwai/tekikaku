<?php
/**
 * ログイン問題の詳細デバッグ
 */

// 全エラー表示
error_reporting(E_ALL);
ini_set('display_errors', 1);

// セッション設定の詳細確認
echo "<h1>🔍 ログイン問題デバッグ</h1>";
echo "<style>body{font-family:sans-serif;padding:20px;} .debug{background:#f0f0f0;padding:15px;margin:10px 0;border-radius:5px;} .error{background:#ffebee;color:#c62828;} .success{background:#e8f5e8;color:#2e7d32;}</style>";

echo "<div class='debug'>";
echo "<h2>1. PHP環境確認</h2>";
echo "PHPバージョン: " . PHP_VERSION . "<br>";
echo "セッション拡張: " . (extension_loaded('session') ? "✅ 有効" : "❌ 無効") . "<br>";
echo "MySQLi拡張: " . (extension_loaded('mysqli') ? "✅ 有効" : "❌ 無効") . "<br>";
echo "</div>";

echo "<div class='debug'>";
echo "<h2>2. セッション設定</h2>";
echo "session.save_handler: " . ini_get('session.save_handler') . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "<br>";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "<br>";
echo "</div>";

// セッション開始前の状態
echo "<div class='debug'>";
echo "<h2>3. セッション開始前</h2>";
echo "セッション状態: " . session_status() . " (1=無効, 2=開始済み)<br>";
echo "セッションID: " . (session_id() ?: "未開始") . "<br>";
echo "</div>";

// セッション強制設定
ini_set("session.save_path", "/tmp");
ini_set("session.gc_maxlifetime", 3600);
ini_set("session.cookie_lifetime", 0);

// セッション開始
session_name("NET8ADMIN");
session_start();

echo "<div class='debug'>";
echo "<h2>4. セッション開始後</h2>";
echo "セッション状態: " . session_status() . "<br>";
echo "セッションID: " . session_id() . "<br>";
echo "セッション名: " . session_name() . "<br>";
echo "</div>";

// DB接続テスト
echo "<div class='debug'>";
echo "<h2>5. データベース接続テスト</h2>";
try {
    $mysqli = new mysqli("136.116.70.86", "net8tech001", "Nene11091108!!", "net8_dev");
    if ($mysqli->connect_error) {
        echo "<div class='error'>❌ DB接続失敗: " . $mysqli->connect_error . "</div>";
    } else {
        echo "<div class='success'>✅ DB接続成功</div>";
        
        // 管理者テーブル確認
        $sql = "SELECT COUNT(*) as count FROM mst_admin WHERE del_flg = 0";
        $result = $mysqli->query($sql);
        if ($result) {
            $row = $result->fetch_assoc();
            echo "管理者アカウント数: " . $row['count'] . "<br>";
        }
        
        // adminアカウント確認
        $sql = "SELECT admin_no, admin_id, admin_name FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0";
        $result = $mysqli->query($sql);
        if ($result && $result->num_rows > 0) {
            echo "<div class='success'>✅ adminアカウント存在</div>";
            while ($row = $result->fetch_assoc()) {
                echo "admin_no: " . $row['admin_no'] . ", admin_id: " . $row['admin_id'] . ", admin_name: " . $row['admin_name'] . "<br>";
            }
        } else {
            echo "<div class='error'>❌ adminアカウントが見つかりません</div>";
        }
        
        $mysqli->close();
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ DB例外: " . $e->getMessage() . "</div>";
}
echo "</div>";

// ログイン処理テスト
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_login'])) {
    echo "<div class='debug'>";
    echo "<h2>6. ログインテスト実行</h2>";
    
    $admin_id = "admin";
    $admin_pass = "admin123";
    
    echo "テストID: " . $admin_id . "<br>";
    echo "テストパスワード: " . $admin_pass . "<br>";
    
    try {
        $mysqli = new mysqli("136.116.70.86", "net8tech001", "Nene11091108!!", "net8_dev");
        
        $sql = "SELECT admin_no, admin_id, admin_name, admin_pass, auth_flg 
                FROM mst_admin 
                WHERE admin_id = ? AND del_flg = 0 LIMIT 1";
        
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("s", $admin_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            echo "✅ アカウント発見<br>";
            echo "保存されたハッシュ: " . substr($row['admin_pass'], 0, 30) . "...<br>";
            
            if (password_verify($admin_pass, $row['admin_pass'])) {
                echo "<div class='success'>✅ パスワード認証成功</div>";
                
                // セッションに保存
                $_SESSION['TEST_ADMIN'] = $row;
                $_SESSION['TEST_LOGIN_TIME'] = time();
                
                echo "セッションに保存完了<br>";
                $message = "ログインテスト成功！";
            } else {
                echo "<div class='error'>❌ パスワード認証失敗</div>";
            }
        } else {
            echo "<div class='error'>❌ アカウントが見つかりません</div>";
        }
        
        $stmt->close();
        $mysqli->close();
        
    } catch (Exception $e) {
        echo "<div class='error'>❌ ログインテスト例外: " . $e->getMessage() . "</div>";
    }
    echo "</div>";
}

// 現在のセッション内容
echo "<div class='debug'>";
echo "<h2>7. 現在のセッション内容</h2>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

// クッキー情報
echo "<div class='debug'>";
echo "<h2>8. クッキー情報</h2>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";
echo "</div>";

?>

<div class="debug">
    <h2>9. ログインテスト</h2>
    <?php if ($message): ?>
        <div class="<?= strpos($message, '成功') !== false ? 'success' : 'error' ?>"><?= $message ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <button type="submit" name="test_login" style="padding:10px 20px; background:#667eea; color:white; border:none; border-radius:5px; cursor:pointer;">
            admin/admin123でログインテスト
        </button>
    </form>
    
    <?php if (isset($_SESSION['TEST_ADMIN'])): ?>
        <div class="success">
            ✅ テストログイン済み: <?= htmlspecialchars($_SESSION['TEST_ADMIN']['admin_name']) ?><br>
            ログイン時刻: <?= date('Y-m-d H:i:s', $_SESSION['TEST_LOGIN_TIME']) ?>
        </div>
    <?php endif; ?>
</div>

<div class="debug">
    <h2>10. 推定される問題</h2>
    <p>以下の項目を確認して問題を特定します：</p>
    <ul>
        <li>セッション保存パスの書き込み権限</li>
        <li>セッションIDの生成・維持</li>
        <li>クッキーの設定・送信</li>
        <li>リダイレクト時のセッション引き継ぎ</li>
        <li>ブラウザのクッキー設定</li>
    </ul>
</div>

<div class="debug">
    <h2>11. 次のステップ</h2>
    <p>このページを実行して、どの段階で問題が発生しているか確認してください。</p>
    <p><strong>URL:</strong> <a href="/xxxadmin/debug_login.php">/xxxadmin/debug_login.php</a></p>
</div>