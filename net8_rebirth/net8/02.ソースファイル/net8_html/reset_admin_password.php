<?php
/**
 * Reset Admin Password
 *
 * 既存管理者のパスワードをリセット
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<pre>";
echo "==========================================\n";
echo "🔐 管理者パスワードリセット\n";
echo "==========================================\n\n";

// 環境変数から直接データベース接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

try {
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✅ データベース接続成功\n\n";

    // テーブル構造を確認
    echo "📋 mst_adminテーブル構造:\n";
    $stmt = $pdo->query("DESCRIBE mst_admin");
    $columns = $stmt->fetchAll();

    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    echo "\n";

    // 既存管理者を確認
    echo "🔍 既存管理者:\n";
    $stmt = $pdo->query("SELECT * FROM mst_admin WHERE del_flg = 0");
    $admins = $stmt->fetchAll();

    foreach ($admins as $admin) {
        echo "  - ID: {$admin['admin_id']} / 名前: {$admin['admin_name']}\n";
    }
    echo "\n";

    // パスワードをリセット
    $new_password = 'admin123';
    $password_hash = md5($new_password);

    echo "🔄 パスワードリセット実行:\n";

    // sradmin のパスワードをリセット
    $stmt = $pdo->prepare("UPDATE mst_admin SET admin_pass = :password WHERE admin_id = 'sradmin'");
    $stmt->execute(['password' => $password_hash]);
    echo "  ✅ sradmin のパスワードをリセットしました\n";

    // spadmin のパスワードをリセット
    $stmt = $pdo->prepare("UPDATE mst_admin SET admin_pass = :password WHERE admin_id = 'spadmin'");
    $stmt->execute(['password' => $password_hash]);
    echo "  ✅ spadmin のパスワードをリセットしました\n\n";

    echo "==========================================\n";
    echo "🔑 ログイン情報\n";
    echo "==========================================\n\n";
    echo "URL: https://mgg-webservice-production.up.railway.app/xxxadmin/login.php\n\n";
    echo "アカウント1:\n";
    echo "  ユーザーID: sradmin\n";
    echo "  パスワード: admin123\n\n";
    echo "アカウント2:\n";
    echo "  ユーザーID: spadmin\n";
    echo "  パスワード: admin123\n\n";
    echo "⚠️  重要: ログイン後、必ずパスワードを変更してください！\n\n";

    echo "==========================================\n";
    echo "✅ 完了\n";
    echo "==========================================\n";

} catch (PDOException $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n\n";
}

echo "</pre>";
?>
