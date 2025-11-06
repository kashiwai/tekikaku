<?php
/**
 * MACHINE-03のカメラ登録状況を確認
 */

require_once('_etc/setting_base.php');
require_once('_etc/setting.php');
require_once('_lib/smartDB.php');

try {
    $DB = new SmartDB(DB_DSN);

    echo "MACHINE-03（機種No.3）のカメラ登録状況を確認します...\n\n";

    // machine_no=3のカメラ情報を取得
    $sql = "SELECT
        machine_no, mac_address, ip_address, peer_id, license_id,
        add_dt, upd_dt, del_flg
    FROM mst_cameralist
    WHERE machine_no = 3
    ORDER BY machine_no";

    $result = $DB->query($sql);
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);

    if (count($rows) > 0) {
        echo "✅ MACHINE-03のカメラが登録されています：\n\n";
        foreach ($rows as $row) {
            echo "【Machine No.{$row['machine_no']}】\n";
            echo "  MAC Address: {$row['mac_address']}\n";
            echo "  IP Address: {$row['ip_address']}\n";
            echo "  Peer ID: {$row['peer_id']}\n";
            echo "  License ID: {$row['license_id']}\n";
            echo "  削除フラグ: {$row['del_flg']}\n";
            echo "  登録日時: {$row['add_dt']}\n";
            echo "  更新日時: {$row['upd_dt']}\n\n";
        }
    } else {
        echo "❌ MACHINE-03のカメラは登録されていません。\n";
        echo "\n登録が必要です。\n";
    }

    // 全カメラ一覧も表示
    echo "\n=== 全カメラ一覧 ===\n";
    $sql_all = "SELECT machine_no, mac_address, peer_id, license_id, del_flg FROM mst_cameralist ORDER BY machine_no";
    $result_all = $DB->query($sql_all);
    $all_rows = $result_all->fetchAll(PDO::FETCH_ASSOC);

    foreach ($all_rows as $row) {
        $status = $row['del_flg'] == 0 ? '有効' : '削除済';
        echo "Machine {$row['machine_no']}: MAC: {$row['mac_address']}, Peer: {$row['peer_id']}, License: {$row['license_id']}, Status: {$status}\n";
    }

} catch (Exception $e) {
    echo "❌ エラー: " . $e->getMessage() . "\n";
}
?>
