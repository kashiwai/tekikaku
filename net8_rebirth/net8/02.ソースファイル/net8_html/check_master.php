<?php
$DB = new PDO('mysql:host=136.116.70.86;port=3306;dbname=net8_dev;charset=utf8mb4', 'net8_dev', 'net8_dev_password');
$DB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== メーカー一覧 ===\n";
$makers = $DB->query("SELECT maker_no, maker_name FROM mst_maker WHERE del_flg = 0 ORDER BY maker_no");
foreach ($makers as $row) {
    echo "ID: {$row['maker_no']} - {$row['maker_name']}\n";
}

echo "\n=== タイプ一覧（スロット） ===\n";
$types = $DB->query("SELECT type_no, type_name FROM mst_type WHERE type_no > 4 AND del_flg = 0 ORDER BY type_no");
foreach ($types as $row) {
    echo "ID: {$row['type_no']} - {$row['type_name']}\n";
}

echo "\n=== 号機一覧 ===\n";
$units = $DB->query("SELECT unit_no, unit_name FROM mst_unit WHERE del_flg = 0 ORDER BY unit_no");
foreach ($units as $row) {
    echo "ID: {$row['unit_no']} - {$row['unit_name']}\n";
}
?>
