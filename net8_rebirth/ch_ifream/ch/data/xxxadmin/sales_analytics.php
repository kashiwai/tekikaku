<?php
/**
 * sales_analytics.php
 *
 * 売上・利益集計画面
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/11/13
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

// メイン処理
main();

function main() {
    try {
        $template = new TemplateAdmin();

        // CSV エクスポート処理
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            ExportCSV($template);
            exit;
        }

        // 集計表示
        DispAnalytics($template);

    } catch (Exception $e) {
        echo '<h1>エラーが発生しました</h1>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        exit;
    }
}

/**
 * 集計画面表示
 */
function DispAnalytics($template) {
    // パラメータ取得
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'machine';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01'); // 今月1日
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d'); // 今日
    $model_no = isset($_GET['model_no']) ? intval($_GET['model_no']) : 0;
    $owner_no = isset($_GET['owner_no']) ? intval($_GET['owner_no']) : 0;

    // 機種リスト取得
    $sql = "SELECT model_no, model_name FROM mst_model WHERE del_flg = 0 ORDER BY model_no";
    $models = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // オーナーリスト取得
    $sql = "SELECT owner_no, owner_nickname FROM mst_owner WHERE del_flg = 0 ORDER BY owner_no";
    $owners = $template->DB->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // 集計データ取得
    $analytics_data = [];
    switch ($tab) {
        case 'machine':
            $analytics_data = GetMachineAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            break;
        case 'daily':
            $analytics_data = GetDailyAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            break;
        case 'monthly':
            $analytics_data = GetMonthlyAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            break;
    }
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>売上・利益集計 - NET8 管理画面</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 32px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }

        .subtitle {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 32px;
        }

        /* タブ */
        .tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            border-bottom: 2px solid #e2e8f0;
        }

        .tab {
            padding: 12px 24px;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            color: #64748b;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .tab:hover {
            color: #667eea;
        }

        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }

        /* フィルター */
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #334155;
        }

        .filter-group input,
        .filter-group select {
            padding: 10px;
            border: 2px solid #e2e8f0;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-actions {
            display: flex;
            gap: 8px;
            align-items: flex-end;
        }

        /* ボタン */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #475569;
        }

        .btn-secondary:hover {
            background: #cbd5e1;
        }

        /* サマリーカード */
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .summary-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            border-radius: 12px;
            color: white;
        }

        .summary-card.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .summary-card.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .summary-card.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .summary-label {
            font-size: 13px;
            opacity: 0.9;
            margin-bottom: 8px;
        }

        .summary-value {
            font-size: 28px;
            font-weight: 700;
        }

        .summary-sub {
            font-size: 12px;
            opacity: 0.8;
            margin-top: 4px;
        }

        /* テーブル */
        .data-table-wrapper {
            overflow-x: auto;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: #f8fafc;
        }

        .data-table th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            color: #334155;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            color: #475569;
            font-size: 14px;
        }

        .data-table tr:hover {
            background: #f8fafc;
        }

        .text-right {
            text-align: right;
        }

        .text-profit {
            color: #10b981;
            font-weight: 600;
        }

        .text-loss {
            color: #ef4444;
            font-weight: 600;
        }

        .text-large {
            font-size: 16px;
            font-weight: 700;
        }

        /* トータル行 */
        .total-row {
            background: #f1f5f9 !important;
            font-weight: 700;
        }

        .total-row td {
            border-top: 3px solid #667eea;
            padding: 16px 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>💰 売上・利益集計</h1>
            <p class="subtitle">機体別・日別・月別の売上と利益を集計します</p>

            <!-- タブ -->
            <div class="tabs">
                <a href="?tab=machine&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&model_no=<?= $model_no ?>&owner_no=<?= $owner_no ?>"
                   class="tab <?= $tab === 'machine' ? 'active' : '' ?>">
                    🎰 機体別集計
                </a>
                <a href="?tab=daily&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&model_no=<?= $model_no ?>&owner_no=<?= $owner_no ?>"
                   class="tab <?= $tab === 'daily' ? 'active' : '' ?>">
                    📅 日別集計
                </a>
                <a href="?tab=monthly&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&model_no=<?= $model_no ?>&owner_no=<?= $owner_no ?>"
                   class="tab <?= $tab === 'monthly' ? 'active' : '' ?>">
                    📊 月別集計
                </a>
            </div>

            <!-- フィルター -->
            <form method="GET" action="">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
                <div class="filters">
                    <div class="filter-group">
                        <label>開始日</label>
                        <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>終了日</label>
                        <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>" required>
                    </div>
                    <div class="filter-group">
                        <label>機種</label>
                        <select name="model_no">
                            <option value="0">全機種</option>
                            <?php foreach ($models as $model): ?>
                                <option value="<?= $model['model_no'] ?>" <?= $model_no == $model['model_no'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($model['model_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>オーナー</label>
                        <select name="owner_no">
                            <option value="0">全オーナー</option>
                            <?php foreach ($owners as $owner): ?>
                                <option value="<?= $owner['owner_no'] ?>" <?= $owner_no == $owner['owner_no'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($owner['owner_nickname']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn btn-primary">🔍 検索</button>
                        <a href="?tab=<?= $tab ?>&export=csv&date_from=<?= urlencode($date_from) ?>&date_to=<?= urlencode($date_to) ?>&model_no=<?= $model_no ?>&owner_no=<?= $owner_no ?>"
                           class="btn btn-success">📥 CSV出力</a>
                    </div>
                </div>
            </form>

            <?php if (!empty($analytics_data)): ?>
                <!-- サマリーカード -->
                <?php
                $total_sales = array_sum(array_column($analytics_data['rows'], 'in_credit'));
                $total_payout = array_sum(array_column($analytics_data['rows'], 'out_credit'));
                $total_profit = $total_sales - $total_payout;
                $profit_rate = $total_sales > 0 ? ($total_profit / $total_sales * 100) : 0;
                $total_plays = array_sum(array_column($analytics_data['rows'], 'play_count'));
                ?>
                <div class="summary-cards">
                    <div class="summary-card">
                        <div class="summary-label">💴 総売上（IN）</div>
                        <div class="summary-value"><?= number_format($total_sales) ?></div>
                        <div class="summary-sub">枚</div>
                    </div>
                    <div class="summary-card">
                        <div class="summary-label">💸 総払出（OUT）</div>
                        <div class="summary-value"><?= number_format($total_payout) ?></div>
                        <div class="summary-sub">枚</div>
                    </div>
                    <div class="summary-card <?= $total_profit >= 0 ? 'success' : 'danger' ?>">
                        <div class="summary-label">💰 総利益</div>
                        <div class="summary-value"><?= $total_profit >= 0 ? '+' : '' ?><?= number_format($total_profit) ?></div>
                        <div class="summary-sub">枚（<?= number_format($profit_rate, 1) ?>%）</div>
                    </div>
                    <div class="summary-card warning">
                        <div class="summary-label">🎮 総プレイ回数</div>
                        <div class="summary-value"><?= number_format($total_plays) ?></div>
                        <div class="summary-sub">回</div>
                    </div>
                </div>

                <!-- データテーブル -->
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <?php foreach ($analytics_data['headers'] as $header): ?>
                                    <th><?= htmlspecialchars($header) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($analytics_data['rows'] as $row): ?>
                                <tr>
                                    <?php foreach ($analytics_data['columns'] as $col): ?>
                                        <?php if (in_array($col, ['in_credit', 'out_credit', 'profit', 'play_count'])): ?>
                                            <td class="text-right">
                                                <?php if ($col === 'profit'): ?>
                                                    <span class="<?= $row[$col] >= 0 ? 'text-profit' : 'text-loss' ?>">
                                                        <?= $row[$col] >= 0 ? '+' : '' ?><?= number_format($row[$col]) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <?= number_format($row[$col]) ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php elseif ($col === 'profit_rate'): ?>
                                            <td class="text-right">
                                                <span class="<?= $row[$col] >= 0 ? 'text-profit' : 'text-loss' ?>">
                                                    <?= number_format($row[$col], 1) ?>%
                                                </span>
                                            </td>
                                        <?php else: ?>
                                            <td><?= htmlspecialchars($row[$col]) ?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            <!-- 合計行 -->
                            <tr class="total-row">
                                <td colspan="<?= count($analytics_data['columns']) - 5 ?>">合計</td>
                                <td class="text-right text-large"><?= number_format($total_sales) ?></td>
                                <td class="text-right text-large"><?= number_format($total_payout) ?></td>
                                <td class="text-right text-large">
                                    <span class="<?= $total_profit >= 0 ? 'text-profit' : 'text-loss' ?>">
                                        <?= $total_profit >= 0 ? '+' : '' ?><?= number_format($total_profit) ?>
                                    </span>
                                </td>
                                <td class="text-right text-large">
                                    <span class="<?= $profit_rate >= 0 ? 'text-profit' : 'text-loss' ?>">
                                        <?= number_format($profit_rate, 1) ?>%
                                    </span>
                                </td>
                                <td class="text-right text-large"><?= number_format($total_plays) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div style="padding: 40px; text-align: center; color: #64748b;">
                    📊 指定期間のデータがありません
                </div>
            <?php endif; ?>

            <div style="margin-top: 24px;">
                <a href="machine_control.php" class="btn btn-secondary">← 台管理画面に戻る</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php
}

/**
 * 機体別集計
 */
function GetMachineAnalytics($template, $date_from, $date_to, $model_no, $owner_no) {
    $where = ["DATE(lp.play_dt) BETWEEN :date_from AND :date_to"];
    $params = ['date_from' => $date_from, 'date_to' => $date_to];

    if ($model_no > 0) {
        $where[] = "dm.model_no = :model_no";
        $params['model_no'] = $model_no;
    }

    if ($owner_no > 0) {
        $where[] = "dm.owner_no = :owner_no";
        $params['owner_no'] = $owner_no;
    }

    $where_clause = implode(' AND ', $where);

    $sql = "SELECT
                dm.machine_no,
                dm.machine_cd,
                COALESCE(dm.machine_name, CONCAT('マシン', dm.machine_no)) as machine_name,
                mm.model_name,
                mo.owner_nickname,
                SUM(lp.in_credit) as in_credit,
                SUM(lp.out_credit) as out_credit,
                SUM(lp.in_credit) - SUM(lp.out_credit) as profit,
                CASE
                    WHEN SUM(lp.in_credit) > 0
                    THEN ((SUM(lp.in_credit) - SUM(lp.out_credit)) / SUM(lp.in_credit) * 100)
                    ELSE 0
                END as profit_rate,
                SUM(lp.play_count) as play_count
            FROM log_play lp
            INNER JOIN dat_machine dm ON lp.machine_no = dm.machine_no
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_owner mo ON dm.owner_no = mo.owner_no
            WHERE {$where_clause}
            GROUP BY dm.machine_no, dm.machine_cd, dm.machine_name, mm.model_name, mo.owner_nickname
            ORDER BY dm.machine_no";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'headers' => ['台番号', '台コード', '台名', '機種', 'オーナー', '売上（IN）', '払出（OUT）', '利益', '利益率', 'プレイ回数'],
        'columns' => ['machine_no', 'machine_cd', 'machine_name', 'model_name', 'owner_nickname', 'in_credit', 'out_credit', 'profit', 'profit_rate', 'play_count'],
        'rows' => $rows
    ];
}

/**
 * 日別集計
 */
function GetDailyAnalytics($template, $date_from, $date_to, $model_no, $owner_no) {
    $where = ["DATE(lp.play_dt) BETWEEN :date_from AND :date_to"];
    $params = ['date_from' => $date_from, 'date_to' => $date_to];

    if ($model_no > 0) {
        $where[] = "dm.model_no = :model_no";
        $params['model_no'] = $model_no;
    }

    if ($owner_no > 0) {
        $where[] = "dm.owner_no = :owner_no";
        $params['owner_no'] = $owner_no;
    }

    $where_clause = implode(' AND ', $where);

    $sql = "SELECT
                DATE(lp.play_dt) as play_date,
                DAYNAME(lp.play_dt) as day_of_week,
                COUNT(DISTINCT lp.machine_no) as machine_count,
                SUM(lp.in_credit) as in_credit,
                SUM(lp.out_credit) as out_credit,
                SUM(lp.in_credit) - SUM(lp.out_credit) as profit,
                CASE
                    WHEN SUM(lp.in_credit) > 0
                    THEN ((SUM(lp.in_credit) - SUM(lp.out_credit)) / SUM(lp.in_credit) * 100)
                    ELSE 0
                END as profit_rate,
                SUM(lp.play_count) as play_count
            FROM log_play lp
            INNER JOIN dat_machine dm ON lp.machine_no = dm.machine_no
            WHERE {$where_clause}
            GROUP BY DATE(lp.play_dt)
            ORDER BY play_date DESC";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 曜日を日本語に変換
    $day_map = [
        'Monday' => '月', 'Tuesday' => '火', 'Wednesday' => '水', 'Thursday' => '木',
        'Friday' => '金', 'Saturday' => '土', 'Sunday' => '日'
    ];

    foreach ($rows as &$row) {
        $row['day_of_week'] = isset($day_map[$row['day_of_week']]) ? $day_map[$row['day_of_week']] : $row['day_of_week'];
    }

    return [
        'headers' => ['日付', '曜日', '稼働台数', '売上（IN）', '払出（OUT）', '利益', '利益率', 'プレイ回数'],
        'columns' => ['play_date', 'day_of_week', 'machine_count', 'in_credit', 'out_credit', 'profit', 'profit_rate', 'play_count'],
        'rows' => $rows
    ];
}

/**
 * 月別集計
 */
function GetMonthlyAnalytics($template, $date_from, $date_to, $model_no, $owner_no) {
    $where = ["DATE(lp.play_dt) BETWEEN :date_from AND :date_to"];
    $params = ['date_from' => $date_from, 'date_to' => $date_to];

    if ($model_no > 0) {
        $where[] = "dm.model_no = :model_no";
        $params['model_no'] = $model_no;
    }

    if ($owner_no > 0) {
        $where[] = "dm.owner_no = :owner_no";
        $params['owner_no'] = $owner_no;
    }

    $where_clause = implode(' AND ', $where);

    $sql = "SELECT
                DATE_FORMAT(lp.play_dt, '%Y-%m') as month,
                COUNT(DISTINCT lp.machine_no) as machine_count,
                COUNT(DISTINCT DATE(lp.play_dt)) as day_count,
                SUM(lp.in_credit) as in_credit,
                SUM(lp.out_credit) as out_credit,
                SUM(lp.in_credit) - SUM(lp.out_credit) as profit,
                CASE
                    WHEN SUM(lp.in_credit) > 0
                    THEN ((SUM(lp.in_credit) - SUM(lp.out_credit)) / SUM(lp.in_credit) * 100)
                    ELSE 0
                END as profit_rate,
                SUM(lp.play_count) as play_count
            FROM log_play lp
            INNER JOIN dat_machine dm ON lp.machine_no = dm.machine_no
            WHERE {$where_clause}
            GROUP BY DATE_FORMAT(lp.play_dt, '%Y-%m')
            ORDER BY month DESC";

    $stmt = $template->DB->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    return [
        'headers' => ['月', '稼働台数', '稼働日数', '売上（IN）', '払出（OUT）', '利益', '利益率', 'プレイ回数'],
        'columns' => ['month', 'machine_count', 'day_count', 'in_credit', 'out_credit', 'profit', 'profit_rate', 'play_count'],
        'rows' => $rows
    ];
}

/**
 * CSV エクスポート
 */
function ExportCSV($template) {
    $tab = isset($_GET['tab']) ? $_GET['tab'] : 'machine';
    $date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
    $date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
    $model_no = isset($_GET['model_no']) ? intval($_GET['model_no']) : 0;
    $owner_no = isset($_GET['owner_no']) ? intval($_GET['owner_no']) : 0;

    // 集計データ取得
    $analytics_data = [];
    switch ($tab) {
        case 'machine':
            $analytics_data = GetMachineAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            $filename = "machine_analytics_{$date_from}_{$date_to}.csv";
            break;
        case 'daily':
            $analytics_data = GetDailyAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            $filename = "daily_analytics_{$date_from}_{$date_to}.csv";
            break;
        case 'monthly':
            $analytics_data = GetMonthlyAnalytics($template, $date_from, $date_to, $model_no, $owner_no);
            $filename = "monthly_analytics_{$date_from}_{$date_to}.csv";
            break;
    }

    // CSV ヘッダー設定
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // BOM付加（Excel対応）
    echo "\xEF\xBB\xBF";

    $output = fopen('php://output', 'w');

    // ヘッダー行
    fputcsv($output, $analytics_data['headers']);

    // データ行
    foreach ($analytics_data['rows'] as $row) {
        $csv_row = [];
        foreach ($analytics_data['columns'] as $col) {
            $csv_row[] = $row[$col];
        }
        fputcsv($output, $csv_row);
    }

    fclose($output);
}
?>
