<?php
/**
 * データベース接続時の実際のIPアドレスを確認
 * GCP Cloud SQLのログと照合して、どのIPから接続されているか確認
 */

// 設定ファイル読み込み
require_once(__DIR__ . '/_etc/setting.php');

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='utf-8'>
    <title>DB Connection IP Check</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { border-left: 4px solid #28a745; }
        .error { border-left: 4px solid #dc3545; }
        .info { border-left: 4px solid #007bff; }
        h2 { margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .ip { font-size: 20px; color: #007bff; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection IP Check</h1>
";

// 1. RailwayサーバーのIPアドレス
echo "<div class='box info'>";
echo "<h2>1️⃣ Railway Server IP (外部から見たIP)</h2>";
$railway_ip = @file_get_contents('https://api.ipify.org');
if ($railway_ip) {
    echo "<div class='ip'>$railway_ip</div>";
    echo "<p>このIPアドレスをGCP Cloud SQLの承認済みネットワークに追加する必要があります。</p>";
} else {
    echo "<p style='color: red;'>取得失敗</p>";
}
echo "</div>";

// 2. データベース接続テスト
echo "<div class='box'>";
echo "<h2>2️⃣ Database Connection Test</h2>";

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);
    echo "<div class='success'>";
    echo "<h3>✅ 接続成功</h3>";
    echo "<p>データベースに正常に接続できました。</p>";

    // 接続情報を表示
    echo "<h4>接続情報:</h4>";
    echo "<pre>";
    echo "DB_HOST: " . DB_HOST . "\n";
    echo "DB_PORT: " . DB_PORT . "\n";
    echo "DB_NAME: " . DB_NAME . "\n";
    echo "DB_USER: " . DB_USER . "\n";
    echo "</pre>";

    // MySQL変数を確認
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'hostname'");
    $hostname = $stmt->fetch(PDO::FETCH_ASSOC);

    echo "<h4>MySQL Server Info:</h4>";
    echo "<pre>";
    $version = $pdo->query("SELECT VERSION()")->fetchColumn();
    echo "MySQL Version: $version\n";
    if ($hostname) {
        echo "Hostname: " . $hostname['Value'] . "\n";
    }
    echo "</pre>";

    echo "</div>";

} catch (PDOException $e) {
    echo "<div class='error'>";
    echo "<h3>❌ 接続失敗</h3>";
    echo "<p style='color: red;'>データベースに接続できません。</p>";
    echo "<h4>エラー詳細:</h4>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";

    echo "<h4>考えられる原因:</h4>";
    echo "<ul>";
    echo "<li>GCP Cloud SQLの承認済みネットワークに <strong class='ip'>$railway_ip</strong> が登録されていない</li>";
    echo "<li>データベース認証情報が間違っている</li>";
    echo "<li>GCP Cloud SQLのパブリックIPが無効になっている</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>";

// 3. GCP設定手順
echo "<div class='box info'>";
echo "<h2>3️⃣ GCP Cloud SQL 設定手順</h2>";
echo "<ol>";
echo "<li>GCP Console → SQL → インスタンス選択</li>";
echo "<li>「接続」 → 「ネットワーキング」</li>";
echo "<li>「承認済みネットワーク」 → 「ネットワークを追加」</li>";
echo "<li>名前: <code>Railway Production</code></li>";
echo "<li>ネットワーク: <code class='ip'>$railway_ip/32</code></li>";
echo "<li>保存</li>";
echo "</ol>";

echo "<h4>⚠️ 重要な注意点:</h4>";
echo "<ul>";
echo "<li><code>0.0.0.0/0</code> がある場合は削除してください（全世界に公開されています）</li>";
echo "<li>Railwayは再デプロイ時にIPが変わる可能性があります</li>";
echo "<li>IP変更時はこのページで新しいIPを確認して再設定してください</li>";
echo "</ul>";
echo "</div>";

// 4. サーバー環境情報
echo "<div class='box'>";
echo "<h2>4️⃣ Server Environment</h2>";
echo "<pre>";
echo "REMOTE_ADDR: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "\n";
echo "SERVER_ADDR: " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "\n";
echo "HTTP_X_FORWARDED_FOR: " . ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'N/A') . "\n";
echo "RAILWAY_ENVIRONMENT: " . (getenv('RAILWAY_ENVIRONMENT') ?: 'Not Railway') . "\n";
echo "</pre>";
echo "</div>";

echo "</body></html>";
?>
