<?php
/**
 * 画像パス自動修正API
 * image_listフィールドから重複したパスプレフィックスを削除
 *
 * 使用方法:
 * curl https://mgg-webservice-production.up.railway.app/api/fix_image_paths_auto.php?key=net8_fix_2025
 */

header('Content-Type: application/json; charset=UTF-8');

// 簡易認証
if (!isset($_GET['key']) || $_GET['key'] !== 'net8_fix_2025') {
    http_response_code(403);
    echo json_encode(['error' => '認証キーが必要です']);
    exit;
}

require_once(__DIR__ . '/../../_etc/require_files.php');

try {
    $db = new SmartDB(DB_DSN);

    // 現在の状態を確認
    $sql = "SELECT model_no, model_cd, model_name, image_list
            FROM mst_model
            WHERE del_flg = 0
            AND image_list IS NOT NULL
            AND image_list != ''
            AND image_list LIKE 'img/model/%'
            ORDER BY model_no";

    $result = $db->query($sql);
    $models = [];
    while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $models[] = $row;
    }

    if (empty($models)) {
        echo json_encode([
            'success' => true,
            'message' => 'すべての画像パスは正常です。修正不要です。',
            'fixed_count' => 0
        ]);
        exit;
    }

    $results = [];
    $fixedCount = 0;

    foreach ($models as $model) {
        // img/model/ プレフィックスを削除してファイル名のみにする
        $oldPath = $model['image_list'];
        $newPath = str_replace('img/model/', '', $oldPath);

        $updateSql = "UPDATE mst_model
                     SET image_list = " . $db->quote($newPath, 'text') . "
                     WHERE model_no = " . $db->quote($model['model_no'], 'integer');

        $db->exec($updateSql);
        $fixedCount++;

        $results[] = [
            'model_cd' => $model['model_cd'],
            'model_name' => $model['model_name'],
            'old_path' => $oldPath,
            'new_path' => $newPath
        ];
    }

    echo json_encode([
        'success' => true,
        'message' => '画像パス修正完了',
        'fixed_count' => $fixedCount,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
