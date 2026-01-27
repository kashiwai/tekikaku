<?php
/**
 * dat_machine テーブル構造確認
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_etc/require_files.php';

try {
    $pdo = get_db_connection();

    // テーブル構造確認
    $sql = "DESCRIBE dat_machine";
    $stmt = $pdo->query($sql);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 既存データ確認
    $sql = "SELECT COUNT(*) as count FROM dat_machine";
    $stmt = $pdo->query($sql);
    $count = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1件取得
    $sql = "SELECT * FROM dat_machine LIMIT 1";
    $stmt = $pdo->query($sql);
    $sample = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'table_structure' => $columns,
        'record_count' => $count['count'],
        'sample_record' => $sample
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
