<?php
/**
 * Update Image API
 * 画像名更新用API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 北斗の拳のモデルを探す
    $sql_find = "
        SELECT model_no, model_name, image_list
        FROM mst_model
        WHERE model_name LIKE '%北斗%'
    ";
    $stmt = $pdo->query($sql_find);
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($models)) {
        echo json_encode([
            'success' => false,
            'message' => '北斗の拳のモデルが見つかりません'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 画像名を hokuto4go.jpg に更新
    $sql_update = "
        UPDATE mst_model
        SET image_list = 'hokuto4go.jpg',
            upd_dt = NOW()
        WHERE model_name LIKE '%北斗%'
    ";
    $affected = $pdo->exec($sql_update);

    echo json_encode([
        'success' => true,
        'message' => '北斗の拳の画像名を更新しました',
        'affected_rows' => $affected,
        'models_before' => $models,
        'new_image' => 'hokuto4go.jpg'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
