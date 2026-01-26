<?php
/**
 * GCP MySQL完全構造確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "GCP MySQL 完全構造確認\n";
    echo "========================================\n\n";

    // 全テーブル一覧
    echo "=== 全テーブル一覧 ===\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }
    echo "\n";

    // game_sessionsテーブル構造
    echo "=== game_sessions テーブル構造 ===\n";
    $cols = $pdo->query("SHOW COLUMNS FROM game_sessions")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $col) {
        echo "  {$col['Field']}: {$col['Type']} {$col['Null']} {$col['Key']}\n";
    }
    echo "\n";

    // api_keysテーブル存在確認
    echo "=== api_keys テーブル ===\n";
    $apiKeysExists = in_array('api_keys', $tables);
    if ($apiKeysExists) {
        echo "✅ 存在します\n";
        $cols = $pdo->query("SHOW COLUMNS FROM api_keys")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  {$col['Field']}: {$col['Type']}\n";
        }
        $count = $pdo->query("SELECT COUNT(*) FROM api_keys")->fetchColumn();
        echo "  レコード数: {$count}\n";
    } else {
        echo "❌ 存在しません\n";
    }
    echo "\n";

    // sdk_usersテーブル
    echo "=== sdk_users テーブル ===\n";
    $sdkUsersExists = in_array('sdk_users', $tables);
    if ($sdkUsersExists) {
        echo "✅ 存在します\n";
        $cols = $pdo->query("SHOW COLUMNS FROM sdk_users")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $col) {
            echo "  {$col['Field']}: {$col['Type']}\n";
        }
        $count = $pdo->query("SELECT COUNT(*) FROM sdk_users")->fetchColumn();
        echo "  レコード数: {$count}\n";
    } else {
        echo "❌ 存在しません\n";
    }
    echo "\n";

    // user_balancesテーブル
    echo "=== user_balances テーブル ===\n";
    $userBalancesExists = in_array('user_balances', $tables);
    if ($userBalancesExists) {
        echo "✅ 存在します\n";
        $count = $pdo->query("SELECT COUNT(*) FROM user_balances")->fetchColumn();
        echo "  レコード数: {$count}\n";
    } else {
        echo "❌ 存在しません\n";
    }
    echo "\n";

    echo "========================================\n";
    echo "確認完了\n";
    echo "========================================\n";

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
