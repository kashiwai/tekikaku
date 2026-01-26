<?php
/**
 * パスワードリセット実行（一時ファイル - 使用後削除）
 */
require_once('../../_etc/require_files_admin.php');

// DB接続
$host = defined("DB_HOST") ? DB_HOST : "136.116.70.86";
$user = defined("DB_USER") ? DB_USER : "net8tech001";
$pass = defined("DB_PASS") ? DB_PASS : "Nene11091108!!";
$name = defined("DB_NAME") ? DB_NAME : "net8_dev";

$mysqli = new mysqli($host, $user, $pass, $name);
if ($mysqli->connect_error) {
    die("DB接続エラー: " . $mysqli->connect_error);
}

// admin123のBcryptハッシュ生成
$new_password = 'admin123';
$new_hash = password_hash($new_password, PASSWORD_BCRYPT);

// 全管理者のパスワードをリセット
$admin_ids = ['admin', 'sradmin', 'spadmin'];

echo "<h1>パスワードリセット実行</h1>";
echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>admin_id</th><th>結果</th></tr>";

foreach ($admin_ids as $admin_id) {
    $sql = "UPDATE mst_admin SET admin_pass = ? WHERE admin_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ss", $new_hash, $admin_id);

    if ($stmt->execute()) {
        $affected = $stmt->affected_rows;
        if ($affected > 0) {
            echo "<tr><td>" . htmlspecialchars($admin_id) . "</td><td style='color:green;'>SUCCESS - パスワードを admin123 に更新</td></tr>";
        } else {
            echo "<tr><td>" . htmlspecialchars($admin_id) . "</td><td style='color:orange;'>NOT FOUND - ユーザーが存在しない</td></tr>";
        }
    } else {
        echo "<tr><td>" . htmlspecialchars($admin_id) . "</td><td style='color:red;'>ERROR - " . htmlspecialchars($stmt->error) . "</td></tr>";
    }
    $stmt->close();
}

echo "</table>";

echo "<h2>リセット完了</h2>";
echo "<p>以下のID/パスワードでログインできます:</p>";
echo "<ul>";
echo "<li><strong>admin</strong> / admin123</li>";
echo "<li><strong>sradmin</strong> / admin123</li>";
echo "<li><strong>spadmin</strong> / admin123</li>";
echo "</ul>";

echo "<p><a href='login.php' style='font-size:20px;'>→ ログイン画面へ</a></p>";

echo "<hr>";
echo "<p style='color:red;font-weight:bold;'>セキュリティ警告: このファイルは使用後すぐに削除してください！</p>";

$mysqli->close();
?>
