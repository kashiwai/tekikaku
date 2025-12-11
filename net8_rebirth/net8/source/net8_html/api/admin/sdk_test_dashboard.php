<?php
/**
 * NET8 SDK - E2E Test Results Dashboard
 * E2Eテスト結果ダッシュボード
 * Version: 1.0.0
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

// 簡易認証
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

$pdo = get_db_connection();

// 最新10件のテスト履歴を取得
$stmt = $pdo->query("
    SELECT * FROM sdk_e2e_test_history
    ORDER BY created_at DESC
    LIMIT 10
");
$test_history = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 統計情報
$stats_stmt = $pdo->query("
    SELECT
        COUNT(*) as total_runs,
        SUM(CASE WHEN overall_status = 'ok' THEN 1 ELSE 0 END) as successful_runs,
        SUM(CASE WHEN overall_status = 'error' THEN 1 ELSE 0 END) as failed_runs,
        AVG(total_duration_ms) as avg_duration_ms,
        SUM(passed_tests) as total_passed,
        SUM(failed_tests) as total_failed
    FROM sdk_e2e_test_history
");
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

// 詳細表示用のテストID
$detail_test_id = $_GET['detail'] ?? null;
$test_details = null;
$test_steps = [];

if ($detail_test_id) {
    $detail_stmt = $pdo->prepare("SELECT * FROM sdk_e2e_test_history WHERE test_run_id = ?");
    $detail_stmt->execute([$detail_test_id]);
    $test_details = $detail_stmt->fetch(PDO::FETCH_ASSOC);

    $steps_stmt = $pdo->prepare("SELECT * FROM sdk_e2e_test_steps WHERE test_run_id = ? ORDER BY step_order");
    $steps_stmt->execute([$detail_test_id]);
    $test_steps = $steps_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 SDK - E2Eテスト結果ダッシュボード</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 1600px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .subtitle {
            text-align: center;
            opacity: 0.7;
            margin-bottom: 30px;
            font-size: 14px;
        }

        /* 統計カード */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        .stat-value {
            font-size: 36px;
            font-weight: 700;
            margin: 10px 0;
        }
        .stat-label {
            opacity: 0.8;
            font-size: 14px;
        }

        /* テスト履歴テーブル */
        .section {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }
        .section h2 {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        th {
            font-weight: 600;
            opacity: 0.8;
            font-size: 14px;
        }
        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        /* ステータスバッジ */
        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-ok { background: #51cf66; color: white; }
        .badge-error { background: #ff6b6b; color: white; }
        .badge-warning { background: #ffd43b; color: #333; }
        .badge-passed { background: #51cf66; color: white; }
        .badge-failed { background: #ff6b6b; color: white; }
        .badge-skipped { background: #868e96; color: white; }

        /* ボタン */
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            margin: 5px;
        }
        .btn:hover { opacity: 0.9; }
        .btn-small { padding: 5px 10px; font-size: 12px; }
        .btn-success { background: #51cf66; }
        .btn-danger { background: #ff6b6b; }

        /* テスト詳細モーダル */
        .detail-section {
            margin-top: 20px;
        }
        .step-card {
            background: rgba(0, 0, 0, 0.3);
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
        }
        .step-card.failed { border-left-color: #ff6b6b; }
        .step-card.passed { border-left-color: #51cf66; }
        .step-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .step-title {
            font-weight: 600;
            font-size: 16px;
        }
        .step-meta {
            font-size: 12px;
            opacity: 0.7;
            margin-bottom: 10px;
        }
        .code-block {
            background: rgba(0, 0, 0, 0.5);
            padding: 10px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            overflow-x: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        /* アクションバー */
        .action-bar {
            text-align: center;
            margin: 30px 0;
        }

        /* レスポンシブ */
        @media (max-width: 768px) {
            .stats-grid { grid-template-columns: repeat(2, 1fr); }
            table { font-size: 12px; }
            th, td { padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 NET8 SDK - E2Eテスト結果ダッシュボード</h1>
        <p class="subtitle">SDK完全自動テストの実行履歴と結果</p>

        <!-- アクションバー -->
        <div class="action-bar">
            <button class="btn btn-success" onclick="runTest()">▶️ テストを実行</button>
            <a href="?auth=<?php echo $admin_password; ?>" class="btn">🔄 更新</a>
            <a href="system_status.php?auth=<?php echo $admin_password; ?>" class="btn">📊 システム状態</a>
        </div>

        <!-- 統計情報 -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">総テスト実行数</div>
                <div class="stat-value"><?php echo $stats['total_runs'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">成功</div>
                <div class="stat-value" style="color: #51cf66;"><?php echo $stats['successful_runs'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">失敗</div>
                <div class="stat-value" style="color: #ff6b6b;"><?php echo $stats['failed_runs'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">平均実行時間</div>
                <div class="stat-value" style="font-size: 24px;"><?php echo round($stats['avg_duration_ms'] ?? 0); ?>ms</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">成功テスト数</div>
                <div class="stat-value" style="font-size: 24px; color: #51cf66;"><?php echo $stats['total_passed'] ?? 0; ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">失敗テスト数</div>
                <div class="stat-value" style="font-size: 24px; color: #ff6b6b;"><?php echo $stats['total_failed'] ?? 0; ?></div>
            </div>
        </div>

        <!-- テスト履歴 -->
        <div class="section">
            <h2>📋 最新のテスト履歴（10件）</h2>
            <table>
                <thead>
                    <tr>
                        <th>実行日時</th>
                        <th>テストID</th>
                        <th>ステータス</th>
                        <th>実行時間</th>
                        <th>成功/失敗</th>
                        <th>詳細</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($test_history)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; opacity: 0.5;">
                                テスト履歴がありません。「テストを実行」ボタンをクリックしてください。
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($test_history as $test): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($test['created_at'])); ?></td>
                                <td style="font-family: monospace; font-size: 11px;"><?php echo $test['test_run_id']; ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $test['overall_status']; ?>">
                                        <?php echo strtoupper($test['overall_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $test['total_duration_ms']; ?>ms</td>
                                <td>
                                    <span style="color: #51cf66;">✓ <?php echo $test['passed_tests']; ?></span> /
                                    <span style="color: #ff6b6b;">✗ <?php echo $test['failed_tests']; ?></span>
                                </td>
                                <td>
                                    <a href="?auth=<?php echo $admin_password; ?>&detail=<?php echo $test['test_run_id']; ?>" class="btn btn-small">
                                        詳細を見る
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- テスト詳細（選択時のみ表示） -->
        <?php if ($test_details): ?>
            <div class="section">
                <h2>🔍 テスト詳細: <?php echo $test_details['test_run_id']; ?></h2>

                <div class="detail-section">
                    <p><strong>実行日時:</strong> <?php echo $test_details['created_at']; ?></p>
                    <p><strong>総合ステータス:</strong>
                        <span class="badge badge-<?php echo $test_details['overall_status']; ?>">
                            <?php echo strtoupper($test_details['overall_status']); ?>
                        </span>
                    </p>
                    <p><strong>実行時間:</strong> <?php echo $test_details['total_duration_ms']; ?>ms</p>
                    <p><strong>成功/失敗:</strong>
                        <span style="color: #51cf66;">✓ <?php echo $test_details['passed_tests']; ?></span> /
                        <span style="color: #ff6b6b;">✗ <?php echo $test_details['failed_tests']; ?></span>
                    </p>

                    <?php if ($test_details['errors']): ?>
                        <?php $errors = json_decode($test_details['errors'], true); ?>
                        <?php if (!empty($errors)): ?>
                            <div style="margin-top: 15px; padding: 15px; background: rgba(255, 107, 107, 0.2); border-radius: 10px; border-left: 4px solid #ff6b6b;">
                                <strong>⚠️ エラー:</strong>
                                <ul style="margin-left: 20px; margin-top: 10px;">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <h3 style="margin-top: 30px; margin-bottom: 15px;">テストステップ詳細</h3>
                <?php foreach ($test_steps as $step): ?>
                    <div class="step-card <?php echo $step['status']; ?>">
                        <div class="step-header">
                            <span class="step-title"><?php echo $step['step_name']; ?></span>
                            <span class="badge badge-<?php echo $step['status']; ?>">
                                <?php echo strtoupper($step['status']); ?>
                            </span>
                        </div>
                        <div class="step-meta">
                            ステップ <?php echo $step['step_order']; ?> |
                            レスポンスタイム: <?php echo $step['response_time_ms']; ?>ms |
                            実行時刻: <?php echo $step['created_at']; ?>
                        </div>

                        <?php if ($step['request_data']): ?>
                            <p style="margin-top: 10px; margin-bottom: 5px; opacity: 0.8;">リクエストデータ:</p>
                            <div class="code-block"><?php echo htmlspecialchars(json_encode(json_decode($step['request_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                        <?php endif; ?>

                        <?php if ($step['response_data']): ?>
                            <p style="margin-top: 10px; margin-bottom: 5px; opacity: 0.8;">レスポンスデータ:</p>
                            <div class="code-block"><?php echo htmlspecialchars(json_encode(json_decode($step['response_data']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)); ?></div>
                        <?php endif; ?>

                        <?php if ($step['error_message']): ?>
                            <p style="margin-top: 10px; margin-bottom: 5px; opacity: 0.8; color: #ff6b6b;">エラーメッセージ:</p>
                            <div class="code-block" style="background: rgba(255, 107, 107, 0.2);"><?php echo htmlspecialchars($step['error_message']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <div style="text-align: center; margin-top: 20px;">
                    <a href="?auth=<?php echo $admin_password; ?>" class="btn">← テスト履歴に戻る</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function runTest() {
            const btn = event.target;
            btn.disabled = true;
            btn.textContent = '⏳ テスト実行中...';

            fetch('sdk_e2e_test.php?auth=<?php echo $admin_password; ?>')
                .then(response => response.json())
                .then(data => {
                    alert('✅ テスト完了！\n\n' +
                        'ステータス: ' + data.overall_status.toUpperCase() + '\n' +
                        '成功: ' + data.passed_tests + '\n' +
                        '失敗: ' + data.failed_tests + '\n' +
                        '実行時間: ' + data.total_duration_ms + 'ms');
                    location.reload();
                })
                .catch(error => {
                    alert('❌ テスト実行エラー:\n' + error.message);
                    btn.disabled = false;
                    btn.textContent = '▶️ テストを実行';
                });
        }
    </script>
</body>
</html>
