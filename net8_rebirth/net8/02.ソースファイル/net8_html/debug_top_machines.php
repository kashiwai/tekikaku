<?php
/**
 * トップページ台表示デバッグ
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 トップページ台表示デバッグ</h1>";
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

    // 1. dat_machine テーブルのデータ確認
    echo "<h2>📋 STEP 1: dat_machine テーブル確認</h2>";
    $machines = $pdo->query("
        SELECT
            machine_no,
            model_no,
            machine_cd,
            machine_corner,
            machine_status,
            del_flg,
            release_date
        FROM dat_machine
        WHERE del_flg = 0
        ORDER BY machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($machines)) {
        echo "<p>❌ dat_machineにデータがありません</p>";
    } else {
        echo "<p>✅ dat_machineに{$count = count($machines)}台登録されています</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>機種No</th><th>台CD</th><th>corner</th><th>status</th><th>del_flg</th><th>公開日</th></tr>";
        foreach ($machines as $m) {
            echo "<tr>";
            echo "<td>{$m['machine_no']}</td>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td>{$m['machine_cd']}</td>";
            echo "<td>" . ($m['machine_corner'] ?: 'NULL') . "</td>";
            echo "<td>{$m['machine_status']}</td>";
            echo "<td>{$m['del_flg']}</td>";
            echo "<td>{$m['release_date']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "<hr>";

    // 2. mst_model テーブル確認
    echo "<h2>📋 STEP 2: mst_model テーブル確認</h2>";
    $models = $pdo->query("
        SELECT model_no, model_cd, model_name, image_list, del_flg
        FROM mst_model
        WHERE del_flg = 0
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<p>✅ mst_modelに{$count = count($models)}機種登録されています</p>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>機種No</th><th>機種CD</th><th>機種名</th><th>画像</th></tr>";
    foreach ($models as $m) {
        echo "<tr>";
        echo "<td>{$m['model_no']}</td>";
        echo "<td>{$m['model_cd']}</td>";
        echo "<td>{$m['model_name']}</td>";
        echo "<td>" . ($m['image_list'] ?: 'なし') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<hr>";

    // 3. mst_corner テーブル確認
    echo "<h2>📋 STEP 3: mst_corner テーブル確認</h2>";
    $corners = $pdo->query("
        SELECT corner_no, corner_name, corner_roman, del_flg
        FROM mst_corner
        WHERE del_flg = 0
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($corners)) {
        echo "<p>❌ mst_cornerにデータがありません</p>";
        echo "<p>⚠️ これが原因でトップページに台が表示されない可能性があります</p>";
    } else {
        echo "<p>✅ mst_cornerに{$count = count($corners)}コーナー登録されています</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>コーナーNo</th><th>コーナー名</th><th>ローマ字</th></tr>";
        foreach ($corners as $c) {
            echo "<tr>";
            echo "<td>{$c['corner_no']}</td>";
            echo "<td>{$c['corner_name']}</td>";
            echo "<td>{$c['corner_roman']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "<hr>";

    // 4. JOIN クエリで実際にトップページで取得されるデータを確認
    echo "<h2>📋 STEP 4: トップページSQLシミュレーション</h2>";
    $top_machines = $pdo->query("
        SELECT
            dm.machine_no,
            dm.machine_cd,
            dm.machine_corner,
            dm.machine_status,
            mm.model_name,
            mm.image_list
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        WHERE dm.del_flg = 0
          AND mm.del_flg = 0
          AND dm.machine_status = 1
        ORDER BY dm.release_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($top_machines)) {
        echo "<p>❌ トップページで表示される台が0台です</p>";
        echo "<p>原因: machine_status=1 かつ del_flg=0 の条件に該当する台がありません</p>";
    } else {
        echo "<p>✅ トップページで表示される台: {$count = count($top_machines)}台</p>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>台CD</th><th>corner</th><th>status</th><th>機種名</th><th>画像</th></tr>";
        foreach ($top_machines as $m) {
            echo "<tr>";
            echo "<td>{$m['machine_no']}</td>";
            echo "<td>{$m['machine_cd']}</td>";
            echo "<td>" . ($m['machine_corner'] ?: 'NULL') . "</td>";
            echo "<td>{$m['machine_status']}</td>";
            echo "<td>{$m['model_name']}</td>";
            echo "<td>" . ($m['image_list'] ?: 'なし') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "<hr>";

    // 5. FIND_IN_SET テスト
    echo "<h2>📋 STEP 5: FIND_IN_SET() テスト</h2>";
    $test_result = $pdo->query("
        SELECT
            machine_no,
            machine_cd,
            machine_corner,
            FIND_IN_SET('1', machine_corner) as find_result
        FROM dat_machine
        WHERE del_flg = 0
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>台No</th><th>台CD</th><th>machine_corner</th><th>FIND_IN_SET('1', machine_corner)</th></tr>";
    foreach ($test_result as $r) {
        echo "<tr>";
        echo "<td>{$r['machine_no']}</td>";
        echo "<td>{$r['machine_cd']}</td>";
        echo "<td>" . ($r['machine_corner'] ?: 'NULL') . "</td>";
        echo "<td>{$r['find_result']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p>FIND_IN_SET()が0の場合、その台はコーナー絞り込みで表示されません</p>";

    echo "<hr>";
    echo "<h2>🎉 診断完了</h2>";
    echo "<p><a href='/'>トップページに戻る</a></p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
