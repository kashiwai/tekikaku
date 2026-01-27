<?php
// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Error Debug</title></head><body>";
echo "<h1>🚨 エラーデバッグ</h1>";
echo "<p>現在時刻: " . date('Y-m-d H:i:s') . "</p>";

try {
    // index.phpの内容をテスト
    echo "<h2>📋 PHPバージョン</h2>";
    echo "<p>" . PHP_VERSION . "</p>";

    echo "<h2>🔍 セッション状態</h2>";
    session_name("NET8ADMIN");
    session_start();
    echo "<p>セッション名: " . session_name() . "</p>";
    echo "<p>セッションID: " . session_id() . "</p>";
    echo "<p>セッション状態: " . session_status() . "</p>";

    echo "<h2>📊 $_SESSION内容</h2>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";

    echo "<h2>🔧 require_files_admin.php テスト</h2>";
    $require_file = __DIR__ . '/data/xxxadmin/../../_etc/require_files_admin.php';
    echo "<p>パス: " . $require_file . "</p>";
    echo "<p>存在確認: " . (file_exists($require_file) ? '✅存在' : '❌なし') . "</p>";
    
    if (file_exists($require_file)) {
        echo "<p>✅ require_files_admin.phpを読み込み試行...</p>";
        require_once($require_file);
        echo "<p>✅ require_files_admin.php読み込み成功</p>";
    }

    echo "<h2>🔍 定数確認</h2>";
    echo "<p>PRE_HTML定義済み: " . (defined('PRE_HTML') ? '✅' : '❌') . "</p>";
    echo "<p>DB_HOST定義済み: " . (defined('DB_HOST') ? '✅' : '❌') . "</p>";
    
} catch (Exception $e) {
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>エラーメッセージ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行番号: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<h2>❌ 致命的エラー発生</h2>";
    echo "<p>エラーメッセージ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>ファイル: " . $e->getFile() . "</p>";
    echo "<p>行番号: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>