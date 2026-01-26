<?php
/**
 * 最新ゲームセッション確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "最新ゲームセッション確認\n";
    echo "========================================\n\n";

    // 最新のセッション
    $stmt = $pdo->query("
        SELECT
            gs.session_id,
            gs.partner_user_id,
            gs.member_no,
            gs.points_consumed,
            gs.points_won,
            gs.result,
            gs.status,
            gs.created_at,
            gs.ended_at,
            m.nickname,
            m.point as current_balance,
            ak.name as partner_name
        FROM game_sessions gs
        LEFT JOIN mst_member m ON gs.member_no = m.member_no
        LEFT JOIN api_keys ak ON gs.api_key_id = ak.id
        WHERE gs.partner_user_id IS NOT NULL
        ORDER BY gs.created_at DESC
        LIMIT 1
    ");

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        echo "✅ 最新SDKセッション:\n\n";
        foreach ($session as $key => $value) {
            echo sprintf("%-20s: %s\n", $key, $value ?? 'NULL');
        }

        // トランザクション履歴
        echo "\n--- ポイントトランザクション ---\n\n";
        $txStmt = $pdo->prepare("
            SELECT transaction_id, amount, balance_before, balance_after, created_at
            FROM point_transactions
            WHERE game_session_id = :sid
            ORDER BY created_at ASC
        ");
        $txStmt->execute(['sid' => $session['session_id']]);
        $transactions = $txStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($transactions as $i => $tx) {
            echo sprintf("%d. %s: %+d ポイント (%s → %s)\n",
                $i + 1,
                $tx['transaction_id'],
                $tx['amount'],
                $tx['balance_before'],
                $tx['balance_after']
            );
        }
    } else {
        echo "⚠️  SDKセッションが見つかりません\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
