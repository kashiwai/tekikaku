<?php
/**
 * game_sessionsテーブル構造確認
 */

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo json_encode([
        'success' => true,
        'table_structure' => $pdo->query("SHOW COLUMNS FROM game_sessions")->fetchAll(PDO::FETCH_ASSOC),
        'api_keys_structure' => $pdo->query("SHOW COLUMNS FROM api_keys")->fetchAll(PDO::FETCH_ASSOC),
        'sdk_users_structure' => $pdo->query("SHOW COLUMNS FROM sdk_users")->fetchAll(PDO::FETCH_ASSOC),
        'api_keys_count' => $pdo->query("SELECT COUNT(*) as count FROM api_keys")->fetch(PDO::FETCH_ASSOC),
        'sdk_users_count' => $pdo->query("SELECT COUNT(*) as count FROM sdk_users")->fetch(PDO::FETCH_ASSOC),
        'game_sessions_count' => $pdo->query("SELECT COUNT(*) as count FROM game_sessions")->fetch(PDO::FETCH_ASSOC)
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT);
}
