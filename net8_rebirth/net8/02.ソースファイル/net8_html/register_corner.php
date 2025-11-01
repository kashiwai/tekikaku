<?php
/**
 * コーナーデータ登録
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🏪 コーナーデータ登録</h1>";
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

    // 既存のコーナー数を確認
    $existing_count = $pdo->query("SELECT COUNT(*) FROM mst_corner WHERE del_flg = 0")->fetchColumn();
    echo "<h2>📊 現在のコーナー数: {$existing_count}個</h2>";

    // デフォルトコーナーデータ
    $corners = [
        ['スロットコーナー', 'SLOT_CORNER', 1],
        ['パチンココーナー', 'PACHINKO_CORNER', 2],
        ['新台コーナー', 'NEW_MACHINE_CORNER', 3],
    ];

    echo "<h2>🏪 コーナー登録中...</h2>";
    echo "<ul>";

    $stmt = $pdo->prepare("
        INSERT INTO mst_corner (corner_name, corner_roman, sort_no, del_flg, add_no, add_dt)
        VALUES (:corner_name, :corner_roman, :sort_no, 0, 1, NOW())
        ON DUPLICATE KEY UPDATE corner_name = VALUES(corner_name), upd_no = 1, upd_dt = NOW()
    ");

    $inserted = 0;
    foreach ($corners as $corner) {
        // 既存チェック
        $check_stmt = $pdo->prepare("SELECT corner_no FROM mst_corner WHERE corner_roman = :corner_roman");
        $check_stmt->execute(['corner_roman' => $corner[1]]);
        $exists = $check_stmt->fetch();

        if ($exists) {
            echo "<li>⚠️ {$corner[0]} ({$corner[1]}) は既に登録済み（コーナーNo: {$exists['corner_no']}）</li>";
        } else {
            $stmt->execute([
                'corner_name' => $corner[0],
                'corner_roman' => $corner[1],
                'sort_no' => $corner[2]
            ]);
            $inserted++;
            echo "<li>✅ {$corner[0]} ({$corner[1]})</li>";
        }
    }
    echo "</ul>";

    // 最終的なコーナー数
    $final_count = $pdo->query("SELECT COUNT(*) FROM mst_corner WHERE del_flg = 0")->fetchColumn();

    echo "<hr>";
    echo "<h2>🎉 完了</h2>";
    echo "<p>新規追加: {$inserted}個</p>";
    echo "<p>合計コーナー数: {$final_count}個</p>";

    // 全コーナーリストを表示
    echo "<hr>";
    echo "<h3>📋 登録済みコーナー一覧</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>コーナーNo</th><th>コーナー名</th><th>ローマ字</th><th>並び順</th></tr>";

    $corners_list = $pdo->query("
        SELECT corner_no, corner_name, corner_roman, sort_no
        FROM mst_corner
        WHERE del_flg = 0
        ORDER BY sort_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($corners_list as $corner) {
        echo "<tr>";
        echo "<td>{$corner['corner_no']}</td>";
        echo "<td>{$corner['corner_name']}</td>";
        echo "<td>{$corner['corner_roman']}</td>";
        echo "<td>{$corner['sort_no']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h3>🔗 次のステップ</h3>";
    echo "<p><a href='/debug_top_machines.php'>デバッグツールで確認</a></p>";
    echo "<p><a href='/'>トップページで表示確認</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
