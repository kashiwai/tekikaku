<?php
/**
 * 北斗の拳（マシン1）のカメラを変更
 *
 * カメラ10000023 (E0-51-D8-16-7D-E1) → カメラ10000021 (34-a6-ef-35-73-73)
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  北斗の拳（マシン1）カメラ変更\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. 現在の状態を確認
echo "📊 変更前の状態:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.model_no,
    mm.model_name,
    mc.camera_mac,
    mc.camera_name
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
WHERE dm.machine_no = 1";

$machine = $template->DB->getRow($sql);

if (empty($machine)) {
    echo "❌ エラー: マシン1が見つかりません\n";
    exit;
}

echo "マシン1（{$machine['model_name']}）\n";
echo "  現在のカメラ: {$machine['camera_no']}\n";
echo "  現在のMAC: {$machine['camera_mac']}\n";
echo "  カメラ名: {$machine['camera_name']}\n\n";

// 2. 新しいカメラ情報を確認
$new_camera_no = 10000021;
$sql = "SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_no = $new_camera_no";
$new_camera = $template->DB->getRow($sql);

if (empty($new_camera)) {
    echo "❌ エラー: カメラ{$new_camera_no}が見つかりません\n";
    exit;
}

if ($new_camera['del_flg'] != 0) {
    echo "❌ エラー: カメラ{$new_camera_no}は削除済みです\n";
    exit;
}

echo "変更先のカメラ:\n";
echo "  カメラ番号: {$new_camera['camera_no']}\n";
echo "  MAC: {$new_camera['camera_mac']}\n";
echo "  カメラ名: {$new_camera['camera_name']}\n\n";

echo str_repeat("-", 100) . "\n\n";

// 3. 変更を実行
echo "🔧 マシン1のカメラを変更します:\n";
echo str_repeat("-", 100) . "\n";
echo "マシン1（北斗の拳）\n";
echo "  カメラ{$machine['camera_no']} (MAC: {$machine['camera_mac']})\n";
echo "  ↓\n";
echo "  カメラ{$new_camera['camera_no']} (MAC: {$new_camera['camera_mac']})\n\n";

$template->DB->beginTransaction();

try {
    $sql = (new SqlString($template->DB))
        ->update('dat_machine')
        ->set()
            ->value('camera_no', $new_camera_no, FD_NUM)
            ->value('upd_no', 1, FD_NUM)
            ->value('upd_dt', date('Y-m-d H:i:s'), FD_DATE)
        ->where()
            ->and('machine_no =', 1, FD_NUM)
        ->createSQL();

    $result = $template->DB->query($sql);

    if ($result) {
        echo "✅ マシン1のカメラを変更しました\n";
        $template->DB->commit();
    } else {
        throw new Exception("カメラの変更に失敗しました");
    }

} catch (Exception $e) {
    $template->DB->rollback();
    echo "❌ エラー: " . $e->getMessage() . "\n";
    exit;
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 変更後の状態確認:\n";
echo str_repeat("=", 100) . "\n\n";

// 4. 変更後の状態を確認
$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    mm.model_name,
    mc.camera_mac,
    mc.camera_name,
    lm.assign_flg,
    lm.member_no
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
WHERE dm.machine_no = 1";

$machine_after = $template->DB->getRow($sql);

echo "マシン1（{$machine_after['model_name']}）\n";
echo "  カメラ番号: {$machine_after['camera_no']}\n";
echo "  カメラMAC: {$machine_after['camera_mac']}\n";
echo "  カメラ名: {$machine_after['camera_name']}\n";
echo "  assign_flg: {$machine_after['assign_flg']} " . ($machine_after['assign_flg'] == 9 ? '✅' : '❌') . "\n";
echo "  member_no: {$machine_after['member_no']} " . ($machine_after['member_no'] == 0 ? '✅' : '❌') . "\n\n";

// 5. 全マシンの状態を確認
echo str_repeat("-", 100) . "\n";
echo "全マシンの状態:\n";
echo str_repeat("-", 100) . "\n\n";

$sql = "SELECT
    dm.machine_no,
    mm.model_name,
    mc.camera_mac,
    lm.assign_flg,
    lm.member_no
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
WHERE dm.del_flg = 0
ORDER BY dm.machine_no";

$all_machines = $template->DB->getAll($sql);

foreach ($all_machines as $m) {
    $status = ($m['assign_flg'] == 9 && $m['member_no'] == 0) ? '✅' : '❌';
    echo "マシン{$m['machine_no']}: {$status} {$m['model_name']} (MAC: {$m['camera_mac']})\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "🚀 次のステップ:\n";
echo str_repeat("=", 100) . "\n\n";

echo "Windows PC (MAC: {$machine_after['camera_mac']}) で slotserver.exe を起動してください。\n\n";

echo "接続確認:\n";
echo "  1. slotserver.exe のコンソール出力を確認\n";
echo "     - MAC:{$machine_after['camera_mac']} が表示される\n";
echo "     - Machine No: 1 が表示される\n";
echo "     - status:ok が表示される\n\n";

echo "  2. プレイヤー画面でテスト:\n";
echo "     https://mgg-webservice-production.up.railway.app/play_v2/?NO=1\n\n";

echo "  3. 正常に映像が表示されることを確認\n\n";

echo str_repeat("=", 100) . "\n";
?>
