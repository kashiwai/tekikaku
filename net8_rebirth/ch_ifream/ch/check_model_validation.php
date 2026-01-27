<?php
/**
 * model.phpの画像バリデーション確認スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>model.php バリデーション確認</h1>";
echo "<hr>";

$model_php_path = __DIR__ . '/data/xxxadmin/model.php';

if (!file_exists($model_php_path)) {
    die("<p>❌ model.php が見つかりません: {$model_php_path}</p>");
}

echo "<p>✅ model.php パス: {$model_php_path}</p>";

$content = file_get_contents($model_php_path);

// A1410の存在チェック
$a1410_count = substr_count($content, 'A1410');
echo "<h2>A1410 出現回数: {$a1410_count}</h2>";

// 652行目付近を確認
$lines = explode("\n", $content);

echo "<h3>652行目付近（リスト画像チェック）:</h3>";
echo "<pre>";
for ($i = 648; $i <= 656; $i++) {
    if (isset($lines[$i-1])) {
        $line = htmlspecialchars($lines[$i-1]);
        $status = (strpos($lines[$i-1], 'throw') !== false && strpos($lines[$i-1], '//') === false) ? '❌ 有効' : '✅ 無効';
        echo sprintf("%4d: %s %s\n", $i, $status, $line);
    }
}
echo "</pre>";

echo "<h3>707行目付近（詳細画像チェック）:</h3>";
echo "<pre>";
for ($i = 703; $i <= 711; $i++) {
    if (isset($lines[$i-1])) {
        $line = htmlspecialchars($lines[$i-1]);
        $status = (strpos($lines[$i-1], 'throw') !== false && strpos($lines[$i-1], '//') === false) ? '❌ 有効' : '✅ 無効';
        echo sprintf("%4d: %s %s\n", $i, $status, $line);
    }
}
echo "</pre>";

echo "<h3>765行目付近（リール画像チェック）:</h3>";
echo "<pre>";
for ($i = 761; $i <= 769; $i++) {
    if (isset($lines[$i-1])) {
        $line = htmlspecialchars($lines[$i-1]);
        $status = (strpos($lines[$i-1], 'throw') !== false && strpos($lines[$i-1], '//') === false) ? '❌ 有効' : '✅ 無効';
        echo sprintf("%4d: %s %s\n", $i, $status, $line);
    }
}
echo "</pre>";

echo "<hr>";
echo "<h2>💡 解説</h2>";
echo "<ul>";
echo "<li>❌ 有効 = throw文がコメントアウトされていない（エラーが発生する）</li>";
echo "<li>✅ 無効 = throw文がコメントアウトされている（エラーが発生しない）</li>";
echo "</ul>";
?>
