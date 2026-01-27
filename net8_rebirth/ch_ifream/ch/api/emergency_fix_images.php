<?php
/**
 * 緊急画像パス修正スクリプト
 * img/model/ プレフィックスを削除
 */
header('Content-Type: text/plain; charset=UTF-8');

// DB接続情報
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "=== 画像パス緊急修正 ===" . PHP_EOL . PHP_EOL;

    // 修正前の状態確認
    echo "【修正前の状態】" . PHP_EOL;
    $stmt = $pdo->query("SELECT model_cd, model_name, image_list
                         FROM mst_model
                         WHERE del_flg = 0
                           AND image_list IS NOT NULL
                           AND image_list != ''
                           AND image_list LIKE 'img/model/%'
                         ORDER BY model_no");
    $before = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($before as $row) {
        echo "  {$row['model_cd']}: {$row['image_list']}" . PHP_EOL;
    }
    echo PHP_EOL;

    // 修正実行
    echo "【修正実行中...】" . PHP_EOL;
    $sql = "UPDATE mst_model
            SET image_list = REPLACE(image_list, 'img/model/', '')
            WHERE del_flg = 0
              AND image_list IS NOT NULL
              AND image_list != ''
              AND image_list LIKE 'img/model/%'";

    $affectedRows = $pdo->exec($sql);
    echo "  修正した件数: {$affectedRows}件" . PHP_EOL . PHP_EOL;

    // 修正後の状態確認
    echo "【修正後の状態】" . PHP_EOL;
    $stmt = $pdo->query("SELECT model_cd, model_name, image_list
                         FROM mst_model
                         WHERE del_flg = 0
                           AND image_list IS NOT NULL
                           AND image_list != ''
                         ORDER BY model_no");
    $after = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($after as $row) {
        echo "  {$row['model_cd']}: {$row['image_list']}" . PHP_EOL;
    }
    echo PHP_EOL;

    echo "=== 修正完了！ ===" . PHP_EOL;

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . PHP_EOL;
}
?>
