<?php
/**
 * Database Setup Script
 *
 * このスクリプトはRailway MySQLに全テーブルを作成します。
 *
 * 警告: セキュリティ上、初回セットアップ後は削除してください。
 */

header('Content-Type: text/html; charset=UTF-8');

// エラー表示（デバッグ用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Net8</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
        }
        h2 {
            color: #569cd6;
            margin-top: 30px;
        }
        .success {
            color: #4ec9b0;
        }
        .error {
            color: #f48771;
            background: #3a1a1a;
            padding: 10px;
            border-left: 4px solid #f48771;
            margin: 10px 0;
        }
        .info {
            color: #ce9178;
            background: #2a2a1a;
            padding: 10px;
            border-left: 4px solid #ce9178;
            margin: 10px 0;
        }
        .code {
            background: #252526;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin: 10px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #3e3e42;
        }
        th {
            background: #252526;
            color: #4ec9b0;
        }
        .warning {
            background: #3a2a1a;
            padding: 15px;
            border-left: 4px solid #dcdcaa;
            margin: 20px 0;
            color: #dcdcaa;
        }
        .button {
            background: #0e639c;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px 10px 0;
        }
        .button:hover {
            background: #1177bb;
        }
        .button.danger {
            background: #c5303b;
        }
        .button.danger:hover {
            background: #d94854;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🗄️ Database Setup - Net8 Project</h1>

        <div class="warning">
            <strong>⚠️ 警告:</strong> このスクリプトはデータベースの全テーブルを作成/再作成します。
            既存のデータは削除される可能性があります。セットアップ完了後は必ずこのファイルを削除してください。
        </div>

<?php

// 接続情報の取得（$_SERVER → $_ENV → getenv() の優先順位）
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_port = $_SERVER['DB_PORT'] ?? $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_pass = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

echo "<h2>📊 接続情報</h2>";
echo "<div class='code'>";
echo "Host: <span class='success'>{$db_host}</span><br>";
echo "Port: <span class='success'>{$db_port}</span><br>";
echo "Database: <span class='success'>{$db_name}</span><br>";
echo "User: <span class='success'>{$db_user}</span><br>";
echo "Password: <span class='success'>" . str_repeat('*', min(strlen($db_pass), 20)) . "</span>";
echo "</div>";

// 実行確認
if (!isset($_POST['confirm'])) {
    echo "<h2>🚀 実行確認</h2>";
    echo "<form method='post'>";
    echo "<p>以下のスクリプトを実行します：</p>";
    echo "<ul>";
    echo "<li>01_create.sql - 全テーブル作成（約65テーブル）</li>";
    echo "<li>02_init.sql - 初期データ挿入</li>";
    echo "</ul>";
    echo "<button type='submit' name='confirm' value='yes' class='button'>✅ 実行する</button>";
    echo "<button type='button' onclick='window.history.back()' class='button'>❌ キャンセル</button>";
    echo "</form>";
    echo "</div></body></html>";
    exit;
}

// MySQL接続
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    echo "<h2>✅ データベース接続成功</h2>";

    // 01_create.sql の実行
    echo "<h2>🔨 テーブル作成中...</h2>";

    $create_sql_file = __DIR__ . '/01_create.sql';
    if (!file_exists($create_sql_file)) {
        throw new Exception("01_create.sql が見つかりません: {$create_sql_file}");
    }

    $create_sql = file_get_contents($create_sql_file);

    // SQL文を分割して実行（セミコロン区切り、コメント除外）
    $statements = preg_split('/;\\s*$/m', $create_sql);
    $executed_count = 0;
    $errors = [];

    echo "<div class='code'>";
    foreach ($statements as $statement) {
        $statement = trim($statement);

        // 空文や SQLコメント行をスキップ
        if (empty($statement) || preg_match('/^(--|\/\*|\/\/|#)/', $statement)) {
            continue;
        }

        // MySQLコマンド（/*!...*/）はスキップ
        if (preg_match('/^\/\*!.*\*\/$/', $statement)) {
            continue;
        }

        try {
            $pdo->exec($statement);
            $executed_count++;

            // CREATE TABLE文の場合、テーブル名を表示
            if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "<span class='success'>✓</span> テーブル作成: {$matches[1]}<br>";
                flush();
            }
        } catch (PDOException $e) {
            // テーブルが既に存在する場合は警告として扱う
            if (strpos($e->getMessage(), 'already exists') !== false) {
                if (preg_match('/CREATE TABLE\s+`?(\w+)`?/i', $statement, $matches)) {
                    echo "<span style='color:#dcdcaa'>⚠</span> テーブル既存: {$matches[1]}<br>";
                }
            } else {
                $errors[] = $e->getMessage();
            }
        }
    }
    echo "</div>";

    echo "<div class='info'>";
    echo "実行したSQL文: {$executed_count} 個";
    if (count($errors) > 0) {
        echo "<br>エラー: " . count($errors) . " 個";
    }
    echo "</div>";

    if (count($errors) > 0) {
        echo "<h3>❌ エラー詳細</h3>";
        echo "<div class='error'>";
        foreach ($errors as $error) {
            echo htmlspecialchars($error) . "<br>";
        }
        echo "</div>";
    }

    // 02_init.sql の実行
    echo "<h2>📝 初期データ挿入中...</h2>";

    $init_sql_file = __DIR__ . '/02_init.sql';
    if (file_exists($init_sql_file)) {
        $init_sql = file_get_contents($init_sql_file);
        $statements = preg_split('/;\\s*$/m', $init_sql);
        $inserted_count = 0;

        echo "<div class='code'>";
        foreach ($statements as $statement) {
            $statement = trim($statement);

            if (empty($statement) || preg_match('/^(--|\/\*|\/\/|#)/', $statement)) {
                continue;
            }

            if (preg_match('/^\/\*!.*\*\/$/', $statement)) {
                continue;
            }

            try {
                $pdo->exec($statement);
                $inserted_count++;

                if (preg_match('/INSERT INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                    echo "<span class='success'>✓</span> データ挿入: {$matches[1]}<br>";
                    flush();
                }
            } catch (PDOException $e) {
                // 重複キーエラーは無視
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<span class='error'>✗</span> エラー: " . htmlspecialchars($e->getMessage()) . "<br>";
                }
            }
        }
        echo "</div>";

        echo "<div class='info'>挿入したデータ: {$inserted_count} 個</div>";
    } else {
        echo "<div class='info'>02_init.sql が見つかりません（スキップ）</div>";
    }

    // 作成されたテーブル一覧を表示
    echo "<h2>📋 作成されたテーブル一覧</h2>";

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo "<table>";
    echo "<thead><tr><th>No.</th><th>テーブル名</th><th>レコード数</th></tr></thead>";
    echo "<tbody>";

    foreach ($tables as $index => $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo "<tr>";
        echo "<td>" . ($index + 1) . "</td>";
        echo "<td><span class='success'>{$table}</span></td>";
        echo "<td>{$count} records</td>";
        echo "</tr>";
    }

    echo "</tbody></table>";

    echo "<div class='success'>";
    echo "<h2>🎉 セットアップ完了！</h2>";
    echo "<p>合計 <strong>" . count($tables) . "</strong> 個のテーブルが作成されました。</p>";
    echo "</div>";

    echo "<div class='warning'>";
    echo "<h3>⚠️ 次のステップ</h3>";
    echo "<ol>";
    echo "<li>アプリケーションが正常に動作するか確認してください</li>";
    echo "<li><strong>セキュリティのため、このファイル（setup_database.php）を削除してください</strong></li>";
    echo "<li>01_create.sql と 02_init.sql も削除することを推奨します</li>";
    echo "</ol>";
    echo "<form method='post' action='?delete=1'>";
    echo "<button type='submit' class='button danger'>🗑️ このファイルを削除する</button>";
    echo "</form>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h2>❌ データベース接続エラー</h2>";
    echo "<p><strong>エラー:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h2>❌ エラー</h2>";
    echo "<p><strong>エラー:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

// ファイル削除機能
if (isset($_GET['delete']) && $_GET['delete'] == '1' && isset($_POST)) {
    echo "<h2>🗑️ ファイル削除</h2>";

    $files_to_delete = [
        __FILE__,
        __DIR__ . '/01_create.sql',
        __DIR__ . '/02_init.sql'
    ];

    foreach ($files_to_delete as $file) {
        if (file_exists($file)) {
            if (unlink($file)) {
                echo "<p class='success'>✓ 削除成功: " . basename($file) . "</p>";
            } else {
                echo "<p class='error'>✗ 削除失敗: " . basename($file) . "</p>";
            }
        }
    }

    echo "<p>削除が完了しました。このページはリロードできません。</p>";
}

?>
    </div>
</body>
</html>
