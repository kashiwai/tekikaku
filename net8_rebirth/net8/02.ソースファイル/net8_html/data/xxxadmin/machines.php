<?php
/*
 * machines.php
 * 
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 機種総合管理画面
 * 
 * 機種総合管理画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/07/11 初版作成 片岡 充
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));			// テンプレートHTMLプレフィックス

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
		$template = new TemplateAdmin();
		$template->Session = new SmartSession(URL_ADMIN . "login.php", SESSION_SEC_ADMIN, SESSION_SID_ADMIN, DOMAIN, true);
		
		// データ取得
		getData($_GET, array("M"));
		
		//権限チェック
		if( mb_strlen( checkAuth( $template)) > 0) header("Location: " . URL_ADMIN);
		
		// 実処理
		$mainWin = true;
		switch ($_GET["M"]) {
			case "setting":			// セッティング
				$mainWin = false;
				DispSetting($template);
				break;
				
			case "regist":			// 登録処理
				$mainWin = false;
				RegistData($template);
				break;
				
			case "end":				// 完了画面
				$mainWin = false;
				DispComplete($template);
				break;
				
			default:				// 一覧画面
				DispList($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), $mainWin);
	}
}

/**
 * 一覧画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispList($template, $message = "") {
	
	// データ取得
	getData($_GET , array("P", "ODR"));
	getData($_GET , array("S_CATEGORY", "S_SIGNALING_ID"));
	getData($_GET , array("S_MODEL_NO", "S_MODEL_NAME", "S_MODEL_CD", "S_MAKER_NO", "S_OWNER_NO", "S_CORNER_NO", "RELEASE_DATE_FROM", "RELEASE_DATE_TO", "END_DATE_FROM", "END_DATE_TO", "S_STATUS" ));
	
	// モデルデータ
	if( $_GET["S_MODEL_NO"] != ""){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "model_cd, model_name" )
				->from("mst_model")
				->where()
					->and( false, "model_no = "        , $_GET["S_MODEL_NO"], FD_NUM )
			->createSql();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$_GET["S_MODEL_CD"]   = $row["model_cd"];
		$_GET["S_MODEL_NAME"] = $row["model_name"];
	}
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "machine_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P","S_MODEL_NO"));
	
	//検索判定
	if( checkKeys( $_GET, array( "S_CATEGORY", "S_SIGNALING_ID", "S_MODEL_CD", "S_MODEL_NAME", "S_MAKER_NO", "S_OWNER_NO", "S_CORNER_NO", "RELEASE_DATE_FROM", "RELEASE_DATE_TO", "END_DATE_FROM", "END_DATE_TO", "S_STATUS"))){
		$_search = "show";	//表示用クラス名
	}else{
		$_search = "";
	}
	
	// 各種リスト取得
	$makerList   = getMakerList( $template);				// メーカーマスタからデータを取得
	$cornerList  = getCornerList( $template);				// コーナーマスタからデータを取得
	$ownerList   = getOwnerList( $template);				// オーナーマスタからデータを取得
	$signalList  = array();
	foreach( $GLOBALS["RTC_Signaling_Servers"] as $key=>$val){
		$signalList[ $key] = $key;
	}
	$signalList  = array( ""=>"指定なし") + $signalList;
	$cateList    = array( ""=>"指定なし") + $GLOBALS["categoryList"];
	
	// コーナー用SQL処理
	$_corner_from = "";
	if( $_GET["S_CORNER_NO"] != "") $_corner_from = "left join dat_machineCorner dmc on dmc.machine_no = dm.machine_no";
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("dat_machine dm")
			->from("left join mst_model mo on mo.model_no = dm.model_no" )
			->from("left join mst_owner ow on ow.owner_no = dm.owner_no" )
			->from("left join mst_maker ma on ma.maker_no = mo.maker_no" )
			->from( $_corner_from )
			->where()
				->and( true, "dm.model_no = "        , $_GET["S_MODEL_NO"], FD_NUM )
				->and( true, "dm.owner_no = "        , $_GET["S_OWNER_NO"], FD_NUM )
				->and( true, "dm.release_date >= "   , [$_GET["RELEASE_DATE_FROM"]," 00:00:00"], FD_DATEEX )
				->and( true, "dm.release_date <= "   , [$_GET["RELEASE_DATE_TO"],  " 23:59:59"], FD_DATEEX )
				->and( true, "dm.end_date >= "       , [$_GET["END_DATE_FROM"]," 00:00:00"], FD_DATEEX )
				->and( true, "dm.end_date <= "       , [$_GET["END_DATE_TO"],  " 23:59:59"], FD_DATEEX )
				->and( true, "dm.machine_status = "  , $_GET["S_STATUS"], FD_STR )
				->and( true, "dm.signaling_id = "    , $_GET["S_SIGNALING_ID"], FD_STR )
				->and( true, "mo.category = "        , $_GET["S_CATEGORY"], FD_NUM )
				->and( true, "mo.model_cd like "     , ["%",$_GET["S_MODEL_CD"],"%"], FD_STR )
				->and( true, "mo.model_name like "   , ["%",$_GET["S_MODEL_NAME"],"%"], FD_STR )
				->and( true, "mo.maker_no = "        , $_GET["S_MAKER_NO"], FD_NUM )
				->and( true, "dmc.corner_no = "      , $_GET["S_CORNER_NO"], FD_NUM )
		->createSql();
	
	$rsql = $sqls
			->resetField()
			->field("dm.machine_no, dm.model_no, dm.owner_no, dm.camera_no, dm.signaling_id, dm.convert_no, dm.release_date, dm.end_date, dm.machine_corner, dm.machine_status, dm.del_flg, dm.upd_dt")
			->field("mo.model_cd, mo.model_name, mo.maker_no, mo.image_list")
			->field("ow.owner_nickname")
			->field("ma.maker_name, ma.maker_roman")
			->field("mcp.convert_name")
			->from("left join mst_convertPoint mcp on mcp.convert_no = dm.convert_no" )
			->groupby( "dm.machine_no" )
			->orderby( "dm.camera_no" )
		->createSql("\n");
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	
	// 検索関連
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("RELEASE_DATE_FROM"   , $_GET["RELEASE_DATE_FROM"], true);
	$template->assign("RELEASE_DATE_TO"     , $_GET["RELEASE_DATE_TO"], true);
	$template->assign("END_DATE_FROM"       , $_GET["END_DATE_FROM"], true);
	$template->assign("END_DATE_TO"         , $_GET["END_DATE_TO"], true);
	$template->assign("S_MODEL_NO"          , $_GET["S_MODEL_NO"], true);
	$template->assign("S_MODEL_CD"          , $_GET["S_MODEL_CD"], true);
	$template->assign("S_MODEL_NAME"        , $_GET["S_MODEL_NAME"], true);
	$template->assign("SEL_OWNER_NO"        , makeOptionArray( $ownerList,  $_GET["S_OWNER_NO"],  true, "指定なし"));
	$template->assign("SEL_CORNER_NO"       , makeOptionArray( $cornerList, $_GET["S_CORNER_NO"], true, "指定なし"));
	$template->assign("SEL_MAKER"           , makeOptionArray( $makerList,  $_GET["S_MAKER_NO"], true, "指定なし"));
	$template->assign("RDO_CATEGORY"        , makeRadioArray( $cateList,   "S_CATEGORY", $_GET["S_CATEGORY"]));
	$template->assign("RDO_SIGNALING_ID"    , makeRadioArray( $signalList, "S_SIGNALING_ID", $_GET["S_SIGNALING_ID"]));
	$template->assign("SEL_STATUS"          , makeOptionArray( $GLOBALS["machineStatusList"], $_GET["S_STATUS"], true, "指定なし"));
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		
		$template->assign("MACHINE_NO_PAD"      , str_pad( $row["machine_no"], 7, 0, STR_PAD_LEFT), true);
		$template->assign("MACHINE_NO"          , $row["machine_no"], true);
		$template->assign("ROW_MODEL_NO"        , $row["model_no"], true);
		$template->assign("MODEL_CD"            , $row["model_cd"], true);
		$template->assign("MODEL_NAME"          , $row["model_name"], true);
		$template->assign("OWNER_NO"            , $row["owner_no"], true);
		$template->assign("OWNER_NICKNAME"      , $row["owner_nickname"], true);
		$template->assign("MAKER_NAME"          , $row["maker_name"], true);
		$template->assign("CAMERA_NO"           , $row["camera_no"], true);
		$template->assign("SIGNALING_ID"        , $row["signaling_id"], true);
		$template->assign("CONVERT_NO"          , $row["convert_no"], true);
		$template->assign("CONVERT_NAME"        , $row["convert_name"], true);
		$template->assign("RELEASE_DATE"        , $row["release_date"], true);
		$template->assign("END_DATE"            , $row["end_date"], true);
		$template->assign("IMAGE_LIST"          , $row["image_list"], true);
		$template->assign("MACHINE_STATUS"      , $row["machine_status"], true);
		$template->assign("MACHINE_STATUS_LABEL", $template->getArrayValue($GLOBALS["machineStatusList"], $row["machine_status"]), true);
		$template->assign("UPD_DT"              , $row["upd_dt"], true);
		$template->assign("DEL_FLG_LABEL"       , $template->getArrayValue($GLOBALS["AdminStatus"], $row["del_flg"]), true);
		// コーナー
		$_CORNERS = "";
		foreach ( explode(',', $row["machine_corner"]) as $value) {
			$_CORNERS .= $cornerList[ trim($value)] . "<br>";
		}
		$template->assign("CORNERS"             , $_CORNERS, false);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
}

/**
 * 詳細画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispSetting($template, $message = "") {
	// データ取得
	getData($_GET , array("NO"));
	
	if ( preg_match("/^[0-9,]+$/", $_GET["NO"] ) ){
		$machine_no_list = explode( ",", $_GET["NO"] );
	} else {
		//不正コード
		return;
	}
	
	$sql = (new SqlString($template->DB))
		->select()
			->field("dm.machine_no,dm.camera_no")
			->field("mo.model_name")
			->from("dat_machine dm")
				->join("left", "mst_model mo", "mo.model_no = dm.model_no and mo.del_flg <> 1" )
			->where()
				->and( "dm.machine_no in ",  $machine_no_list, FD_NUM )
			->orderby("dm.camera_no")
		->createSql();
	$rs = $template->DB->query( $sql );
	$machiNameList = array();
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$machiNameList[] = "台番号:{$row["machine_no"]} - {$row["model_name"]}";
	}

	// 画面表示開始
	$template->open(PRE_HTML . "_setting.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"          , mb_strlen($message) > 0);
	
	$template->assign("NO"                 , $_GET["NO"], true);
	$template->assign("ROD_MACHINE_STATUS" , makeRadioArray( $GLOBALS["machineStatusList"], "MACHINE_STATUS", "1"));
	$template->assign("ROD_RESET_BONUS"    , makeRadioArray( $GLOBALS["resetMachineBonusList"], "RESET_BONUS", "0"));
	$template->assign("MACHINE_LIST"       , implode( "<br>", $machiNameList ));
	
	// 表示
	$template->flush();
	
}

/**
 * 登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData($template) {
	// データ取得
	getData($_GET  , array("ACT", "NO"));
	getData($_POST , array("CORNER_NO"), false);
	getData($_POST , array("MACHINE_STATUS", "OWNER_NO", "CAMERA_NO", "MAKER_NO", "CONVERT_NO", "SIGNALING_ID", "RELEASE_DATE", "END_DATE", "DEL_FLG"));
	$corners = (!empty($_POST["CORNER_NO"])) ? implode(",", $_POST["CORNER_NO"]) : "";
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispSetting($template, $message);
		return;
	}

	if ( preg_match("/^[0-9,]+$/", $_GET["NO"] ) ){
		$machine_no_list = explode( ",", $_GET["NO"] );
	} else {
		//不正コード
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);

	//ステータスの一括変更
	$sql = (new SqlString($template->DB))
		->update( "dat_machine" )
			->set()
				->value( "machine_status"    , $_POST["MACHINE_STATUS"] , FD_STR)
				->value( "upd_no"            , $template->Session->AdminInfo["admin_no"], FD_NUM)
				->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "machine_no in" , $machine_no_list, FD_NUM)
		->createSQL();

	$template->DB->query($sql);

	$nextdate = Date("Y-m-d ") . CHROME_RESTART_TIME . ":00";
	if ( Date("Y-m-d H:is") > $nextdate ) $nextdate = Date("Y-m-d ", strtotime("+1 day")) . CHROME_RESTART_TIME . ":00";
	//実機の設定変更をしていた場合
	if ( $_POST["RESET_BONUS"] == "1" ){
		$sql = (new SqlString($template->DB))
			->insert()
				->into("dat_client_message")
					->value( "message_time"    , $nextdate, FD_DATE)
					->value( "message_text"    , "*", FD_STR)
					->value( "machines"        , implode(",",$machine_no_list), FD_STR)
					->value( "reset_bonus"     , "1", FD_NUM)
					->value( "add_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
			->createSQL();
		$template->DB->query($sql);

	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end&ACT=" . $mode);
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {
	// データ取得
	getData($_GET , array("ACT"));
	
	switch ($_GET["ACT"]) {
		case "update":
			// 更新
			$title = $template->message("A1862");
			$msg = $template->message("A1863");
			break;
		case "del":
			// 削除
			$title = $template->message("A1864");
			$msg = $template->message("A1865");
			break;
		default:
			// 新規登録
			$title = $template->message("A5001");
			$msg = $template->message("A5002");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}


/**
 * 賞球設定データJSON作成
 * @access	private
 * @param	arrayt	$_post			連想配列
 * @return	string					JSON形式の文字列
 */
function createPrizeballDataJsonString( $_post) {
	$ret = '{';
	$ret .=  '"MAX":'      .$_post["PRIZEBALL_DATA_MAX"];
	$ret .= ',"MAX_RATE":' .$_post["PRIZEBALL_DATA_MAX_RATE"];
	$ret .= ',"NAVEL":'    .$_post["PRIZEBALL_DATA_NAVEL"];
	$ret .= ',"TULIP":'    .$_post["PRIZEBALL_DATA_TULIP"];
	$ret .= ',"ATTACKER1":'.$_post["PRIZEBALL_DATA_ATTACKER1"];
	$ret .= ',"ATTACKER2":'.$_post["PRIZEBALL_DATA_ATTACKER2"];
	$ret .= ($_post["PRIZEBALL_DATA_EXTEND"]!="")? ',"EXTEND":'.$_post["PRIZEBALL_DATA_EXTEND"]:'';
	$ret .= '}';
	return $ret;
}

/**
 * カメラマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getCameraList( $template, $self=""){
	
	//新規の時は、使用されていないカメラを全部返す
	//	更新の時は、使用されていないカメラと、自分のカメラを返す
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mc.camera_no")
				->field("dm.machine_no")
				->orderby("mc.camera_no asc")
				->from("mst_camera mc")
				->from("left join dat_machine dm on dm.camera_no = mc.camera_no and dm.del_flg <> 1" )
				->where()
					->and(false, "mc.del_flg <> ", "1", FD_NUM)
			->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[] = array('camera_no' => $row['camera_no'], 'machine_no' => $row['machine_no']);
	}
	$ret = array();
	if( $self != ""){
		foreach( $arr as $value){
			if( $value["machine_no"] <= 0){
				$ret[$value["camera_no"]] = $value["camera_no"];
			}else{
				if( $value["machine_no"] == $self){
					$ret[$value["camera_no"]] = $value["camera_no"];
				}
			}
		}
		
	}else{
		foreach( $arr as $value){
			if( $value["machine_no"] <= 0) $ret[$value["camera_no"]] = $value["camera_no"];
		}
	}
	return $ret;
}


/**
 * オーナーマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getOwnerList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("owner_no, owner_nickname")
				->orderby("owner_no asc")
				->from("mst_owner")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
			->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row['owner_no']] = $row['owner_nickname'];
	}
	return $arr;
}


/**
 * メーカーマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getMakerList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("maker_no, maker_name, maker_roman")
				->orderby("maker_no asc")
				->from("mst_maker")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
			->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row['maker_no']] = $row['maker_name'];
	}
	return $arr;
}

/**
 * タイプマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getTypeList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("type_no, type_name, type_roman")
				->orderby("sort_no asc")
				->from("mst_type")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
			->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row['type_no']] = $row['type_name'];
	}
	return $arr;
}

/**
 * 号機マスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getUnitList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("unit_no, unit_name, unit_roman")
			->orderby("sort_no asc")
			->from("mst_unit")
			->where()
				->and(false, "del_flg != ", "1", FD_NUM)
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row['unit_no']] = $row['unit_name'];
	}
	return $arr;
}

/**
 * コーナーマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getCornerList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("corner_no, corner_name, corner_roman")
			->from("mst_corner")
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row["corner_no"]] = $row["corner_name"];
	}
	return $arr;
}

/**
 * 機種マスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getModelList( $template, $addclass = false){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("model_no, model_name, model_roman, category, maker_no")
			->from("mst_model")
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		if( $addclass){
			$arr[ $row["model_no"]] = array( "value" => $row["model_name"], "class" => "category".$row["category"] ." maker".$row["maker_no"] );
		}else{
			$arr[ $row["model_no"]] = $row["model_name"];
		}
	}
	return $arr;
}



/**
 * 検索フォームを開くために、$_GETに検索項目が含まれているかをチェックする
 * @access	private
 * @param	array	$arr			チェック対象配列
 * @param	array	$chks			検索項目名リスト
 * @return	boolean					true / false  含まれている場合 true を返す
 */
function checkKeys( $arr, $chks){
	foreach( $chks as $key){
		if( array_key_exists( $key , $arr)){
			if( $arr[$key] != ""){
				return true;
			}
		}
	}
	return false;
}


/**
 * 権限チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkAuth( $template) {
	$errMessage = "";
	//自分にコーナー管理（機種管理：2）の権限があるかどうかをチェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "deny_menu" )
			->from( "mst_admin" )
			->where()
				->and( "admin_no = ", $template->Session->AdminInfo["admin_no"], FD_NUM )
		->createSql();
	$_ary = explode(',', $template->DB->getOne($sql));
	//
	if( in_array('2', $_ary, true)) $errMessage = $template->message("A0005");
	//
	return $errMessage;
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["NO"])
			->required("A1801")
		->item($_POST["MACHINE_STATUS"])
			->required("A1802")
		->item($_POST["RESET_BONUS"])
			->required("A1802")
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}



?>
