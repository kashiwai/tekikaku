<?php
/*
 * search.php
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
 * 機種管理画面表示
 * 
 * 機種管理画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
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
		
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理
		$mainWin = true;
		switch ($_GET["M"]) {
			case "detail":			// 詳細画面
				$mainWin = false;
				DispDetail($template);
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
	$cornerList  = getCornerList( $template);				// コーナーマスタからデータを取得
	$ownerList   = getOwnerList( $template);				// オーナーマスタからデータを取得
	$signalList  = array();
	foreach( $GLOBALS["RTC_Signaling_Servers"] as $key=>$val){
		$signalList[ $key] = $key;
	}
	
	// コーナー用SQL処理
	$_corner_from = "";
	if( $_GET["S_CORNER_NO"] != "") $_corner_from = "left join dat_machineCorner dmc on dmc.machine_no = dm.machine_no";
	
	// 検索用日付
	$releaseSt = ((mb_strlen($_GET["RELEASE_DATE_FROM"]) > 0) ? GetRefTimeStart($_GET["RELEASE_DATE_FROM"], "Y/m/d") : "");
	$releaseEd = ((mb_strlen($_GET["RELEASE_DATE_TO"]) > 0) ? GetRefTimeEnd($_GET["RELEASE_DATE_TO"], "Y/m/d") : "");
	$endSt = ((mb_strlen($_GET["END_DATE_FROM"]) > 0) ? GetRefTimeStart($_GET["END_DATE_FROM"], "Y/m/d") : "");
	$endEd = ((mb_strlen($_GET["END_DATE_TO"]) > 0) ? GetRefTimeEnd($_GET["END_DATE_TO"], "Y/m/d") : "");
	
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
				->and( true, "dm.release_date >= "   , $releaseSt, FD_DATEEX )
				->and( true, "dm.release_date <= "   , $releaseEd, FD_DATEEX )
				->and( true, "dm.end_date >= "       , $endSt, FD_DATEEX )
				->and( true, "dm.end_date <= "       , $endEd, FD_DATEEX )
				->and( true, "dm.machine_status = "  , $_GET["S_STATUS"], FD_STR )
				->and( true, "dm.signaling_id = "    , $_GET["S_SIGNALING_ID"], FD_STR )
				->and( true, "mo.category = "        , $_GET["S_CATEGORY"], FD_NUM )
				->and( true, "mo.model_cd like "     , ["%",$_GET["S_MODEL_CD"],"%"], FD_STR )
				->and( true, "mo.model_name like "   , ["%",$_GET["S_MODEL_NAME"],"%"], FD_STR )
				->and( true, "mo.maker_no = "        , $_GET["S_MAKER_NO"], FD_NUM )
				->and( true, "dmc.corner_no = "      , $_GET["S_CORNER_NO"], FD_NUM )
				->and( "dm.del_flg != "              , "1", FD_NUM )	// 2020/04/24 [ADD]
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("dm.machine_no, dm.machine_cd, dm.model_no, dm.owner_no, dm.camera_no, dm.signaling_id")
			->field("dm.convert_no, dm.release_date, dm.end_date, dm.machine_corner, dm.machine_status, dm.upd_dt")
			->field("mo.model_cd, mo.model_name, mo.maker_no")
			->field("ow.owner_nickname")
			->field("ma.maker_name, ma.maker_roman")
			->field("mcp.convert_name")
			->from("left join mst_convertPoint mcp on mcp.convert_no = dm.convert_no" )
			->groupby( "dm.machine_no" )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 検索関連
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("RELEASE_DATE_FROM"   , $_GET["RELEASE_DATE_FROM"], true);
	$template->assign("RELEASE_DATE_TO"     , $_GET["RELEASE_DATE_TO"], true);
	$template->assign("END_DATE_FROM"       , $_GET["END_DATE_FROM"], true);
	$template->assign("END_DATE_TO"         , $_GET["END_DATE_TO"], true);
	$template->assign("S_MODEL_NO"          , $_GET["S_MODEL_NO"], true);
	$template->assign("S_MODEL_CD"          , $_GET["S_MODEL_CD"], true);
	$template->assign("S_MODEL_NAME"        , $_GET["S_MODEL_NAME"], true);
	$template->assign("SEL_OWNER_NO"        , makeOptionArray( $ownerList,  $_GET["S_OWNER_NO"],  true));	// 2020/04/24 [UPD]
	$template->assign("SEL_CORNER_NO"       , makeOptionArray( $cornerList, $_GET["S_CORNER_NO"], true));	// 2020/04/24 [UPD]
	$template->assign("SEL_MAKER"           , makeOptionArray( $template->DB->getMakerList(1), $_GET["S_MAKER_NO"], true));	// 2020/04/24 [UPD]
	$template->assign("SEL_CATEGORY"        , makeOptionArray($GLOBALS["categoryList"], $_GET["S_CATEGORY"],  true));	// 2020/04/27 [UPD]
	$template->assign("SEL_SIGNALING_ID"    , makeOptionArray($signalList,  $_GET["S_SIGNALING_ID"], true));			// 2020/04/27 [UPD]
	$template->assign("SEL_STATUS"          , makeOptionArray( $GLOBALS["machineStatusList"], $_GET["S_STATUS"], true));	// 2020/04/24 [UPD]
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		
		$template->assign("MACHINE_NO_PAD"      , $template->formatNoBasic($row["machine_no"]), true);	// 2020/04/24 [UPD]
		$template->assign("MACHINE_NO"          , $row["machine_no"], true);
		$template->assign("MACHINE_CD"          , $row["machine_cd"], true);
		$template->assign("MODEL_CD"            , $row["model_cd"], true);
		$template->assign("MODEL_NAME"          , $row["model_name"], true);
		$template->assign("OWNER_NICKNAME"      , $row["owner_nickname"], true);
		$template->assign("MAKER_NAME"          , $row["maker_name"], true);
		$template->assign("CAMERA_NO"           , $template->formatNoBasic($row["camera_no"]), true);
		$template->assign("SIGNALING_ID"        , $row["signaling_id"], true);
		$template->assign("CONVERT_NAME"        , $row["convert_name"], true);
		$template->assign("RELEASE_DATE"        , format_date($row["release_date"]), true);
		$template->assign("END_DATE"            , format_date($row["end_date"]), true);
		$template->assign("MACHINE_STATUS_LABEL", $template->getArrayValue($GLOBALS["machineStatusList"], $row["machine_status"]), true);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);
	
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
function DispDetail($template, $message = "") {
	// データ取得
	getData($_GET  , array("ACT", "NO"));
	getData($_POST , array("CORNER_NO"), false);
	getData($_POST , array("NO", "MACHINE_STATUS", "OWNER_NO", "CAMERA_NO", "MAKER_NO", "CONVERT_NO"
						, "SIGNALING_ID", "RELEASE_DATE", "END_DATE", "H_OWNER_NO"
						, "MACHINE_CD", "REMARKS", "SETTING", "H_REAL_SETTING", "MODEL_NO"
						, "CLS_PAST_MAX", "PAST_MAX_CREDIT", "PAST_MAX_BB", "PAST_MAX_RB"	// 2020/12/22 [ADD]
						));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if(mb_strlen($message) == 0 || $_GET["ACT"] == "del") {		// 2020/04/24 [UPD] 初回表示若しくは削除チェックエラー時に修正
			$_load = true;
		}else{
			$_load = false;
		}
	}else{
		$_load = false;
	}

	// 初期値設定
	$arySettingList = array();
	if (isset($GLOBALS["ModelSettingList"])) {
		foreach ($GLOBALS["ModelSettingList"] as $val) {
			$arySettingList[$val] = array("value" => $val, "class" => "setval" . $val);
		}
	}
	
	$modelList   = getModelList( $template, true);			// 機種マスタからデータを取得
	$cornerList  = getCornerList( $template);				// コーナーマスタからデータを取得
	$ownerList   = getOwnerList( $template);				// オーナーマスタからデータを取得
	$convertList = getConvertList( $template);				// ポイント変換マスタからデータを取得
	$cameraList  = getCameraList( $template, $_GET["NO"]);	// カメラマスタからデータを取得
	$signalList  = array();
	foreach( $GLOBALS["RTC_Signaling_Servers"] as $key=>$val){
		$signalList[ $key] = $key;
	}
	
	
	if( $_load ){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dm.machine_no, dm.machine_cd, dm.owner_no, dm.model_no, dm.camera_no, dm.del_flg, dm.convert_no")
				->field("dm.signaling_id, dm.release_date, dm.end_date, dm.machine_status, dm.machine_corner, dm.remarks")
				->field("dm.real_setting, dm.upd_setting, IF(dm.upd_setting = 0, dm.real_setting, dm.upd_setting) as setting")
				->field("mo.maker_no")
				->from("dat_machine dm")
				->from("left join mst_model mo on mo.model_no = dm.model_no and mo.del_flg <> 1" )
				->where()
					->and( "dm.machine_no = ", $_GET["NO"], FD_NUM )
					->and( "dm.del_flg <> "  , "1", FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// 2020/04/24 [ADD Start]
		if (empty($row["machine_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$_POST["H_OWNER_NO"] = $row["owner_no"];
		// 2020/04/24 [ADD End]
		$corners = explode( ",", $row["machine_corner"]);

		// 2020/12/22 [ADD Start] 過去最大数クリア
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("machine_no, past_max_credit, past_max_bb, past_max_rb")
				->from("dat_machinePlay")
				->where()
					->and( "machine_no = ", $_GET["NO"], FD_NUM )
			->createSql();
		$rowPlay = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		if (empty($rowPlay["machine_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$_POST["PAST_MAX_CREDIT"] = number_formatEx($rowPlay["past_max_credit"]);
		$_POST["PAST_MAX_BB"] = number_formatEx($rowPlay["past_max_bb"]);
		$_POST["PAST_MAX_RB"] = number_formatEx($rowPlay["past_max_rb"]);
		// 2020/12/22 [ADD End] 過去最大数クリア
	}else{
		//POST or 新規
		$row["machine_status"] = $_POST["MACHINE_STATUS"];
		$row["machine_no"]     = $_POST["NO"];
		$row["machine_cd"]     = $_POST["MACHINE_CD"];
		$row["owner_no"]       = $_POST["OWNER_NO"];
		$row["camera_no"]      = $_POST["CAMERA_NO"];
		$row["maker_no"]       = $_POST["MAKER_NO"];
		$row["model_no"]       = $_POST["MODEL_NO"];
		$row["convert_no"]     = $_POST["CONVERT_NO"];
		$row["signaling_id"]   = $_POST["SIGNALING_ID"];
		$row["release_date"]   = $_POST["RELEASE_DATE"];
		$row["end_date"]       = $_POST["END_DATE"];
		$row["remarks"]        = $_POST["REMARKS"];
		$row["setting"]        = $_POST["SETTING"];
		$row["real_setting"]   = ((mb_strlen($_POST["H_REAL_SETTING"]) > 0) ? $_POST["H_REAL_SETTING"]: "0");
		$corners               = $_POST["CORNER_NO"];
		$_POST["H_OWNER_NO"]   = $_POST["H_OWNER_NO"];	// 2020/04/24 [ADD]
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"          , mb_strlen($message) > 0);
	$template->if_enable("EDIT"            , mb_strlen($row["machine_no"]) > 0);
	$template->if_enable("NOW_SETTING"     , $row["real_setting"] != "0");
	
	$template->assign("NO"                 , $row["machine_no"], true);
	$template->assign("MACHINE_CD"         , $row["machine_cd"], true);
	$template->assign("RDO_MACHINE_STATUS" , makeRadioArray( $GLOBALS["machineStatusList"], "MACHINE_STATUS", $row["machine_status"]));
	$template->assign("SEL_OWNER_NO"       , makeOptionArray( $ownerList,  $row["owner_no"],  true));	// 2020/04/24 [UPD]
	$template->assign("SEL_CAMERA_NO"      , makeOptionArray( $cameraList, $row["camera_no"], true));	// 2020/04/24 [UPD]
	$template->assign("SEL_MAKER_NO"       , makeOptionArray( $template->DB->getMakerList(1), $row["maker_no"], false));	// 2020/04/24 [UPD]
	$template->assign("SEL_MODEL_NO"       , makeOptionArrayAddClass( $modelList ,$row["model_no"] , true));	// 2020/04/24 [UPD]
	$template->assign("SEL_CONVERT_NO"     , makeOptionArray( $convertList, $row["convert_no"], true));			// 2020/04/24 [UPD]
	$template->assign("SEL_SIGNALING_ID"   , makeOptionArray( $signalList,  $row["signaling_id"], true));		// 2020/04/24 [UPD]
	$template->assign("CHK_CONERS"         , makeCheckBoxArray( $cornerList, "CORNER_NO[]", $corners));
	$template->assign("RELEASE_DATE"       , $row["release_date"], true);
	$template->assign("END_DATE"           , $row["end_date"], true);
	$template->assign("H_OWNER_NO"         , $_POST["H_OWNER_NO"], true);	// 2020/04/24 [ADD]
	$template->assign("DEFAULT_END_DATE"   , DEFAULT_END_DATE, true);		// 2020/05/15 [ADD]
	$template->assign("REMARKS"            , $row["remarks"], true);
	
	$template->assign("REAL_SETTING"       , $row["real_setting"], true);
	$template->assign("SEL_SETTING"        , makeOptionArrayAddClass($arySettingList, $row["setting"], false));	// 設定値
	$template->assign("STATUS_MAINTE"      , $GLOBALS["machineStatusList"]["2"], true);

	// シグナリング
	// SIGNALING_ID_COUNT
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.signaling_id, count( dm.signaling_id) as mcnt")
			->from("dat_machine dm")
			->where()
				->and( "dm.del_flg <> ", "1", FD_NUM )
			->groupby( "dm.signaling_id" )
		->createSql();
	$row = $template->DB->query($sql);
	$_sig_str = "";
	foreach( $row as $line){
		$_sig_str .= $line["signaling_id"] ." : ". $line["mcnt"] . " units <br>";
	}
	$template->assign("SIGNALING_ID_COUNT"            , $_sig_str);
	
	// 設定リスト
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mm.model_no, mm.setting_list")
		->from("mst_model mm")
		->where()
			->and( "mm.del_flg = ", "0", FD_NUM )
		->createSql("\n");
	$rsModel = $template->DB->query($sql);
	$template->loop_start("SETTING_LIST");
	while ($setting = $rsModel->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("MODEL"       , $setting["model_no"], true);
		$template->assign("SETTING_LIST", $setting["setting_list"], true);
		$template->loop_next();
	}
	$template->loop_end("SETTING_LIST");
	unset($rsModel);

	// 2020/12/22 [ADD Start] 過去最大数クリア
	$template->if_enable("PLAY_PAST_MAX", PLAY_PAST_MAX);
	$template->assign("CHK_PAST_MAX"    , (mb_strlen($_POST["CLS_PAST_MAX"]) > 0) ? 'checked="checked"' : "");
	$template->assign("PAST_MAX_CREDIT" , $_POST["PAST_MAX_CREDIT"], true);
	$template->assign("PAST_MAX_BB" , $_POST["PAST_MAX_BB"], true);
	$template->assign("PAST_MAX_RB" , $_POST["PAST_MAX_RB"], true);
	// 2020/12/22 [ADD End] 過去最大数クリア

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
	getData($_POST , array("NO", "MACHINE_STATUS", "OWNER_NO", "CAMERA_NO", "MAKER_NO", "CONVERT_NO"
						, "SIGNALING_ID", "RELEASE_DATE", "END_DATE", "H_OWNER_NO"
						, "MACHINE_CD", "REMARKS", "SETTING", "H_REAL_SETTING", "MODEL_NO"
						, "CLS_PAST_MAX"	// 2020/12/22 [ADD]
						));
	$corners = (!empty($_POST["CORNER_NO"])) ? implode(",", $_POST["CORNER_NO"]) : "";
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "dat_machine" )
				->set()
					->value( "camera_no"         , NULL , FD_STR)
					->value( "del_flg"           , 1, FD_NUM)
					->value( "del_no"            , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"            , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "machine_no ="        , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
		// dat_machineConner
		// 削除
		$dmcDeleteSql = "delete from dat_machineCorner where machine_no = " . $template->DB->conv_sql( $_GET["NO"], FD_NUM);
		$template->DB->query($dmcDeleteSql);
		
		// 2020/04/27 [ADD Start]
		// 実機接続状況[lnk_machine]削除
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->delete()
				->from("lnk_machine")
				->where()
					->and("machine_no =", $_GET["NO"], FD_NUM)
			->createSQL("\n");
		$template->DB->exec($sql);

		// 実機プレイデータ[dat_machinePlay]削除
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->delete()
				->from("dat_machinePlay")
				->where()
					->and("machine_no =", $_GET["NO"], FD_NUM)
			->createSQL("\n");
		$template->DB->exec($sql);

		// オーナNO取得
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dm.owner_no")
				->from("dat_machine dm")
				->where()
					->and("machine_no =" , $_GET["NO"], FD_NUM)
			->createSQL("\n");
		$ownNo = $template->DB->getOne($sql);
		// 2020/04/27 [ADD End]
		// mst_owner 登録台数更新
		UpdMachineCount($template, $ownNo);	// 2020/04/27 [UPD]
		
	}else{
		if (mb_strlen($_POST["NO"]) > 0) {
			// 更新
			$mode = "update";
			$sqls = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_machine" )
					->set()
						->value( true, "machine_status"  , $_POST["MACHINE_STATUS"], FD_NUM)
						->value( "owner_no"        , $_POST["OWNER_NO"], FD_NUM)
						->value( "model_no"        , $_POST["MODEL_NO"], FD_NUM)
						->value( "machine_cd"      , $_POST["MACHINE_CD"], FD_STR)
						->value( "camera_no"       , $_POST["CAMERA_NO"], FD_NUM)
						->value( "signaling_id"    , $_POST["SIGNALING_ID"], FD_STR)
						->value( "convert_no"      , $_POST["CONVERT_NO"], FD_NUM)
						->value( "machine_corner"  , $corners, FD_STR)
						->value( "release_date"    , $_POST["RELEASE_DATE"], FD_DATEEX)
						->value( "end_date"        , ($_POST["END_DATE"]!="")? $_POST["END_DATE"]:DEFAULT_END_DATE, FD_DATEEX)	// 2020/05/15 [UPD]
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "machine_no =" , $_POST["NO"], FD_NUM);
			// 設定値
			$sql =  (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dm.real_setting, dm.upd_setting")
				->from("dat_machine dm")
				->where()
					->and("dm.machine_no =" , $_POST["NO"], FD_NUM)
			->createSQL("\n");
			$nowRow = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
			$updSetting = ((mb_strlen($_POST["SETTING"]) <= 0) ? "0" : $_POST["SETTING"]);
			if ($nowRow["upd_setting"] != $updSetting) {
				if ($nowRow["real_setting"] != $updSetting) {
					$sqls->set()
						->value("upd_setting"   , $updSetting, FD_NUM)
						->value("setting_upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("setting_upd_dt", "current_timestamp", FD_FUNCTION);
				} else {
					$sqls->set()
						->value("upd_setting"   , "0", FD_NUM);
				}
			}
			$sql = $sqls->createSQL("\n");
			$template->DB->query($sql);
			// 削除
			$dmcDeleteSql = "delete from dat_machineCorner where machine_no = " . $template->DB->conv_sql( $_POST["NO"], FD_NUM);
			$template->DB->query($dmcDeleteSql);
			// 登録
			if( $corners != ""){
				$cary = explode(",", $corners);
				foreach( $cary as $v){
					$playsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
						->insert()
							->into( "dat_machineCorner" )
								->value( "machine_no", $_POST["NO"], FD_NUM)
								->value( "corner_no" , $v, FD_NUM)
								->value( "add_dt"    , "current_timestamp", FD_FUNCTION)
						->createSQL();
					$template->DB->query($playsql);
				}
			}
			// 2020/04/27 [ADD Start] オーナ機種台数更新
			if ($_POST["OWNER_NO"] != $_POST["H_OWNER_NO"]) {
				UpdMachineCount($template, $_POST["OWNER_NO"]);		// 変更後オーナ
				UpdMachineCount($template, $_POST["H_OWNER_NO"]);	// 変更前オーナ
			}
			// 2020/04/27 [ADD End] オーナ機種台数更新
			// 2020/12/22 [ADD Start] 過去最大数クリア
			if (mb_strlen($_POST["CLS_PAST_MAX"]) > 0) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->update( "dat_machinePlay" )
						->set()
							->value(SQL_CUT, "past_max_credit", $_POST["CLS_PAST_MAX"], FD_NUM)
							->value(SQL_CUT, "past_max_bb"    , $_POST["CLS_PAST_MAX"], FD_NUM)
							->value(SQL_CUT, "past_max_rb"    , $_POST["CLS_PAST_MAX"], FD_NUM)
							->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
						->where()
							->and( false, "machine_no =" , $_POST["NO"], FD_NUM)
					->createSQL();
				$template->DB->exec($sql);
			}
			// 2020/12/22 [ADD End] 過去最大数クリア
		}else{
			// 新規
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "dat_machine" )
						->value( "owner_no"        , $_POST["OWNER_NO"], FD_NUM)
						->value( "model_no"        , $_POST["MODEL_NO"], FD_NUM)
						->value( "machine_cd"      , $_POST["MACHINE_CD"], FD_STR)
						->value( "camera_no"       , $_POST["CAMERA_NO"], FD_NUM)
						->value( "signaling_id"    , $_POST["SIGNALING_ID"], FD_STR)
						->value( "convert_no"      , $_POST["CONVERT_NO"], FD_NUM)
						->value( "machine_corner"  , $corners, FD_STR)
						->value( "release_date"    , $_POST["RELEASE_DATE"], FD_DATEEX)
						->value( true, "end_date"  , $_POST["END_DATE"], FD_DATEEX)
						->value( true, "machine_status", $_POST["MACHINE_STATUS"], FD_NUM)
						->value(SQL_CUT, "upd_setting", $_POST["SETTING"], FD_NUM)
						->value(SQL_CUT, "setting_upd_no", (mb_strlen($_POST["SETTING"] > 0) ? $template->Session->AdminInfo["admin_no"] : ""), FD_NUM)
						->value(SQL_CUT, "setting_upd_dt", (mb_strlen($_POST["SETTING"] > 0) ? "current_timestamp" : ""), FD_FUNCTION)
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
						->value( "add_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($sql);
			// lnk_machine
			$idx = $template->DB->lastInsertId("machine_no");
			$linksql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "lnk_machine" )
						->value( "machine_no", $idx, FD_NUM)
						->value( "assign_flg", "9",  FD_NUM)
				->createSQL();
			$template->DB->query($linksql);
			// dat_machineConner
			if( $corners != ""){
				$cary = explode(",", $corners);
				foreach( $cary as $v){
					$playsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
						->insert()
							->into( "dat_machineCorner" )
								->value( "machine_no", $idx, FD_NUM)
								->value( "corner_no" , $v, FD_NUM)
								->value( "add_dt"    , "current_timestamp", FD_FUNCTION)
						->createSQL();
					$template->DB->query($playsql);
				}
			}
			// dat_machinePlay
			$playsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "dat_machinePlay" )
						->value( "machine_no", $idx, FD_NUM)
						->value( "hit_data", "[]", FD_STR)
						->value( "add_dt", "current_timestamp", FD_FUNCTION)
						->value( "upd_dt", "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($playsql);
			
			// mst_owner 登録台数更新
			UpdMachineCount($template, $_POST["OWNER_NO"]);	// 2020/04/24 [UPD]
			
		}
		
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
			$title = $template->message("A1860");
			$msg = $template->message("A1861");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}


/**
 * カメラマスタからデータを取得
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	string	$self		実機NO
 * @return	array				連想配列
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
	unset($rs);
	$ret = array();
	if( $self != ""){
		foreach( $arr as $value){
			if( $value["machine_no"] <= 0){
				$ret[$value["camera_no"]] = $template->formatNoBasic($value["camera_no"]);
			}else{
				if( $value["machine_no"] == $self){
					$ret[$value["camera_no"]] = $template->formatNoBasic($value["camera_no"]);
				}
			}
		}
		
	}else{
		foreach( $arr as $value){
			if( $value["machine_no"] <= 0) $ret[$value["camera_no"]] = $template->formatNoBasic($value["camera_no"]);
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
	unset($rs);
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
			->where()
				->and(false, "del_flg != ", "1", FD_NUM)
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row["corner_no"]] = $row["corner_name"];
	}
	unset($rs);
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
			->and(false, "del_flg != ", "1", FD_NUM)
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		if( $addclass){
			$arr[ $row["model_no"]] = array( "value" => $row["model_name"], "class" => "category".$row["category"] ." maker".$row["maker_no"]);
		}else{
			$arr[ $row["model_no"]] = $row["model_name"];
		}
	}
	unset($rs);
	return $arr;
}

/**
 * ポイント変換マスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getConvertList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("convert_no, convert_name")
			->from("mst_convertPoint")
			->and(false, "del_flg != ", "1", FD_NUM)
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row["convert_no"]] = $row["convert_name"];
	}
	unset($rs);
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
			//if( isset(
			if( $arr[$key] != ""){
				return true;
			}
		}
	}
	return false;
}


/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	// カメラ重複
	$sqlCameraDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_machine")
		->where()
			->and( "camera_no = ", $_POST["CAMERA_NO"], FD_NUM)
			->and( "del_flg != ", "1", FD_NUM)
			->and( true, "machine_no <> ", $_POST["NO"], FD_NUM)
	->createSql("\n");

	// 使用中実機
	$sqlUseMachine = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("lnk_machine")
		->where()
			->and("machine_no = ", (($_GET["ACT"] != "del") ? $_POST["NO"] : $_GET["NO"]), FD_NUM)
			->and( "assign_flg = ", "1", FD_NUM)
	->createSql("\n");

	// 実機CD重複
	$sqlCdDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_machine")
		->where()
			->and( "machine_cd = ", $_POST["MACHINE_CD"], FD_STR)
			->and( "del_flg != ", "1", FD_NUM)
			->and( true, "machine_no <> ", $_POST["NO"], FD_NUM)
	->createSql("\n");

	if ($_GET["ACT"] != "del") {
		// 設定値リスト取得
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("setting_list")
			->from("mst_model")
			->where()
				->and( "model_no = ", $_POST["MODEL_NO"], FD_NUM)
				->and( "del_flg != ", "1", FD_NUM)
		->createSql("\n");
		$settingList = $template->DB->getOne($sql);

		$errMessage = (new SmartAutoCheck($template))
			->item($_POST["NO"])					//-- 実機
				->any()
				->countSQL("A1819", $sqlUseMachine)		// 使用中
			->item($_POST["MACHINE_CD"])			//-- 実機CD
				->required("A1821")						// 必須
				->alnum("A1822", 3)						// 半角英数字(-_も可)
				->maxLength("A1823", 20)				// 最大桁
				->countSQL("A1824", $sqlCdDupli)	// 重複
			->item($_POST["OWNER_NO"])				//-- オーナ
				->required("A1801")						// 必須
			->item($_POST["CAMERA_NO"])				//-- カメラ
				->required("A1802")						// 必須
				->countSQL("A1818", $sqlCameraDupli)	// 重複
			->item($_POST["MODEL_NO"])				//-- 機種 2020/04/24 [UPD]
				->required("A1803")						// 必須
				->case(mb_strlen($settingList) > 0)			//-- 設定値
					->if("A1825", mb_strlen($_POST["SETTING"]) > 0)	// 未設定
					->if("A1826", in_array($_POST["SETTING"], explode(",", $settingList)))
			->item($_POST["CONVERT_NO"])			//-- 変換レート
				->required("A1804")						// 必須 2020/04/24 [ADD]
			->item($_POST["SIGNALING_ID"])			//-- シグナル
				->required("A1805")						// 必須
			->item($_POST["RELEASE_DATE"])			//-- 公開開始日
				->required("A1807")						// 必須
				->date("A1808")							// 型
			->item($_POST["END_DATE"])				//-- 公開終了日 2020/04/24 [UPD]
				->any()
				->date("A1810")							// 型
		->report();
	// 2020/04/24 [ADD Start]
		if (count($errMessage) <= 0) {
			// 開始終了整合チェック
			if (mb_strlen($_POST["END_DATE"]) > 0) {
				if (strtotime($_POST["RELEASE_DATE"]) > strtotime($_POST["END_DATE"])) $errMessage[] = $template->message("A1817");
			}
		}
	} else {
		$errMessage = (new SmartAutoCheck($template))
			->item($_GET["NO"])						//-- 実機
				->countSQL("A1820", $sqlUseMachine)		// 使用中
		->report();
	}
	// 2020/04/24 [ADD End]
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

/**
 * 機種台数更新
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	string	$ownNo		オーナーNO
 * @return	なし
 */
function UpdMachineCount($template, $ownNo) {
	if (mb_strlen($ownNo) <= 0) return;		// オーナーNO未指定はそのままreturn

	// mst_owner 登録台数更新
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update("mst_owner mo" )
			->set()
				->value("mo.machine_count", "(select count(*) from dat_machine dm where dm.owner_no = ". $template->DB->conv_sql($ownNo, FD_NUM) ." and dm.del_flg != 1)", FD_FUNCTION)
				->value("mo.upd_no"       , $template->Session->AdminInfo["admin_no"], FD_NUM)
				->value("mo.upd_dt"       , "current_timestamp", FD_FUNCTION)
			->where()
				->and("mo.owner_no =" , $ownNo, FD_NUM)
		->createSQL();
	$template->DB->exec($sql);
}

?>
