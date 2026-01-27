<?php
/**
 * Update Machine 2 Camera Configuration
 * マシン2のカメラ設定を修正
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 現在の設定を確認
    $checkSql = "SELECT machine_no, camera_no FROM dat_machine WHERE machine_no = 2";
    $checkStmt = $pdo->query($checkSql);
    $before = $checkStmt->fetch(PDO::FETCH_ASSOC);

    // camera_noを2に更新
    $updateSql = "UPDATE dat_machine SET camera_no = 2 WHERE machine_no = 2";
    $updateStmt = $pdo->prepare($updateSql);
    $result = $updateStmt->execute();

    // 更新後の設定を確認
    $afterSql = "
        SELECT
            dm.machine_no, dm.camera_no,
            mc.camera_name, mc.camera_mac
        FROM dat_machine dm
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        WHERE dm.machine_no = 2
    ";
    $afterStmt = $pdo->query($afterSql);
    $after = $afterStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Machine 2 camera_no updated successfully',
        'before' => $before,
        'after' => $after,
        'rows_affected' => $updateStmt->rowCount()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
