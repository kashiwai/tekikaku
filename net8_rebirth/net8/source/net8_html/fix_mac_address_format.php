<?php
/**
 * MACアドレスフォーマット修正スクリプト
 *
 * Windows側のハイフン形式MACアドレスに対応
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 80) . "\n";
echo "  MACアドレスフォーマット修正\n";
echo "=" . str_repeat("=", 80) . "\n\n";

// Windows PC 1台目のMACアドレス
$mac_hyphen_1 = '34-a6-ef-35-73-73';  // Windows側が送信する形式
$mac_colon_1 = '34:a6:ef:35:73:73';   // DB登録済みの形式

// Windows PC 2台目のMACアドレス（既にDB登録済み）
$mac_hyphen_2 = 'E0-51-D8-16-13-3D';
$mac_colon_2 = 'E0:51:D8:16:13:3D';

// 1. 既存のカメラを確認
echo "📷 既存のカメラ確認:\n";
echo str_repeat("-", 80) . "\n";

$sql = "SELECT camera_no, camera_mac, camera_name
        FROM mst_camera
        WHERE camera_mac IN (
            '$mac_colon_1',
            '$mac_hyphen_1',
            '$mac_colon_2',
            '$mac_hyphen_2'
        )
        AND del_flg = 0";

$existing_cameras = $template->DB->getAll($sql);

foreach ($existing_cameras as $camera) {
    echo "カメラ{$camera['camera_no']}: {$camera['camera_mac']} ({$camera['camera_name']})\n";
}

echo "\n" . str_repeat("-", 80) . "\n\n";

// 2. ハイフン形式のMACアドレスがなければ追加
$actions = [];

// Windows PC 1台目
$sql = "SELECT camera_no FROM mst_camera WHERE camera_mac = '$mac_hyphen_1' AND del_flg = 0";
$camera1_hyphen = $template->DB->getRow($sql);

if (empty($camera1_hyphen)) {
    echo "⚠️  Windows PC 1台目のハイフン形式MACアドレスが未登録です\n";

    // コロン形式が存在するか確認
    $sql = "SELECT camera_no, camera_name FROM mst_camera WHERE camera_mac = '$mac_colon_1' AND del_flg = 0";
    $camera1_colon = $template->DB->getRow($sql);

    if (!empty($camera1_colon)) {
        echo "   既存のコロン形式（カメラ{$camera1_colon['camera_no']}）をハイフン形式に更新します\n";
        $actions[] = [
            'action' => 'update',
            'camera_no' => $camera1_colon['camera_no'],
            'old_mac' => $mac_colon_1,
            'new_mac' => $mac_hyphen_1,
            'camera_name' => $camera1_colon['camera_name']
        ];
    } else {
        echo "   新規カメラとして登録します\n";
        $actions[] = [
            'action' => 'insert',
            'mac' => $mac_hyphen_1,
            'name' => 'Windows_PC_1_Hokuto'
        ];
    }
} else {
    echo "✅ Windows PC 1台目のハイフン形式MACアドレスは登録済み（カメラ{$camera1_hyphen['camera_no']}）\n";
}

// Windows PC 2台目
$sql = "SELECT camera_no FROM mst_camera WHERE camera_mac = '$mac_hyphen_2' AND del_flg = 0";
$camera2_hyphen = $template->DB->getRow($sql);

if (empty($camera2_hyphen)) {
    echo "⚠️  Windows PC 2台目のハイフン形式MACアドレスが未登録です\n";

    // コロン形式が存在するか確認
    $sql = "SELECT camera_no, camera_name FROM mst_camera WHERE camera_mac = '$mac_colon_2' AND del_flg = 0";
    $camera2_colon = $template->DB->getRow($sql);

    if (!empty($camera2_colon)) {
        echo "   既存のカメラ{$camera2_colon['camera_no']}のMACアドレスをハイフン形式に更新します\n";
        $actions[] = [
            'action' => 'update',
            'camera_no' => $camera2_colon['camera_no'],
            'old_mac' => $mac_colon_2,
            'new_mac' => $mac_hyphen_2,
            'camera_name' => $camera2_colon['camera_name']
        ];
    } else {
        echo "   新規カメラとして登録します\n";
        $actions[] = [
            'action' => 'insert',
            'mac' => $mac_hyphen_2,
            'name' => 'Windows_PC_2_Zenigata'
        ];
    }
} else {
    echo "✅ Windows PC 2台目のハイフン形式MACアドレスは登録済み（カメラ{$camera2_hyphen['camera_no']}）\n";
}

echo "\n" . str_repeat("=", 80) . "\n";

// 3. 実行確認
if (empty($actions)) {
    echo "✅ すべてのMACアドレスが正しく登録されています。修正の必要はありません。\n";
} else {
    echo "🔧 以下の修正を実行します:\n";
    echo str_repeat("-", 80) . "\n";

    foreach ($actions as $idx => $action) {
        $num = $idx + 1;
        if ($action['action'] === 'update') {
            echo "{$num}. UPDATE: カメラ{$action['camera_no']}\n";
            echo "   {$action['old_mac']} → {$action['new_mac']}\n";
        } else {
            echo "{$num}. INSERT: 新規カメラ\n";
            echo "   MAC: {$action['mac']}, 名前: {$action['name']}\n";
        }
    }

    echo "\n実行しますか？ (このスクリプトは自動実行モードです)\n";
    echo str_repeat("-", 80) . "\n\n";

    // 実行
    $template->DB->beginTransaction();

    try {
        foreach ($actions as $action) {
            if ($action['action'] === 'update') {
                $sql = (new SqlString($template->DB))
                    ->update('mst_camera')
                    ->set()
                        ->value('camera_mac', $action['new_mac'], FD_STR)
                        ->value('upd_no', 1, FD_NUM)
                        ->value('upd_dt', date('Y-m-d H:i:s'), FD_DATE)
                    ->where()
                        ->and('camera_no =', $action['camera_no'], FD_NUM)
                    ->createSQL();

                $result = $template->DB->query($sql);

                if ($result) {
                    echo "✅ カメラ{$action['camera_no']} を更新しました\n";
                } else {
                    throw new Exception("カメラ{$action['camera_no']} の更新に失敗");
                }

            } else {
                $sql = (new SqlString($template->DB))
                    ->insert()
                    ->into('mst_camera')
                        ->value('camera_mac', $action['mac'], FD_STR)
                        ->value('camera_name', $action['name'], FD_STR)
                        ->value('add_no', 1, FD_NUM)
                        ->value('add_dt', date('Y-m-d H:i:s'), FD_DATE)
                    ->createSQL();

                $result = $template->DB->query($sql);

                if ($result) {
                    $new_camera_no = $template->DB->lastInsertId('camera_no');
                    echo "✅ 新規カメラ{$new_camera_no} を登録しました\n";
                } else {
                    throw new Exception("新規カメラの登録に失敗");
                }
            }
        }

        $template->DB->commit();
        echo "\n✅ 全ての修正が完了しました！\n";

    } catch (Exception $e) {
        $template->DB->rollback();
        echo "\n❌ エラーが発生しました: " . $e->getMessage() . "\n";
        echo "変更はロールバックされました。\n";
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "📋 次のステップ:\n";
echo str_repeat("=", 80) . "\n\n";

if (empty($actions) || isset($result)) {
    echo "1. Windows PC 1台目で slotserver.exe を再起動してください\n";
    echo "2. Windows PC 2台目で slotserver.exe を起動してください\n";
    echo "3. 接続が成功したか確認してください\n\n";
    echo "接続成功の確認方法:\n";
    echo "  - コンソールに 'Machine No: 1' などが表示される\n";
    echo "  - Chromeが自動的に開く\n";
    echo "  - エラーが表示されない\n";
}

echo "\n" . str_repeat("=", 80) . "\n";
?>
