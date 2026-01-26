<?php
/**
 * デスクトップリストからマシン一括登録
 *
 * desktop_no_list.csv を読み込んで、
 * 「無」以外の機種をdat_machineに登録
 */

require_once('../../_etc/require_files_admin.php');

// 機種名とmodel_noのマッピング（既存のmst_modelから取得、なければ新規作成）
$model_mapping = [];

try {
    $template = new TemplateAdmin();
    $db = $template->DB;

    echo "<h1>デスクトップリスト インポート</h1>";
    echo "<pre>";

    // CSVデータ（desktop_no_list.csv の内容）
    $csv_data = [
        ['no' => 1, 'name' => 'CAMERA-001-0001', 'model' => '吉宗(ピンク)'],
        ['no' => 2, 'name' => 'CAMERA-001-0002', 'model' => '番長'],
        ['no' => 3, 'name' => 'CAMERA-001-0003', 'model' => '吉宗(ピンク)'],
        ['no' => 4, 'name' => 'CAMERA-001-0004', 'model' => '番長'],
        ['no' => 5, 'name' => 'CAMERA-001-0005', 'model' => '吉宗(ピンク)'],
        ['no' => 6, 'name' => 'CAMERA-001-0006', 'model' => '番長'],
        ['no' => 7, 'name' => 'CAMERA-001-0007', 'model' => '吉宗(ピンク)'],
        ['no' => 8, 'name' => 'CAMERA-001-0008', 'model' => 'カイジ'],
        ['no' => 9, 'name' => 'CAMERA-001-0009', 'model' => 'カイジ'],
        ['no' => 10, 'name' => 'CAMERA-001-0010', 'model' => '吉宗(ピンク)'],
        ['no' => 11, 'name' => 'CAMERA-001-0011', 'model' => '吉宗(青)'],
        ['no' => 12, 'name' => 'CAMERA-001-0012', 'model' => '吉宗(青)'],
        ['no' => 13, 'name' => 'CAMERA-001-0013', 'model' => '南国物語'],
        ['no' => 14, 'name' => 'CAMERA-001-0014', 'model' => '南国物語'],
        ['no' => 15, 'name' => 'CAMERA-001-0015', 'model' => '南国物語'],
        ['no' => 16, 'name' => 'CAMERA-001-0016', 'model' => 'ミリオンゴッド'],
        ['no' => 17, 'name' => 'CAMERA-001-0017', 'model' => '無'],
        ['no' => 18, 'name' => 'CAMERA-001-0018', 'model' => 'ミリオンゴッド'],
        ['no' => 19, 'name' => 'CAMERA-001-0019', 'model' => '無'],
        ['no' => 20, 'name' => 'CAMERA-001-0020', 'model' => 'ミリオンゴッド'],
        ['no' => 21, 'name' => 'CAMERA-001-0021', 'model' => '無'],
        ['no' => 22, 'name' => 'CAMERA-001-0022', 'model' => '無'],
        ['no' => 23, 'name' => 'CAMERA-001-0023', 'model' => '北斗の拳', 'note' => 'リール無'],
        ['no' => 24, 'name' => 'CAMERA-001-0024', 'model' => '北斗の拳'],
        ['no' => 25, 'name' => 'CAMERA-001-0025', 'model' => '無'],
        ['no' => 26, 'name' => 'CAMERA-001-0026', 'model' => '北斗の拳'],
        ['no' => 27, 'name' => 'CAMERA-001-0027', 'model' => '無'],
        ['no' => 28, 'name' => 'CAMERA-001-0028', 'model' => '無'],
        ['no' => 29, 'name' => 'CAMERA-001-0029', 'model' => 'ジャグラー'],
        ['no' => 30, 'name' => 'CAMERA-001-0030', 'model' => 'ジャグラー'],
        ['no' => 31, 'name' => 'CAMERA-001-0031', 'model' => '無'],
        ['no' => 32, 'name' => 'CAMERA-001-0032', 'model' => '無'],
        ['no' => 33, 'name' => 'CAMERA-001-0033', 'model' => 'ジャグラー'],
        ['no' => 34, 'name' => 'CAMERA-001-0034', 'model' => '無'],
        ['no' => 35, 'name' => 'CAMERA-001-0035', 'model' => '無'],
        ['no' => 36, 'name' => 'CAMERA-001-0036', 'model' => '無'],
        ['no' => 37, 'name' => 'CAMERA-001-0037', 'model' => 'ファイヤードリフト'],
        ['no' => 38, 'name' => 'CAMERA-001-0038', 'model' => '鬼武者'],
        ['no' => 39, 'name' => 'CAMERA-001-0039', 'model' => '無'],
        ['no' => 40, 'name' => 'CAMERA-001-0040', 'model' => 'ビンゴ'],
        ['no' => 41, 'name' => 'CAMERA-001-0041', 'model' => '北斗の拳'],
        ['no' => 42, 'name' => 'CAMERA-001-0042', 'model' => '無'],
        ['no' => 43, 'name' => 'CAMERA-001-0043', 'model' => '北斗の拳'],
        ['no' => 44, 'name' => 'CAMERA-001-0044', 'model' => '無'],
        ['no' => 45, 'name' => 'CAMERA-001-0045', 'model' => '無'],
        ['no' => 46, 'name' => 'CAMERA-001-0046', 'model' => '無'],
        ['no' => 47, 'name' => 'CAMERA-001-0047', 'model' => '無'],
        ['no' => 48, 'name' => 'CAMERA-001-0048', 'model' => '無'],
        ['no' => 49, 'name' => 'CAMERA-001-0049', 'model' => '北斗の拳'],
        ['no' => 50, 'name' => 'CAMERA-001-0050', 'model' => '北斗の拳'],
        ['no' => 51, 'name' => 'CAMERA-001-0051', 'model' => '銭形'],
        ['no' => 52, 'name' => 'CAMERA-001-0052', 'model' => '無'],
        ['no' => 53, 'name' => 'CAMERA-001-0053', 'model' => '銭形'],
        ['no' => 54, 'name' => 'CAMERA-001-0054', 'model' => 'ファイヤードリフト'],
        ['no' => 55, 'name' => 'CAMERA-001-0055', 'model' => '鬼武者'],
        ['no' => 56, 'name' => 'CAMERA-001-0056', 'model' => '無'],
        ['no' => 57, 'name' => 'CAMERA-001-0057', 'model' => 'ビンゴ'],
        ['no' => 58, 'name' => 'CAMERA-001-0058', 'model' => '秘宝伝'],
        ['no' => 59, 'name' => 'CAMERA-001-0059', 'model' => '秘宝伝'],
        ['no' => 60, 'name' => 'CAMERA-001-0060', 'model' => '番長'],
        ['no' => 61, 'name' => 'CAMERA-001-0061', 'model' => '無'],
        ['no' => 62, 'name' => 'CAMERA-001-0062', 'model' => '無'],
        ['no' => 63, 'name' => 'CAMERA-001-0063', 'model' => '無'],
        ['no' => 64, 'name' => 'CAMERA-001-0064', 'model' => '島唄'],
        ['no' => 65, 'name' => 'CAMERA-001-0065', 'model' => '無'],
        ['no' => 66, 'name' => 'CAMERA-001-0066', 'model' => '無'],
        ['no' => 67, 'name' => 'CAMERA-001-0067', 'model' => '島唄'],
        ['no' => 68, 'name' => 'CAMERA-001-0068', 'model' => '秘宝伝'],
    ];

    // Step 1: 既存の機種を取得
    echo "=== Step 1: 既存機種を取得 ===\n";
    $existing_models = $db->getAll("SELECT model_no, model_name FROM mst_model WHERE del_flg = 0", PDO::FETCH_ASSOC);
    foreach ($existing_models as $m) {
        $model_mapping[$m['model_name']] = $m['model_no'];
        echo "  既存: {$m['model_name']} => model_no={$m['model_no']}\n";
    }

    // Step 2: 新しい機種を登録
    echo "\n=== Step 2: 新しい機種を登録 ===\n";
    $unique_models = [];
    foreach ($csv_data as $row) {
        if ($row['model'] !== '無' && !empty($row['model'])) {
            $unique_models[$row['model']] = true;
        }
    }

    $next_model_no = 100; // 新規機種は100番から
    $max_model = $db->getRow("SELECT MAX(model_no) as max_no FROM mst_model", PDO::FETCH_ASSOC);
    if ($max_model && $max_model['max_no'] >= 100) {
        $next_model_no = $max_model['max_no'] + 1;
    }

    foreach (array_keys($unique_models) as $model_name) {
        if (!isset($model_mapping[$model_name])) {
            // 新規登録（maker_no=1 はデフォルトメーカー）
            $sql = "INSERT INTO mst_model (model_no, model_name, model_cd, category, maker_no, del_flg, add_no, add_dt)
                    VALUES ($next_model_no, " . $db->quote($model_name) . ", " . $db->quote('SLOT-' . $next_model_no) . ", 2, 1, 0, 1, NOW())";
            $db->query($sql);
            $model_mapping[$model_name] = $next_model_no;
            echo "  新規登録: {$model_name} => model_no={$next_model_no}\n";
            $next_model_no++;
        }
    }

    // Step 3: マシンを登録/更新
    echo "\n=== Step 3: マシンを登録/更新 ===\n";
    $registered = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($csv_data as $row) {
        $machine_no = $row['no'];
        $model_name = $row['model'];

        // 「無」の場合は機種未設定（model_no = 0）
        $is_empty = ($model_name === '無' || empty($model_name));
        $model_no = $is_empty ? 0 : ($model_mapping[$model_name] ?? 1);
        $model_no_display = $is_empty ? '0（未設定）' : $model_no;

        $name = "MACHINE-" . str_pad($machine_no, 2, '0', STR_PAD_LEFT);
        $camera_no = $machine_no;
        $signaling_id = 'PEER' . str_pad($machine_no, 3, '0', STR_PAD_LEFT);
        $token = 'net8_m' . str_pad($machine_no, 3, '0', STR_PAD_LEFT) . '_' . bin2hex(random_bytes(16));

        // 既存チェック
        $exists = $db->getRow("SELECT machine_no, model_no FROM dat_machine WHERE machine_no = $machine_no", PDO::FETCH_ASSOC);

        if ($exists) {
            // 更新（機種のみ）
            $sql = "UPDATE dat_machine SET model_no = $model_no WHERE machine_no = $machine_no";
            $db->query($sql);
            if ($is_empty) {
                echo "  UPDATE: No.{$machine_no} => 機種未設定\n";
                $skipped++;
            } else {
                echo "  UPDATE: No.{$machine_no} => {$model_name} (model_no={$model_no})\n";
                $updated++;
            }
        } else {
            // 新規登録
            $sql = "INSERT INTO dat_machine (machine_no, name, model_no, camera_no, signaling_id, token, status, machine_status, del_flg, release_date, convert_no)
                    VALUES ($machine_no, " . $db->quote($name) . ", $model_no, $camera_no, " . $db->quote($signaling_id) . ", " . $db->quote($token) . ", 'offline', 0, 0, CURDATE(), 0)";
            $db->query($sql);
            if ($is_empty) {
                echo "  INSERT: No.{$machine_no} => 機種未設定（空きスロット）\n";
                $skipped++;
            } else {
                echo "  INSERT: No.{$machine_no} => {$model_name} (model_no={$model_no})\n";
                $registered++;
            }
        }
    }

    echo "\n=== 完了 ===\n";
    echo "機種設定済み（新規）: {$registered}台\n";
    echo "機種設定済み（更新）: {$updated}台\n";
    echo "機種未設定（空きスロット）: {$skipped}台\n";
    echo "</pre>";

    echo "<p><a href='machine_control_v2.php'>マシン管理画面へ</a></p>";

} catch (Exception $e) {
    echo "エラー: " . htmlspecialchars($e->getMessage());
}
?>
