<?php
/**
 * マシン番号1のデバッグ情報確認
 */

header('Content-Type: application/json; charset=utf-8');

require_once('_etc/require_files.php');

try {
    $DB = new NetDB();

    // マシン番号1の情報取得
    $sql = "SELECT * FROM lnk_machine WHERE machine_no = 1";
    $stmt = $DB->query($sql);
    $lnk_data = $stmt->fetch(PDO::FETCH_ASSOC);

    // member_noがあれば、メンバー情報も取得
    $member_info = null;
    if ($lnk_data && !empty($lnk_data['member_no'])) {
        $sql = "SELECT member_no, mail, nickname, tester_flg FROM mst_member WHERE member_no = ?";
        $stmt = $DB->prepare($sql);
        $stmt->execute([$lnk_data['member_no']]);
        $member_info = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'status' => 'success',
        'machine_no' => 1,
        'lnk_machine' => $lnk_data,
        'member_info' => $member_info,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
