<?php
/**
 * Check Machine 3 Detail Configuration
 * マシン3の詳細設定を確認（カメラ情報含む）
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // マシン3の詳細情報取得（play_v2/index.phpと同じクエリ）
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
        WHERE dm.machine_no = 3
    ";

    $stmt = $pdo->query($sql);
    $machine3 = $stmt->fetch(PDO::FETCH_ASSOC);

    // 全カメラのリスト（参考用）
    $camerasSql = "SELECT camera_no, camera_name, camera_mac FROM mst_camera WHERE del_flg = 0 LIMIT 10";
    $camerasStmt = $pdo->query($camerasSql);
    $cameras = $camerasStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'machine_3' => $machine3,
        'available_cameras' => $cameras,
        'diagnosis' => [
            'camera_no_set' => !empty($machine3['camera_no']),
            'camera_found' => !empty($machine3['camera_name']),
            'signaling_id_set' => !empty($machine3['signaling_id']),
            'model_set' => !empty($machine3['model_no']),
            'issue' => empty($machine3['camera_name']) ?
                'Camera not registered or camera_no not set in dat_machine' : null,
            'recommendation' => empty($machine3['camera_no']) ?
                'Need to set camera_no in dat_machine table for machine_no=3' : null
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
