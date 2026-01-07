<?php
/**
 * NET8 Callback Helper
 * Version: 1.0.0
 * Created: 2026-01-07
 *
 * Purpose: サーバー間コールバック処理（セキュリティ強化）
 * - HMAC-SHA256署名生成
 * - コールバックURL送信
 * - リトライ機構（最大5回）
 */

/**
 * コールバック送信（HMAC-SHA256署名付き）
 *
 * @param string $callbackUrl コールバック先URL（HTTPS必須）
 * @param string $callbackSecret Webhook署名検証用秘密鍵
 * @param array $data コールバックデータ
 * @param int $maxRetries 最大リトライ回数（デフォルト: 5）
 * @return array ['success' => bool, 'response' => array|null, 'error' => string|null]
 */
function sendSecureCallback($callbackUrl, $callbackSecret, $data, $maxRetries = 5) {
    // HTTPS必須チェック
    if (strpos($callbackUrl, 'https://') !== 0) {
        error_log("❌ Callback URL must be HTTPS: {$callbackUrl}");
        return [
            'success' => false,
            'error' => 'CALLBACK_URL_NOT_HTTPS',
            'message' => 'Callback URL must use HTTPS'
        ];
    }

    $retryIntervals = [0, 5, 15, 30, 60]; // 秒単位
    $attempt = 0;

    while ($attempt < $maxRetries) {
        $attempt++;

        // リトライ間隔（1回目は即座）
        if ($attempt > 1) {
            $waitTime = $retryIntervals[$attempt - 1];
            error_log("⏳ Callback retry #{$attempt} after {$waitTime} seconds...");
            sleep($waitTime);
        } else {
            error_log("📤 Sending callback to {$callbackUrl} (attempt #{$attempt})");
        }

        // タイムスタンプ生成
        $timestamp = time();

        // ペイロード作成
        $payload = [
            'event' => 'game.ended',
            'timestamp' => $timestamp,
            'data' => $data
        ];

        // JSON エンコード
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // HMAC-SHA256 署名生成
        $signature = hash_hmac('sha256', $jsonPayload, $callbackSecret);
        $signatureHeader = "sha256={$signature}";

        // HTTPヘッダー
        $headers = [
            'Content-Type: application/json',
            'X-NET8-Signature: ' . $signatureHeader,
            'X-NET8-Timestamp: ' . $timestamp,
            'X-NET8-Event: game.ended',
            'User-Agent: NET8-Callback/1.0'
        ];

        // cURL送信
        $ch = curl_init($callbackUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10秒タイムアウト
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 成功判定
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("✅ Callback succeeded on attempt #{$attempt}: HTTP {$httpCode}");

            $responseData = json_decode($response, true);
            return [
                'success' => true,
                'response' => $responseData,
                'httpCode' => $httpCode,
                'attempts' => $attempt
            ];
        }

        // リトライ不要なエラー（400番台）
        if ($httpCode >= 400 && $httpCode < 500) {
            error_log("❌ Callback failed with client error (no retry): HTTP {$httpCode}");
            return [
                'success' => false,
                'error' => 'CALLBACK_CLIENT_ERROR',
                'httpCode' => $httpCode,
                'response' => $response,
                'attempts' => $attempt
            ];
        }

        // リトライ対象エラー（5xx、タイムアウト、ネットワークエラー）
        if ($attempt < $maxRetries) {
            $errorMsg = $error ? $error : "HTTP {$httpCode}";
            error_log("⚠️ Callback failed (will retry): {$errorMsg}");
        } else {
            error_log("❌ Callback failed after {$maxRetries} attempts: HTTP {$httpCode}");

            // 管理者通知（最終リトライ失敗時）
            notifyCallbackFailure($callbackUrl, $data, $httpCode, $error);

            return [
                'success' => false,
                'error' => 'CALLBACK_MAX_RETRIES_EXCEEDED',
                'httpCode' => $httpCode,
                'response' => $response,
                'attempts' => $attempt,
                'lastError' => $error
            ];
        }
    }

    // 到達しないはずだが念のため
    return [
        'success' => false,
        'error' => 'CALLBACK_UNKNOWN_ERROR'
    ];
}

/**
 * コールバック失敗通知（管理者向け）
 *
 * @param string $callbackUrl
 * @param array $data
 * @param int $httpCode
 * @param string $error
 */
function notifyCallbackFailure($callbackUrl, $data, $httpCode, $error) {
    $errorLog = [
        'timestamp' => date('Y-m-d H:i:s'),
        'callbackUrl' => $callbackUrl,
        'httpCode' => $httpCode,
        'error' => $error,
        'sessionId' => $data['sessionId'] ?? 'unknown',
        'userId' => $data['userId'] ?? 'unknown'
    ];

    error_log("🚨 ADMIN ALERT: Callback failed after all retries: " . json_encode($errorLog));

    // TODO: メール/Slack通知実装
    // - 本番環境では管理者にメールまたはSlack通知を送信
    // - エラーログはDBにも記録することを推奨
}

/**
 * コールバックデータ構築
 *
 * @param array $session ゲームセッション情報
 * @param array $result ゲーム結果情報
 * @return array コールバックペイロード
 */
function buildCallbackData($session, $result) {
    $startedAt = new DateTime($session['started_at']);
    $endedAt = new DateTime($result['endedAt'] ?? 'now');
    $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

    return [
        'sessionId' => $session['session_id'],
        'memberNo' => $result['memberNo'] ?? $session['member_no'],
        'userId' => $session['partner_user_id'] ?? $session['user_id'],
        'modelId' => $session['model_cd'],
        'machineNo' => $session['machine_no'],

        'startedAt' => $startedAt->format('c'), // ISO 8601
        'endedAt' => $endedAt->format('c'),
        'duration' => $duration,

        'points' => [
            'initial' => $session['points_consumed'], // 初期ポイント（消費予定）
            'consumed' => $session['points_consumed'],
            'won' => $result['pointsWon'] ?? 0,
            'final' => $result['newBalance'] ?? null,
            'net' => $result['netProfit'] ?? 0
        ],

        'result' => $result['result'] ?? 'completed',
        'status' => $result['result'] === 'error' ? 'error' : 'completed',

        // 追加情報（オプション）
        'currency' => $session['currency'] ?? 'JPY',
        'playDuration' => $result['playDuration'] ?? $duration
    ];
}

/**
 * 署名検証（韓国サーバー側で使用）
 *
 * @param string $payload JSONペイロード
 * @param string $signature 受信した署名（sha256=...形式）
 * @param string $secret 秘密鍵
 * @return bool 検証結果
 */
function verifyCallbackSignature($payload, $signature, $secret) {
    $expectedSignature = hash_hmac('sha256', $payload, $secret);
    $expectedHeader = "sha256={$expectedSignature}";

    return hash_equals($expectedHeader, $signature);
}

/**
 * タイムスタンプ検証（リプレイ攻撃防止）
 *
 * @param int $timestamp リクエストのタイムスタンプ
 * @param int $maxAge 許容する最大経過時間（秒、デフォルト: 300秒=5分）
 * @return bool 有効か否か
 */
function verifyCallbackTimestamp($timestamp, $maxAge = 300) {
    $now = time();
    $diff = abs($now - $timestamp);

    return $diff <= $maxAge;
}
