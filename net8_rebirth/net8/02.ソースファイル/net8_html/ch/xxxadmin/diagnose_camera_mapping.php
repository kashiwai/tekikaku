<?php
/**
 * カメラとマシンのマッピング診断
 *
 * mst_camera と dat_machine の関連を確認
 */
require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>カメラ・マシン マッピング診断</h1>";
    echo "<pre>";

    // 1. mst_camera の状態
    echo "=== mst_camera の状態 ===\n";
    $sql = "SELECT camera_no, camera_mac, camera_name FROM mst_camera WHERE del_flg = 0 ORDER BY camera_no";
    $cameras = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "登録カメラ数: " . count($cameras) . "\n\n";
    foreach ($cameras as $c) {
        echo "  camera_no={$c['camera_no']} | MAC={$c['camera_mac']} | name={$c['camera_name']}\n";
    }

    // 2. dat_machine の状態
    echo "\n=== dat_machine の状態 ===\n";
    $sql = "SELECT dm.machine_no, dm.name, dm.camera_no, dm.model_no, mm.model_name
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.del_flg = 0
            ORDER BY dm.machine_no";
    $machines = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "登録マシン数: " . count($machines) . "\n\n";
    foreach ($machines as $m) {
        $model_display = $m['model_name'] ?? '未設定(model_no=' . $m['model_no'] . ')';
        echo "  machine_no={$m['machine_no']} | camera_no={$m['camera_no']} | model={$model_display}\n";
    }

    // 3. マッピング問題の検出
    echo "\n=== マッピング問題の検出 ===\n";

    // dat_machine の camera_no が mst_camera に存在しないケース
    $sql = "SELECT dm.machine_no, dm.camera_no, dm.name
            FROM dat_machine dm
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no AND mc.del_flg = 0
            WHERE dm.del_flg = 0 AND mc.camera_no IS NULL";
    $missing_cameras = $db->getAll($sql, PDO::FETCH_ASSOC);

    if (count($missing_cameras) > 0) {
        echo "\n❌ mst_camera に対応がないマシン: " . count($missing_cameras) . "台\n";
        foreach ($missing_cameras as $m) {
            echo "  machine_no={$m['machine_no']} expects camera_no={$m['camera_no']} → mst_camera に未登録\n";
        }
    } else {
        echo "\n✅ すべてのマシンのcamera_noがmst_cameraに存在します\n";
    }

    // mst_camera の camera_no が dat_machine に紐づいていないケース
    $sql = "SELECT mc.camera_no, mc.camera_mac, mc.camera_name
            FROM mst_camera mc
            LEFT JOIN dat_machine dm ON mc.camera_no = dm.camera_no AND dm.del_flg = 0
            WHERE mc.del_flg = 0 AND dm.machine_no IS NULL";
    $orphan_cameras = $db->getAll($sql, PDO::FETCH_ASSOC);

    if (count($orphan_cameras) > 0) {
        echo "\n⚠️ dat_machine に紐づいていないカメラ: " . count($orphan_cameras) . "台\n";
        foreach ($orphan_cameras as $c) {
            echo "  camera_no={$c['camera_no']} | MAC={$c['camera_mac']} | name={$c['camera_name']}\n";
        }
    } else {
        echo "\n✅ すべてのカメラがマシンに紐づいています\n";
    }

    // 4. model_no = 0 のマシン
    echo "\n=== model_no = 0 (機種未設定) のマシン ===\n";
    $sql = "SELECT machine_no, name, camera_no FROM dat_machine WHERE model_no = 0 AND del_flg = 0";
    $unset_machines = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "機種未設定マシン: " . count($unset_machines) . "台\n";

    echo "\n</pre>";
    echo "<p><a href='machine_control_v2.php'>マシン管理画面へ</a></p>";

} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage());
}
?>
