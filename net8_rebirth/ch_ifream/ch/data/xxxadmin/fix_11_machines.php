<?php
/**
 * 11台の機材をオンライン状態に更新（サーバー側実行用）
 * URL: /data/xxxadmin/fix_11_machines.php
 */

require_once __DIR__ . '/../../_etc/require_files.php';

$pdo = get_db_connection();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>11台機材オンライン化</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { max-width: 800px; margin: 0 auto; }
        h1 { color: #00d4ff; }
        .button {
            background: #00ff88;
            color: #000;
            padding: 15px 30px;
            border: none;
            border-radius: 5px;
            font-size: 18px;
            cursor: pointer;
            margin: 20px 0;
        }
        .button:hover { background: #00cc70; }
        .result {
            background: #16213e;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .success { color: #00ff88; }
        .error { color: #ff4444; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #0f1626; color: #00d4ff; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 11台機材オンライン化ツール</h1>

        <?php
        $machine_nos = [1, 2, 3, 5, 7, 9, 10, 11, 12, 13, 15];

        if (isset($_POST['execute'])) {
            try {
                // 更新前の状態取得
                $placeholders = implode(',', array_fill(0, count($machine_nos), '?'));
                $sql = "SELECT dm.machine_no, mm.model_name, mc.camera_mac, lm.assign_flg
                        FROM dat_machine dm
                        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
                        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
                        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                        WHERE dm.machine_no IN ($placeholders)
                        ORDER BY dm.machine_no";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($machine_nos);
                $before = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<div class="result">';
                echo '<h2>📊 更新前の状態:</h2>';
                echo '<table>';
                echo '<tr><th>機材NO</th><th>モデル名</th><th>MACアドレス</th><th>assign_flg</th><th>状態</th></tr>';
                foreach ($before as $m) {
                    $status = ($m['assign_flg'] == 9) ? '🟢 ONLINE' : '🔴 OFFLINE';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($m['machine_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($m['model_name'] ?? '未設定') . '</td>';
                    echo '<td>' . htmlspecialchars($m['camera_mac'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($m['assign_flg'] ?? 'NULL') . '</td>';
                    echo '<td>' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                // 更新実行
                $pdo->beginTransaction();

                $sql = "UPDATE lnk_machine SET assign_flg = 9 WHERE machine_no IN ($placeholders)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($machine_nos);
                $updated_count = $stmt->rowCount();

                $pdo->commit();

                echo '<h2 class="success">✅ 更新完了: ' . $updated_count . '件</h2>';

                // 更新後の状態取得
                $sql = "SELECT dm.machine_no, lm.assign_flg
                        FROM dat_machine dm
                        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                        WHERE dm.machine_no IN ($placeholders)
                        ORDER BY dm.machine_no";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($machine_nos);
                $after = $stmt->fetchAll(PDO::FETCH_ASSOC);

                echo '<h2>📊 更新後の状態:</h2>';
                echo '<table>';
                echo '<tr><th>機材NO</th><th>assign_flg</th><th>状態</th></tr>';
                foreach ($after as $m) {
                    $status = ($m['assign_flg'] == 9) ? '🟢 ONLINE' : '🔴 OFFLINE';
                    $statusClass = ($m['assign_flg'] == 9) ? 'success' : 'error';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($m['machine_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($m['assign_flg']) . '</td>';
                    echo '<td class="' . $statusClass . '">' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                echo '<p class="success">🎉 全11台の機材がオンライン状態になりました！</p>';
                echo '<p><a href="machine_monitor.php" style="color: #00d4ff;">machine_monitor.phpで確認 →</a></p>';

                echo '</div>';

            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                echo '<div class="result error">';
                echo '<h2>❌ エラー</h2>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
        } else {
            // 現在の状態表示
            try {
                $placeholders = implode(',', array_fill(0, count($machine_nos), '?'));
                $sql = "SELECT dm.machine_no, mm.model_name, mc.camera_mac, lm.assign_flg
                        FROM dat_machine dm
                        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
                        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
                        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                        WHERE dm.machine_no IN ($placeholders)
                        ORDER BY dm.machine_no";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($machine_nos);
                $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $offline_count = 0;
                foreach ($machines as $m) {
                    if ($m['assign_flg'] != 9) {
                        $offline_count++;
                    }
                }

                echo '<div class="result">';
                echo '<h2>📋 対象機材（11台）</h2>';
                echo '<p>オフライン状態の機材: <span class="error">' . $offline_count . '台</span></p>';

                echo '<table>';
                echo '<tr><th>機材NO</th><th>モデル名</th><th>MACアドレス</th><th>assign_flg</th><th>現在の状態</th></tr>';
                foreach ($machines as $m) {
                    $status = ($m['assign_flg'] == 9) ? '🟢 ONLINE' : '🔴 OFFLINE';
                    $statusClass = ($m['assign_flg'] == 9) ? 'success' : 'error';
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($m['machine_no']) . '</td>';
                    echo '<td>' . htmlspecialchars($m['model_name'] ?? '未設定') . '</td>';
                    echo '<td>' . htmlspecialchars($m['camera_mac'] ?? 'N/A') . '</td>';
                    echo '<td>' . htmlspecialchars($m['assign_flg'] ?? 'NULL') . '</td>';
                    echo '<td class="' . $statusClass . '">' . $status . '</td>';
                    echo '</tr>';
                }
                echo '</table>';

                if ($offline_count > 0) {
                    echo '<form method="POST">';
                    echo '<button type="submit" name="execute" class="button">⚠️ ' . $offline_count . '台をオンライン状態にする（assign_flg = 9）</button>';
                    echo '</form>';
                } else {
                    echo '<p class="success">✅ 全機材が既にオンライン状態です</p>';
                }

                echo '</div>';

            } catch (PDOException $e) {
                echo '<div class="result error">';
                echo '<h2>❌ エラー</h2>';
                echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                echo '</div>';
            }
        }
        ?>

        <p><a href="machine_monitor.php" style="color: #00d4ff;">← machine_monitor.phpに戻る</a></p>
    </div>
</body>
</html>
