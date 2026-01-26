<?php
/**
 * Agent Control Panel
 * エージェント管理・コマンド送信UI
 */
require_once __DIR__ . '/../../_etc/require_files.php';

$pdo = get_db_connection();

// APIキー（管理画面用）
$adminApiKey = 'admin_dev_key_2024';

// アクション処理
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_command') {
        $agentId = $_POST['agent_id'] ?? '';
        $command = $_POST['command'] ?? '';
        $broadcast = isset($_POST['broadcast']);

        if ($broadcast) {
            // 全オンラインエージェントに送信
            $stmt = $pdo->query("SELECT agent_id FROM agents WHERE status = 'online'");
            $agentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $agentIds = [$agentId];
        }

        foreach ($agentIds as $aid) {
            if (!empty($aid) && !empty($command)) {
                $sql = "INSERT INTO command_queue (agent_id, command, created_by) VALUES (:aid, :cmd, 'admin')";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':aid' => $aid, ':cmd' => $command]);
            }
        }
        $message = count($agentIds) . '台にコマンドを送信しました';
    }
}

// エージェント一覧取得
$sql = "SELECT a.*,
        dm.machine_no,
        mm.model_name,
        TIMESTAMPDIFF(MINUTE, a.last_seen, NOW()) as minutes_ago,
        (SELECT COUNT(*) FROM command_queue cq WHERE cq.agent_id = a.agent_id AND cq.status = 'pending') as pending
        FROM agents a
        LEFT JOIN dat_machine dm ON CAST(SUBSTRING_INDEX(a.agent_id, '-', -1) AS UNSIGNED) = dm.machine_no
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        ORDER BY a.agent_id";
$agents = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

// 最近の結果
$sql = "SELECT cr.*, cq.command, a.agent_id
        FROM command_results cr
        JOIN command_queue cq ON cr.command_id = cq.id
        JOIN agents a ON cr.agent_id = a.agent_id
        ORDER BY cr.created_at DESC
        LIMIT 20";
$recentResults = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$onlineCount = count(array_filter($agents, function($a) {
    return $a['status'] === 'online' && ($a['minutes_ago'] === null || $a['minutes_ago'] <= 5);
}));
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Net8 Agent Control</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, sans-serif;
            background: #0f0f1a;
            color: #e0e0e0;
            padding: 20px;
        }
        h1 { color: #00d4ff; margin-bottom: 20px; }
        h2 { color: #00d4ff; margin: 30px 0 15px; font-size: 18px; }

        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat {
            background: #1a1a2e;
            padding: 20px 30px;
            border-radius: 10px;
            text-align: center;
        }
        .stat .num { font-size: 36px; font-weight: bold; color: #00d4ff; }
        .stat.online .num { color: #00ff88; }
        .stat.offline .num { color: #ff4444; }

        .message {
            background: #00ff88;
            color: #000;
            padding: 10px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .command-form {
            background: #1a1a2e;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; align-items: center; }
        select, input[type="text"], textarea {
            background: #0f0f1a;
            border: 1px solid #333;
            color: #fff;
            padding: 10px;
            border-radius: 5px;
            font-size: 14px;
        }
        select { min-width: 200px; }
        input[type="text"], textarea { flex: 1; }
        textarea { height: 60px; font-family: monospace; resize: vertical; }
        button {
            background: #00d4ff;
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        button:hover { background: #00a8cc; }
        button.danger { background: #ff4444; }
        button.danger:hover { background: #cc3333; }

        label { display: flex; align-items: center; gap: 5px; }
        input[type="checkbox"] { width: 18px; height: 18px; }

        .quick-commands { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px; }
        .quick-cmd {
            background: #0f3460;
            color: #00d4ff;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
        }
        .quick-cmd:hover { background: #1a4a80; }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #1a1a2e;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 30px;
        }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #0f3460; color: #00d4ff; }
        tr:hover { background: #252540; }

        .status-online { color: #00ff88; }
        .status-offline { color: #666; }
        .status-pending { color: #ffaa00; }

        .output-preview {
            max-width: 400px;
            max-height: 60px;
            overflow: hidden;
            font-family: monospace;
            font-size: 11px;
            background: #0a0a15;
            padding: 5px;
            border-radius: 3px;
            white-space: pre-wrap;
            word-break: break-all;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.8);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: #1a1a2e;
            padding: 30px;
            border-radius: 10px;
            max-width: 800px;
            max-height: 80vh;
            overflow: auto;
            width: 90%;
        }
        .modal-content h3 { margin-bottom: 15px; }
        .modal-content pre {
            background: #0a0a15;
            padding: 15px;
            border-radius: 5px;
            overflow: auto;
            white-space: pre-wrap;
            font-size: 12px;
        }
        .close-btn {
            float: right;
            background: none;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div style="margin-bottom: 20px; display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="index.php" style="background: #333; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">← ダッシュボード</a>
        <a href="machine_control.php" style="background: #0066cc; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">🖥️ マシンコントロール</a>
        <a href="machine_monitor.php" style="background: #00cc66; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">📊 マシンモニター</a>
        <a href="menu.php" style="background: #666; color: #fff; padding: 10px 20px; border-radius: 5px; text-decoration: none;">📋 全メニュー</a>
    </div>
    <h1>Net8 Agent Control Panel</h1>

    <?php if ($message): ?>
    <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="stats">
        <div class="stat online">
            <div class="num"><?= $onlineCount ?></div>
            <div>オンライン</div>
        </div>
        <div class="stat offline">
            <div class="num"><?= count($agents) - $onlineCount ?></div>
            <div>オフライン</div>
        </div>
        <div class="stat">
            <div class="num"><?= count($agents) ?></div>
            <div>総エージェント</div>
        </div>
    </div>

    <h2>コマンド送信</h2>
    <form method="POST" class="command-form">
        <input type="hidden" name="action" value="send_command">
        <div class="form-row">
            <select name="agent_id" id="agentSelect">
                <option value="">-- エージェント選択 --</option>
                <?php foreach ($agents as $a): ?>
                <?php if ($a['status'] === 'online'): ?>
                <option value="<?= htmlspecialchars($a['agent_id']) ?>">
                    <?= htmlspecialchars($a['agent_id']) ?>
                    <?= $a['model_name'] ? "({$a['model_name']})" : '' ?>
                </option>
                <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <label>
                <input type="checkbox" name="broadcast" id="broadcastCheck">
                全オンラインに送信
            </label>
        </div>
        <div class="form-row">
            <textarea name="command" id="commandInput" placeholder="実行するコマンドを入力..."></textarea>
        </div>
        <div class="form-row">
            <button type="submit">送信</button>
        </div>
        <div class="quick-commands">
            <span class="quick-cmd" onclick="setCommand('ipconfig /all')">ipconfig</span>
            <span class="quick-cmd" onclick="setCommand('hostname')">hostname</span>
            <span class="quick-cmd" onclick="setCommand('Get-Process chrome')">Chrome確認</span>
            <span class="quick-cmd" onclick="setCommand('Get-NetIPAddress -AddressFamily IPv4')">IP詳細</span>
            <span class="quick-cmd" onclick="setCommand('getmac')">MAC取得</span>
            <span class="quick-cmd" onclick="setCommand('systeminfo | Select-String \"OS\"')">OS情報</span>
            <span class="quick-cmd" onclick="setCommand('Restart-Computer -Force')">PC再起動</span>
        </div>
    </form>

    <h2>エージェント一覧</h2>
    <table>
        <thead>
            <tr>
                <th>Agent ID</th>
                <th>機種</th>
                <th>IP</th>
                <th>MAC</th>
                <th>状態</th>
                <th>最終通信</th>
                <th>待機コマンド</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($agents as $a):
                $isOnline = $a['status'] === 'online' && ($a['minutes_ago'] === null || $a['minutes_ago'] <= 5);
            ?>
            <tr>
                <td><?= htmlspecialchars($a['agent_id']) ?></td>
                <td><?= htmlspecialchars($a['model_name'] ?? '-') ?></td>
                <td><?= htmlspecialchars($a['ip_address'] ?? '-') ?></td>
                <td><?= htmlspecialchars($a['mac_address'] ?? '-') ?></td>
                <td class="<?= $isOnline ? 'status-online' : 'status-offline' ?>">
                    <?= $isOnline ? '● Online' : '○ Offline' ?>
                </td>
                <td><?= $a['minutes_ago'] !== null ? "{$a['minutes_ago']}分前" : '-' ?></td>
                <td class="<?= $a['pending'] > 0 ? 'status-pending' : '' ?>">
                    <?= $a['pending'] ?>件
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>最近の実行結果</h2>
    <table>
        <thead>
            <tr>
                <th>日時</th>
                <th>Agent</th>
                <th>コマンド</th>
                <th>結果プレビュー</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recentResults as $r): ?>
            <tr>
                <td><?= htmlspecialchars($r['created_at']) ?></td>
                <td><?= htmlspecialchars($r['agent_id']) ?></td>
                <td><code><?= htmlspecialchars(substr($r['command'], 0, 50)) ?></code></td>
                <td>
                    <div class="output-preview"><?= htmlspecialchars(substr($r['output'], 0, 200)) ?></div>
                </td>
                <td>
                    <button type="button" onclick="showOutput(<?= $r['id'] ?>)">詳細</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- 結果詳細モーダル -->
    <div class="modal" id="outputModal">
        <div class="modal-content">
            <button class="close-btn" onclick="closeModal()">&times;</button>
            <h3>実行結果詳細</h3>
            <pre id="outputContent"></pre>
        </div>
    </div>

    <script>
        function setCommand(cmd) {
            document.getElementById('commandInput').value = cmd;
        }

        document.getElementById('broadcastCheck').addEventListener('change', function() {
            document.getElementById('agentSelect').disabled = this.checked;
        });

        const outputs = <?= json_encode(array_column($recentResults, 'output', 'id')) ?>;

        function showOutput(id) {
            document.getElementById('outputContent').textContent = outputs[id] || 'No output';
            document.getElementById('outputModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('outputModal').classList.remove('active');
        }

        document.getElementById('outputModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });

        // 30秒で自動リロード
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>
