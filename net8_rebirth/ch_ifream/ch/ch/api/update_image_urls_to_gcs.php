<?php
/**
 * 画像URLをGCS URLに一括更新するスクリプト
 *
 * 実行方法:
 * https://mgg-webservice-production.up.railway.app/data/api/update_image_urls_to_gcs.php?execute=1
 */
header('Content-Type: application/json; charset=UTF-8');

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

$result = [];
$gcs_base_url = 'https://storage.googleapis.com/avamodb-net8-images/models/';

// GCSにアップロード済みの画像リスト
$gcs_images = [
    'hokuto4go.jpg',
    'jagger01.jpg',
    'milliongod_gaisen.jpg',
    'milliongod_gaisen_old.jpg',
    'yoshimune.png',
    'zenigata.jpg'
];

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 現在の状態を取得
    $stmt = $pdo->query("
        SELECT model_no, model_cd, model_name, image_list
        FROM mst_model
        WHERE del_flg = 0
        ORDER BY model_no
    ");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updates = [];
    $skipped = [];

    foreach ($models as $model) {
        $current_image = $model['image_list'];

        if (empty($current_image)) {
            $skipped[] = [
                'model_cd' => $model['model_cd'],
                'model_name' => $model['model_name'],
                'reason' => 'No image set'
            ];
            continue;
        }

        // 既にGCS URLの場合はスキップ
        if (strpos($current_image, 'https://storage.googleapis.com/') === 0) {
            $skipped[] = [
                'model_cd' => $model['model_cd'],
                'model_name' => $model['model_name'],
                'current_image' => $current_image,
                'reason' => 'Already GCS URL'
            ];
            continue;
        }

        // ファイル名を抽出（img/model/を削除）
        $filename = str_replace('img/model/', '', $current_image);

        // GCSにアップロード済みか確認
        if (in_array($filename, $gcs_images)) {
            $new_url = $gcs_base_url . $filename;

            $updates[] = [
                'model_cd' => $model['model_cd'],
                'model_name' => $model['model_name'],
                'old_path' => $current_image,
                'new_url' => $new_url
            ];
        } else {
            $skipped[] = [
                'model_cd' => $model['model_cd'],
                'model_name' => $model['model_name'],
                'current_image' => $current_image,
                'reason' => 'Image not found in GCS'
            ];
        }
    }

    // 実際に更新を実行するか（セーフティ）
    if (isset($_GET['execute']) && $_GET['execute'] == '1') {
        $pdo->beginTransaction();

        $updated_count = 0;
        foreach ($updates as $update) {
            $stmt = $pdo->prepare("
                UPDATE mst_model
                SET image_list = :new_url,
                    upd_no = 1,
                    upd_dt = NOW()
                WHERE model_cd = :model_cd
                  AND del_flg = 0
            ");

            $stmt->execute([
                'new_url' => $update['new_url'],
                'model_cd' => $update['model_cd']
            ]);

            $updated_count++;
        }

        $pdo->commit();

        $result = [
            'success' => true,
            'action' => 'UPDATED',
            'updated_count' => $updated_count,
            'updates' => $updates,
            'skipped' => $skipped,
            'message' => "✅ {$updated_count}件の画像URLをGCS URLに更新しました"
        ];

    } else {
        // プレビューモード（実際には更新しない）
        $result = [
            'success' => true,
            'action' => 'PREVIEW',
            'updates_pending' => count($updates),
            'updates' => $updates,
            'skipped' => $skipped,
            'message' => '⚠️ プレビューモード: 実際に更新するには ?execute=1 を追加してください',
            'execute_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?execute=1'
        ];
    }

} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
