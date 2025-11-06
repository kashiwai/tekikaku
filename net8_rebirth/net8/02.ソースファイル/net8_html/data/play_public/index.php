<?php
/*
 * index.php (Public Viewer - ログイン不要版)
 *
 * 視聴専用ページ（複数人が同時視聴可能、ログイン不要）
 *
 * @version  2.0
 * @since    2025/11/06 ログイン不要版作成
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル

require_once('../../_sys/WebRTCAPI.php');				// requireファイル
require_once('../../_etc/webRTC_setting.php');			// webRTCセッティングファイル

// 項目定義
define("PRE_1p_HTML",  "play/index_pachi");				// テンプレートHTMLプレフィックス（パチンコ縦画面）
define("PRE_1l_HTML",  "play/index_pachi_ls_v2");		// テンプレートHTMLプレフィックス（パチンコ横画面）
define("PRE_2p_HTML",  "play/index_slot");				// テンプレートHTMLプレフィックス（スロット縦画面）
define("PRE_2l_HTML",  "play/index_slot_ls_v2");		// テンプレートHTMLプレフィックス（スロット横画面）
define("ERR_HTML",     "play/no_assign");				// テンプレートHTMLプレフィックス(エラー時）

// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 * @info
 */
function main() {

	try {
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		// 画面表示
		DispTop($template);

	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * VIEW画面表示（視聴専用・ログイン不要）
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {

	//webRTCのauth設定
	$webRTC = new WebRTCAPI();

	// データ取得
	getData($_GET, array("NO"));

	// マシン番号チェック
	if (empty($_GET["NO"])) {
		DispError($template, "マシン番号が指定されていません");
		return;
	}

	$jsDir = "/data/play_v2/js";

	//ブラウザチェック
	$browserStatus = $webRTC->checkBrowser(true);
	if ( $browserStatus["status"] == false ){
		DispError( $template, "お使いのブラウザはサポートされていません。Chrome、Edge、Safariをご利用ください。" );
		return;
	}

	// 視聴専用：ダミーのmember_noを使用
	$memberNo = sha1("public_viewer_" . $_GET["NO"] . "_" . time());

	//ワンタイムパスの発行
	$oneTimeAuthID = $webRTC->getOneTimeAuthID();

	//台情報の取得
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.machine_no,dm.signaling_id,dm.camera_no,mm.category,mm.prizeball_data,mm.layout_data,mc.camera_name,mm.image_reel")
			->field("mm.model_name,mm.model_roman,mm.model_cd")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no" )
			->where()
				->and( "dm.machine_no =", $_GET["NO"], FD_NUM)
				->and( "dm.del_flg =", "0", FD_NUM)
		->createSQL("\n");

	$machineRow = $template->DB->getRow($sql);

	if (empty($machineRow) || empty($machineRow["machine_no"])) {
		DispError($template, "指定されたマシンが見つかりません");
		return;
	}

	$prizeball_data = json_decode( $machineRow["prizeball_data"], true );
	$layout_data    = json_decode( $machineRow["layout_data"], true );
	if ( !isset($layout_data["hide"]) ) $layout_data["hide"] = array();

	// 押し順対応[pushorder]が非表示に無い場合、押し順非対応[nonepushorder]を非表示にする
	if (!in_array("pushorder", $layout_data["hide"])) {
		$layout_data["hide"][] = "nonepushorder";
	}

	//接続先の情報
	$camera  = $machineRow["camera_name"];
	$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$machineRow["signaling_id"]]);
	$sighost = $sig[0];
	$sigport = $sig[1];

	//シグナリングサーバへ登録
	if ( !$webRTC->addKeySignaling( $oneTimeAuthID, $machineRow["signaling_id"] ) ){
		DispError( $template, "シグナリングサーバへの接続に失敗しました" );
		return;
	}

	// 画面表示開始（既存の動作するテンプレート使用）
	if ( $machineRow["category"] == "1" ){
		if ( $layout_data["video_portrait"] == "1" ){
			$template->open(PRE_1p_HTML . ".html");
		} else {
			$template->open(PRE_1l_HTML . ".html");
		}
	} else {
		if ( $layout_data["video_portrait"] == "1" ){
			$template->open(PRE_2p_HTML . ".html");
		} else {
			$template->open(PRE_2l_HTML . ".html");
		}
	}

	$template->assignCommon();

	// WebRTC接続に必要な全ての変数
	$template->assign("CAMERA_ID"       , $camera);
	$template->assign("MACHINE_NO"      , $machineRow["machine_no"]);
	$template->assign("MODEL_NAME"      , $machineRow["model_name"]);
	$template->assign("MODEL_ROMAN"     , $machineRow["model_roman"]);
	$template->assign("MODEL_CD"        , $machineRow["model_cd"]);
	$template->assign("PEERJSKEY"       , $GLOBALS["RTC_PEER_APIKEY"]);
	$template->assign("MEMBERNO"        , $memberNo );
	$template->assign("AUTHID"          , $oneTimeAuthID);
	$template->assign("SIGHOST"         , $sighost);
	$template->assign("SIGPORT"         , $sigport);
	$template->assign("ICESERVERS"      , $webRTC->getIceServers($camera) );
	$template->assign("AUTO_PUSH"       , false );

	// デモ用ポイント設定
	$template->assign("PURCHASE"        , json_encode([
		["id" => "demo", "amount" => 999999, "price" => 0]
	]));
	$template->assign("CONVCREDIT"      , 1);
	$template->assign("CONVPLAYPOINT"   , 1);

	$template->assign("MAX"             , $prizeball_data["MAX"] ?? 0);
	$template->assign("MAX_RATE"        , $prizeball_data["MAX_RATE"] ?? 0);
	$template->assign("NAVEL"           , $prizeball_data["NAVEL"] ?? 0);
	$template->assign("TULIP"           , $prizeball_data["TULIP"] ?? 0);
	$template->assign("ATTACKER1"       , $prizeball_data["ATTACKER1"] ?? 0);
	$template->assign("ATTACKER2"       , $prizeball_data["ATTACKER2"] ?? 0);
	$template->assign("ERRORMESSAGES"   , json_encode([]));
	$template->assign("LAYOUTOPTION"    , json_encode($layout_data));

	$template->assign("IMAGE_REEL"      , $machineRow["image_reel"]);
	$template->assign("TIMESTAMP"       , "ts=".time() );
	$template->assign("JSDIR"           , $jsDir );
	$template->assign("LANG"            , FOLDER_LANG );
	$template->assign("USERNAME"        , "デモプレイヤー" );
	$template->assign("CLOSETIME"       , "24:00" );
	$template->assign("PACHI_RATE"      , 0 );
	$template->assign("RATE_DISPLAY"    , "d-none");
	$template->assign("CURRENCY_1"      , "コイン" );
	$template->assign("CURRENCY_2"      , "COIN" );

	$template->if_enable("PUSH_BONUS", false);

	$template->assign("PAYMENT_URL"    , "", true);
	$template->assign("PAYMENT_HIDDEN" , "", false);

	$template->assign("BROWSERVERSION"  , "{$browserStatus["name"]}/{$browserStatus["version"]}" );
	$template->assign("NOTICETIME"      , "24:00");

	$template->flush();
}

/**
 * エラー処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラーメッセージ
 * @return	なし
 */
function DispError( $template, $message ) {
	echo "<html><head><meta charset='UTF-8'><title>エラー</title></head><body>";
	echo "<h1>エラー</h1>";
	echo "<p>" . htmlspecialchars($message) . "</p>";
	echo "<p><a href='/'>トップページに戻る</a></p>";
	echo "</body></html>";
}

?>
