<?php
/*
 * list_members.php
 *
 * メンバー一覧とポイント状況を確認するAPI
 */

header('Content-Type: application/json; charset=utf-8');

// インクルード
require_once('../../_etc/require_files.php');

try {
    $DB = new NetDB();

    // メンバー一覧取得
    $sql = "SELECT member_no, mail, nickname, point, tester_flg, regist_dt
            FROM mst_member
            ORDER BY member_no DESC
            LIMIT 20";

    $stmt = $DB->prepare($sql);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'count' => count($members),
        'members' => array_map(function($m) {
            return [
                'member_no' => (int)$m['member_no'],
                'mail' => $m['mail'],
                'nickname' => $m['nickname'],
                'playpoint' => (int)$m['point'],
                'tester_flg' => $m['tester_flg'],
                'regist_dt' => $m['regist_dt']
            ];
        }, $members)
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
