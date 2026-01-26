<?php
/**
 * NET8 SDK - Health Check API
 * システム稼働状況確認エンドポイント
 * Version: 1.0.0
 */

header('Content-Type: application/json; charset=UTF-8');

$health_status = [
    'status' => 'ok',
    'timestamp' => date('Y-m-d H:i:s'),
    'checks' => []
];

$start_time = microtime(true);

try {
    // 1. データベース接続チェック
    $db_start = microtime(true);
    try {
        require_once(__DIR__ . '/../_etc/require_files.php');
        $pdo = get_db_connection();

        // 簡単なクエリで接続確認
        $stmt = $pdo->query("SELECT 1");
        $db_time = round((microtime(true) - $db_start) * 1000, 2);

        $health_status['checks']['database'] = [
            'status' => 'ok',
            'response_time_ms' => $db_time,
            'message' => 'Database connection successful'
        ];
    } catch (Exception $e) {
        $health_status['status'] = 'error';
        $health_status['checks']['database'] = [
            'status' => 'error',
            'message' => 'Database connection failed: ' . $e->getMessage()
        ];
    }

    // 2. APIキーテーブル確認
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM api_keys WHERE is_active = 1");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $health_status['checks']['api_keys'] = [
            'status' => 'ok',
            'active_keys' => (int)$result['count'],
            'message' => 'API keys table accessible'
        ];
    } catch (Exception $e) {
        $health_status['status'] = 'error';
        $health_status['checks']['api_keys'] = [
            'status' => 'error',
            'message' => 'API keys check failed: ' . $e->getMessage()
        ];
    }

    // 3. 機種マスターテーブル確認
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mst_model");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $health_status['checks']['models'] = [
            'status' => 'ok',
            'total_models' => (int)$result['count'],
            'message' => 'Models table accessible'
        ];
    } catch (Exception $e) {
        $health_status['status'] = 'warning';
        $health_status['checks']['models'] = [
            'status' => 'warning',
            'message' => 'Models check failed: ' . $e->getMessage()
        ];
    }

    // 4. ディスク容量チェック（簡易）
    $disk_free = disk_free_space('/');
    $disk_total = disk_total_space('/');
    $disk_usage_percent = round((1 - ($disk_free / $disk_total)) * 100, 2);

    $disk_status = 'ok';
    if ($disk_usage_percent > 90) {
        $disk_status = 'critical';
        $health_status['status'] = 'warning';
    } elseif ($disk_usage_percent > 80) {
        $disk_status = 'warning';
    }

    $health_status['checks']['disk'] = [
        'status' => $disk_status,
        'usage_percent' => $disk_usage_percent,
        'free_gb' => round($disk_free / 1024 / 1024 / 1024, 2),
        'message' => 'Disk space check completed'
    ];

    // 5. PHP設定確認
    $health_status['checks']['php'] = [
        'status' => 'ok',
        'version' => phpversion(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'message' => 'PHP environment check completed'
    ];

} catch (Exception $e) {
    $health_status['status'] = 'error';
    $health_status['error'] = $e->getMessage();
}

// 全体のレスポンスタイム
$total_time = round((microtime(true) - $start_time) * 1000, 2);
$health_status['response_time_ms'] = $total_time;

// HTTPステータスコードを設定
if ($health_status['status'] === 'error') {
    http_response_code(503); // Service Unavailable
} elseif ($health_status['status'] === 'warning') {
    http_response_code(200); // OK but with warnings
} else {
    http_response_code(200); // OK
}

echo json_encode($health_status, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
