<?php
header('Content-Type: text/plain; charset=utf-8');
set_time_limit(300); // 5分

echo "=== MySQL ダンプインポート ===\n\n";

// 環境変数取得（Railway対応）
$host = getenv('MYSQLHOST') ?: 'mysql.railway.internal';
$port = getenv('MYSQLPORT') ?: '3306';
$user = getenv('MYSQLUSER') ?: 'root';
$password = getenv('MYSQL_ROOT_PASSWORD') ?: getenv('MYSQLPASSWORD') ?: '';
$database = getenv('MYSQL_DATABASE') ?: getenv('MYSQLDATABASE') ?: 'net8_dev';

echo "接続情報:\n";
echo "  Host: $host\n";
echo "  Port: $port\n";
echo "  User: $user\n";
echo "  Database: $database\n\n";

// ダンプファイルのアップロードを受け付ける
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dumpfile'])) {
    $uploadedFile = $_FILES['dumpfile']['tmp_name'];

    if (!file_exists($uploadedFile)) {
        echo "❌ アップロードされたファイルが見つかりません\n";
        exit(1);
    }

    echo "📁 ファイル受信: " . $_FILES['dumpfile']['name'] . " (" . number_format($_FILES['dumpfile']['size']) . " bytes)\n\n";

    try {
        // MySQL接続
        $dsn = "mysql:host=$host;port=$port;charset=utf8";
        $pdo = new PDO($dsn, $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        echo "✅ MySQL接続成功\n\n";

        // データベース作成（存在しない場合）
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$database` DEFAULT CHARACTER SET utf8");
        echo "✅ データベース作成/確認完了: $database\n";

        // データベース選択
        $pdo->exec("USE `$database`");
        echo "✅ データベース選択完了\n\n";

        // ダンプファイルを読み込んで実行
        echo "📥 ダンプファイルをインポート中...\n";

        $sql = file_get_contents($uploadedFile);

        // SQLをセミコロンで分割して実行
        $statements = preg_split('/;\s*$/m', $sql);
        $count = 0;
        $errors = 0;

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (empty($statement) || strpos($statement, '--') === 0) {
                continue;
            }

            try {
                $pdo->exec($statement);
                $count++;
                if ($count % 50 == 0) {
                    echo "  処理済み: $count ステートメント\n";
                }
            } catch (PDOException $e) {
                $errors++;
                if ($errors <= 10) {
                    echo "  ⚠️  エラー (スキップ): " . $e->getMessage() . "\n";
                }
            }
        }

        echo "\n✅ インポート完了！\n";
        echo "   処理済みステートメント: $count\n";
        echo "   エラー: $errors\n\n";

        // テーブル一覧表示
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "📋 インポートされたテーブル (" . count($tables) . "個):\n";
        foreach ($tables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo "   - $table ($count 行)\n";
        }

    } catch (PDOException $e) {
        echo "❌ エラー: " . $e->getMessage() . "\n";
        exit(1);
    }

} else {
    echo "ダンプファイルをアップロードしてください。\n\n";
    echo "使用方法:\n";
    echo "  curl -X POST -F 'dumpfile=@/tmp/net8_db_dump.sql' https://dockerfileweb-production.up.railway.app/import_dump.php\n";
}
