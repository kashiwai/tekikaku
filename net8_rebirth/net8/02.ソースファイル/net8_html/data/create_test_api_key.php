<?php
/**
 * テスト用APIキー作成スクリプト
 * SDK v1.1.0 テスト用
 */

header('Content-Type: application/json');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 既存のテストAPIキーを確認
    $stmt = $pdo->prepare("
        SELECT id, key_value, partner_name, environment, is_active
        FROM api_keys
        WHERE key_value LIKE 'pk_test_%' OR key_value LIKE 'sk_test_%'
        LIMIT 10
    ");
    $stmt->execute();
    $existingKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 既存のテストAPIキー:\n";
    echo json_encode($existingKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // テスト用APIキーを作成
    $testKey = 'pk_test_demo_2025';

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO api_keys (
            key_value,
            partner_name,
            environment,
            is_active,
            allowed_origins,
            rate_limit_per_minute,
            created_at
        ) VALUES (
            :key_value,
            'Demo Partner',
            'test',
            1,
            '*',
            100,
            NOW()
        )
    ");

    $stmt->execute(['key_value' => $testKey]);

    if ($stmt->rowCount() > 0) {
        echo "✅ 新しいテストAPIキーを作成しました: {$testKey}\n\n";
    } else {
        echo "ℹ️ APIキー {$testKey} は既に存在します\n\n";
    }

    // 作成されたAPIキーを確認
    $stmt = $pdo->prepare("
        SELECT id, key_value, partner_name, environment, is_active, allowed_origins, rate_limit_per_minute
        FROM api_keys
        WHERE key_value = :key_value
    ");
    $stmt->execute(['key_value' => $testKey]);
    $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "🔑 使用するAPIキー情報:\n";
    echo json_encode($apiKey, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "✅ 完了！このAPIキーを使用してテストを実行してください。\n";

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'FAILED',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
