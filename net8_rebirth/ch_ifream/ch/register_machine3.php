<?php
/**
 * マシン#3 登録スクリプト
 * Windows PCセットアップエラー対応
 */

require_once(__DIR__ . '/_etc/setting_base.php');

$machine_no = 3;
$camera_no = 3;
$signaling_id = 'PEER003';
$mac_address = '34:a6:ef:00:0e:a9';
$machine_name = 'CAMERA-001-0096';

try {
    // データベース接続
    $pdo = new PDO(
        "mysql:host={$GLOBALS['DB_HOST']};port={$GLOBALS['DB_PORT']};dbname={$GLOBALS['DB_NAME']};charset=utf8mb4",
        $GLOBALS['DB_USER'],
        $GLOBALS['DB_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 既存マシン#1からデフォルト値を取得
    $stmt = $pdo->query("SELECT model_no, convert_no FROM dat_machine WHERE machine_no = 1 LIMIT 1");
    $defaults = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$defaults) {
        // デフォルト値がない場合は固定値を使用
        $defaults = ['model_no' => 1, 'convert_no' => 1];
    }

    // マシン#3を登録
    $sql = "INSERT INTO dat_machine
        (camera_no, signaling_id, model_no, convert_no, release_date, end_date, machine_status, del_flg, add_dt, upd_dt)
    VALUES
        (:camera_no, :signaling_id, :model_no, :convert_no, CURDATE(), '2099-12-31', 0, 0, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':camera_no' => $camera_no,
        ':signaling_id' => $signaling_id,
        ':model_no' => $defaults['model_no'],
        ':convert_no' => $defaults['convert_no']
    ]);

    $inserted_machine_no = $pdo->lastInsertId();

    echo "<!DOCTYPE html>\n";
    echo "<html><head><meta charset='utf-8'><title>マシン登録完了</title>\n";
    echo "<link rel='stylesheet' href='/data/xxxadmin/assets/admin_modern.css'>\n";
    echo "<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);}</style>\n";
    echo "</head><body>\n";
    echo "<div class='card' style='max-width:600px;text-align:center;animation:fadeIn 0.5s;'>\n";
    echo "<div style='font-size:64px;margin-bottom:20px;'>✅</div>\n";
    echo "<h1 style='color:var(--dark);margin-bottom:24px;'>マシン#3 登録完了！</h1>\n";
    echo "<div style='background:var(--bg);padding:20px;border-radius:8px;margin-bottom:24px;text-align:left;'>\n";
    echo "<div style='margin-bottom:12px;'><strong>machine_no:</strong> <span class='badge badge-primary'>{$inserted_machine_no}</span></div>\n";
    echo "<div style='margin-bottom:12px;'><strong>camera_no:</strong> <span class='badge badge-success'>{$camera_no}</span></div>\n";
    echo "<div style='margin-bottom:12px;'><strong>signaling_id:</strong> <span class='badge badge-primary'>{$signaling_id}</span></div>\n";
    echo "<div style='margin-bottom:12px;'><strong>mac_address:</strong> <code style='padding:4px 8px;background:white;border-radius:4px;'>{$mac_address}</code></div>\n";
    echo "<div style='margin-bottom:12px;'><strong>model_no:</strong> {$defaults['model_no']}</div>\n";
    echo "<div><strong>convert_no:</strong> {$defaults['convert_no']}</div>\n";
    echo "</div>\n";
    echo "<a href='/xxxadmin/menu.php' class='btn btn-primary' style='text-decoration:none;'>管理画面に戻る</a>\n";
    echo "</div></body></html>\n";

} catch (PDOException $e) {
    echo "<!DOCTYPE html>\n";
    echo "<html><head><meta charset='utf-8'><title>エラー</title></head><body>\n";
    echo "<h1>❌ エラーが発生しました</h1>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p><a href='/xxxadmin/'>管理画面に戻る</a></p>\n";
    echo "</body></html>\n";
}
?>
