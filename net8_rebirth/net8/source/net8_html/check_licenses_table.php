<?php
/**
 * licensesテーブルの構造を確認し、license_cdカラムを追加
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 licensesテーブル確認とlicense_cd追加</h1>";
echo "<hr>";

// 環境変数から直接データベース接続情報を取得
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

    echo "<h2>✅ データベース接続成功</h2>";
    echo "<p>Host: $db_host / DB: $db_name</p>";
    echo "<hr>";

    // データベース内のすべてのテーブルを表示
    echo "<h2>📋 データベース内のテーブル一覧</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        $highlighted = (stripos($table, 'license') !== false) ? " style='color:red; font-weight:bold;'" : "";
        echo "<li{$highlighted}>{$table}</li>";
    }
    echo "</ul>";
    echo "<hr>";

    // licensesテーブルの存在確認
    $license_tables = array_filter($tables, function($table) {
        return stripos($table, 'license') !== false;
    });

    if (empty($license_tables)) {
        echo "<h2>⚠️ licensesテーブルが見つかりません</h2>";
        echo "<p>slotserver.exeが使用するlicensesテーブルを作成します。</p>";

        // licensesテーブルを作成
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS licenses (
                license_id INT PRIMARY KEY AUTO_INCREMENT,
                license_cd VARCHAR(255) NOT NULL,
                domain VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            )
        ");

        echo "<p>✅ licensesテーブルを作成しました</p>";

        // slotserver.iniのライセンス情報を登録
        $license_cd = '6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c';
        $domain = 'mgg-webservice-production.up.railway.app';

        $pdo->exec("
            INSERT INTO licenses (license_cd, domain) VALUES ('$license_cd', '$domain')
            ON DUPLICATE KEY UPDATE domain='$domain'
        ");

        echo "<p>✅ ライセンス情報を登録しました</p>";
        echo "<p>license_cd: $license_cd</p>";
        echo "<p>domain: $domain</p>";

    } else {
        echo "<h2>✅ licensesテーブルが見つかりました</h2>";

        foreach ($license_tables as $table) {
            echo "<h3>テーブル名: {$table}</h3>";

            // テーブル構造を表示
            $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th></tr>";

            $has_license_cd = false;
            foreach ($columns as $col) {
                echo "<tr>";
                echo "<td>{$col['Field']}</td>";
                echo "<td>{$col['Type']}</td>";
                echo "<td>{$col['Null']}</td>";
                echo "<td>{$col['Key']}</td>";
                echo "<td>{$col['Default']}</td>";
                echo "</tr>";

                if ($col['Field'] === 'license_cd') {
                    $has_license_cd = true;
                }
            }
            echo "</table>";

            echo "<hr>";

            // license_cdカラムが無い場合は追加
            if (!$has_license_cd) {
                echo "<h3>⚠️ license_cdカラムが存在しません</h3>";
                echo "<p>license_cdカラムを追加します...</p>";

                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN license_cd VARCHAR(255) DEFAULT ''");

                echo "<p>✅ license_cdカラムを追加しました</p>";

                // 更新後のテーブル構造を表示
                echo "<h3>更新後のテーブル構造:</h3>";
                $columns_new = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>カラム名</th><th>型</th><th>NULL</th><th>キー</th><th>デフォルト</th></tr>";
                foreach ($columns_new as $col) {
                    $style = ($col['Field'] === 'license_cd') ? " style='background-color: #90EE90;'" : "";
                    echo "<tr{$style}>";
                    echo "<td>{$col['Field']}</td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "<td>{$col['Null']}</td>";
                    echo "<td>{$col['Key']}</td>";
                    echo "<td>{$col['Default']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>✅ license_cdカラムは既に存在します</p>";
            }

            // テーブルのデータ件数を表示
            $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            echo "<p>登録件数: {$count}件</p>";

            // データの中身を表示（最大5件）
            if ($count > 0) {
                echo "<h3>データ内容（最大5件）:</h3>";
                $data = $pdo->query("SELECT * FROM `{$table}` LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                if (!empty($data)) {
                    echo "<tr>";
                    foreach (array_keys($data[0]) as $key) {
                        echo "<th>{$key}</th>";
                    }
                    echo "</tr>";

                    foreach ($data as $row) {
                        echo "<tr>";
                        foreach ($row as $value) {
                            echo "<td>" . htmlspecialchars($value) . "</td>";
                        }
                        echo "</tr>";
                    }
                }
                echo "</table>";
            }
        }
    }

    echo "<hr>";
    echo "<h2>🎉 完了</h2>";
    echo "<p>Windows側のslotserver.exeを再起動してください。</p>";
    echo "<p>これでlicense_cdカラムのエラーが解消されるはずです。</p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
