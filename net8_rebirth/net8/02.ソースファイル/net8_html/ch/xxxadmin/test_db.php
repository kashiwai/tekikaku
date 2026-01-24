<?php
// データベース接続テスト
require_once('../../_etc/require_files_admin.php');

echo "<h1>データベース接続テスト</h1>";

// DB接続
$db = new NetDB();

echo "<h2>1. メーカーデータ (全件)</h2>";
$sql = "SELECT COUNT(*) as count FROM mst_maker";
$count = $db->getOne($sql);
echo "<p>全件数: " . $count . "</p>";

echo "<h2>2. メーカーデータ (del_flg=0)</h2>";
$sql = "SELECT COUNT(*) as count FROM mst_maker WHERE del_flg = 0";
$count = $db->getOne($sql);
echo "<p>有効件数: " . $count . "</p>";

echo "<h2>3. メーカーデータ (先頭5件)</h2>";
$sql = "SELECT maker_no, maker_name, del_flg FROM mst_maker LIMIT 5";
$rows = $db->getAll($sql, PDO::FETCH_ASSOC);
echo "<table border='1'>";
echo "<tr><th>maker_no</th><th>maker_name</th><th>del_flg</th></tr>";
foreach ($rows as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['maker_no']) . "</td>";
    echo "<td>" . htmlspecialchars($row['maker_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['del_flg']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>4. 会員データ</h2>";
$sql = "SELECT COUNT(*) as count FROM mst_member";
$count = $db->getOne($sql);
echo "<p>会員数: " . $count . "</p>";

echo "<h2>5. 管理者データ</h2>";
$sql = "SELECT admin_no, admin_name, admin_id FROM mst_admin";
$rows = $db->getAll($sql, PDO::FETCH_ASSOC);
echo "<table border='1'>";
echo "<tr><th>admin_no</th><th>admin_name</th><th>admin_id</th></tr>";
foreach ($rows as $row) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['admin_no']) . "</td>";
    echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
    echo "<td>" . htmlspecialchars($row['admin_id']) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>6. データベース接続情報</h2>";
echo "<p>DB_HOST: " . DB_HOST . "</p>";
echo "<p>DB_NAME: " . DB_NAME . "</p>";
echo "<p>DB_USER: " . DB_USER . "</p>";
?>
