<?php
// mst_setting テーブルの内容を確認するスクリプト
require_once('../../_etc/require_files.php');

try {
    $db = new NetDB();

    echo "<h2>mst_setting テーブルの内容確認</h2>\n";
    echo "<pre>\n";

    $sql = "SELECT * FROM mst_setting WHERE del_flg = 0 ORDER BY setting_no";
    $result = $db->query($sql);

    $count = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "========== 設定 #{$count} ==========\n";
        echo "setting_no: {$row['setting_no']}\n";
        echo "setting_type: {$row['setting_type']}\n";
        echo "setting_name: {$row['setting_name']}\n";
        echo "setting_key: {$row['setting_key']}\n";
        echo "setting_format: {$row['setting_format']}\n";
        echo "setting_val: {$row['setting_val']}\n";
        echo "remarks: {$row['remarks']}\n";
        echo "\n";
    }

    if ($count == 0) {
        echo "データが見つかりませんでした。\n";
    } else {
        echo "合計: {$count}件\n";
    }

    echo "\n</pre>\n";

    echo "<h3>営業時間関連の設定を検索</h3>\n";
    echo "<pre>\n";

    $sql2 = "SELECT * FROM mst_setting WHERE setting_key LIKE '%TIME%' OR setting_key LIKE '%OPEN%' OR setting_key LIKE '%CLOSE%'";
    $result2 = $db->query($sql2);

    $count2 = 0;
    while ($row2 = $result2->fetch(PDO::FETCH_ASSOC)) {
        $count2++;
        echo "Found: {$row2['setting_key']} = {$row2['setting_val']}\n";
    }

    if ($count2 == 0) {
        echo "営業時間関連の設定は見つかりませんでした。\n";
    }

    echo "</pre>\n";

} catch (Exception $e) {
    echo "<pre>エラー: " . $e->getMessage() . "</pre>\n";
}
?>
