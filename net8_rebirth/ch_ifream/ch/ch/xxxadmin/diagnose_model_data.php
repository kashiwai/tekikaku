<?php
/**
 * 機種データ (mst_model) の診断
 *
 * layout_data, prizeball_data の状態を確認
 */
require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>機種データ (mst_model) 診断</h1>";
    echo "<pre>";

    // 1. 全機種のデータ状態
    echo "=== mst_model の状態 ===\n";
    $sql = "SELECT model_no, model_name, category, layout_data, prizeball_data FROM mst_model WHERE del_flg = 0 ORDER BY model_no";
    $models = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "登録機種数: " . count($models) . "\n\n";

    $missing_layout = [];
    $missing_prizeball = [];

    foreach ($models as $m) {
        $layout = json_decode($m['layout_data'], true);
        $prizeball = json_decode($m['prizeball_data'], true);

        $layout_status = ($layout !== null && !empty($m['layout_data'])) ? '✅' : '❌ NULL/空';
        $prizeball_status = ($prizeball !== null && !empty($m['prizeball_data'])) ? '✅' : '❌ NULL/空';

        echo "model_no={$m['model_no']} | {$m['model_name']}\n";
        echo "  layout_data: {$layout_status}";
        if ($layout) {
            echo " (video_mode=" . ($layout['video_mode'] ?? 'なし') . ", drum=" . ($layout['drum'] ?? 'なし') . ")";
        }
        echo "\n";
        echo "  prizeball_data: {$prizeball_status}";
        if ($prizeball) {
            echo " (MAX=" . ($prizeball['MAX'] ?? 'なし') . ")";
        }
        echo "\n\n";

        if ($layout === null || empty($m['layout_data'])) {
            $missing_layout[] = $m;
        }
        if ($prizeball === null || empty($m['prizeball_data'])) {
            $missing_prizeball[] = $m;
        }
    }

    // 2. 問題のある機種
    echo "=== 問題のある機種 ===\n";
    if (count($missing_layout) > 0) {
        echo "\n❌ layout_data が NULL/空の機種: " . count($missing_layout) . "件\n";
        foreach ($missing_layout as $m) {
            echo "  model_no={$m['model_no']} - {$m['model_name']}\n";
        }
    } else {
        echo "✅ すべての機種に layout_data が設定されています\n";
    }

    if (count($missing_prizeball) > 0) {
        echo "\n❌ prizeball_data が NULL/空の機種: " . count($missing_prizeball) . "件\n";
        foreach ($missing_prizeball as $m) {
            echo "  model_no={$m['model_no']} - {$m['model_name']}\n";
        }
    } else {
        echo "✅ すべての機種に prizeball_data が設定されています\n";
    }

    // 3. 接続済みマシンの機種データ確認
    echo "\n=== 接続済みマシン (camera_no設定済み) の機種データ確認 ===\n";
    $sql = "SELECT dm.machine_no, dm.camera_no, dm.model_no, mm.model_name, mm.layout_data, mm.prizeball_data
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.del_flg = 0 AND dm.camera_no IS NOT NULL AND dm.camera_no != ''
            ORDER BY dm.machine_no";
    $connected = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "接続済みマシン数: " . count($connected) . "\n\n";
    foreach ($connected as $c) {
        $layout = json_decode($c['layout_data'], true);
        $prizeball = json_decode($c['prizeball_data'], true);

        $status = ($layout !== null && $prizeball !== null) ? '✅' : '❌';
        echo "{$status} machine_no={$c['machine_no']} | model_no={$c['model_no']} | {$c['model_name']}\n";
        if ($layout === null) {
            echo "   ❌ layout_data が NULL\n";
        }
        if ($prizeball === null) {
            echo "   ❌ prizeball_data が NULL\n";
        }
    }

    echo "\n</pre>";
    echo "<p><a href='machine_control_v2.php'>マシン管理画面へ</a></p>";

} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage());
}
?>
