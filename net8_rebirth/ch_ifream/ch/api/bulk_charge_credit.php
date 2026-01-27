<?php
/*
 * bulk_charge_credit.php
 * 一括クレジットチャージAPI
 *
 * 使い方: /data/api/bulk_charge_credit.php?member_no=4&credit=3000
 */

header('Content-Type: application/json; charset=utf-8');

// インクルード
require_once('../../_etc/require_files.php');

try {
    $DB = new NetDB();

    // パラメータ取得
    $memberNo = isset($_GET['member_no']) ? (int)$_GET['member_no'] : 0;
    $addCredit = isset($_GET['credit']) ? (int)$_GET['credit'] : 3000;

    if ($memberNo == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'member_no パラメータが必要です'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 変換レート取得（マシン1を基準にする）
    $sql = "SELECT convert_no FROM dat_machine WHERE machine_no = 1";
    $stmt = $DB->prepare($sql);
    $stmt->execute();
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        echo json_encode([
            'status' => 'error',
            'message' => 'マシン情報が見つかりません'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 変換レート取得
    $sql = "SELECT point, credit FROM mst_convertPoint WHERE convert_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$machine['convert_no']]);
    $convert = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$convert) {
        echo json_encode([
            'status' => 'error',
            'message' => '変換レートが見つかりません'
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 必要ポイント計算
    $convPoint = $convert['point'];    // 例: 5
    $convCredit = $convert['credit'];  // 例: 1
    $usePoint = ($addCredit / $convCredit) * $convPoint;

    // 現在のポイント確認
    $sql = "SELECT member_no, mail, nickname, point FROM mst_member WHERE member_no = ?";
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

    if ($before['point'] < $usePoint) {
        echo json_encode([
            'status' => 'error',
            'message' => "ポイントが不足しています (必要: {$usePoint}, 現在: {$before['point']})"
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // トランザクション開始
    $DB->beginTransaction();

    // ポイント減算
    $beforePoint = $before['point'];
    $afterPoint = $beforePoint - $usePoint;

    $sql = "UPDATE mst_member SET point = point - ? WHERE member_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$usePoint, $memberNo]);

    // his_point に履歴を追加
    $sql = "INSERT INTO his_point (member_no, proc_dt, type, proc_cd, before_point, point, after_point, reason, add_no)
            VALUES (?, NOW(3), 51, '51', ?, ?, ?, ?, ?)";

    $stmt = $DB->prepare($sql);
    $stmt->execute([
        $memberNo,
        $beforePoint,
        -$usePoint,
        $afterPoint,
        "クレジット一括変換({$addCredit}クレジット)",
        $memberNo
    ]);

    // コミット
    $DB->commit();

    // 更新後の確認
    $sql = "SELECT member_no, mail, nickname, point FROM mst_member WHERE member_no = ?";
    $stmt = $DB->prepare($sql);
    $stmt->execute([$memberNo]);
    $after = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'message' => "{$addCredit} クレジットをチャージしました",
        'data' => [
            'member_no' => $after['member_no'],
            'nickname' => $after['nickname'],
            'before_point' => (int)$beforePoint,
            'after_point' => (int)$after['point'],
            'used_point' => (int)$usePoint,
            'added_credit' => $addCredit,
            'conversion_rate' => "{$convPoint}ポイント = {$convCredit}クレジット"
        ],
        'sync_command' => [
            'note' => 'Windows PC のコンソールで以下を実行してください',
            'commands' => [
                "game.credit += {$addCredit};",
                "game.playpoint = {$after['point']};",
                "keysocket.send('@CREDIT_'+game.credit);",
                "_sconnect.send('t:Apt,m:'+game.playpoint);",
                "_sconnect.send('t:Acr,m:'+game.credit);",
                "console.log('同期完了: credit='+game.credit+', playpoint='+game.playpoint);"
            ]
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    if (isset($DB) && $DB->inTransaction()) {
        $DB->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
