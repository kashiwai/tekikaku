<?php
/**
 * 実機完全登録スクリプト
 *
 * 北斗の拳の実機（台）を関連テーブル含めて完全登録します
 * - dat_machine (実機マスタ)
 * - dat_machinePlay (実機プレイデータ)
 * - lnk_machine (実機接続状況)
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🎰 実機完全登録スクリプト</h1>";
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

    echo "<p>✅ データベース接続成功</p>";
    echo "<p>Host: $db_host / DB: $db_name</p>";
    echo "<hr>";

    // 北斗の拳の機種番号を取得
    $model = $pdo->query("SELECT model_no, model_name FROM mst_model WHERE model_cd = 'HOKUTO4GO' AND del_flg = 0")->fetch();

    if (!$model) {
        die("<p>❌ 北斗の拳の機種データが見つかりません。先にcomplete_setup.phpを実行してください。</p>");
    }

    echo "<h2>📋 機種情報</h2>";
    echo "<p>機種No: {$model['model_no']}</p>";
    echo "<p>機種名: {$model['model_name']}</p>";
    echo "<hr>";

    // 登録する実機の設定
    $machines = [
        [
            'machine_cd' => 'HOKUTO001',
            'camera_no' => 1,
            'signaling_id' => 'PEER001',
            'convert_no' => 1,
        ],
        [
            'machine_cd' => 'HOKUTO002',
            'camera_no' => 2,
            'signaling_id' => 'PEER002',
            'convert_no' => 1,
        ],
        [
            'machine_cd' => 'HOKUTO003',
            'camera_no' => 3,
            'signaling_id' => 'PEER003',
            'convert_no' => 1,
        ],
    ];

    $today = date('Y-m-d');

    echo "<h2>🎰 実機完全登録</h2>";

    $registered_count = 0;
    $updated_count = 0;

    foreach ($machines as $machine) {
        // 既存チェック
        $check = $pdo->prepare("SELECT machine_no FROM dat_machine WHERE machine_cd = :machine_cd");
        $check->execute(['machine_cd' => $machine['machine_cd']]);
        $exists = $check->fetch();

        if ($exists) {
            // 既存データを更新（machine_statusを1に変更）
            $update_stmt = $pdo->prepare("
                UPDATE dat_machine SET
                    machine_status = 1,
                    upd_no = 1,
                    upd_dt = NOW()
                WHERE machine_no = :machine_no
            ");
            $update_stmt->execute(['machine_no' => $exists['machine_no']]);

            $machine_no = $exists['machine_no'];
            echo "<p>⚠️ {$machine['machine_cd']} は既に登録済み → machine_statusを1（稼働中）に更新（台No: {$machine_no}）</p>";
            $updated_count++;
        } else {
            // 新規登録
            $stmt = $pdo->prepare("
                INSERT INTO dat_machine (
                    model_no,
                    machine_cd,
                    owner_no,
                    camera_no,
                    signaling_id,
                    convert_no,
                    release_date,
                    end_date,
                    machine_status,
                    del_flg,
                    add_no,
                    add_dt
                ) VALUES (
                    :model_no,
                    :machine_cd,
                    NULL,
                    :camera_no,
                    :signaling_id,
                    :convert_no,
                    :release_date,
                    '2099-12-31',
                    1,
                    0,
                    1,
                    NOW()
                )
            ");

            $stmt->execute([
                'model_no' => $model['model_no'],
                'machine_cd' => $machine['machine_cd'],
                'camera_no' => $machine['camera_no'],
                'signaling_id' => $machine['signaling_id'],
                'convert_no' => $machine['convert_no'],
                'release_date' => $today
            ]);

            $machine_no = $pdo->lastInsertId();
            echo "<p>✅ {$machine['machine_cd']} 登録完了（台No: {$machine_no}）</p>";
            $registered_count++;
        }

        // dat_machinePlay（実機プレイデータ）の登録
        $play_check = $pdo->prepare("SELECT machine_no FROM dat_machinePlay WHERE machine_no = :machine_no");
        $play_check->execute(['machine_no' => $machine_no]);

        if (!$play_check->fetch()) {
            $play_stmt = $pdo->prepare("
                INSERT INTO dat_machinePlay (
                    machine_no,
                    total_count,
                    count,
                    bb_count,
                    rb_count,
                    hit_data,
                    add_dt
                ) VALUES (
                    :machine_no,
                    0,
                    0,
                    0,
                    0,
                    '',
                    NOW()
                )
            ");
            $play_stmt->execute(['machine_no' => $machine_no]);
            echo "<p>  ↳ dat_machinePlay 登録完了</p>";
        }

        // lnk_machine（実機接続状況）の登録
        $lnk_check = $pdo->prepare("SELECT machine_no FROM lnk_machine WHERE machine_no = :machine_no");
        $lnk_check->execute(['machine_no' => $machine_no]);

        if (!$lnk_check->fetch()) {
            $lnk_stmt = $pdo->prepare("
                INSERT INTO lnk_machine (
                    machine_no,
                    member_no,
                    assign_flg
                ) VALUES (
                    :machine_no,
                    NULL,
                    0
                )
            ");
            $lnk_stmt->execute(['machine_no' => $machine_no]);
            echo "<p>  ↳ lnk_machine 登録完了</p>";
        }
    }

    echo "<hr>";
    echo "<h2>🎉 登録完了</h2>";
    echo "<p>新規登録: {$registered_count}台</p>";
    echo "<p>更新: {$updated_count}台</p>";

    // 登録確認
    $total = $pdo->query("SELECT COUNT(*) FROM dat_machine WHERE model_no = {$model['model_no']} AND del_flg = 0")->fetchColumn();
    $active = $pdo->query("SELECT COUNT(*) FROM dat_machine WHERE model_no = {$model['model_no']} AND del_flg = 0 AND machine_status = 1")->fetchColumn();

    echo "<p>北斗の拳の実機総数: {$total}台（稼働中: {$active}台）</p>";

    echo "<hr>";
    echo "<h2>🔗 次のステップ</h2>";
    echo "<ul>";
    echo "<li><a href='/xxxadmin/search.php'>実機管理画面</a>で台を確認</li>";
    echo "<li><a href='/'>トップページ</a>で北斗の拳が表示されるか確認</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<h2>📋 登録された実機詳細一覧</h2>";
    $machines_list = $pdo->query("
        SELECT
            m.machine_no,
            m.machine_cd,
            m.camera_no,
            m.signaling_id,
            m.release_date,
            m.machine_status,
            mp.total_count,
            lm.assign_flg
        FROM dat_machine m
        LEFT JOIN dat_machinePlay mp ON mp.machine_no = m.machine_no
        LEFT JOIN lnk_machine lm ON lm.machine_no = m.machine_no
        WHERE m.model_no = {$model['model_no']} AND m.del_flg = 0
        ORDER BY m.machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    $status_labels = ['停止中', '稼働中', 'メンテナンス中'];

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>台No</th><th>台CD</th><th>カメラNo</th><th>シグナリングID</th><th>公開日</th><th>ステータス</th><th>プレイデータ</th><th>接続状況</th></tr>";
    foreach ($machines_list as $m) {
        $status = $status_labels[$m['machine_status']] ?? '不明';
        $play_status = ($m['total_count'] !== null) ? '✅' : '❌';
        $lnk_status = ($m['assign_flg'] !== null) ? '✅' : '❌';

        echo "<tr>";
        echo "<td>{$m['machine_no']}</td>";
        echo "<td>{$m['machine_cd']}</td>";
        echo "<td>{$m['camera_no']}</td>";
        echo "<td>{$m['signaling_id']}</td>";
        echo "<td>{$m['release_date']}</td>";
        echo "<td>{$status}</td>";
        echo "<td>{$play_status}</td>";
        echo "<td>{$lnk_status}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><small>プレイデータ: dat_machinePlay への登録状況 / 接続状況: lnk_machine への登録状況</small></p>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
