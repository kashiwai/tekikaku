<?php
/**
 * game_sessionsテーブル即座修正
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "game_sessions テーブル修正開始\n";
    echo "========================================\n\n";

    // 現在の構造
    echo "=== 現在の構造 ===\n";
    $cols = $pdo->query("SHOW COLUMNS FROM game_sessions")->fetchAll(PDO::FETCH_ASSOC);
    $existingCols = array_column($cols, 'Field');
    foreach ($cols as $col) {
        echo "  {$col['Field']}: {$col['Type']}\n";
    }
    echo "\n";

    // partner_user_id追加
    if (!in_array('partner_user_id', $existingCols)) {
        echo "⚠️  partner_user_id カラムがありません。追加中...\n";
        $pdo->exec("ALTER TABLE game_sessions ADD COLUMN partner_user_id VARCHAR(255) NULL COMMENT 'パートナー側のユーザーID'");
        $pdo->exec("ALTER TABLE game_sessions ADD INDEX idx_partner_user_id (partner_user_id)");
        echo "✅ partner_user_id カラム追加完了\n\n";
    } else {
        echo "✅ partner_user_id カラムは既に存在します\n\n";
    }

    // member_no追加
    if (!in_array('member_no', $existingCols)) {
        echo "⚠️  member_no カラムがありません。追加中...\n";
        $pdo->exec("ALTER TABLE game_sessions ADD COLUMN member_no INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ'");
        $pdo->exec("ALTER TABLE game_sessions ADD INDEX idx_member_no (member_no)");
        echo "✅ member_no カラム追加完了\n\n";
    } else {
        echo "✅ member_no カラムは既に存在します\n\n";
    }

    // 修正後の構造
    echo "=== 修正後の構造 ===\n";
    $cols = $pdo->query("SHOW COLUMNS FROM game_sessions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']}: {$col['Type']}\n";
    }
    echo "\n";

    echo "========================================\n";
    echo "✅ 修正完了\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "コード: " . $e->getCode() . "\n";
}
