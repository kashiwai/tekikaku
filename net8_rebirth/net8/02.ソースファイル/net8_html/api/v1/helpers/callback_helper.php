<?php
/**
 * NET8 Callback Helper
 * Version: 2.0.0
 * Created: 2026-01-07
 * Updated: 2026-01-08 - Added real-time bet/win callback support
 *
 * Purpose: サーバー間コールバック処理（セキュリティ強化）
 * - HMAC-SHA256署名生成
 * - コールバックURL送信
 * - リトライ機構（最大5回）
 * - リアルタイムイベント対応（game.bet, game.win, game.ended）
 */

/**
 * リアルタイムコールバック送信（game.bet, game.win用）
 *
 * @param string $callbackUrl コールバック先URL（HTTPS必須）
 * @param string $callbackSecret Webhook署名検証用秘密鍵
 * @param string $eventType イベントタイプ（game.bet, game.win, game.ended）
 * @param array $data コールバックデータ
 * @param int $maxRetries 最大リトライ回数（デフォルト: 3, リアルタイムイベントは短め）
 * @return array ['success' => bool, 'response' => array|null, 'error' => string|null]
 */
function sendRealtimeCallback($callbackUrl, $callbackSecret, $eventType, $data, $maxRetries = 3) {
    // HTTPS必須チェック
    if (strpos($callbackUrl, 'https://') !== 0) {
        error_log("❌ Callback URL must be HTTPS: {$callbackUrl}");
        return [
            'success' => false,
            'error' => 'CALLBACK_URL_NOT_HTTPS',
            'message' => 'Callback URL must use HTTPS'
        ];
    }

    $retryIntervals = [0, 2, 5]; // リアルタイムは短い間隔（秒単位）
    $attempt = 0;

    while ($attempt < $maxRetries) {
        $attempt++;

        // リトライ間隔（1回目は即座）
        if ($attempt > 1) {
            $waitTime = $retryIntervals[$attempt - 1];
            error_log("⏳ Realtime callback retry #{$attempt} after {$waitTime} seconds...");
            sleep($waitTime);
        } else {
            error_log("📤 Sending realtime callback ({$eventType}) to {$callbackUrl} (attempt #{$attempt})");
        }

        // タイムスタンプ生成
        $timestamp = time();

        // ペイロード作成
        $payload = [
            'event' => $eventType,
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
            'X-NET8-Event: ' . $eventType,
            'User-Agent: NET8-Callback/2.0'
        ];

        // cURL送信
        $ch = curl_init($callbackUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // リアルタイムイベントは5秒タイムアウト
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // 成功判定
        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("✅ Realtime callback ({$eventType}) succeeded on attempt #{$attempt}: HTTP {$httpCode}");

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
            error_log("❌ Realtime callback failed with client error (no retry): HTTP {$httpCode}");
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
            error_log("⚠️ Realtime callback failed (will retry): {$errorMsg}");
        } else {
            error_log("❌ Realtime callback failed after {$maxRetries} attempts: HTTP {$httpCode}");

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
 * コールバック送信（HMAC-SHA256署名付き）- game.ended用
 *
 * @param string $callbackUrl コールバック先URL（HTTPS必須）
 * @param string $callbackSecret Webhook署名検証用秘密鍵
 * @param array $data コールバックデータ
 * @param int $maxRetries 最大リトライ回数（デフォルト: 5）
 * @return array ['success' => bool, 'response' => array|null, 'error' => string|null]
 */
function sendSecureCallback($callbackUrl, $callbackSecret, $data, $maxRetries = 5) {
    return sendRealtimeCallback($callbackUrl, $callbackSecret, 'game.ended', $data, $maxRetries);
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
 * ベットイベントコールバックデータ構築
 *
 * @param array $session ゲームセッション情報
 * @param array $betData ベット情報
 * @return array コールバックペイロード
 */
function buildBetCallbackData($session, $betData) {
    return [
        'sessionId' => $session['session_id'],
        'memberNo' => $session['member_no'],
        'userId' => $session['partner_user_id'] ?? $session['user_id'],
        'modelId' => $session['model_cd'],
        'machineNo' => $session['machine_no'],

        'betAmount' => $betData['betAmount'],
        'creditBefore' => $betData['creditBefore'],
        'creditAfter' => $betData['creditAfter'],
        'totalBetsInSession' => $betData['totalBets'] ?? 1,

        'timestamp' => date('c'), // ISO 8601
        'currency' => $session['currency'] ?? 'JPY'
    ];
}

/**
 * 勝利イベントコールバックデータ構築
 *
 * @param array $session ゲームセッション情報
 * @param array $winData 勝利情報
 * @return array コールバックペイロード
 */
function buildWinCallbackData($session, $winData) {
    return [
        'sessionId' => $session['session_id'],
        'memberNo' => $session['member_no'],
        'userId' => $session['partner_user_id'] ?? $session['user_id'],
        'modelId' => $session['model_cd'],
        'machineNo' => $session['machine_no'],

        'winAmount' => $winData['winAmount'],
        'winType' => $winData['winType'] ?? 'normal', // normal, bonus, jackpot
        'creditBefore' => $winData['creditBefore'],
        'creditAfter' => $winData['creditAfter'],
        'totalWinsInSession' => $winData['totalWins'] ?? 1,

        'timestamp' => date('c'), // ISO 8601
        'currency' => $session['currency'] ?? 'JPY'
    ];
}

/**
 * コールバックデータ構築（game.ended用）
 * 韓国チーム対応: 正確なポイントデータ構造
 *
 * @param array $session ゲームセッション情報
 * @param array $result ゲーム結果情報
 * @return array コールバックペイロード
 */
function buildCallbackData($session, $result) {
    $startedAt = new DateTime($session['started_at']);
    $endedAt = new DateTime($result['endedAt'] ?? 'now');
    $duration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

    // 韓国チーム対応: 正確なポイント計算
    $initialBalance = isset($session['initial_balance']) ? (int)$session['initial_balance'] : 0;
    $totalBets = isset($session['total_bets']) ? (int)$session['total_bets'] : 0;
    $totalWins = isset($session['total_wins']) ? (int)$session['total_wins'] : 0;
    $finalBalance = $result['newBalance'] ?? null;

    // 純損益を計算（最終残高 - 初期残高）
    $netProfit = $finalBalance !== null ? ($finalBalance - $initialBalance) : 0;

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
            'initial' => $initialBalance,      // ✅ ゲーム開始時の残高
            'consumed' => $totalBets,          // ✅ 累計ベット額
            'won' => $totalWins,               // ✅ 累計勝利額
            'final' => $finalBalance,          // ✅ 最終残高
            'net' => $netProfit                // ✅ 純損益（final - initial）
        ],

        // ★ 追加: 韓国チーム対応 - 統計情報を別フィールドで提供
        'statistics' => [
            'totalBets' => $totalBets,         // ✅ 累計ベット額（統計用）
            'totalWins' => $totalWins          // ✅ 累計勝利額（統計用）
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
