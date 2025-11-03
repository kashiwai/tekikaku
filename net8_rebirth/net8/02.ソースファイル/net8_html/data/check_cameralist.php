<?php
/*
 * check_cameralist.php
 * mst_cameralistテーブル確認用
 */

require_once('../_etc/require_files.php');

$template = new TemplateUser(false);

echo "<h1>mst_cameralistテーブル確認</h1>";
echo "<pre>";

// テーブル構造確認
echo "=== テーブル構造 ===\n";
$sql = "DESCRIBE mst_cameralist";
$result = $template->DB->queryAll($sql, MDB2_FETCHMODE_ASSOC);
if ($result) {
    foreach($result as $row) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} else {
    echo "❌ mst_cameralistテーブルが存在しません\n";
}

// 全データ表示
echo "\n=== 全データ ===\n";
$sql = "SELECT * FROM mst_cameralist";
$rows = $template->DB->queryAll($sql, MDB2_FETCHMODE_ASSOC);
if ($rows && count($rows) > 0) {
    foreach($rows as $row) {
        print_r($row);
        echo "\n";
    }
} else {
    echo "❌ データが登録されていません\n";
}

// MACアドレスで検索
echo "\n=== MAC=34-a6-ef-35-73-73 のデータ ===\n";
$sql = "SELECT * FROM mst_cameralist WHERE mac_address = '34-a6-ef-35-73-73'";
$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
if ($row) {
    print_r($row);
} else {
    echo "❌ 該当データなし\n";
}

echo "</pre>";
?>
