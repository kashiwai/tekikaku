<?php
/**
 * 台の登録状況確認API
 * 急ぎで2台稼働させるための確認用
 */

header('Content-Type: application/json; charset=utf-8');

require_once('_etc/setting_base.php');
require_once('_etc/setting.php');

try {
    // PDO接続
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    // 台情報を取得
    $sql = "SELECT
        dm.machine_no,
        dm.machine_cd,
        dm.model_no,
        dm.camera_no,
        dm.signaling_id,
        dm.machine_status,
        dm.release_date,
        dm.end_date,
        mm.model_name,
        mm.model_cd,
        mc.camera_name,
        mc.camera_mac
    FROM dat_machine dm
    LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
    LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
    WHERE dm.del_flg = 0
    ORDER BY dm.machine_no
    LIMIT 5";

    $stmt = $pdo->query($sql);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カメラ一覧も取得
    $sql2 = "SELECT camera_no, camera_name, camera_mac, machine_no
             FROM mst_camera
             WHERE del_flg = 0
             ORDER BY camera_no
             LIMIT 5";
    $stmt2 = $pdo->query($sql2);
    $cameras = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'machines' => $machines,
        'cameras' => $cameras,
        'db_host' => DB_HOST,
        'db_name' => DB_NAME
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'db_host' => DB_HOST ?? 'not defined',
        'db_name' => DB_NAME ?? 'not defined'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
