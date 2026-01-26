<?php
/**
 * index.php エラーデバッグ
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 index.php エラーデバッグ</h1>";
echo "<hr>";

try {
    echo "<h2>STEP 1: 基本設定確認</h2>";

    // 環境変数確認
    $db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
    $db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
    $db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';

    echo "<p>✅ DB_HOST: $db_host</p>";
    echo "<p>✅ DB_NAME: $db_name</p>";
    echo "<p>✅ DB_USER: $db_user</p>";

    echo "<hr>";
    echo "<h2>STEP 2: require_files.php 読み込みテスト</h2>";

    // 正しいパス: /var/www/html/_etc/require_files.php
    $require_file = __DIR__ . '/_etc/require_files.php';
    if (file_exists($require_file)) {
        echo "<p>✅ require_files.php が存在します</p>";
        echo "<p>パス: $require_file</p>";

        // 実際に読み込んでみる
        echo "<p>読み込み開始...</p>";
        require_once($require_file);
        echo "<p>✅ require_files.php 読み込み成功</p>";
    } else {
        echo "<p>❌ require_files.php が見つかりません</p>";
        echo "<p>パス: $require_file</p>";
    }

    echo "<hr>";
    echo "<h2>STEP 3: index.php 読み込みテスト</h2>";

    $index_file = __DIR__ . '/data/index.php';
    if (file_exists($index_file)) {
        echo "<p>✅ index.php が存在します</p>";
        echo "<p>パス: $index_file</p>";

        echo "<p>⚠️ index.phpの読み込みを試みます...</p>";
        echo "<hr>";

        // index.phpを読み込む
        ob_start();
        include($index_file);
        $output = ob_get_clean();

        echo "<h3>✅ index.php読み込み成功</h3>";
        echo "<p>出力サイズ: " . strlen($output) . " bytes</p>";

        // 出力を表示
        echo "<hr>";
        echo "<h3>📄 index.php 出力内容:</h3>";
        echo $output;

    } else {
        echo "<p>❌ index.php が見つかりません</p>";
        echo "<p>パス: $index_file</p>";
    }

} catch (Exception $e) {
    echo "<hr>";
    echo "<h2>❌ エラー発生</h2>";
    echo "<p><strong>エラーメッセージ:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>ファイル:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>スタックトレース:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
} catch (Error $e) {
    echo "<hr>";
    echo "<h2>❌ Fatal Error 発生</h2>";
    echo "<p><strong>エラーメッセージ:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    echo "<p><strong>ファイル:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>スタックトレース:</strong></p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
