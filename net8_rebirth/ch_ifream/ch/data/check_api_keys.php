<?php
/**
 * APIキー確認スクリプト
 * テスト環境で使用可能なAPIキーを表示します
 */

header('Content-Type: application/json');

require_once('../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 有効なAPIキーを取得
    $stmt = $pdo->query("
        SELECT
            id,
            key_value,
            key_type,
            name,
            environment,
            is_active,
            created_at
        FROM api_keys
        WHERE is_active = 1
        ORDER BY environment, created_at DESC
        LIMIT 10
    ");

    $keys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($keys)) {
        // APIキーが存在しない場合、テスト用を作成
        $testKey = 'pk_test_' . bin2hex(random_bytes(16));
        $pdo->exec("
            INSERT INTO api_keys (key_value, key_type, name, environment, is_active)
            VALUES ('$testKey', 'public', 'Test API Key', 'test', 1)
        ");

        $keys = [$testKey => [
            'id' => $pdo->lastInsertId(),
            'key_value' => $testKey,
            'key_type' => 'public',
            'name' => 'Test API Key',
            'environment' => 'test',
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'newly_created' => true
        ]];
    }

    echo json_encode([
        'success' => true,
        'keys' => $keys,
        'message' => 'Use any of these API keys in your test page'
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
