<?php
/**
 * Quick Machine Registration - Simplified version
 */
require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: application/json');

try {
    $pdo = get_db_connection();

    // Get HOKUTO4GO model_no
    $modelStmt = $pdo->query("SELECT model_no, model_cd, model_name FROM mst_model WHERE model_cd = 'HOKUTO4GO' AND del_flg = 0");
    $model = $modelStmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        echo json_encode(['error' => 'Model HOKUTO4GO not found']);
        exit;
    }

    // Insert machine
    $insertSql = "INSERT INTO dat_machine (model_no, signaling_id, machine_status, end_date, del_flg)
                  VALUES (:model_no, :signaling_id, 0, DATE_ADD(CURDATE(), INTERVAL 1 YEAR), 0)";

    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        'model_no' => $model['model_no'],
        'signaling_id' => 'sig_hokuto_quick_' . time()
    ]);

    $machineNo = $pdo->lastInsertId();

    // Verify
    $verifyStmt = $pdo->prepare("SELECT * FROM dat_machine WHERE machine_no = :machine_no");
    $verifyStmt->execute(['machine_no' => $machineNo]);
    $machine = $verifyStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'message' => 'Machine registered successfully',
        'machine_no' => $machineNo,
        'model' => $model,
        'machine' => $machine
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_PRETTY_PRINT);
}
?>
