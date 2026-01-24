<?php
/**
 * machine_no=1 のデータ確認
 */
require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>Machine No.1 詳細確認</h1>";
    echo "<pre>";

    // dat_machine の情報
    $sql = "SELECT * FROM dat_machine WHERE machine_no = 1";
    $machine = $db->getRow($sql, PDO::FETCH_ASSOC);
    echo "=== dat_machine ===\n";
    print_r($machine);

    // mst_model の情報
    if ($machine && $machine['model_no']) {
        $sql = "SELECT * FROM mst_model WHERE model_no = " . $machine['model_no'];
        $model = $db->getRow($sql, PDO::FETCH_ASSOC);
        echo "\n=== mst_model (model_no={$machine['model_no']}) ===\n";
        print_r($model);

        echo "\n=== layout_data 解析 ===\n";
        $layout = json_decode($model['layout_data'], true);
        if ($layout === null) {
            echo "❌ layout_data が NULL または無効なJSON\n";
            echo "生データ: " . var_export($model['layout_data'], true) . "\n";
        } else {
            echo "✅ layout_data は有効\n";
            print_r($layout);
        }

        echo "\n=== prizeball_data 解析 ===\n";
        $prizeball = json_decode($model['prizeball_data'], true);
        if ($prizeball === null) {
            echo "❌ prizeball_data が NULL または無効なJSON\n";
            echo "生データ: " . var_export($model['prizeball_data'], true) . "\n";
        } else {
            echo "✅ prizeball_data は有効\n";
            print_r($prizeball);
        }
    }

    // mst_camera の情報
    if ($machine && $machine['camera_no']) {
        $sql = "SELECT * FROM mst_camera WHERE camera_no = " . $machine['camera_no'];
        $camera = $db->getRow($sql, PDO::FETCH_ASSOC);
        echo "\n=== mst_camera (camera_no={$machine['camera_no']}) ===\n";
        print_r($camera);
    }

    echo "</pre>";

} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage());
}
?>
