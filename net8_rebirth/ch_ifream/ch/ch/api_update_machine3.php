<?php
/**
 * Update Machine 3 to Million God API
 * マシン3をミリオンゴッドに変更するAPI
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // マシン3の現在の状態確認
    $checkSql = "
        SELECT
            dm.machine_no,
            dm.machine_cd,
            dm.model_no,
            mm.model_name,
            dm.machine_status
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        WHERE dm.machine_no = 3
    ";
    $checkStmt = $pdo->query($checkSql);
    $before = $checkStmt->fetch(PDO::FETCH_ASSOC);

    if (!$before) {
        echo json_encode([
            'success' => false,
            'error' => 'マシン3が見つかりません'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ミリオンゴッドのmodel_no確認
    $modelSql = "SELECT model_no, model_name FROM mst_model WHERE model_name LIKE '%ミリオンゴッド%'";
    $modelStmt = $pdo->query($modelSql);
    $milliongod = $modelStmt->fetch(PDO::FETCH_ASSOC);

    if (!$milliongod) {
        echo json_encode([
            'success' => false,
            'error' => 'ミリオンゴッドがmst_modelに登録されていません。先にapi_register_milliongod.phpを実行してください。'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // マシン3をミリオンゴッドに更新
    $updateSql = "
        UPDATE dat_machine
        SET
            model_no = :model_no,
            machine_cd = 'MILLIONGOD01',
            upd_dt = NOW()
        WHERE machine_no = 3
    ";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindValue(':model_no', $milliongod['model_no'], PDO::PARAM_INT);
    $updateStmt->execute();
    $affected = $updateStmt->rowCount();

    // 更新後の確認
    $afterStmt = $pdo->query($checkSql);
    $after = $afterStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'マシン3をミリオンゴッドに変更しました',
        'affected_rows' => $affected,
        'before' => $before,
        'after' => $after,
        'milliongod_model_no' => $milliongod['model_no']
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
