<?php
/**
 * game_sessionsテーブルの最新セッション確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "最新SDK game_session確認\n";
    echo "========================================\n\n";

    // 最新のSDKセッションを取得（partner_user_idがある ものだけ）
    $stmt = $pdo->query("
        SELECT *
        FROM game_sessions
        WHERE partner_user_id IS NOT NULL
        ORDER BY created_at DESC
        LIMIT 1
    ");

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($session) {
        echo "✅ SDKセッション found!\n\n";
        foreach ($session as $key => $value) {
            echo sprintf("%-20s: %s\n", $key, $value ?? 'NULL');
        }
    } else {
        echo "⚠️  SDKセッションが見つかりません\n";
    }

    echo "\n========================================\n";

    // sdk_usersテーブルも確認
    echo "最新SDK user:\n\n";
    $stmt = $pdo->query("SELECT * FROM sdk_users ORDER BY created_at DESC LIMIT 1");
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        foreach ($user as $key => $value) {
            echo sprintf("%-20s: %s\n", $key, $value ?? 'NULL');
        }
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
