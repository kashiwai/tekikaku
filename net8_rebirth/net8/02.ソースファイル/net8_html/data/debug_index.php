<?php
/**
 * デバッグ用：index.phpのエラー確認
 */

// エラー表示を有効化
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('log_errors', '1');

echo "<h1>デバッグ開始</h1>";
echo "<p>Step 1: PHPは動作しています</p>";

// require_files.phpを読み込み
echo "<p>Step 2: require_files.phpを読み込み中...</p>";
try {
    require_once('../_etc/require_files.php');
    echo "<p>✅ require_files.php読み込み成功</p>";
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    die();
}

// 定数チェック
echo "<p>Step 3: 定数確認</p>";
echo "<ul>";
echo "<li>SITE_URL: " . (defined('SITE_URL') ? SITE_URL : '未定義') . "</li>";
echo "<li>FOLDER_LANG: " . (defined('FOLDER_LANG') ? FOLDER_LANG : '未定義') . "</li>";
echo "<li>DIR_HTML: " . (defined('DIR_HTML') ? DIR_HTML : '未定義') . "</li>";
echo "<li>GLOBAL_OPEN_TIME: " . (defined('GLOBAL_OPEN_TIME') ? GLOBAL_OPEN_TIME : '未定義') . "</li>";
echo "</ul>";

// TemplateUserクラス存在確認
echo "<p>Step 4: TemplateUserクラス確認</p>";
if (class_exists('TemplateUser')) {
    echo "<p>✅ TemplateUserクラスが存在します</p>";
} else {
    echo "<p>❌ TemplateUserクラスが存在しません</p>";
    die();
}

// データベース接続確認
echo "<p>Step 5: データベース接続確認</p>";
try {
    $template = new TemplateUser(false);
    echo "<p>✅ TemplateUserインスタンス作成成功</p>";
    echo "<p>データベース接続: " . (isset($template->DB) ? "OK" : "NG") . "</p>";
} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    die();
}

// お知らせデータ取得テスト
echo "<p>Step 6: お知らせデータ取得テスト</p>";
try {
    $refToDay = GetRefTimeTodayExt();
    echo "<p>基準日: " . htmlspecialchars($refToDay) . "</p>";

    $notice_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
        ->select()
            ->field("dnl.lang, dnl.title")
            ->field("dn.notice_no, dn.notice_name")
            ->from( "dat_notice_lang dnl" )
            ->from( "inner join dat_notice dn on dn.notice_no = dnl.notice_no and dn.del_flg <> 1" )
            ->where()
                ->and(false, "dnl.lang = ", FOLDER_LANG, FD_STR)
            ->orderby( 'dn.disp_order asc' )
        ->createSql("\n");

    echo "<p>SQL: <pre>" . htmlspecialchars($notice_sql) . "</pre></p>";

    $notice_row = $template->DB->getAll($notice_sql, MDB2_FETCHMODE_ASSOC);
    echo "<p>✅ お知らせ件数: " . count($notice_row) . "件</p>";

} catch (Exception $e) {
    echo "<p>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h2>✅ デバッグ完了</h2>";
echo "<p>元のindex.phpにエラーがある場合は、上記のステップのどこかで止まっているはずです。</p>";
?>
