<?php
/**
 * Railway MySQL 5.7 接続テストスクリプト
 *
 * このスクリプトは以下を確認します：
 * 1. .env.railway ファイルの読み込み（ローカル環境のみ）
 * 2. 環境変数の設定状態
 * 3. データベース接続の成否
 * 4. MySQLバージョン
 * 5. テーブル一覧
 */

// 設定ファイル読み込み
require_once(__DIR__ . '/_etc/setting.php');

echo "==========================================\n";
echo "🔍 Railway MySQL 5.7 接続テスト\n";
echo "==========================================\n\n";

// 環境情報の表示
echo "📋 環境情報:\n";
echo "  - Railway環境: " . (getenv('RAILWAY_ENVIRONMENT') ? 'Yes ✓' : 'No (ローカル環境)') . "\n";
echo "  - .env.railway読み込み: " . (file_exists(__DIR__ . '/_etc/../../../.env.railway') ? 'Yes ✓' : 'No ✗') . "\n\n";

// 接続設定の表示
echo "🔧 接続設定:\n";
echo "  - DB_HOST: " . DB_HOST . "\n";
echo "  - DB_PORT: 3306\n";
echo "  - DB_NAME: " . DB_NAME . "\n";
echo "  - DB_USER: " . DB_USER . "\n";
echo "  - DB_PASSWORD: " . str_repeat('*', min(strlen(DB_PASSWORD), 8)) . "\n";
echo "  - DB_CHARSET: " . DB_CHARSET . "\n\n";

echo "🌐 シグナリングサーバー設定:\n";
echo "  - SIGNALING_HOST: " . SIGNALING_HOST . "\n";
echo "  - SIGNALING_PORT: " . SIGNALING_PORT . "\n";
echo "  - SIGNALING_KEY: " . SIGNALING_KEY . "\n\n";

// データベース接続テスト
echo "🔌 データベース接続テスト:\n";

try {
    // PDOでの接続テスト
    $pdo = new PDO(
        DB_DSN_PDO,
        DB_USER,
        DB_PASSWORD,
        DB_OPTIONS
    );

    echo "  ✅ 接続成功！\n\n";

    // MySQLバージョン確認
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "📊 MySQL情報:\n";
    echo "  - Version: {$version}\n";

    // 文字セット確認
    $charset = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'")->fetch(PDO::FETCH_ASSOC);
    echo "  - Charset: {$charset['Value']}\n";

    // 照合順序確認
    $collation = $pdo->query("SHOW VARIABLES LIKE 'collation_database'")->fetch(PDO::FETCH_ASSOC);
    echo "  - Collation: {$collation['Value']}\n\n";

    // テーブル一覧取得
    echo "📋 テーブル一覧:\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        foreach ($tables as $table) {
            // テーブルのレコード数を取得
            $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            echo "  - {$table} ({$count} records)\n";
        }
    } else {
        echo "  ⚠️  テーブルが存在しません\n";
        echo "  💡 データベースにテーブルをインポートしてください\n";
    }

    echo "\n==========================================\n";
    echo "✅ テスト完了！すべて正常です\n";
    echo "==========================================\n";

} catch (PDOException $e) {
    echo "  ❌ 接続失敗\n\n";
    echo "エラー詳細:\n";
    echo "  - Message: " . $e->getMessage() . "\n";
    echo "  - Code: " . $e->getCode() . "\n\n";

    echo "==========================================\n";
    echo "🔍 トラブルシューティング\n";
    echo "==========================================\n\n";

    if (strpos($e->getMessage(), 'Connection refused') !== false) {
        echo "❌ 問題: Connection refused\n\n";
        echo "解決策:\n";
        echo "1. Railway MySQLのPublic Networkingが有効か確認\n";
        echo "2. DB_HOSTが正しいか確認:\n";
        echo "   - ローカル: meticulous-vitality-production-f216.up.railway.app\n";
        echo "   - Railway: meticulous-vitality.railway.internal\n";
        echo "3. ファイアウォール設定を確認\n";
    } elseif (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "❌ 問題: Access denied\n\n";
        echo "解決策:\n";
        echo "1. DB_USERとDB_PASSWORDを確認\n";
        echo "2. .env.railway の設定を確認\n";
        echo "3. Railwayダッシュボードで認証情報を確認\n";
    } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
        echo "❌ 問題: Unknown database\n\n";
        echo "解決策:\n";
        echo "1. データベース名を確認: {DB_NAME}\n";
        echo "2. Railwayダッシュボードでデータベースが作成されているか確認\n";
        echo "3. 必要に応じてCREATE DATABASEを実行\n";
    } else {
        echo "❌ 予期しないエラー\n\n";
        echo "解決策:\n";
        echo "1. エラーメッセージを確認\n";
        echo "2. RAILWAY_DB_SETUP.mdのトラブルシューティングを参照\n";
        echo "3. Railway Deploy Logsを確認\n";
    }

    echo "\n==========================================\n";
    exit(1);
}
?>
