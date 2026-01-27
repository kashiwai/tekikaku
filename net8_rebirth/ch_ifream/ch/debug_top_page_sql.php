<?php
/**
 * トップページSQL デバッグスクリプト（簡易版）
 *
 * トップページに表示されない原因を調査します
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 トップページSQL デバッグ</h1>";
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

    // 1. 基本的な台情報確認
    echo "<h2>📋 1. dat_machine（実機マスタ）基本情報</h2>";
    $machines = $pdo->query("
        SELECT
            machine_no,
            machine_cd,
            model_no,
            camera_no,
            signaling_id,
            machine_status,
            machine_corner,
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
        echo "<tr><th>台No</th><th>台CD</th><th>機種No</th><th>カメラNo</th><th>シグナリングID</th><th>コーナー</th><th>ステータス</th><th>公開日</th><th>終了日</th><th>削除フラグ</th></tr>";
        foreach ($machines as $m) {
            $status_label = ['停止中', '稼働中', 'メンテナンス中'][$m['machine_status']] ?? '不明';
            $status_color = $m['machine_status'] == 1 ? 'green' : 'red';
            $del_color = $m['del_flg'] == 0 ? 'green' : 'red';
            $corner_display = ($m['machine_corner'] === null || $m['machine_corner'] === '') ? '<span style="color:red;">NULL</span>' : $m['machine_corner'];

            echo "<tr>";
            echo "<td>{$m['machine_no']}</td>";
            echo "<td>{$m['machine_cd']}</td>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td>{$m['camera_no']}</td>";
            echo "<td>{$m['signaling_id']}</td>";
            echo "<td>{$corner_display}</td>";
            echo "<td style='color:{$status_color}'><b>{$status_label}</b></td>";
            echo "<td>{$m['release_date']}</td>";
            echo "<td>{$m['end_date']}</td>";
            echo "<td style='color:{$del_color}'>{$m['del_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "<p>合計: " . count($machines) . "台</p>";
    }

    // 2. トップページ表示条件テスト
    echo "<hr>";
    echo "<h2>🔍 2. トップページ表示条件SQLテスト</h2>";

    $today = date('Y-m-d');
    echo "<p><b>今日の日付:</b> $today</p>";

    // テストケース1: 基本SQL（check_machine_status.phpと同じ）
    echo "<h3>テストケース1: 基本SQL（check_machine_status.phpと同じ）</h3>";
    $basic_sql = "
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

    echo "<pre style='background:#f0f0f0; padding:10px; overflow:auto;'>" . htmlspecialchars($basic_sql) . "</pre>";

    try {
        $result1 = $pdo->query($basic_sql)->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><b>取得件数:</b> " . count($result1) . "件</p>";

        if (!empty($result1)) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>台No</th><th>台CD</th><th>機種名</th><th>カメラNo</th><th>ステータス</th></tr>";
            foreach ($result1 as $r) {
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
        } else {
            echo "<p style='color:red;'>❌ 該当データなし</p>";
        }
    } catch (PDOException $e) {
        echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
    }

    // テストケース2: machine_cornerのFIND_IN_SETテスト
    echo "<hr>";
    echo "<h3>テストケース2: machine_corner のFIND_IN_SETテスト</h3>";
    echo "<p>トップページでは、CNパラメータがある場合、FIND_IN_SET()でコーナーフィルタリングを行います。</p>";

    $corner_tests = [
        ['value' => '1', 'label' => 'コーナー1'],
        ['value' => '2', 'label' => 'コーナー2'],
        ['value' => '', 'label' => '空文字列（CNパラメータなし）'],
    ];

    foreach ($corner_tests as $test) {
        echo "<h4>{$test['label']} (CN={$test['value']})</h4>";

        if ($test['value'] === '') {
            echo "<p>CNパラメータなしの場合、FIND_IN_SET条件は適用されません。</p>";
            $corner_sql = "
                SELECT
                    dm.machine_no,
                    dm.machine_cd,
                    dm.machine_corner,
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
        } else {
            $corner_sql = "
                SELECT
                    dm.machine_no,
                    dm.machine_cd,
                    dm.machine_corner,
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
                  AND FIND_IN_SET('{$test['value']}', dm.machine_corner)
                ORDER BY dm.machine_no
            ";
        }

        echo "<pre style='background:#f0f0f0; padding:10px; overflow:auto;'>" . htmlspecialchars($corner_sql) . "</pre>";

        try {
            $corner_result = $pdo->query($corner_sql)->fetchAll(PDO::FETCH_ASSOC);
            echo "<p><b>取得件数:</b> " . count($corner_result) . "件</p>";

            if (!empty($corner_result)) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>台No</th><th>台CD</th><th>コーナー値</th><th>機種名</th></tr>";
                foreach ($corner_result as $r) {
                    $corner_display = ($r['machine_corner'] === null || $r['machine_corner'] === '') ? '<span style="color:red;">NULL</span>' : $r['machine_corner'];
                    echo "<tr>";
                    echo "<td>{$r['machine_no']}</td>";
                    echo "<td>{$r['machine_cd']}</td>";
                    echo "<td>{$corner_display}</td>";
                    echo "<td>{$r['model_name']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color:orange;'>該当データなし</p>";
            }
        } catch (PDOException $e) {
            echo "<p style='color:red;'><b>エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }

    // 3. データ整合性チェック
    echo "<hr>";
    echo "<h2>🔍 3. データ整合性チェック</h2>";

    // dat_machinePlay チェック
    echo "<h3>dat_machinePlay 存在チェック</h3>";
    $play_check = $pdo->query("
        SELECT dm.machine_no, dmp.machine_no as play_exists
        FROM dat_machine dm
        LEFT JOIN dat_machinePlay dmp ON dmp.machine_no = dm.machine_no
        WHERE dm.del_flg = 0
        ORDER BY dm.machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>台No</th><th>dat_machinePlayに存在</th></tr>";
    foreach ($play_check as $pc) {
        $exists = $pc['play_exists'] ? '✅' : '<span style="color:red;">❌</span>';
        echo "<tr>";
        echo "<td>{$pc['machine_no']}</td>";
        echo "<td>{$exists}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // lnk_machine チェック
    echo "<h3>lnk_machine 存在チェック</h3>";
    $lnk_check = $pdo->query("
        SELECT dm.machine_no, lm.machine_no as lnk_exists
        FROM dat_machine dm
        LEFT JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
        WHERE dm.del_flg = 0
        ORDER BY dm.machine_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>台No</th><th>lnk_machineに存在</th></tr>";
    foreach ($lnk_check as $lc) {
        $exists = $lc['lnk_exists'] ? '✅' : '<span style="color:red;">❌</span>';
        echo "<tr>";
        echo "<td>{$lc['machine_no']}</td>";
        echo "<td>{$exists}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<hr>";
    echo "<h2>💡 診断結果</h2>";
    echo "<ul>";
    echo "<li><b>テストケース1</b>で台が取得できれば、データは正常です</li>";
    echo "<li><b>machine_corner</b>がNULLの場合、FIND_IN_SET()でマッチしません</li>";
    echo "<li>トップページでCNパラメータが設定されている場合、machine_cornerの値が必要です</li>";
    echo "<li>dat_machinePlay または lnk_machine に❌がある場合、JOINで除外されます</li>";
    echo "</ul>";

    echo "<h3>🔗 推奨アクション</h3>";
    echo "<ol>";
    echo "<li>テストケース1で0件の場合 → データ整合性チェックを確認</li>";
    echo "<li>machine_cornerがNULLの場合 → register_machine_complete.phpでデフォルト値を設定</li>";
    echo "<li>トップページのURLを確認して、CNパラメータの値を確認</li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2>❌ データベースエラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
