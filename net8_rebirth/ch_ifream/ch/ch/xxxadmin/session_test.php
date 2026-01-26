<?php
/**
 * セッション問題の詳細調査
 */

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>セッション詳細調査</title>";
echo "<style>body{font-family:monospace;margin:20px;} .debug{background:#f0f0f0;padding:10px;margin:10px 0;border:1px solid #ccc;} .error{background:#fee;} .success{background:#efe;}</style>";
echo "</head><body>";

echo "<h1>🔍 セッション問題詳細調査</h1>";
echo "<p>実行時刻: " . date('Y-m-d H:i:s') . "</p>";

// 1. セッション開始前の状態
echo "<div class='debug'>";
echo "<h3>📋 セッション開始前の状態</h3>";
echo "<strong>Session Status:</strong> " . session_status() . " (1=disabled, 2=active, 3=none)<br>";
echo "<strong>Session ID:</strong> " . (session_id() ?: 'NONE') . "<br>";
echo "<strong>Session Name:</strong> " . (session_name() ?: 'NONE') . "<br>";
echo "</div>";

// 2. セッション開始（login.phpと同じ方式）
echo "<div class='debug'>";
echo "<h3>🔧 セッション開始テスト</h3>";
session_name("NET8ADMIN");
session_start();
echo "<strong>セッション開始後:</strong><br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Name:</strong> " . session_name() . "<br>";
echo "</div>";

// 3. テストデータ設定
echo "<div class='debug'>";
echo "<h3>🧪 テストセッションデータ設定</h3>";
$_SESSION['test_time'] = time();
$_SESSION['test_value'] = 'テスト値: ' . rand(1000, 9999);

// 管理者情報のテスト設定
$_SESSION['AdminInfo'] = [
    'admin_id' => 'test_admin',
    'admin_name' => 'テスト管理者',
    'admin_no' => 1
];

echo "<strong>設定完了:</strong><br>";
echo "test_time = " . $_SESSION['test_time'] . "<br>";
echo "test_value = " . $_SESSION['test_value'] . "<br>";
echo "AdminInfo設定済み<br>";
echo "</div>";

// 4. セッション内容確認
echo "<div class='debug'>";
echo "<h3>📊 現在のセッション内容</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// 5. セッションファイル確認
echo "<div class='debug'>";
echo "<h3>💾 セッションファイル確認</h3>";
$sessionPath = session_save_path() ?: '/tmp';
$sessionFile = $sessionPath . '/sess_' . session_id();
echo "<strong>Session Save Path:</strong> " . $sessionPath . "<br>";
echo "<strong>Session File:</strong> " . $sessionFile . "<br>";
echo "<strong>File Exists:</strong> " . (file_exists($sessionFile) ? '✅YES' : '❌NO') . "<br>";

if (file_exists($sessionFile)) {
    $content = file_get_contents($sessionFile);
    echo "<strong>File Size:</strong> " . strlen($content) . " bytes<br>";
    echo "<strong>File Content:</strong><br>";
    echo "<pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "<strong style='color:red'>⚠️ セッションファイルが存在しません！</strong><br>";
}
echo "</div>";

// 6. Cookie確認
echo "<div class='debug'>";
echo "<h3>🍪 Cookie確認</h3>";
echo "<strong>HTTP Cookies:</strong><br>";
foreach ($_COOKIE as $name => $value) {
    echo $name . " = " . $value . "<br>";
}

if (isset($_COOKIE['NET8ADMIN'])) {
    echo "<div class='success'>✅ NET8ADMINクッキーが存在します</div>";
} else {
    echo "<div class='error'>❌ NET8ADMINクッキーが存在しません</div>";
}
echo "</div>";

// 7. TemplateAdminクラステスト
echo "<div class='debug'>";
echo "<h3>🔧 TemplateAdminクラステスト</h3>";
try {
    // require_files_admin.phpを読み込んでTemplateAdminをテスト
    $requireFile = '../../_etc/require_files_admin.php';
    if (file_exists($requireFile)) {
        echo "require_files_admin.php読み込み中...<br>";
        require_once($requireFile);
        echo "✅ require_files_admin.php読み込み成功<br>";
        
        echo "TemplateAdminクラス作成中...<br>";
        $template = new TemplateAdmin(false); // セッションチェック無効
        echo "✅ TemplateAdminクラス作成成功<br>";
    } else {
        echo "❌ require_files_admin.phpが見つかりません<br>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ TemplateAdminエラー: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Error $e) {
    echo "<div class='error'>❌ 致命的エラー: " . htmlspecialchars($e->getMessage()) . "</div>";
}
echo "</div>";

// 8. PHP設定確認
echo "<div class='debug'>";
echo "<h3>⚙️ PHP Session設定</h3>";
$settings = [
    'session.save_handler',
    'session.save_path',
    'session.cookie_lifetime',
    'session.cookie_path',
    'session.cookie_domain',
    'session.cookie_secure',
    'session.cookie_httponly',
    'session.gc_maxlifetime',
    'session.gc_probability'
];

foreach ($settings as $setting) {
    echo "<strong>{$setting}:</strong> " . ini_get($setting) . "<br>";
}
echo "</div>";

echo "<div class='debug'>";
echo "<h3>🔗 リンクテスト</h3>";
echo "<a href='?reload=1' style='padding:5px 10px;background:#007bff;color:white;text-decoration:none;margin:5px;'>リロード</a>";
echo "<a href='login.php' style='padding:5px 10px;background:#28a745;color:white;text-decoration:none;margin:5px;'>ログインページ</a>";
echo "<a href='index.php' style='padding:5px 10px;background:#dc3545;color:white;text-decoration:none;margin:5px;'>ダッシュボード</a>";
echo "</div>";

echo "</body></html>";
?>