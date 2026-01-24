<?php
/**
 * Check Machine 2 Detail Configuration
 * マシン2（銭形）の詳細設定を確認（カメラ情報含む）
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // マシン2の詳細情報取得（play_v2/index.phpと同じクエリ）
    $sql = "
        SELECT
            dm.machine_no, dm.machine_cd, dm.model_no, dm.camera_no, dm.signaling_id, dm.machine_status,
            mm.category, mm.model_name, mm.model_roman, mm.prizeball_data, mm.layout_data,
            mc.camera_name, mc.camera_mac,
            cp.credit as convcredit, cp.point as convplaypoint
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        LEFT JOIN mst_convertPoint cp ON dm.convert_no = cp.convert_no
        WHERE dm.machine_no = 2
    ";

    $stmt = $pdo->query($sql);
    $machine2 = $stmt->fetch(PDO::FETCH_ASSOC);

    // MACアドレスでカメラを検索
    $macAddress = 'E0-51-D8-16-13-3D';
    $cameraSql = "SELECT camera_no, camera_name, camera_mac FROM mst_camera WHERE camera_mac = :mac LIMIT 1";
    $cameraStmt = $pdo->prepare($cameraSql);
    $cameraStmt->execute(['mac' => $macAddress]);
    $cameraByMac = $cameraStmt->fetch(PDO::FETCH_ASSOC);

    // 全カメラのリスト（参考用）
    $camerasSql = "SELECT camera_no, camera_name, camera_mac FROM mst_camera WHERE del_flg = 0 LIMIT 10";
    $camerasStmt = $pdo->query($camerasSql);
    $cameras = $camerasStmt->fetchAll(PDO::FETCH_ASSOC);

    // 診断結果
    $diagnosis = [
        'machine_no_2_exists' => !empty($machine2),
        'camera_no_set' => !empty($machine2['camera_no']),
        'camera_found_in_join' => !empty($machine2['camera_name']),
        'camera_found_by_mac' => !empty($cameraByMac),
        'signaling_id_set' => !empty($machine2['signaling_id']),
        'model_set' => !empty($machine2['model_no']),
        'target_mac_address' => $macAddress,
        'issues' => [],
        'recommendations' => []
    ];

    // 問題点と推奨事項の診断
    if (empty($machine2)) {
        $diagnosis['issues'][] = 'Machine 2 not found in dat_machine table';
        $diagnosis['recommendations'][] = 'Need to register machine_no=2 in dat_machine table';
    } else {
        if (empty($machine2['camera_no'])) {
            $diagnosis['issues'][] = 'camera_no not set in dat_machine for machine_no=2';
            if ($cameraByMac) {
                $diagnosis['recommendations'][] = "Set camera_no={$cameraByMac['camera_no']} (found by MAC: {$macAddress})";
            } else {
                $diagnosis['recommendations'][] = "Register camera with MAC: {$macAddress} in mst_camera table first";
            }
        }
        if (empty($machine2['signaling_id'])) {
            $diagnosis['issues'][] = 'signaling_id not set in dat_machine for machine_no=2';
            $diagnosis['recommendations'][] = 'Set signaling_id for PeerJS connection';
        }
        if (empty($machine2['model_no'])) {
            $diagnosis['issues'][] = 'model_no not set in dat_machine for machine_no=2';
            $diagnosis['recommendations'][] = 'Set model_no to link to 銭形 model';
        }
    }

    echo json_encode([
        'success' => true,
        'machine_2' => $machine2,
        'camera_by_mac' => $cameraByMac,
        'available_cameras' => $cameras,
        'diagnosis' => $diagnosis
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
