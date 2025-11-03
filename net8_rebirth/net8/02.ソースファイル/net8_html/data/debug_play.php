<?php
/*
 * debug_play.php
 *
 * デバッグ用：play_test_noauth.phpのエラー原因を調査
 */

// インクルード
require_once('../_etc/require_files_payment.php');
require_once('../_sys/WebRTCAPI.php');
require_once('../_etc/webRTC_setting.php');

// データ取得
getData($_GET, array("NO"));
if (!isset($_GET["NO"]) || $_GET["NO"] == "") {
    $_GET["NO"] = "1";
}

echo "<h1>デバッグ情報</h1>";
echo "<pre>";

// ユーザー情報の取得と表示
$template = new TemplateUser(false);

echo "=== ステップ1: ユーザー情報取得 ===\n";
$sql = "SELECT member_no, nickname, mail, last_name, first_name, state, point, login_dt, draw_point
        FROM mst_member
        WHERE mail = 'test@example.com' AND state = '1'";
$userRow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($userRow["member_no"])) {
    echo "❌ test@example.com のユーザーが見つかりません\n";
    exit;
} else {
    echo "✅ ユーザー取得成功: " . $userRow["nickname"] . " (member_no=" . $userRow["member_no"] . ")\n\n";
}

// 台情報の確認
echo "=== ステップ2: 台情報確認 (machine_no=" . $_GET["NO"] . ") ===\n";
$sql = "SELECT dm.machine_no, dm.machine_status, dm.signaling_id, dm.camera_no,
               mm.model_name, mc.camera_name
        FROM dat_machine dm
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        WHERE dm.machine_no = " . $_GET["NO"];
$machineRow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($machineRow)) {
    echo "❌ 台番号 " . $_GET["NO"] . " のデータが見つかりません\n";
} else {
    echo "✅ 台情報:\n";
    print_r($machineRow);
    echo "\n";

    if ($machineRow["machine_status"] != "1") {
        echo "⚠️ machine_status が 1 (稼働中) ではありません: " . $machineRow["machine_status"] . "\n";
    }
    if (empty($machineRow["camera_no"])) {
        echo "⚠️ camera_no が設定されていません\n";
    }
    if (empty($machineRow["signaling_id"])) {
        echo "⚠️ signaling_id が設定されていません\n";
    }
}

// lnk_machine の状態確認
echo "\n=== ステップ3: lnk_machine の状態確認 ===\n";
$sql = "SELECT machine_no, assign_flg, member_no, onetime_id
        FROM lnk_machine
        WHERE machine_no = " . $_GET["NO"];
$lnkRow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

if (empty($lnkRow)) {
    echo "❌ lnk_machine にデータがありません\n";
} else {
    echo "✅ lnk_machine 情報:\n";
    print_r($lnkRow);
    echo "\n";
}

// シグナリングサーバー設定確認
echo "\n=== ステップ4: シグナリングサーバー設定 ===\n";
if (isset($GLOBALS["RTC_Signaling_Servers"])) {
    echo "✅ RTC_Signaling_Servers 設定:\n";
    print_r($GLOBALS["RTC_Signaling_Servers"]);
} else {
    echo "❌ RTC_Signaling_Servers が設定されていません\n";
}

echo "\n=== ステップ5: WebRTC設定確認 ===\n";
$webRTC = new WebRTCAPI();
$browserStatus = $webRTC->checkBrowser(true);
echo "ブラウザチェック:\n";
print_r($browserStatus);

echo "</pre>";
