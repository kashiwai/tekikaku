<?php
/*
 * goods.php
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
 * 商品管理画面表示
 * 
 * 商品管理画面の表示を行う
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
	getData($_GET , array("P", "ODR"));
	getData($_GET , array("S_GOODS_CD", "RELEASE_DT_FROM", "RELEASE_DT_TO", "DRAW_DT_FROM", "DRAW_DT_TO", "RECEPT_START_DT_FROM", "RECEPT_START_DT_TO", "RECEPT_END_DT_FROM", "RECEPT_END_DT_TO", "S_DRAW_POINT_FROM", "S_DRAW_POINT_TO", "S_DRAW_TYPE", "S_SOLDOUT_FLG", "S_DRAW_STATE" ));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "goods_no desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//検索判定
	if( checkKeys( $_GET, array("S_GOODS_CD", "RELEASE_DT_FROM", "RELEASE_DT_TO", "DRAW_DT_FROM", "DRAW_DT_TO", "RECEPT_START_DT_FROM", "RECEPT_START_DT_TO", "RECEPT_END_DT_FROM", "RECEPT_END_DT_TO", "S_DRAW_POINT_FROM", "S_DRAW_POINT_TO", "S_DRAW_TYPE", "S_SOLDOUT_FLG", "S_DRAW_STATE" ))){
		$_search = "show";	//表示用クラス名
	}else{
		$_search = "";
	}
	
	// 検索用日付
	$releaseSt = ((mb_strlen($_GET["RELEASE_DT_FROM"]) > 0) ? $_GET["RELEASE_DT_FROM"] . " 00:00:00" : "");
	$releaseEd = ((mb_strlen($_GET["RELEASE_DT_TO"]) > 0) ? $_GET["RELEASE_DT_TO"] . " 23:59:59" : "");
	$drawSt = ((mb_strlen($_GET["DRAW_DT_FROM"]) > 0) ?$_GET["DRAW_DT_FROM"] . " 00:00:00" : "");
	$drawEd = ((mb_strlen($_GET["DRAW_DT_TO"]) > 0) ? $_GET["DRAW_DT_TO"] . " 23:59:59" : "");
	$receptStartSt = ((mb_strlen($_GET["RECEPT_START_DT_FROM"]) > 0) ? $_GET["RECEPT_START_DT_FROM"] . " 00:00:00" : "");
	$receptStartEd = ((mb_strlen($_GET["RECEPT_START_DT_TO"]) > 0) ? $_GET["RECEPT_START_DT_TO"] . " 23:59:59" : "");
	$receptEndSt = ((mb_strlen($_GET["RECEPT_END_DT_FROM"]) > 0) ? $_GET["RECEPT_END_DT_FROM"] . " 00:00:00" : "");
	$receptEndEd = ((mb_strlen($_GET["RECEPT_END_DT_TO"]) > 0) ? $_GET["RECEPT_END_DT_TO"] . " 23:59:59" : "");
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_goods mg")
			->join( "inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			
			->where()
				->and( true, "mg.goods_cd like "     , ["%",$_GET["S_GOODS_CD"],"%"], FD_STR )
				->and( true, "mg.release_dt >= "     , $releaseSt, FD_DATEEX )
				->and( true, "mg.release_dt <= "     , $releaseEd, FD_DATEEX )
				->and( true, "mg.draw_dt >= "        , $drawSt, FD_DATEEX )
				->and( true, "mg.draw_dt <= "        , $drawEd, FD_DATEEX )
				->and( true, "mg.recept_start_dt >= ", $receptStartSt, FD_DATEEX )
				->and( true, "mg.recept_start_dt <= ", $receptStartEd, FD_DATEEX )
				->and( true, "mg.recept_end_dt >= "  , $receptEndSt, FD_DATEEX )
				->and( true, "mg.recept_end_dt <= "  , $receptEndEd, FD_DATEEX )
				->and( true, "mg.draw_point >= "     , $_GET["S_DRAW_POINT_FROM"], FD_NUM )
				->and( true, "mg.draw_point <= "     , $_GET["S_DRAW_POINT_TO"], FD_NUM )
				->and( true, "mg.draw_type = "       , $_GET["S_DRAW_TYPE"], FD_STR )
				->and( true, "mg.sold_out_flg = "    , $_GET["S_SOLDOUT_FLG"], FD_STR )
				->and( true, "mg.draw_state = "      , $_GET["S_DRAW_STATE"], FD_STR )
				->and("mg.del_flg = "                , 0, FD_NUM )
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mg.goods_no, mg.goods_cd, mg.draw_point, mg.release_dt, mg.recept_start_dt, mg.recept_end_dt, mg.draw_dt, mg.draw_type, mg.draw_min_count, mg.recept_count, mg.win_count, mg.request_count, mg.sold_out_flg, mg.draw_state, mg.upd_dt, mg.del_flg")
			->field("lng.goods_name")
			->field("(select count(*) from dat_request dr inner join mst_member mm on mm.member_no = dr.member_no where mg.goods_no = dr.goods_no ) as mcnt")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 検索
	$template->assign("S_OPEN"                , $_search, true);
	$template->assign("S_GOODS_CD"            , $_GET["S_GOODS_CD"], true);
	$template->assign("RELEASE_DT_FROM"       , $_GET["RELEASE_DT_FROM"], true);
	$template->assign("RELEASE_DT_TO"         , $_GET["RELEASE_DT_TO"], true);
	$template->assign("DRAW_DT_FROM"          , $_GET["DRAW_DT_FROM"], true);
	$template->assign("DRAW_DT_TO"            , $_GET["DRAW_DT_TO"], true);
	$template->assign("RECEPT_START_DT_FROM"  , $_GET["RECEPT_START_DT_FROM"], true);
	$template->assign("RECEPT_START_DT_TO"    , $_GET["RECEPT_START_DT_TO"], true);
	$template->assign("RECEPT_END_DT_FROM"    , $_GET["RECEPT_END_DT_FROM"], true);
	$template->assign("RECEPT_END_DT_TO"      , $_GET["RECEPT_END_DT_TO"], true);
	$template->assign("S_DRAW_POINT_FROM"     , $_GET["S_DRAW_POINT_FROM"], true);
	$template->assign("S_DRAW_POINT_TO"       , $_GET["S_DRAW_POINT_TO"], true);
	$template->assign("SEL_DRAW_TYPE"         , makeOptionArray($GLOBALS["drawTypeList"],      $_GET["S_DRAW_TYPE"]));
	$template->assign("SEL_SOLDOUT_FLG"       , makeOptionArray($GLOBALS["SoldOutStatusList"], $_GET["S_SOLDOUT_FLG"]));
	$template->assign("SEL_DRAW_STATE"        , makeOptionArray($GLOBALS["drawStatusList"],    $_GET["S_DRAW_STATE"]));
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));	// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));	// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));	// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {

		//pick
		$today = date("Y/m/d H:i:00");
		if(strtotime($today) > strtotime( $row["draw_dt"]) && $row["draw_type"] == 2 && $row["draw_state"] == 0){
			$template->if_enable("CANT_DRAW" , false);
			$template->if_enable("PICK_DRAW" , true);
		}else{
			$template->if_enable("CANT_DRAW" , true);
			$template->if_enable("PICK_DRAW" , false);
		}
		
		$template->assign("GOODS_NO_PAD"       , $template->formatNoBasic( $row["goods_no"]), true);
		$template->assign("GOODS_NO"           , $row["goods_no"], true);
		$template->assign("GOODS_CD"           , $row["goods_cd"], true);
		$template->assign("GOODS_NAME"         , $row["goods_name"], true);
		$template->assign("DRAW_POINT"         , number_formatEx($row["draw_point"]), true);
		$template->assign("RELEASE_DT"         , format_datetime($row["release_dt"]), true);
		$template->assign("RECEPT_START_DT"    , format_datetime($row["recept_start_dt"]), true);
		$template->assign("RECEPT_END_DT"      , format_datetime($row["recept_end_dt"]), true);
		$template->assign("DRAW_DT"            , format_datetime($row["draw_dt"]), true);
		$template->assign("DRAW_TYPE_LABEL"    , $template->getArrayValue($GLOBALS["drawTypeList"], $row["draw_type"]), true);
		$template->assign("DRAW_MIN_COUNT"     , number_formatEx($row["draw_min_count"]), true);
		$template->assign("RECEPT_COUNT"       , number_formatEx($row["recept_count"]), true);
		$template->assign("WIN_COUNT"          , number_formatEx($row["win_count"]), true);
		$template->assign("REQUEST_COUNT"      , number_formatEx($row["request_count"]), true);
		$template->assign("SOLD_OUT_FLG_LABEL" , $template->getArrayValue($GLOBALS["SoldOutStatusList"], $row["sold_out_flg"]), true);
		$template->assign("CSS_SOLD_OUT"       , ($row["sold_out_flg"] == "1") ? " text-danger" : "", true);
		$template->assign("DRAW_STATE_LABEL"   , $template->getArrayValue($GLOBALS["drawStatusList"], $row["draw_state"]), true);
		$template->assign("DRAW_COUNT"         , number_formatEx($row["mcnt"]), true);
		
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
function DispDetail($template, $message = "") {
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	// データ取得
	getData($_GET , array("NO"));
	getData($_POST , array("NO", "GOODS_CD", "GOODS_IMAGE", "DRAW_POINT"
							, "RELEASE_DT", "RELEASE_DT_TIME_HR", "RELEASE_DT_TIME_MIN"
							, "RECEPT_START_DT", "RECEPT_START_TIME_HR", "RECEPT_START_TIME_MIN", "RECEPT_END_DT", "RECEPT_END_TIME_HR", "RECEPT_END_TIME_MIN"
							, "DRAW_DT", "DRAW_MIN_COUNT", "RECEPT_COUNT", "WIN_COUNT", "REQUEST_COUNT", "DRAW_TYPE", "DRAW_STATE"
							, "DRAW_DT_TIME_HR", "DRAW_DT_TIME_MIN"
							));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 ){
			$_load = true;
		}else{
			$_load = false;
		}
		//既存データなのでカウント取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("count(*)")
				->from("dat_request dr")
				->from("inner join mst_member mm on mm.member_no = dr.member_no")
				->where()
					->and( "dr.goods_no = " , $_GET["NO"], FD_NUM )
			->createSql("\n");
		$mcnt = (int)$template->DB->getOne( $sql);
	}else{
		$_load = false;
		$mcnt = 0;
	}
	
	if( $_load ){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mg.goods_no, mg.goods_cd, mg.goods_image, mg.draw_point")
				->field("mg.release_dt, mg.recept_start_dt, mg.recept_end_dt, mg.draw_dt, mg.draw_type")
				->field("mg.draw_min_count, mg.recept_count, mg.win_count, mg.request_count, mg.sold_out_flg, mg.draw_state")
				->from("mst_goods mg")
				->where()
					->and( "mg.goods_no = ", $_GET["NO"], FD_NUM )
					->and( "mg.del_flg = " , 0, FD_NUM )
			->createSql("\n");
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$row["release_dt_time_hr"]  = get_time($row["release_dt"], 0);
		$row["release_dt_time_min"] = get_time($row["release_dt"], 1);
		$row["recept_start_time_hr"]  = get_time($row["recept_start_dt"], 0);
		$row["recept_start_time_min"] = get_time($row["recept_start_dt"], 1);
		$row["recept_end_time_hr"]  = get_time($row["recept_end_dt"], 0);
		$row["recept_end_time_min"] = get_time($row["recept_end_dt"], 1);
		$row["draw_dt_time_hr"]  = get_time($row["draw_dt"], 0);
		$row["draw_dt_time_min"] = get_time($row["draw_dt"], 1);

		// 言語
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("lng.lang, lng.goods_name, lng.goods_info" )
					->from("mst_goods_lang lng")
					->where()
						->and( "lng.goods_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$lang = $template->DB->getAll( $sql, MDB2_FETCHMODE_ASSOC);
		foreach( $lang as $v){
			$langrow["goods_name"][$v["lang"]]  = $v["goods_name"];
			$langrow["goods_info"][$v["lang"]]  = $v["goods_info"];
		}
	}else{
		//POST or 新規
		$row["goods_no"]        = $_POST["NO"];
		$row["goods_cd"]        = $_POST["GOODS_CD"];
		$row["goods_image"]     = $_POST["GOODS_IMAGE"];
		$row["draw_point"]      = $_POST["DRAW_POINT"];
		$row["release_dt"]      = $_POST["RELEASE_DT"];
		$row["recept_start_dt"] = $_POST["RECEPT_START_DT"];
		$row["recept_end_dt"]   = $_POST["RECEPT_END_DT"];
		
		$row["release_dt_time_hr"]    = $_POST["RELEASE_DT_TIME_HR"];
		$row["release_dt_time_min"]   = $_POST["RELEASE_DT_TIME_MIN"];
		$row["recept_start_time_hr"]  = $_POST["RECEPT_START_TIME_HR"];
		$row["recept_start_time_min"] = $_POST["RECEPT_START_TIME_MIN"];
		$row["recept_end_time_hr"]    = $_POST["RECEPT_END_TIME_HR"];
		$row["recept_end_time_min"]   = $_POST["RECEPT_END_TIME_MIN"];
		
		$row["draw_dt"]         = $_POST["DRAW_DT"];
		$row["draw_dt_time_hr"] = $_POST["DRAW_DT_TIME_HR"];
		$row["draw_dt_time_min"]= $_POST["DRAW_DT_TIME_MIN"];
		$row["draw_type"]       = $_POST["DRAW_TYPE"];
		$row["draw_state"]      = $_POST["DRAW_STATE"];
		$row["draw_min_count"]  = $_POST["DRAW_MIN_COUNT"];
		$row["recept_count"]    = $_POST["RECEPT_COUNT"];
		$row["win_count"]       = $_POST["WIN_COUNT"];
		$row["request_count"]   = $_POST["REQUEST_COUNT"];
		// 言語
		foreach( $setLangList as $k=>$v){
			$langrow["goods_name"][$v["lang"]] = ((empty($_POST["GOODS_NAME"][$v["lang"]])) ? "" : $_POST["GOODS_NAME"][$v["lang"]]);
			$langrow["goods_info"][$v["lang"]] = ((empty($_POST["GOODS_INFO"][$v["lang"]])) ? "" : $_POST["GOODS_INFO"][$v["lang"]]);
		}
	}
	$row["mcnt"]                = $mcnt;
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	// 2020/06/08 [ADD Start] 共通置換される前に置換
	$defLangName = $GLOBALS["langList"][FOLDER_LANG]["names"][array_search(FOLDER_LANG, array_column($GLOBALS["langList"][FOLDER_LANG]["names"], 'lang'))]["name"];
	$template->assign("A1904", $template->message("A1904", $defLangName), true);
	$template->assign("A1906", $template->message("A1906", $defLangName), true);
	// 2020/06/08 [ADD End] 共通置換される前に置換
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->assign("DEF_LANG", FOLDER_LANG);
	
	$aryExt = explode("/", str_replace(" ", "", UPFILE_IMG_EXT));
	$template->assign("IMG_EXT", "." . implode(",.", $aryExt));
	$template->assign("UPFILE_IMG_EXT"  , UPFILE_IMG_EXT, true);
	$template->assign("UPFILE_IMG_MAX"  , UPFILE_IMG_MAX, true);
	$template->assign("UPFILE_IMG_MAXBYTE" , (UPFILE_IMG_MAX * 1024 *1024), true);
	$imgType = array_column($GLOBALS["ImgExtension"], "mine");
	$template->assign("UPFILE_IMG_TYPE" , "'" . implode("','", $imgType) . "'");
	
	$template->assign("NO"              , $row["goods_no"], true);
	$template->assign("RDO_DRAW_TYPE"   , makeRadioArray( $GLOBALS["drawTypeList"], "DRAW_TYPE", $row["draw_type"]));
	$template->assign("GOODS_CD"        , $row["goods_cd"], true);

	// 言語タブ処理
	$template->loop_start("LANG_LIST_TAB");
	foreach( $setLangList as $k=>$v){
		$template->assign("ACTIVE" , (($k==0)?"active":""));
		$template->assign("INDEX"  , ($k+1));
		$template->assign("LANGAGE", $v["name"], true);
		$template->loop_next();
	}
	$template->loop_end("LANG_LIST_TAB");
	
	// 言語タブ処理
	$template->loop_start("LANG_LIST");
	foreach( $setLangList as $k=>$v){
		$template->assign("SHOW_IN", (($k==0)?"show in":""));
		$template->assign("ACTIVE" , (($k==0)?"active":""));
		$template->assign("INDEX"  , ($k+1));
		$template->assign("LANG"   ,  $v["lang"], true);
		$template->assign("GOODS_NAME", $langrow["goods_name"][$v["lang"]], true);
		$template->assign("GOODS_INFO", $langrow["goods_info"][$v["lang"]], false);
		$template->if_enable("IS_DEF_LANG", $v["lang"] == FOLDER_LANG);
		//
		$template->loop_next();
	}
	$template->loop_end("LANG_LIST");

	$template->assign("GOODS_IMAGE"     , $row["goods_image"], true);
	$template->assign("GOODS_IMAGE_URL" , URL_SITE . DIR_IMG_GOODS_DIR . $row["goods_image"], true);
	$template->assign("DRAW_POINT"      , $row["draw_point"], true);
	$template->assign("RELEASE_DT"      , $row["release_dt"], true);
	$template->assign("SEL_RELEASE_DT_TIME_HR"    , makeSelectHourTag($row["release_dt_time_hr"]));
	$template->assign("SEL_RELEASE_DT_TIME_MIN"   , makeSelectMinuteTag($row["release_dt_time_min"], MINUTE_SPAN));
	$template->assign("RECEPT_START_DT" , $row["recept_start_dt"], true);
	$template->assign("RECEPT_END_DT"   , $row["recept_end_dt"], true);
	$template->assign("SEL_RECEPT_START_TIME_HR" , makeSelectHourTag($row["recept_start_time_hr"]));
	$template->assign("SEL_RECEPT_START_TIME_MIN", makeSelectMinuteTag($row["recept_start_time_min"], MINUTE_SPAN));
	$template->assign("SEL_RECEPT_END_TIME_HR"   , makeSelectHourTag($row["recept_end_time_hr"]));
	$template->assign("SEL_RECEPT_END_TIME_MIN"  , makeSelectMinuteTag($row["recept_end_time_min"], MINUTE_SPAN));
	$template->assign("DRAW_DT"             , $row["draw_dt"], true);
	$template->assign("SEL_DRAW_DT_TIME_HR" , makeSelectHourTag($row["draw_dt_time_hr"]));
	$template->assign("SEL_DRAW_DT_TIME_MIN", makeSelectMinuteTag($row["draw_dt_time_min"], MINUTE_SPAN));
	$template->assign("DRAW_MIN_COUNT"  , $row["draw_min_count"], true);
	$template->assign("RECEPT_COUNT"    , $row["recept_count"], true);
	$template->assign("WIN_COUNT"       , $row["win_count"], true);
	$template->assign("REQUEST_COUNT"   , $row["request_count"], true);

	$template->assign("DRAW_STATE"      , $row["draw_state"], true);
	$template->assign("DRAW_STATE_LABEL", $template->getArrayValue($GLOBALS["drawStatusList"], $row["draw_state"]), true);
	$template->assign("NOW_TOTAL_DRAW_COUNT" , $row["mcnt"], true);
	
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("UPD"          , mb_strlen($row["goods_no"]) > 0);
	$template->if_enable("DEL"          , mb_strlen($row["goods_no"]) > 0 && $row["draw_state"] == 0 && $row["mcnt"] < 1);
	$template->if_enable("EDIT"         , mb_strlen($row["goods_no"]) == 0 || (mb_strlen($row["goods_no"]) > 0 && $row["draw_state"] == 0));
	$template->if_enable("EDIT_IMG"     , $row["goods_image"] != "");
	
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
	getData($_GET  , array("ACT", "NO"));
	getData($_POST , array("NO", "GOODS_CD", "GOODS_IMAGE", "DRAW_POINT"
							, "RELEASE_DT", "RELEASE_DT_TIME_HR", "RELEASE_DT_TIME_MIN"
							, "RECEPT_START_DT", "RECEPT_START_TIME_HR", "RECEPT_START_TIME_MIN", "RECEPT_END_DT", "RECEPT_END_TIME_HR", "RECEPT_END_TIME_MIN"
							, "DRAW_DT", "DRAW_MIN_COUNT", "RECEPT_COUNT", "WIN_COUNT", "REQUEST_COUNT", "DRAW_TYPE", "DRAW_STATE"
							, "DRAW_DT_TIME_HR", "DRAW_DT_TIME_MIN"
						));
	
	// 入力チェック
	if ($_GET["ACT"] != "del") {
		$message = checkInput($template);
		if (mb_strlen($message) > 0) {
			DispDetail($template, $message);
			return;
		}
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		
		// 申込がある場合は削除不可
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("count(*)")
				->from("dat_request dr")
				->from("inner join mst_member mm on mm.member_no = dr.member_no")
				->where()
					->and(false, "dr.goods_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$mcnt = $template->DB->getOne($sql);
		if ((int)$mcnt > 0) {
			DispDetail($template, $template->message("A1946"));
			return;
		}
		
		// 先に画像を削除
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("goods_image")
				->from("mst_goods")
				->where()
					->and(false, "goods_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$delimage = $template->DB->getOne($sql);
		if ($delimage != null && file_exists(DIR_IMG_GOODS . $delimage)) {
			chmod(DIR_IMG_GOODS . $delimage, 0755);
			unlink(DIR_IMG_GOODS . $delimage);
		}
		// データ論理削除
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_goods" )
				->set()
					->value( "del_flg" , 1, FD_NUM)
					->value( "del_no"  , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"  , "current_timestamp", FD_FUNCTION)
				->where()
					->and(false, "goods_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}else{

		$upfile = "";
		if (isset($_FILES['GOODS_IMAGE_NEW']['tmp_name']) && !empty($_FILES['GOODS_IMAGE_NEW']['tmp_name'])) {
			try {
				if (!isset($_FILES['GOODS_IMAGE_NEW']['error']) || !is_int($_FILES['GOODS_IMAGE_NEW']['error'])) {
					throw new RuntimeException($template->message("A1927"));
				}
				
				switch ($_FILES['GOODS_IMAGE_NEW']['error']) {
					case UPLOAD_ERR_OK: // OK
						break;
					case UPLOAD_ERR_NO_FILE:   // ファイル未選択
						if (mb_strlen($_POST["NO"]) == 0) {
							throw new RuntimeException($template->message("A1926"));
						}
						break;
					case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
					case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
						throw new RuntimeException($template->message("A1929"));
					default:
						throw new RuntimeException($template->message("A1927"));
				}
				
				// ファイルサイズチェック
				if ($_FILES['GOODS_IMAGE_NEW']['size'] > (UPFILE_IMG_MAX * 1024 *1024)) {
					throw new RuntimeException($template->message("A1929"));
				}
				
				// MIMEタイプチェック(拡張子)
				$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
				if (!$ext = array_search(mime_content_type($_FILES['GOODS_IMAGE_NEW']['tmp_name']), $chkMime, true)) {
					throw new RuntimeException($template->message("A1928"));
				}
				
				// 保存
				$upfile = sha1(mt_rand() . time());
				if (move_uploaded_file($_FILES['GOODS_IMAGE_NEW']['tmp_name'], sprintf(DIR_IMG_GOODS . '%s.%s', $upfile, $ext))) {
					$upfile = $upfile . "." . $ext;
					if (mb_strlen($_POST["GOODS_IMAGE"]) > 0) {
						if (file_exists(DIR_IMG_GOODS . $_POST["GOODS_IMAGE"])) {
							chmod(DIR_IMG_GOODS . $_POST["GOODS_IMAGE"], 0755);
							unlink(DIR_IMG_GOODS . $_POST["GOODS_IMAGE"]);
						}
					}
				} else {
					$upfile = "";
					throw new RuntimeException($template->message("A1927"));
				}

			} catch (RuntimeException $e) {
				DispDetail($template, $e->getMessage());
				return;
			}
		}
		
		//日時成形
		$_rs_dt = $_POST["RECEPT_START_DT"] . " " . $_POST["RECEPT_START_TIME_HR"] . ":" . $_POST["RECEPT_START_TIME_MIN"] . ":00";
		$_re_dt = $_POST["RECEPT_END_DT"] . " " . $_POST["RECEPT_END_TIME_HR"] . ":" . $_POST["RECEPT_END_TIME_MIN"] . ":59";
		$drawDt = $_POST["DRAW_DT"] . " " . $_POST["DRAW_DT_TIME_HR"] . ":" . $_POST["DRAW_DT_TIME_MIN"] . ":00";
		
		//公開日の入力がなければ応募開始日時に設定にする
		if (mb_strlen($_POST["RELEASE_DT"]) == 0 && mb_strlen($_POST["RELEASE_DT_TIME_HR"]) == 0 && mb_strlen($_POST["RELEASE_DT_TIME_MIN"]) == 0) {
			$_set_release_dt = $_rs_dt;
		} else {
			$_set_release_dt = $_POST["RELEASE_DT"] . " " . $_POST["RELEASE_DT_TIME_HR"] . ":" . $_POST["RELEASE_DT_TIME_MIN"] . ":00";
		}
		
		if (mb_strlen($_POST["NO"]) > 0) {
			// 更新
			$mode = "update";

			// 現在の応募数取得して売切フラグを判定する
			$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->select()
					->field("count(*)")
					->from("dat_request dr")
					->from("inner join mst_member mm on mm.member_no = dr.member_no")
					->where()
						->and(false, "dr.goods_no = ", $_POST["NO"], FD_NUM)
				->createSql();
			$mcnt = (int)$template->DB->getOne($sql);
			$soldOut = (((int)$_POST["RECEPT_COUNT"] <= $mcnt) ? 1 : 0);

			
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_goods" )
					->set()
						->value( "goods_cd"        , $_POST["GOODS_CD"],    FD_STR)
						->value(true, "goods_image", $upfile, FD_STR)
						->value( "draw_point"      , $_POST["DRAW_POINT"],  FD_NUM)
						//日時系
						->value( "release_dt"      , $_set_release_dt, FD_DATEEX)
						->value( "recept_start_dt" , $_rs_dt, FD_DATEEX)
						->value( "recept_end_dt"   , $_re_dt, FD_DATEEX)
						->value( "draw_dt"         , $drawDt, FD_DATEEX)
						->value( "draw_type"       , $_POST["DRAW_TYPE"], FD_NUM)
						//ポイント系
						->value( "draw_min_count"  , $_POST["DRAW_MIN_COUNT"], FD_NUM)
						->value( "recept_count"    , $_POST["RECEPT_COUNT"],   FD_NUM)
						->value( "win_count"       , $_POST["WIN_COUNT"],      FD_NUM)
						->value( "request_count"   , $_POST["REQUEST_COUNT"],  FD_NUM)
						->value( "sold_out_flg"    , $soldOut,  FD_NUM)
						//
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "goods_no =" , $_POST["NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
			// 言語
			$goodsNo = $_POST["NO"];
			$gozName = $_POST["GOODS_NAME"][FOLDER_LANG];
			$gozInfo = $_POST["GOODS_INFO"][FOLDER_LANG];
			foreach ($setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->update("mst_goods_lang")
						->set()
							->value( "goods_name", (mb_strlen($_POST["GOODS_NAME"][$lang["lang"]]) > 0) ? $_POST["GOODS_NAME"][$lang["lang"]] : $gozName, FD_STR)
							->value( "goods_info", (mb_strlen($_POST["GOODS_INFO"][$lang["lang"]]) > 0) ? $_POST["GOODS_INFO"][$lang["lang"]] : $gozInfo, FD_STR)
							->value( "upd_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value( "upd_dt"    , "current_timestamp", FD_FUNCTION)
						->where()
							->and("goods_no = ", $goodsNo, FD_NUM)
							->and("lang ="     , $lang["lang"], FD_STR)
					->createSQL();
				$template->DB->query($sql);
			}

		}else{
			// 新規
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_goods" )
						->value( "goods_cd"        , $_POST["GOODS_CD"],    FD_STR)
						->value( "goods_image"     , $upfile, FD_STR)
						->value( "draw_point"      , $_POST["DRAW_POINT"],  FD_NUM)
						//日時系
						->value( "release_dt"      , $_set_release_dt,  FD_DATEEX)
						->value( "recept_start_dt" , $_rs_dt, FD_DATEEX)
						->value( "recept_end_dt"   , $_re_dt, FD_DATEEX)
						->value( "draw_dt"         , $drawDt, FD_DATEEX)
						->value( "draw_type"       , $_POST["DRAW_TYPE"], FD_NUM)
						//ポイント系
						->value( "draw_min_count"  , $_POST["DRAW_MIN_COUNT"], FD_NUM)
						->value( "recept_count"    , $_POST["RECEPT_COUNT"],   FD_NUM)
						->value( "win_count"       , $_POST["WIN_COUNT"],      FD_NUM)
						->value( "request_count"   , $_POST["REQUEST_COUNT"],  FD_NUM)
						//
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
						->value( "add_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($sql);
			// 言語
			$sql = "select last_insert_id()";
			$goodsNo = $template->DB->getOne($sql);
			$gozName = $_POST["GOODS_NAME"][FOLDER_LANG];
			$gozInfo = $_POST["GOODS_INFO"][FOLDER_LANG];
			foreach ($setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->insert()
						->into( "mst_goods_lang" )
							->value("goods_no"  , $goodsNo, FD_NUM)
							->value("lang"      , $lang["lang"], FD_STR)
							->value("goods_name", (mb_strlen($_POST["GOODS_NAME"][$lang["lang"]]) > 0) ? $_POST["GOODS_NAME"][$lang["lang"]] : $gozName, FD_STR)
							->value("goods_info", (mb_strlen($_POST["GOODS_INFO"][$lang["lang"]]) > 0) ? $_POST["GOODS_INFO"][$lang["lang"]] : $gozInfo, FD_STR)
							->value("add_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value("add_dt"    , "current_timestamp", FD_FUNCTION)
							->value("upd_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
					->createSQL();
				$template->DB->query($sql);
			}
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
			$title = $template->message("A1962");
			$msg = $template->message("A1963");
			break;
		case "del":
			// 削除
			$title = $template->message("A1964");
			$msg = $template->message("A1965");
			break;
		default:
			// 新規登録
			$title = $template->message("A1960");
			$msg = $template->message("A1961");
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

	//イメージチェック用
	$_chk_img1 = ($_FILES['GOODS_IMAGE_NEW']['tmp_name'] != "") ? './' . $_FILES['GOODS_IMAGE_NEW']['name'] : $_POST["GOODS_IMAGE"];

	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["GOODS_CD"])
			->required("A1901")
			->alnum("A1902")
			->maxLength("A1903", 20)
		->item($_POST["GOODS_NAME"][FOLDER_LANG])
			->required("A1905")
		->item($_POST["GOODS_INFO"][FOLDER_LANG])
			->required("A1948")

		->item($_chk_img1)
			->required("A1926")

		->item($_POST["DRAW_POINT"])
			->required("A1908")
			->number("A1909")

		->item($_POST["RELEASE_DT"])
			->any()
				->date("A1911")
		->item($_POST["RECEPT_START_DT"])
			->required("A1912")
			->date("A1913")
		->item($_POST["RECEPT_START_TIME_HR"])
			->required("A1931")
		->item($_POST["RECEPT_START_TIME_MIN"])
			->required("A1931")
		->item($_POST["RECEPT_END_DT"])
			->required("A1914")
			->date("A1915")
		->item($_POST["RECEPT_END_TIME_HR"])
			->required("A1932")
		->item($_POST["RECEPT_END_TIME_MIN"])
			->required("A1932")
		->item($_POST["DRAW_DT"])
			->required("A1916")
			->date("A1917")
		->item($_POST["DRAW_DT_TIME_HR"])
			->required("A1949")
		->item($_POST["DRAW_DT_TIME_MIN"])
			->required("A1949")
		->item($_POST["DRAW_MIN_COUNT"])
			->required("A1918")
			->number("A1919")
		->item($_POST["RECEPT_COUNT"])
			->required("A1920")
			->number("A1921")
		->item($_POST["WIN_COUNT"])
			->required("A1922")
			->number("A1923")
		->item($_POST["REQUEST_COUNT"])
			->required("A1924")
			->number("A1925")
		
	->report();

	// ここまででエラーがない場合のみ更にチェック
	if (empty($errMessage)) {
		// 公開日・時刻のいずれか1つでも入力がある場合は必須にする
		if (!empty($_POST["RELEASE_DT"]) || mb_strlen($_POST["RELEASE_DT_TIME_HR"]) > 0 || mb_strlen($_POST["RELEASE_DT_TIME_MIN"]) > 0) {
			if (empty($_POST["RELEASE_DT"])) array_push($errMessage, $template->message("A1910"));
			if (mb_strlen($_POST["RELEASE_DT_TIME_HR"]) == 0 || mb_strlen($_POST["RELEASE_DT_TIME_MIN"]) == 0) array_push($errMessage, $template->message("A1946"));
		}
		// 公開日・応募開始
		if (empty($errMessage) && !empty($_POST["RELEASE_DT"])) {
			$from = new DateTime($_POST["RELEASE_DT"]
						. " " . sprintf('%02d', $_POST["RELEASE_DT_TIME_HR"]) . ":" . sprintf('%02d', $_POST["RELEASE_DT_TIME_MIN"]) . ":00");
			$to = new DateTime($_POST["RECEPT_START_DT"]
						. " " . sprintf('%02d', $_POST["RECEPT_START_TIME_HR"]) . ":" . sprintf('%02d', $_POST["RECEPT_START_TIME_MIN"]) . ":00");
			if ($from > $to) {
				array_push($errMessage, $template->message("A1933"));
			}
		}
		// 応募開始・終了(チェック時はENDの秒を00にする)
		$from = new DateTime($_POST["RECEPT_START_DT"]
						. " " . sprintf('%02d', $_POST["RECEPT_START_TIME_HR"]) . ":" . sprintf('%02d', $_POST["RECEPT_START_TIME_MIN"]) . ":00");
		$to = new DateTime($_POST["RECEPT_END_DT"]
						. " " . sprintf('%02d', $_POST["RECEPT_END_TIME_HR"]) . ":" . sprintf('%02d', $_POST["RECEPT_END_TIME_MIN"]) . ":00");
		if ($from >= $to) {
			array_push($errMessage, $template->message("A1934"));
		}
		// 応募終了・抽選日
		$from = new DateTime($_POST["RECEPT_END_DT"]
						. " " . sprintf('%02d', $_POST["RECEPT_END_TIME_HR"]) . ":" . sprintf('%02d', $_POST["RECEPT_END_TIME_MIN"]) . ":00");
		$to = new DateTime($_POST["DRAW_DT"]
						. " " . sprintf('%02d', $_POST["DRAW_DT_TIME_HR"]) . ":" . sprintf('%02d', $_POST["DRAW_DT_TIME_MIN"]) . ":00");
		if ($from >= $to) {
			array_push($errMessage, $template->message("A1935"));
		}
		// ポイント系
		if ((int)$_POST["DRAW_MIN_COUNT"] < 1) array_push($errMessage, $template->message("A1936"));
		if ((int)$_POST["RECEPT_COUNT"] < 1) array_push($errMessage, $template->message("A1937"));
		if ((int)$_POST["DRAW_MIN_COUNT"] > (int)$_POST["RECEPT_COUNT"]) array_push($errMessage, $template->message("A1940"));
		if ((int)$_POST["WIN_COUNT"] < 1) array_push($errMessage, $template->message("A1938"));
		if ((int)$_POST["REQUEST_COUNT"] < 1) array_push($errMessage, $template->message("A1939"));
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
