<?php
/**
 * game_sessionsテーブルの最新セッション詳細確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "最新game_session詳細確認\n";
    echo "========================================\n\n";

    // 最新のセッションを取得
    $stmt = $pdo->query("
        SELECT
            gs.session_id,
            gs.partner_user_id,
            gs.member_no,
            gs.api_key_id,
            gs.machine_no,
            gs.model_cd,
            gs.points_consumed,
            gs.status,
            gs.created_at,
            m.nickname,
            m.email,
            ak.name as partner_name
        FROM game_sessions gs
        LEFT JOIN mst_member m ON gs.member_no = m.member_no
        LEFT JOIN api_keys ak ON gs.api_key_id = ak.id
        ORDER BY gs.created_at DESC
        LIMIT 5
    ");

    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "最新5セッション:\n\n";
    foreach ($sessions as $i => $session) {
        echo "=== セッション " . ($i + 1) . " ===\n";
        echo "session_id: " . ($session['session_id'] ?? 'NULL') . "\n";
        echo "partner_user_id: " . ($session['partner_user_id'] ?? 'NULL') . "\n";
        echo "member_no: " . ($session['member_no'] ?? 'NULL') . "\n";
        echo "nickname: " . ($session['nickname'] ?? 'NULL') . "\n";
        echo "email: " . ($session['email'] ?? 'NULL') . "\n";
        echo "partner_name: " . ($session['partner_name'] ?? 'NULL') . "\n";
        echo "api_key_id: " . ($session['api_key_id'] ?? 'NULL') . "\n";
        echo "machine_no: " . ($session['machine_no'] ?? 'NULL') . "\n";
        echo "model_cd: " . ($session['model_cd'] ?? 'NULL') . "\n";
        echo "points_consumed: " . ($session['points_consumed'] ?? 'NULL') . "\n";
        echo "status: " . ($session['status'] ?? 'NULL') . "\n";
        echo "created_at: " . ($session['created_at'] ?? 'NULL') . "\n";
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
