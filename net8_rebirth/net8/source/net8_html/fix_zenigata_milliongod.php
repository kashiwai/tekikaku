<?php
/**
 * 銭形・ミリオンゴッド修正スクリプト
 *
 * マシン2（銭形）とマシン3（ミリオンゴッド）を動作可能にする
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  銭形・ミリオンゴッド 修正スクリプト\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. 現在の状態を確認
echo "📊 現在の状態:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.signaling_id,
    mm.model_name,
    mm.category,
    mc.camera_mac,
    mc.camera_name,
    lm.assign_flg,
    lm.member_no,
    lm.start_dt
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
WHERE dm.machine_no IN (1, 2, 3, 4)
ORDER BY dm.machine_no";

$machines = $template->DB->getAll($sql);

foreach ($machines as $machine) {
    $status = $machine['assign_flg'] == 9 ? '✅ カメラ待機中' : '❌ 問題あり (assign_flg=' . $machine['assign_flg'] . ')';
    $member = $machine['member_no'] == 0 ? '正常' : "⚠️ member_no={$machine['member_no']}";

    echo sprintf(
        "マシン%d: %s\n  カメラ%d (MAC:%s)\n  状態: %s, メンバー: %s\n\n",
        $machine['machine_no'],
        $machine['model_name'] ?? '未設定',
        $machine['camera_no'] ?? 0,
        $machine['camera_mac'] ?? '未登録',
        $status,
        $member
    );
}

echo str_repeat("-", 100) . "\n\n";

// 2. 問題を特定
echo "🔍 問題分析:\n";
echo str_repeat("-", 100) . "\n";

$issues = [];
$fixes = [];

foreach ($machines as $machine) {
    $machine_no = $machine['machine_no'];
    $model_name = $machine['model_name'] ?? '不明';

    // 問題1: assign_flgが9でない
    if ($machine['assign_flg'] != '9') {
        $issues[] = "マシン{$machine_no}（{$model_name}）: assign_flg が {$machine['assign_flg']}";
        $fixes[] = [
            'type' => 'update_assign_flg',
            'machine_no' => $machine_no,
            'model_name' => $model_name
        ];
    }

    // 問題2: member_noが0でない
    if ($machine['member_no'] != '0') {
        $issues[] = "マシン{$machine_no}（{$model_name}）: member_no が {$machine['member_no']}（本来は0）";
        if (!in_array($machine_no, array_column($fixes, 'machine_no'))) {
            $fixes[] = [
                'type' => 'reset_member',
                'machine_no' => $machine_no,
                'model_name' => $model_name
            ];
        }
    }
}

// カメラ重複チェック
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

foreach ($camera_usage as $camera_no => $machines_using) {
    if (count($machines_using) > 1) {
        $machine_list = implode(', ', array_map(function($m) {
            return "マシン{$m['machine_no']}（{$m['model_name']}）";
        }, $machines_using));

        $issues[] = "カメラ{$camera_no} が複数のマシンで使用されています: {$machine_list}";
        echo "⚠️ カメラ重複: カメラ{$camera_no} → {$machine_list}\n";
    }
}

if (empty($issues)) {
    echo "✅ 問題は見つかりませんでした。\n";
} else {
    foreach ($issues as $issue) {
        echo "❌ {$issue}\n";
    }
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 3. 修正実行
if (!empty($fixes)) {
    echo "🔧 以下の修正を実行します:\n";
    echo str_repeat("-", 100) . "\n";

    foreach ($fixes as $idx => $fix) {
        $num = $idx + 1;
        echo "{$num}. マシン{$fix['machine_no']}（{$fix['model_name']}）: ";

        if ($fix['type'] === 'update_assign_flg') {
            echo "assign_flg を 9 に設定、member_noを0にリセット\n";
        } else {
            echo "member_no を 0 にリセット\n";
        }
    }

    echo "\n実行中...\n";
    echo str_repeat("-", 100) . "\n\n";

    $template->DB->beginTransaction();

    try {
        foreach ($fixes as $fix) {
            $machine_no = $fix['machine_no'];

            $sql = (new SqlString($template->DB))
                ->update('lnk_machine')
                ->set()
                    ->value('assign_flg', '9', FD_NUM)
                    ->value('member_no', '0', FD_NUM)
                    ->value('onetime_id', '', FD_STR)
                    ->value('start_dt', '', FD_DATE)
                ->where()
                    ->and('machine_no =', $machine_no, FD_NUM)
                ->createSQL();

            $result = $template->DB->query($sql);

            if ($result) {
                echo "✅ マシン{$machine_no}（{$fix['model_name']}）を修正しました\n";
            } else {
                throw new Exception("マシン{$machine_no} の修正に失敗");
            }
        }

        $template->DB->commit();
        echo "\n✅ 全ての修正が完了しました！\n";

    } catch (Exception $e) {
        $template->DB->rollback();
        echo "\n❌ エラーが発生しました: " . $e->getMessage() . "\n";
        echo "変更はロールバックされました。\n";
    }

} else {
    echo "修正の必要はありません。\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 修正後の状態:\n";
echo str_repeat("=", 100) . "\n\n";

// 4. 修正後の確認
$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    mm.model_name,
    mc.camera_mac,
    lm.assign_flg,
    lm.member_no
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
WHERE dm.machine_no IN (1, 2, 3, 4)
ORDER BY dm.machine_no";

$machines_after = $template->DB->getAll($sql);

foreach ($machines_after as $machine) {
    $status = $machine['assign_flg'] == 9 ? '✅' : '❌';
    $member = $machine['member_no'] == 0 ? '✅' : '❌';

    echo sprintf(
        "マシン%d: %s %s assign_flg=%s, member_no=%s, カメラ=%s (MAC:%s)\n",
        $machine['machine_no'],
        $status,
        $machine['model_name'] ?? '未設定',
        $machine['assign_flg'],
        $machine['member_no'],
        $machine['camera_no'],
        $machine['camera_mac'] ?? '未登録'
    );
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "🚀 次のステップ:\n";
echo str_repeat("=", 100) . "\n\n";

// Windows側のMACアドレス確認
echo "Windows PC側で以下のMACアドレスを確認してください:\n\n";

foreach ($machines_after as $machine) {
    if ($machine['machine_no'] == 2) {
        echo "【銭形用PC】\n";
        echo "  期待されるMAC: {$machine['camera_mac']}\n";
        echo "  Windows側で確認: ipconfig /all で MACアドレスを確認\n";
        echo "  slotserver.exeを起動してMACが一致するか確認してください\n\n";
    }
    if ($machine['machine_no'] == 3) {
        echo "【ミリオンゴッド用PC】\n";
        echo "  期待されるMAC: {$machine['camera_mac']}\n";
        echo "  Windows側で確認: ipconfig /all で MACアドレスを確認\n";
        echo "  slotserver.exeを起動してMACが一致するか確認してください\n\n";
    }
}

echo "Windows PCでslotserver.exeを起動した際に表示されるエラーを教えてください。\n";
echo "特に以下の情報が重要です:\n";
echo "  1. MAC:XX-XX-XX-XX-XX-XX （どのMACアドレスが表示されるか）\n";
echo "  2. エラーメッセージの内容\n";
echo "  3. status:ng または status:ok\n\n";

echo str_repeat("=", 100) . "\n";
?>
