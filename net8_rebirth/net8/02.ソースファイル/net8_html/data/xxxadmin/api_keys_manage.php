<?php
/**
 * NET8 SDK - APIキー管理画面（簡易版）
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 */

require_once('../../_etc/require_files_admin.php');

// 管理者ログインチェック
if (!isset($template->Session->AdminInfo)) {
    header('Location: login.php');
    exit;
}

// APIキー生成処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'generate') {
        $keyName = $_POST['key_name'] ?? 'New API Key';
        $environment = $_POST['environment'] ?? 'test';
        $prefix = $environment === 'live' ? 'pk_live_' : 'pk_test_';
        $keyValue = $prefix . bin2hex(random_bytes(16));

        $sql = (new SqlString())->setAutoConvert([$template->DB, "conv_sql"])
            ->insert('api_keys')
            ->values([
                'key_value' => [$keyValue, FD_TEXT],
                'name' => [$keyName, FD_TEXT],
                'environment' => [$environment, FD_TEXT],
                'rate_limit' => [1000, FD_NUM],
                'is_active' => [1, FD_NUM],
                'created_at' => ['NOW()', FD_RAW]
            ])
            ->createSQL("\n");

        $template->DB->query($sql);
        $message = "APIキーを生成しました: $keyValue";
    }

    if ($_POST['action'] === 'toggle') {
        $keyId = $_POST['key_id'];
        $sql = "UPDATE api_keys SET is_active = IF(is_active = 1, 0, 1) WHERE id = " . $template->DB->conv_sql($keyId, FD_NUM);
        $template->DB->query($sql);
        $message = "APIキーの状態を変更しました";
    }
}

// APIキー一覧取得
$sql = "SELECT * FROM api_keys ORDER BY created_at DESC";
$apiKeys = $template->DB->getAll($sql);

// 使用統計取得
$statsSql = "SELECT
    DATE(created_at) as date,
    COUNT(*) as request_count,
    AVG(response_time_ms) as avg_response_time
FROM api_usage_logs
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY DATE(created_at)
ORDER BY date DESC";
$stats = $template->DB->getAll($statsSql);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>APIキー管理 - NET8 SDK</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .section {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .section h2 {
            color: #667eea;
            margin-bottom: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        .key-value {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
        }

        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }

        .badge.active {
            background: #d4edda;
            color: #155724;
        }

        .badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .badge.test {
            background: #fff3cd;
            color: #856404;
        }

        .badge.live {
            background: #d1ecf1;
            color: #0c5460;
        }

        button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }

        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }

        .stat-card h3 {
            color: #667eea;
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stat-card p {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔑 APIキー管理</h1>
            <p>NET8 SDK - Beta Version</p>
        </header>

        <?php if (isset($message)): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- 新規APIキー生成 -->
        <div class="section">
            <h2>新規APIキー生成</h2>
            <form method="POST">
                <input type="hidden" name="action" value="generate">

                <div class="form-group">
                    <label>キー名</label>
                    <input type="text" name="key_name" placeholder="例: 本番環境用" required>
                </div>

                <div class="form-group">
                    <label>環境</label>
                    <select name="environment" required>
                        <option value="test">テスト環境 (pk_test_xxx)</option>
                        <option value="live">本番環境 (pk_live_xxx)</option>
                    </select>
                </div>

                <button type="submit" class="btn-primary">APIキーを生成</button>
            </form>
        </div>

        <!-- APIキー一覧 -->
        <div class="section">
            <h2>APIキー一覧</h2>
            <table>
                <thead>
                    <tr>
                        <th>キー名</th>
                        <th>APIキー</th>
                        <th>環境</th>
                        <th>状態</th>
                        <th>レート制限</th>
                        <th>最終使用</th>
                        <th>作成日</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($apiKeys as $key): ?>
                        <tr>
                            <td><?= htmlspecialchars($key['name']) ?></td>
                            <td><span class="key-value"><?= htmlspecialchars($key['key_value']) ?></span></td>
                            <td><span class="badge <?= $key['environment'] ?>"><?= strtoupper($key['environment']) ?></span></td>
                            <td><span class="badge <?= $key['is_active'] ? 'active' : 'inactive' ?>"><?= $key['is_active'] ? '有効' : '無効' ?></span></td>
                            <td><?= number_format($key['rate_limit']) ?> req/hour</td>
                            <td><?= $key['last_used_at'] ?? '未使用' ?></td>
                            <td><?= $key['created_at'] ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="key_id" value="<?= $key['id'] ?>">
                                    <button type="submit" class="btn-secondary">
                                        <?= $key['is_active'] ? '無効化' : '有効化' ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- 使用統計 -->
        <div class="section">
            <h2>使用統計（過去7日間）</h2>
            <div class="stats-grid">
                <?php foreach ($stats as $stat): ?>
                    <div class="stat-card">
                        <h3><?= number_format($stat['request_count']) ?></h3>
                        <p><?= $stat['date'] ?></p>
                        <p style="font-size: 0.9em; color: #999;">
                            平均: <?= round($stat['avg_response_time']) ?>ms
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="section">
            <h2>📚 SDKドキュメント</h2>
            <p>SDKの使い方は以下のデモページを参照してください：</p>
            <p><a href="/sdk/demo.html" style="color: #667eea; font-weight: 600;">SDK Beta Demo</a></p>
        </div>
    </div>
</body>
</html>
