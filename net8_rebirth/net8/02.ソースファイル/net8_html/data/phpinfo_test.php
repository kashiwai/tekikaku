<?php
/**
 * PHP設定確認
 */

echo "<h1>PHP設定確認</h1>\n";

echo "<h2>エラー表示設定</h2>\n";
echo "display_errors: " . ini_get('display_errors') . "<br>\n";
echo "error_reporting: " . error_reporting() . "<br>\n";
echo "log_errors: " . ini_get('log_errors') . "<br>\n";
echo "error_log: " . ini_get('error_log') . "<br>\n";

echo "<h2>意図的なエラーテスト</h2>\n";
echo "これからエラーを発生させます...<br>\n";

// 存在しない関数を呼び出す
try {
    undefinedFunction();
} catch (Error $e) {
    echo "<pre style='background: #ffcccc; padding: 10px;'>";
    echo "Caught Error: " . $e->getMessage();
    echo "</pre>";
}

echo "<h2>存在しない変数アクセス</h2>\n";
echo "test: " . $undefinedVariable . "<br>\n";

echo "<h2>完了</h2>\n";
?>
