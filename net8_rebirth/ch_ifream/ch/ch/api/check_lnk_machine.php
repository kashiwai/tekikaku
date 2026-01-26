<?php
/**
 * デバッグ用: lnk_machineテーブル確認API
 */

require_once('../../_etc/require_files.php');

header('Content-Type: application/json; charset=utf-8');

try {
    $DB = new NetDB();

    $machine_no = isset($_GET['machine_no']) ? intval($_GET['machine_no']) : 1;

    $sql = "SELECT
                machine_no,
                member_no,
                onetime_id,
                auth_status,
                start_dt,
                end_dt,
                add_dt,
                upd_dt
            FROM lnk_machine
            WHERE machine_no = :machine_no
            ORDER BY add_dt DESC
            LIMIT 5";

    $stmt = $DB->prepare($sql);
    $stmt->execute(['machine_no' => $machine_no]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'machine_no' => $machine_no,
        'count' => count($rows),
        'records' => $rows
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
