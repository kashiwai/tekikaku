<?php
/**
 * mst_convertPoint テーブルのスキーマとデータ確認
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 mst_convertPoint テーブル確認</h1>";
echo "<hr>";

$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p>✅ データベース接続成功: $db_host / $db_name</p>";
    echo "<hr>";

    // テーブル構造確認
    echo "<h2>📋 mst_convertPoint テーブル構造</h2>";

    $columns = $pdo->query("SHOW COLUMNS FROM mst_convertPoint")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL許可</th><th>Key</th><th>デフォルト値</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><b>{$col['Field']}</b></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // データ確認
    echo "<hr>";
    echo "<h2>📊 mst_convertPoint データ確認</h2>";

    $data = $pdo->query("SELECT * FROM mst_convertPoint")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($data)) {
        echo "<p style='color:red;'><b>❌ データが0件です</b></p>";

        // サンプルデータ登録
        echo "<h3>💡 サンプルデータを登録します</h3>";

        $insert_sql = "
            INSERT INTO mst_convertPoint (convert_no, point, del_flg, add_no, add_dt)
            VALUES (1, 1, 0, 1, NOW())
        ";

        try {
            $pdo->exec($insert_sql);
            echo "<p>✅ サンプルデータを登録しました（convert_no=1, point=1）</p>";

            // 再取得
            $data = $pdo->query("SELECT * FROM mst_convertPoint")->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            echo "<p style='color:red;'>❌ 登録エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    if (!empty($data)) {
        echo "<p><b>登録件数:</b> " . count($data) . "件</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        foreach (array_keys($data[0]) as $key) {
            echo "<th>{$key}</th>";
        }
        echo "</tr>";

        foreach ($data as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    }

    echo "<hr>";
    echo "<h2>🔗 次のステップ</h2>";
    echo "<ol>";
    echo "<li><a href='/debug_index_sql.php'>debug_index_sql.php を再実行</a></li>";
    echo "<li><a href='/'>トップページ</a>を確認</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
