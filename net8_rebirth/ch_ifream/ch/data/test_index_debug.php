<?php
/**
 * Index.php デバッグテスト
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>/data/index.php デバッグテスト</h1>\n";

try {
    echo "<h2>1. require_files.php 読み込み</h2>\n";
    require_once(__DIR__ . '/../_etc/require_files.php');
    echo "✅ require_files.php 読み込み成功<br>\n";

    echo "<h2>2. TemplateUser インスタンス生成</h2>\n";
    $template = new TemplateUser(false);
    echo "✅ TemplateUser インスタンス生成成功<br>\n";

    echo "<h2>3. GLOBAL_OPEN_TIME / GLOBAL_CLOSE_TIME 確認</h2>\n";
    echo "GLOBAL_OPEN_TIME: " . (defined('GLOBAL_OPEN_TIME') ? GLOBAL_OPEN_TIME : "未定義") . "<br>\n";
    echo "GLOBAL_CLOSE_TIME: " . (defined('GLOBAL_CLOSE_TIME') ? GLOBAL_CLOSE_TIME : "未定義") . "<br>\n";

    echo "<h2>4. GetRefTimeTodayExt() 関数呼び出し</h2>\n";
    if (function_exists('GetRefTimeTodayExt')) {
        $refToDay = GetRefTimeTodayExt();
        echo "✅ GetRefTimeTodayExt() 成功: " . $refToDay . "<br>\n";
    } else {
        echo "❌ GetRefTimeTodayExt() 関数が存在しません<br>\n";
    }

    echo "<h2>5. データベース接続確認</h2>\n";
    if (isset($template->DB)) {
        echo "✅ DB接続オブジェクト存在<br>\n";

        $sql = "SELECT COUNT(*) as cnt FROM dat_machine";
        $cnt = $template->DB->getOne($sql);
        echo "✅ dat_machine テーブル件数: " . $cnt . "<br>\n";
    } else {
        echo "❌ DB接続オブジェクトなし<br>\n";
    }

    echo "<h2>6. $GLOBALS 確認</h2>\n";
    echo "langList: " . (isset($GLOBALS["langList"]) ? "あり" : "なし") . "<br>\n";
    echo "grantPointStatusList: " . (isset($GLOBALS["grantPointStatusList"]) ? "あり" : "なし") . "<br>\n";

    echo "<h2>✅ すべてのテスト成功</h2>\n";
    echo "<p><a href='index.php'>index.phpへ</a></p>\n";

} catch (Exception $e) {
    echo "<h2>❌ エラー発生</h2>\n";
    echo "<pre style='background: #ffcccc; padding: 20px; border: 2px solid red;'>";
    echo "エラーメッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
} catch (Error $e) {
    echo "<h2>❌ PHPエラー発生</h2>\n";
    echo "<pre style='background: #ffcccc; padding: 20px; border: 2px solid red;'>";
    echo "エラーメッセージ: " . $e->getMessage() . "\n";
    echo "ファイル: " . $e->getFile() . "\n";
    echo "行番号: " . $e->getLine() . "\n";
    echo "\nスタックトレース:\n";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
