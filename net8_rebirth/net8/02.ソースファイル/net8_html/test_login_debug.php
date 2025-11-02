<?php
/**
 * ログインデバッグテスト
 */

// 環境設定読み込み
require_once("_sys/start.php");

// テストパラメータ
$testEmail = 'test@example.com';
$testPass = 'password';

echo "<h1>ログインデバッグテスト</h1>\n";

// 1. ユーザー情報取得
echo "<h2>1. ユーザー情報取得</h2>\n";
$sql = "SELECT member_no, nickname, mail, pass, state FROM mst_member WHERE mail = '" . $testEmail . "'";
$row = $GLOBALS["DB"]->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($row)) {
    echo "❌ ユーザーが見つかりません<br>\n";
    exit;
}

echo "✅ ユーザー見つかりました:<br>\n";
echo "member_no: " . $row["member_no"] . "<br>\n";
echo "nickname: " . $row["nickname"] . "<br>\n";
echo "mail: " . $row["mail"] . "<br>\n";
echo "state: " . $row["state"] . "<br>\n";
echo "pass (hash): " . substr($row["pass"], 0, 20) . "...<br>\n";

// 2. パスワード検証テスト
echo "<h2>2. パスワード検証テスト</h2>\n";
echo "入力パスワード: '" . $testPass . "'<br>\n";
echo "保存されたハッシュ: " . $row["pass"] . "<br>\n";

$verifyResult = password_verify($testPass, $row["pass"]);
echo "<br>password_verify結果: ";
if ($verifyResult) {
    echo "✅ <strong>MATCH</strong> - パスワードは正しい<br>\n";
} else {
    echo "❌ <strong>NO MATCH</strong> - パスワードが一致しません<br>\n";
}

// 3. SmartAutoCheckテスト
echo "<h2>3. SmartAutoCheckテスト</h2>\n";
$template = new TemplateUser();
$template->DB = $GLOBALS["DB"];

$errMessage = (new SmartAutoCheck($template))
    ->item($testPass)
        ->password_verify("U0103", $row["pass"])
    ->report(false);

echo "SmartAutoCheck結果:<br>\n";
if (mb_strlen($errMessage) == 0) {
    echo "✅ エラーなし - パスワード検証成功<br>\n";
} else {
    echo "❌ エラー: " . $errMessage . "<br>\n";
}

// 4. mst_grantPoint確認
echo "<h2>4. mst_grantPointテーブル確認</h2>\n";
$sql = "SELECT * FROM mst_grantPoint WHERE proc_cd = '02'";
$gpRow = $GLOBALS["DB"]->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($gpRow)) {
    echo "❌ mst_grantPoint (proc_cd='02') が見つかりません<br>\n";
} else {
    echo "✅ mst_grantPoint見つかりました:<br>\n";
    echo "point: " . $gpRow["point"] . "<br>\n";
    echo "limit_days: " . $gpRow["limit_days"] . "<br>\n";
}

// 5. AUTH_MEMBER_MOBILE設定確認
echo "<h2>5. AUTH_MEMBER_MOBILE設定</h2>\n";
echo "AUTH_MEMBER_MOBILE: " . (AUTH_MEMBER_MOBILE ? "true" : "false") . "<br>\n";
echo "AUTH_MOBILE_VALID_DAYS: " . (defined('AUTH_MOBILE_VALID_DAYS') ? AUTH_MOBILE_VALID_DAYS : "未定義") . "<br>\n";

echo "<h2>テスト完了</h2>\n";
echo "<p><a href='login.php'>実際のログインページへ</a></p>\n";
?>
