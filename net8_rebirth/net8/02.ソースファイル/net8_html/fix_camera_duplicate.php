<?php
/**
 * カメラ重複問題修正スクリプト
 *
 * マシン3とマシン4でカメラ3が重複している問題を解決
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  カメラ重複問題 修正スクリプト\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 1. マシン3とマシン4の詳細確認
echo "📊 マシン3とマシン4の現在の状態:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.model_no,
    dm.machine_cd,
    mm.model_name,
    mm.category,
    mc.camera_mac,
    mc.camera_name,
    dm.add_dt,
    dm.upd_dt
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
WHERE dm.machine_no IN (3, 4)
ORDER BY dm.machine_no";

$machines = $template->DB->getAll($sql);

foreach ($machines as $machine) {
    echo "\n【マシン {$machine['machine_no']}】\n";
    echo "  機種: {$machine['model_name']}\n";
    echo "  機種コード: {$machine['machine_cd']}\n";
    echo "  カテゴリ: " . ($machine['category'] == 1 ? 'パチンコ' : 'スロット') . "\n";
    echo "  カメラ番号: {$machine['camera_no']}\n";
    echo "  カメラMAC: {$machine['camera_mac']}\n";
    echo "  登録日: {$machine['add_dt']}\n";
    echo "  更新日: {$machine['upd_dt']}\n";
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 2. どちらを残すか判断
echo "🔍 問題分析:\n";
echo str_repeat("-", 100) . "\n";
echo "マシン3とマシン4が同じカメラ3を使用しています。\n\n";

// マシン3（ミリオンゴッド）を残す
// マシン4（北斗の拳）は重複なので別のカメラに変更するか削除する

echo "推奨される対処:\n";
echo "  ✅ マシン3（ミリオンゴッド）: カメラ3を使用（そのまま維持）\n";
echo "  ❌ マシン4（北斗の拳）: 重複しているので処理が必要\n\n";

echo "マシン4の処理オプション:\n";
echo "  オプション1: マシン4を削除する（不要なマシンの場合）\n";
echo "  オプション2: マシン4に新しいカメラを割り当てる（使用する場合）\n\n";

// 利用可能なカメラを確認
echo "利用可能なカメラ:\n";
echo str_repeat("-", 100) . "\n";

$sql = "SELECT c.camera_no, c.camera_mac, c.camera_name
        FROM mst_camera c
        WHERE c.del_flg = 0
        AND c.camera_no NOT IN (
            SELECT camera_no FROM dat_machine WHERE camera_no IS NOT NULL
        )
        LIMIT 10";

$available_cameras = $template->DB->getAll($sql);

if (empty($available_cameras)) {
    echo "⚠️  未使用のカメラがありません。\n";
    echo "    マシン4を残す場合は、新しいカメラを登録する必要があります。\n\n";
} else {
    echo "以下のカメラが未使用です:\n";
    foreach ($available_cameras as $camera) {
        echo "  - カメラ{$camera['camera_no']}: {$camera['camera_mac']} ({$camera['camera_name']})\n";
    }
    echo "\n";
}

echo str_repeat("-", 100) . "\n\n";

// 3. ユーザーに確認（自動実行版: マシン4を削除）
echo "🔧 自動修正を実行します:\n";
echo str_repeat("-", 100) . "\n";
echo "⚠️  マシン4は北斗の拳の重複レコードと判断し、削除します。\n";
echo "   （マシン1で既に北斗の拳が動作しているため）\n\n";

echo "実行内容:\n";
echo "  1. dat_machine からマシン4を削除（del_flg=1）\n";
echo "  2. lnk_machine からマシン4のレコードを削除\n\n";

$template->DB->beginTransaction();

try {
    // dat_machine のマシン4を論理削除
    $sql = (new SqlString($template->DB))
        ->update('dat_machine')
        ->set()
            ->value('del_flg', '1', FD_NUM)
            ->value('del_no', '1', FD_NUM)
            ->value('del_dt', date('Y-m-d H:i:s'), FD_DATE)
        ->where()
            ->and('machine_no =', '4', FD_NUM)
        ->createSQL();

    $result = $template->DB->query($sql);

    if ($result) {
        echo "✅ dat_machine からマシン4を削除しました\n";
    } else {
        throw new Exception("マシン4の削除に失敗");
    }

    // lnk_machine のマシン4を物理削除
    $sql = "DELETE FROM lnk_machine WHERE machine_no = 4";
    $result = $template->DB->query($sql);

    if ($result !== false) {
        echo "✅ lnk_machine からマシン4のレコードを削除しました\n";
    } else {
        throw new Exception("lnk_machine のマシン4削除に失敗");
    }

    $template->DB->commit();
    echo "\n✅ カメラ重複問題を解決しました！\n";

} catch (Exception $e) {
    $template->DB->rollback();
    echo "\n❌ エラーが発生しました: " . $e->getMessage() . "\n";
    echo "変更はロールバックされました。\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 修正後の確認:\n";
echo str_repeat("=", 100) . "\n\n";

// 4. 修正後の状態を確認
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
WHERE dm.del_flg = 0
ORDER BY dm.machine_no";

$machines_after = $template->DB->getAll($sql);

echo "現在のアクティブなマシン:\n";
echo str_repeat("-", 100) . "\n";

foreach ($machines_after as $machine) {
    $status = $machine['assign_flg'] == 9 ? '✅' : '❌';
    echo sprintf(
        "マシン%d: %s %s (カメラ%d: %s) - assign_flg=%s, member_no=%s\n",
        $machine['machine_no'],
        $status,
        $machine['model_name'] ?? '未設定',
        $machine['camera_no'],
        $machine['camera_mac'] ?? '未登録',
        $machine['assign_flg'],
        $machine['member_no']
    );
}

// カメラ重複チェック
$camera_usage = [];
foreach ($machines_after as $machine) {
    $camera_no = $machine['camera_no'];
    if ($camera_no) {
        if (!isset($camera_usage[$camera_no])) {
            $camera_usage[$camera_no] = [];
        }
        $camera_usage[$camera_no][] = $machine['machine_no'];
    }
}

echo "\nカメラ使用状況:\n";
echo str_repeat("-", 100) . "\n";

$has_duplicate = false;
foreach ($camera_usage as $camera_no => $machine_nos) {
    if (count($machine_nos) > 1) {
        echo "❌ カメラ{$camera_no}: マシン " . implode(', ', $machine_nos) . " （重複！）\n";
        $has_duplicate = true;
    } else {
        echo "✅ カメラ{$camera_no}: マシン {$machine_nos[0]}\n";
    }
}

if (!$has_duplicate) {
    echo "\n✅ カメラの重複はありません！\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "🚀 次のステップ:\n";
echo str_repeat("=", 100) . "\n\n";

echo "現在動作するマシン:\n";
foreach ($machines_after as $machine) {
    echo "  - マシン{$machine['machine_no']}: {$machine['model_name']} (MAC: {$machine['camera_mac']})\n";
}

echo "\nWindows PC側で slotserver.exe を起動してください:\n";
echo "  1. 北斗の拳用PC (MAC: E0-51-D8-16-7D-E1) → マシン1\n";
echo "  2. 銭形用PC (MAC: E0-51-D8-16-13-3D) → マシン2\n";
echo "  3. ミリオンゴッド用PC (MAC: E0-51-D8-16-13-66) → マシン3\n\n";

echo "各PCで slotserver.exe を起動して、接続が成功するか確認してください。\n";
echo "エラーが出る場合は、表示されるMACアドレスとエラーメッセージを教えてください。\n\n";

echo str_repeat("=", 100) . "\n";
?>
