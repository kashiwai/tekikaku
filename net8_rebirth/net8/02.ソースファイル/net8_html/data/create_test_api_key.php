<?php
/**
 * テスト用APIキー作成スクリプト
 * SDK v1.1.0 テスト用
 */

header('Content-Type: application/json');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // まずapi_keysテーブルの構造を確認
    $stmt = $pdo->query("SHOW COLUMNS FROM api_keys");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 api_keysテーブル構造:\n";
    echo json_encode($columns, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    // 既存のAPIキーを確認（利用可能なカラムのみ）
    $stmt = $pdo->prepare("
        SELECT *
        FROM api_keys
        LIMIT 5
    ");
    $stmt->execute();
    $existingKeys = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "📋 既存のAPIキー（最大5件）:\n";
    echo json_encode($existingKeys, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

    echo "✅ テーブル情報を確認しました。\n";
    echo "ℹ️ 次に、この情報を基にAPIキーを作成するスクリプトを作成します。\n";

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'FAILED',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
