<?php
/**
 * 管理者アカウント確認スクリプト
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once(__DIR__ . '/_etc/setting.php');

echo "<h1>管理者アカウント確認</h1>";

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p>✅ データベース接続成功</p>";

    // 管理者アカウント一覧
    $sql = "SELECT admin_no, admin_id, admin_name, admin_auth, del_flg FROM mst_admin ORDER BY admin_no";
    $stmt = $pdo->query($sql);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>📋 登録されている管理者アカウント</h2>";

    if (empty($admins)) {
        echo "<p>❌ 管理者アカウントが1件も登録されていません</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>No</th><th>ログインID</th><th>管理者名</th><th>権限</th><th>削除フラグ</th><th>状態</th></tr>";

        foreach ($admins as $admin) {
            $status = ($admin['del_flg'] == 0) ? "✅ 有効" : "❌ 削除済み";
            $auth_label = '';
            switch ($admin['admin_auth']) {
                case 0: $auth_label = '一般'; break;
                case 1: $auth_label = '管理者'; break;
                case 9: $auth_label = 'システム管理者'; break;
                default: $auth_label = '不明';
            }

            echo "<tr>";
            echo "<td>" . htmlspecialchars($admin['admin_no']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($admin['admin_id']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($admin['admin_name']) . "</td>";
            echo "<td>" . htmlspecialchars($auth_label) . "</td>";
            echo "<td>" . htmlspecialchars($admin['del_flg']) . "</td>";
            echo "<td>" . htmlspecialchars($status) . "</td>";
            echo "</tr>";
        }

        echo "</table>";

        echo "<h3>💡 ログイン方法</h3>";
        echo "<p>上記のテーブルの「ログインID」列に表示されているIDを使ってログインしてください。</p>";
        echo "<p>パスワードは全て <strong>admin123</strong> です。</p>";

        echo "<h3>🔗 ログインページ</h3>";
        echo "<p><a href='/xxxadmin/login.php'>管理画面ログインページへ</a></p>";
    }

    // テーブル存在確認
    echo "<h2>📊 mst_adminテーブル情報</h2>";
    $table_info = $pdo->query("SHOW CREATE TABLE mst_admin")->fetch(PDO::FETCH_ASSOC);
    echo "<details><summary>テーブル定義を表示</summary>";
    echo "<pre>" . htmlspecialchars($table_info['Create Table']) . "</pre>";
    echo "</details>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
