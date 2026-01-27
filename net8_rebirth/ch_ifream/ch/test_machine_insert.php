<?php
/**
 * dat_machine テスト挿入スクリプト
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_etc/require_files.php';

try {
    $pdo = get_db_connection();

    // テーブル構造確認
    $sql = "DESCRIBE dat_machine";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カラム名一覧
    $columnNames = array_column($columns, 'Field');

    // INSERT テスト
    $testData = [
        'model_no' => 8,
        'machine_cd' => 'TEST-001',
        'signaling_id' => 'PEER-TEST001',
        'convert_no' => 1,
        'release_date' => date('Y-m-d'),
        'end_date' => '2099-12-31',
        'machine_status' => 0,
        'del_flg' => 0
    ];

    // 実際に挿入してみる
    $sql = "INSERT INTO dat_machine (model_no, machine_cd, signaling_id, convert_no, release_date, end_date, machine_status, del_flg, add_dt, upd_dt)
            VALUES (:model_no, :machine_cd, :signaling_id, :convert_no, :release_date, :end_date, :machine_status, :del_flg, NOW(), NOW())";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($testData);

    $machineNo = $pdo->lastInsertId();

    // 挿入されたデータを確認
    $sql = "SELECT * FROM dat_machine WHERE machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    $inserted = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'table_columns' => $columnNames,
        'inserted_machine_no' => $machineNo,
        'inserted_data' => $inserted
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error_type' => 'PDOException',
        'error' => $e->getMessage(),
        'code' => $e->getCode()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error_type' => 'Exception',
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
