<?php
/**
 * カメラマスター登録スクリプト
 *
 * mst_cameraテーブルにカメラを登録します
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>📹 カメラマスター登録</h1>";
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

    // 登録するカメラ情報
    $cameras = [
        [
            'camera_no' => 1,
            'camera_mac' => '00:00:00:00:00:01',
            'camera_name' => 'HOKUTO_CAMERA_1',
        ],
        [
            'camera_no' => 2,
            'camera_mac' => '00:00:00:00:00:02',
            'camera_name' => 'HOKUTO_CAMERA_2',
        ],
        [
            'camera_no' => 3,
            'camera_mac' => '00:00:00:00:00:03',
            'camera_name' => 'HOKUTO_CAMERA_3',
        ],
    ];

    echo "<h2>📹 カメラマスター登録</h2>";

    $registered_count = 0;
    $updated_count = 0;

    foreach ($cameras as $camera) {
        // 既存チェック
        $check = $pdo->prepare("SELECT camera_no FROM mst_camera WHERE camera_no = :camera_no");
        $check->execute(['camera_no' => $camera['camera_no']]);
        $exists = $check->fetch();

        if ($exists) {
            // 既存データを更新
            $stmt = $pdo->prepare("
                UPDATE mst_camera SET
                    camera_mac = :camera_mac,
                    camera_name = :camera_name,
                    del_flg = 0,
                    upd_no = 1,
                    upd_dt = NOW()
                WHERE camera_no = :camera_no
            ");
            $stmt->execute([
                'camera_no' => $camera['camera_no'],
                'camera_mac' => $camera['camera_mac'],
                'camera_name' => $camera['camera_name']
            ]);
            echo "<p>⚠️ カメラNo.{$camera['camera_no']} は既に登録済み → 更新しました</p>";
            $updated_count++;
        } else {
            // 新規登録
            $stmt = $pdo->prepare("
                INSERT INTO mst_camera (
                    camera_no,
                    camera_mac,
                    camera_name,
                    del_flg,
                    add_no,
                    add_dt
                ) VALUES (
                    :camera_no,
                    :camera_mac,
                    :camera_name,
                    0,
                    1,
                    NOW()
                )
            ");
            $stmt->execute([
                'camera_no' => $camera['camera_no'],
                'camera_mac' => $camera['camera_mac'],
                'camera_name' => $camera['camera_name']
            ]);
            echo "<p>✅ カメラNo.{$camera['camera_no']} 登録完了（名前: {$camera['camera_name']}, MAC: {$camera['camera_mac']}）</p>";
            $registered_count++;
        }
    }

    echo "<hr>";
    echo "<h2>🎉 登録完了</h2>";
    echo "<p>新規登録: {$registered_count}件</p>";
    echo "<p>更新: {$updated_count}件</p>";

    // 登録確認
    $total = $pdo->query("SELECT COUNT(*) FROM mst_camera WHERE del_flg = 0")->fetchColumn();
    echo "<p>カメラマスター総数: {$total}件</p>";

    echo "<hr>";
    echo "<h2>📋 登録されたカメラ一覧</h2>";
    $camera_list = $pdo->query("
        SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE del_flg = 0
        ORDER BY camera_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>カメラNo</th><th>MACアドレス</th><th>カメラ名</th><th>削除フラグ</th></tr>";
    foreach ($camera_list as $c) {
        echo "<tr>";
        echo "<td>{$c['camera_no']}</td>";
        echo "<td>{$c['camera_mac']}</td>";
        echo "<td>{$c['camera_name']}</td>";
        echo "<td>{$c['del_flg']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h2>🔗 次のステップ</h2>";
    echo "<ol>";
    echo "<li><a href='/check_machine_status.php'>実機状態確認</a>でトップページに表示されるか確認</li>";
    echo "<li><a href='/'>トップページ</a>で北斗の拳が表示されるか確認</li>";
    echo "<li><a href='/xxxadmin/search.php'>実機管理画面</a>で台の詳細を確認</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
