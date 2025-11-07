<?php
/*
 * charge_playpoint.php
 *
 * ゲームプレイポイントをチャージするAPI
 */

header('Content-Type: application/json; charset=utf-8');

// インクルード
require_once('../../_etc/require_files.php');

try {
    $DB = new NetDB();

    // パラメータ取得
    $memberNo = isset($_GET['member_no']) ? (int)$_GET['member_no'] : 0;
    $amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 1000;

    if ($memberNo == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'member_no パラメータが必要です'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 現在のポイント確認
    $sql = "SELECT member_no, mail, playpoint FROM mst_member WHERE member_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$memberNo]);
    $before = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$before) {
        echo json_encode([
            'status' => 'error',
            'message' => 'ユーザーが見つかりません'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // ポイント追加
    $sql = "UPDATE mst_member SET playpoint = playpoint + ? WHERE member_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$amount, $memberNo]);

    // 更新後の確認
    $sql = "SELECT member_no, mail, playpoint FROM mst_member WHERE member_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$memberNo]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => "{$amount} プレイポイントをチャージしました",
        'before' => [
            'member_no' => $before['member_no'],
            'mail' => $before['mail'],
            'playpoint' => (int)$before['playpoint']
        ],
        'after' => [
            'member_no' => $after['member_no'],
            'mail' => $after['mail'],
            'playpoint' => (int)$after['playpoint']
        ],
        'charged' => $amount
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
