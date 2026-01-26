<?php
/**
 * 特定セッションの完全データ確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

$sessionId = $_GET['sid'] ?? '';

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "セッション詳細確認\n";
    echo "========================================\n\n";

    // game_sessionsから取得
    $stmt = $pdo->prepare("
        SELECT
            gs.*,
            su.partner_user_id as sdk_partner_user_id,
            su.email as sdk_email,
            m.nickname,
            m.mail,
            m.point as current_points,
            ak.name as partner_name
        FROM game_sessions gs
        LEFT JOIN sdk_users su ON gs.user_id = su.id
        LEFT JOIN mst_member m ON gs.member_no = m.member_no
        LEFT JOIN api_keys ak ON gs.api_key_id = ak.id
        WHERE gs.session_id = :session_id
    ");
    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        echo "✅ セッション発見!\n\n";
        foreach ($session as $key => $value) {
            echo sprintf("%-30s: %s\n", $key, $value ?? 'NULL');
        }
    } else {
        echo "❌ セッションが見つかりません: {$sessionId}\n";
    }

    // トランザクション履歴
    echo "\n========================================\n";
    echo "ポイントトランザクション履歴\n";
    echo "========================================\n\n";

    $stmt = $pdo->prepare("
        SELECT *
        FROM point_transactions
        WHERE game_session_id = :session_id
        ORDER BY created_at DESC
    ");
    $stmt->execute(['session_id' => $sessionId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($transactions as $i => $tx) {
        echo "=== トランザクション " . ($i + 1) . " ===\n";
        foreach ($tx as $key => $value) {
            echo sprintf("%-20s: %s\n", $key, $value ?? 'NULL');
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
