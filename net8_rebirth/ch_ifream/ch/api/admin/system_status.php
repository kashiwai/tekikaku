<?php
/**
 * NET8 SDK - System Status Dashboard
 * システム稼働監視ダッシュボード
 * Version: 1.0.0
 */

// 簡易認証
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 SDK - システム稼働監視</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: #fff;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        h1 {
            text-align: center;
            margin-bottom: 10px;
            font-size: 32px;
        }
        .last-update {
            text-align: center;
            opacity: 0.7;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-left: auto;
        }
        .status-ok { background: #51cf66; color: white; }
        .status-warning { background: #ffd43b; color: #333; }
        .status-error { background: #ff6b6b; color: white; }
        .metric {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .metric:last-child { border-bottom: none; }
        .metric-label {
            opacity: 0.8;
            font-size: 14px;
        }
        .metric-value {
            font-weight: 600;
            font-size: 16px;
        }
        .refresh-btn {
            display: block;
            width: 200px;
            margin: 20px auto;
            padding: 15px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .refresh-btn:hover {
            transform: scale(1.05);
        }
        .loading {
            text-align: center;
            padding: 40px;
            font-size: 18px;
        }
        .log-entry {
            background: rgba(0, 0, 0, 0.3);
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            font-size: 13px;
            font-family: 'Courier New', monospace;
        }
        .log-error { border-left: 3px solid #ff6b6b; }
        .log-warning { border-left: 3px solid #ffd43b; }
        .log-info { border-left: 3px solid #51cf66; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 NET8 SDK システム稼働監視</h1>
        <div class="last-update" id="lastUpdate">読み込み中...</div>

        <div class="grid" id="dashboard">
            <div class="loading">⏳ データを取得中...</div>
        </div>

        <button class="refresh-btn" onclick="loadDashboard()">🔄 最新データを取得</button>
    </div>

    <script>
        let autoRefreshInterval;

        // ダッシュボードデータ読み込み
        async function loadDashboard() {
            try {
                document.getElementById('lastUpdate').textContent = '⏳ 更新中...';

                // ヘルスチェックAPI呼び出し
                const response = await fetch('/api/health.php');
                const health = await response.json();

                // システムステータスAPI呼び出し（複数エンドポイントテスト）
                const [authTest, modelsTest] = await Promise.all([
                    testAuthAPI(),
                    testModelsAPI()
                ]);

                renderDashboard(health, authTest, modelsTest);

                document.getElementById('lastUpdate').textContent =
                    `最終更新: ${new Date().toLocaleString('ja-JP')} - 次回更新: 30秒後`;

            } catch (error) {
                console.error('Dashboard load error:', error);
                document.getElementById('dashboard').innerHTML = `
                    <div class="card">
                        <h2>❌ エラー</h2>
                        <p>ダッシュボードの読み込みに失敗しました: ${error.message}</p>
                    </div>
                `;
            }
        }

        // 認証APIテスト
        async function testAuthAPI() {
            const start = performance.now();
            try {
                const response = await fetch('/api/v1/auth.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ apiKey: 'pk_demo_12345' })
                });
                const data = await response.json();
                const time = Math.round(performance.now() - start);

                return {
                    status: data.success ? 'ok' : 'error',
                    response_time_ms: time,
                    environment: data.environment || 'unknown'
                };
            } catch (error) {
                return {
                    status: 'error',
                    error: error.message
                };
            }
        }

        // 機種一覧APIテスト
        async function testModelsAPI() {
            const start = performance.now();
            try {
                const response = await fetch('/api/v1/models.php?apiKey=pk_demo_12345');
                const data = await response.json();
                const time = Math.round(performance.now() - start);

                return {
                    status: data.success ? 'ok' : 'error',
                    response_time_ms: time,
                    model_count: data.count || 0
                };
            } catch (error) {
                return {
                    status: 'error',
                    error: error.message
                };
            }
        }

        // ダッシュボード描画
        function renderDashboard(health, authTest, modelsTest) {
            const overallStatus = health.status === 'ok' && authTest.status === 'ok' && modelsTest.status === 'ok'
                ? 'ok' : (health.status === 'error' ? 'error' : 'warning');

            const html = `
                <!-- 全体ステータス -->
                <div class="card" style="grid-column: 1 / -1;">
                    <h2>
                        ${overallStatus === 'ok' ? '✅' : (overallStatus === 'error' ? '❌' : '⚠️')}
                        全体ステータス
                        <span class="status-badge status-${overallStatus}">
                            ${overallStatus === 'ok' ? 'OK' : (overallStatus === 'error' ? 'ERROR' : 'WARNING')}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">全体レスポンスタイム</span>
                        <span class="metric-value">${health.response_time_ms}ms</span>
                    </div>
                </div>

                <!-- データベース -->
                <div class="card">
                    <h2>
                        💾 データベース
                        <span class="status-badge status-${health.checks.database.status}">
                            ${health.checks.database.status.toUpperCase()}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">接続状態</span>
                        <span class="metric-value">${health.checks.database.message}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">レスポンスタイム</span>
                        <span class="metric-value">${health.checks.database.response_time_ms || 0}ms</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">有効APIキー数</span>
                        <span class="metric-value">${health.checks.api_keys.active_keys || 0}個</span>
                    </div>
                </div>

                <!-- API v1 - 認証 -->
                <div class="card">
                    <h2>
                        🔐 認証API
                        <span class="status-badge status-${authTest.status}">
                            ${authTest.status.toUpperCase()}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">レスポンスタイム</span>
                        <span class="metric-value">${authTest.response_time_ms || 0}ms</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">環境</span>
                        <span class="metric-value">${authTest.environment || 'N/A'}</span>
                    </div>
                </div>

                <!-- API v1 - 機種一覧 -->
                <div class="card">
                    <h2>
                        🎮 機種一覧API
                        <span class="status-badge status-${modelsTest.status}">
                            ${modelsTest.status.toUpperCase()}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">レスポンスタイム</span>
                        <span class="metric-value">${modelsTest.response_time_ms || 0}ms</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">登録機種数</span>
                        <span class="metric-value">${modelsTest.model_count || 0}機種</span>
                    </div>
                </div>

                <!-- システムリソース -->
                <div class="card">
                    <h2>
                        💻 システムリソース
                        <span class="status-badge status-${health.checks.disk.status}">
                            ${health.checks.disk.status.toUpperCase()}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">ディスク使用率</span>
                        <span class="metric-value">${health.checks.disk.usage_percent}%</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">空き容量</span>
                        <span class="metric-value">${health.checks.disk.free_gb} GB</span>
                    </div>
                </div>

                <!-- PHP環境 -->
                <div class="card">
                    <h2>
                        🐘 PHP環境
                        <span class="status-badge status-${health.checks.php.status}">
                            ${health.checks.php.status.toUpperCase()}
                        </span>
                    </h2>
                    <div class="metric">
                        <span class="metric-label">PHPバージョン</span>
                        <span class="metric-value">${health.checks.php.version}</span>
                    </div>
                    <div class="metric">
                        <span class="metric-label">メモリ制限</span>
                        <span class="metric-value">${health.checks.php.memory_limit}</span>
                    </div>
                </div>
            `;

            document.getElementById('dashboard').innerHTML = html;
        }

        // 初期読み込み
        loadDashboard();

        // 30秒ごとに自動更新
        autoRefreshInterval = setInterval(loadDashboard, 30000);

        // ページ離脱時にクリーンアップ
        window.addEventListener('beforeunload', () => {
            if (autoRefreshInterval) {
                clearInterval(autoRefreshInterval);
            }
        });
    </script>
</body>
</html>
