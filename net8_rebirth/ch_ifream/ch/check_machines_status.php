<?php
/**
 * マシン登録状況確認スクリプト
 *
 * 北斗の拳・銭形の2台のマシン登録状況を確認
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 80) . "\n";
echo "  NET8 マシン登録状況確認\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// 1. 全マシン一覧
echo "📊 登録済みマシン一覧:\n";
echo str_repeat("-", 80) . "\n";

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.signaling_id,
    dm.model_no,
    mm.model_name,
    mm.category,
    mc.camera_mac,
    mc.camera_name,
    lm.assign_flg
FROM dat_machine dm
LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
ORDER BY dm.machine_no";

$machines = $template->DB->getAll($sql);

if (empty($machines)) {
    echo "⚠️  マシンが1台も登録されていません！\n\n";
} else {
    foreach ($machines as $machine) {
        $category = $machine['category'] == 1 ? 'パチンコ' : 'スロット';
        $status = $machine['assign_flg'] == 9 ? '✅ カメラ待機中' : '❌ 状態不明';

        echo sprintf(
            "マシン%d: %s [%s] - カメラ%d (MAC:%s) %s\n",
            $machine['machine_no'],
            $machine['model_name'] ?: '未設定',
            $category,
            $machine['camera_no'] ?: 0,
            $machine['camera_mac'] ?: '未登録',
            $status
        );
    }
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// 2. 北斗の拳を検索
echo "🔍 北斗の拳を検索中...\n";
$sql = "SELECT model_no, model_name, category
        FROM mst_model
        WHERE model_name LIKE '%北斗%'
        OR model_name LIKE '%hokuto%'
        LIMIT 5";
$hokuto = $template->DB->getAll($sql);

if (empty($hokuto)) {
    echo "❌ 北斗の拳が登録されていません\n";
    echo "   → 管理画面から機種を登録してください\n\n";
} else {
    echo "✅ 見つかりました:\n";
    foreach ($hokuto as $model) {
        $assigned = false;
        foreach ($machines as $machine) {
            if ($machine['model_no'] == $model['model_no']) {
                $assigned = true;
                echo sprintf("   - %s (model_no: %d) → マシン%d に割り当て済み\n",
                    $model['model_name'],
                    $model['model_no'],
                    $machine['machine_no']
                );
            }
        }
        if (!$assigned) {
            echo sprintf("   - %s (model_no: %d) → ⚠️  マシンに未割り当て\n",
                $model['model_name'],
                $model['model_no']
            );
        }
    }
    echo "\n";
}

// 3. 銭形を検索
echo "🔍 銭形を検索中...\n";
$sql = "SELECT model_no, model_name, category
        FROM mst_model
        WHERE model_name LIKE '%銭形%'
        OR model_name LIKE '%zenigata%'
        LIMIT 5";
$zenigata = $template->DB->getAll($sql);

if (empty($zenigata)) {
    echo "❌ 銭形が登録されていません\n";
    echo "   → 管理画面から機種を登録してください\n\n";
} else {
    echo "✅ 見つかりました:\n";
    foreach ($zenigata as $model) {
        $assigned = false;
        foreach ($machines as $machine) {
            if ($machine['model_no'] == $model['model_no']) {
                $assigned = true;
                echo sprintf("   - %s (model_no: %d) → マシン%d に割り当て済み\n",
                    $model['model_name'],
                    $model['model_no'],
                    $machine['machine_no']
                );
            }
        }
        if (!$assigned) {
            echo sprintf("   - %s (model_no: %d) → ⚠️  マシンに未割り当て\n",
                $model['model_name'],
                $model['model_no']
            );
        }
    }
    echo "\n";
}

// 4. カメラ登録状況
echo str_repeat("-", 80) . "\n";
echo "📷 カメラ登録状況:\n";
echo str_repeat("-", 80) . "\n";

$sql = "SELECT camera_no, camera_mac, camera_name
        FROM mst_camera
        WHERE del_flg = 0
        ORDER BY camera_no";
$cameras = $template->DB->getAll($sql);

if (empty($cameras)) {
    echo "⚠️  カメラが1台も登録されていません！\n\n";
} else {
    foreach ($cameras as $camera) {
        echo sprintf(
            "カメラ%d: MAC=%s, 名前=%s\n",
            $camera['camera_no'],
            $camera['camera_mac'],
            $camera['camera_name']
        );
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📋 推奨アクション:\n";
echo str_repeat("=", 80) . "\n\n";

// 推奨アクション
$recommendations = [];

if (empty($machines)) {
    $recommendations[] = "1. マシンを登録してください:";
    $recommendations[] = "   → 管理画面: https://mgg-webservice-production.up.railway.app/xxxadmin/";
    $recommendations[] = "   → 「マシン管理」→「新規登録」";
}

if (count($machines) < 2) {
    $recommendations[] = "2. 北斗の拳と銭形の2台を登録してください";
}

if (empty($hokuto)) {
    $recommendations[] = "3. 北斗の拳を機種マスタに登録してください";
    $recommendations[] = "   → 「機種管理」→「新規登録」";
}

if (empty($zenigata)) {
    $recommendations[] = "4. 銭形を機種マスタに登録してください";
    $recommendations[] = "   → 「機種管理」→「新規登録」";
}

if (empty($cameras) || count($cameras) < 2) {
    $recommendations[] = "5. Windows PC 2台のカメラを登録してください:";
    $recommendations[] = "   → Windows側で slotserver.exe を起動";
    $recommendations[] = "   → 初回起動時に自動登録されます";
}

if (empty($recommendations)) {
    echo "✅ 全ての設定が完了しています！\n";
    echo "\n次のステップ:\n";
    echo "1. Windows PC側で slotserver.exe を起動\n";
    echo "2. 診断ツールで接続確認:\n";
    echo "   https://mgg-webservice-production.up.railway.app/data/debug_player.php?NO=1\n";
    echo "   https://mgg-webservice-production.up.railway.app/data/debug_player.php?NO=2\n";
} else {
    foreach ($recommendations as $rec) {
        echo $rec . "\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";

// 5. Windows側設定ファイル生成
if (!empty($cameras)) {
    echo "\n🔧 Windows側設定ファイル (slotserver_railway.ini):\n";
    echo str_repeat("-", 80) . "\n";

    foreach ($cameras as $idx => $camera) {
        $camera_num = $idx + 1;
        echo "\n■ Windows PC {$camera_num}台目用:\n\n";
        echo "[License]\n";
        echo "id = IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=\n";
        echo "cd = 6cce6f56edba0d5fc2b57e1f7d5e666f47b789fba27ae0a6fcef15c9cf49527c\n";
        echo "domain = mgg-webservice-production.up.railway.app\n";
        echo "\n[PatchServer]\n";
        echo "filesurl =\n";
        echo "url =\n";
        echo "\n[API]\n";
        echo "url = https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=\n";
        echo "\n[Chrome]\n";
        echo "url = https://mgg-webservice-production.up.railway.app/server_v2/\n";
        echo "\n[Monitor]\n";
        echo "url = wss://mgg-webservice-production.up.railway.app/ws\n";
        echo "\n[Credit]\n";
        echo "playmin = 3\n";
        echo "\n" . str_repeat("-", 80) . "\n";
    }

    echo "\n💾 このiniファイルを Windows PC の C:\\serverset\\slotserver.ini として保存してください\n\n";
}

?>
