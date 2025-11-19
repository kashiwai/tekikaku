<?php
/**
 * X-Frame-Options ヘルパー
 * Version: 1.0.0
 * Created: 2025-11-18
 */

/**
 * X-Frame-Options ヘッダーを設定
 * APIキーに紐づく許可ドメインに基づいて動的に設定
 *
 * @param PDO $pdo
 * @param int|null $apiKeyId
 * @return void
 */
function setFrameSecurityHeaders($pdo, $apiKeyId = null) {
    // リファラーから呼び出し元を取得
    $referer = $_SERVER['HTTP_REFERER'] ?? null;
    $origin = $_SERVER['HTTP_ORIGIN'] ?? null;

    // APIキーIDが提供されている場合、許可ドメインをチェック
    if ($apiKeyId) {
        $stmt = $pdo->prepare("
            SELECT allowed_domains
            FROM api_keys
            WHERE id = :id
            AND is_active = 1
        ");

        $stmt->execute(['id' => $apiKeyId]);
        $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($apiKey) {
            $allowedDomains = json_decode($apiKey['allowed_domains'] ?? '[]', true);

            if (!is_array($allowedDomains)) {
                $allowedDomains = [];
            }

            // 許可ドメインがある場合
            if (!empty($allowedDomains)) {
                // Originまたはrefererをチェック
                $requestOrigin = $origin ?: extractOriginFromReferer($referer);

                if ($requestOrigin && in_array($requestOrigin, $allowedDomains)) {
                    // CSP frame-ancestors で許可
                    $cspDomains = implode(' ', $allowedDomains);
                    header("Content-Security-Policy: frame-ancestors {$cspDomains}");

                    // CORSヘッダー
                    header("Access-Control-Allow-Origin: {$requestOrigin}");
                    header("Access-Control-Allow-Credentials: true");
                    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
                    header("Access-Control-Allow-Headers: Content-Type, Authorization");

                    // X-Frame-Options は CSP と競合するため設定しない
                    return;
                }
            }
        }
    }

    // デフォルト：同一オリジンのみ許可
    header("X-Frame-Options: SAMEORIGIN");
    header("Content-Security-Policy: frame-ancestors 'self'");
}

/**
 * RefererからOriginを抽出
 *
 * @param string|null $referer
 * @return string|null
 */
function extractOriginFromReferer($referer) {
    if (!$referer) {
        return null;
    }

    $parsed = parse_url($referer);
    if (!$parsed || !isset($parsed['scheme']) || !isset($parsed['host'])) {
        return null;
    }

    $origin = $parsed['scheme'] . '://' . $parsed['host'];

    // ポート番号がある場合は追加
    if (isset($parsed['port']) && !in_array($parsed['port'], [80, 443])) {
        $origin .= ':' . $parsed['port'];
    }

    return $origin;
}

/**
 * セッションIDからAPIキーIDを取得
 *
 * @param PDO $pdo
 * @param string $sessionId
 * @return int|null
 */
function getApiKeyIdFromSession($pdo, $sessionId) {
    $stmt = $pdo->prepare("
        SELECT api_key_id
        FROM game_sessions
        WHERE session_id = :session_id
    ");

    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    return $session ? $session['api_key_id'] : null;
}

/**
 * マシン番号からAPIキーIDを取得（既存のlnk_machineテーブルを利用）
 *
 * @param PDO $pdo
 * @param int $machineNo
 * @return int|null
 */
function getApiKeyIdFromMachine($pdo, $machineNo) {
    // TODO: マシンとAPIキーの紐付けロジックを実装
    // 現状はデフォルトのAPIキーを返す
    $stmt = $pdo->query("SELECT id FROM api_keys WHERE environment = 'production' AND is_active = 1 LIMIT 1");
    $apiKey = $stmt->fetch(PDO::FETCH_ASSOC);

    return $apiKey ? $apiKey['id'] : null;
}
