<?php
/**
 * カメラ・マシン 1:1マッピング修正スクリプト
 *
 * 目標: camera_no = machine_no の完全な1対1対応を実現
 *
 * 処理内容:
 * 1. mst_camera を整理（camera_no=1〜68に統一）
 * 2. dat_machine の camera_no を machine_no と同じ値に設定
 * 3. 実カメラのMACアドレスを優先的に割り当て
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔧 カメラ・マシン 1:1マッピング修正</h1>";
    echo "<hr>";

    // ========================================
    // ステップ1: 現在のマシン一覧取得
    // ========================================
    echo "<h2>📋 ステップ1: マシン情報取得</h2>";
    $sql = "SELECT machine_no, name, model_no, camera_no
            FROM dat_machine
            WHERE del_flg = 0
            ORDER BY machine_no ASC";
    $machines = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "<p>✅ 登録マシン数: " . count($machines) . "台</p>";

    // ========================================
    // ステップ2: 既存の実カメラ情報取得
    // ========================================
    echo "<h2>📹 ステップ2: 実カメラ情報取得</h2>";
    $sql = "SELECT camera_no, camera_mac, camera_name
            FROM mst_camera
            WHERE del_flg = 0
            AND camera_mac NOT LIKE '00:00:00:00:00:%'
            AND camera_mac NOT LIKE 'test-%'
            AND camera_name NOT LIKE '%entry_mode%'
            AND camera_name != 'keysocket.exe'
            ORDER BY camera_no ASC";
    $real_cameras = $db->getAll($sql, PDO::FETCH_ASSOC);
    echo "<p>✅ 実カメラ数: " . count($real_cameras) . "台</p>";

    // 実カメラのMACアドレスリスト
    $real_mac_addresses = array_column($real_cameras, 'camera_mac');

    // ========================================
    // ステップ3: mst_camera を再構築
    // ========================================
    echo "<h2>🗑️ ステップ3: mst_camera クリーンアップ</h2>";

    // 既存のカメラを全て削除フラグON（論理削除）
    $sql = "UPDATE mst_camera SET del_flg = 1, del_no = 1, del_dt = NOW()";
    $db->exec($sql);
    echo "<p>✅ 既存カメラを全て論理削除</p>";

    // ========================================
    // ステップ4: 新しいカメラマスター作成（1〜68）
    // ========================================
    echo "<h2>📹 ステップ4: 新カメラマスター作成（1:1対応）</h2>";

    $db->beginTransaction();

    $real_camera_index = 0;
    foreach ($machines as $m) {
        $machine_no = $m['machine_no'];
        $camera_no = $machine_no; // camera_no = machine_no

        // 実カメラがあれば優先的に割り当て
        if ($real_camera_index < count($real_cameras)) {
            $real_cam = $real_cameras[$real_camera_index];
            $mac_address = $real_cam['camera_mac'];
            $camera_name = "camera_{$camera_no}_" . time();
            $real_camera_index++;
            echo "<p>📹 camera_no={$camera_no} → 実カメラ MAC={$mac_address}</p>";
        } else {
            // 実カメラが不足している場合はダミーカメラ
            $mac_address = sprintf("00:00:00:00:%02X:%02X", floor($camera_no / 256), $camera_no % 256);
            $camera_name = "dummy_camera_{$camera_no}";
            echo "<p>⚪ camera_no={$camera_no} → ダミーカメラ MAC={$mac_address}</p>";
        }

        // mst_camera に登録（del_flg=0で復活 or 新規作成）
        $check = $db->prepare("SELECT camera_no FROM mst_camera WHERE camera_no = :camera_no");
        $check->execute(['camera_no' => $camera_no]);
        $exists = $check->fetch();

        if ($exists) {
            // 既存カメラを更新（論理削除を解除）
            $stmt = $db->prepare("
                UPDATE mst_camera SET
                    camera_mac = :camera_mac,
                    camera_name = :camera_name,
                    del_flg = 0,
                    upd_no = 1,
                    upd_dt = NOW()
                WHERE camera_no = :camera_no
            ");
        } else {
            // 新規カメラ作成
            $stmt = $db->prepare("
                INSERT INTO mst_camera (
                    camera_no, camera_mac, camera_name, del_flg, add_no, add_dt
                ) VALUES (
                    :camera_no, :camera_mac, :camera_name, 0, 1, NOW()
                )
            ");
        }

        $stmt->execute([
            'camera_no' => $camera_no,
            'camera_mac' => $mac_address,
            'camera_name' => $camera_name
        ]);
    }

    // ========================================
    // ステップ5: dat_machine の camera_no を更新
    // ========================================
    echo "<h2>🔗 ステップ5: マシンとカメラを1:1紐付け</h2>";

    foreach ($machines as $m) {
        $machine_no = $m['machine_no'];
        $camera_no = $machine_no; // camera_no = machine_no

        $stmt = $db->prepare("
            UPDATE dat_machine
            SET camera_no = :camera_no
            WHERE machine_no = :machine_no
        ");
        $stmt->execute([
            'camera_no' => $camera_no,
            'machine_no' => $machine_no
        ]);

        echo "<p>🔗 machine_no={$machine_no} → camera_no={$camera_no}</p>";
    }

    $db->commit();

    // ========================================
    // 完了
    // ========================================
    echo "<hr>";
    echo "<h2>✅ 修正完了！</h2>";
    echo "<p><strong>結果:</strong></p>";
    echo "<ul>";
    echo "<li>マシン数: " . count($machines) . "台</li>";
    echo "<li>実カメラ割り当て: {$real_camera_index}台</li>";
    echo "<li>ダミーカメラ作成: " . (count($machines) - $real_camera_index) . "台</li>";
    echo "</ul>";

    echo "<h3>📋 確認</h3>";
    echo "<p><a href='diagnose_camera_mapping.php' target='_blank'>📊 マッピング診断を再実行</a></p>";
    echo "<p><a href='machine_control_v2.php'>🖥️ マシン管理画面へ</a></p>";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
