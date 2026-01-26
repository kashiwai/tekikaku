<?php
/**
 * NET8 SDK - Client Configuration Export
 * お客様環境情報データ出力（JSON/CSV）
 * Version: 1.0.0
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

// 簡易認証
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

$key_id = (int)($_GET['key_id'] ?? 0);
$format = $_GET['format'] ?? 'json'; // json or csv

if (!$key_id) {
    die('Error: key_id is required');
}

try {
    $pdo = get_db_connection();

    // APIキー情報取得
    $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE id = :id");
    $stmt->execute(['id' => $key_id]);
    $key_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key_data) {
        die('Error: API Key not found');
    }

    // 環境情報構築
    $is_mock = ($key_data['environment'] === 'test' || $key_data['environment'] === 'staging');
    $client_name = preg_replace('/ - (test|staging|live)$/i', '', $key_data['name']);

    $base_url = 'https://mgg-webservice-production.up.railway.app';

    $config_data = [
        'client_info' => [
            'company_name' => $client_name,
            'api_key_id' => $key_data['id'],
            'issued_date' => date('Y-m-d', strtotime($key_data['created_at'])),
            'contact_email' => 'support@net8.example.com'
        ],
        'credentials' => [
            'api_key' => $key_data['key_value'],
            'environment' => $key_data['environment'],
            'mock_mode' => $is_mock
        ],
        'limits' => [
            'rate_limit_per_day' => (int)$key_data['rate_limit'],
            'expires_at' => $key_data['expires_at']
        ],
        'endpoints' => [
            'base_url' => $base_url,
            'auth' => $base_url . '/api/v1/auth.php',
            'models' => $base_url . '/api/v1/models.php',
            'game_start' => $base_url . '/api/v1/game_start.php',
            'sdk_url' => $base_url . '/sdk/net8-sdk-beta.js',
            'demo_url' => $base_url . '/sdk/demo.html'
        ],
        'available_models' => [
            ['id' => 'HOKUTO4GO', 'name' => '北斗の拳 初号機', 'category' => 'slot'],
            ['id' => 'ZENIGATA01', 'name' => '主役は銭形', 'category' => 'slot'],
            ['id' => 'MILLIONGOD01', 'name' => 'ミリオンゴッド4号機', 'category' => 'slot']
        ],
        'sample_code' => [
            'html_basic' => "<!DOCTYPE html>\n<html>\n<head>\n  <script src=\"{$base_url}/sdk/net8-sdk-beta.js\"></script>\n</head>\n<body>\n  <div id=\"game\"></div>\n  <script>\n    Net8.init('{$key_data['key_value']}').then(() => {\n      Net8.createGame({ model: 'HOKUTO4GO', container: '#game' }).start();\n    });\n  </script>\n</body>\n</html>",
            'curl_auth' => "curl -X POST {$base_url}/api/v1/auth.php \\\n  -H \"Content-Type: application/json\" \\\n  -d '{\"apiKey\":\"{$key_data['key_value']}\"}'"
        ],
        'support' => [
            'documentation' => [
                'user_manual' => 'NET8_SDK_USER_MANUAL_v1.01.md',
                'technical_manual' => 'NET8_SDK_TECHNICAL_MANUAL_v1.01.md',
                'api_reference' => 'NET8_JAVASCRIPT_SDK_SPEC.md'
            ],
            'contact' => [
                'email' => 'support@net8.example.com',
                'documentation_url' => $base_url . '/docs/'
            ]
        ]
    ];

    // 出力形式に応じて処理
    if ($format === 'csv') {
        // CSV出力
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="NET8_Client_Config_' . urlencode($client_name) . '.csv"');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo "項目,値\n";
        echo "お客様名,{$client_name}\n";
        echo "APIキー,{$key_data['key_value']}\n";
        echo "環境,{$key_data['environment']}\n";
        echo "モックモード," . ($is_mock ? 'はい' : 'いいえ') . "\n";
        echo "レート制限,{$key_data['rate_limit']} リクエスト/日\n";
        echo "有効期限,{$key_data['expires_at']}\n";
        echo "ベースURL,{$base_url}\n";
        echo "SDK URL,{$base_url}/sdk/net8-sdk-beta.js\n";
        echo "デモURL,{$base_url}/sdk/demo.html\n";
        echo "\n機種ID,機種名,カテゴリ\n";
        foreach ($config_data['available_models'] as $model) {
            echo "{$model['id']},{$model['name']},{$model['category']}\n";
        }

    } else {
        // JSON出力
        header('Content-Type: application/json; charset=UTF-8');
        header('Content-Disposition: attachment; filename="NET8_Client_Config_' . urlencode($client_name) . '.json"');

        echo json_encode($config_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
