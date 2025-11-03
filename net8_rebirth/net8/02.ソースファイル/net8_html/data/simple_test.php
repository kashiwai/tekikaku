<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>ステップバイステップテスト</h1>\n";
try {
    echo "<h2>Step 1: require_files.php</h2>\n";
    require_once(__DIR__ . '/../_etc/require_files.php');
    echo "✅ OK<br>\n";
    echo "<h2>Step 2: TemplateUser</h2>\n";
    $template = new TemplateUser(false);
    echo "✅ OK<br>\n";
    echo "<h2>Step 3: GetRefTimeTodayExt()</h2>\n";
    $refToDay = GetRefTimeTodayExt();
    echo "✅ OK: " . $refToDay . "<br>\n";
    echo "<h2>Step 4: SearchMachineBase()</h2>\n";
    $sqls = new SqlString($template->DB);
    $template->SearchMachineBase($sqls, false);
    echo "✅ OK<br>\n";
    echo "<h2>Step 5: SQL作成</h2>\n";
    $sqls->orderby('dm.release_date desc');
    $count_sql = $sqls->resetField()->field("count(*)")->createSQL();
    echo "✅ SQL: <pre>" . htmlspecialchars($count_sql) . "</pre>\n";
    echo "<h2>Step 6: SQL実行</h2>\n";
    $allrows = $template->DB->getOne($count_sql);
    echo "✅ OK: 件数 = " . $allrows . "<br>\n";
    echo "<h2>✅ 全ステップ成功！</h2>\n";
} catch (Exception $e) {
    echo "<h2>❌ Exception</h2><pre style='background:#ffcccc;padding:20px'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
} catch (Error $e) {
    echo "<h2>❌ Error</h2><pre style='background:#ffcccc;padding:20px'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>";
}
?>
