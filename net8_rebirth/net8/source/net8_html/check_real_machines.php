<?php
/**
 * 実機器一覧確認（SDK対応可能な機器）
 */
header('Content-Type: text/plain; charset=utf-8');
require_once(__DIR__ . '/_etc/require_files.php');

try {
    $pdo = get_db_connection();

    echo "========================================\n";
    echo "実機器一覧（SDK対応可能）\n";
    echo "========================================\n\n";

    // lnk_machineとmst_modelを結合して実機を取得
    $stmt = $pdo->query("
        SELECT
            lm.machine_no,
            lm.model_cd,
            mm.model_name,
            mm.category,
            lm.owner_no,
            lm.corner_no,
            lm.convertPoint_no,
            lm.machine_state
        FROM lnk_machine lm
        LEFT JOIN mst_model mm ON lm.model_cd = mm.model_cd
        WHERE lm.machine_state = 1
        ORDER BY lm.machine_no ASC
        LIMIT 10
    ");

    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($machines) > 0) {
        echo "✅ 稼働中の実機: " . count($machines) . "台\n\n";
        foreach ($machines as $i => $machine) {
            echo sprintf("【機器%d】\n", $i + 1);
            echo sprintf("  machine_no    : %s\n", $machine['machine_no']);
            echo sprintf("  model_cd      : %s\n", $machine['model_cd']);
            echo sprintf("  model_name    : %s\n", $machine['model_name']);
            echo sprintf("  category      : %s\n", $machine['category']);
            echo sprintf("  owner_no      : %s\n", $machine['owner_no']);
            echo sprintf("  corner_no     : %s\n", $machine['corner_no']);
            echo sprintf("  machine_state : %s\n", $machine['machine_state']);
            echo "\n";
        }

        echo "--- SDK game_start用コマンド例 ---\n\n";
        $first = $machines[0];
        echo "curl -X POST \"https://mgg-webservice-production.up.railway.app/api/v1/game_start.php\" \\\n";
        echo "  -H \"Authorization: Bearer pk_demo_12345\" \\\n";
        echo "  -H \"Content-Type: application/json\" \\\n";
        echo "  -d '{\"modelId\": \"" . $first['model_cd'] . "\", \"userId\": \"real_test_001\"}'\n";
    } else {
        echo "⚠️  稼働中の実機が見つかりません\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
