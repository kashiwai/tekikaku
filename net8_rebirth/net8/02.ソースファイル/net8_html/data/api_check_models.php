<?php
/**
 * Model Check API
 * 機種マスタ確認用API
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // キーワード検索（オプション）
    $keyword = isset($_GET['keyword']) ? $_GET['keyword'] : '';

    $sql = "
        SELECT
            model_no,
            model_name,
            model_roman,
            maker_no,
            image_list
        FROM mst_model
    ";

    if ($keyword) {
        $sql .= " WHERE model_name LIKE :keyword OR model_roman LIKE :keyword ";
    }

    $sql .= " ORDER BY model_no LIMIT 50";

    $stmt = $pdo->prepare($sql);

    if ($keyword) {
        $stmt->bindValue(':keyword', "%{$keyword}%", PDO::PARAM_STR);
    }

    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 全機種数
    $totalSql = "SELECT COUNT(*) as total FROM mst_model";
    $totalStmt = $pdo->query($totalSql);
    $totalCount = $totalStmt->fetch(PDO::FETCH_ASSOC)['total'];

    echo json_encode([
        'success' => true,
        'models' => $models,
        'count' => count($models),
        'total' => $totalCount,
        'keyword' => $keyword
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
