<?php
/*
 * owner.php
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
 * オーナー管理画面表示
 * 
 * オーナー管理画面の表示を行う
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
		// 管理系表示コントロールのインスタンス生成
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
				
			case "sales":			// 売上画面
				$mainWin = false;
				DispSales($template);
				break;
			case "csv":				// CSVダウンロード
				$mainWin = false;
				DispCSV($template);
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
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "owner_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->where()
				->and( "ow.del_flg <> ", 1, FD_NUM )
			->from("mst_owner ow")
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// 2020/04/22 [UPD Start] 無駄な外結削除
	$rsql = $sqls
			->resetField()
			->field("ow.owner_no, ow.owner_cd, ow.owner_name, ow.owner_nickname, ow.owner_pref, ow.mail, ow.machine_count, ow.dummy_flg, ow.del_flg, ow.upd_dt")
			->where()
				->and( "ow.del_flg <> ", 1, FD_NUM )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	// 2020/04/22 [UPD End] 無駄な外結削除
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		
		$template->assign("OWNER_NO_PAD"       , $template->formatNoBasic($row["owner_no"]), true);		// 2020/04/22 [UPD]
		$template->assign("OWNER_NO"           , $row["owner_no"], true);
		$template->assign("OWNER_CD"           , $row["owner_cd"], true);
		$template->assign("OWNER_NAME"         , $row["owner_name"], true);
		$template->assign("OWNER_NICKNAME"     , $row["owner_nickname"], true);
		$template->assign("OWNER_PREF_LABEL"   , $template->getArrayValue($GLOBALS["prefList"], $row["owner_pref"]), true);
		$template->assign("MAIL"               , $row["mail"], true);
		$template->assign("MACHINE_COUNT"      , $row["machine_count"], true);
		$template->assign("DUMMY_FLG"          , $template->getArrayValue($GLOBALS["dummyFlgStatus"], $row["dummy_flg"]), true);
		$template->assign("UPD_DT"             , $row["upd_dt"], true);
		
		$template->if_enable("EXISTS_LIST"   , $row["machine_count"] > 0);		// 2020/04/22 [UPD]
		$template->if_enable("NO_EXISTS_LIST", $row["machine_count"] == 0);		// 2020/04/22 [UPD]
		
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
	getData($_GET , array("NO", "ACT"));	// 2020/04/22 [UPD]
	getData($_POST , array("OWNER_NO", "OWNER_CD", "OWNER_NAME", "OWNER_NICKNAME", "OWNER_PREF", "MAIL", "DUMMY_FLG", "REMARKS"
							, "MACHINE_COUNT", "ADD_DT", "UPD_DT"
						));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 || $_GET["ACT"] == "del"){		// 2020/04/22 [UPD] 初回表示若しくは削除チェックエラー時に修正
			$_load = true;
		}else{
			$_load = false;
		}
	}else{
		$_load = false;
	}
	
	if( $_load ){
		
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("ow.owner_no, ow.owner_cd, ow.owner_name, ow.owner_nickname, ow.owner_pref, ow.mail, ow.machine_count, ow.remarks, ow.dummy_flg, ow.del_flg, ow.add_dt, ow.upd_dt")
				->from("mst_owner ow" )
				->where()
					->and( "ow.owner_no = ",   $_GET["NO"], FD_NUM )
					->and( "ow.del_flg != ", "1", FD_NUM)	// 2020/04/22 [ADD]
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		// 2020/04/22 [ADD Start] 再表示時に消える項目
		if (empty($row["owner_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$row["add_dt"] = format_datetime($row["add_dt"]);
		$row["upd_dt"] = format_datetime($row["upd_dt"]);
		// 2020/04/22 [ADD End] 再表示時に消える項目
	}else{
		//POST or 新規
		$row["owner_no"]       = $_POST["OWNER_NO"];
		$row["owner_cd"]       = $_POST["OWNER_CD"];
		$row["owner_name"]     = $_POST["OWNER_NAME"];
		$row["owner_nickname"] = $_POST["OWNER_NICKNAME"];
		$row["owner_pref"]     = $_POST["OWNER_PREF"];
		$row["mail"]           = $_POST["MAIL"];
		$row["dummy_flg"]      = $_POST["DUMMY_FLG"];
		$row["remarks"]        = $_POST["REMARKS"];
		// 2020/04/22 [ADD Start] 再表示時に消える項目
		$row["machine_count"]  = $_POST["MACHINE_COUNT"];
		$row["add_dt"]         = $_POST["ADD_DT"];
		$row["upd_dt"]         = $_POST["UPD_DT"];
		// 2020/04/22 [ADD End] 再表示時に消える項目
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"          , mb_strlen($message) > 0);
	$template->if_enable("CAN_DEL"         , mb_strlen($row["owner_no"]) > 0 && (int)$row["machine_count"] <= 0);		// 2020/04/22 [ADD]
	$template->assign("NO"                 , $row["owner_no"], true);
	$template->assign("OWNER_NO_PAD"       , $template->formatNoBasic($row["owner_no"]), true);		// 2020/04/22 [ADD]
	$template->assign("OWNER_NO"           , $row["owner_no"], true);
	$template->assign("OWNER_CD"           , $row["owner_cd"], true);
	$template->assign("OWNER_NAME"         , $row["owner_name"], true);
	$template->assign("OWNER_NICKNAME"     , $row["owner_nickname"], true);
	$template->assign("SEL_OWNER_PREF"     , makeOptionArray( $GLOBALS["prefList"],  $row["owner_pref"], true));	// 2020/04/24 [UPD]
	$template->assign("MAIL"               , $row["mail"], true);
	$template->assign("MACHINE_COUNT"      , $row["machine_count"], true);
	$template->assign("DSP_MACHINE_COUNT"  , number_formatEx($row["machine_count"]), true);		// 2020/04/22 [ADD]
	$template->assign("RDO_DUMMY_FLG"      , makeRadioArray( $GLOBALS["dummyFlgStatus"], "DUMMY_FLG", $row["dummy_flg"]));
	$template->assign("REMARKS"            , $row["remarks"], true);
	$template->assign("ADD_DT"             , $row["add_dt"], true);
	$template->assign("UPD_DT"             , $row["upd_dt"], true);
	
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
	getData($_GET , array("ACT", "NO"));
	getData($_POST , array("OWNER_NO", "OWNER_CD", "OWNER_NAME", "OWNER_NICKNAME", "OWNER_PREF", "MAIL", "DUMMY_FLG", "REMARKS"
							, "MACHINE_COUNT", "ADD_DT", "UPD_DT"
						));
	
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
			->update( "mst_owner" )
				->set()
					->value( "del_flg"          , 1, FD_NUM)
					->value( "del_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"           , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "owner_no ="         , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}else{
		
		if (mb_strlen($_POST["OWNER_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_owner" )
					->set()
						->value( "owner_cd"        , $_POST["OWNER_CD"], FD_STR)
						->value( "owner_name"      , $_POST["OWNER_NAME"], FD_STR)
						->value( "owner_nickname"  , $_POST["OWNER_NICKNAME"], FD_STR)
						->value( "owner_pref"      , $_POST["OWNER_PREF"], FD_NUM)
						->value( "mail"            , $_POST["MAIL"], FD_STR)
						->value( "dummy_flg"       , $_POST["DUMMY_FLG"], FD_NUM)
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "owner_no =" , $_POST["OWNER_NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}else{
			
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_owner" )
						->value( "owner_cd"        , $_POST["OWNER_CD"], FD_STR)
						->value( "owner_name"      , $_POST["OWNER_NAME"], FD_STR)
						->value( "owner_nickname"  , $_POST["OWNER_NICKNAME"], FD_STR)
						->value( "owner_pref"      , $_POST["OWNER_PREF"], FD_NUM)
						->value( "mail"            , $_POST["MAIL"], FD_STR)
						->value( "dummy_flg"       , $_POST["DUMMY_FLG"], FD_NUM)
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
						->value( "add_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($sql);
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
			$title = $template->message("A1562");
			$msg = $template->message("A1563");
			break;
		case "del":
			// 削除
			$title = $template->message("A1564");
			$msg = $template->message("A1565");
			break;
		default:
			// 新規登録
			$title = $template->message("A1560");
			$msg = $template->message("A1561");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}


/**
 * 売上画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispSales($template, $message = "") {
	// データ取得
	getData($_GET , array("NO"));
	
	if( mb_strlen($_GET["NO"]) <= 0){
		$template->dispProcError( $template->message("A0099"), false);		// 2020/04/23 [UPD]
		exit();
	}
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("ow.owner_no, ow.owner_cd, ow.owner_name, ow.owner_nickname, ow.owner_pref, ow.mail, ow.machine_count, ow.remarks, ow.dummy_flg, ow.del_flg, ow.add_dt, ow.upd_dt")
			->from("mst_owner ow" )
			->where()
				->and( "ow.owner_no = ",   $_GET["NO"], FD_NUM )
				->and( "ow.del_flg != ", "1", FD_NUM)	// 2020/04/22 [ADD]
		->createSql();
	$owner = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
	// 2020/04/22 [ADD Start] 再表示時に消える項目
	if (mb_strlen($owner["owner_no"]) <= 0) {		// データ不存在は通常あり得ないのでシステムエラー
		$template->dispProcError($template->message("A0003"), false);
		return;
	}

	// 2020/04/22 [ADD Start] 現在 若しくは プレイ履歴が存在する実機を取得
	// 現在保有の実機NO取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("machine_no")
		->from("dat_machine")
		->where()
			->and( "owner_no = ", $_GET["NO"], FD_NUM )
			->and( "del_flg != ", "1", FD_NUM)
		->createSql();
	$grtRow = $template->DB->getAll($sql, PDO::FETCH_ASSOC);
	$holdMachineNO = array_column($grtRow, "machine_no");

	// プレイ履歴が存在する実機NO取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("machine_no")
		->from("his_play")
		->where()
			->and( "owner_no = ", $_GET["NO"], FD_NUM )
		->groupby( "machine_no")
		->createSql();
	$grtRow = $template->DB->getAll($sql, PDO::FETCH_ASSOC);
	$playMachineNO = array_column($grtRow, "machine_no");

	// 実機NOをユニークに取得
	$playMachineNO = array_flip($holdMachineNO)+array_flip($playMachineNO);
	// 2020/04/22 [ADD End] 現在 若しくは プレイ履歴が存在する実機を取得


	// 2020/04/22 [UPD Start] プレイ履歴の存在する実機では無く現在 若しくは プレイ履歴が存在するデータの取得にする
	$row = array();
	if (count($playMachineNO) > 0) {
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dm.machine_no, IFNULL(SUM(hp.play_count), 0) as machine_play_count")
				->field("dm.release_date, dm.end_date")
				->field("mm.model_name")
			->from("dat_machine dm")
			->from("inner join mst_model mm on mm.model_no = dm.model_no")
			->from("left join his_play hp on dm.machine_no = hp.machine_no and hp.owner_no = " . $template->DB->conv_sql($owner["owner_no"], FD_NUM))
			->where()
				->and("dm.machine_no in", array_keys($playMachineNO), FD_NUM)
			->groupby("dm.machine_no")
			->orderby("mm.model_no, dm.machine_no")
			->createSql("\n");
		$row = $template->DB->getAll( $sql, MDB2_FETCHMODE_ASSOC);
	}
	// 2020/04/22 [UPD End] プレイ履歴の存在する実機では無く現在 若しくは プレイ履歴が存在するデータの取得にする
	$total_play_count = array_sum( array_column( $row, 'machine_play_count'));
	
	// 2020/04/22 [UPD Start] 0件時Worningを吐くので修正
	$year  = date("Y");
	$month = date("m");
	$old = $year;
	if (count($row) > 0) $old = min(array_column( $row, 'release_date'));
	// 2020/04/22 [UPD End] 0件時Worningを吐くので修正

	
	// 画面表示開始
	$template->open(PRE_HTML . "_sales.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// オーナー情報表示
	$template->assign("OWNER_NO"           , $owner["owner_no"], true);
	$template->assign("OWNER_CD"           , $owner["owner_cd"], true);
	$template->assign("OWNER_NAME"         , $owner["owner_name"], true);
	$template->assign("TOTAL_PLAY_COUNT"   , number_formatEx($total_play_count), true);
	// リスト処理
	$template->loop_start("LIST");
	foreach( $row as $v){
		$template->assign("DSP_MACHINE_NO" , $template->formatNoBasic($v["machine_no"]), true);	// 2020/04/23 [ADD]
		$template->assign("MODEL_NAME"     , $v["model_name"], true);
		$template->assign("RELEASE_DATE"   , format_date($v["release_date"]), true);	// 2020/04/23 [UPD]
		$template->assign("END_DATE"       , format_date($v["end_date"]), true);		// 2020/04/23 [UPD]
		$template->loop_next();
	}
	$template->loop_end("LIST");
	//CSV
	$template->assign("SEL_YEAR"           , makeOptionArray( createYearList( (int)substr($old, 0, 4)), (int)$year, false));
	$template->assign("SEL_MONTH"          , makeOptionArray( createMonthList(),  (int)$month, false));	// 2020/04/24 [UPD]

	// 2020/04/23 [ADD Start]
	// 表示制御
	$template->if_enable("EXIST_MACHINE", count($row) > 0);		// 実機存在
	$template->if_enable("NONE_MACHINE" , count($row) <= 0);	// 実機不存在

	// 2020/04/23 [ADD End]

	// 表示
	$template->flush();
}

/**
 * CSVダウンロード
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispCSV($template, $message = "") {
	// データ取得
	getData($_POST , array("OWNER_NO", "CSV_YEAR", "CSV_MONTH"));
	
	if( mb_strlen($_POST["OWNER_NO"]) <= 0){
		$template->dispProcError( $template->message("A0099"), false);		// 2020/04/23 [UPD]
		exit();
	}
	
	$year  = ( mb_strlen( $_POST["CSV_YEAR"]) > 0)? $_POST["CSV_YEAR"]:GetRefTimeToday("", "Y");
	if( mb_strlen( $_POST["CSV_MONTH"]) > 0){
		$month = str_pad( $_POST["CSV_MONTH"], 2, 0, STR_PAD_LEFT);
		$start = $year ."-". $month . "-01";
		$end   = date('Y-m-d', strtotime('last day of ' . $start));
	}else{
		$start = $year ."-01-01";
		$end   = $year ."-12-31";
	}
	
	// 開始日～終了日までのデータを取得
	$outBuff = array();
	for ($calDate = strtotime($start); $calDate <= strtotime($end); $calDate = strtotime("+1 day", $calDate)) {
		// 取得用日付
		$stDtTime = GetRefTimeStart(date("Y-m-d", $calDate));
		$edDtTime = GetRefTimeEnd(date("Y-m-d"  , $calDate));
		// 対象取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field($template->DB->conv_sql(date("Y/m/d", $calDate), FD_DATE) . " as play_dt, hp.machine_no")
					->field("IFNULL(SUM(hp.play_count), 0) as play_count")
					->field("(IFNULL(SUM(hp.in_credit), 0) - IFNULL(SUM(hp.out_credit), 0)) as samai")
					->field("mm.model_name")
				->from("his_play hp")
					->from("inner join dat_machine dm on hp.machine_no = dm.machine_no")
					->from("inner join mst_model mm on dm.model_no = mm.model_no")
				->where()
					->and("hp.owner_no = " , $_POST["OWNER_NO"], FD_NUM )
					->and("hp.in_credit >= " , "1", FD_NUM )
					->and("hp.end_dt", "between", $stDtTime, FD_DATE, $edDtTime, FD_DATE )
				->groupby("play_dt, hp.machine_no, mm.model_name")
				->orderby("play_dt, mm.model_no, dm.machine_no")
			->createSql("\n");
		$outRs = $template->DB->query($sql);
		while ($row = $outRs->fetch(MDB2_FETCHMODE_ASSOC)) {
			$outBuff[] = $row;
		}
		unset($outRs);
		unset($row);
	}
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $GLOBALS["csvOwnerSalesHeader"]) . '"');

	// 整形
	foreach($outBuff as $value) {
		$value["machine_no"] = "[" . $template->formatNoBasic($value["machine_no"]) . "] " . $value["model_name"];
		// 不要項目削除
		unset($value["model_name"]);

		$value = str_replace('"', '""', $value);
		array_push($outData, '"' . implode('","', $value) . '"');
	}

	// 出力文字列編集
	$ret = mb_convert_encoding(implode("\r\n", $outData), FILE_CSV_OUTPUT_ENCODE);
	if (CSV_OUTPUT_BOM_ENC == FILE_CSV_OUTPUT_ENCODE && CSV_OUTPUT_SET_BOM) $ret = pack('C*',0xEF,0xBB,0xBF) . $ret;	// BOMを付ける

	// CSV用出力設定
	header('Cache-Control: public');
	header('Pragma: public');	// キャッシュを制限しない設定にする
	header("Content-Disposition: attachment; filename=OwnerSales_". $template->formatNoBasic($_POST["OWNER_NO"]) ."_".date("YmdHis").".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	//-- 検索SQL生成
	// ニックネーム重複
	$sqlNkNameDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_owner" )
		->where()
			->and( "owner_nickname = ", $_POST["OWNER_NICKNAME"], FD_STR)
			->and( "del_flg != ", "1", FD_NUM)
			->and( true, "owner_no <> ", $_POST["OWNER_NO"], FD_NUM)
	->createSql();
	// 実機存在
	$sqlExtMachine = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "dat_machine" )
		->where()
			->and( "owner_no = ", $_GET["NO"], FD_NUM)
			->and( "del_flg != ", "1", FD_NUM)
	->createSql();

	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			
			//オーナーCD
			->item($_POST["OWNER_CD"])
				->required("A1501")
				->maxLength("A1502", 20)					//文字長の最高値
			//オーナー名
			->item($_POST["OWNER_NAME"])
				->any()
				->maxLength("A1506", 20)					//文字長の最高値
			//ニックネーム
			->item($_POST["OWNER_NICKNAME"])
				->required("A1505")
				->maxLength("A1506", 20)					//文字長の最高値
				->countSQL("A1509", $sqlNkNameDupli)		// 重複
			//メールアドレス
			->item($_POST["MAIL"])
				->required("A1507")
				->mail("A1508")
				->maxLength("A1508", 255)					//文字長の最高値
			
		->report();
		
	} else {
		$errMessage = (new SmartAutoCheck($template))
			//オーナーCD
			->item($_POST["OWNER_CD"])
			->countSQL("A1510", $sqlExtMachine)		// 実機存在
		->report();
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


function createYearList( $_start = 2019){
	$ret = array();
	$_end   = (int)date("Y");
	for( $i=$_start; $i<=$_end; $i++){
		$ret[$i] = $i;
	}
	return $ret;
}

function createMonthList(){
	$ret = array();
	for( $i=1; $i<=12; $i++){
		$ret[$i] = $i;
	}
	return $ret;
}


?>
