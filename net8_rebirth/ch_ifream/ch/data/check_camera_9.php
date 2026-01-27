<?php
/**
 * Check camera 9 current peerID from database
 */

require_once('../_etc/require_files.php');

header('Content-Type: application/json');

try {
    $pdo = get_db_connection();

    // camera 9の現在の情報を取得
    $stmt = $pdo->prepare("
        SELECT
            camera_no,
            camera_name,
            camera_mac,
            created_at,
            updated_at
        FROM mst_camera
        WHERE camera_no = 9
    ");
    $stmt->execute();
    $camera = $stmt->fetch(PDO::FETCH_ASSOC);

    // machine 9の情報も取得
    $machineStmt = $pdo->prepare("
        SELECT
            dm.machine_no,
            dm.camera_no,
            dm.signaling_id,
            mc.camera_name as db_camera_name
        FROM dat_machine dm
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        WHERE dm.machine_no = 9
    ");
    $machineStmt->execute();
    $machine = $machineStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'camera_table' => $camera,
        'machine_9_info' => $machine,
        'db_host' => DB_HOST,
        'db_name' => DB_NAME,
        'expected_camera_id' => 'camera_9_1769472099',
        'match' => ($camera['camera_name'] === 'camera_9_1769472099')
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
