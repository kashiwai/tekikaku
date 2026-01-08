<?php
/*
 * index.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * カメラ側制御ページ
 * 
 * カメラ側制御ページの表示を行う
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/10 初版作成 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル

require_once('../../_sys/WebRTCAPI.php');				// requireファイル
require_once('../../_etc/webRTC_setting.php');			// webRTCセッティングファイル

// 項目定義
define("PRE_2_HTML", "server/index_v2");				// テンプレートHTMLプレフィックス
define("PRE_1_HTML", "server/index_pachi");				// テンプレートHTMLプレフィックス

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
 * VIEW画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {

	// データ取得
	getData($_GET, array("MAC"));

	if ( mb_strlen($_GET["MAC"]) == 0 ){
		print "MACの指定がありません。";
		return;
	}

	$sql = (new SqlString($template->DB))
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("camera_no")
			->from("mst_camera")
			->where()
				->and( "camera_mac =", $_GET["MAC"], FD_STR)
				->and( "del_flg =",    "0", FD_NUM)
		->createSQL();
	$cameraRow = $template->DB->getRow($sql);

	if ( $cameraRow["camera_no"] == "" ){
		//macアドレスが登録されていないので新規にmst_cameraに登録する。
		$sql = (new SqlString($template->DB))
			->insert()
				->into("mst_camera")
					->value("camera_mac",  $_GET["MAC"], FD_STR)
					->value("camera_name", "camera_entry_mode", FD_STR)
					->value("add_no",      API_CAMERA_ADD_NO, FD_NUM)
					->value("add_dt",      Date("Y-m-d H:i:s"), FD_DATE)
			->createSQL();
		$result = $template->DB->query($sql);
		if ( $result == false ){
			print "新しいカメラの登録に失敗しました。管理者にお問い合わせ下さい。";
			return;
		}
		$camera_no = $template->DB->lastInsertId('camera_no');
		print "新しいカメラの登録しました。管理画面で機種設定を行って下さい。<br>";
		print "カメラ番号は「".$camera_no."」です。";
		return;
	}


	$sql = (new SqlString($template->DB))
		->select()
			->field("dm.machine_no,dm.signaling_id,dm.camera_no,mm.category,mm.model_name,mm.prizeball_data,mm.layout_data")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->where()
				->subQuery("camera_no",
							(new SqlString())
								->setAutoConvert( [$template->DB,"conv_sql"] )
								->select()
									->field("camera_no")
									->from("mst_camera")
									->where()
										->and( "camera_mac =", $_GET["MAC"], FD_STR)
										->and( "del_flg =",    "0", FD_NUM)
								->createSQL()
				)
		->createSQL("\n");

	$row = $template->DB->getRow($sql);
	
	if ( !array_key_exists("machine_no", $row) ){
	//if ( mb_strlen($row["machine_no"]) == 0 ){
		print "管理画面での機種設定がまだ行われていないので実行できません。";
		return;
	}

	$layout_data    = json_decode( $row["layout_data"], true );

	// デフォルト値を設定（model_no = 0 等で layout_data が NULL の場合）
	if ( $layout_data === null ) {
		$layout_data = [
			'video_portrait' => 0,
			'video_mode' => 4,
			'drum' => 1
		];
	}

	// lnk_machineの更新（レコードがない場合は新規作成）
	$sql = (new SqlString($template->DB))
		->insert()
			->into( "lnk_machine" )
				->value( "machine_no",        $row["machine_no"], FD_NUM)
				->value( "assign_flg",        "9", FD_NUM)
				->value( "member_no",         "0", FD_NUM)
				->value( "onetime_id",        "", FD_STR)
				->value( "exit_flg",          "0", FD_NUM)
				->value( "start_dt",          "", FD_DATE)
			->onDuplicateKeyUpdate()
				->value( "assign_flg",        "9", FD_NUM)
				->value( "member_no",         "0", FD_NUM)
				->value( "onetime_id",        "", FD_STR)
				->value( "start_dt",          "", FD_DATE)
		->createSQL("\n");
	$result = $template->DB->query($sql);
	if ( $result == false ){
		print "接続情報の更新に失敗しました。";
		return;
	}


	$prizeball_data = json_decode( $row["prizeball_data"], true );

	// デフォルト値を設定（model_no = 0 等で prizeball_data が NULL の場合）
	if ( $prizeball_data === null ) {
		$prizeball_data = [
			'MAX' => 0,
			'MAX_RATE' => 0,
			'NAVEL' => 0,
			'TULIP' => 0,
			'ATTACKER1' => 0,
			'ATTACKER2' => 0
		];
	}

	//カメラ名称の更新
	$camera  = sprintf(CAMERA_NAME, $row["camera_no"], Time());
	//2020-09-18 シグナリングサーバのPort設定追加
	$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$row["signaling_id"]]);
	$sighost = $sig[0];
	$sigport = $sig[1];
	//$sighost = $GLOBALS["RTC_Signaling_Servers"][$row["signaling_id"]];

	$sql = (new SqlString($template->DB))
		->update( "mst_camera" )
			->set()
				->value( "camera_name",       $camera, FD_STR)
				->value( "upd_no",            $row["machine_no"], FD_DATE)
				->value( "upd_dt",            Date("Y-m-d H:i:s"), FD_DATE)
			->where()
				->and( "camera_no =",        $row["camera_no"], FD_NUM)
		->createSQL("\n");
	$result = $template->DB->query($sql);
	if ( $result == false ){
		print "カメラ名称の更新に失敗しました。";
		return;
	}

	//メッセージ取得範囲の時間を設定
	$nowdate = Date("Y-m-d H:i:s");
	if ( GLOBAL_CLOSE_TIME > GLOBAL_OPEN_TIME ){
		$mesStartDT = Date("Y-m-d ",strtotime("-1 day")) . GLOBAL_CLOSE_TIME . "00";
		$mesEndDT   = Date("Y-m-d ") . GLOBAL_OPEN_TIME . "00";
	} else {
		$mesStartDT = Date("Y-m-d ") . GLOBAL_CLOSE_TIME . "00";
		$mesEndDT   = Date("Y-m-d ") . GLOBAL_OPEN_TIME . "00";
	}
	//メンテナンス時間以外なら直近の5分間
	if ( $nowdate > $mesEndDT || $nowdate < $mesStartDT ){
		$mesStartDT = date("Y-m-d H:i:s",strtotime("-5 minute"));
		$mesEndDT   = $nowdate;
	}

	//メッセージ情報からbonusなどのリセット情報を取得
	// TEMPORARY: Disabled dat_client_message query until table is properly configured
	$resetBonus = "off"; // Default value
	/*
	try {
		$sql = (new SqlString($template->DB))
			->select()
				->field("message_time,reset_bonus")
				->from("dat_client_message")
				->where()
					->and( "message_time >= ",  $mesStartDT, FD_DATE )
					->and( "message_time <= ",  $mesEndDT, FD_DATE )
					->groupStart()
						->or( "machines = '*'" )
						->or( "machines like ",   "%".$row["machine_no"]."%", FD_STR )
					->groupEnd()
				->orderby("message_time desc")
			->createSQL("\n");
		$rs = $template->DB->query($sql);

		while ($rbrow = $rs->fetch(PDO::FETCH_ASSOC)) {
			if ( $rbrow["reset_bonus"] == "1" ) $resetBonus = "on";
		}
	} catch (Exception $e) {
		// dat_client_message table not found or query error - use default value
		error_log("dat_client_message query failed: " . $e->getMessage());
	}
	*/

	//webRTCのauth設定
	$webRTC = new WebRTCAPI();

	//キーの取得
	$id   = $webRTC->getOneTimeAuthID();
	$pass = $webRTC->getOneTimeAuthPASS();

	//シグナリングサーバへ登録
	//TEMPORARY: Authentication bypassed on PeerJS server side
	//if ( !$webRTC->addKeySignaling( $id, $row["signaling_id"] ) ){
	//	print $webRTC->errorMessage();
	//}
	
	//時間計算
	$noticeTime = date('H:i', strtotime("-". NOTICE_CLOSE_TIME ." minute", strtotime(date("Y-m-d").' '.GLOBAL_CLOSE_TIME.':00')));

	// 画面表示開始
	if ( $row["category"] == "1" ){
		$template->open(PRE_1_HTML . ".html");
	} else {
		$template->open(PRE_2_HTML . ".html");
	}
	$template->assignCommon();
	
	$template->assign("CAMERA_ID"       , $camera);
	$template->assign("MACHINE_NO"      , $row["machine_no"]);
	$template->assign("MACHINE_NAME"    , $row["model_name"]);
	$template->assign("PEERJSKEY"       , $GLOBALS["RTC_PEER_APIKEY"]);
	$template->assign("AUTHID"          , $id);
	$template->assign("AUTHPASS"        , $pass);
	$template->assign("SIGHOST"         , $sighost);
	//2020-09-18 port追加
	$template->assign("SIGPORT"         , $sigport);
	$template->assign("ICESERVERS"      , $webRTC->getIceServers($camera, true) );
	$template->assign("AUTOPAYTIME"     , AUTO_PAY_TIME);
	$template->assign("GAMEOPENTIME"    , GLOBAL_OPEN_TIME);
	$template->assign("GAMECLOSETIME"   , GLOBAL_CLOSE_TIME);
	$template->assign("RESTARTTIME"     , CHROME_RESTART_TIME);
	
	$template->assign("NOTICECLOSETIME" , $noticeTime);
	$template->assign("RESETBONUS"      , $resetBonus);

	$template->assign("MAX"             , $prizeball_data["MAX"]);
	$template->assign("MAX_RATE"        , $prizeball_data["MAX_RATE"]);
	$template->assign("NAVEL"           , $prizeball_data["NAVEL"]);
	$template->assign("TULIP"           , $prizeball_data["TULIP"]);
	$template->assign("ATTACKER1"       , $prizeball_data["ATTACKER1"]);
	$template->assign("ATTACKER2"       , $prizeball_data["ATTACKER2"]);
	$template->assign("LAYOUTOPTION"    , json_encode($layout_data));

	$template->assign("TIMESTAMP"       , "ts=".time() );

	$template->flush();
}

?>
