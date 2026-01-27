<?php
/**
 * game_sessionsテーブルにカラム追加
 * SDK v1.1.0
 */

header('Content-Type: text/plain; charset=utf-8');

require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "🚀 game_sessionsテーブル更新開始...\n\n";

    // 1. member_noカラム追加
    echo "📋 member_noカラム追加中...\n";
    try {
        $pdo->exec("ALTER TABLE game_sessions ADD COLUMN member_no INT(10) UNSIGNED NULL COMMENT 'NET8側のmst_member.member_noとの紐づけ'");
        echo "✅ member_noカラム追加完了\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  member_noカラムは既に存在します\n";
        } else {
            throw $e;
        }
    }

    // 2. partner_user_idカラム追加
    echo "📋 partner_user_idカラム追加中...\n";
    try {
        $pdo->exec("ALTER TABLE game_sessions ADD COLUMN partner_user_id VARCHAR(255) NULL COMMENT 'パートナー側のユーザーID'");
        echo "✅ partner_user_idカラム追加完了\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️  partner_user_idカラムは既に存在します\n";
        } else {
            throw $e;
        }
    }

    // 3. インデックス追加
    echo "📋 インデックス追加中...\n";
    try {
        $pdo->exec("ALTER TABLE game_sessions ADD INDEX idx_member_no (member_no)");
        echo "✅ member_noインデックス追加完了\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "ℹ️  member_noインデックスは既に存在します\n";
        } else {
            throw $e;
        }
    }

    try {
        $pdo->exec("ALTER TABLE game_sessions ADD INDEX idx_partner_user_id (partner_user_id)");
        echo "✅ partner_user_idインデックス追加完了\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "ℹ️  partner_user_idインデックスは既に存在します\n";
        } else {
            throw $e;
        }
    }

    // 4. テーブル構造確認
    echo "\n📋 game_sessionsテーブル構造:\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        echo "  - {$col['Field']}: {$col['Type']} " . ($col['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }

    echo "\n✅ game_sessionsテーブル更新完了！\n";

} catch (Exception $e) {
    http_response_code(500);
    echo "❌ エラー: " . $e->getMessage() . "\n";
    echo "スタックトレース:\n" . $e->getTraceAsString() . "\n";
}
