<?php
/**
 * 全カメラとマシンの状態を完全検証
 *
 * 3台のWindows PC構成を明確にするための診断ツール
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  全カメラ・マシン状態検証（3台構成確認）\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. 全ての登録済みカメラを確認
echo "📷 登録済みカメラ一覧（del_flg=0のみ）:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT camera_no, camera_mac, camera_name, add_dt
        FROM mst_camera
        WHERE del_flg = 0
        ORDER BY camera_no";
$all_cameras = $template->DB->getAll($sql);

foreach ($all_cameras as $camera) {
    echo sprintf("カメラ%d: %s (%s) [登録日: %s]\n",
        $camera['camera_no'],
        $camera['camera_mac'],
        $camera['camera_name'] ?? '名前未設定',
        $camera['add_dt']
    );
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 2. 全てのアクティブなマシンを確認
echo "🎰 アクティブなマシン一覧:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.model_no,
    dm.signaling_id,
    mm.model_name,
    mm.category,
    mc.camera_mac,
    mc.camera_name,
    lm.assign_flg,
    lm.member_no
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
WHERE dm.del_flg = 0
ORDER BY dm.machine_no";

$machines = $template->DB->getAll($sql);

foreach ($machines as $machine) {
    $status = $machine['assign_flg'] == 9 ? '✅ 待機中' : '❌ 異常';
    $member = $machine['member_no'] == 0 ? '正常' : "異常(member_no={$machine['member_no']})";

    echo "\n【マシン {$machine['machine_no']}】\n";
    echo "  機種名: {$machine['model_name']}\n";
    echo "  カメラ番号: {$machine['camera_no']}\n";
    echo "  カメラMAC: {$machine['camera_mac']}\n";
    echo "  カメラ名: {$machine['camera_name']}\n";
    echo "  シグナリングID: {$machine['signaling_id']}\n";
    echo "  状態: {$status} (assign_flg={$machine['assign_flg']})\n";
    echo "  メンバー: {$member}\n";
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 3. 特定のMACアドレスが登録されているか確認
echo "🔍 問題のMACアドレス確認:\n";
echo str_repeat("-", 100) . "\n";

$problematic_mac = '34-a6-ef-35-73-73';
$sql = "SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_mac = '$problematic_mac'";
$found = $template->DB->getRow($sql);

if (empty($found)) {
    echo "❌ MAC '{$problematic_mac}' は mst_camera に登録されていません\n";
    echo "   このMACアドレスを持つWindows PCは接続できません\n\n";
} else {
    echo "✅ MAC '{$problematic_mac}' が見つかりました\n";
    echo "   カメラ番号: {$found['camera_no']}\n";
    echo "   カメラ名: {$found['camera_name']}\n";
    echo "   削除フラグ: {$found['del_flg']} " . ($found['del_flg'] == 0 ? '(有効)' : '(削除済み)') . "\n\n";
}

// 4. MACアドレスバリエーションチェック（コロン形式も確認）
$problematic_mac_colon = str_replace('-', ':', $problematic_mac);
$sql = "SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_mac = '$problematic_mac_colon'";
$found_colon = $template->DB->getRow($sql);

if (!empty($found_colon)) {
    echo "⚠️  コロン形式のMAC '{$problematic_mac_colon}' が見つかりました\n";
    echo "   カメラ番号: {$found_colon['camera_no']}\n";
    echo "   カメラ名: {$found_colon['camera_name']}\n";
    echo "   削除フラグ: {$found_colon['del_flg']}\n";
    echo "   ➡️ ハイフン形式に変更する必要があります\n\n";
}

echo str_repeat("-", 100) . "\n\n";

// 5. カメラ使用状況サマリー
echo "📊 カメラ使用状況サマリー:\n";
echo str_repeat("=", 100) . "\n\n";

$camera_usage = [];
foreach ($machines as $machine) {
    $camera_no = $machine['camera_no'];
    if ($camera_no) {
        if (!isset($camera_usage[$camera_no])) {
            $camera_usage[$camera_no] = [];
        }
        $camera_usage[$camera_no][] = [
            'machine_no' => $machine['machine_no'],
            'model_name' => $machine['model_name']
        ];
    }
}

foreach ($all_cameras as $camera) {
    $camera_no = $camera['camera_no'];

    if (isset($camera_usage[$camera_no])) {
        $machines_using = $camera_usage[$camera_no];

        if (count($machines_using) > 1) {
            echo "❌ カメラ{$camera_no} ({$camera['camera_mac']}): 重複使用！\n";
            foreach ($machines_using as $m) {
                echo "   - マシン{$m['machine_no']}: {$m['model_name']}\n";
            }
        } else {
            echo "✅ カメラ{$camera_no} ({$camera['camera_mac']}): マシン{$machines_using[0]['machine_no']} ({$machines_using[0]['model_name']})\n";
        }
    } else {
        echo "⚪ カメラ{$camera_no} ({$camera['camera_mac']}): 未使用\n";
    }
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "🎯 推奨される対応:\n";
echo str_repeat("=", 100) . "\n\n";

if (empty($found) && empty($found_colon)) {
    echo "Windows PC (MAC: {$problematic_mac}) を登録する必要があります。\n\n";
    echo "以下の選択肢があります:\n\n";
    echo "【オプション1】新規カメラとして登録\n";
    echo "  このMACアドレスを新しいカメラとして登録し、\n";
    echo "  既存のマシン（1, 2, 3）のいずれかに割り当てる\n\n";
    echo "【オプション2】既存マシンのMACを変更\n";
    echo "  既存のマシン1, 2, 3のいずれかのカメラMACを\n";
    echo "  このMACアドレスに変更する\n\n";
    echo "どのマシンがどのWindows PC（どのMAC）を使うべきか\n";
    echo "教えていただければ、適切に設定します。\n\n";

    echo "現在のWindows PC情報を教えてください:\n";
    echo "  1. 北斗の拳用PC のMACアドレス: ?\n";
    echo "  2. 銭形用PC のMACアドレス: ?\n";
    echo "  3. ミリオンゴッド用PC のMACアドレス: ?\n\n";
} elseif (!empty($found_colon) && empty($found)) {
    echo "コロン形式のMACをハイフン形式に修正します。\n";
    echo "修正後、再度接続をテストしてください。\n\n";
} else {
    echo "データベースは正常です。\n";
    echo "Windows PC側の slotserver.exe を確認してください。\n\n";
}

echo str_repeat("=", 100) . "\n";
?>
