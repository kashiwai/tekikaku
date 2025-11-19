<?php
/**
 * Frame Security for Play Pages
 * プレイページ用のX-Frame-Options設定
 *
 * このファイルをplay_v2/index.phpの最初でインクルードしてください
 */

// データベース接続とヘルパー読み込み
require_once(__DIR__ . '/../../_etc/require_files.php');
require_once(__DIR__ . '/../../api/v1/helpers/frame_security.php');

try {
    $pdo = get_db_connection();

    // マシン番号からAPIキーIDを取得
    $machineNo = $_GET['NO'] ?? null;
    $apiKeyId = null;

    if ($machineNo) {
        $apiKeyId = getApiKeyIdFromMachine($pdo, $machineNo);
    }

    // X-Frame-Options と CSP ヘッダーを設定
    setFrameSecurityHeaders($pdo, $apiKeyId);

} catch (Exception $e) {
    error_log('Frame Security Error: ' . $e->getMessage());

    // エラー時はデフォルトで同一オリジンのみ許可
    header("X-Frame-Options: SAMEORIGIN");
    header("Content-Security-Policy: frame-ancestors 'self'");
}
