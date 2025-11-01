<?php
/**
 * index.phpの実際のSQL実行をデバッグ
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🔍 index.php SQL実行デバッグ</h1>";
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

    $today = date('Y-m-d');

    // index.phpと同じSQLを構築
    // SearchMachineBase + SearchMachineField を再現
    $sql = "
        SELECT
            dm.machine_no,
            dm.machine_cd,
            dm.machine_status,
            dm.camera_no,
            dm.signaling_id,
            mm.model_no,
            mm.model_cd,
            mm.model_name,
            mm.maker_no,
            mm.type_no,
            mm.category,
            mm.unit_no,
            dmp.count,
            dmp.total_count,
            dmp.bb_count,
            dmp.rb_count,
            dmp.hit_data,
            lm.assign_flg,
            lm.member_no,
            ma.maker_name,
            mt.type_name,
            mu.unit_name,
            mcp.convert_point
        FROM dat_machine dm
        INNER JOIN dat_machinePlay dmp ON dmp.machine_no = dm.machine_no
        INNER JOIN lnk_machine lm ON lm.machine_no = dm.machine_no
        INNER JOIN mst_model mm ON mm.model_no = dm.model_no AND mm.del_flg <> '1'
        INNER JOIN mst_maker ma ON ma.maker_no = mm.maker_no AND ma.del_flg <> '1'
        LEFT JOIN mst_unit mu ON mu.unit_no = mm.unit_no AND mu.del_flg <> '1'
        INNER JOIN mst_type mt ON mt.type_no = mm.type_no AND mt.del_flg <> '1'
        INNER JOIN mst_convertPoint mcp ON mcp.convert_no = dm.convert_no AND mcp.del_flg <> '1'
        WHERE dm.camera_no IS NOT NULL
          AND dm.del_flg <> '1'
          AND dm.release_date <= '$today'
          AND dm.end_date >= '$today'
          AND dm.machine_status <> '0'
        ORDER BY dm.release_date DESC
    ";

    echo "<h2>📊 実行SQL（index.phpと同じ）</h2>";
    echo "<pre style='background:#f0f0f0; padding:10px; overflow:auto;'>" . htmlspecialchars($sql) . "</pre>";

    try {
        $result = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        echo "<h2>✅ 実行結果</h2>";
        echo "<p><b>取得件数:</b> " . count($result) . "件</p>";

        if (!empty($result)) {
            echo "<h3>取得データ詳細</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='font-size:12px;'>";
            echo "<tr>";
            echo "<th>台No</th>";
            echo "<th>台CD</th>";
            echo "<th>機種名</th>";
            echo "<th>カメラNo</th>";
            echo "<th>シグナリングID</th>";
            echo "<th>ステータス</th>";
            echo "<th>メーカー</th>";
            echo "<th>タイプ</th>";
            echo "<th>ユニット</th>";
            echo "<th>割当フラグ</th>";
            echo "</tr>";

            foreach ($result as $row) {
                $status_label = ['停止中', '稼働中', 'メンテナンス中'][$row['machine_status']] ?? '不明';
                echo "<tr>";
                echo "<td>{$row['machine_no']}</td>";
                echo "<td>{$row['machine_cd']}</td>";
                echo "<td>{$row['model_name']}</td>";
                echo "<td>{$row['camera_no']}</td>";
                echo "<td>{$row['signaling_id']}</td>";
                echo "<td>{$status_label}</td>";
                echo "<td>{$row['maker_name']}</td>";
                echo "<td>{$row['type_name']}</td>";
                echo "<td>" . ($row['unit_name'] ?? 'NULL') . "</td>";
                echo "<td>{$row['assign_flg']}</td>";
                echo "</tr>";
            }
            echo "</table>";

            echo "<hr>";
            echo "<h3>🎉 結論</h3>";
            echo "<p style='font-size:16px; color:green;'><b>✅ SQLは正常に実行され、{count($result)}件のデータが取得できています。</b></p>";
            echo "<p>データベースとSQLには問題ありません。</p>";
            echo "<p><b>考えられる原因:</b></p>";
            echo "<ul>";
            echo "<li>テンプレート表示ロジック（AssignMachineList）の問題</li>";
            echo "<li>HTMLテンプレートファイルの問題</li>";
            echo "<li>JavaScriptエラーによる表示ブロック</li>";
            echo "</ul>";

        } else {
            echo "<p style='color:red;'><b>❌ データが取得できませんでした</b></p>";
            echo "<p>以下を確認してください：</p>";
            echo "<ul>";
            echo "<li>mst_maker テーブルにデータがあるか</li>";
            echo "<li>mst_type テーブルにデータがあるか</li>";
            echo "<li>mst_convertPoint テーブルにデータがあるか</li>";
            echo "</ul>";
        }

    } catch (PDOException $e) {
        echo "<p style='color:red;'><b>❌ SQL実行エラー:</b> " . htmlspecialchars($e->getMessage()) . "</p>";

        // エラーの場合、各テーブルの存在確認
        echo "<h3>📋 テーブル存在確認</h3>";

        $tables = ['mst_maker', 'mst_type', 'mst_unit', 'mst_convertPoint'];
        foreach ($tables as $table) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM $table WHERE del_flg = 0")->fetchColumn();
                echo "<p>✅ {$table}: {$count}件</p>";
            } catch (PDOException $e) {
                echo "<p style='color:red;'>❌ {$table}: テーブルが存在しないか、エラー</p>";
            }
        }
    }

} catch (PDOException $e) {
    echo "<h2>❌ データベース接続エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
