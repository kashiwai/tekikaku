<?php
/**
 * 500エラー完全診断ツール
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 500エラー完全診断</h1>";
echo "<hr>";

echo "<h2>📋 STEP 1: PHP基本情報</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . __FILE__ . "</p>";
echo "<hr>";

echo "<h2>📂 STEP 2: index.php存在確認</h2>";
$index_paths = [
    '/var/www/html/index.php',
    '/var/www/html/data/index.php',
];

foreach ($index_paths as $path) {
    if (file_exists($path)) {
        echo "<p>✅ 発見: $path</p>";
        echo "<p>サイズ: " . filesize($path) . " bytes</p>";
        echo "<p>読み取り可: " . (is_readable($path) ? 'Yes' : 'No') . "</p>";

        // 最初の50行を表示
        echo "<h3>📄 ファイル内容（最初の50行）:</h3>";
        echo "<pre style='background:#f5f5f5; padding:10px; overflow-x:auto;'>";
        $lines = file($path);
        $line_count = min(50, count($lines));
        for ($i = 0; $i < $line_count; $i++) {
            echo sprintf("%3d: %s", $i + 1, htmlspecialchars($lines[$i]));
        }
        echo "</pre>";
    } else {
        echo "<p>❌ 存在しない: $path</p>";
    }
}

echo "<hr>";
echo "<h2>📂 STEP 3: _etc/require_files.php確認</h2>";
$require_paths = [
    '/var/www/html/_etc/require_files.php',
    '/var/www/html/data/_etc/require_files.php',
];

foreach ($require_paths as $path) {
    if (file_exists($path)) {
        echo "<p>✅ 発見: $path</p>";
        echo "<p>サイズ: " . filesize($path) . " bytes</p>";
        echo "<p>読み取り可: " . (is_readable($path) ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p>❌ 存在しない: $path</p>";
    }
}

echo "<hr>";
echo "<h2>🔍 STEP 4: index.php読み込みテスト</h2>";

$index_file = '/var/www/html/data/index.php';
if (file_exists($index_file)) {
    echo "<p>✅ index.phpが存在します</p>";
    echo "<p>⚠️ index.phpの実行を試みます...</p>";
    echo "<hr>";

    try {
        ob_start();
        include($index_file);
        $output = ob_get_clean();

        echo "<h3>✅ index.php実行成功</h3>";
        echo "<p>出力サイズ: " . strlen($output) . " bytes</p>";

        if (strlen($output) > 0) {
            echo "<h3>📄 出力内容:</h3>";
            echo "<div style='background:#f5f5f5; padding:10px; max-height:500px; overflow:auto;'>";
            echo $output;
            echo "</div>";
        } else {
            echo "<p>⚠️ 出力が空です</p>";
        }
    } catch (Throwable $e) {
        echo "<h3>❌ エラー発生</h3>";
        echo "<p><strong>エラータイプ:</strong> " . get_class($e) . "</p>";
        echo "<p><strong>メッセージ:</strong></p>";
        echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
        echo "<p><strong>ファイル:</strong> " . $e->getFile() . "</p>";
        echo "<p><strong>行番号:</strong> " . $e->getLine() . "</p>";
        echo "<p><strong>スタックトレース:</strong></p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "<p>❌ index.phpが見つかりません: $index_file</p>";
}

echo "<hr>";
echo "<h2>📋 STEP 5: Apache/PHPエラーログ確認</h2>";

$error_log_paths = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    ini_get('error_log'),
];

echo "<p>PHPエラーログ設定: " . (ini_get('error_log') ?: '(未設定)') . "</p>";
echo "<p>log_errors: " . (ini_get('log_errors') ? 'On' : 'Off') . "</p>";

foreach ($error_log_paths as $path) {
    if (empty($path)) continue;

    echo "<h3>📁 $path</h3>";
    if (file_exists($path)) {
        echo "<p>✅ ファイル存在</p>";
        echo "<p>サイズ: " . filesize($path) . " bytes</p>";
        echo "<p>読み取り可: " . (is_readable($path) ? 'Yes' : 'No') . "</p>";

        if (is_readable($path)) {
            echo "<h4>最新50行:</h4>";
            echo "<pre style='background:#f5f5f5; padding:10px; max-height:400px; overflow:auto;'>";
            $output = shell_exec("tail -50 " . escapeshellarg($path) . " 2>&1");
            echo htmlspecialchars($output ?: '(空)');
            echo "</pre>";
        } else {
            echo "<p>❌ 読み取り権限がありません</p>";
        }
    } else {
        echo "<p>❌ ファイルが存在しません</p>";
    }
}

echo "<hr>";
echo "<h2>🎉 診断完了</h2>";
?>
