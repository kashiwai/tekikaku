<?php
/*
 * reset_machine_mode.php
 *
 * Machine を1:1プレイモードに戻すAPI
 */

header('Content-Type: application/json; charset=utf-8');

// インクルード
require_once('../../_etc/require_files.php');

try {
    $DB = new NetDB();

    // パラメータ取得
    $machineNo = isset($_GET['machine_no']) ? (int)$_GET['machine_no'] : 1;

    // 現在の状態確認
    $sql = "SELECT machine_no, member_no, assign_flg, exit_flg
            FROM lnk_machine
            WHERE machine_no = ?";

    $stmt = $DB->prepare($sql);
    $stmt->execute([$machineNo]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1:1プレイモードに変更
    $sql = "UPDATE lnk_machine
            SET assign_flg = 0,
                member_no = 0,
                exit_flg = 0,
                onetime_id = NULL,
                start_dt = NULL
            WHERE machine_no = ?";

    $stmt = $DB->prepare($sql);
    $stmt->execute([$machineNo]);

    // 変更後の状態確認
    $sql = "SELECT machine_no, member_no, assign_flg, exit_flg
            FROM lnk_machine
            WHERE machine_no = ?";

    $stmt = $DB->prepare($sql);
    $stmt->execute([$machineNo]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => "Machine {$machineNo} を1:1プレイモードに変更しました",
        'before' => $before,
        'after' => $after
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
