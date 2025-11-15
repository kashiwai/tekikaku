<?php
/**
 * 画像パス一括修正API - img/model/ プレフィックスを削除
 */
header('Content-Type: application/json; charset=UTF-8');

require_once(__DIR__ . '/../../_etc/require_files.php');

try {
    $db = new SmartDB(DB_DSN);

    // 現在の状態確認
    $checkSql = "SELECT model_cd, model_name, image_list
                 FROM mst_model
                 WHERE del_flg = 0
                 AND image_list IS NOT NULL
                 AND image_list != ''
                 ORDER BY model_no";

    $result = $db->query($checkSql);
    $models = [];
    while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $models[] = $row;
    }

    // 修正実行
    $fixSql = "UPDATE mst_model
              SET image_list = REPLACE(image_list, 'img/model/', '')
              WHERE del_flg = 0
              AND image_list IS NOT NULL
              AND image_list != ''
              AND image_list LIKE 'img/model/%'";

    $affectedRows = $db->exec($fixSql);

    // 修正後の状態確認
    $result2 = $db->query($checkSql);
    $modelsAfter = [];
    while ($row = $result2->fetchRow(MDB2_FETCHMODE_ASSOC)) {
        $modelsAfter[] = $row;
    }

    echo json_encode([
        'success' => true,
        'affected_rows' => $affectedRows,
        'before' => $models,
        'after' => $modelsAfter
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
