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

    // 特定のカメラ情報取得（camera_noが設定されている場合）
    $specificCamera = null;
    if (!empty($machine['camera_no'])) {
        $specificCameraSql = "SELECT * FROM mst_camera WHERE camera_no = :camera_no";
        $specificCameraStmt = $pdo->prepare($specificCameraSql);
        $specificCameraStmt->execute(['camera_no' => $machine['camera_no']]);
        $specificCamera = $specificCameraStmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'success' => true,
        'machine_3' => $machine,
        'mst_camera_all' => $cameras,
        'mst_camera_count' => count($cameras),
        'machine_3_camera' => $specificCamera,
        'diagnosis' => [
            'camera_no_set' => !empty($machine['camera_no']),
            'camera_exists_in_mst' => !empty($cameras),
            'machine_camera_found' => !empty($specificCamera),
            'signaling_id_set' => !empty($machine['signaling_id']),
            'issue' => empty($specificCamera) ? 'Camera not found in mst_camera or camera_no not set' : null
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
