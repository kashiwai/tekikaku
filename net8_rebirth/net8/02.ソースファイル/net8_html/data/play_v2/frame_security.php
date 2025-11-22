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
    error_log('❌ Frame Security Error: ' . $e->getMessage());
    error_log('❌ Frame Security Stack Trace: ' . $e->getTraceAsString());

    // エラー時は全オリジンを許可（SDK開発環境対応）
    // 本番環境では管理画面でドメイン登録することを推奨
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");

    // X-Frame-Options と CSP は設定しない（すべてのiFrame埋め込みを許可）
    error_log('✅ Frame Security: Error handler - no CSP/X-Frame-Options set');
}
