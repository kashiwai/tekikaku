<?php
/**
 * NET8 Claude Code API - Authentication Helper
 * Version: 1.0.0
 * Created: 2025-12-12
 */

require_once __DIR__ . '/ApiResponse.php';

class ClaudeAuth {

    private $db;
    private $secretKey = 'NET8_CLAUDE_SECRET_KEY_2025';
    private $tokenExpiry = 86400; // 24時間

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Claude Code専用APIキーで認証
     */
    public function authenticate($apiKey) {
        if (empty($apiKey)) {
            return false;
        }

        // Claude Code専用キー (ck_claude_xxx) または通常キーを検証
        $sql = "SELECT * FROM api_keys
                WHERE key_value = :key
                AND is_active = 1
                AND (expires_at IS NULL OR expires_at > NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['key' => $apiKey]);
        $keyData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$keyData) {
            return false;
        }

        // 最終使用日時を更新
        $updateSql = "UPDATE api_keys SET last_used_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($updateSql);
        $stmt->execute(['id' => $keyData['id']]);

        return $keyData;
    }

    /**
     * JWTトークン生成
     */
    public function generateToken($apiKeyData) {
        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'api_key_id' => $apiKeyData['id'],
            'key_type' => $apiKeyData['key_type'] ?? 'public',
            'environment' => $apiKeyData['environment'],
            'iat' => time(),
            'exp' => time() + $this->tokenExpiry
        ]));

        $signature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );

        return "$header.$payload.$signature";
    }

    /**
     * JWTトークン検証
     */
    public function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($header, $payload, $signature) = $parts;

        // 署名検証
        $expectedSignature = $this->base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", $this->secretKey, true)
        );

        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // ペイロードデコード
        $payloadData = json_decode($this->base64UrlDecode($payload), true);

        // 有効期限チェック
        if (!isset($payloadData['exp']) || $payloadData['exp'] < time()) {
            return false;
        }

        return $payloadData;
    }

    /**
     * リクエストから認証情報を取得して検証
     */
    public function validateRequest() {
        // Authorizationヘッダーからトークン取得
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Bearer トークン
        if (preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            $payload = $this->verifyToken($token);
            if ($payload) {
                return $payload;
            }
        }

        // X-API-Key ヘッダー
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? '';
        if (!empty($apiKey)) {
            $keyData = $this->authenticate($apiKey);
            if ($keyData) {
                return [
                    'api_key_id' => $keyData['id'],
                    'key_type' => $keyData['key_type'] ?? 'public',
                    'environment' => $keyData['environment']
                ];
            }
        }

        // クエリパラメータ (非推奨だが対応)
        $apiKey = $_GET['api_key'] ?? '';
        if (!empty($apiKey)) {
            $keyData = $this->authenticate($apiKey);
            if ($keyData) {
                return [
                    'api_key_id' => $keyData['id'],
                    'key_type' => $keyData['key_type'] ?? 'public',
                    'environment' => $keyData['environment']
                ];
            }
        }

        return false;
    }

    /**
     * 認証必須エンドポイント用ミドルウェア
     */
    public function requireAuth() {
        $auth = $this->validateRequest();
        if (!$auth) {
            ApiResponse::unauthorized('有効なAPIキーまたはトークンが必要です');
        }
        return $auth;
    }

    /**
     * Claude Code専用APIキー生成
     */
    public function generateClaudeApiKey($name, $environment = 'live') {
        $prefix = 'ck_claude_';
        $keyValue = $prefix . bin2hex(random_bytes(24));

        $sql = "INSERT INTO api_keys (key_value, key_type, name, environment, rate_limit, is_active, created_at)
                VALUES (:key_value, 'claude', :name, :environment, 100000, 1, NOW())";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'key_value' => $keyValue,
            'name' => $name,
            'environment' => $environment
        ]);

        return [
            'id' => $this->db->lastInsertId(),
            'key_value' => $keyValue,
            'name' => $name,
            'environment' => $environment
        ];
    }

    private function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
