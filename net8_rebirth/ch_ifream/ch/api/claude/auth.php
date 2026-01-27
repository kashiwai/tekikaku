<?php
/**
 * NET8 Claude Code API - Authentication Endpoint
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * POST /api/claude/auth - APIキーで認証してJWTトークン取得
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ClaudeAuth.php';
require_once __DIR__ . '/../../_etc/require_files.php';

// POSTのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ApiResponse::methodNotAllowed(['POST']);
}

try {
    $pdo = get_db_connection();
    $auth = new ClaudeAuth($pdo);

    // リクエストボディ取得
    $input = json_decode(file_get_contents('php://input'), true);
    $apiKey = $input['api_key'] ?? $input['apiKey'] ?? '';

    if (empty($apiKey)) {
        ApiResponse::error('APIキーが必要です', 400, 'MISSING_API_KEY');
    }

    // APIキー認証
    $keyData = $auth->authenticate($apiKey);
    if (!$keyData) {
        ApiResponse::unauthorized('無効なAPIキーです');
    }

    // JWTトークン生成
    $token = $auth->generateToken($keyData);

    ApiResponse::success([
        'token' => $token,
        'token_type' => 'Bearer',
        'expires_in' => 86400,
        'api_key_id' => $keyData['id'],
        'environment' => $keyData['environment'],
        'key_type' => $keyData['key_type'] ?? 'public'
    ], '認証成功');

} catch (Exception $e) {
    error_log('Claude Auth API Error: ' . $e->getMessage());
    ApiResponse::serverError('認証処理中にエラーが発生しました');
}
