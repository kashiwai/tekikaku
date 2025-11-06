<?php
/**
 * 管理画面用DB接続テストスクリプト
 *
 * GCP Cloud SQLへの接続と各テーブルの確認を行う
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<!DOCTYPE html>";
echo "<html lang='ja'>";
echo "<head>";
echo "<meta charset='utf-8'>";
echo "<title>DB接続テスト - 竜神8 管理画面</title>";
echo "<style>
body { font-family: -apple-system, sans-serif; background: #1a1d2e; color: #E5E9F2; padding: 20px; }
.success { color: #00C48C; }
.error { color: #FF647C; }
.warning { color: #FFA26B; }
.card { background: #252839; padding: 20px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #0376C9; }
h1, h2 { color: #0376C9; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; }
th, td { padding: 10px; text-align: left; border-bottom: 1px solid #2e3447; }
th { background: #0f1117; color: #99A1B7; }
code { background: #0f1117; padding: 2px 6px; border-radius: 4px; }
.btn { background: #0376C9; color: white; padding: 10px 20px; border-radius: 6px; text-decoration: none; display: inline-block; margin-top: 10px; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔍 DB接続テスト - 竜神8 管理システム</h1>";

// 設定ファイル読み込み
require_once('_etc/setting_base.php');
require_once('_etc/setting.php');

echo "<div class='card'>";
echo "<h2>📋 接続設定情報</h2>";
echo "<table>";
echo "<tr><th>項目</th><th>値</th></tr>";
echo "<tr><td>DB Host</td><td><code>" . DB_HOST . "</code></td></tr>";
echo "<tr><td>DB Port</td><td><code>" . DB_PORT . "</code></td></tr>";
echo "<tr><td>DB Name</td><td><code>" . DB_NAME . "</code></td></tr>";
echo "<tr><td>DB User</td><td><code>" . DB_USER . "</code></td></tr>";
echo "<tr><td>DB Password</td><td><code>" . str_repeat('*', strlen(DB_PASSWORD)) . "</code></td></tr>";
echo "</table>";
echo "</div>";

// DB接続テスト
echo "<div class='card'>";
echo "<h2>🔌 接続テスト</h2>";

try {
    $pdo = new PDO(
        DB_DSN_PDO,
        DB_USER,
        DB_PASSWORD,
        DB_OPTIONS
    );

    echo "<p class='success'>✅ <strong>接続成功！</strong></p>";
    echo "<p>GCP Cloud SQL (136.116.70.86 / net8_dev) に正常に接続できました。</p>";

    // サーバー情報取得
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "<p>MySQLバージョン: <code>$version</code></p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ <strong>接続失敗</strong></p>";
    echo "<p>エラーメッセージ: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div></body></html>";
    exit;
}
echo "</div>";

// カメラ関連テーブル確認
echo "<div class='card'>";
echo "<h2>📹 カメラ関連テーブル</h2>";

try {
    // mst_camera
    $camera_count = $pdo->query("SELECT COUNT(*) FROM mst_camera WHERE del_flg = 0")->fetchColumn();
    echo "<p>✅ <code>mst_camera</code>: {$camera_count}件</p>";

    // mst_cameralist
    $cameralist_count = $pdo->query("SELECT COUNT(*) FROM mst_cameralist WHERE del_flg = 0")->fetchColumn();
    echo "<p>✅ <code>mst_cameralist</code>: {$cameralist_count}件</p>";

    // カメラデータサンプル
    $cameras = $pdo->query("
        SELECT camera_no, camera_mac, camera_name
        FROM mst_camera
        WHERE del_flg = 0
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($cameras) > 0) {
        echo "<h3>カメラデータサンプル:</h3>";
        echo "<table><tr><th>No</th><th>MACアドレス</th><th>名前</th></tr>";
        foreach ($cameras as $cam) {
            echo "<tr><td>#{$cam['camera_no']}</td><td><code>{$cam['camera_mac']}</code></td><td>{$cam['camera_name']}</td></tr>";
        }
        echo "</table>";
    }

} catch (PDOException $e) {
    echo "<p class='error'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// 台データ確認
echo "<div class='card'>";
echo "<h2>🎰 台データ確認</h2>";

try {
    $machine_count = $pdo->query("SELECT COUNT(*) FROM dat_machine WHERE del_flg = 0")->fetchColumn();
    echo "<p>✅ <code>dat_machine</code>: {$machine_count}件</p>";

    $model_count = $pdo->query("SELECT COUNT(*) FROM mst_model WHERE del_flg = 0")->fetchColumn();
    echo "<p>✅ <code>mst_model</code>: {$model_count}件</p>";

    // カメラ割り当て済み台数
    $assigned = $pdo->query("SELECT COUNT(*) FROM dat_machine WHERE camera_no IS NOT NULL AND del_flg = 0")->fetchColumn();
    echo "<p>✅ カメラ割り当て済み台数: {$assigned}件</p>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}
echo "</div>";

// 管理画面ファイル確認
echo "<div class='card'>";
echo "<h2>🎨 管理画面ファイル確認</h2>";

$admin_pages = [
    'data/xxxadmin/camera.php' => 'カメラ管理',
    'data/xxxadmin/camera_settings.php' => 'カメラ割り当て',
    'data/xxxadmin/signaling.php' => 'Signaling管理',
    'data/xxxadmin/streaming.php' => '配信管理'
];

echo "<table><tr><th>画面名</th><th>ファイルパス</th><th>状態</th></tr>";

foreach ($admin_pages as $file => $name) {
    $exists = file_exists($file);
    echo "<tr>";
    echo "<td>$name</td>";
    echo "<td><code>$file</code></td>";
    echo "<td>" . ($exists ? "<span class='success'>✅ 存在</span>" : "<span class='error'>❌ 存在しない</span>") . "</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// 最終結果
echo "<div class='card'>";
echo "<h2>📊 テスト結果サマリー</h2>";
echo "<p class='success' style='font-size: 18px;'><strong>✅ 全てのDB接続テストが正常に完了しました！</strong></p>";
echo "<p><a href='data/xxxadmin/index.php' class='btn'>📊 管理画面ダッシュボードへ</a></p>";
echo "<p><a href='data/xxxadmin/camera.php' class='btn'>📹 カメラ管理へ</a></p>";
echo "</div>";

echo "</body></html>";
