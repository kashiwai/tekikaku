<?php
/**
 * MAC Address Registration Script
 *
 * Windows PC slotserver.exeとkeysocket.exe用のMACアドレス登録
 *
 * 警告: 実行後は必ずこのファイルを削除してください。
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
    <title>MAC Address Registration - Net8</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
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
        <h1>🔐 MAC Address Registration - Net8 Project</h1>

        <div class="warning">
            <strong>⚠️ 警告:</strong> このスクリプトはMACアドレスをデータベースに登録します。
            実行完了後は必ずこのファイルを削除してください。
        </div>

<?php

// 接続情報の取得（$_SERVER → $_ENV → getenv() の優先順位）
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'localhost';
$db_port = $_SERVER['DB_PORT'] ?? $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: '3306';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_pass = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

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
    echo "<p>以下のMACアドレスを登録します：</p>";
    echo "<div class='code'>";
    echo "1. <span class='success'>34-a6-ef-35-73-73</span> (slotserver.exe用)<br>";
    echo "2. <span class='success'>de-2e-80-43-28-b3</span> (keysocket.exe用)<br>";
    echo "</div>";
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

    // MACアドレス登録
    echo "<h2>🔐 MACアドレス登録中...</h2>";

    $license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=';

    $mac_addresses = [
        ['mac' => '34-a6-ef-35-73-73', 'camera_no' => 1, 'name' => 'slotserver.exe'],
        ['mac' => 'de-2e-80-43-28-b3', 'camera_no' => 2, 'name' => 'keysocket.exe']
    ];

    echo "<div class='code'>";
    foreach ($mac_addresses as $data) {
        try {
            // 既存チェック
            $stmt = $pdo->prepare("SELECT mac_address FROM mst_cameralist WHERE mac_address = ?");
            $stmt->execute([$data['mac']]);
            $existing = $stmt->fetch();

            if ($existing) {
                echo "<span style='color:#dcdcaa'>⚠</span> 既に登録済み: {$data['mac']} ({$data['name']})<br>";
            } else {
                // INSERT
                $stmt = $pdo->prepare("
                    INSERT INTO mst_cameralist (
                        mac_address,
                        state,
                        camera_no,
                        license_id,
                        add_dt,
                        del_flg
                    ) VALUES (?, 1, ?, ?, NOW(), 0)
                ");
                $stmt->execute([$data['mac'], $data['camera_no'], $license_id]);
                echo "<span class='success'>✓</span> 登録成功: {$data['mac']} ({$data['name']})<br>";
                flush();
            }
        } catch (PDOException $e) {
            echo "<span class='error'>✗</span> エラー: {$data['mac']} - " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
    echo "</div>";

    // 登録結果確認
    echo "<h2>📋 登録されたMACアドレス一覧</h2>";

    $stmt = $pdo->query("SELECT mac_address, camera_no, state, add_dt FROM mst_cameralist ORDER BY camera_no");
    $rows = $stmt->fetchAll();

    if (count($rows) > 0) {
        echo "<div class='code'>";
        foreach ($rows as $row) {
            $state = $row['state'] == 1 ? '有効' : '無効';
            echo "<span class='success'>{$row['mac_address']}</span> - カメラ番号: {$row['camera_no']} - 状態: {$state} - 登録日時: {$row['add_dt']}<br>";
        }
        echo "</div>";
    }

    echo "<div class='success'>";
    echo "<h2>🎉 登録完了！</h2>";
    echo "<p>合計 <strong>" . count($mac_addresses) . "</strong> 件のMACアドレスを処理しました。</p>";
    echo "</div>";

    // API接続テスト
    echo "<h2>🧪 API接続テスト</h2>";
    echo "<div class='code'>";

    foreach ($mac_addresses as $data) {
        $url = "https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC={$data['mac']}";
        echo "<strong>テスト:</strong> {$data['name']} ({$data['mac']})<br>";
        echo "URL: <a href='{$url}' target='_blank' style='color:#569cd6'>{$url}</a><br>";

        // cURL でテスト
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code == 200) {
            $json = json_decode($response, true);
            if ($json && isset($json['status']) && $json['status'] == 'ok') {
                echo "<span class='success'>✓ API接続成功！</span><br>";
            } else {
                echo "<span style='color:#dcdcaa'>⚠ API応答: " . htmlspecialchars($response) . "</span><br>";
            }
        } else {
            echo "<span class='error'>✗ API接続失敗 (HTTP {$http_code})</span><br>";
        }
        echo "<br>";
    }
    echo "</div>";

    echo "<div class='warning'>";
    echo "<h3>⚠️ 次のステップ</h3>";
    echo "<ol>";
    echo "<li><strong>Windows PCでslotserver.exeとkeysocket.exeを起動してください</strong></li>";
    echo "<li>プログラムが常駐化し、エラーで落ちないことを確認してください</li>";
    echo "<li><strong>セキュリティのため、このファイル（insert_mac_addresses.php）を削除してください</strong></li>";
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

    if (file_exists(__FILE__)) {
        if (unlink(__FILE__)) {
            echo "<p class='success'>✓ 削除成功: insert_mac_addresses.php</p>";
            echo "<p>削除が完了しました。このページはリロードできません。</p>";
        } else {
            echo "<p class='error'>✗ 削除失敗: insert_mac_addresses.php</p>";
            echo "<p>手動で削除してください。</p>";
        }
    }
}

?>
    </div>
</body>
</html>
