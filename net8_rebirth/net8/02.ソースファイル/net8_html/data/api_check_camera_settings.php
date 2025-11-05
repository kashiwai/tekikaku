<?php
/**
 * Check Camera Settings for Machine 3
 * マシン3のカメラ設定を確認
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // マシン3の設定確認
    $machineSql = "SELECT machine_no, machine_cd, model_no, camera_no, signaling_id, machine_status FROM dat_machine WHERE machine_no = 3";
    $machineStmt = $pdo->query($machineSql);
    $machine = $machineStmt->fetch(PDO::FETCH_ASSOC);

    // カメラマスタ確認
    $cameraSql = "SELECT * FROM mst_camera";
    $cameraStmt = $pdo->query($cameraSql);
    $cameras = $cameraStmt->fetchAll(PDO::FETCH_ASSOC);

    // dat_cameraテーブル確認
    $datCameraSql = "SELECT * FROM dat_camera WHERE machine_no = 3";
    $datCameraStmt = $pdo->query($datCameraSql);
    $datCameras = $datCameraStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'machine_3' => $machine,
        'mst_camera' => $cameras,
        'mst_camera_count' => count($cameras),
        'dat_camera' => $datCameras,
        'dat_camera_count' => count($datCameras),
        'diagnosis' => [
            'camera_no_set' => !empty($machine['camera_no']),
            'camera_exists_in_mst' => !empty($cameras),
            'camera_registered_in_dat' => !empty($datCameras)
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
