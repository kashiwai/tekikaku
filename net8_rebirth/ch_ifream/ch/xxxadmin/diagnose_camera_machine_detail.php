<?php
/**
 * カメラ・マシン・機種の詳細診断
 *
 * 1番台の吉宗がカイジ（8番）として認識される問題を調査
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔍 カメラ・マシン・機種 詳細診断</h1>";
    echo "<hr>";

    // ========================================
    // ステップ1: 1番台の完全な状態確認
    // ========================================
    echo "<h2>📋 ステップ1: 1番台の詳細情報</h2>";

    $sql = "SELECT
                dm.*,
                mm.model_name,
                mm.model_cd,
                mc.camera_name,
                mc.camera_mac
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
            WHERE dm.machine_no = 1
            AND dm.del_flg = 0";
    $machine1 = $db->getRow($sql, PDO::FETCH_ASSOC);

    if ($machine1) {
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr style='background-color:#e3f2fd;'><th colspan='2'>1番台 マシン情報</th></tr>";
        echo "<tr><th>machine_no</th><td><strong style='color:blue;'>{$machine1['machine_no']}</strong></td></tr>";
        echo "<tr><th>camera_no</th><td><strong style='color:blue;'>{$machine1['camera_no']}</strong></td></tr>";
        echo "<tr><th>model_no</th><td><strong style='color:blue;'>{$machine1['model_no']}</strong></td></tr>";
        echo "<tr><th>model_name</th><td><strong style='color:green;'>{$machine1['model_name']}</strong></td></tr>";
        echo "<tr><th>model_cd</th><td>{$machine1['model_cd']}</td></tr>";
        echo "<tr><th>camera_name (PeerID)</th><td>{$machine1['camera_name']}</td></tr>";
        echo "<tr><th>camera_mac</th><td><strong style='color:purple;'>{$machine1['camera_mac']}</strong></td></tr>";
        echo "<tr><th>machine_status</th><td>{$machine1['machine_status']}</td></tr>";
        echo "</table>";

        // 期待値との比較
        echo "<h3>🎯 期待値との比較</h3>";
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr><th>項目</th><th>期待値</th><th>実際の値</th><th>状態</th></tr>";

        $checks = [
            ['machine_no', '1', $machine1['machine_no']],
            ['camera_no', '1', $machine1['camera_no']],
            ['model_name', '吉宗', $machine1['model_name']],
        ];

        foreach ($checks as $check) {
            list($item, $expected, $actual) = $check;
            $isOk = ($expected == $actual);
            $style = $isOk ? 'style="background-color:#d4edda;"' : 'style="background-color:#f8d7da;"';
            $status = $isOk ? '✅ 正常' : '❌ 異常';

            echo "<tr {$style}>";
            echo "<td>{$item}</td>";
            echo "<td>{$expected}</td>";
            echo "<td><strong>{$actual}</strong></td>";
            echo "<td>{$status}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ 1番台のデータが見つかりません</p>";
    }

    // ========================================
    // ステップ2: MACアドレスで逆引き
    // ========================================
    echo "<h2>📋 ステップ2: MACアドレスからの逆引き</h2>";

    if ($machine1 && $machine1['camera_mac']) {
        $mac = $machine1['camera_mac'];
        echo "<p>検索MACアドレス: <strong style='color:purple;'>{$mac}</strong></p>";

        // このMACアドレスを持つカメラをすべて検索
        $sql = "SELECT * FROM mst_camera WHERE camera_mac = :mac ORDER BY camera_no ASC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['mac' => $mac]);
        $cameras = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo "<p>✅ このMACアドレスを持つカメラ: " . count($cameras) . "件</p>";
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr><th>camera_no</th><th>camera_name</th><th>camera_mac</th><th>del_flg</th></tr>";
        foreach ($cameras as $cam) {
            $delStyle = ($cam['del_flg'] == 1) ? 'style="background-color:#ffcccc;"' : '';
            echo "<tr {$delStyle}>";
            echo "<td><strong>{$cam['camera_no']}</strong></td>";
            echo "<td>{$cam['camera_name']}</td>";
            echo "<td>{$cam['camera_mac']}</td>";
            echo "<td>{$cam['del_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";

        if (count($cameras) > 1) {
            echo "<div style='background:#fff3cd; padding:10px; margin:10px 0; border-left:4px solid #ffc107;'>";
            echo "<strong>⚠️ 警告: 同じMACアドレスが複数のカメラ番号に登録されています</strong><br>";
            echo "これがWindows側のカメラサーバで混乱を起こしている可能性があります。";
            echo "</div>";
        }
    }

    // ========================================
    // ステップ3: カメラ番号8の確認（カイジ）
    // ========================================
    echo "<h2>📋 ステップ3: カメラ番号8の確認（カイジの可能性）</h2>";

    $sql = "SELECT
                dm.machine_no,
                dm.camera_no,
                dm.model_no,
                mm.model_name,
                mc.camera_name,
                mc.camera_mac
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
            WHERE dm.machine_no = 8
            AND dm.del_flg = 0";
    $machine8 = $db->getRow($sql, PDO::FETCH_ASSOC);

    if ($machine8) {
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr style='background-color:#ffe6e6;'><th colspan='2'>8番台（カイジ）情報</th></tr>";
        echo "<tr><th>machine_no</th><td>{$machine8['machine_no']}</td></tr>";
        echo "<tr><th>camera_no</th><td>{$machine8['camera_no']}</td></tr>";
        echo "<tr><th>model_no</th><td>{$machine8['model_no']}</td></tr>";
        echo "<tr><th>model_name</th><td><strong>{$machine8['model_name']}</strong></td></tr>";
        echo "<tr><th>camera_name (PeerID)</th><td>{$machine8['camera_name']}</td></tr>";
        echo "<tr><th>camera_mac</th><td><strong style='color:purple;'>{$machine8['camera_mac']}</strong></td></tr>";
        echo "</table>";

        // MACアドレスが1番台と同じかチェック
        if ($machine1 && $machine1['camera_mac'] === $machine8['camera_mac']) {
            echo "<div style='background:#f8d7da; padding:10px; margin:10px 0; border-left:4px solid #dc3545;'>";
            echo "<strong>🔴 重大な問題: 1番台と8番台が同じMACアドレスを使用しています！</strong><br>";
            echo "MACアドレス: {$machine1['camera_mac']}<br>";
            echo "これがWindows側のカメラサーバで混乱を起こしている根本原因です。";
            echo "</div>";
        }
    }

    // ========================================
    // ステップ4: 全台のカメラ・機種マッピング確認
    // ========================================
    echo "<h2>📋 ステップ4: 全台のカメラ・機種マッピング</h2>";

    $sql = "SELECT
                dm.machine_no,
                dm.camera_no,
                dm.model_no,
                mm.model_name,
                mc.camera_mac
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
            WHERE dm.del_flg = 0
            ORDER BY dm.machine_no ASC";
    $allMachines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0; font-size:11px;'>";
    echo "<tr><th>machine_no</th><th>camera_no</th><th>model_no</th><th>model_name</th><th>camera_mac</th><th>状態</th></tr>";

    foreach ($allMachines as $m) {
        $isOk = ($m['machine_no'] == $m['camera_no']);
        $style = $isOk ? '' : 'style="background-color:#ffe6e6;"';
        $status = $isOk ? '✅' : '❌';

        echo "<tr {$style}>";
        echo "<td><strong>{$m['machine_no']}</strong></td>";
        echo "<td>{$m['camera_no']}</td>";
        echo "<td>{$m['model_no']}</td>";
        echo "<td>{$m['model_name']}</td>";
        echo "<td style='font-size:9px;'>{$m['camera_mac']}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // ステップ5: MACアドレス重複チェック
    // ========================================
    echo "<h2>📋 ステップ5: MACアドレス重複チェック</h2>";

    $sql = "SELECT camera_mac, GROUP_CONCAT(camera_no ORDER BY camera_no) as camera_nos, COUNT(*) as cnt
            FROM mst_camera
            WHERE camera_mac IS NOT NULL
            AND camera_mac != ''
            AND camera_mac NOT LIKE '00:00:00:00:%'
            GROUP BY camera_mac
            HAVING cnt > 1
            ORDER BY cnt DESC";
    $duplicateMacs = $db->getAll($sql, PDO::FETCH_ASSOC);

    if (count($duplicateMacs) > 0) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0; border-left:4px solid #dc3545;'>";
        echo "<strong>🔴 重複MACアドレス発見: " . count($duplicateMacs) . "件</strong>";
        echo "</div>";

        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr><th>camera_mac</th><th>重複しているcamera_no</th><th>重複数</th></tr>";
        foreach ($duplicateMacs as $dup) {
            echo "<tr style='background-color:#f8d7da;'>";
            echo "<td>{$dup['camera_mac']}</td>";
            echo "<td>{$dup['camera_nos']}</td>";
            echo "<td>{$dup['cnt']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:green;'>✅ MACアドレスの重複はありません</p>";
    }

    // ========================================
    // ステップ6: 解決策の提示
    // ========================================
    echo "<h2>💡 ステップ6: 問題と解決策</h2>";

    echo "<div style='background:#e3f2fd; padding:15px; margin:10px 0; border-left:4px solid #2196f3;'>";
    echo "<h3>🔍 発見された問題</h3>";
    echo "<ol>";

    if ($machine1 && $machine1['camera_no'] != 1) {
        echo "<li>1番台のcamera_noが{$machine1['camera_no']}になっている（1であるべき）</li>";
    }

    if ($machine1 && $machine1['model_name'] != '吉宗') {
        echo "<li>1番台の機種が「{$machine1['model_name']}」になっている（「吉宗」であるべき）</li>";
    }

    if (count($duplicateMacs) > 0) {
        echo "<li>MACアドレスが重複している（" . count($duplicateMacs) . "件）</li>";
    }

    echo "</ol>";
    echo "</div>";

    echo "<div style='background:#d4edda; padding:15px; margin:10px 0; border-left:4px solid #28a745;'>";
    echo "<h3>✅ 推奨される修正手順</h3>";
    echo "<ol>";
    echo "<li><a href='fix_duplicate_models.php'>重複機種番号を修正</a> - model_no 100-107の重複を解消</li>";
    echo "<li><a href='fix_camera_number_only.php'>カメラ番号を修正</a> - camera_noとmachine_noを1:1にする</li>";
    echo "<li><a href='fix_all_machines_from_list.php'>機種情報を一括修正</a> - CSVデータに基づく正しい機種設定</li>";
    echo "<li>Windows側カメラサーバを再起動 - 正しい情報で再接続</li>";
    echo "</ol>";
    echo "</div>";

} catch (Exception $e) {
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
