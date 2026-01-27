<?php
/**
 * machine_status確認ページ
 * DBの実際の値を確認
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();

    // 全マシンのmachine_statusを取得
    $sql = "SELECT
                machine_no,
                name,
                machine_status,
                camera_no,
                model_no,
                CASE
                    WHEN machine_status = 0 THEN '停止中'
                    WHEN machine_status = 1 THEN '稼働中'
                    WHEN machine_status = 2 THEN 'メンテナンス中'
                    ELSE '不明'
                END as status_label
            FROM dat_machine
            ORDER BY machine_no ASC";

    $machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

} catch (Exception $e) {
    die("エラー: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>machine_status 確認</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #4361ee;
            color: white;
            font-weight: 600;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-0 { color: #666; }
        .status-1 { color: #10b981; font-weight: bold; }
        .status-2 { color: #f59e0b; font-weight: bold; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #4361ee;
        }
        .summary-card h3 {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }
        .summary-card p {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background: #4361ee;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        .back-link:hover {
            background: #3a56d4;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 machine_status 確認ページ</h1>

        <?php
        // 集計
        $total = count($machines);
        $status_0 = 0;
        $status_1 = 0;
        $status_2 = 0;

        foreach ($machines as $m) {
            if ($m['machine_status'] == 0) $status_0++;
            if ($m['machine_status'] == 1) $status_1++;
            if ($m['machine_status'] == 2) $status_2++;
        }
        ?>

        <div class="summary">
            <div class="summary-card">
                <h3>総マシン数</h3>
                <p><?= $total ?></p>
            </div>
            <div class="summary-card">
                <h3>停止中 (0)</h3>
                <p style="color: #666;"><?= $status_0 ?></p>
            </div>
            <div class="summary-card">
                <h3>稼働中 (1)</h3>
                <p style="color: #10b981;"><?= $status_1 ?></p>
            </div>
            <div class="summary-card">
                <h3>メンテナンス中 (2)</h3>
                <p style="color: #f59e0b;"><?= $status_2 ?></p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>マシン番号</th>
                    <th>名前</th>
                    <th>machine_status (数値)</th>
                    <th>状態</th>
                    <th>camera_no</th>
                    <th>model_no</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($machines as $m): ?>
                <tr>
                    <td><strong><?= $m['machine_no'] ?></strong></td>
                    <td><?= htmlspecialchars($m['name'] ?: '-') ?></td>
                    <td class="status-<?= $m['machine_status'] ?>">
                        <strong><?= $m['machine_status'] ?></strong>
                    </td>
                    <td class="status-<?= $m['machine_status'] ?>">
                        <?= $m['status_label'] ?>
                    </td>
                    <td><?= $m['camera_no'] ?: '-' ?></td>
                    <td><?= $m['model_no'] ?: '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="machine_control_v2.php" class="back-link">← 管理画面に戻る</a>
    </div>
</body>
</html>
