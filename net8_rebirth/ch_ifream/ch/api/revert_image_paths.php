<?php
/**
 * 画像URLをファイル名のみに戻すスクリプト（GCS URLから）
 */
header('Content-Type: application/json; charset=UTF-8');

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

    $stmt = $pdo->query("SELECT model_cd, model_name, image_list FROM mst_model WHERE del_flg = 0");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updates = [];
    foreach ($models as $model) {
        if (strpos($model['image_list'], 'https://storage.googleapis.com/') === 0) {
            // GCS URLからファイル名だけを抽出
            $filename = basename($model['image_list']);
            $updates[] = [
                'model_cd' => $model['model_cd'],
                'model_name' => $model['model_name'],
                'old_url' => $model['image_list'],
                'new_path' => $filename
            ];
        }
    }

    if (isset($_GET['execute']) && $_GET['execute'] == '1') {
        $pdo->beginTransaction();

        foreach ($updates as $update) {
            $stmt = $pdo->prepare("
                UPDATE mst_model
                SET image_list = :filename,
                    upd_no = 1,
                    upd_dt = NOW()
                WHERE model_cd = :model_cd AND del_flg = 0
            ");
            $stmt->execute([
                'filename' => $update['new_path'],
                'model_cd' => $update['model_cd']
            ]);
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'action' => 'UPDATED',
            'updated_count' => count($updates),
            'updates' => $updates,
            'message' => '✅ ' . count($updates) . '件の画像URLをファイル名に戻しました'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success' => true,
            'action' => 'PREVIEW',
            'updates_pending' => count($updates),
            'updates' => $updates,
            'message' => '⚠️ プレビューモード: ?execute=1 で実行',
            'execute_url' => $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?execute=1'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_PRETTY_PRINT);
}
