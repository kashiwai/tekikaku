<?php
/**
 * MAC アドレスのカメラとマシン情報を確認
 */

// 設定ファイル読み込み
require_once(__DIR__ . '/02.ソースファイル/net8_html/_etc/setting.php');

try {
    $pdo = new PDO(DB_DSN_PDO, DB_USER, DB_PASSWORD, DB_OPTIONS);

    $mac = '34-a6-ef-35-73-73';

    echo "=== MAC Address: {$mac} ===\n\n";

    // mst_camera テーブル確認
    echo "--- mst_camera テーブル ---\n";
    $stmt = $pdo->prepare("
        SELECT camera_no, camera_mac, camera_name, del_flg
        FROM mst_camera
        WHERE camera_mac = :mac AND del_flg = 0
    ");
    $stmt->execute(['mac' => $mac]);
    $camera = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($camera) {
        print_r($camera);
        $camera_no = $camera['camera_no'];

        // dat_machine テーブル確認
        echo "\n--- dat_machine テーブル ---\n";
        $stmt = $pdo->prepare("
            SELECT machine_no, signaling_id, camera_no, model_no
            FROM dat_machine
            WHERE camera_no = :camera_no
        ");
        $stmt->execute(['camera_no' => $camera_no]);
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($machine) {
            print_r($machine);

            if (empty($machine['signaling_id']) || $machine['signaling_id'] === NULL) {
                echo "\n⚠️ signaling_id が設定されていません！\n";
                echo "\n修正SQLを生成します...\n\n";

                echo "--- 実行するSQL ---\n";
                $sql = "UPDATE dat_machine SET signaling_id = '1' WHERE machine_no = " . $machine['machine_no'] . ";\n";
                echo $sql;

                echo "\n上記SQLを実行しますか？ (このスクリプトは確認のみで実行しません)\n";
            } else {
                echo "\n✅ signaling_id = " . $machine['signaling_id'] . " (設定済み)\n";
            }
        } else {
            echo "❌ dat_machine テーブルにレコードが見つかりません\n";
            echo "camera_no = {$camera_no} のマシンが未登録です\n";
        }
    } else {
        echo "❌ mst_camera テーブルにMAC {$mac} が見つかりません\n";
        echo "まずカメラを登録してください\n";
    }

} catch (PDOException $e) {
    echo "エラー: " . $e->getMessage() . "\n";
}
?>
