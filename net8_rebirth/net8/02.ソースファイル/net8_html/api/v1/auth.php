<?php
/**
 * NET8 SDK API - Authentication Endpoint
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/require_files.php');

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['apiKey'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_API_KEY',
        'message' => 'API key is required'
    ]);
    exit;
}

$apiKey = $input['apiKey'];

// APIキー検証
try {
    $pdo = get_db_connection();

    // APIキーテーブルから検証（PDO prepared statement使用）
    $sql = "SELECT * FROM api_keys
            WHERE key_value = :api_key
            AND is_active = 1
            AND (expires_at IS NULL OR expires_at > NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['api_key' => $apiKey]);
    $apiKeyData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$apiKeyData) {
        http_response_code(401);
        echo json_encode([
            'error' => 'INVALID_API_KEY',
            'message' => 'Invalid or expired API key'
        ]);
        exit;
    }

    // JWT生成（簡易版 - 本番ではライブラリ使用推奨）
    $header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = base64_encode(json_encode([
        'api_key_id' => $apiKeyData['id'],
        'user_id' => $apiKeyData['user_id'],
        'exp' => time() + 3600 // 1時間有効
    ]));

    $signature = hash_hmac('sha256', "$header.$payload", 'NET8_SECRET_KEY_CHANGE_ME', true);
    $signature = base64_encode($signature);

    $jwt = "$header.$payload.$signature";

    // 最終使用日時を更新
    $updateSql = "UPDATE api_keys
                  SET last_used_at = NOW()
                  WHERE id = " . $db->conv_sql($apiKeyData['id'], FD_NUM);
    $db->query($updateSql);

    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'token' => $jwt,
        'expiresIn' => 3600,
        'environment' => $apiKeyData['environment']
    ]);

} catch (Exception $e) {
    error_log('Auth API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Authentication failed'
    ]);
}
