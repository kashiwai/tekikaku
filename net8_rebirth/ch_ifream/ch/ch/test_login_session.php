<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ログインセッションテスト</h1>\n";

try {
    require_once(__DIR__ . '/../_etc/require_files.php');

    echo "<h2>Step 1: TemplateUser作成</h2>\n";
    $template = new TemplateUser(false);
    echo "✅ OK<br>\n";

    echo "<h2>Step 2: セッション確認</h2>\n";
    $isLogin = $template->checkSessionUser(true, false);
    echo "ログイン状態: " . ($isLogin ? "ログイン中" : "未ログイン") . "<br>\n";

    if ($isLogin) {
        echo "<h3>セッション情報</h3>\n";
        echo "<pre>";
        print_r($template->Session->UserInfo);
        echo "</pre>\n";

        echo "<h2>Step 3: ユーザー情報取得</h2>\n";
        $sql = (new SqlString())
            ->setAutoConvert([$template->DB, "conv_sql"])
            ->select()
                ->field("men.member_no, men.mail, men.tester_flg")
                ->from("mst_member men")
                ->where()
                    ->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
                    ->and(false, "men.state = ", "1", FD_NUM)
                ->createSQL();

        $row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($row);
        echo "</pre>\n";

        echo "<h2>Step 4: assignCommon実行</h2>\n";
        $template->open("index.html");
        $template->assignCommon();
        echo "✅ OK<br>\n";
    }

    echo "<h2>✅ 全ステップ成功</h2>\n";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Exception</h2>\n";
    echo "<pre style='background:#ffcccc;padding:20px;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>\n";
} catch (Error $e) {
    echo "<h2 style='color:red;'>❌ Error</h2>\n";
    echo "<pre style='background:#ffcccc;padding:20px;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>\n";
}
?>
