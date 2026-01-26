<?php
/**
 * List All Tables in Database
 * GCP Cloud SQLのテーブル一覧を取得
 */

header('Content-Type: application/json; charset=utf-8');
require_once(__DIR__ . '/../_etc/require_files.php');

try {
    $pdo = get_db_connection();

    // 全テーブルのリスト取得
    $tablesSql = "SHOW TABLES";
    $tablesStmt = $pdo->query($tablesSql);
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

    // カメラ関連テーブルのみ詳細情報を取得
    $cameraRelatedTables = [];
    foreach ($tables as $table) {
        if (stripos($table, 'camera') !== false || stripos($table, 'machine') !== false) {
            $descSql = "DESCRIBE `$table`";
            $descStmt = $pdo->query($descSql);
            $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);

            $cameraRelatedTables[$table] = [
                'columns' => $columns,
                'row_count' => $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn()
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'database' => $pdo->query("SELECT DATABASE()")->fetchColumn(),
        'total_tables' => count($tables),
        'all_tables' => $tables,
        'camera_related_tables' => $cameraRelatedTables
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
