<?php
/**
 * Machine Check API
 * マシン情報確認用API
 */

header('Content-Type: application/json; charset=utf-8');

// CORS設定
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// インクルード
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    // データベース接続
    $pdo = get_db_connection();

    // machine_noパラメータ取得（デフォルト3番）
    $machine_no = isset($_GET['machine_no']) ? (int)$_GET['machine_no'] : 3;

    // マシン情報取得
    $sql = "
        SELECT
            dm.machine_no,
            dm.machine_cd,
            dm.model_no,
            mm.model_name,
            mm.model_roman,
            dm.machine_status,
            dm.camera_flg,
            dm.release_date,
            dm.add_dt,
            dm.upd_dt
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        WHERE dm.machine_no = :machine_no
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':machine_no', $machine_no, PDO::PARAM_INT);
    $stmt->execute();
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        // マシンが見つからない場合、全マシン一覧を取得
        $sql_all = "
            SELECT
                dm.machine_no,
                dm.machine_cd,
                dm.model_no,
                mm.model_name,
                dm.machine_status,
                dm.camera_flg
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            ORDER BY dm.machine_no
            LIMIT 10
        ";
        $stmt_all = $pdo->query($sql_all);
        $all_machines = $stmt_all->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => false,
            'error' => "Machine #{$machine_no} not found",
            'available_machines' => $all_machines
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // カメラ情報取得
    $sql_camera = "
        SELECT
            camera_id,
            camera_name,
            camera_url,
            status
        FROM dat_camera
        WHERE machine_no = :machine_no
    ";
    $stmt_camera = $pdo->prepare($sql_camera);
    $stmt_camera->bindValue(':machine_no', $machine_no, PDO::PARAM_INT);
    $stmt_camera->execute();
    $cameras = $stmt_camera->fetchAll(PDO::FETCH_ASSOC);

    // 成功レスポンス
    echo json_encode([
        'success' => true,
        'machine' => $machine,
        'cameras' => $cameras,
        'camera_count' => count($cameras)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // エラーレスポンス
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => DEBUG_MODE ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
