<?php
/**
 * 重複機種番号修正スクリプト
 *
 * model_no 100-107 の重複を修正
 * 100 → 5
 * 101 → 6
 * 102 → 5
 * 103 → 7
 * 104 → 4
 * 105 → 10
 * 106 → 2
 * 107 → 107 (正しい)
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔧 重複機種番号修正</h1>";
    echo "<hr>";

    // 重複マッピング
    $duplicateMapping = [
        100 => 5,
        101 => 6,
        102 => 5,
        103 => 7,
        104 => 4,
        105 => 10,
        106 => 2,
        107 => 107  // これは正しい
    ];

    // ========================================
    // ステップ1: 現在の重複機種確認
    // ========================================
    echo "<h2>📋 ステップ1: 重複機種確認 (model_no 100-107)</h2>";
    $sql = "SELECT model_no, model_cd, model_name, model_roman
            FROM mst_model
            WHERE model_no BETWEEN 100 AND 107
            ORDER BY model_no ASC";
    $duplicates = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 重複機種数: " . count($duplicates) . "件</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>model_no</th><th>model_cd</th><th>model_name</th><th>→</th><th>正しいmodel_no</th></tr>";
    foreach ($duplicates as $dup) {
        $correctNo = $duplicateMapping[$dup['model_no']] ?? $dup['model_no'];
        $style = ($correctNo != $dup['model_no']) ? 'style="background-color:#ffe6e6;"' : '';
        echo "<tr {$style}>";
        echo "<td>{$dup['model_no']}</td>";
        echo "<td>{$dup['model_cd']}</td>";
        echo "<td>{$dup['model_name']}</td>";
        echo "<td>→</td>";
        echo "<td><strong>{$correctNo}</strong></td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // ステップ2: 影響を受けるマシン確認
    // ========================================
    echo "<h2>📋 ステップ2: 影響を受けるマシン確認</h2>";
    $sql = "SELECT dm.machine_no, dm.model_no, dm.name, mm.model_name
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.model_no BETWEEN 100 AND 107
            AND dm.del_flg = 0
            ORDER BY dm.machine_no ASC";
    $affectedMachines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 影響を受けるマシン: " . count($affectedMachines) . "台</p>";

    if (count($affectedMachines) > 0) {
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        echo "<tr><th>machine_no</th><th>現在のmodel_no</th><th>機種名</th><th>→</th><th>正しいmodel_no</th></tr>";
        foreach ($affectedMachines as $m) {
            $correctNo = $duplicateMapping[$m['model_no']] ?? $m['model_no'];
            echo "<tr style='background-color:#fff3cd;'>";
            echo "<td>{$m['machine_no']}</td>";
            echo "<td>{$m['model_no']}</td>";
            echo "<td>{$m['model_name']}</td>";
            echo "<td>→</td>";
            echo "<td><strong>{$correctNo}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }

    // ========================================
    // ステップ3: 正しい機種番号の存在確認
    // ========================================
    echo "<h2>📋 ステップ3: 正しい機種番号の存在確認</h2>";
    $correctNos = array_unique(array_values($duplicateMapping));
    $correctNos = array_filter($correctNos, function($no) {
        return $no < 100; // 100未満のみチェック（107は除外）
    });

    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>model_no</th><th>model_cd</th><th>model_name</th><th>状態</th></tr>";

    $missingModels = [];
    foreach ($correctNos as $correctNo) {
        $sql = "SELECT model_no, model_cd, model_name FROM mst_model WHERE model_no = :model_no";
        $stmt = $db->prepare($sql);
        $stmt->execute(['model_no' => $correctNo]);
        $model = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($model) {
            echo "<tr style='background-color:#d4edda;'>";
            echo "<td>{$model['model_no']}</td>";
            echo "<td>{$model['model_cd']}</td>";
            echo "<td>{$model['model_name']}</td>";
            echo "<td>✅ 存在</td>";
            echo "</tr>";
        } else {
            echo "<tr style='background-color:#f8d7da;'>";
            echo "<td>{$correctNo}</td>";
            echo "<td colspan='2'>-</td>";
            echo "<td>❌ 存在しない</td>";
            echo "</tr>";
            $missingModels[] = $correctNo;
        }
    }
    echo "</table>";

    if (count($missingModels) > 0) {
        echo "<div style='background:#f8d7da; padding:10px; margin:10px 0; border-left:4px solid #dc3545;'>";
        echo "<strong>❌ エラー: 以下の機種番号が存在しません</strong><br>";
        echo implode(', ', $missingModels);
        echo "<br><br>先にこれらの機種を作成してください。";
        echo "</div>";
        exit;
    }

    // ========================================
    // 修正確認
    // ========================================
    if (!isset($_GET['confirm']) || $_GET['confirm'] != '1') {
        echo "<hr>";
        echo "<h2>⚠️ 修正確認</h2>";
        echo "<p><strong>以下の処理を実行します：</strong></p>";
        echo "<ol>";
        echo "<li>dat_machineで重複model_no(100-106)を使用している台を正しいmodel_noに更新</li>";
        echo "<li>mst_modelから重複機種(100-106)を論理削除</li>";
        echo "<li>model_no=107は削除せず保持（正しい番号）</li>";
        echo "</ol>";
        echo "<p><strong>影響: " . count($affectedMachines) . "台のマシン</strong></p>";
        echo "<p><a href='?confirm=1' class='btn btn-danger' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>🔧 修正を実行する</a></p>";
        echo "<p><a href='machine_control_v2.php'>❌ キャンセル</a></p>";
        exit;
    }

    // ========================================
    // 修正実行
    // ========================================
    echo "<h2>🔧 ステップ4: 修正実行</h2>";

    $db->beginTransaction();

    // マシンのmodel_noを修正
    foreach ($affectedMachines as $m) {
        $correctNo = $duplicateMapping[$m['model_no']];

        if ($correctNo != $m['model_no']) {
            echo "<p>🔄 machine_no={$m['machine_no']}: model_no を {$m['model_no']} → {$correctNo} に変更</p>";

            $stmt = $db->prepare("
                UPDATE dat_machine
                SET model_no = :correct_no
                WHERE machine_no = :machine_no
            ");
            $stmt->execute([
                'correct_no' => $correctNo,
                'machine_no' => $m['machine_no']
            ]);
        }
    }

    // 重複機種を論理削除（107は除く）
    echo "<p>🗑️ 重複機種を論理削除中...</p>";
    foreach ($duplicateMapping as $dupNo => $correctNo) {
        if ($dupNo != 107 && $dupNo != $correctNo) {  // 107と一致するものは削除しない
            $stmt = $db->prepare("
                UPDATE mst_model
                SET del_flg = 1,
                    del_no = 1,
                    del_dt = NOW()
                WHERE model_no = :model_no
            ");
            $stmt->execute(['model_no' => $dupNo]);
            echo "<p>&nbsp;&nbsp;✅ model_no={$dupNo} を削除</p>";
        }
    }

    $db->commit();

    // ========================================
    // 完了
    // ========================================
    echo "<hr>";
    echo "<h2>✅ 修正完了！</h2>";
    echo "<p><strong>修正したマシン数: " . count($affectedMachines) . "台</strong></p>";
    echo "<p><strong>削除した重複機種: " . (count($duplicateMapping) - 1) . "件</strong></p>";

    echo "<h3>📋 次のステップ</h3>";
    echo "<ol>";
    echo "<li><a href='debug_machine_model_mapping.php' target='_blank'>🔍 デバッグ画面で確認</a></li>";
    echo "<li><a href='/api/v1/machines.php' target='_blank'>🌐 API動作確認</a></li>";
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
