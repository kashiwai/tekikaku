<?php
/**
 * カメラ番号のみ修正スクリプト
 *
 * camera_noをmachine_noと一致させる（1:1マッピング）
 * ただし、camera_name（PeerID）は変更しない
 *
 * IMPORTANT: camera_nameは動的PeerID取得があるので変更不要
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔧 カメラ番号修正（camera_nameは保持）</h1>";
    echo "<hr>";

    // ========================================
    // ステップ1: 現在のマシンとカメラの紐付け確認
    // ========================================
    echo "<h2>📋 ステップ1: 現在の状態確認</h2>";
    $sql = "SELECT dm.machine_no, dm.camera_no, dm.name, mc.camera_name, mc.camera_mac
            FROM dat_machine dm
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no AND mc.del_flg = 0
            WHERE dm.del_flg = 0
            ORDER BY dm.machine_no ASC";
    $machines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 登録マシン数: " . count($machines) . "台</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>machine_no</th><th>camera_no</th><th>camera_name</th><th>camera_mac</th><th>状態</th></tr>";

    $mismatched = [];
    foreach ($machines as $m) {
        $status = ($m['machine_no'] == $m['camera_no']) ? '✅ 一致' : '❌ 不一致';
        $style = ($m['machine_no'] == $m['camera_no']) ? '' : 'style="background-color:#ffe6e6;"';

        echo "<tr {$style}>";
        echo "<td>{$m['machine_no']}</td>";
        echo "<td>{$m['camera_no']}</td>";
        echo "<td>{$m['camera_name']}</td>";
        echo "<td>{$m['camera_mac']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";

        if ($m['machine_no'] != $m['camera_no']) {
            $mismatched[] = $m;
        }
    }
    echo "</table>";

    echo "<p><strong>不一致のマシン: " . count($mismatched) . "台</strong></p>";

    // ========================================
    // ステップ2: 修正実行確認
    // ========================================
    if (count($mismatched) == 0) {
        echo "<hr>";
        echo "<h2>✅ すべて一致しています</h2>";
        echo "<p>修正不要です。</p>";
        echo "<p><a href='diagnose_camera_mapping.php'>📊 マッピング診断</a></p>";
        echo "<p><a href='machine_control_v2.php'>🖥️ マシン管理画面</a></p>";
        exit;
    }

    if (!isset($_GET['confirm']) || $_GET['confirm'] != '1') {
        echo "<hr>";
        echo "<h2>⚠️ 修正確認</h2>";
        echo "<p><strong>以下の処理を実行します：</strong></p>";
        echo "<ol>";
        echo "<li>dat_machine の camera_no を machine_no と同じ値に更新</li>";
        echo "<li>必要に応じて mst_camera のカメラ番号を変更</li>";
        echo "<li><strong>重要：camera_name（PeerID）は変更しません</strong></li>";
        echo "</ol>";
        echo "<p><a href='?confirm=1' class='btn btn-danger' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>🔧 修正を実行する</a></p>";
        echo "<p><a href='machine_control_v2.php'>❌ キャンセル</a></p>";
        exit;
    }

    // ========================================
    // ステップ3: 修正実行
    // ========================================
    echo "<h2>🔧 ステップ2: 修正実行</h2>";

    $db->beginTransaction();

    foreach ($mismatched as $m) {
        $machine_no = $m['machine_no'];
        $old_camera_no = $m['camera_no'];
        $new_camera_no = $machine_no;  // camera_no = machine_no

        echo "<p>🔄 machine_no={$machine_no}: camera_no を {$old_camera_no} → {$new_camera_no} に変更</p>";

        // mst_camera にカメラ番号が存在するか確認
        $check = $db->prepare("SELECT camera_no FROM mst_camera WHERE camera_no = :camera_no");
        $check->execute(['camera_no' => $new_camera_no]);
        $target_camera_exists = $check->fetch();

        if ($target_camera_exists) {
            // 既に新しいcamera_noが存在する場合
            echo "<p>&nbsp;&nbsp;⚠️  camera_no={$new_camera_no} は既に存在します</p>";

            // 古いカメラ番号のカメラ情報を取得
            $check2 = $db->prepare("SELECT camera_name, camera_mac FROM mst_camera WHERE camera_no = :camera_no");
            $check2->execute(['camera_no' => $old_camera_no]);
            $old_camera = $check2->fetch(PDO::FETCH_ASSOC);

            if ($old_camera) {
                // 既存のカメラ番号に上書き（camera_nameとMACを保持）
                echo "<p>&nbsp;&nbsp;✅ camera_no={$new_camera_no} の情報を更新（name={$old_camera['camera_name']}, mac={$old_camera['camera_mac']}）</p>";
                $stmt = $db->prepare("
                    UPDATE mst_camera
                    SET camera_name = :camera_name,
                        camera_mac = :camera_mac,
                        upd_no = 1,
                        upd_dt = NOW()
                    WHERE camera_no = :camera_no
                ");
                $stmt->execute([
                    'camera_no' => $new_camera_no,
                    'camera_name' => $old_camera['camera_name'],
                    'camera_mac' => $old_camera['camera_mac']
                ]);

                // 古いカメラ番号を削除
                $stmt = $db->prepare("DELETE FROM mst_camera WHERE camera_no = :camera_no");
                $stmt->execute(['camera_no' => $old_camera_no]);
            }
        } else {
            // 新しいcamera_noが存在しない場合、カメラ番号を変更
            $check3 = $db->prepare("SELECT camera_no FROM mst_camera WHERE camera_no = :camera_no");
            $check3->execute(['camera_no' => $old_camera_no]);
            $camera_exists = $check3->fetch();

            if ($camera_exists) {
                echo "<p>&nbsp;&nbsp;✅ mst_camera のカメラ番号を変更（camera_nameは保持）</p>";
                $stmt = $db->prepare("
                    UPDATE mst_camera
                    SET camera_no = :new_camera_no,
                        upd_no = 1,
                        upd_dt = NOW()
                    WHERE camera_no = :old_camera_no
                ");
                $stmt->execute([
                    'new_camera_no' => $new_camera_no,
                    'old_camera_no' => $old_camera_no
                ]);
            } else {
                echo "<p>&nbsp;&nbsp;⚠️  mst_camera にカメラが存在しないため、スキップ</p>";
            }
        }

        // dat_machine の camera_no を更新
        $stmt = $db->prepare("
            UPDATE dat_machine
            SET camera_no = :camera_no
            WHERE machine_no = :machine_no
        ");
        $stmt->execute([
            'camera_no' => $new_camera_no,
            'machine_no' => $machine_no
        ]);

        echo "<p>&nbsp;&nbsp;✅ dat_machine 更新完了</p>";
    }

    $db->commit();

    // ========================================
    // 完了
    // ========================================
    echo "<hr>";
    echo "<h2>✅ 修正完了！</h2>";
    echo "<p><strong>修正したマシン数: " . count($mismatched) . "台</strong></p>";

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
