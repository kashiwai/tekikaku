<?php
/**
 * yoshimune画像パス修正
 */
header('Content-Type: text/plain; charset=UTF-8');

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

    echo "=== yoshimune画像パス修正 ===" . PHP_EOL . PHP_EOL;

    $sql = "UPDATE mst_model
            SET image_list = 'yoshimune.png'
            WHERE model_cd = 'YOSHIMUNE'
              AND del_flg = 0";

    $affectedRows = $pdo->exec($sql);

    echo "修正完了: {$affectedRows}件" . PHP_EOL . PHP_EOL;

    // 確認
    $stmt = $pdo->query("SELECT model_cd, model_name, image_list
                         FROM mst_model
                         WHERE model_cd = 'YOSHIMUNE'
                           AND del_flg = 0");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        echo "修正後:" . PHP_EOL;
        echo "  機種コード: {$result['model_cd']}" . PHP_EOL;
        echo "  機種名: {$result['model_name']}" . PHP_EOL;
        echo "  画像パス: {$result['image_list']}" . PHP_EOL;
    }

    echo PHP_EOL . "=== 完了！ ===" . PHP_EOL;

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . PHP_EOL;
}
?>
