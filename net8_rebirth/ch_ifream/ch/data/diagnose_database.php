<?php
/**
 * Database Diagnostic Script (READ-ONLY)
 * 既存データを一切変更しない診断専用スクリプト
 */

header('Content-Type: text/html; charset=utf-8');

require_once('../_etc/require_files.php');

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>";
echo "<title>🔍 Database Diagnostic (READ-ONLY)</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #fff; }
    .section { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 5px; }
    .success { color: #00ff00; }
    .warning { color: #ffaa00; }
    .error { color: #ff4444; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #555; }
    th { background: #333; }
    tr:hover { background: #333; }
</style>";
echo "</head><body>";

echo "<h1>🔍 NET8 Database Diagnostic (READ-ONLY)</h1>";
echo "<p class='warning'>⚠️ このスクリプトはデータを一切変更しません</p>";

try {
    $pdo = get_db_connection();
    echo "<p class='success'>✅ データベース接続成功</p>";

    // 1. モデル一覧
    echo "<div class='section'>";
    echo "<h2>📊 モデル一覧 (mst_model)</h2>";
    $stmt = $pdo->query("
        SELECT model_no, model_cd, model_name, category, del_flg
        FROM mst_model
        ORDER BY model_cd
        LIMIT 20
    ");
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($models) > 0) {
        echo "<table>";
        echo "<tr><th>model_no</th><th>model_cd</th><th>model_name</th><th>category</th><th>del_flg</th></tr>";
        foreach ($models as $m) {
            $catText = $m['category'] == 1 ? 'パチンコ' : 'スロット';
            $delClass = $m['del_flg'] == 1 ? 'error' : 'success';
            echo "<tr class='{$delClass}'>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td><strong>{$m['model_cd']}</strong></td>";
            echo "<td>{$m['model_name']}</td>";
            echo "<td>{$catText}</td>";
            echo "<td>{$m['del_flg']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ モデルが見つかりません</p>";
    }
    echo "</div>";

    // 2. マシン一覧
    echo "<div class='section'>";
    echo "<h2>🎰 マシン一覧 (dat_machine)</h2>";
    $stmt = $pdo->query("
        SELECT
            m.machine_no,
            m.model_no,
            mm.model_cd,
            mm.model_name,
            m.camera_no,
            mc.camera_name,
            m.signaling_id,
            m.machine_status,
            m.del_flg,
            m.end_date,
            lm.assign_flg,
            lm.member_no as assigned_member
        FROM dat_machine m
        LEFT JOIN mst_model mm ON m.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON m.camera_no = mc.camera_no
        LEFT JOIN lnk_machine lm ON m.machine_no = lm.machine_no
        ORDER BY m.machine_no
        LIMIT 20
    ");
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($machines) > 0) {
        echo "<table>";
        echo "<tr><th>machine_no</th><th>model_cd</th><th>model_name</th><th>camera_no</th><th>camera_name</th><th>status</th><th>assigned</th></tr>";
        foreach ($machines as $m) {
            $statusText = [
                0 => '⏸️ 停止',
                1 => '✅ 稼働中',
                2 => '🔧 メンテ'
            ][$m['machine_status']] ?? '❓';

            $assignedText = $m['assign_flg'] == 1 ? '🔒 使用中' : '🟢 利用可能';
            $rowClass = $m['del_flg'] == 1 ? 'error' : ($m['assign_flg'] == 1 ? 'warning' : 'success');

            echo "<tr class='{$rowClass}'>";
            echo "<td><strong>{$m['machine_no']}</strong></td>";
            echo "<td>{$m['model_cd']}</td>";
            echo "<td>{$m['model_name']}</td>";
            echo "<td>{$m['camera_no']}</td>";
            echo "<td>{$m['camera_name']}</td>";
            echo "<td>{$statusText}</td>";
            echo "<td>{$assignedText}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ マシンが見つかりません</p>";
    }
    echo "</div>";

    // 3. カメラ一覧
    echo "<div class='section'>";
    echo "<h2>📹 カメラ一覧 (mst_camera)</h2>";
    $stmt = $pdo->query("
        SELECT camera_no, camera_name, camera_mac, created_at, updated_at
        FROM mst_camera
        ORDER BY camera_no
        LIMIT 20
    ");
    $cameras = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($cameras) > 0) {
        echo "<table>";
        echo "<tr><th>camera_no</th><th>camera_name (PeerID)</th><th>camera_mac</th><th>updated_at</th></tr>";
        foreach ($cameras as $c) {
            echo "<tr>";
            echo "<td><strong>{$c['camera_no']}</strong></td>";
            echo "<td>{$c['camera_name']}</td>";
            echo "<td>{$c['camera_mac']}</td>";
            echo "<td>{$c['updated_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>❌ カメラが見つかりません</p>";
    }
    echo "</div>";

    // 4. KAIJI01の状態
    echo "<div class='section'>";
    echo "<h2>🎯 KAIJI01モデルの状態</h2>";
    $stmt = $pdo->prepare("
        SELECT model_no, model_cd, model_name
        FROM mst_model
        WHERE model_cd = 'KAIJI01' AND del_flg = 0
    ");
    $stmt->execute();
    $kaiji01 = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($kaiji01) {
        echo "<p class='success'>✅ KAIJI01モデルが存在します</p>";
        echo "<ul>";
        echo "<li>model_no: {$kaiji01['model_no']}</li>";
        echo "<li>model_name: {$kaiji01['model_name']}</li>";
        echo "</ul>";

        // KAIJI01用のマシン確認
        $stmt = $pdo->prepare("
            SELECT m.machine_no, m.camera_no, mc.camera_name, m.machine_status, lm.assign_flg
            FROM dat_machine m
            LEFT JOIN mst_camera mc ON m.camera_no = mc.camera_no
            LEFT JOIN lnk_machine lm ON m.machine_no = lm.machine_no
            WHERE m.model_no = :model_no AND m.del_flg = 0
        ");
        $stmt->execute(['model_no' => $kaiji01['model_no']]);
        $kaiji01Machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($kaiji01Machines) > 0) {
            echo "<p class='success'>✅ KAIJI01用のマシンが {count($kaiji01Machines)} 台見つかりました</p>";
            echo "<table>";
            echo "<tr><th>machine_no</th><th>camera_no</th><th>camera_name</th><th>status</th><th>available</th></tr>";
            foreach ($kaiji01Machines as $m) {
                $available = ($m['machine_status'] == 1 && $m['assign_flg'] == 0) ? '✅ 利用可能' : '❌ 利用不可';
                echo "<tr>";
                echo "<td>{$m['machine_no']}</td>";
                echo "<td>{$m['camera_no']}</td>";
                echo "<td>{$m['camera_name']}</td>";
                echo "<td>{$m['machine_status']}</td>";
                echo "<td>{$available}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='error'>❌ KAIJI01用のマシンが見つかりません</p>";
            echo "<p class='warning'>💡 他のモデルのマシンを利用するか、新しいマシンを追加する必要があります</p>";
        }
    } else {
        echo "<p class='error'>❌ KAIJI01モデルが見つかりません</p>";
        echo "<p class='warning'>💡 モデルを追加する必要があります</p>";
    }
    echo "</div>";

    // 5. 推奨アクション
    echo "<div class='section'>";
    echo "<h2>💡 推奨アクション</h2>";

    $availableCount = 0;
    $stmt = $pdo->query("
        SELECT COUNT(*) as cnt
        FROM dat_machine m
        WHERE m.del_flg = 0
        AND m.machine_status = 1
        AND m.end_date >= CURDATE()
        AND NOT EXISTS (
            SELECT 1 FROM lnk_machine lm
            WHERE lm.machine_no = m.machine_no AND lm.assign_flg = 1
        )
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $availableCount = $result['cnt'];

    echo "<ul>";
    echo "<li>利用可能なマシン総数: <strong>{$availableCount}</strong> 台</li>";

    if (!$kaiji01) {
        echo "<li class='error'>❌ KAIJI01モデルを追加する必要があります</li>";
    } elseif (count($kaiji01Machines ?? []) == 0) {
        echo "<li class='warning'>⚠️ KAIJI01用のマシンを追加するか、既存マシンを割り当てる必要があります</li>";

        // 利用可能な他のマシンを表示
        if ($availableCount > 0) {
            echo "<li class='success'>💡 他の利用可能なマシンを一時的にKAIJI01に割り当てることができます</li>";
        }
    } else {
        $availableKaiji = array_filter($kaiji01Machines, function($m) {
            return $m['machine_status'] == 1 && $m['assign_flg'] == 0;
        });
        if (count($availableKaiji) > 0) {
            echo "<li class='success'>✅ 利用可能なKAIJI01マシンがあります。ゲーム開始できます！</li>";
        } else {
            echo "<li class='error'>❌ KAIJI01マシンは全て使用中または停止中です</li>";
        }
    }
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p class='error'>❌ データベースエラー: " . htmlspecialchars($e->getMessage()) . "</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ エラー: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<p><a href='/data/xxxadmin/test_china_embed.html' style='color: #667eea;'>← テストページに戻る</a></p>";
echo "</body></html>";
