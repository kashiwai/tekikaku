<?php
/**
 * データベース内の画像情報確認スクリプト
 */
header('Content-Type: application/json; charset=UTF-8');

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

$result = [];

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 全機種の画像情報を取得
    $stmt = $pdo->query("
        SELECT
            model_no,
            model_cd,
            model_name,
            image_list,
            CASE
                WHEN image_list IS NULL THEN 'NULL'
                WHEN image_list = '' THEN 'EMPTY'
                WHEN image_list LIKE 'https://storage.googleapis.com/%' THEN 'GCS_URL'
                WHEN image_list LIKE 'img/model/%' THEN 'OLD_PATH'
                ELSE 'FILENAME_ONLY'
            END as image_type
        FROM mst_model
        WHERE del_flg = 0
        ORDER BY model_no
    ");

    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 統計情報
    $stats = [
        'total' => count($models),
        'has_image' => 0,
        'no_image' => 0,
        'gcs_url' => 0,
        'old_path' => 0,
        'filename_only' => 0
    ];

    foreach ($models as $model) {
        if ($model['image_list']) {
            $stats['has_image']++;

            switch ($model['image_type']) {
                case 'GCS_URL':
                    $stats['gcs_url']++;
                    break;
                case 'OLD_PATH':
                    $stats['old_path']++;
                    break;
                case 'FILENAME_ONLY':
                    $stats['filename_only']++;
                    break;
            }
        } else {
            $stats['no_image']++;
        }
    }

    $result = [
        'success' => true,
        'stats' => $stats,
        'models' => $models,
        'message' => count($models) > 0 ? '✅ データベース内の画像情報は保持されています' : '❌ 画像情報がありません'
    ];

} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
