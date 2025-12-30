<?php
/**
 * 全マシンの機種情報を一括修正
 *
 * 画像リストに基づいて正しい機種を設定
 */

require_once('../../_etc/require_files_admin.php');

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>🔧 全マシン機種情報一括修正</h1>";
    echo "<hr>";

    // 正しい機種マッピング（CSVデータから正確に読み取り）
    // 注: 吉宗(青/ピンク/オレンジ) は全て「吉宗」機種
    // 注: 「無」は空いている機器（model_no = 0）
    $correctMapping = [
        1 => '吉宗',      // CSVでは吉宗(ピンク)
        2 => '番長',
        3 => '吉宗',      // CSVでは吉宗(ピンク)
        4 => '番長',
        5 => '吉宗',      // CSVでは吉宗(ピンク)
        6 => '番長',
        7 => '吉宗',      // CSVでは吉宗(ピンク)
        8 => 'カイジ',
        9 => 'カイジ',
        10 => '吉宗',     // CSVでは吉宗(ピンク)
        11 => '吉宗',     // CSVでは吉宗(青)
        12 => '吉宗',     // CSVでは吉宗(青)
        13 => '南国物語',
        14 => '南国物語',
        15 => '南国物語',
        16 => 'ミリオンゴッド',
        17 => null,       // 無（空き機器）
        18 => 'ミリオンゴッド',
        19 => null,       // 無（空き機器）
        20 => 'ミリオンゴッド',
        21 => null,       // 無（空き機器）
        22 => null,       // 無（空き機器）
        23 => '北斗の拳',
        24 => '北斗の拳',
        25 => null,       // 無（空き機器）
        26 => '北斗の拳',
        27 => null,       // 無（空き機器）
        28 => null,       // 無（空き機器）
        29 => 'ジャグラー',
        30 => 'ジャグラー',
        31 => null,       // 無（空き機器）
        32 => null,       // 無（空き機器）
        33 => 'ジャグラー',
        34 => null,       // 無（空き機器）
        35 => null,       // 無（空き機器）
        36 => null,       // 無（空き機器）
        37 => 'ファイヤードリフト',
        38 => '鬼武者',
        39 => null,       // 無（空き機器）
        40 => 'ビンゴ',
        41 => '北斗の拳',
        42 => null,       // 無（空き機器）
        43 => '北斗の拳',
        44 => null,       // 無（空き機器）
        45 => null,       // 無（空き機器）
        46 => null,       // 無（空き機器）
        47 => null,       // 無（空き機器）
        48 => null,       // 無（空き機器）
        49 => '北斗の拳',
        50 => '銭形',
        51 => '銭形',
        52 => null,       // 無（空き機器）
        53 => '銭形',
        54 => 'ファイヤードリフト',
        55 => '鬼武者',
        56 => null,       // 無（空き機器）
        57 => 'ビンゴ',
        58 => '島唄',
        59 => '島唄',
        60 => '吉宗',     // CSVでは吉宗(オレンジ)
        61 => null,       // 無（空き機器）
        62 => null,       // 無（空き機器）
        63 => null,       // 無（空き機器）
        64 => '島唄',
        65 => null,       // 無（空き機器）
        66 => null,       // 無（空き機器）
        67 => '島唄',
        68 => '吉宗'      // CSVでは吉宗(オレンジ)
    ];

    // 機種名とmodel_noのマッピングを取得
    $modelSql = "SELECT model_no, model_name FROM mst_model WHERE del_flg = 0";
    $models = $db->getAll($modelSql, PDO::FETCH_ASSOC);

    $modelNameToNo = [];
    foreach ($models as $model) {
        $modelNameToNo[$model['model_name']] = $model['model_no'];
    }

    echo "<h2>📋 ステップ1: 機種マスター確認</h2>";
    echo "<p>登録機種数: " . count($models) . "種類</p>";
    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>model_no</th><th>model_name</th></tr>";
    foreach ($models as $model) {
        echo "<tr><td>{$model['model_no']}</td><td>{$model['model_name']}</td></tr>";
    }
    echo "</table>";

    // 現在の状態を確認
    echo "<h2>📋 ステップ2: 現在の状態と修正内容</h2>";
    $sql = "SELECT dm.machine_no, dm.model_no, mm.model_name, dm.camera_no
            FROM dat_machine dm
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE dm.del_flg = 0
            ORDER BY dm.machine_no ASC";
    $machines = $db->getAll($sql, PDO::FETCH_ASSOC);

    echo "<table border='1' style='border-collapse:collapse; margin: 10px 0;'>";
    echo "<tr><th>machine_no</th><th>現在の機種</th><th>→</th><th>正しい機種</th><th>状態</th></tr>";

    $toUpdate = [];
    $missingModels = [];

    foreach ($machines as $m) {
        $machine_no = $m['machine_no'];
        $currentModel = $m['model_name'] ?? '未設定';

        // マッピングに存在しない台はスキップ
        if (!array_key_exists($machine_no, $correctMapping)) {
            continue;
        }

        $correctModel = $correctMapping[$machine_no];

        // 空き機器（無）の場合
        if ($correctModel === null) {
            $correctModelNo = 0;
            $correctModelDisplay = '無（空き機器）';
            $needsUpdate = ($m['model_no'] != 0);
        } else {
            // 通常の機種
            $correctModelNo = $modelNameToNo[$correctModel] ?? null;
            $correctModelDisplay = $correctModel;

            if (!$correctModelNo) {
                $missingModels[$correctModel] = true;
                echo "<tr style='background-color:#fff3cd;'>";
                echo "<td>{$machine_no}</td>";
                echo "<td>{$currentModel}</td>";
                echo "<td>→</td>";
                echo "<td>{$correctModel}</td>";
                echo "<td>⚠️ 機種マスターに未登録</td>";
                echo "</tr>";
                continue;
            }

            $needsUpdate = ($currentModel != $correctModel);
        }

        $style = $needsUpdate ? 'style="background-color:#ffe6e6;"' : '';
        $status = $needsUpdate ? '❌ 要修正' : '✅ 一致';

        echo "<tr {$style}>";
        echo "<td>{$machine_no}</td>";
        echo "<td>{$currentModel}</td>";
        echo "<td>→</td>";
        echo "<td>{$correctModelDisplay}</td>";
        echo "<td>{$status}</td>";
        echo "</tr>";

        if ($needsUpdate) {
            $toUpdate[] = [
                'machine_no' => $machine_no,
                'current_model' => $currentModel,
                'correct_model' => $correctModelDisplay,
                'correct_model_no' => $correctModelNo
            ];
        }
    }
    echo "</table>";

    if (count($missingModels) > 0) {
        echo "<div style='background:#fff3cd; padding:10px; margin:10px 0; border-left:4px solid #ffc107;'>";
        echo "<strong>⚠️ 機種マスターに未登録の機種:</strong><br>";
        echo implode(', ', array_keys($missingModels));
        echo "<br><br>これらの機種をmst_modelに追加してから再実行してください。";
        echo "</div>";
    }

    echo "<p><strong>修正が必要なマシン: " . count($toUpdate) . "台</strong></p>";

    if (count($toUpdate) == 0) {
        echo "<hr>";
        echo "<h2>✅ すべて正しく設定されています</h2>";
        echo "<p><a href='machine_control_v2.php'>🖥️ マシン管理画面へ</a></p>";
        exit;
    }

    // 修正確認
    if (!isset($_GET['confirm']) || $_GET['confirm'] != '1') {
        echo "<hr>";
        echo "<h2>⚠️ 修正確認</h2>";
        echo "<p><strong>" . count($toUpdate) . "台のマシンの機種を修正します。</strong></p>";
        echo "<p><a href='?confirm=1' class='btn btn-danger' style='display:inline-block; padding:10px 20px; background:#dc3545; color:white; text-decoration:none; border-radius:5px;'>🔧 修正を実行する</a></p>";
        echo "<p><a href='machine_control_v2.php'>❌ キャンセル</a></p>";
        exit;
    }

    // 修正実行
    echo "<h2>🔧 ステップ3: 修正実行</h2>";

    $db->beginTransaction();

    foreach ($toUpdate as $update) {
        echo "<p>🔄 machine_no={$update['machine_no']}: {$update['current_model']} → {$update['correct_model']}</p>";

        $stmt = $db->prepare("
            UPDATE dat_machine
            SET model_no = :model_no
            WHERE machine_no = :machine_no
        ");
        $stmt->execute([
            'model_no' => $update['correct_model_no'],
            'machine_no' => $update['machine_no']
        ]);
    }

    $db->commit();

    echo "<hr>";
    echo "<h2>✅ 修正完了！</h2>";
    echo "<p><strong>修正したマシン数: " . count($toUpdate) . "台</strong></p>";
    echo "<p><a href='machine_control_v2.php'>🖥️ マシン管理画面で確認</a></p>";
    echo "<p><a href='diagnose_camera_mapping.php'>📊 マッピング診断</a></p>";

} catch (Exception $e) {
    if (isset($db)) {
        $db->rollBack();
    }
    echo "<h2>❌ エラー発生</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
?>
