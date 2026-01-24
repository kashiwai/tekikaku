<?php
/**
 * spectate.php
 *
 * 観戦モード - プレイ中の台をLL-HLSで視聴
 *
 * @package NET8
 * @author Claude Code
 * @version 1.0
 */

require_once('../_etc/require_files.php');
define("PRE_HTML", basename(get_self(), ".php"));

main();

function main() {
    try {
        $template = new TemplateUser(false);
        DispSpectate($template);
    } catch (Exception $e) {
        $template->dispProcError($e->getMessage());
    }
}

function DispSpectate($template) {
    getData($_GET, array("NO", "MODE"));

    $machineNo = $_GET["NO"];
    $mode = $_GET["MODE"] ?: "llhls"; // llhls or webrtc

    if (mb_strlen($machineNo) == 0) {
        // 台番号がない場合は観戦可能な台一覧を表示
        DispSpectateList($template);
        return;
    }

    // 台情報を取得
    $sql = (new SqlString())
        ->setAutoConvert([$template->DB, "conv_sql"])
        ->select()
            ->field("dm.machine_no, dm.machine_cd, dm.model_no")
            ->field("mm.model_name, mm.model_roman, mm.image_list")
            ->field("lm.assign_flg, lm.member_no")
            ->from("dat_machine dm")
            ->join("left", "mst_model mm", "dm.model_no = mm.model_no")
            ->join("left", "lnk_machine lm", "dm.machine_no = lm.machine_no")
            ->where()
                ->and(false, "dm.machine_no = ", $machineNo, FD_NUM)
                ->and(false, "dm.del_flg != ", "1", FD_NUM)
            ->createSQL();

    $machineData = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

    if (!$machineData) {
        header("Location: spectate.php");
        exit;
    }

    // プレイ中かどうかチェック
    $isPlaying = ($machineData["assign_flg"] == "1");

    // MediaMTXサーバーURL（環境変数から取得）
    $mediaServerUrl = getenv('MEDIAMTX_URL') ?: 'https://mediamtx-server.railway.app';

    // 画面表示
    $template->open(PRE_HTML . ".html");
    $template->assignCommon();

    $template->assign("MACHINE_NO", $machineData["machine_no"], true);
    $template->assign("MACHINE_CD", $machineData["machine_cd"], true);
    $template->assign("MODEL_NAME", $machineData["model_name"], true);
    $template->assign("MODEL_IMAGE", $machineData["image_list"], true);
    $template->assign("MEDIA_SERVER_URL", $mediaServerUrl, true);
    $template->assign("MODE", $mode, true);

    $template->if_enable("IS_PLAYING", $isPlaying);
    $template->if_enable("NOT_PLAYING", !$isPlaying);
    $template->if_enable("MODE_LLHLS", $mode == "llhls");
    $template->if_enable("MODE_WEBRTC", $mode == "webrtc");

    $template->flush();
}

function DispSpectateList($template) {
    // プレイ中の台一覧を取得
    $sql = (new SqlString())
        ->setAutoConvert([$template->DB, "conv_sql"])
        ->select()
            ->field("dm.machine_no, dm.machine_cd")
            ->field("mm.model_name, mm.model_roman, mm.image_list")
            ->from("dat_machine dm")
            ->join("left", "mst_model mm", "dm.model_no = mm.model_no")
            ->join("inner", "lnk_machine lm", "dm.machine_no = lm.machine_no AND lm.assign_flg = 1")
            ->where()
                ->and(false, "dm.del_flg != ", "1", FD_NUM)
                ->and(false, "dm.machine_status = ", "1", FD_NUM)
            ->orderby("dm.machine_no asc")
            ->createSQL();

    $machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

    // 画面表示
    $template->open(PRE_HTML . "_list.html");
    $template->assignCommon();

    $template->assign("PLAYING_COUNT", count($machines), true);
    $template->if_enable("HAS_PLAYING", count($machines) > 0);
    $template->if_enable("NO_PLAYING", count($machines) == 0);

    $template->loop_start("MACHINE_LIST");
    foreach ($machines as $machine) {
        $template->assign("M_MACHINE_NO", $machine["machine_no"], true);
        $template->assign("M_MACHINE_CD", $machine["machine_cd"], true);
        $template->assign("M_MODEL_NAME", $machine["model_name"], true);
        $template->assign("M_MODEL_IMAGE", $machine["image_list"], true);
        $template->loop_next();
    }
    $template->loop_end("MACHINE_LIST");

    $template->flush();
}
?>
