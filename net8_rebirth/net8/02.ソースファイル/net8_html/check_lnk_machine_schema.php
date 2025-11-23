<?php
/**
 * lnk_machineテーブル構造確認
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "lnk_machine テーブル構造確認\n";
    echo "========================================\n\n";

    $cols = $pdo->query("SHOW COLUMNS FROM lnk_machine")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cols as $col) {
        echo sprintf("%-30s: %s %s %s\n",
            $col['Field'],
            $col['Type'],
            $col['Null'],
            $col['Key']
        );
    }

    echo "\n========================================\n";
    echo "サンプルデータ（最初の5件）\n";
    echo "========================================\n\n";

    $stmt = $pdo->query("SELECT * FROM lnk_machine LIMIT 5");
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($samples as $i => $row) {
        echo "【レコード " . ($i + 1) . "】\n";
        foreach ($row as $key => $value) {
            echo sprintf("  %-25s: %s\n", $key, $value ?? 'NULL');
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
