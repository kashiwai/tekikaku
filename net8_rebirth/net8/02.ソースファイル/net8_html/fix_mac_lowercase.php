<?php
/**
 * MACアドレス小文字対応
 *
 * slotserver.pyが小文字でMACアドレスを送信する場合に備えて
 * 小文字版のMACアドレスをmst_cameraに登録
 */

require_once('_etc/require_files.php');

$template = new TemplateUser(false);

echo "=" . str_repeat("=", 100) . "\n";
echo "  MACアドレス小文字対応\n";
echo "=" . str_repeat("=", 100) . "\n\n";

// 現在のMAC（大文字）
$mac_upper = 'E0-51-D8-16-13-66';
$mac_lower = 'e0-51-d8-16-13-66';

echo "📊 現在の状態確認:\n";
echo str_repeat("-", 100) . "\n";

// 大文字版を確認
$sql = "SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_mac = '$mac_upper'";
$camera_upper = $template->DB->getRow($sql);

if (!empty($camera_upper)) {
    echo "✅ 大文字版: カメラ{$camera_upper['camera_no']} (MAC: {$camera_upper['camera_mac']})\n";
} else {
    echo "❌ 大文字版が見つかりません\n";
}

// 小文字版を確認
$sql = "SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_mac = '$mac_lower'";
$camera_lower = $template->DB->getRow($sql);

if (!empty($camera_lower)) {
    echo "✅ 小文字版: カメラ{$camera_lower['camera_no']} (MAC: {$camera_lower['camera_mac']})\n";
} else {
    echo "❌ 小文字版が見つかりません\n";
}

echo "\n" . str_repeat("-", 100) . "\n\n";

// 修正実行
if (empty($camera_lower)) {
    echo "🔧 小文字版MACアドレスを登録します:\n";
    echo str_repeat("-", 100) . "\n";

    $template->DB->beginTransaction();

    try {
        $sql = (new SqlString($template->DB))
            ->insert()
            ->into('mst_camera')
                ->value('camera_mac', $mac_lower, FD_STR)
                ->value('camera_name', 'camera_3_lowercase', FD_STR)
                ->value('add_no', 1, FD_NUM)
                ->value('add_dt', date('Y-m-d H:i:s'), FD_DATE)
            ->createSQL();

        $result = $template->DB->query($sql);

        if ($result) {
            $new_camera_no = $template->DB->lastInsertId('camera_no');
            echo "✅ 小文字版MACアドレスを登録しました (カメラ{$new_camera_no})\n";

            $template->DB->commit();
        } else {
            throw new Exception("小文字版MACアドレスの登録に失敗");
        }

    } catch (Exception $e) {
        $template->DB->rollback();
        echo "❌ エラー: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ 小文字版MACアドレスは既に登録済みです\n";
}

echo "\n" . str_repeat("=", 100) . "\n";
echo "📋 次のステップ:\n";
echo str_repeat("=", 100) . "\n\n";

echo "slotserver.py の設定ファイルまたはコードを確認してください:\n\n";
echo "【確認ポイント1】Base URLが正しいか\n";
echo "  正しい: https://mgg-webservice-production.up.railway.app/data/server_v2/\n";
echo "  間違い: http://mgg-webservice-production.up.railway.app/slot/\n\n";

echo "【確認ポイント2】アクセスしているフルURL\n";
echo "  正しい: https://mgg-webservice-production.up.railway.app/data/server_v2/?MAC=e0-51-d8-16-13-66\n\n";

echo "【確認ポイント3】HTTPSでアクセスしているか\n";
echo "  現在: Port 80 (HTTP) ← これが404エラーの原因\n";
echo "  正しい: Port 443 (HTTPS)\n\n";

echo "slotserver.py のコードまたは設定ファイル (config.ini など) を見せていただければ、\n";
echo "正確な修正方法をお伝えできます。\n\n";

echo str_repeat("=", 100) . "\n";
?>
