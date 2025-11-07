<?php
/*
 * debug_play_check.php
 *
 * play_v2 アクセス時のチェック項目をデバッグ
 */

header('Content-Type: application/json; charset=utf-8');

// インクルード
require_once('../../_etc/require_files.php');

try {
    $template = new TemplateUser(false);
    $machineNo = isset($_GET['machine_no']) ? (int)$_GET['machine_no'] : 1;

    $result = [
        'machine_no' => $machineNo,
        'checks' => []
    ];

    // 1. ログインチェック
    $result['checks']['login'] = [
        'check' => 'ログイン状態',
        'status' => isset($template->Session->UserInfo) ? 'OK' : 'NG',
        'user_info' => isset($template->Session->UserInfo) ? [
            'member_no' => $template->Session->UserInfo['member_no'] ?? null,
            'mail' => substr($template->Session->UserInfo['mail'] ?? '', 0, 5) . '***'
        ] : null
    ];

    if (!isset($template->Session->UserInfo)) {
        $result['error'] = 'U5001: ログインが必要です';
        echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    // 2. テスターフラグ確認
    $sql = "SELECT member_no, tester_flg FROM mst_member WHERE member_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$template->Session->UserInfo['member_no']]);
    $testerRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $result['checks']['tester'] = [
        'check' => 'テスターフラグ',
        'tester_flg' => $testerRow['tester_flg'],
        'status' => $testerRow['tester_flg'] == '1' ? 'テスター（時間チェック無し）' : '通常ユーザー'
    ];

    // 3. 営業時間チェック
    $nowTime = date("H:i");
    $isBusinessHours = true;
    if ($testerRow['tester_flg'] == '0') {
        if (GLOBAL_CLOSE_TIME <= $nowTime && GLOBAL_OPEN_TIME > $nowTime) {
            $isBusinessHours = false;
        }
    }

    $result['checks']['business_hours'] = [
        'check' => '営業時間',
        'current_time' => $nowTime,
        'open_time' => GLOBAL_OPEN_TIME,
        'close_time' => GLOBAL_CLOSE_TIME,
        'status' => $isBusinessHours ? 'OK' : 'NG (U5004)',
        'skip' => $testerRow['tester_flg'] == '1' ? 'テスターのためスキップ' : null
    ];

    // 4. dat_machine の状態
    $sql = "SELECT machine_no, machine_status FROM dat_machine WHERE machine_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$machineNo]);
    $datmachineRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $machineStatusOK = ($testerRow['tester_flg'] == '1' || $datmachineRow['machine_status'] == '1');

    $result['checks']['machine_status'] = [
        'check' => 'dat_machine.machine_status',
        'machine_status' => $datmachineRow['machine_status'],
        'expected' => '1 (稼働中)',
        'status' => $machineStatusOK ? 'OK' : 'NG (U5005)',
        'skip' => $testerRow['tester_flg'] == '1' ? 'テスターのためスキップ' : null
    ];

    // 5. lnk_machine の状態
    $sql = "SELECT machine_no, assign_flg, member_no FROM lnk_machine WHERE machine_no = ?";
    $stmt = $template->DB->prepare($sql);
    $stmt->execute([$machineNo]);
    $lnkRow = $stmt->fetch(PDO::FETCH_ASSOC);

    $assignOK = !($lnkRow['assign_flg'] == '9' || empty($lnkRow['machine_no']));

    $result['checks']['assign_status'] = [
        'check' => 'lnk_machine.assign_flg',
        'assign_flg' => $lnkRow['assign_flg'],
        'member_no' => $lnkRow['member_no'],
        'status' => $assignOK ? 'OK' : 'NG (U5005)',
        'note' => '0=空き, 1=使用中, 9=視聴専用'
    ];

    // 6. 総合判定
    $allOK = $result['checks']['login']['status'] == 'OK' &&
             $isBusinessHours &&
             $machineStatusOK &&
             $assignOK;

    $result['final_result'] = $allOK ? 'プレイ可能' : 'プレイ不可';

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>
