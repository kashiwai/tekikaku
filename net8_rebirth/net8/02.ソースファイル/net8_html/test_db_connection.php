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

    // 🔐 管理者ユーザー作成
    echo "\n\n==========================================\n";
    echo "🔐 管理者ユーザー作成\n";
    echo "==========================================\n\n";

    try {
        // 既存の管理者を確認
        $stmt = $pdo->query("SELECT admin_no, admin_id, admin_name FROM mst_admin WHERE del_flg = 0");
        $existing_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($existing_admins) > 0) {
            echo "📋 既存の管理者ユーザー:\n";
            foreach ($existing_admins as $admin) {
                echo "  - ID: {$admin['admin_id']} / 名前: {$admin['admin_name']}\n";
            }
            echo "\n";
        } else {
            echo "ℹ️  既存の管理者ユーザーは存在しません。新規作成します。\n\n";
        }

        // 新しい管理者ユーザーを作成
        $admin_id = 'admin';
        $admin_password = 'admin123'; // 初期パスワード
        $admin_name = 'システム管理者';
        $admin_auth = 9; // 最高権限

        // パスワードをMD5でハッシュ化（既存のシステムに合わせる）
        $password_hash = md5($admin_password);

        // 既に同じIDが存在するかチェック
        $stmt = $pdo->prepare("SELECT admin_no FROM mst_admin WHERE admin_id = :admin_id");
        $stmt->execute(['admin_id' => $admin_id]);
        $exists = $stmt->fetch();

        if ($exists) {
            echo "ℹ️  管理者ID \"$admin_id\" は既に存在します。パスワードを更新します。\n\n";

            // パスワードを更新
            $stmt = $pdo->prepare("
                UPDATE mst_admin
                SET admin_pass = :password,
                    admin_name = :name,
                    admin_auth = :auth,
                    upd_dt = NOW()
                WHERE admin_id = :admin_id
            ");

            $result = $stmt->execute([
                'password' => $password_hash,
                'name' => $admin_name,
                'auth' => $admin_auth,
                'admin_id' => $admin_id
            ]);

            if ($result) {
                echo "✅ 管理者ユーザーを更新しました\n\n";
            }

        } else {
            // 新規作成
            $stmt = $pdo->prepare("
                INSERT INTO mst_admin (
                    admin_id,
                    admin_pass,
                    admin_name,
                    admin_auth,
                    del_flg,
                    add_no,
                    add_dt
                ) VALUES (
                    :admin_id,
                    :password,
                    :name,
                    :auth,
                    0,
                    1,
                    NOW()
                )
            ");

            $result = $stmt->execute([
                'admin_id' => $admin_id,
                'password' => $password_hash,
                'name' => $admin_name,
                'auth' => $admin_auth
            ]);

            if ($result) {
                echo "✅ 管理者ユーザーを作成しました\n\n";
            }
        }

        // ログイン情報を表示
        echo "🔑 ログイン情報:\n";
        echo "  URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php\n";
        echo "  ユーザーID: $admin_id\n";
        echo "  パスワード: $admin_password\n";
        echo "  権限レベル: $admin_auth (最高権限)\n\n";
        echo "⚠️  重要: ログイン後、必ずパスワードを変更してください！\n\n";

    } catch (PDOException $e) {
        echo "❌ 管理者作成エラー: " . $e->getMessage() . "\n\n";
    }

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
