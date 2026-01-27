<?php
/**
 * image_list カラムサイズ拡張スクリプト
 */
header('Content-Type: application/json; charset=UTF-8');

// DB接続
$db_host = $_SERVER['DB_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '136.116.70.86';
$db_name = $_SERVER['DB_NAME'] ?? $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'net8_dev';
$db_user = $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8tech001';
$db_password = $_SERVER['DB_PASSWORD'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: 'CaD?7&Bi+_:`QKb*';

$result = [];

try {
    $pdo = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // 現在のカラム定義を確認
    $stmt = $pdo->query("SHOW COLUMNS FROM mst_model WHERE Field = 'image_list'");
    $current_column = $stmt->fetch(PDO::FETCH_ASSOC);

    $result['current_schema'] = $current_column;

    // 実行モードかプレビューか
    if (isset($_GET['execute']) && $_GET['execute'] == '1') {
        // カラムサイズを VARCHAR(255) に拡張
        $pdo->exec("
            ALTER TABLE mst_model
            MODIFY COLUMN image_list VARCHAR(255) DEFAULT NULL
        ");

        // 変更後のカラム定義を確認
        $stmt = $pdo->query("SHOW COLUMNS FROM mst_model WHERE Field = 'image_list'");
        $updated_column = $stmt->fetch(PDO::FETCH_ASSOC);

        $result['success'] = true;
        $result['action'] = 'UPDATED';
        $result['updated_schema'] = $updated_column;
        $result['message'] = '✅ image_list カラムを VARCHAR(255) に拡張しました';
    } else {
        // プレビューモード
        $result['success'] = true;
        $result['action'] = 'PREVIEW';
        $result['proposed_change'] = [
            'from' => $current_column['Type'],
            'to' => 'varchar(255)'
        ];
        $result['message'] = '⚠️ プレビューモード: 実際に変更するには ?execute=1 を追加してください';
        $result['execute_url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . '?execute=1';
    }

} catch (Exception $e) {
    $result = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
