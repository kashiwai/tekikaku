<?php
/**
 * NET8 SDK - Automated Monitoring Script
 * 自動監視スクリプト（Cron実行用）
 * Version: 1.0.0
 *
 * 使い方:
 * php monitoring_check.php
 * または
 * curl https://your-domain.com/api/admin/monitoring_check.php?auth=net8_admin_2025
 */

// CLI実行かWeb実行かを判定
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    // Web実行の場合は認証チェック
    $admin_password = $_GET['auth'] ?? '';
    if ($admin_password !== 'net8_admin_2025') {
        http_response_code(403);
        die('Access Denied');
    }
    header('Content-Type: application/json; charset=UTF-8');
}

$check_results = [
    'timestamp' => date('Y-m-d H:i:s'),
    'overall_status' => 'ok',
    'checks' => [],
    'errors' => [],
    'warnings' => []
];

$base_url = 'https://mgg-webservice-production.up.railway.app';

// カラー出力用（CLI時）
function output($message, $type = 'info') {
    global $is_cli, $check_results;

    if ($is_cli) {
        $colors = [
            'ok' => "\033[32m",      // Green
            'error' => "\033[31m",   // Red
            'warning' => "\033[33m", // Yellow
            'info' => "\033[36m",    // Cyan
            'reset' => "\033[0m"
        ];

        $prefix = [
            'ok' => '✅',
            'error' => '❌',
            'warning' => '⚠️',
            'info' => 'ℹ️'
        ];

        echo $colors[$type] . $prefix[$type] . ' ' . $message . $colors['reset'] . "\n";
    }
}

output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');
output("NET8 SDK 自動監視チェック開始", 'info');
output("実行時刻: " . date('Y-m-d H:i:s'), 'info');
output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');

// 1. ヘルスチェックAPI
output("\n[1/4] ヘルスチェックAPI確認中...", 'info');
try {
    $health_url = $base_url . '/api/health.php';
    $health_response = @file_get_contents($health_url);

    if ($health_response === false) {
        throw new Exception('Health check API unreachable');
    }

    $health_data = json_decode($health_response, true);

    if ($health_data['status'] === 'ok') {
        output("ヘルスチェック: OK (レスポンスタイム: {$health_data['response_time_ms']}ms)", 'ok');
        $check_results['checks']['health'] = [
            'status' => 'ok',
            'response_time_ms' => $health_data['response_time_ms']
        ];
    } else {
        output("ヘルスチェック: 警告あり (ステータス: {$health_data['status']})", 'warning');
        $check_results['overall_status'] = 'warning';
        $check_results['warnings'][] = 'Health check returned warning status';
        $check_results['checks']['health'] = [
            'status' => 'warning',
            'details' => $health_data
        ];
    }
} catch (Exception $e) {
    output("ヘルスチェック: エラー - " . $e->getMessage(), 'error');
    $check_results['overall_status'] = 'error';
    $check_results['errors'][] = 'Health check failed: ' . $e->getMessage();
    $check_results['checks']['health'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 2. 認証API
output("\n[2/4] 認証API確認中...", 'info');
try {
    $auth_url = $base_url . '/api/v1/auth.php';
    $auth_data = json_encode(['apiKey' => 'pk_demo_12345']);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $auth_data,
            'timeout' => 10
        ]
    ]);

    $start_time = microtime(true);
    $auth_response = @file_get_contents($auth_url, false, $context);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($auth_response === false) {
        throw new Exception('Auth API unreachable');
    }

    $auth_result = json_decode($auth_response, true);

    if ($auth_result['success'] === true) {
        output("認証API: OK (レスポンスタイム: {$response_time}ms, 環境: {$auth_result['environment']})", 'ok');
        $check_results['checks']['auth'] = [
            'status' => 'ok',
            'response_time_ms' => $response_time,
            'environment' => $auth_result['environment']
        ];
    } else {
        throw new Exception('Auth API returned success=false');
    }
} catch (Exception $e) {
    output("認証API: エラー - " . $e->getMessage(), 'error');
    $check_results['overall_status'] = 'error';
    $check_results['errors'][] = 'Auth API failed: ' . $e->getMessage();
    $check_results['checks']['auth'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 3. 機種一覧API
output("\n[3/4] 機種一覧API確認中...", 'info');
try {
    $models_url = $base_url . '/api/v1/models.php?apiKey=pk_demo_12345';

    $start_time = microtime(true);
    $models_response = @file_get_contents($models_url);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($models_response === false) {
        throw new Exception('Models API unreachable');
    }

    $models_result = json_decode($models_response, true);

    if ($models_result['success'] === true) {
        output("機種一覧API: OK (レスポンスタイム: {$response_time}ms, 機種数: {$models_result['count']})", 'ok');
        $check_results['checks']['models'] = [
            'status' => 'ok',
            'response_time_ms' => $response_time,
            'model_count' => $models_result['count']
        ];
    } else {
        throw new Exception('Models API returned success=false');
    }
} catch (Exception $e) {
    output("機種一覧API: エラー - " . $e->getMessage(), 'error');
    $check_results['overall_status'] = 'error';
    $check_results['errors'][] = 'Models API failed: ' . $e->getMessage();
    $check_results['checks']['models'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 4. ゲーム開始API（完全フローテスト）
output("\n[4/4] ゲーム開始API確認中（完全フローテスト）...", 'info');
try {
    // まず認証してJWT取得
    $auth_url = $base_url . '/api/v1/auth.php';
    $auth_data = json_encode(['apiKey' => 'pk_demo_12345']);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $auth_data,
            'timeout' => 10
        ]
    ]);

    $auth_response = @file_get_contents($auth_url, false, $context);
    $auth_result = json_decode($auth_response, true);
    $token = $auth_result['token'];

    // ゲーム開始APIを呼び出し
    $game_start_url = $base_url . '/api/v1/game_start.php';
    $game_data = json_encode(['modelId' => 'HOKUTO4GO']);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAuthorization: Bearer $token",
            'content' => $game_data,
            'timeout' => 10
        ]
    ]);

    $start_time = microtime(true);
    $game_response = @file_get_contents($game_start_url, false, $context);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($game_response === false) {
        throw new Exception('Game start API unreachable');
    }

    $game_result = json_decode($game_response, true);

    if ($game_result['success'] === true) {
        $is_mock = $game_result['mock'] ?? false;
        output("ゲーム開始API: OK (レスポンスタイム: {$response_time}ms, モック: " . ($is_mock ? 'はい' : 'いいえ') . ")", 'ok');
        $check_results['checks']['game_start'] = [
            'status' => 'ok',
            'response_time_ms' => $response_time,
            'mock_mode' => $is_mock,
            'environment' => $game_result['environment'] ?? 'unknown'
        ];
    } else {
        throw new Exception('Game start API returned success=false');
    }
} catch (Exception $e) {
    output("ゲーム開始API: エラー - " . $e->getMessage(), 'error');
    $check_results['overall_status'] = 'error';
    $check_results['errors'][] = 'Game start API failed: ' . $e->getMessage();
    $check_results['checks']['game_start'] = [
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 結果サマリー
output("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');
output("監視チェック完了", 'info');
output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');

$status_type = $check_results['overall_status'] === 'ok' ? 'ok' :
              ($check_results['overall_status'] === 'error' ? 'error' : 'warning');

output("総合ステータス: " . strtoupper($check_results['overall_status']), $status_type);
output("エラー数: " . count($check_results['errors']), count($check_results['errors']) > 0 ? 'error' : 'ok');
output("警告数: " . count($check_results['warnings']), count($check_results['warnings']) > 0 ? 'warning' : 'ok');

// エラーがあれば詳細表示
if (count($check_results['errors']) > 0) {
    output("\n検出されたエラー:", 'error');
    foreach ($check_results['errors'] as $error) {
        output("  - " . $error, 'error');
    }
}

if (count($check_results['warnings']) > 0) {
    output("\n検出された警告:", 'warning');
    foreach ($check_results['warnings'] as $warning) {
        output("  - " . $warning, 'warning');
    }
}

output("\n", 'info');

// JSON形式で結果を出力（ログ記録用）
if (!$is_cli) {
    echo json_encode($check_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// 終了コード（エラーがあれば1、なければ0）
exit($check_results['overall_status'] === 'error' ? 1 : 0);
?>
