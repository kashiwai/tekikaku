<?php
/**
 * SLOT-107 と NANGOKU01 の layout_data 修正スクリプト
 *
 * アクセス URL:
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/fix_slot_layout.php
 */
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once('../../_etc/setting.php');

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

header('Content-Type: text/html; charset=UTF-8');
echo "<html><head><meta charset='UTF-8'><title>Slot Layout Data Fix</title></head><body>";
echo "<h1>スロット機種 layout_data 修正スクリプト</h1>";
echo "<hr>";

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>✅ データベース接続成功</div>";

    // 現在の状態確認
    echo "<h2>修正前の状態</h2>";
    echo "<pre>";
    $stmt = $pdo->query("SELECT model_cd, model_name, layout_data FROM mst_model WHERE model_cd IN ('SLOT-107', 'NANGOKU01') ORDER BY model_cd");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "【{$row['model_cd']}】{$row['model_name']}\n";
        if (empty($row['layout_data'])) {
            echo "  ❌ layout_data: 未設定\n\n";
        } else {
            echo "  現在値: {$row['layout_data']}\n\n";
        }
    }
    echo "</pre>";

    // SLOT-107 の layout_data を設定
    echo "<h2>SLOT-107 (秘宝伝) 修正</h2>";
    $slot107_layout = json_encode([
        "video_portrait" => 0,
        "video_mode" => 4,
        "drum" => 0,
        "bonus_push" => [
            ["label" => "select", "path" => "noselect_bonus.png"]
        ],
        "version" => 1,
        "hide" => []
    ], JSON_UNESCAPED_UNICODE);

    echo "<pre>設定値: {$slot107_layout}</pre>";

    $stmt = $pdo->prepare("
        UPDATE mst_model SET
            layout_data = :layout_data,
            upd_no = 1,
            upd_dt = NOW()
        WHERE model_cd = 'SLOT-107'
    ");

    $stmt->execute(['layout_data' => $slot107_layout]);
    echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>✅ 更新成功（影響行数: " . $stmt->rowCount() . "）</div>";

    // NANGOKU01 の layout_data を強化
    echo "<h2>NANGOKU01 (南国育ち) 修正</h2>";
    $nangoku_layout = json_encode([
        "video_portrait" => 0,
        "video_mode" => 4,
        "drum" => 0,
        "bonus_push" => [
            ["label" => "select", "path" => "noselect_bonus.png"]
        ],
        "version" => 1,
        "lightsofftime" => 200,
        "orderretry" => 3,
        "hide" => []
    ], JSON_UNESCAPED_UNICODE);

    echo "<pre>設定値: {$nangoku_layout}</pre>";

    $stmt = $pdo->prepare("
        UPDATE mst_model SET
            layout_data = :layout_data,
            upd_no = 1,
            upd_dt = NOW()
        WHERE model_cd = 'NANGOKU01'
    ");

    $stmt->execute(['layout_data' => $nangoku_layout]);
    echo "<div style='background: #e8f5e9; padding: 10px; margin: 10px 0;'>✅ 更新成功（影響行数: " . $stmt->rowCount() . "）</div>";

    // 修正後の確認
    echo "<h2>修正後の確認</h2>";
    echo "<pre>";
    $stmt = $pdo->query("SELECT model_cd, model_name, layout_data FROM mst_model WHERE model_cd IN ('SLOT-107', 'NANGOKU01') ORDER BY model_cd");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "【{$row['model_cd']}】{$row['model_name']}\n";
        if (!empty($row['layout_data'])) {
            $json = json_decode($row['layout_data'], true);
            echo json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
        } else {
            echo "  ❌ 未設定\n\n";
        }
    }
    echo "</pre>";

    echo "<div style='background: #e8f5e9; padding: 20px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
    echo "<h2 style='color: #2e7d32; margin-top: 0;'>✅ 修正完了</h2>";
    echo "<p>SLOT-107 と NANGOKU01 の layout_data が正常に更新されました。</p>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<div style='background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0;'>";
    echo "<h2 style='color: #c62828; margin-top: 0;'>❌ データベースエラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
} catch (Exception $e) {
    echo "<div style='background: #fff3e0; padding: 20px; border-left: 4px solid #ff9800; margin: 20px 0;'>";
    echo "<h2 style='color: #f57c00; margin-top: 0;'>❌ エラー</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
