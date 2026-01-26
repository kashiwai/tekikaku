<?php
/**
 * 全機材の現在のステータスを確認
 */
require_once __DIR__ . '/../../_etc/require_files.php';

$pdo = get_db_connection();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>全機材ステータス確認</title>
    <style>
        body { font-family: sans-serif; padding: 20px; background: #1a1a2e; color: #fff; }
        .container { max-width: 1200px; margin: 0 auto; }
        h1 { color: #00d4ff; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; font-size: 12px; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #0f1626; color: #00d4ff; position: sticky; top: 0; }
        .online { color: #00ff88; font-weight: bold; }
        .offline { color: #ff4444; font-weight: bold; }
        .stats { background: #16213e; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .stats h2 { margin-top: 0; color: #00d4ff; }
        .big-number { font-size: 48px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 全機材ステータス確認</h1>

        <?php
        try {
            // 全機材の状態取得
            $sql = "SELECT
                        dm.machine_no,
                        dm.machine_cd,
                        dm.status as dm_status,
                        mm.model_name,
                        mc.camera_mac,
                        lm.assign_flg,
                        lm.member_no,
                        TIMESTAMPDIFF(MINUTE, dm.last_report, NOW()) as minutes_ago
                    FROM dat_machine dm
                    LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
                    LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
                    LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
                    WHERE dm.del_flg = 0
                    ORDER BY dm.machine_no";

            $stmt = $pdo->query($sql);
            $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 統計
            $total = count($machines);
            $online = 0;
            $offline = 0;
            $assign_counts = [];

            foreach ($machines as $m) {
                // machine_monitor.phpと同じ判定
                $isOnline = ($m['assign_flg'] == 9 || $m['assign_flg'] == 1);
                if ($isOnline) {
                    $online++;
                } else {
                    $offline++;
                }

                $flag = $m['assign_flg'] ?? 'NULL';
                $assign_counts[$flag] = ($assign_counts[$flag] ?? 0) + 1;
            }

            echo '<div class="stats">';
            echo '<h2>📊 統計情報</h2>';
            echo '<p><span class="big-number online">' . $online . '</span> 台がオンライン</p>';
            echo '<p><span class="big-number offline">' . $offline . '</span> 台がオフライン</p>';
            echo '<p>合計: ' . $total . '台</p>';

            echo '<h3>assign_flg分布:</h3>';
            ksort($assign_counts);
            foreach ($assign_counts as $flag => $count) {
                $label = '';
                switch($flag) {
                    case '0': $label = '未割当（オフライン）'; break;
                    case '1': $label = '使用中（オンライン）'; break;
                    case '9': $label = 'カメラ配信中（オンライン）'; break;
                    case 'NULL': $label = 'レコードなし（オフライン）'; break;
                    default: $label = '不明';
                }
                echo '<p>assign_flg = ' . htmlspecialchars($flag) . ' (' . $label . '): <strong>' . $count . '台</strong></p>';
            }
            echo '</div>';

            // 詳細テーブル
            echo '<h2>📋 全機材詳細</h2>';
            echo '<table>';
            echo '<tr>';
            echo '<th>機材NO</th>';
            echo '<th>機材CD</th>';
            echo '<th>モデル名</th>';
            echo '<th>MACアドレス</th>';
            echo '<th>assign_flg</th>';
            echo '<th>member_no</th>';
            echo '<th>表示</th>';
            echo '</tr>';

            foreach ($machines as $m) {
                $isOnline = ($m['assign_flg'] == 9 || $m['assign_flg'] == 1);
                $displayClass = $isOnline ? 'online' : 'offline';
                $displayText = $isOnline ? '🟢 ONLINE' : '🔴 OFFLINE';

                echo '<tr>';
                echo '<td>' . htmlspecialchars($m['machine_no']) . '</td>';
                echo '<td>' . htmlspecialchars($m['machine_cd'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($m['model_name'] ?? '未設定') . '</td>';
                echo '<td>' . htmlspecialchars($m['camera_mac'] ?? 'N/A') . '</td>';
                echo '<td>' . htmlspecialchars($m['assign_flg'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($m['member_no'] ?? 'NULL') . '</td>';
                echo '<td class="' . $displayClass . '">' . $displayText . '</td>';
                echo '</tr>';
            }

            echo '</table>';

            // オフライン機材のMACアドレス一覧
            $offline_macs = [];
            foreach ($machines as $m) {
                if ($m['assign_flg'] != 9 && $m['assign_flg'] != 1) {
                    if ($m['camera_mac']) {
                        $offline_macs[] = [
                            'machine_no' => $m['machine_no'],
                            'mac' => $m['camera_mac'],
                            'model' => $m['model_name'] ?? '未設定'
                        ];
                    }
                }
            }

            if (!empty($offline_macs)) {
                echo '<div class="stats">';
                echo '<h2>⚠️ オフライン機材のserver_v2 URL一覧</h2>';
                echo '<p>以下のURLにWin側のブラウザでアクセスすると、自動的にオンラインになります：</p>';
                echo '<table>';
                echo '<tr><th>機材NO</th><th>モデル名</th><th>URL</th></tr>';
                foreach ($offline_macs as $info) {
                    $url = 'https://mgg-webservice-production.up.railway.app/data/server_v2/?MAC=' . $info['mac'];
                    echo '<tr>';
                    echo '<td>' . $info['machine_no'] . '</td>';
                    echo '<td>' . htmlspecialchars($info['model']) . '</td>';
                    echo '<td><a href="' . $url . '" target="_blank" style="color: #00d4ff;">' . htmlspecialchars($url) . '</a></td>';
                    echo '</tr>';
                }
                echo '</table>';
                echo '</div>';
            }

        } catch (PDOException $e) {
            echo '<div style="color: #ff4444;">';
            echo '<h2>❌ エラー</h2>';
            echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '</div>';
        }
        ?>

        <p><a href="machine_monitor.php" style="color: #00d4ff;">← machine_monitor.phpに戻る</a></p>
        <p><a href="fix_11_machines.php" style="color: #00d4ff;">→ オフライン機材を一括修正</a></p>
    </div>
</body>
</html>
