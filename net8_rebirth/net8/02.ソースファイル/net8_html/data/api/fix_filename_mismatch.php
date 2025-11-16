<?php
/**
 * ファイル名不一致修正スクリプト
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

    echo "=== ファイル名不一致修正 ===" . PHP_EOL . PHP_EOL;

    // 修正マッピング
    $fixes = [
        ['code' => 'ZENIGATA01', 'old' => 'zenigata01.jpg', 'new' => 'zenigata.jpg'],
        ['code' => 'MILLIONGOD01', 'old' => 'milliongod01.jpg', 'new' => 'milliongod_gaisen.jpg'],
        ['code' => 'JAGGLERK', 'old' => 'jagglerk.jpg', 'new' => 'jagger01.jpg'],
    ];

    foreach ($fixes as $fix) {
        echo "修正中: {$fix['code']}" . PHP_EOL;
        echo "  {$fix['old']} → {$fix['new']}" . PHP_EOL;

        $sql = "UPDATE mst_model
                SET image_list = :new_name
                WHERE model_cd = :code
                  AND del_flg = 0";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'new_name' => $fix['new'],
            'code' => $fix['code']
        ]);

        echo "  完了！" . PHP_EOL . PHP_EOL;
    }

    // 修正後の状態確認
    echo "【修正後の全機種】" . PHP_EOL;
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

    echo PHP_EOL . "=== 修正完了！ ===" . PHP_EOL;

} catch (Exception $e) {
    echo "エラー: " . $e->getMessage() . PHP_EOL;
}
?>
