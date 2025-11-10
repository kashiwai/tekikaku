<?php
/**
 * NET8 SDK - E2E Automated Test Script
 * SDK完全自動E2Eテストスクリプト
 * Version: 1.0.0
 *
 * 使い方:
 * - Web実行: /api/admin/sdk_e2e_test.php?auth=net8_admin_2025
 * - CLI実行: php api/admin/sdk_e2e_test.php
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

// CLI実行かWeb実行かを判定
$is_cli = php_sapi_name() === 'cli';

if (!$is_cli) {
    $admin_password = $_GET['auth'] ?? '';
    if ($admin_password !== 'net8_admin_2025') {
        http_response_code(403);
        die('Access Denied');
    }
    header('Content-Type: application/json; charset=UTF-8');
}

// テスト実行ID生成
$test_run_id = 'test_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);

// 基本設定
$base_url = 'https://mgg-webservice-production.up.railway.app';
$test_api_key = 'pk_test_dummy_partner_2025';

// 結果格納
$test_results = [
    'test_run_id' => $test_run_id,
    'timestamp' => date('Y-m-d H:i:s'),
    'api_key' => $test_api_key,
    'overall_status' => 'ok',
    'total_tests' => 0,
    'passed_tests' => 0,
    'failed_tests' => 0,
    'total_duration_ms' => 0,
    'steps' => [],
    'errors' => []
];

// カラー出力関数
function output($message, $type = 'info') {
    global $is_cli;

    if ($is_cli) {
        $colors = [
            'ok' => "\033[32m",
            'error' => "\033[31m",
            'warning' => "\033[33m",
            'info' => "\033[36m",
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

// テストステップ記録関数
function record_test_step($step_name, $step_order, $status, $response_time_ms, $request_data = null, $response_data = null, $error_message = null) {
    global $test_results, $test_run_id;

    $test_results['total_tests']++;
    if ($status === 'passed') {
        $test_results['passed_tests']++;
    } else {
        $test_results['failed_tests']++;
        $test_results['overall_status'] = 'error';
    }

    $test_results['steps'][] = [
        'step_name' => $step_name,
        'step_order' => $step_order,
        'status' => $status,
        'response_time_ms' => $response_time_ms,
        'request_data' => $request_data,
        'response_data' => $response_data,
        'error_message' => $error_message
    ];
}

output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');
output("NET8 SDK E2E 自動テスト開始", 'info');
output("テストID: {$test_run_id}", 'info');
output("実行時刻: " . date('Y-m-d H:i:s'), 'info');
output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');

$total_start_time = microtime(true);

// ========================================
// テスト1: ヘルスチェックAPI
// ========================================
output("\n[1/6] ヘルスチェックAPI テスト中...", 'info');
try {
    $health_url = $base_url . '/api/health.php';

    $start_time = microtime(true);
    $health_response = @file_get_contents($health_url);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($health_response === false) {
        throw new Exception('Health check API unreachable');
    }

    $health_data = json_decode($health_response, true);

    if ($health_data['status'] === 'ok') {
        output("ヘルスチェック: OK (レスポンスタイム: {$response_time}ms)", 'ok');
        record_test_step('health_check', 1, 'passed', $response_time, null, $health_data, null);
    } else {
        throw new Exception('Health check returned non-ok status');
    }
} catch (Exception $e) {
    output("ヘルスチェック: エラー - " . $e->getMessage(), 'error');
    record_test_step('health_check', 1, 'failed', 0, null, null, $e->getMessage());
    $test_results['errors'][] = 'Health check failed: ' . $e->getMessage();
}

// ========================================
// テスト2: 認証API（JWT取得）
// ========================================
output("\n[2/6] 認証API テスト中...", 'info');
$jwt_token = null;
try {
    $auth_url = $base_url . '/api/v1/auth.php';
    $auth_data = json_encode(['apiKey' => $test_api_key]);

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

    if ($auth_result['success'] === true && !empty($auth_result['token'])) {
        $jwt_token = $auth_result['token'];
        output("認証API: OK (レスポンスタイム: {$response_time}ms, 環境: {$auth_result['environment']})", 'ok');
        record_test_step('auth_api', 2, 'passed', $response_time, ['apiKey' => $test_api_key], $auth_result, null);
    } else {
        throw new Exception('Auth API returned success=false or missing token');
    }
} catch (Exception $e) {
    output("認証API: エラー - " . $e->getMessage(), 'error');
    record_test_step('auth_api', 2, 'failed', 0, ['apiKey' => $test_api_key], null, $e->getMessage());
    $test_results['errors'][] = 'Auth API failed: ' . $e->getMessage();
}

// ========================================
// テスト3: 機種一覧API
// ========================================
output("\n[3/6] 機種一覧API テスト中...", 'info');
$available_models = [];
try {
    $models_url = $base_url . '/api/v1/models.php?apiKey=' . $test_api_key;

    $start_time = microtime(true);
    $models_response = @file_get_contents($models_url);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    if ($models_response === false) {
        throw new Exception('Models API unreachable');
    }

    $models_result = json_decode($models_response, true);

    if ($models_result['success'] === true && !empty($models_result['models'])) {
        $available_models = $models_result['models'];
        output("機種一覧API: OK (レスポンスタイム: {$response_time}ms, 機種数: {$models_result['count']})", 'ok');
        record_test_step('models_api', 3, 'passed', $response_time, null, $models_result, null);

        // 機種詳細を表示
        foreach ($available_models as $model) {
            output("  - {$model['id']}: {$model['name']} ({$model['category']})", 'info');
        }
    } else {
        throw new Exception('Models API returned success=false or empty models');
    }
} catch (Exception $e) {
    output("機種一覧API: エラー - " . $e->getMessage(), 'error');
    record_test_step('models_api', 3, 'failed', 0, null, null, $e->getMessage());
    $test_results['errors'][] = 'Models API failed: ' . $e->getMessage();
}

// ========================================
// テスト4: ゲーム開始API（完全フロー）
// ========================================
output("\n[4/6] ゲーム開始API テスト中（完全フロー）...", 'info');
if ($jwt_token && !empty($available_models)) {
    try {
        $game_start_url = $base_url . '/api/v1/game_start.php';
        $test_model_id = $available_models[0]['id'];
        $game_data = json_encode(['modelId' => $test_model_id]);

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\nAuthorization: Bearer {$jwt_token}",
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
            $mode_text = $is_mock ? 'モック' : '実機';
            output("ゲーム開始API: OK (レスポンスタイム: {$response_time}ms, モード: {$mode_text})", 'ok');
            record_test_step('game_start_api', 4, 'passed', $response_time, ['modelId' => $test_model_id], $game_result, null);
        } else {
            throw new Exception('Game start API returned success=false');
        }
    } catch (Exception $e) {
        output("ゲーム開始API: エラー - " . $e->getMessage(), 'error');
        record_test_step('game_start_api', 4, 'failed', 0, ['modelId' => $test_model_id], null, $e->getMessage());
        $test_results['errors'][] = 'Game start API failed: ' . $e->getMessage();
    }
} else {
    output("ゲーム開始API: スキップ（前のテストが失敗）", 'warning');
    record_test_step('game_start_api', 4, 'skipped', 0, null, null, 'JWT token or models not available');
}

// ========================================
// テスト5: エラーハンドリング（無効なAPIキー）
// ========================================
output("\n[5/6] エラーハンドリング テスト中（無効なAPIキー）...", 'info');
try {
    $auth_url = $base_url . '/api/v1/auth.php';
    $invalid_key = 'pk_test_invalid_key_12345';
    $auth_data = json_encode(['apiKey' => $invalid_key]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => $auth_data,
            'timeout' => 10,
            'ignore_errors' => true
        ]
    ]);

    $start_time = microtime(true);
    $auth_response = @file_get_contents($auth_url, false, $context);
    $response_time = round((microtime(true) - $start_time) * 1000, 2);

    $auth_result = json_decode($auth_response, true);

    // 無効なAPIキーで success=false が返ることを期待
    if ($auth_result['success'] === false) {
        output("エラーハンドリング: OK（無効なAPIキーを正しく拒否, レスポンスタイム: {$response_time}ms）", 'ok');
        record_test_step('error_handling', 5, 'passed', $response_time, ['apiKey' => $invalid_key], $auth_result, null);
    } else {
        throw new Exception('Invalid API key was accepted (should be rejected)');
    }
} catch (Exception $e) {
    output("エラーハンドリング: エラー - " . $e->getMessage(), 'error');
    record_test_step('error_handling', 5, 'failed', 0, ['apiKey' => $invalid_key], null, $e->getMessage());
    $test_results['errors'][] = 'Error handling test failed: ' . $e->getMessage();
}

// ========================================
// テスト6: レスポンスタイム検証
// ========================================
output("\n[6/6] レスポンスタイム検証 テスト中...", 'info');
$response_time_threshold = 2000; // 2秒
$slow_responses = [];

foreach ($test_results['steps'] as $step) {
    if ($step['status'] === 'passed' && $step['response_time_ms'] > $response_time_threshold) {
        $slow_responses[] = "{$step['step_name']} ({$step['response_time_ms']}ms)";
    }
}

if (empty($slow_responses)) {
    output("レスポンスタイム: OK（すべてのAPIが{$response_time_threshold}ms以内に応答）", 'ok');
    record_test_step('response_time_check', 6, 'passed', 0, null, ['threshold' => $response_time_threshold], null);
} else {
    output("レスポンスタイム: 警告（遅いAPI: " . implode(', ', $slow_responses) . "）", 'warning');
    record_test_step('response_time_check', 6, 'warning', 0, null, ['threshold' => $response_time_threshold, 'slow_responses' => $slow_responses], 'Some APIs are slow');
}

// 総実行時間
$test_results['total_duration_ms'] = round((microtime(true) - $total_start_time) * 1000, 2);

// ========================================
// 結果サマリー
// ========================================
output("\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');
output("E2Eテスト完了", 'info');
output("━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━", 'info');

$status_type = $test_results['overall_status'] === 'ok' ? 'ok' : 'error';
output("総合ステータス: " . strtoupper($test_results['overall_status']), $status_type);
output("実行テスト数: {$test_results['total_tests']}", 'info');
output("成功: {$test_results['passed_tests']}", 'ok');
output("失敗: {$test_results['failed_tests']}", $test_results['failed_tests'] > 0 ? 'error' : 'ok');
output("総実行時間: {$test_results['total_duration_ms']}ms", 'info');

if (!empty($test_results['errors'])) {
    output("\n検出されたエラー:", 'error');
    foreach ($test_results['errors'] as $error) {
        output("  - " . $error, 'error');
    }
}

// ========================================
// データベースに保存
// ========================================
try {
    $pdo = get_db_connection();

    // テスト履歴を保存
    $stmt = $pdo->prepare("
        INSERT INTO sdk_e2e_test_history
        (test_run_id, test_type, overall_status, total_tests, passed_tests, failed_tests, total_duration_ms, test_details, errors)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $test_run_id,
        'full',
        $test_results['overall_status'],
        $test_results['total_tests'],
        $test_results['passed_tests'],
        $test_results['failed_tests'],
        $test_results['total_duration_ms'],
        json_encode($test_results, JSON_UNESCAPED_UNICODE),
        json_encode($test_results['errors'], JSON_UNESCAPED_UNICODE)
    ]);

    // 各ステップを保存
    $stmt = $pdo->prepare("
        INSERT INTO sdk_e2e_test_steps
        (test_run_id, step_name, step_order, status, response_time_ms, request_data, response_data, error_message)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($test_results['steps'] as $step) {
        $stmt->execute([
            $test_run_id,
            $step['step_name'],
            $step['step_order'],
            $step['status'],
            $step['response_time_ms'],
            json_encode($step['request_data'], JSON_UNESCAPED_UNICODE),
            json_encode($step['response_data'], JSON_UNESCAPED_UNICODE),
            $step['error_message']
        ]);
    }

    output("\n✅ テスト結果をデータベースに保存しました", 'ok');

} catch (Exception $e) {
    output("\n⚠️ データベース保存エラー: " . $e->getMessage(), 'warning');
}

output("\n", 'info');

// JSON形式で結果を出力
if (!$is_cli) {
    echo json_encode($test_results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// 終了コード
exit($test_results['overall_status'] === 'error' ? 1 : 0);
?>
