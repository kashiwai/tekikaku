<?php
/**
 * 実機状態確認スクリプト
 *
 * トップページに表示されない原因を調査します
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 実機状態確認</h1>";
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

    // 1. dat_machine の状態確認
    echo "<h2>📋 1. dat_machine（実機マスタ）</h2>";
    $machines = $pdo->query("
        SELECT
            machine_no,
            machine_cd,
            model_no,
            camera_no,
            signaling_id,
            machine_status,
            release_date,
            end_date,
            del_flg
        FROM dat_machine
        ORDER BY machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($machines)) {
        echo "<p>❌ 実機データが登録されていません</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>台CD</th><th>機種No</th><th>カメラNo</th><th>シグナリングID</th><th>ステータス</th><th>公開日</th><th>終了日</th><th>削除フラグ</th></tr>";
        foreach ($machines as $m) {
            $status_label = ['停止中', '稼働中', 'メンテナンス中'][$m['machine_status']] ?? '不明';
            $status_color = $m['machine_status'] == 1 ? 'green' : 'red';
            $del_color = $m['del_flg'] == 0 ? 'green' : 'red';

            echo "<tr>";
            echo "<td>{$m['machine_no']}</td>";
            echo "<td>{$m['machine_cd']}</td>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td>{$m['camera_no']}</td>";
            echo "<td>{$m['signaling_id']}</td>";
            echo "<td style='color:{$status_color}'><b>{$status_label}</b></td>";
            echo "<td>{$m['release_date']}</td>";
            echo "<td>{$m['end_date']}</td>";
            echo "<td style='color:{$del_color}'>{$m['del_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>合計: " . count($machines) . "台</p>";
    }

    // 2. dat_machinePlay の状態確認
    echo "<hr>";
    echo "<h2>📊 2. dat_machinePlay（プレイデータ）</h2>";
    $plays = $pdo->query("
        SELECT machine_no, total_count, count, bb_count, rb_count
        FROM dat_machinePlay
        ORDER BY machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($plays)) {
        echo "<p>❌ プレイデータが登録されていません</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>総回転数</th><th>現在回転数</th><th>BB回数</th><th>RB回数</th></tr>";
        foreach ($plays as $p) {
            echo "<tr>";
            echo "<td>{$p['machine_no']}</td>";
            echo "<td>{$p['total_count']}</td>";
            echo "<td>{$p['count']}</td>";
            echo "<td>{$p['bb_count']}</td>";
            echo "<td>{$p['rb_count']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>合計: " . count($plays) . "件</p>";
    }

    // 3. lnk_machine の状態確認
    echo "<hr>";
    echo "<h2>🔗 3. lnk_machine（接続状況）</h2>";
    $links = $pdo->query("
        SELECT machine_no, assign_flg, member_no, exit_flg
        FROM lnk_machine
        ORDER BY machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($links)) {
        echo "<p>❌ 接続状況データが登録されていません</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>台No</th><th>割当フラグ</th><th>会員No</th><th>退出フラグ</th></tr>";
        foreach ($links as $l) {
            echo "<tr>";
            echo "<td>{$l['machine_no']}</td>";
            echo "<td>{$l['assign_flg']}</td>";
            echo "<td>" . ($l['member_no'] ?? 'NULL') . "</td>";
            echo "<td>{$l['exit_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>合計: " . count($links) . "件</p>";
    }

    // 4. mst_model の状態確認
    echo "<hr>";
    echo "<h2>🎰 4. mst_model（機種マスタ）</h2>";
    $models = $pdo->query("
        SELECT model_no, model_cd, model_name, category, del_flg
        FROM mst_model
        ORDER BY model_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (empty($models)) {
        echo "<p>❌ 機種データが登録されていません</p>";
    } else {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>機種No</th><th>機種CD</th><th>機種名</th><th>カテゴリ</th><th>削除フラグ</th></tr>";
        foreach ($models as $m) {
            $category_label = ['', 'パチンコ', 'スロット'][$m['category']] ?? '不明';
            echo "<tr>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td>{$m['model_cd']}</td>";
            echo "<td>{$m['model_name']}</td>";
            echo "<td>{$category_label}</td>";
            echo "<td>{$m['del_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>合計: " . count($models) . "件</p>";
    }

    // 5. トップページ表示SQL確認
    echo "<hr>";
    echo "<h2>🔍 5. トップページ表示SQL実行テスト</h2>";

    $today = date('Y-m-d');

    $test_sql = "
        SELECT
            dm.machine_no,
            dm.machine_cd,
            dm.machine_status,
            dm.camera_no,
            mm.model_name
        FROM dat_machine dm
        INNER JOIN dat_machinePlay dmp ON dmp.machine_no = dm.machine_no
        INNER JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
        INNER JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg <> '1'
        WHERE dm.camera_no IS NOT NULL
          AND dm.del_flg <> '1'
          AND dm.release_date <= '$today'
          AND dm.end_date >= '$today'
          AND dm.machine_status <> '0'
        ORDER BY dm.machine_no
    ";

    try {
        $result = $pdo->query($test_sql)->fetchAll(PDO::FETCH_ASSOC);

        if (empty($result)) {
            echo "<p>❌ トップページに表示される台がありません</p>";
            echo "<p><b>原因候補:</b></p>";
            echo "<ul>";
            echo "<li>machine_status が 0（停止中）になっている</li>";
            echo "<li>del_flg が 1（削除済み）になっている</li>";
            echo "<li>release_date が未来日付になっている</li>";
            echo "<li>end_date が過去日付になっている</li>";
            echo "<li>dat_machinePlay または lnk_machine にデータがない</li>";
            echo "</ul>";
        } else {
            echo "<p>✅ トップページに表示される台: " . count($result) . "台</p>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>台No</th><th>台CD</th><th>機種名</th><th>カメラNo</th><th>ステータス</th></tr>";
            foreach ($result as $r) {
                $status_label = ['停止中', '稼働中', 'メンテナンス中'][$r['machine_status']] ?? '不明';
                echo "<tr>";
                echo "<td>{$r['machine_no']}</td>";
                echo "<td>{$r['machine_cd']}</td>";
                echo "<td>{$r['model_name']}</td>";
                echo "<td>{$r['camera_no']}</td>";
                echo "<td>{$status_label}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } catch (PDOException $e) {
        echo "<p>❌ SQL実行エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    echo "<hr>";
    echo "<h2>💡 推奨アクション</h2>";
    echo "<ol>";
    echo "<li>❌ が表示されている項目を確認</li>";
    echo "<li>machine_status が 0 の場合は、register_machine_complete.php を再実行</li>";
    echo "<li>データが不足している場合は、complete_setup.php から再実行</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2>❌ データベースエラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
