<?php
/**
 * ログイン後のエラーデバッグ
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>ログイン後デバッグ</h1>\n";

try {
    require_once(__DIR__ . '/../_etc/require_files.php');

    $template = new TemplateUser(false);

    echo "<h2>Step 1: セッション確認</h2>\n";
    $isLogin = $template->checkSessionUser(true, false);
    echo "ログイン状態: " . ($isLogin ? "✅ ログイン中" : "❌ 未ログイン") . "<br>\n";

    if ($isLogin && isset($template->Session->UserInfo)) {
        echo "<h3>セッション情報</h3>\n";
        echo "<pre>";
        foreach ($template->Session->UserInfo as $key => $value) {
            if ($key === 'pass') {
                echo "$key => (hidden)\n";
            } else {
                echo "$key => " . var_export($value, true) . "\n";
            }
        }
        echo "</pre>\n";

        echo "<h2>Step 2: ログイン状態での処理テスト</h2>\n";

        // index.phpの82-110行目と同じ処理
        $_login_flg  = false;
        $testerFlg = false;

        try {
            if (isset($template->Session->UserInfo["member_no"]) &&
                isset($template->Session->UserInfo["mail"])) {

                $sql = (new SqlString())
                    ->setAutoConvert( [$template->DB,"conv_sql"] )
                    ->select()
                        ->field("men.tester_flg")
                        ->from("mst_member men")
                        ->where()
                            ->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
                            ->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
                            ->and(false, "men.state = ", "1", FD_NUM)
                        ->createSQL();

                echo "<h3>実行SQL</h3>\n";
                echo "<pre>" . htmlspecialchars($sql) . "</pre>\n";

                $row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

                echo "<h3>取得結果</h3>\n";
                echo "<pre>";
                var_dump($row);
                echo "</pre>\n";

                if ($row && isset($row["tester_flg"])) {
                    $testerFlg = ($row["tester_flg"] == "1");
                    echo "<p>✅ tester_flg = " . ($testerFlg ? "true" : "false") . "</p>\n";
                }
                $_login_flg  = true;
                echo "<p>✅ 処理成功</p>\n";
            } else {
                echo "<p>❌ member_no または mail が存在しません</p>\n";
            }
        } catch (Exception $e) {
            echo "<h3 style='color:red;'>❌ エラー発生</h3>\n";
            echo "<pre style='background:#ffcccc;padding:20px;'>";
            echo "Message: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
            echo "Trace:\n" . $e->getTraceAsString();
            echo "</pre>\n";
        }
    }

    echo "<h2>✅ デバッグ完了</h2>\n";
    echo "<p><a href='/data/'>トップページへ</a></p>\n";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Exception</h2>\n";
    echo "<pre style='background:#ffcccc;padding:20px;'>";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    echo "Trace:\n" . $e->getTraceAsString();
    echo "</pre>\n";
}
?>
