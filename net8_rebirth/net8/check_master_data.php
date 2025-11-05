<?php
/**
 * マスタテーブルデータ確認スクリプト
 */

// データベース接続情報を環境変数から取得
$dbHost = getenv('DB_HOST') ?: 'autorack.proxy.rlwy.net';
$dbPort = getenv('DB_PORT') ?: '20815';
$dbName = getenv('DB_DATABASE') ?: 'railway';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASSWORD') ?: 'BbhJjOdBWMNPNvZvBcFrTFiPTyajvPGp';

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
    } else {
        echo "❌ データなし\n";
    }
    echo "\n";

    // mst_camera テーブル
    echo "=== mst_camera (カメラマスタ) ===\n";
    $stmt = $pdo->query("SELECT camera_no, del_flg FROM mst_camera WHERE del_flg != 1 ORDER BY camera_no LIMIT 10");
    $cameras = $stmt->fetchAll();
    if (count($cameras) > 0) {
        echo "登録件数: " . count($cameras) . " 件（最初の10件表示）\n";
        foreach ($cameras as $row) {
            echo "Camera No: {$row['camera_no']}\n";
        }
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
    } else {
        echo "❌ データなし\n";
    }
    echo "\n";

    // Signaling Servers
    echo "=== RTC_Signaling_Servers (設定ファイル) ===\n";
    $configFile = __DIR__ . '/02.ソースファイル/net8_html/_etc/config_database.php';
    if (file_exists($configFile)) {
        require_once($configFile);
        if (isset($RTC_Signaling_Servers)) {
            foreach ($RTC_Signaling_Servers as $key => $val) {
                echo "Signaling ID: {$key}\n";
            }
        } else {
            echo "❌ RTC_Signaling_Servers 未定義\n";
        }
    } else {
        echo "❌ config_database.php が見つかりません\n";
    }

} catch (PDOException $e) {
    echo "❌ データベース接続エラー: " . $e->getMessage() . "\n";
    echo "接続情報: {$dbHost}:{$dbPort} / {$dbName}\n";
}
