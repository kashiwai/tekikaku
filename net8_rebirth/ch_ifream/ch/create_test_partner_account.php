<?php
/**
 * NET8 SDK - 新規テストパートナーアカウント作成
 * Version: 1.01
 * Created: 2025-11-13
 */

require_once(__DIR__ . '/_etc/require_files.php');

header('Content-Type: application/json; charset=UTF-8');

try {
    $pdo = get_db_connection();

    // 新規テストパートナー情報
    $client_name = 'Test Partner Company (Netlify Demo)';
    $environment = 'test';
    $rate_limit = 10000;

    // APIキー生成（testプレフィックス）
    $api_key = 'pk_demo_' . bin2hex(random_bytes(16));

    echo "🚀 新規テストパートナーアカウント作成開始\n\n";
    echo "===================================\n";
    echo "クライアント名: {$client_name}\n";
    echo "環境: TEST（モックモード）\n";
    echo "レート制限: " . number_format($rate_limit) . " req/day\n";
    echo "SDKバージョン: 1.01-beta\n";
    echo "===================================\n\n";

    // api_keysテーブルに挿入
    $insertSql = "INSERT INTO api_keys (
        key_value,
        key_type,
        name,
        environment,
        rate_limit,
        is_active,
        expires_at,
        created_at
    ) VALUES (
        :key_value,
        'public',
        :name,
        :environment,
        :rate_limit,
        1,
        DATE_ADD(NOW(), INTERVAL 1 YEAR),
        NOW()
    )";

    $stmt = $pdo->prepare($insertSql);
    $success = $stmt->execute([
        'key_value' => $api_key,
        'name' => $client_name . ' - SDK v1.01',
        'environment' => $environment,
        'rate_limit' => $rate_limit
    ]);

    if (!$success) {
        throw new Exception('APIキーの挿入に失敗しました');
    }

    $api_key_id = $pdo->lastInsertId();

    echo "✅ APIキー生成完了\n";
    echo "   ID: {$api_key_id}\n";
    echo "   キー: {$api_key}\n\n";

    // 接続情報
    $base_url = 'https://mgg-webservice-production.up.railway.app';
    $sdk_url = $base_url . '/sdk/net8-sdk-beta.js';
    $demo_url = $base_url . '/sdk/demo.html';
    $auth_endpoint = $base_url . '/api/v1/auth.php';
    $game_start_endpoint = $base_url . '/api/v1/game_start.php';

    echo "📋 接続情報\n";
    echo "===================================\n";
    echo "SDK URL: {$sdk_url}\n";
    echo "デモURL: {$demo_url}\n";
    echo "認証エンドポイント: {$auth_endpoint}\n";
    echo "ゲーム開始エンドポイント: {$game_start_endpoint}\n";
    echo "===================================\n\n";

    // クレデンシャル情報をJSON形式で出力
    $credentials = [
        'success' => true,
        'client' => [
            'id' => $api_key_id,
            'name' => $client_name,
            'environment' => $environment,
            'sdk_version' => '1.01-beta'
        ],
        'credentials' => [
            'api_key' => $api_key,
            'rate_limit' => $rate_limit,
            'expires_at' => date('Y-m-d', strtotime('+1 year'))
        ],
        'endpoints' => [
            'sdk_url' => $sdk_url,
            'demo_url' => $demo_url,
            'auth' => $auth_endpoint,
            'game_start' => $game_start_endpoint
        ],
        'test_mode' => true,
        'mock_data' => [
            'enabled' => true,
            'description' => 'テスト環境では実機接続不要。モックデータで動作します。'
        ],
        'integration_guide' => [
            'step_1' => 'HTML内でSDKをロード: <script src="' . $sdk_url . '"></script>',
            'step_2' => 'SDK初期化: await Net8.init("' . $api_key . '")',
            'step_3' => 'ゲーム作成: game = Net8.createGame({ model: "HOKUTO4GO", container: "#game-container" })',
            'step_4' => 'ゲーム開始: await game.start()'
        ]
    ];

    echo "\n📦 JSON形式のクレデンシャル\n";
    echo "===================================\n";
    echo json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo "\n===================================\n\n";

    // 簡易HTMLテストコード生成
    $html_example = <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 SDK Test Integration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        #game-container {
            width: 100%;
            height: 600px;
            background: #000;
            border-radius: 10px;
            margin: 20px 0;
        }
        button {
            padding: 15px 30px;
            font-size: 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin: 10px 5px;
        }
        button:hover {
            background: #5568d3;
        }
        #log {
            background: #1e1e1e;
            color: #00ff00;
            padding: 20px;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <h1>🎮 NET8 SDK Integration Test</h1>
    <p>Test Partner Company - SDK v1.01</p>

    <button onclick="initSDK()">1. SDK初期化</button>
    <button onclick="startGame()">2. ゲーム開始</button>
    <button onclick="clearLog()">ログクリア</button>

    <div id="game-container"></div>

    <h2>📝 実行ログ</h2>
    <div id="log"></div>

    <script src="{$sdk_url}"></script>
    <script>
        let game = null;

        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const timestamp = new Date().toLocaleTimeString();
            const emoji = {
                'info': 'ℹ️',
                'success': '✅',
                'error': '❌',
                'warning': '⚠️'
            }[type] || 'ℹ️';

            logDiv.innerHTML += `[\${timestamp}] \${emoji} \${message}\n`;
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        async function initSDK() {
            try {
                log('SDK初期化開始...', 'info');
                await Net8.init('{$api_key}');
                log('✅ SDK初期化成功！', 'success');
            } catch (error) {
                log('SDK初期化失敗: ' + error.message, 'error');
                console.error(error);
            }
        }

        async function startGame() {
            try {
                if (!game) {
                    log('ゲームインスタンス作成中...', 'info');
                    game = Net8.createGame({
                        model: 'HOKUTO4GO',
                        container: '#game-container'
                    });
                    log('ゲームインスタンス作成完了', 'success');
                }

                log('ゲーム開始中...', 'info');
                await game.start();
                log('✅ ゲーム開始成功！', 'success');
            } catch (error) {
                log('ゲーム開始失敗: ' + error.message, 'error');
                console.error(error);
            }
        }

        function clearLog() {
            document.getElementById('log').innerHTML = '';
        }

        // ページロード時の初期ログ
        log('NET8 SDK テスト環境準備完了', 'success');
        log('APIキー: {$api_key}', 'info');
        log('環境: TEST（モックモード）', 'info');
    </script>
</body>
</html>
HTML;

    // HTMLファイルとして保存
    $html_file_path = __DIR__ . '/sdk/test_partner_demo.html';
    file_put_contents($html_file_path, $html_example);

    echo "✅ テスト用HTMLファイルを生成しました\n";
    echo "   ファイルパス: {$html_file_path}\n";
    echo "   アクセスURL: {$base_url}/sdk/test_partner_demo.html\n\n";

    echo "🎉 新規テストパートナーアカウント作成完了！\n\n";

    echo "📧 お客様への送付情報\n";
    echo "===================================\n";
    echo "1. APIキー: {$api_key}\n";
    echo "2. デモURL: {$demo_url}\n";
    echo "3. テスト統合URL: {$base_url}/sdk/test_partner_demo.html\n";
    echo "4. SDK URL: {$sdk_url}\n";
    echo "5. 環境: TEST（実機不要・モックモード）\n";
    echo "6. 有効期限: 1年間\n";
    echo "===================================\n";

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
?>
