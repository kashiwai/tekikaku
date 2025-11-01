<?php
/**
 * Quick Admin Setup - Standalone Script
 *
 * このスクリプトはsetting.phpを読み込まずに直接データベースに接続し、
 * 管理者ユーザーを作成します。
 */

// エラー表示
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "==========================================\n";
echo "🔐 管理者ユーザー作成 (Quick Setup)\n";
echo "==========================================\n\n";

// 環境変数から直接データベース接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

echo "📋 接続情報:\n";
echo "  - DB_HOST: $db_host\n";
echo "  - DB_NAME: $db_name\n";
echo "  - DB_USER: $db_user\n";
echo "  - DB_PASSWORD: " . str_repeat('*', min(strlen($db_password), 8)) . "\n\n";

try {
    // データベース接続
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ データベース接続成功\n\n";

    // テーブル一覧取得
    echo "📋 テーブル確認:\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

    if (count($tables) > 0) {
        echo "  テーブル数: " . count($tables) . "\n";
        $admin_table_exists = in_array('mst_admin', $tables);
        echo "  mst_admin存在: " . ($admin_table_exists ? '✅ Yes' : '❌ No') . "\n\n";

        if (!$admin_table_exists) {
            echo "⚠️  mst_adminテーブルが存在しません。\n";
            echo "💡 先にsetup_database.phpを実行してください:\n";
            echo "   https://mgg-webservice-production.up.railway.app/setup_database.php\n\n";
            exit;
        }
    } else {
        echo "  ⚠️  テーブルが1つも存在しません\n";
        echo "  💡 setup_database.phpを実行してください\n\n";
        exit;
    }

    // 既存の管理者確認
    echo "🔍 既存管理者確認:\n";
    $stmt = $pdo->query("SELECT admin_no, admin_id, admin_name FROM mst_admin WHERE del_flg = 0");
    $existing_admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($existing_admins) > 0) {
        echo "  既存の管理者:\n";
        foreach ($existing_admins as $admin) {
            echo "    - ID: {$admin['admin_id']} / 名前: {$admin['admin_name']}\n";
        }
        echo "\n";
    } else {
        echo "  既存管理者なし（新規作成します）\n\n";
    }

    // 管理者ユーザー情報
    $admin_id = 'admin';
    $admin_password = 'admin123';
    $admin_name = 'システム管理者';
    $auth_flg = 9; // 最高権限
    $password_hash = md5($admin_password);

    // 既存チェック
    $stmt = $pdo->prepare("SELECT admin_no FROM mst_admin WHERE admin_id = :admin_id");
    $stmt->execute(['admin_id' => $admin_id]);
    $exists = $stmt->fetch();

    if ($exists) {
        echo "🔄 管理者更新:\n";

        // パスワードを更新
        $stmt = $pdo->prepare("
            UPDATE mst_admin
            SET admin_pass = :password,
                admin_name = :name,
                auth_flg = :auth,
                upd_dt = NOW()
            WHERE admin_id = :admin_id
        ");

        $stmt->execute([
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $auth_flg,
            'admin_id' => $admin_id
        ]);

        echo "  ✅ 管理者「{$admin_id}」を更新しました\n\n";

    } else {
        echo "➕ 管理者作成:\n";

        // 新規作成
        $stmt = $pdo->prepare("
            INSERT INTO mst_admin (
                admin_id,
                admin_pass,
                admin_name,
                auth_flg,
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

        $stmt->execute([
            'admin_id' => $admin_id,
            'password' => $password_hash,
            'name' => $admin_name,
            'auth' => $auth_flg
        ]);

        echo "  ✅ 管理者「{$admin_id}」を作成しました\n\n";
    }

    // ログイン情報表示
    echo "==========================================\n";
    echo "🔑 ログイン情報\n";
    echo "==========================================\n\n";
    echo "URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php\n\n";
    echo "ユーザーID: {$admin_id}\n";
    echo "パスワード: {$admin_password}\n\n";
    echo "権限レベル: {$auth_flg} (最高権限)\n\n";
    echo "⚠️  重要: ログイン後、必ずパスワードを変更してください！\n\n";

    echo "==========================================\n";
    echo "✅ セットアップ完了\n";
    echo "==========================================\n\n";

    echo "次のステップ:\n";
    echo "1. 上記URLにアクセス\n";
    echo "2. ログイン情報を入力\n";
    echo "3. ログイン後、パスワード変更\n";
    echo "4. このスクリプトを削除（セキュリティのため）\n\n";

} catch (PDOException $e) {
    echo "❌ エラー発生\n\n";
    echo "エラーメッセージ:\n";
    echo "  " . $e->getMessage() . "\n\n";
    echo "エラーコード: " . $e->getCode() . "\n\n";
}

echo "</pre>";
?>
