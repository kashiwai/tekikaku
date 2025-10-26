<?php
/*
 * coupon.php
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
 * クーポン管理画面表示
 * 
 * クーポン管理画面の表示を行う
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
	getData( $_GET  , array( "P", "ODR"));
	getData($_GET, array("S_PLAN_DT_FROM", "S_PLAN_DT_TO", "S_GRANT_DT_FROM", "S_GRANT_DT_TO", "S_COUPON_STATUS"	// 2020/04/30 [ADD]
						));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "coupon_no desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//検索判定
	if( checkKeys( $_GET, array("S_PLAN_DT_FROM", "S_PLAN_DT_TO", "S_GRANT_DT_FROM", "S_GRANT_DT_TO", "S_COUPON_STATUS"))){
		$_search = "show";	//表示用クラス名
	}else{
		$_search = "";
	}
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "count(*)" )
					->from("dat_coupon dc")
					->from("inner join dat_coupon_lang dcl on dcl.coupon_no = dc.coupon_no and dcl.lang = \"". FOLDER_LANG ."\"")
					->where()
						->and( "dc.del_flg <> ", 1, FD_NUM)
						//検索
						->and( true, "dc.plan_dt >= "     , [$_GET["S_PLAN_DT_FROM"] , " 00:00:00"], FD_DATEEX )
						->and( true, "dc.plan_dt <= "     , [$_GET["S_PLAN_DT_TO"]   , " 23:59:59"], FD_DATEEX )
						->and( true, "dc.grant_dt >= "    , [$_GET["S_GRANT_DT_FROM"], " 00:00:00"], FD_DATEEX )
						->and( true, "dc.grant_dt <= "    , [$_GET["S_GRANT_DT_TO"]  , " 23:59:59"], FD_DATEEX )
						->and( true, "dc.coupon_state = " , $_GET["S_COUPON_STATUS"], FD_NUM)
			->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls->resetField()
				->field("dc.*")
				->field("dcl.title, dcl.contents")
				->orderby( $_GET["ODR"] )
				->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("S_OPEN"                , $_search, true);
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	//検索
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_PLAN_DT_FROM"      , $_GET["S_PLAN_DT_FROM"], true);
	$template->assign("S_PLAN_DT_TO"        , $_GET["S_PLAN_DT_TO"], true);
	$template->assign("S_GRANT_DT_FROM"     , $_GET["S_GRANT_DT_FROM"], true);
	$template->assign("S_GRANT_DT_TO"       , $_GET["S_GRANT_DT_TO"], true);
	$template->assign("SEL_COUPON_STATUS"   , makeOptionArray( $GLOBALS["couponGrantStatusList"],  $_GET["S_COUPON_STATUS"], true));	// 2020/04/30 [UPD]
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->assign("COUPON_NO_PAD"     , $template->formatNoBasic($row["coupon_no"]), true);		// 2020/04/30 [UPD]
		$template->assign("COUPON_NO"         , $row["coupon_no"], true);
		$template->assign("TITLE"             , $row["title"], true);
		$template->assign("POINT"             , number_formatEx( $row["point"]), true);
		$limitDate = (($row["limit_days"]==0) ? ""
							: (($row["coupon_state"] == 1) 
								? date('Y-m-d H:i:00', strtotime("+" . $row["limit_days"] . " day" , strtotime($row["grant_dt"])))
								: date('Y-m-d H:i:00', strtotime("+" . $row["limit_days"] . " day" , strtotime($row["plan_dt"])))));
		$template->assign("LIMIT_DAYS"        , format_datetime($limitDate), true);
		$template->assign("COUNT"             , ($row["coupon_state"] == 1)? number_format( $row["grant_count"]): number_format( $row["plan_count"]) , true);
		$template->assign("PLAN_DT"           , format_datetime($row["plan_dt"]), true);
		$template->assign("GRANT_DT"          , format_datetime($row["grant_dt"]), true);
		$template->assign("STATUS_LABEL"      , $GLOBALS["couponGrantStatusList"][$row["coupon_state"]], true);
		//
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
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	
	// データ取得
	getData( $_GET ,  array( "NO", "ACT"));
	getData( $_POST , array( "NO", "POINT", "LIMIT_DAYS", "GRANT_TIMING", "PLAN_DT_DATE", "PLAN_DT_HR", "PLAN_DT_MIN"	// 2020/04/30 [UPD]
							,"COND_MEMBER_NO", "COND_SEX", "COND_BMONTH", "COND_POINT_FROM", "COND_POINT_TO", "COND_DRAW_POINT_FROM", "COND_DRAW_POINT_TO", "COND_JOIN_FROM", "COND_JOIN_TO"
							,"COND_LOGIN_FROM", "COND_LOGIN_TO", "COND_PLAY_COUNT_FROM", "COND_PLAY_COUNT_TO", "COND_PLAY_DT_FROM", "COND_PLAY_DT_TO", "COUPON_STATUS"
							,"COND_PURCHASE_COUNT_FROM", "COND_PURCHASE_COUNT_TO", "COND_PURCHASE_AMOUNT_FROM", "COND_PURCHASE_AMOUNT_TO", "COND_PURCHASE_DT_FROM", "COND_PURCHASE_DT_TO"));
	if (empty($_POST["COND_PURCHASE_TYPE"])) $_POST["COND_PURCHASE_TYPE"] = array();
	//既存表示
	if( mb_strlen( $_GET["NO"]) > 0){
		
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("dc.*" )
					->from("dat_coupon dc")
					->where()
						->and( "dc.coupon_no = ", $_GET["NO"], FD_NUM)
						->and( "dc.del_flg != ", "1", FD_NUM)
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		// 2020/04/30 [ADD Start]
		if (empty($row["coupon_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		// 2020/04/30 [ADD End]
		//
		$purchase_type_list = array();
		foreach( explode(",", $row["cond_purchase_type"]) as $v){
			$purchase_type_list[ $v] = $v;
		}
		//データセット
		$_POST["COUPON_STATUS"]        = $row["coupon_state"];
		$_POST["POINT"]                = $row["point"];
		$_POST["LIMIT_DAYS"]           = $row["limit_days"];
		$_POST["PLAN_COUNT"]           = $row["plan_count"];
		$_POST["PLAN_DT_DATE"]         = (mb_strlen( $row["plan_dt"]) > 0)? explode(" ", $row["plan_dt"])[0]:"";
		$_POST["PLAN_DT_HR"]           = (mb_strlen( $row["plan_dt"]) > 0)? explode(":", explode(" ", $row["plan_dt"])[1])[0]:"";
		$_POST["PLAN_DT_MIN"]          = (mb_strlen( $row["plan_dt"]) > 0)? explode(":", explode(" ", $row["plan_dt"])[1])[1]:"";
		$_POST["COND_MEMBER_NO"]       = $row["cond_member_no"];
		$_POST["COND_SEX"]             = $row["cond_sex"];
		$_POST["COND_BMONTH"]          = $row["cond_bmonth"];
		$_POST["COND_POINT_FROM"]      = $row["cond_point_from"];
		$_POST["COND_POINT_TO"]        = $row["cond_point_to"];
		$_POST["COND_DRAW_POINT_FROM"] = $row["cond_draw_point_from"];
		$_POST["COND_DRAW_POINT_TO"]   = $row["cond_draw_point_to"];
		$_POST["COND_JOIN_FROM"]       = $row["cond_join_from"];
		$_POST["COND_JOIN_TO"]         = $row["cond_join_to"];
		$_POST["COND_LOGIN_FROM"]      = $row["cond_login_from"];
		$_POST["COND_LOGIN_TO"]        = $row["cond_login_to"];
		$_POST["COND_PLAY_COUNT_FROM"] = $row["cond_play_count_from"];
		$_POST["COND_PLAY_COUNT_TO"]   = $row["cond_play_count_to"];
		$_POST["COND_PLAY_DT_FROM"]    = $row["cond_play_dt_from"];
		$_POST["COND_PLAY_DT_TO"]      = $row["cond_play_dt_to"];
		$_POST["COND_PURCHASE_TYPE"]       = $purchase_type_list;
		$_POST["COND_PURCHASE_COUNT_FROM"] = $row["cond_purchase_count_from"];
		$_POST["COND_PURCHASE_COUNT_TO"]   = $row["cond_purchase_count_to"];
		$_POST["COND_PURCHASE_AMOUNT_FROM"] = $row["cond_purchase_amount_from"];
		$_POST["COND_PURCHASE_AMOUNT_TO"]   = $row["cond_purchase_amount_to"];
		$_POST["COND_PURCHASE_DT_FROM"]     = $row["cond_purchase_dt_from"];
		$_POST["COND_PURCHASE_DT_TO"]       = $row["cond_purchase_dt_to"];
		$_POST["NO"]                        = $row["coupon_no"];		// 2020/04/30 [ADD]
		//
		$_POST["GRANT_TIMING"]         = (mb_strlen( $row["plan_dt"]) > 0)? 1:0;
		
		//lang
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("dcl.lang, dcl.title, dcl.contents" )
					->from("dat_coupon_lang dcl")
					->where()
						->and( "dcl.coupon_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$langrow = $template->DB->getAll( $sql, MDB2_FETCHMODE_ASSOC);
		foreach( $langrow as $v){
			$_POST["TITLE"][$v["lang"]]    = $v["title"];
			$_POST["CONTENTS"][$v["lang"]] = $v["contents"];
		}
	}
	
	//
	$_count_show = false;
	$hitCount = "";
	if( $_GET["ACT"] == "check" && mb_strlen($message) <= 0){	// 2020/04/30 [UPD]
		$_count_show = true;
		//検索SQL
		$rows = $template->DB->getMemberRows( $_POST, false);	// 2020/05/01/[UPD]
		//
		$hitCount = count( $rows);
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	// 2020/04/30 [ADD Start] 共通置換される前に置換
	$defLangName = $GLOBALS["langList"][FOLDER_LANG]["names"][array_search(FOLDER_LANG, array_column($GLOBALS["langList"][FOLDER_LANG]["names"], 'lang'))]["name"];
	$template->assign("A2507", $template->message("A2507", $defLangName), true);
	// 2020/04/30 [ADD End] 共通置換される前に置換
	$template->assignCommon();
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("NOGRANT", ($_POST["COUPON_STATUS"] == 0 || mb_strlen($_POST["COUPON_STATUS"]) <= 0));
	$template->if_enable("EDIT"   , mb_strlen($_POST["NO"]) > 0);
	
	$template->assign("ERRMSG"  , $message);
	$template->assign("DEF_LANG", FOLDER_LANG);
	$template->assign("NO"      , $_POST["NO"]);
	$template->assign("COUPON_STATUS", $_POST["COUPON_STATUS"]);
	
	// 言語タブ処理
	$template->loop_start("LANG_LIST_TAB");
	foreach( $setLangList as $k=>$v){
		$template->assign("ACTIVE", (($k==0)?"active":""));
		$template->assign("INDEX",  ($k+1));
		$template->assign("LANGAGE",$v["name"], true);
		$template->loop_next();
	}
	$template->loop_end("LANG_LIST_TAB");
	
	// 言語タブ処理
	$template->loop_start("LANG_LIST");
	foreach( $setLangList as $k=>$v){
		$template->assign("SHOW_IN", (($k==0)?"show in":""));
		$template->assign("ACTIVE",  (($k==0)?"active":""));
		$template->assign("INDEX",   ($k+1));
		$template->assign("LANG",    $v["lang"], true);
		$template->assign("TITLE",   (isset($_POST["TITLE"][$v["lang"]]) ? $_POST["TITLE"][$v["lang"]] : ""), true);
		$template->assign("CONTENTS",(isset($_POST["CONTENTS"][$v["lang"]]) ? $_POST["CONTENTS"][$v["lang"]] : ""), true);
		$template->if_enable("IS_DEF_LANG", $v["lang"] == FOLDER_LANG);
		//
		$template->loop_next();
	}
	$template->loop_end("LANG_LIST");
	
	// 付与情報
	$template->if_enable("COUNT"            , $_count_show);
	$template->assign("COUNT"               , $hitCount, true);
	$template->assign("POINT"               , $_POST["POINT"], true);
	$template->assign("LIMIT_DAYS"          , $_POST["LIMIT_DAYS"], true);
	$template->assign("RDO_GRANT_TIMING"    , makeRadioArray($GLOBALS["couponGrantList"], "GRANT_TIMING", $_POST["GRANT_TIMING"]));
	$template->assign("PLAN_DT_DATE"        , $_POST["PLAN_DT_DATE"], true);
	$template->assign("SEL_PLAN_DT_HR"      , makeSelectHourTag($_POST["PLAN_DT_HR"]));					// 2020/04/30 [UPD]
	$template->assign("SEL_PLAN_DT_MIN"     , makeSelectMinuteTag($_POST["PLAN_DT_MIN"], MINUTE_SPAN));	// 2020/04/30 [UPD]
	// 付与条件
	$template->assign("COND_MEMBER_NO"      , $_POST["COND_MEMBER_NO"], true);
	$template->assign("SEL_SEX"             , makeOptionArray( $GLOBALS["SexList"] , $_POST["COND_SEX"], true));
	$template->assign("SEL_BMONTH"          , makeSelectMonthTag($_POST["COND_BMONTH"], true));	// 2020/04/30 [UPD]
	$template->assign("COND_POINT_FROM"     , $_POST["COND_POINT_FROM"], true);
	$template->assign("COND_POINT_TO"       , $_POST["COND_POINT_TO"], true);
	$template->assign("COND_DRAW_POINT_FROM", $_POST["COND_DRAW_POINT_FROM"], true);
	$template->assign("COND_DRAW_POINT_TO"  , $_POST["COND_DRAW_POINT_TO"], true);
	$template->assign("COND_JOIN_FROM"      , $_POST["COND_JOIN_FROM"], true);
	$template->assign("COND_JOIN_TO"        , $_POST["COND_JOIN_TO"], true);
	$template->assign("COND_LOGIN_FROM"     , $_POST["COND_LOGIN_FROM"], true);
	$template->assign("COND_LOGIN_TO"       , $_POST["COND_LOGIN_TO"], true);
	$template->assign("COND_PLAY_COUNT_FROM", $_POST["COND_PLAY_COUNT_FROM"], true);
	$template->assign("COND_PLAY_COUNT_TO"  , $_POST["COND_PLAY_COUNT_TO"], true);
	$template->assign("COND_PLAY_DT_FROM"   , $_POST["COND_PLAY_DT_FROM"], true);
	$template->assign("COND_PLAY_DT_TO"     , $_POST["COND_PLAY_DT_TO"], true);
	// 付与条件 支払い系
	$template->assign("RDO_PURCHASE_TYPE"   , makeCheckBoxArray( $GLOBALS["viewPurchaseType"], "COND_PURCHASE_TYPE", $_POST["COND_PURCHASE_TYPE"], 2));
	$template->assign("COND_PURCHASE_COUNT_FROM", $_POST["COND_PURCHASE_COUNT_FROM"], true);
	$template->assign("COND_PURCHASE_COUNT_TO"  , $_POST["COND_PURCHASE_COUNT_TO"], true);
	$template->assign("COND_PURCHASE_AMOUNT_FROM", $_POST["COND_PURCHASE_AMOUNT_FROM"], true);
	$template->assign("COND_PURCHASE_AMOUNT_TO"  , $_POST["COND_PURCHASE_AMOUNT_TO"], true);
	$template->assign("COND_PURCHASE_DT_FROM"    , $_POST["COND_PURCHASE_DT_FROM"], true);
	$template->assign("COND_PURCHASE_DT_TO"      , $_POST["COND_PURCHASE_DT_TO"], true);
	
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
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	
	// データ取得
	getData( $_GET ,  array( "NO", "ACT"));
	getData( $_POST , array( "NO", "POINT", "LIMIT_DAYS", "GRANT_TIMING", "PLAN_DT_DATE", "PLAN_DT_HR", "PLAN_DT_MIN"	// 2020/04/30 [UPD]
							,"COND_MEMBER_NO", "COND_SEX", "COND_BMONTH", "COND_POINT_FROM", "COND_POINT_TO", "COND_DRAW_POINT_FROM", "COND_DRAW_POINT_TO", "COND_JOIN_FROM", "COND_JOIN_TO"
							,"COND_LOGIN_FROM", "COND_LOGIN_TO", "COND_PLAY_COUNT_FROM", "COND_PLAY_COUNT_TO", "COND_PLAY_DT_FROM", "COND_PLAY_DT_TO"
							,"COND_PURCHASE_COUNT_FROM", "COND_PURCHASE_COUNT_TO", "COND_PURCHASE_AMOUNT_FROM", "COND_PURCHASE_AMOUNT_TO", "COND_PURCHASE_DT_FROM", "COND_PURCHASE_DT_TO"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	if( $_GET["ACT"] == "check"){
		DispDetail($template, $message);
		return;
	}
	
	$now   = date("Y/m/d H:i:s");	// 2020/04/30 [UPD]
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "dat_coupon" )
				->set()
					->value( "del_flg"           , 1, FD_NUM)
					->value( "del_no"            , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"            , "current_timestamp", FD_FUNCTION)
				->where()
					->and( false, "coupon_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
		
	}else{
		
		//対象者
		$rows = $template->DB->getMemberRows( $_POST, false);	// 2020/05/01 [UPD]
		
		$grant_dt = "";
		$plan_dt = (($_POST["GRANT_TIMING"]==1)? ($_POST["PLAN_DT_DATE"]." ". ((mb_strlen($_POST["PLAN_DT_HR"])>0)?$_POST["PLAN_DT_HR"]:"00") .":". ((mb_strlen($_POST["PLAN_DT_MIN"])>0)?$_POST["PLAN_DT_MIN"]:"00") ):$now);
		if( $_POST["GRANT_TIMING"] == 0){
			$grant_dt = $plan_dt;
		}
		
		$isGrant = false;	// 即時処理 2020/05/01 [ADD]
		if( mb_strlen( $_POST["NO"]) > 0){
			//更新
			$mode = "update";
			// クーポン更新
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update("dat_coupon")
					->set()
						->value( "coupon_state"       , (($_POST["GRANT_TIMING"]==0)? 1:0), FD_NUM)
						->value( "point"              , $_POST["POINT"], FD_NUM)
						->value( "limit_days"         , $_POST["LIMIT_DAYS"], FD_NUM)
						->value( "plan_count"         , count( $rows), FD_NUM)
						->value( false, "grant_count" , (($_POST["GRANT_TIMING"]==0)? count( $rows):""), FD_NUM)
						->value( false, "plan_dt"     , (($_POST["GRANT_TIMING"]==0)? "":$plan_dt), FD_STR)
						->value( false, "grant_dt"    , (($_POST["GRANT_TIMING"]==0)? $grant_dt:""), FD_STR)
						->value( "upd_no"             , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"             , "current_timestamp", FD_FUNCTION)
						//配布条件系
						->value( false, "cond_member_no"            , $_POST["COND_MEMBER_NO"], FD_STR)
						->value( false, "cond_sex"                  , $_POST["COND_SEX"], FD_STR)
						->value( false, "cond_bmonth"               , $_POST["COND_BMONTH"], FD_STR)
						->value( false, "cond_point_from"           , $_POST["COND_POINT_FROM"], FD_NUM)
						->value( false, "cond_point_to"             , $_POST["COND_POINT_TO"], FD_NUM)
						->value( false, "cond_draw_point_from"      , $_POST["COND_DRAW_POINT_FROM"], FD_NUM)
						->value( false, "cond_draw_point_to"        , $_POST["COND_DRAW_POINT_TO"], FD_NUM)
						->value( false, "cond_join_from"            , $_POST["COND_JOIN_FROM"], FD_STR)
						->value( false, "cond_join_to"              , $_POST["COND_JOIN_TO"], FD_STR)
						->value( false, "cond_login_from"           , $_POST["COND_LOGIN_FROM"], FD_STR)
						->value( false, "cond_login_to"             , $_POST["COND_LOGIN_TO"], FD_STR)
						->value( false, "cond_play_count_from"      , $_POST["COND_PLAY_COUNT_FROM"], FD_STR)
						->value( false, "cond_play_count_to"        , $_POST["COND_PLAY_COUNT_TO"], FD_STR)
						->value( false, "cond_play_dt_from"         , $_POST["COND_PLAY_DT_FROM"], FD_STR)
						->value( false, "cond_play_dt_to"           , $_POST["COND_PLAY_DT_TO"], FD_STR)
						->value( false, "cond_purchase_type"        , ((isset($_POST["COND_PURCHASE_TYPE"]) && is_array($_POST["COND_PURCHASE_TYPE"]))
																			? implode(",", $_POST["COND_PURCHASE_TYPE"]):""), FD_STR)
						->value( false, "cond_purchase_count_from"  , $_POST["COND_PURCHASE_COUNT_FROM"], FD_STR)
						->value( false, "cond_purchase_count_to"    , $_POST["COND_PURCHASE_COUNT_TO"], FD_STR)
						->value( false, "cond_purchase_amount_from" , $_POST["COND_PURCHASE_AMOUNT_FROM"], FD_STR)
						->value( false, "cond_purchase_amount_to"   , $_POST["COND_PURCHASE_AMOUNT_TO"], FD_STR)
						->value( false, "cond_purchase_dt_from"     , $_POST["COND_PURCHASE_DT_FROM"], FD_STR)
						->value( false, "cond_purchase_dt_to"       , $_POST["COND_PURCHASE_DT_TO"], FD_STR)
					->where()
						->and( false, "coupon_no =" , $_POST["NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
			
			$couponNo = $_POST["NO"];
			foreach ( $setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->update("dat_coupon_lang")
						->set()
							->value( "title"          , $_POST["TITLE"][$lang["lang"]], FD_STR)
							->value( "contents"       , $_POST["CONTENTS"][$lang["lang"]], FD_STR)
							->value( "upd_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value( "upd_dt"         , "current_timestamp", FD_FUNCTION)
						->where()
							->and( false, "coupon_no =" , $couponNo, FD_NUM)
							->and( false, "lang ="      , $lang["lang"], FD_STR)
					->createSQL();
				$template->DB->query($sql);
			}
			
			//即時判定
			$isGrant = ($_POST["GRANT_TIMING"] == "0");
		}else{
			//新規
			$mode = "";
			// クーポン登録
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "dat_coupon" )
						->value( "coupon_state"       , (($_POST["GRANT_TIMING"]==0)? 1:0), FD_NUM)
						->value( "point"              , $_POST["POINT"], FD_NUM)
						->value( "limit_days"         , $_POST["LIMIT_DAYS"], FD_NUM)
						->value( "plan_count"         , count( $rows), FD_NUM)
						->value( false, "grant_count" , (($_POST["GRANT_TIMING"]==0)? count( $rows):""), FD_NUM)
						->value( false, "plan_dt"     , (($_POST["GRANT_TIMING"]==0)? "":$plan_dt), FD_STR)
						->value( false, "grant_dt"    , (($_POST["GRANT_TIMING"]==0)? $grant_dt:""), FD_STR)
						->value( "add_no"             , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"             , "current_timestamp", FD_FUNCTION)
						->value( "upd_no"             , $template->Session->AdminInfo["admin_no"], FD_NUM)		// 2020/05/01 [ADD]
						->value( "upd_dt"             , "current_timestamp", FD_FUNCTION)						// 2020/05/01 [ADD]
						//配布条件系
						->value( true, "cond_member_no"            , $_POST["COND_MEMBER_NO"], FD_STR)
						->value( true, "cond_sex"                  , $_POST["COND_SEX"], FD_STR)
						->value( true, "cond_bmonth"               , $_POST["COND_BMONTH"], FD_STR)
						->value( true, "cond_point_from"           , $_POST["COND_POINT_FROM"], FD_NUM)
						->value( true, "cond_point_to"             , $_POST["COND_POINT_TO"], FD_NUM)
						->value( true, "cond_draw_point_from"      , $_POST["COND_DRAW_POINT_FROM"], FD_NUM)
						->value( true, "cond_draw_point_to"        , $_POST["COND_DRAW_POINT_TO"], FD_NUM)
						->value( true, "cond_join_from"            , $_POST["COND_JOIN_FROM"], FD_STR)
						->value( true, "cond_join_to"              , $_POST["COND_JOIN_TO"], FD_STR)
						->value( true, "cond_login_from"           , $_POST["COND_LOGIN_FROM"], FD_STR)
						->value( true, "cond_login_to"             , $_POST["COND_LOGIN_TO"], FD_STR)
						->value( true, "cond_play_count_from"      , $_POST["COND_PLAY_COUNT_FROM"], FD_STR)
						->value( true, "cond_play_count_to"        , $_POST["COND_PLAY_COUNT_TO"], FD_STR)
						->value( true, "cond_play_dt_from"         , $_POST["COND_PLAY_DT_FROM"], FD_STR)
						->value( true, "cond_play_dt_to"           , $_POST["COND_PLAY_DT_TO"], FD_STR)
						->value( true, "cond_purchase_type"        , ((isset($_POST["COND_PURCHASE_TYPE"]) && is_array($_POST["COND_PURCHASE_TYPE"]))
																		? implode(",", $_POST["COND_PURCHASE_TYPE"]):""), FD_STR)
						->value( true, "cond_purchase_count_from"  , $_POST["COND_PURCHASE_COUNT_FROM"], FD_STR)
						->value( true, "cond_purchase_count_to"    , $_POST["COND_PURCHASE_COUNT_TO"], FD_STR)
						->value( true, "cond_purchase_amount_from" , $_POST["COND_PURCHASE_AMOUNT_FROM"], FD_STR)
						->value( true, "cond_purchase_amount_to"   , $_POST["COND_PURCHASE_AMOUNT_TO"], FD_STR)
						->value( true, "cond_purchase_dt_from"     , $_POST["COND_PURCHASE_DT_FROM"], FD_STR)
						->value( true, "cond_purchase_dt_to"       , $_POST["COND_PURCHASE_DT_TO"], FD_STR)
				->createSQL();
			$template->DB->query($sql);
			//
			$sql = "select last_insert_id()";
			$couponNo = $template->DB->getOne($sql);
			foreach ( $setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->insert()
						->into( "dat_coupon_lang" )
							->value( "coupon_no"      , $couponNo, FD_NUM)
							->value( "lang"           , $lang["lang"], FD_STR)
							->value( "title"          , $_POST["TITLE"][$lang["lang"]], FD_STR)
							->value( "contents"       , $_POST["CONTENTS"][$lang["lang"]], FD_STR)
							->value( "add_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value( "add_dt"         , "current_timestamp", FD_FUNCTION)
							->value( "upd_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value( "upd_dt"         , "current_timestamp", FD_FUNCTION)
					->createSQL();
				$template->DB->query($sql);
			}
			
			//即時判定
			$isGrant = ($_POST["GRANT_TIMING"] == "0");
		}
		//即時処理
		if($isGrant && count($rows) > 0) {
			// クーポンログ
			$params = "(coupon_no, member_no, add_dt)";
			$values = array();
			foreach( $rows as $row){
				$str =   "(" . $template->DB->conv_sql( $couponNo, FD_NUM)
						."," . $template->DB->conv_sql( $row["member_no"], FD_NUM)
						.", current_timestamp)";
				$values[] = $str;
			}
			$sql = "insert into log_coupon ". $params ." values ". implode(',', $values) .";";
			$template->DB->query($sql);
			
			// ポイント加算
			$PPOINT = new PlayPoint($template->DB, false);
			$limit = ($_POST["LIMIT_DAYS"]=="")? "":date('Y-m-d H:i', strtotime( "+".$_POST["LIMIT_DAYS"]." day"));
			foreach( $rows as $v){
				$PPOINT->addPoint( $v["member_no"], "05", $_POST["POINT"], $couponNo, $limit, $template->getArrayValue( $GLOBALS["pointHistoryProcessCode"], "05"), $template->Session->AdminInfo["admin_no"]);
			}
			
			//連絡Box登録
			$contact_message = array();
			$search  = array( "%TITLE%", "%POINT%", "%LABEL_1%");
			foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
				if( $_POST["TITLE"][$k] != ""){
					$title = $_POST["TITLE"][$k];
				}else{
					$title = $_POST["TITLE"][FOLDER_LANG];
				}
				//
				$replace = array( $title, $_POST["POINT"], $GLOBALS["unitLangList"][$k]["1"]);
				$contact_message[$k] = str_replace( $search, $replace, $v["01"]);
			}
			$contact = new ContactBox( $template->DB, false);
			$contact->addRecords( $rows, "01", $couponNo, $contact_message, "", $template->Session->AdminInfo["admin_no"]);
			
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
			$title = $template->message("A2592");
			$msg = $template->message("A2593");
			break;
		case "del":
			// 削除
			$title = $template->message("A2594");
			$msg = $template->message("A2595");
			break;
		default:
			// 新規登録
			$title = $template->message("A2590");
			$msg = $template->message("A2591");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
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
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();

	$isUpdate = ($_GET["ACT"] != "check");	// 本来の使用とは異なるが件数確認以外を更新とする

	// 未配布
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_coupon")
		->where()
			->and("coupon_no = ", (($_GET["ACT"] != "del") ? $_POST["NO"] : $_GET["NO"]), FD_NUM)
			->and( "coupon_state = ", "0", FD_NUM)
	->createSql("\n");

	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			->setUpdateMode($isUpdate)
			->item($_POST["NO"])
				->isUpdate()				// 更新のみ
				->any()
				->noCountSQL("A2519", $sql)		// 未配布以外
			->item($_POST["TITLE"][FOLDER_LANG])	//-- タイトル
				->isUpdate()							// 更新のみ
				->required("A2518")						// 必須
			->item($_POST["POINT"])		//-- 付与プレイポイント
				->isUpdate()				// 更新のみ
				->required("A2508")			// 必須
				->number("A2509")			// 数字
				->if("A2510", (int)$_POST["POINT"] > 0)
			->item($_POST["LIMIT_DAYS"])	//-- 有効期限
				->isUpdate()					// 更新のみ
					->required("A2511")			// 必須
					->number("A2512")			// 数字
			->item($_POST["PLAN_DT_DATE"])	//-- 付与予定日
				->isUpdate()					// 更新のみ
				->case($_POST["GRANT_TIMING"] == "1")	// 日時指定
					->required("A2513")				// 必須
					->date("A2514")
			->item($_POST["PLAN_DT_HR"])	//-- 付与予定時間
				->isUpdate()					// 更新のみ
				->case($_POST["GRANT_TIMING"] == "1")	// 日時指定
					->required("A2515")				// 必須
			->item($_POST["PLAN_DT_MIN"])	//-- 付与予定分
				->isUpdate()					// 更新のみ
				->case($_POST["GRANT_TIMING"] == "1")	// 日時指定
					->required("A2516")				// 必須
			->item($_POST["COND_MEMBER_NO"])	//-- 条件：会員NO
				->any()
				->number('A2601')
			->item($_POST["COND_POINT_FROM"])	//-- 条件：所持ポイント(開始)
				->any()
				->number('A2602')
			->item($_POST["COND_POINT_TO"])		//-- 条件：所持ポイント(終了)
				->any()
				->number('A2603')
			->item($_POST["COND_DRAW_POINT_FROM"])	//-- 条件：所持抽選ポイント(開始)
				->any()
				->number('A2605')
			->item($_POST["COND_DRAW_POINT_TO"])	//-- 条件：所持抽選ポイント(終了)
				->any()
				->number('A2606')
			->item($_POST["COND_JOIN_FROM"])	//-- 条件：会員登録日(開始)
				->any()
				->date("A2608")
			->item($_POST["COND_JOIN_TO"])		//-- 条件：会員登録日(終了)
				->any()
				->date("A2609")
			->item($_POST["COND_LOGIN_FROM"])	//-- 条件：最終ログイン日(開始)
				->any()
				->date("A2611")
			->item($_POST["COND_LOGIN_TO"])		//-- 条件：最終ログイン日(終了)
				->any()
				->date("A2612")
			->item($_POST["COND_PLAY_COUNT_FROM"])	//-- 条件：プレイ回数(開始)
				->any()
				->number('A2614')
			->item($_POST["COND_PLAY_COUNT_TO"])	//-- 条件：プレイ回数(終了)
				->any()
				->number('A2615')
			->item($_POST["COND_PLAY_DT_FROM"])		//-- 条件：プレイ期間(開始)
				->any()
				->date("A2617")
			->item($_POST["COND_PLAY_DT_TO"])		//-- 条件：プレイ期間(終了)
				->any()
				->date("A2618")
			->item($_POST["COND_PURCHASE_COUNT_FROM"])	//-- 条件：購入回数(開始)
				->any()
				->number('A2620')
			->item($_POST["COND_PURCHASE_COUNT_TO"])	//-- 条件：購入回数(終了)
				->any()
				->number('A2621')
			->item($_POST["COND_PURCHASE_AMOUNT_FROM"])	//-- 条件：購入金額(開始)
				->any()
				->number('A2623')
			->item($_POST["COND_PURCHASE_AMOUNT_TO"])	//-- 条件：購入金額(終了)
				->any()
				->number('A2624')
			->item($_POST["COND_PURCHASE_DT_FROM"])		//-- 条件：購入期間(開始)
				->any()
				->date("A2626")
			->item($_POST["COND_PURCHASE_DT_TO"])		//-- 条件：購入期間(終了)
				->any()
				->date("A2627")
		->report();
		if (count($errMessage) <= 0) {
			// 付与予定日時
			if ($isUpdate && $_POST["GRANT_TIMING"] == "1") {
				if (strtotime(date("Y/m/d H:i:00")) > strtotime($_POST["PLAN_DT_DATE"] . " " . $_POST["PLAN_DT_HR"] . ":"  . $_POST["PLAN_DT_MIN"] . ":00")) $errMessage[] = $template->message("A2517");
			}
			// 所持ポイント
			if (mb_strlen($_POST["COND_POINT_FROM"]) > 0 && mb_strlen($_POST["COND_POINT_TO"]) > 0) {
				if ((int)$_POST["COND_POINT_FROM"] > (int)$_POST["COND_POINT_TO"]) $errMessage[] = $template->message("A2604");
			}
			// 所持抽選ポイント
			if (mb_strlen($_POST["COND_DRAW_POINT_FROM"]) > 0 && mb_strlen($_POST["COND_DRAW_POINT_TO"]) > 0) {
				if ((int)$_POST["COND_DRAW_POINT_FROM"] > (int)$_POST["COND_DRAW_POINT_TO"]) $errMessage[] = $template->message("A2607");
			}
			// 会員登録日
			if (mb_strlen($_POST["COND_JOIN_FROM"]) > 0 && mb_strlen($_POST["COND_JOIN_TO"]) > 0) {
				if (strtotime($_POST["COND_JOIN_FROM"]) > strtotime($_POST["COND_JOIN_TO"])) $errMessage[] = $template->message("A2610");
			}
			// 最終ログイン日
			if (mb_strlen($_POST["COND_LOGIN_FROM"]) > 0 && mb_strlen($_POST["COND_LOGIN_TO"]) > 0) {
				if (strtotime($_POST["COND_LOGIN_FROM"]) > strtotime($_POST["COND_LOGIN_TO"])) $errMessage[] = $template->message("A2613");
			}
			// プレイ回数
			if (mb_strlen($_POST["COND_PLAY_COUNT_FROM"]) > 0 && mb_strlen($_POST["COND_PLAY_COUNT_TO"]) > 0) {
				if ((int)$_POST["COND_PLAY_COUNT_FROM"] > (int)$_POST["COND_PLAY_COUNT_TO"]) $errMessage[] = $template->message("A2616");
			}
			// プレイ期間
			if (mb_strlen($_POST["COND_PLAY_DT_FROM"]) > 0 && mb_strlen($_POST["COND_PLAY_DT_TO"]) > 0) {
				if (strtotime($_POST["COND_PLAY_DT_FROM"]) > strtotime($_POST["COND_PLAY_DT_TO"])) $errMessage[] = $template->message("A2619");
			}
			// 購入回数
			if (mb_strlen($_POST["COND_PURCHASE_COUNT_FROM"]) > 0 && mb_strlen($_POST["COND_PURCHASE_COUNT_TO"]) > 0) {
				if ((int)$_POST["COND_PURCHASE_COUNT_FROM"] > (int)$_POST["COND_PURCHASE_COUNT_TO"]) $errMessage[] = $template->message("A2622");
			}
			// 購入金額
			if (mb_strlen($_POST["COND_PURCHASE_AMOUNT_FROM"]) > 0 && mb_strlen($_POST["COND_PURCHASE_AMOUNT_TO"]) > 0) {
				if ((int)$_POST["COND_PURCHASE_AMOUNT_FROM"] > (int)$_POST["COND_PURCHASE_AMOUNT_TO"]) $errMessage[] = $template->message("A2625");
			}
			// 購入期間
			if (mb_strlen($_POST["COND_PURCHASE_DT_FROM"]) > 0 && mb_strlen($_POST["COND_PURCHASE_DT_TO"]) > 0) {
				if (strtotime($_POST["COND_PURCHASE_DT_FROM"]) > strtotime($_POST["COND_PURCHASE_DT_TO"])) $errMessage[] = $template->message("A2628");
			}
		}
	} else {
		$errMessage = (new SmartAutoCheck($template))
			->item($_GET["NO"])
				->noCountSQL("A2520", $sql)		// 未配布以外
		->report();
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
