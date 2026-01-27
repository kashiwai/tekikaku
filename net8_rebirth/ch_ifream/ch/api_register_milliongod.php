<?php
/**
 * Register Million God API
 * ミリオンゴッド登録用API（テスト・本番共用）
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 既存チェック
    $checkSql = "SELECT model_no, model_name FROM mst_model WHERE model_name LIKE '%ミリオンゴッド%'";
    $checkStmt = $pdo->query($checkSql);
    $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo json_encode([
            'success' => false,
            'message' => 'ミリオンゴッドは既に登録されています',
            'existing' => $existing
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ミリオンゴッド登録
    $insertSql = "
        INSERT INTO mst_model (
            model_cd,
            model_name,
            model_roman,
            maker_no,
            category,
            image_list,
            add_dt,
            upd_dt
        ) VALUES (
            'MILLIONGOD01',
            'ミリオンゴッド～神々の凱旋～',
            'MILLION GOD KAMIGAMI NO GAISEN',
            86,
            2,
            'milliongod_gaisen.jpg',
            NOW(),
            NOW()
        )
    ";

    $result = $pdo->exec($insertSql);
    $newModelNo = $pdo->lastInsertId();

    // 登録確認
    $verifySql = "SELECT model_no, model_name, maker_no, category, image_list FROM mst_model WHERE model_no = :model_no";
    $verifyStmt = $pdo->prepare($verifySql);
    $verifyStmt->bindValue(':model_no', $newModelNo, PDO::PARAM_INT);
    $verifyStmt->execute();
    $newModel = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    // 全機種一覧
    $allSql = "SELECT model_no, model_name, maker_no, category FROM mst_model ORDER BY model_no";
    $allStmt = $pdo->query($allSql);
    $allModels = $allStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'ミリオンゴッドの登録に成功しました',
        'new_model_no' => $newModelNo,
        'new_model' => $newModel,
        'all_models' => $allModels,
        'total_models' => count($allModels)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
