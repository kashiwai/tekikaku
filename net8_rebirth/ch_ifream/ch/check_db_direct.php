<?php
/**
 * マスタテーブルデータ確認（直接接続版）
 */

header('Content-Type: text/html; charset=UTF-8');

// データベース接続情報を環境変数から取得
$dbHost = getenv('DB_HOST');
$dbPort = getenv('DB_PORT');
$dbName = getenv('DB_DATABASE');
$dbUser = getenv('DB_USER');
$dbPass = getenv('DB_PASSWORD');

echo "<pre>";
echo "=== データベース接続情報 ===\n";
echo "Host: {$dbHost}\n";
echo "Port: {$dbPort}\n";
echo "Database: {$dbName}\n";
echo "User: {$dbUser}\n\n";

try {
    // PDO接続
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ データベース接続成功\n\n";

    // mst_owner テーブル
    echo "=== mst_owner (オーナーマスタ) ===\n";
    $stmt = $pdo->query("SELECT owner_no, owner_nickname, del_flg FROM mst_owner WHERE del_flg != 1 ORDER BY owner_no");
    $owners = $stmt->fetchAll();
    if (count($owners) > 0) {
        foreach ($owners as $row) {
            echo "ID: {$row['owner_no']}, Name: {$row['owner_nickname']}\n";
        }
        echo "✅ " . count($owners) . "件\n";
    } else {
        echo "❌ データなし\n";
    }
    echo "\n";

    // mst_camera テーブル
    echo "=== mst_camera (カメラマスタ) ===\n";
    $stmt = $pdo->query("SELECT camera_no, del_flg FROM mst_camera WHERE del_flg != 1 ORDER BY camera_no LIMIT 10");
    $cameras = $stmt->fetchAll();
    if (count($cameras) > 0) {
        foreach ($cameras as $row) {
            echo "Camera No: {$row['camera_no']}\n";
        }
        echo "✅ " . count($cameras) . "件（最初の10件表示）\n";
    } else {
        echo "❌ データなし\n";
    }
    echo "\n";

    // mst_convertPoint テーブル
    echo "=== mst_convertPoint (変換レートマスタ) ===\n";
    $stmt = $pdo->query("SELECT convert_no, convert_name, del_flg FROM mst_convertPoint WHERE del_flg != 1 ORDER BY convert_no");
    $converts = $stmt->fetchAll();
    if (count($converts) > 0) {
        foreach ($converts as $row) {
            echo "ID: {$row['convert_no']}, Name: {$row['convert_name']}\n";
        }
        echo "✅ " . count($converts) . "件\n";
    } else {
        echo "❌ データなし\n";
    }
    echo "\n";

    // テーブル一覧
    echo "=== データベーステーブル一覧 ===\n";
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- {$table}\n";
    }

} catch (PDOException $e) {
    echo "❌ データベース接続エラー: " . $e->getMessage() . "\n";
}

echo "</pre>";
