<?php
/**
 * 視聴者用WebRTC接続情報API
 *
 * マシン番号から必要な接続情報を返す
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once('../_etc/require_files.php');
require_once('../_sys/WebRTCAPI.php');
require_once('../_etc/webRTC_setting.php');

$template = new TemplateUser(false);
$webRTC = new WebRTCAPI();

// マシン番号を取得
$machine_no = isset($_GET['NO']) ? intval($_GET['NO']) : 0;

if ($machine_no <= 0) {
    echo json_encode([
        'status' => 'error',
        'message' => 'マシン番号が指定されていません'
    ]);
    exit;
}

// マシン情報を取得
$sql = (new SqlString())
    ->setAutoConvert( [$template->DB,"conv_sql"] )
    ->select()
        ->field("dm.machine_no,dm.signaling_id,dm.camera_no")
        ->field("mm.model_name,mm.category")
        ->field("mc.camera_name")
        ->from("dat_machine dm")
        ->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
        ->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no" )
        ->where()
            ->and( "dm.machine_no =", $machine_no, FD_NUM)
            ->and( "dm.del_flg =", "0", FD_NUM)
    ->createSQL("\n");

$machine = $template->DB->getRow($sql);

if (empty($machine) || empty($machine['machine_no'])) {
    echo json_encode([
        'status' => 'error',
        'message' => '指定されたマシンが見つかりません'
    ]);
    exit;
}

// ワンタイムパスの発行
$oneTimeAuthID = $webRTC->getOneTimeAuthID();

// ダミーのmember_no
$memberNo = sha1("public_viewer_" . $machine_no . "_" . time());

// シグナリングサーバ情報
$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$machine['signaling_id']]);
$sighost = $sig[0];
$sigport = $sig[1];

//シグナリングサーバへ登録
if ( !$webRTC->addKeySignaling( $oneTimeAuthID, $machine['signaling_id'] ) ){
    echo json_encode([
        'status' => 'error',
        'message' => 'シグナリングサーバへの登録に失敗しました'
    ]);
    exit;
}

// 接続情報を返す
echo json_encode([
    'status' => 'success',
    'data' => [
        'machine_no' => $machine['machine_no'],
        'model_name' => $machine['model_name'],
        'category' => $machine['category'],
        'camera_id' => $machine['camera_name'],
        'peerjskey' => $GLOBALS["RTC_PEER_APIKEY"],
        'member_no' => $memberNo,
        'auth_id' => $oneTimeAuthID,
        'sig_host' => $sighost,
        'sig_port' => $sigport,
        'ice_servers' => $webRTC->getIceServers($machine['camera_name'])
    ]
]);
?>
