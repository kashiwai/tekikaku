<?php
/**
 * パスワード形式デバッグ用（一時ファイル）
 */
require_once('../../_etc/require_files_admin.php');

echo "<h1>パスワード形式チェック</h1>";

// DB接続
$host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
$user = defined("DB_USER") ? DB_USER : "net8tech001";
$pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
$name = defined("DB_NAME") ? DB_NAME : "net8_dev";

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    die("DB接続エラー: " . $mysqli->connect_error);
}

// 管理者データ取得
$sql = "SELECT admin_no, admin_id, admin_name, admin_pass, del_flg FROM mst_admin";
$result = $mysqli->query($sql);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>admin_no</th><th>admin_id</th><th>admin_name</th><th>pass_length</th><th>pass_preview</th><th>is_bcrypt</th><th>del_flg</th></tr>";

while ($row = $result->fetch_assoc()) {
    $pass = $row['admin_pass'];
    $pass_length = strlen($pass);
    $pass_preview = substr($pass, 0, 20) . '...';

    // Bcryptハッシュかどうか確認（$2y$または$2a$で始まる）
    $is_bcrypt = (strpos($pass, '$2y$') === 0 || strpos($pass, '$2a$') === 0) ? 'YES' : 'NO';

    echo "<tr>";
    echo "<td>" . htmlspecialchars($row['admin_no']) . "</td>";
    echo "<td>" . htmlspecialchars($row['admin_id']) . "</td>";
    echo "<td>" . htmlspecialchars($row['admin_name']) . "</td>";
    echo "<td>" . $pass_length . "</td>";
    echo "<td>" . htmlspecialchars($pass_preview) . "</td>";
    echo "<td style='color:" . ($is_bcrypt === 'YES' ? 'green' : 'red') . ";font-weight:bold;'>" . $is_bcrypt . "</td>";
    echo "<td>" . htmlspecialchars($row['del_flg']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// テスト用：パスワード「admin」のハッシュを生成
echo "<h2>テスト用ハッシュ生成</h2>";
$test_passwords = ['admin', 'password', '123456', 'sradmin', 'spadmin'];
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>平文</th><th>Bcryptハッシュ</th></tr>";
foreach ($test_passwords as $tp) {
    $hash = password_hash($tp, PASSWORD_BCRYPT);
    echo "<tr>";
    echo "<td>" . htmlspecialchars($tp) . "</td>";
    echo "<td style='font-size:11px;'>" . htmlspecialchars($hash) . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>パスワード検証テスト</h2>";
echo "<p>admin_id「admin」のパスワードが「admin」かテスト:</p>";

$sql = "SELECT admin_pass FROM mst_admin WHERE admin_id = 'admin' AND del_flg = 0";
$result = $mysqli->query($sql);
if ($row = $result->fetch_assoc()) {
    $stored_pass = $row['admin_pass'];

    // 平文比較
    $plain_match = ($stored_pass === 'admin') ? 'YES' : 'NO';

    // Bcrypt検証
    $bcrypt_match = password_verify('admin', $stored_pass) ? 'YES' : 'NO';

    echo "<p>平文一致: <strong style='color:" . ($plain_match === 'YES' ? 'green' : 'red') . ";'>" . $plain_match . "</strong></p>";
    echo "<p>Bcrypt検証: <strong style='color:" . ($bcrypt_match === 'YES' ? 'green' : 'red') . ";'>" . $bcrypt_match . "</strong></p>";
} else {
    echo "<p>admin_id='admin' のレコードが見つかりません</p>";
}

$mysqli->close();
?>
