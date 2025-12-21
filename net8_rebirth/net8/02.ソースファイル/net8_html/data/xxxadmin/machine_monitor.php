<?php
/**
 * マシンモニター - 全台状態一覧
 * 各PCから報告された状態をリアルタイム表示
 */
require_once __DIR__ . '/../lib/db.php';

$pdo = getDbConnection();

// 全マシン取得
$sql = "SELECT dm.*, mm.model_name,
        TIMESTAMPDIFF(MINUTE, dm.last_report, NOW()) as minutes_ago
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        ORDER BY dm.machine_no";
$machines = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net8 マシンモニター</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #1a1a2e;
            color: #fff;
            padding: 20px;
        }
        h1 {
            text-align: center;
            margin-bottom: 20px;
            color: #00d4ff;
        }
        .stats {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-bottom: 30px;
        }
        .stat-box {
            background: #16213e;
            padding: 20px 40px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-box .number {
            font-size: 48px;
            font-weight: bold;
        }
        .stat-box.online .number { color: #00ff88; }
        .stat-box.offline .number { color: #ff4444; }
        .stat-box.total .number { color: #00d4ff; }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            gap: 15px;
        }
        .machine {
            background: #16213e;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .machine.online {
            border-color: #00ff88;
            box-shadow: 0 0 15px rgba(0,255,136,0.3);
        }
        .machine.offline {
            border-color: #444;
            opacity: 0.6;
        }
        .machine.warning {
            border-color: #ffaa00;
            box-shadow: 0 0 15px rgba(255,170,0,0.3);
        }
        .machine .no {
            font-size: 28px;
            font-weight: bold;
            color: #00d4ff;
        }
        .machine .model {
            font-size: 14px;
            color: #aaa;
            margin: 5px 0;
        }
        .machine .ip {
            font-size: 12px;
            color: #666;
        }
        .machine .status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
        }
        .machine.online .status {
            background: #00ff88;
            color: #000;
        }
        .machine.offline .status {
            background: #444;
            color: #888;
        }
        .machine .last-seen {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .refresh-info {
            text-align: center;
            color: #666;
            margin-top: 20px;
        }
        .crd-link {
            display: block;
            margin-top: 10px;
            color: #00d4ff;
            text-decoration: none;
            font-size: 11px;
        }
        .crd-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h1>Net8 マシンモニター</h1>

    <?php
    $online = 0;
    $offline = 0;
    foreach ($machines as $m) {
        if ($m['pc_status'] == 'online' && ($m['minutes_ago'] === null || $m['minutes_ago'] <= 5)) {
            $online++;
        } else {
            $offline++;
        }
    }
    ?>

    <div class="stats">
        <div class="stat-box online">
            <div class="number"><?= $online ?></div>
            <div>オンライン</div>
        </div>
        <div class="stat-box offline">
            <div class="number"><?= $offline ?></div>
            <div>オフライン</div>
        </div>
        <div class="stat-box total">
            <div class="number"><?= count($machines) ?></div>
            <div>合計</div>
        </div>
    </div>

    <div class="grid">
        <?php foreach ($machines as $m):
            $isOnline = ($m['pc_status'] == 'online' && ($m['minutes_ago'] === null || $m['minutes_ago'] <= 5));
            $isWarning = ($m['minutes_ago'] !== null && $m['minutes_ago'] > 2 && $m['minutes_ago'] <= 5);
            $statusClass = $isOnline ? ($isWarning ? 'warning' : 'online') : 'offline';
        ?>
        <div class="machine <?= $statusClass ?>">
            <div class="no"><?= $m['machine_no'] ?></div>
            <div class="model"><?= htmlspecialchars($m['model_name'] ?? '未設定') ?></div>
            <div class="ip"><?= htmlspecialchars($m['ip_address'] ?? '---') ?></div>
            <div class="status"><?= $isOnline ? 'ONLINE' : 'OFFLINE' ?></div>
            <?php if ($m['last_report']): ?>
            <div class="last-seen">最終: <?= $m['minutes_ago'] ?>分前</div>
            <?php endif; ?>
            <a class="crd-link" href="https://remotedesktop.google.com/access" target="_blank">
                Chrome Remote
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="refresh-info">
        自動更新: 30秒ごと | 最終更新: <?= date('H:i:s') ?>
    </div>

    <script>
        // 30秒ごとに自動リロード
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
