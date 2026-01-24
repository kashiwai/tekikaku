<?php
/**
 * Direct Update Machine 3 to Million God
 * マシン3を直接ミリオンゴッドに変更（一時的なスクリプト）
 */

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 現在の状態確認
    $beforeSql = "SELECT machine_no, machine_cd, model_no FROM dat_machine WHERE machine_no = 3";
    $beforeStmt = $pdo->query($beforeSql);
    $before = $beforeStmt->fetch(PDO::FETCH_ASSOC);

    // ミリオンゴッドのmodel_no確認
    $modelSql = "SELECT model_no FROM mst_model WHERE model_name LIKE '%ミリオンゴッド%'";
    $modelStmt = $pdo->query($modelSql);
    $model = $modelStmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        die(json_encode(['error' => 'ミリオンゴッドがmst_modelに存在しません']));
    }

    // マシン3を更新
    $updateSql = "UPDATE dat_machine SET model_no = :model_no, machine_cd = 'MILLIONGOD01', upd_dt = NOW() WHERE machine_no = 3";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->bindValue(':model_no', $model['model_no'], PDO::PARAM_INT);
    $updateStmt->execute();

    // 更新後確認
    $afterStmt = $pdo->query($beforeSql);
    $after = $afterStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'before' => $before,
        'after' => $after,
        'message' => 'マシン3をミリオンゴッドに変更しました'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
