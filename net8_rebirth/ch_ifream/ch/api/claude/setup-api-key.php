<?php
/**
 * Claude Code API Key Setup Script
 * 初回APIキー発行用（一度だけ実行）
 */

header('Content-Type: application/json; charset=utf-8');

// セキュリティ: 特定のトークンがないと実行不可
$setupToken = $_GET['token'] ?? '';
$expectedToken = 'NET8_CLAUDE_SETUP_2025';

if ($setupToken !== $expectedToken) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid setup token']);
    exit;
}

require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();

    // 既存のClaude APIキーをチェック
    $stmt = $pdo->query("SELECT * FROM api_keys WHERE key_value LIKE 'ck_claude_%' AND is_active = 1 LIMIT 1");
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode([
            'success' => true,
            'message' => '既存のClaude APIキーがあります',
            'api_key' => $existing['key_value'],
            'name' => $existing['name'],
            'created_at' => $existing['created_at']
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 新規APIキー発行
    $keyValue = 'ck_claude_' . bin2hex(random_bytes(24));
    $name = 'Claude Code Master Key';

    $sql = "INSERT INTO api_keys (key_value, key_type, name, environment, rate_limit, is_active, created_at)
            VALUES (:key_value, 'claude', :name, 'live', 10000, 1, NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'key_value' => $keyValue,
        'name' => $name
    ]);

    $id = $pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'message' => 'Claude Code APIキーを発行しました',
        'api_key' => $keyValue,
        'id' => $id,
        'name' => $name,
        'environment' => 'live',
        'rate_limit' => 10000,
        'usage' => [
            'header' => "X-API-Key: {$keyValue}",
            'bearer' => "Authorization: Bearer <token from POST /api/claude/auth>",
            'query' => "?api_key={$keyValue}"
        ],
        'important' => 'このキーは一度しか表示されません。安全に保管してください。'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Setup failed',
        'message' => $e->getMessage()
    ]);
}
