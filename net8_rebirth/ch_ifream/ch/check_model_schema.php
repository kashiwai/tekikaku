<?php
/**
 * mst_modelテーブルのスキーマ確認
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 mst_model テーブルスキーマ確認</h1>";
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

    echo "<p>✅ データベース接続成功</p>";
    echo "<p>Host: $db_host / DB: $db_name</p>";
    echo "<hr>";

    // テーブル構造確認
    echo "<h2>📋 mst_model テーブル構造</h2>";

    $columns = $pdo->query("SHOW COLUMNS FROM mst_model")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0' style='font-size:12px;'>";
    echo "<tr><th>カラム名</th><th>型</th><th>NULL許可</th><th>Key</th><th>デフォルト値</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td><b>{$col['Field']}</b></td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>" . ($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . ($col['Extra'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";

    // 画像関連のカラムを探す
    echo "<hr>";
    echo "<h2>🔍 画像関連カラムの検索</h2>";

    $image_columns = array_filter($columns, function($col) {
        return stripos($col['Field'], 'img') !== false ||
               stripos($col['Field'], 'image') !== false ||
               stripos($col['Field'], 'pic') !== false ||
               stripos($col['Field'], 'photo') !== false;
    });

    if (!empty($image_columns)) {
        echo "<p>✅ 画像関連のカラムが見つかりました：</p>";
        echo "<ul>";
        foreach ($image_columns as $col) {
            echo "<li><b>{$col['Field']}</b> ({$col['Type']})</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:red;'>❌ 画像関連のカラムが見つかりませんでした</p>";
        echo "<p>カラムを追加する必要があります。</p>";
    }

    // 現在のデータ確認
    echo "<hr>";
    echo "<h2>📊 mst_model データ確認</h2>";

    $data = $pdo->query("SELECT * FROM mst_model WHERE del_flg = 0")->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($data)) {
        echo "<p><b>登録件数:</b> " . count($data) . "件</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0' style='font-size:11px; overflow:auto;'>";
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
    } else {
        echo "<p style='color:red;'>データが0件です</p>";
    }

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
