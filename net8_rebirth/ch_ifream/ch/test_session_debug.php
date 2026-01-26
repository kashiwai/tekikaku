<?php
/**
 * セッション管理デバッグスクリプト
 * ログインが繰り返される問題の診断
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>セッション管理デバッグ</title>";
echo "<style>
    body { font-family: sans-serif; padding: 20px; background: #f5f5f5; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    .warning { background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin: 10px 0; }
    .error { background: #f8d7da; padding: 15px; border-left: 4px solid #dc3545; margin: 10px 0; }
    .success { background: #d4edda; padding: 15px; border-left: 4px solid #28a745; margin: 10px 0; }
    .info { background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
    pre { background: #f4f4f4; padding: 10px; overflow-x: auto; border-radius: 4px; }
    .code { font-family: monospace; background: #f0f0f0; padding: 2px 4px; border-radius: 3px; }
</style></head><body>";

echo "<h1>🔍 NET8 セッション管理デバッグ</h1>";

// 設定ファイル読み込み
require_once(__DIR__ . '/_etc/require_files_admin.php');

// 1. PHP セッション設定の確認
echo "<div class='section'>";
echo "<h2>1. PHPセッション設定</h2>";
echo "<table>";
echo "<tr><th>設定項目</th><th>値</th><th>説明</th></tr>";

$sessionSettings = [
    'session.save_handler' => 'セッション保存ハンドラ',
    'session.save_path' => 'セッション保存パス',
    'session.name' => 'セッション名',
    'session.gc_maxlifetime' => 'GC最大生存時間（秒）',
    'session.cookie_lifetime' => 'クッキー生存時間',
    'session.cookie_path' => 'クッキーパス',
    'session.cookie_domain' => 'クッキードメイン',
    'session.cookie_secure' => 'セキュアクッキー',
    'session.cookie_httponly' => 'HTTPオンリー',
    'session.use_cookies' => 'クッキー使用',
    'session.use_only_cookies' => 'クッキーのみ使用',
    'session.cache_expire' => 'キャッシュ期限（分）'
];

foreach ($sessionSettings as $key => $desc) {
    $value = ini_get($key);
    echo "<tr>";
    echo "<td><code>$key</code></td>";
    echo "<td>" . ($value !== false ? htmlspecialchars($value) : 'N/A') . "</td>";
    echo "<td>$desc</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 2. NET8 セッション定数の確認
echo "<div class='section'>";
echo "<h2>2. NET8 セッション定数</h2>";
echo "<table>";
echo "<tr><th>定数名</th><th>値</th><th>説明</th></tr>";

$constants = [
    'SESSION_SEC_ADMIN' => '管理画面セッション継続時間',
    'SESSION_SID_ADMIN' => '管理画面セッションID名',
    'URL_ADMIN' => '管理画面URL',
    'DOMAIN' => 'ドメイン名'
];

foreach ($constants as $const => $desc) {
    if (defined($const)) {
        $value = constant($const);
        echo "<tr>";
        echo "<td><code>$const</code></td>";
        echo "<td>" . htmlspecialchars($value) . "</td>";
        echo "<td>$desc</td>";
        echo "</tr>";
    } else {
        echo "<tr>";
        echo "<td><code>$const</code></td>";
        echo "<td class='error'>未定義</td>";
        echo "<td>$desc</td>";
        echo "</tr>";
    }
}
echo "</table>";
echo "</div>";

// 3. 現在のセッション状態
echo "<div class='section'>";
echo "<h2>3. 現在のセッション状態</h2>";

// セッション開始（まだ開始されていない場合）
if (session_status() == PHP_SESSION_NONE) {
    session_name(SESSION_SID_ADMIN);
    session_start();
    echo "<div class='info'>セッションを開始しました</div>";
} else {
    echo "<div class='success'>セッションは既に開始されています</div>";
}

echo "<h3>セッション基本情報</h3>";
echo "<table>";
echo "<tr><th>項目</th><th>値</th></tr>";
echo "<tr><td>セッションID</td><td><code>" . session_id() . "</code></td></tr>";
echo "<tr><td>セッション名</td><td><code>" . session_name() . "</code></td></tr>";
echo "<tr><td>セッションステータス</td><td>";
switch (session_status()) {
    case PHP_SESSION_DISABLED:
        echo "無効";
        break;
    case PHP_SESSION_NONE:
        echo "未開始";
        break;
    case PHP_SESSION_ACTIVE:
        echo "<span style='color: green;'>アクティブ</span>";
        break;
}
echo "</td></tr>";
echo "</table>";

// セッション変数の内容
echo "<h3>セッション変数の内容</h3>";
if (!empty($_SESSION)) {
    echo "<pre>" . htmlspecialchars(print_r($_SESSION, true)) . "</pre>";
} else {
    echo "<div class='warning'>セッション変数は空です</div>";
}

// AdminInfoの確認
if (isset($_SESSION['AdminInfo'])) {
    echo "<div class='success'>AdminInfoが存在します</div>";
    echo "<h4>AdminInfo詳細：</h4>";
    echo "<pre>" . htmlspecialchars(print_r($_SESSION['AdminInfo'], true)) . "</pre>";
} else {
    echo "<div class='warning'>AdminInfoが存在しません（未ログイン状態）</div>";
}
echo "</div>";

// 4. クッキーの確認
echo "<div class='section'>";
echo "<h2>4. クッキー情報</h2>";
if (!empty($_COOKIE)) {
    echo "<table>";
    echo "<tr><th>クッキー名</th><th>値</th></tr>";
    foreach ($_COOKIE as $name => $value) {
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($name) . "</code></td>";
        echo "<td>" . htmlspecialchars(substr($value, 0, 50)) . (strlen($value) > 50 ? '...' : '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='warning'>クッキーが設定されていません</div>";
}
echo "</div>";

// 5. セッションファイルの確認
echo "<div class='section'>";
echo "<h2>5. セッションファイルの状態</h2>";
$savePath = ini_get('session.save_path');
if ($savePath) {
    echo "<p>セッション保存パス: <code>$savePath</code></p>";
    
    // セッションファイルの存在確認
    $sessionFile = $savePath . '/sess_' . session_id();
    if (file_exists($sessionFile)) {
        echo "<div class='success'>セッションファイルが存在します</div>";
        $fileSize = filesize($sessionFile);
        $fileMtime = date('Y-m-d H:i:s', filemtime($sessionFile));
        echo "<table>";
        echo "<tr><th>項目</th><th>値</th></tr>";
        echo "<tr><td>ファイルパス</td><td><code>$sessionFile</code></td></tr>";
        echo "<tr><td>ファイルサイズ</td><td>$fileSize bytes</td></tr>";
        echo "<tr><td>最終更新時刻</td><td>$fileMtime</td></tr>";
        echo "</table>";
        
        // ファイル内容（最初の200文字のみ）
        $content = file_get_contents($sessionFile);
        if ($content) {
            echo "<h4>セッションファイル内容（最初の200文字）：</h4>";
            echo "<pre>" . htmlspecialchars(substr($content, 0, 200)) . "...</pre>";
        }
    } else {
        echo "<div class='warning'>セッションファイルが見つかりません</div>";
    }
} else {
    echo "<div class='warning'>セッション保存パスが設定されていません</div>";
}
echo "</div>";

// 6. 問題診断
echo "<div class='section'>";
echo "<h2>6. 問題診断</h2>";

$problems = [];

// セッションタイムアウトチェック
if (defined('SESSION_SEC_ADMIN')) {
    $timeout = SESSION_SEC_ADMIN;
    if ($timeout < 1800) {
        $problems[] = [
            'level' => 'warning',
            'message' => "セッションタイムアウトが短い（{$timeout}秒 = " . ($timeout/60) . "分）",
            'solution' => 'SESSION_SEC_ADMINを3600以上に設定することを推奨'
        ];
    }
}

// セッションパスの書き込み権限チェック
if ($savePath) {
    if (!is_writable($savePath)) {
        $problems[] = [
            'level' => 'error',
            'message' => "セッション保存パスに書き込み権限がありません",
            'solution' => "chmod 777 $savePath を実行してください"
        ];
    }
}

// クッキー設定チェック
if (ini_get('session.cookie_httponly') != '1') {
    $problems[] = [
        'level' => 'warning',
        'message' => "HTTPOnlyクッキーが無効です",
        'solution' => 'session.cookie_httponly = 1 を設定してください'
    ];
}

// セッションリジェネレーションの問題
$problems[] = [
    'level' => 'info',
    'message' => "ログインが繰り返される場合の一般的な原因",
    'solution' => "1. セッションタイムアウトが短すぎる\n2. session_regenerate_id()の頻繁な呼び出し\n3. ドメイン設定の不一致\n4. セッションファイルの権限問題"
];

if (empty($problems)) {
    echo "<div class='success'>問題は検出されませんでした</div>";
} else {
    foreach ($problems as $problem) {
        $class = $problem['level'];
        echo "<div class='$class'>";
        echo "<strong>問題:</strong> " . $problem['message'] . "<br>";
        echo "<strong>解決策:</strong> " . nl2br($problem['solution']);
        echo "</div>";
    }
}
echo "</div>";

// 7. テスト機能
echo "<div class='section'>";
echo "<h2>7. セッションテスト</h2>";

// セッションテストフォーム
echo "<form method='POST' action=''>";
echo "<h3>セッション変数のテスト</h3>";
echo "<input type='text' name='test_key' placeholder='キー' value='test_data'> ";
echo "<input type='text' name='test_value' placeholder='値' value='" . date('Y-m-d H:i:s') . "'> ";
echo "<button type='submit' name='action' value='set_session'>セッション変数を設定</button> ";
echo "<button type='submit' name='action' value='clear_session'>セッションクリア</button>";
echo "</form>";

// テストアクション処理
if (isset($_POST['action'])) {
    if ($_POST['action'] == 'set_session' && isset($_POST['test_key']) && isset($_POST['test_value'])) {
        $_SESSION[$_POST['test_key']] = $_POST['test_value'];
        echo "<div class='success'>セッション変数を設定しました: {$_POST['test_key']} = {$_POST['test_value']}</div>";
    } elseif ($_POST['action'] == 'clear_session') {
        session_destroy();
        echo "<div class='success'>セッションをクリアしました</div>";
    }
}

echo "</div>";

// 8. 推奨される修正
echo "<div class='section'>";
echo "<h2>8. 推奨される修正</h2>";
echo "<div class='info'>";
echo "<h3>ログインが繰り返される問題の修正案：</h3>";
echo "<ol>";
echo "<li><strong>セッションタイムアウトの延長</strong><br>";
echo "現在: " . (defined('SESSION_SEC_ADMIN') ? SESSION_SEC_ADMIN . "秒" : "未定義") . "<br>";
echo "推奨: 3600秒（1時間）以上</li>";
echo "<li><strong>session_regenerate_id()の呼び出し削減</strong><br>";
echo "TemplateAdmin.phpの82行目付近でコメントアウトされているのを確認</li>";
echo "<li><strong>セッションチェックの簡略化</strong><br>";
echo "SmartSession.phpのcheck()メソッドでドメイン検証を緩和</li>";
echo "<li><strong>デバッグログの追加</strong><br>";
echo "ログイン・ログアウト時にデバッグログを出力して原因を特定</li>";
echo "</ol>";
echo "</div>";
echo "</div>";

echo "</body></html>";
?>