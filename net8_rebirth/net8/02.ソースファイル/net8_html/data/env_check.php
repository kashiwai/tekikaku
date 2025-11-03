<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
echo "<h1>環境変数チェック</h1>\n";

// setting.phpを読み込む前に環境変数を確認
echo "<h2>環境変数（読み込み前）</h2>\n";
echo "DB_HOST from getenv: " . (getenv('DB_HOST') ?: '(empty)') . "<br>\n";
echo "DB_HOST from \$_ENV: " . ($_ENV['DB_HOST'] ?? '(empty)') . "<br>\n";
echo "DB_HOST from \$_SERVER: " . ($_SERVER['DB_HOST'] ?? '(empty)') . "<br>\n";
echo "RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ?: '(empty)') . "<br>\n";

// setting.phpを読み込む
require_once(__DIR__ . '/../_etc/setting.php');

echo "<h2>定数（読み込み後）</h2>\n";
echo "DB_HOST: " . DB_HOST . "<br>\n";
echo "DB_NAME: " . DB_NAME . "<br>\n";
echo "DB_USER: " . DB_USER . "<br>\n";
echo "DB_PASSWORD: " . (defined('DB_PASSWORD') ? '(set)' : '(not set)') . "<br>\n";
echo "DB_DSN: " . DB_DSN . "<br>\n";

echo "<h2>接続テスト</h2>\n";
try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
    echo "✅ PDO接続成功!<br>\n";
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM dat_machine");
    $result = $stmt->fetch();
    echo "✅ クエリ成功! dat_machine件数: " . $result['cnt'] . "<br>\n";
} catch (PDOException $e) {
    echo "❌ PDO接続失敗: " . $e->getMessage() . "<br>\n";
}
?>
