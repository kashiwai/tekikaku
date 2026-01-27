<?php
/**
 * マシン1のMACアドレスを e0:51:d8:16:7d:e1 に更新
 * 実行URL: https://your-domain.com/data/api/update_machine1_mac.php
 */

require_once('../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>マシン1 MACアドレス更新</h1>";
echo "<pre>";

try {
    $DB = new NetDB();

    // 更新前の状態確認
    echo "【更新前の状態】\n";
    $sql = "SELECT
        dm.machine_no,
        dm.mac_address AS dat_machine_mac,
        dm.camera_no,
        mc.camera_mac AS mst_camera_mac,
        mc.camera_name,
        mcl.mac_address AS cameralist_mac,
        LEFT(mcl.license_id, 30) AS license_id_preview
    FROM dat_machine dm
    LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
    LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
    WHERE dm.machine_no = 1";

    $before = $DB->getRow($sql);
    print_r($before);

    // トランザクション開始
    $DB->autoCommit(false);

    // ❶ dat_machine.mac_address を更新
    echo "\n【ステップ1】 dat_machine.mac_address を更新\n";
    $sql = "UPDATE dat_machine SET mac_address = 'e0-51-d8-16-7d-e1' WHERE machine_no = 1";
    $DB->query($sql);
    echo "✅ dat_machine 更新完了\n";

    // camera_noを取得
    $sql = "SELECT camera_no FROM dat_machine WHERE machine_no = 1";
    $row = $DB->getRow($sql);
    $camera_no = $row['camera_no'];
    echo "camera_no: $camera_no\n";

    if ($camera_no) {
        // ❷ mst_camera.camera_mac を更新
        echo "\n【ステップ2】 mst_camera.camera_mac を更新\n";
        $sql = "UPDATE mst_camera SET camera_mac = 'e0-51-d8-16-7d-e1' WHERE camera_no = $camera_no";
        $DB->query($sql);
        echo "✅ mst_camera 更新完了\n";

        // ❸ mst_cameralist を更新（既存レコードがある場合のみ）
        echo "\n【ステップ3】 mst_cameralist を更新\n";

        // 既存の古いMACアドレスのレコードを削除マーク
        $sql = "UPDATE mst_cameralist
                SET del_flg = 1, del_dt = NOW()
                WHERE camera_no = $camera_no
                AND mac_address != 'e0-51-d8-16-7d-e1'";
        $DB->query($sql);
        echo "✅ 古いMACアドレスのレコードを削除マーク\n";

        // 新しいMACアドレスのレコードが既に存在するか確認
        $sql = "SELECT COUNT(*) as cnt FROM mst_cameralist WHERE mac_address = 'e0-51-d8-16-7d-e1'";
        $count_row = $DB->getRow($sql);

        if ($count_row['cnt'] > 0) {
            // 既存レコードを更新
            $sql = "UPDATE mst_cameralist
                    SET camera_no = $camera_no,
                        del_flg = 0,
                        upd_dt = NOW()
                    WHERE mac_address = 'e0-51-d8-16-7d-e1'";
            $DB->query($sql);
            echo "✅ mst_cameralist 更新完了（既存レコード）\n";
        } else {
            echo "⚠️ mst_cameralistに新規レコードなし（NET8License.pyで登録が必要）\n";
        }
    }

    // コミット
    $DB->autoCommit(true);
    echo "\n✅ トランザクションコミット完了\n";

    // 更新後の状態確認
    echo "\n【更新後の状態】\n";
    $sql = "SELECT
        dm.machine_no,
        dm.mac_address AS dat_machine_mac,
        dm.camera_no,
        mc.camera_mac AS mst_camera_mac,
        mc.camera_name,
        mcl.mac_address AS cameralist_mac,
        LEFT(mcl.license_id, 30) AS license_id_preview,
        mcl.state,
        mcl.del_flg
    FROM dat_machine dm
    LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
    LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
    WHERE dm.machine_no = 1";

    $after = $DB->getRow($sql);
    print_r($after);

    // mst_cameralistの確認
    echo "\n【mst_cameralist確認】\n";
    $sql = "SELECT
        mac_address,
        camera_no,
        LEFT(license_id, 30) AS license_id_preview,
        state,
        del_flg,
        add_dt
    FROM mst_cameralist
    WHERE mac_address = 'e0-51-d8-16-7d-e1'";

    $cameralist = $DB->getRow($sql);
    print_r($cameralist);

    echo "\n\n✅ 全ての更新が完了しました！\n";

} catch (Exception $e) {
    if (isset($DB)) {
        $DB->autoCommit(true); // ロールバック
    }
    echo "\n❌ エラー: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
