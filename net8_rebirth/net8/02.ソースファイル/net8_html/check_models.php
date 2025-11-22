<?php
/**
 * 利用可能なモデル確認スクリプト
 */

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 利用可能なモデルを取得
    $stmt = $pdo->query("
        SELECT model_no, model_cd, model_name, category, del_flg
        FROM mst_model
        WHERE del_flg = 0
        LIMIT 20
    ");

    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'count' => count($models),
        'models' => $models
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'FAILED',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
