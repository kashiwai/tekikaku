<?php
/**
 * 機種マッピング詳細デバッグ
 *
 * dat_machineとmst_modelの関係を詳細に調査
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔍 機種マッピング詳細デバッグ</h1>";
    echo "<hr>";

    // ========================================
    // ステップ1: mst_model テーブルの全機種確認
    // ========================================
    echo "<h2>📋 ステップ1: 機種マスター (mst_model)</h2>";
    $sql = "SELECT model_no, model_cd, model_name, model_roman, del_flg
            FROM mst_model
            ORDER BY model_no ASC";
    $models = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 登録機種数: " . count($models) . "種類</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>model_no</th><th>model_cd</th><th>model_name</th><th>model_roman</th><th>del_flg</th></tr>";
    foreach ($models as $model) {
        $delStyle = ($model['del_flg'] == 1) ? 'style="background-color:#ffcccc;"' : '';
        echo "<tr {$delStyle}>";
        echo "<td>{$model['model_no']}</td>";
        echo "<td>{$model['model_cd']}</td>";
        echo "<td>{$model['model_name']}</td>";
        echo "<td>{$model['model_roman']}</td>";
        echo "<td>{$model['del_flg']}</td>";
        echo "</tr>";
    }
    echo "</table>";

    // ========================================
    // ステップ2: dat_machine と mst_model の結合確認
    // ========================================
    echo "<h2>📋 ステップ2: マシンと機種の紐付け状態</h2>";
    $sql = "SELECT
                dm.machine_no,
                dm.model_no as machine_model_no,
                dm.camera_no,
                mm.model_no as model_model_no,
                mm.model_name,
                mm.model_cd,
                mm.del_flg as model_del_flg
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.del_flg = 0
            ORDER BY dm.machine_no ASC";
    $machines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<p>✅ 登録マシン数: " . count($machines) . "台</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr>";
    echo "<th>machine_no</th>";
    echo "<th>camera_no</th>";
    echo "<th>dat_machine<br>model_no</th>";
    echo "<th>mst_model<br>model_no</th>";
    echo "<th>model_name</th>";
    echo "<th>model_cd</th>";
    echo "<th>状態</th>";
    echo "</tr>";

    $errorCount = 0;
    foreach ($machines as $m) {
        $hasError = false;
        $errorMsg = '';

        // エラーチェック
        if (empty($m['model_name']) && $m['machine_model_no'] != 0) {
            $hasError = true;
            $errorMsg = '❌ 機種が見つからない';
            $errorCount++;
        } elseif ($m['model_del_flg'] == 1) {
            $hasError = true;
            $errorMsg = '⚠️ 削除された機種';
            $errorCount++;
        } elseif ($m['machine_model_no'] == 0) {
            $errorMsg = '✅ 空き機器';
        } else {
            $errorMsg = '✅ 正常';
        }

        $style = $hasError ? 'style="background-color:#ffe6e6;"' : '';

        echo "<tr {$style}>";
        echo "<td><strong>{$m['machine_no']}</strong></td>";
        echo "<td>{$m['camera_no']}</td>";
        echo "<td>{$m['machine_model_no']}</td>";
        echo "<td>{$m['model_model_no']}</td>";
        echo "<td>{$m['model_name']}</td>";
        echo "<td>{$m['model_cd']}</td>";
        echo "<td>{$errorMsg}</td>";
        echo "</tr>";
    }
    echo "</table>";

    echo "<p><strong>エラーのあるマシン: {$errorCount}台</strong></p>";

    // ========================================
    // ステップ3: 特定マシンの詳細確認（1番台）
    // ========================================
    echo "<h2>🔍 ステップ3: 1番台の詳細確認</h2>";
    $sql = "SELECT * FROM dat_machine WHERE machine_no = 1 AND del_flg = 0";
    $machine1 = $db->getRow($sql, PDO::FETCH_ASSOC);

    if ($machine1) {
        echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
        foreach ($machine1 as $key => $value) {
            echo "<tr>";
            echo "<th style='text-align:right; padding:5px;'>{$key}</th>";
            echo "<td style='padding:5px;'>{$value}</td>";
            echo "</tr>";
        }
        echo "</table>";

        // 1番台のmodel_noに対応する機種を確認
        if ($machine1['model_no']) {
            echo "<h3>📊 1番台のmodel_no={$machine1['model_no']}に対応する機種</h3>";
            $sql = "SELECT * FROM mst_model WHERE model_no = :model_no";
            $stmt = $db->prepare($sql);
            $stmt->execute(['model_no' => $machine1['model_no']]);
            $model = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($model) {
                echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
                foreach ($model as $key => $value) {
                    echo "<tr>";
                    echo "<th style='text-align:right; padding:5px;'>{$key}</th>";
                    echo "<td style='padding:5px;'>{$value}</td>";
                    echo "</tr>";
                }
                echo "</table>";

                if ($model['model_name'] == '吉宗') {
                    echo "<p style='color:green; font-weight:bold;'>✅ 1番台は正しく「吉宗」に設定されています</p>";
                } else {
                    echo "<p style='color:red; font-weight:bold;'>❌ 1番台が「{$model['model_name']}」になっています（本来は「吉宗」）</p>";
                }
            } else {
                echo "<p style='color:red;'>❌ model_no={$machine1['model_no']}の機種がmst_modelに存在しません</p>";
            }
        }
    } else {
        echo "<p style='color:red;'>❌ 1番台のデータが見つかりません</p>";
    }

    // ========================================
    // ステップ4: API実行結果確認
    // ========================================
    echo "<h2>🌐 ステップ4: API実行結果</h2>";
    echo "<p>以下のAPIにアクセスして確認してください：</p>";
    echo "<ul>";
    echo "<li><a href='/api/v1/machines.php' target='_blank'>/api/v1/machines.php</a> - 全マシン一覧</li>";
    echo "<li><a href='/api/v1/machines.php?machineNo=1' target='_blank'>/api/v1/machines.php?machineNo=1</a> - 1番台のみ</li>";
    echo "</ul>";

} catch (Exception $e) {
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
