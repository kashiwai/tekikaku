<?php
/**
 * API Keys管理画面 500エラー診断ツール
 * Created: 2025-12-12
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>API Keys 500エラー診断</title></head><body>";
echo "<h1>🔍 API Keys管理画面 500エラー診断ツール</h1>";
echo "<hr>";

// ステップ1: PHPエラー表示
echo "<h2>📋 STEP 1: PHP環境確認</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Error Reporting Level: " . error_reporting() . "</p>";
echo "<p>Display Errors: " . ini_get('display_errors') . "</p>";
echo "<hr>";

// ステップ2: 必要なファイル確認
echo "<h2>📂 STEP 2: 必要ファイル存在確認</h2>";

$files_to_check = [
    '/var/www/html/data/_etc/require_files_admin.php',
    '/var/www/html/_etc/require_files_admin.php',
    '/var/www/html/data/_lib/TemplateAdmin.class.php',
    '/var/www/html/_lib/TemplateAdmin.class.php',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<p>✅ 存在: $file (" . filesize($file) . " bytes)</p>";
    } else {
        echo "<p>❌ 見つからない: $file</p>";
    }
}
echo "<hr>";

// ステップ3: require_files_admin.phpの内容確認
echo "<h2>📄 STEP 3: require_files_admin.php読み込みテスト</h2>";

$admin_require_files = [
    '/var/www/html/data/_etc/require_files_admin.php',
    '/var/www/html/_etc/require_files_admin.php'
];

$found_admin_require = false;
foreach ($admin_require_files as $file) {
    if (file_exists($file)) {
        $found_admin_require = true;
        echo "<p>✅ require_files_admin.php発見: $file</p>";
        
        // ファイル内容の確認
        $content = file_get_contents($file);
        echo "<p>ファイルサイズ: " . strlen($content) . " bytes</p>";
        
        // 最初の20行を表示
        echo "<h3>最初の20行:</h3>";
        echo "<pre style='background:#f5f5f5; padding:10px;'>";
        $lines = explode("\n", $content);
        for ($i = 0; $i < min(20, count($lines)); $i++) {
            echo htmlspecialchars($lines[$i]) . "\n";
        }
        echo "</pre>";
        break;
    }
}

if (!$found_admin_require) {
    echo "<p>❌ require_files_admin.phpが見つかりません！</p>";
    echo "<p>⚠️ 解決策: require_files_admin.phpファイルを作成する必要があります</p>";
}
echo "<hr>";

// ステップ4: データベース接続確認
echo "<h2>💾 STEP 4: データベース接続テスト</h2>";

// _etc/require_files.phpから設定を読み込み
$require_paths = [
    '/var/www/html/data/_etc/require_files.php',
    '/var/www/html/_etc/require_files.php'
];

$db_connected = false;
foreach ($require_paths as $path) {
    if (file_exists($path)) {
        echo "<p>📝 require_files.phpを読み込んでDB接続をテスト: $path</p>";
        
        try {
            // DB接続をテスト
            $test_connection = @new mysqli('136.116.70.86', 'net8tech001', 'Nene11091108!!', 'net8_dev', 3306);
            
            if ($test_connection && !$test_connection->connect_error) {
                echo "<p>✅ データベース接続成功！</p>";
                $db_connected = true;
                
                // api_keysテーブルの存在確認
                $result = $test_connection->query("SHOW TABLES LIKE 'api_keys'");
                if ($result && $result->num_rows > 0) {
                    echo "<p>✅ api_keysテーブル存在確認</p>";
                    
                    // カラム情報取得
                    $columns = $test_connection->query("DESCRIBE api_keys");
                    if ($columns) {
                        echo "<h3>📊 api_keysテーブル構造:</h3>";
                        echo "<table border='1' style='border-collapse:collapse;'>";
                        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
                        while ($col = $columns->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>{$col['Field']}</td>";
                            echo "<td>{$col['Type']}</td>";
                            echo "<td>{$col['Null']}</td>";
                            echo "<td>{$col['Key']}</td>";
                            echo "<td>{$col['Default']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                    }
                    
                    // レコード数確認
                    $count = $test_connection->query("SELECT COUNT(*) as cnt FROM api_keys");
                    if ($count) {
                        $row = $count->fetch_assoc();
                        echo "<p>📊 api_keysレコード数: {$row['cnt']}件</p>";
                    }
                } else {
                    echo "<p>❌ api_keysテーブルが存在しません</p>";
                }
                
                $test_connection->close();
            } else {
                echo "<p>❌ データベース接続失敗: " . ($test_connection ? $test_connection->connect_error : 'Connection failed') . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ エラー: " . $e->getMessage() . "</p>";
        }
        break;
    }
}

if (!$db_connected) {
    echo "<p>⚠️ データベース接続に失敗しました</p>";
}
echo "<hr>";

// ステップ5: 推奨される修正
echo "<h2>🔧 STEP 5: 推奨される修正方法</h2>";

if (!$found_admin_require) {
    echo "<h3>📝 require_files_admin.phpを作成:</h3>";
    echo "<pre style='background:#f5f5f5; padding:10px;'>";
    echo htmlspecialchars("<?php
// require_files_admin.php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 基本的なrequire_filesを読み込み
require_once(__DIR__ . '/require_files.php');

// 管理画面用の追加クラス
require_once(__DIR__ . '/../_lib/TemplateAdmin.class.php');
require_once(__DIR__ . '/../_lib/SqlString.class.php');

// セッション開始
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>");
    echo "</pre>";
}

echo "<h3>✅ 次のステップ:</h3>";
echo "<ol>";
echo "<li>上記のrequire_files_admin.phpファイルを作成</li>";
echo "<li>TemplateAdmin.class.phpが存在することを確認</li>";
echo "<li>データベース接続情報が正しいことを確認</li>";
echo "<li>api_keysテーブルが存在することを確認</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='/xxxadmin/api_keys_manage.php'>API Keys管理画面に戻る</a></p>";
echo "</body></html>";
?>