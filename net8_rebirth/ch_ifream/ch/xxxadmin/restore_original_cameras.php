<?php
/**
 * カメラ情報 完全復元スクリプト
 *
 * 論理削除された元のカメラ情報を復元し、
 * 上書きされた新しいカメラを削除します
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔄 カメラ情報 完全復元</h1>";
    echo "<hr>";

    // ========================================
    // ステップ1: 論理削除されたカメラを確認
    // ========================================
    echo "<h2>📋 ステップ1: 論理削除されたカメラ確認</h2>";
    $sql = "SELECT camera_no, camera_mac, camera_name, del_flg, del_dt
            FROM mst_camera
            WHERE del_flg = 1
            AND camera_mac NOT LIKE '00:00:00:00:%'
            AND camera_name NOT LIKE 'dummy_%'
            ORDER BY camera_no ASC";
    $deleted_cameras = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 論理削除された実カメラ: " . count($deleted_cameras) . "台</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>camera_no</th><th>camera_mac</th><th>camera_name</th><th>del_dt</th></tr>";
    foreach ($deleted_cameras as $cam) {
        echo "<tr>";
        echo "<td>{$cam['camera_no']}</td>";
        echo "<td>{$cam['camera_mac']}</td>";
        echo "<td>{$cam['camera_name']}</td>";
        echo "<td>{$cam['del_dt']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // ステップ2: 現在有効なカメラを確認
    // ========================================
    echo "<h2>📋 ステップ2: 現在有効なカメラ確認</h2>";
    $sql = "SELECT camera_no, camera_mac, camera_name
            FROM mst_camera
            WHERE del_flg = 0
            ORDER BY camera_no ASC";
    $active_cameras = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 現在有効なカメラ: " . count($active_cameras) . "台</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>camera_no</th><th>camera_mac</th><th>camera_name</th></tr>";
    foreach ($active_cameras as $cam) {
        echo "<tr>";
        echo "<td>{$cam['camera_no']}</td>";
        echo "<td>{$cam['camera_mac']}</td>";
        echo "<td>{$cam['camera_name']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // ステップ3: 復元処理
    // ========================================
    echo "<h2>🔧 ステップ3: 復元処理</h2>";

    $db->beginTransaction();

    $restored_count = 0;

    foreach ($deleted_cameras as $del_cam) {
        $camera_no = $del_cam['camera_no'];

        // 同じcamera_noで有効なカメラがあるか確認
        $check = $db->prepare("SELECT camera_no FROM mst_camera WHERE camera_no = :camera_no AND del_flg = 0");
        $check->execute(['camera_no' => $camera_no]);
        $active_exists = $check->fetch();

        if ($active_exists) {
            // 有効なカメラがある場合、それを削除
            echo "<p>⚠️ camera_no={$camera_no}: 有効なカメラを削除</p>";
            $stmt = $db->prepare("DELETE FROM mst_camera WHERE camera_no = :camera_no AND del_flg = 0");
            $stmt->execute(['camera_no' => $camera_no]);
        }

        // 論理削除を解除
        echo "<p>✅ camera_no={$camera_no}: 元のカメラを復元 (name={$del_cam['camera_name']})</p>";
        $stmt = $db->prepare("
            UPDATE mst_camera
            SET del_flg = 0,
                del_no = NULL,
                del_dt = NULL
            WHERE camera_no = :camera_no AND del_flg = 1
        ");
        $stmt->execute(['camera_no' => $camera_no]);
        $restored_count++;
    }

    $db->commit();

    // ========================================
    // 完了
    // ========================================
    echo "<hr>";
    echo "<h2>✅ 復元完了！</h2>";
    echo "<p><strong>復元したカメラ数: {$restored_count}台</strong></p>";

    echo "<h3>📋 次のステップ</h3>";
    echo "<ol>";
    echo "<li><a href='diagnose_camera_mapping.php' target='_blank'>📊 マッピング診断を再実行</a></li>";
    echo "<li>プレイヤーページでWebRTC接続を確認</li>";
    echo "<li><a href='machine_control_v2.php'>🖥️ マシン管理画面で確認</a></li>";
    echo "</ol>";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
