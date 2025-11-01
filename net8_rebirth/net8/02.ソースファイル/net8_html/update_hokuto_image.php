<?php
/**
 * 北斗の拳4号機の画像パスをデータベースに登録
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>🎨 北斗の拳 画像パス登録</h1>";
echo "<hr>";

// 環境変数から直接データベース接続情報を取得
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: 'db';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'net8pass';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<p>✅ データベース接続成功</p>";
    echo "<p>Host: $db_host / DB: $db_name</p>";
    echo "<hr>";

    // 北斗の拳の機種情報を取得
    $model = $pdo->query("SELECT model_no, model_cd, model_name, model_img_path FROM mst_model WHERE model_cd = 'HOKUTO4GO' AND del_flg = 0")->fetch();

    if (!$model) {
        die("<p>❌ 北斗の拳の機種データが見つかりません。</p>");
    }

    echo "<h2>📋 現在の機種情報</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>機種No</th><th>機種CD</th><th>機種名</th><th>現在の画像パス</th></tr>";
    echo "<tr>";
    echo "<td>{$model['model_no']}</td>";
    echo "<td>{$model['model_cd']}</td>";
    echo "<td>{$model['model_name']}</td>";
    echo "<td>" . ($model['model_img_path'] ?: '<span style="color:red;">未設定</span>') . "</td>";
    echo "</tr>";
    echo "</table>";
    echo "<hr>";

    // 画像パスを更新
    $image_path = 'img/model/hokuto4go.jpg';

    $stmt = $pdo->prepare("
        UPDATE mst_model SET
            model_img_path = :model_img_path,
            upd_no = 1,
            upd_dt = NOW()
        WHERE model_cd = 'HOKUTO4GO' AND del_flg = 0
    ");

    $stmt->execute(['model_img_path' => $image_path]);

    echo "<h2>✅ 画像パス更新完了</h2>";
    echo "<p><b>設定した画像パス:</b> {$image_path}</p>";

    // 更新後の情報を取得
    $updated = $pdo->query("SELECT model_no, model_cd, model_name, model_img_path FROM mst_model WHERE model_cd = 'HOKUTO4GO' AND del_flg = 0")->fetch();

    echo "<h2>📋 更新後の機種情報</h2>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>機種No</th><th>機種CD</th><th>機種名</th><th>画像パス</th></tr>";
    echo "<tr>";
    echo "<td>{$updated['model_no']}</td>";
    echo "<td>{$updated['model_cd']}</td>";
    echo "<td>{$updated['model_name']}</td>";
    echo "<td style='color:green;'><b>{$updated['model_img_path']}</b></td>";
    echo "</tr>";
    echo "</table>";

    echo "<hr>";
    echo "<h2>🔗 次のステップ</h2>";
    echo "<ul>";
    echo "<li><a href='/'>トップページ</a>で北斗の拳の画像が表示されるか確認</li>";
    echo "<li>画像が表示されない場合は、ブラウザのキャッシュをクリア（Ctrl+Shift+R / Cmd+Shift+R）</li>";
    echo "</ul>";

    echo "<hr>";
    echo "<h2>📷 画像プレビュー</h2>";
    echo "<img src='/data/{$image_path}' alt='北斗の拳' style='max-width: 300px; border: 2px solid #333;'>";

} catch (PDOException $e) {
    echo "<h2>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
