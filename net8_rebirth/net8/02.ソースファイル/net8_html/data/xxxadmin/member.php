<?php
/*
 * member.php
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
 * 会員管理画面表示
 * 
 * 会員管理画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/29 初版作成 片岡 充
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
			
			case "output":			// CSVダウンロード
				$mainWin = false;
				ProcOutput($template);
				break;
			
			case "end":				// 完了画面
				$mainWin = false;
				DispComplete($template);
				break;
			
			case "detail2":			// ポイント修正画面
				$mainWin = false;
				DispDetail2($template);
				break;
			
			case "regist2":			// ポイント修正処理
				$mainWin = false;
				RegistData2($template);
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
 * @return	なし
 */
function DispList($template) {
	// データ取得
	getData($_GET , array("M", "NO", "P", "ODR"
						, "S_MEMBER_NO", "S_NICKNAME", "S_MAIL", "S_BLACK_FLG", "S_INVITE_CD"
						, "S_PLAY_POINT_FROM", "S_PLAY_POINT_TO", "S_DRAW_POINT_FROM", "S_DRAW_POINT_TO"
						, "JOIN_DT_FROM", "JOIN_DT_TO", "S_STATUS"
						, "S_TESTER", "S_MOBILE", "S_AGENT"
						));

	// 初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "member_no desc";
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//検索判定
	if( ($_GET["S_MEMBER_NO"]!="") || ($_GET["S_NICKNAME"]!="") || ($_GET["S_MAIL"]!="") || ($_GET["S_PLAY_POINT_FROM"]!="") || ($_GET["S_PLAY_POINT_TO"]!="")
			 || ($_GET["S_BLACK_FLG"]!="") || ($_GET["S_INVITE_CD"]!="") || ($_GET["S_DRAW_POINT_FROM"]!="") || ($_GET["S_DRAW_POINT_TO"]!="")
			 || ($_GET["JOIN_DT_FROM"]!="") || ($_GET["JOIN_DT_TO"]!="") || ($_GET["S_STATUS"]!="") || ($_GET["S_TESTER"]!="") || ($_GET["S_MOBILE"]!="") || ($_GET["S_AGENT"]!="")){
		$_search = "show";
	}else{
		$_search = "";
	}
	
	// 検索用日付
	$joinSt = ((mb_strlen($_GET["JOIN_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["JOIN_DT_FROM"]) : "");
	$joinEd = ((mb_strlen($_GET["JOIN_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["JOIN_DT_TO"]) : "");
	
	// 検索SQL生成
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_member m" )
			->from( "left join mst_member mm on mm.member_no = m.invite_member_no" )
			->where()
				->and( true, "m.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "m.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
			// 2024/01/10 [UPD Start]
			//	->and( true, "m.mail like ", ["%",$_GET["S_MAIL"]], FD_STR )
				->and( true, "m.mail like ", ["%",$_GET["S_MAIL"],"%"], FD_STR )
			// 2024/01/10 [UPD End]
				->and( true, "m.mobile like ", [$_GET["S_MOBILE"], "%"], FD_STR )
				->and( true, "m.state = ", $_GET["S_STATUS"], FD_NUM )
				->and( true, "m.join_dt >= ", $joinSt, FD_DATEEX )
				->and( true, "m.join_dt <= ", $joinEd, FD_DATEEX )
				->and( true, "m.point >= ", $_GET["S_PLAY_POINT_FROM"], FD_NUM )
				->and( true, "m.point <= ", $_GET["S_PLAY_POINT_TO"], FD_NUM )
				->and( true, "m.draw_point >= ", $_GET["S_DRAW_POINT_FROM"], FD_NUM )
				->and( true, "m.draw_point <= ", $_GET["S_DRAW_POINT_TO"], FD_NUM )
				->and( true, "m.black_flg = ", $_GET["S_BLACK_FLG"], FD_NUM )
				->and( true, "m.tester_flg = ", $_GET["S_TESTER"], FD_NUM )		// 2020/04/20 [ADD]
				->and( true, "m.agent_flg = ", $_GET["S_AGENT"], FD_NUM )
				->and( true, "mm.invite_cd = ", $_GET["S_INVITE_CD"], FD_STR )
		->createSql("\n");
	
	$allrows = $template->DB->getOne($csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);	// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$lsql = $sqls
			->resetField()
			->field( "m.member_no, m.mail, m.nickname, m.state, m.temp_dt, m.join_dt, m.login_dt ,m.point, m.draw_point, m.black_flg")
			->field( "m.tester_flg")	// 2020/04/20 [ADD]
			->field( "mm.invite_cd, m.agent_flg")
			->field( "m.mobile, m.international_cd")
			->field( "(select count(*) from dat_request dr where dr.member_no = m.member_no) as draw_count")
			->orderby( $_GET["ODR"] )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
		->createSql("\n");
	
	$rs = $template->DB->query($lsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 検索条件
	$template->assign("S_OPEN"                , $_search, true);
	$template->assign("S_MEMBER_NO"           , $_GET["S_MEMBER_NO"], true);
	$template->assign("S_NICKNAME"            , $_GET["S_NICKNAME"], true);
	$template->assign("S_MAIL"                , $_GET["S_MAIL"], true);
	$template->assign("S_MOBILE"              , $_GET["S_MOBILE"], true);
	$template->assign("S_PLAY_POINT_FROM"     , $_GET["S_PLAY_POINT_FROM"], true);
	$template->assign("S_PLAY_POINT_TO"       , $_GET["S_PLAY_POINT_TO"], true);
	$template->assign("S_DRAW_POINT_FROM"     , $_GET["S_DRAW_POINT_FROM"], true);
	$template->assign("S_DRAW_POINT_TO"       , $_GET["S_DRAW_POINT_TO"], true);
	$template->assign("JOIN_DT_FROM"          , $_GET["JOIN_DT_FROM"], true);
	$template->assign("JOIN_DT_TO"            , $_GET["JOIN_DT_TO"], true);
	$template->assign("SEL_STATUS"            , makeOptionArray($GLOBALS["MemberStatus"], $_GET["S_STATUS"], true));
	$template->assign("SEL_BLACK_FLG"         , makeOptionArray($GLOBALS["BlackMemberStatus"], $_GET["S_BLACK_FLG"], true));
	$template->assign("SEL_TESTER"            , makeOptionArray($GLOBALS["TesterMember"], $_GET["S_TESTER"], true));
	$template->assign("SEL_AGENT"             , makeOptionArray($GLOBALS["AgentMember"], $_GET["S_AGENT"], true));
	$template->assign("GIFT_AGENT_DISPNAME"   , GIFT_AGENT_DISPNAME, true);
	$template->assign("S_INVITE_CD"           , $_GET["S_INVITE_CD"], true);
	
	// 明細処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		if( $row["join_dt"] != ""){
			$template->if_enable("JOIN_DT_FLG", "1");
			$template->if_enable("TEMP_DT_FLG", "");
		}else{
			$template->if_enable("JOIN_DT_FLG", "");
			$template->if_enable("TEMP_DT_FLG", "1");
		}
		$template->if_enable("BLACK_FLG", ($row["black_flg"]==1));
		$template->if_enable("IS_TESTER", ($row["tester_flg"]==1));	// 2020/04/20 [ADD] テスター会員表示
		$template->if_enable("IS_QUIT"  , ($row["state"]==9));	// 2020/04/21 [ADD] 退会会員表示制御
		$template->if_enable("NO_QUIT"  , ($row["state"]!=9));	// 2020/04/21 [ADD] 退会以外会員表示制御
		$template->if_enable("IS_AGENT" , $row["agent_flg"] == 1);

		$template->if_enable("EXT_MOBILE", mb_strlen($row["mobile"]) > 0 || mb_strlen($row["international_cd"]) > 0);

		// 行出力
		$template->assign("MEMBER_NO"        , $row["member_no"], true);
		$template->assign("DISP_MEMBER_NO"   , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"         , $row["nickname"], true);
		$template->assign("MAIL"             , $row["mail"], true);
		$template->assign("INTERNATIONAL_CD" , $row["international_cd"], true);
		$template->assign("MOBILE"           , $row["mobile"], true);
		$template->assign("PLAY_POINT"       , (mb_strlen($row["point"])==0)? $row["point"]:number_format( $row["point"]), true);
		$template->assign("DRAW_POINT"       , (mb_strlen($row["draw_point"])==0)? $row["draw_point"]:number_format( $row["draw_point"]), true);
		$template->assign("DRAW_COUNT"       , $row["draw_count"], true);
		$template->assign("TEMP_DT"          , format_datetime($row["temp_dt"]), true);
		$template->assign("JOIN_DT"          , format_datetime($row["join_dt"]), true);
		$template->assign("LAST_LOGIN_DT"    , format_datetime($row["login_dt"]), true);
		$template->assign("STATUS"           , $template->getArrayValue($GLOBALS["MemberStatus"], $row["state"]), true);
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);
	$template->if_enable("AUTH_MEMBER_MOBILE", AUTH_MEMBER_MOBILE);
	$template->if_enable("GIFT_AGENT"        , GIFT_AGENT);

	// ページ処理
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?".$_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW", (string)$allrows);		// 総件数
	$template->assign("P", (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP", (string)$allpage);		// 総ページ数
	$template->assign("ODR", $_GET["ODR"]);				// ソート順

	// 表示
	$template->flush();
}

/**
 * 詳細画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispDetail($template, $message = "") {

	// データ取得
	getData($_GET , array("NO"));
	getData($_POST , array("NO", "NICKNAME", "MAIL", "SEX", "BIRTH", "STATE", "STATUS", "DRAW_POINT", "BLACK_FLG", "BLACK_REASON", "QUIT_REASON"
						 , "JOIN_DT", "LAST_LOGIN_DT", "LAST_LOGIN_UA", "WITHDRAWAL_DT", "WITHDRAWAL_REASON", "REMARKS", "MAGAZINE", "FIRST_NAME", "LAST_NAME"
						, "TEMP_DT", "QUIT_DT", "TESTER_FLG", "H_PLAY_POINT", "BLACK_DT", "INVITE_CD", "INVITED_BY_NO", "INVITED_BY_NAME"		// 2020/04/21 [ADD]
						, "MAIL_ERROR_COUNT", "MAIL_ERROR_DT", "MOBILE", "MOBILE_UPD_DT", "INTERNATIONAL_CD", "PASS", "AGENT_FLG", "GIFT_POINT", "TOTAL_GIFT_POINT"
						, "MOBILE_CHECKED_DT", "MOBILE_CHECKED_TIME"	// 2020/12/25 [ADD]
						));
	
	if (mb_strlen($message) == 0 ) {
		
		if( $_GET["NO"] != ""){
			
			$sqls = new SqlString();
			$sqls->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
					->field( "m.member_no, m.nickname, m.mail, m.sex, m.birthday, m.state, m.point, m.draw_point, m.join_dt, m.login_dt, m.login_ua, m.temp_dt, m.quit_dt, m.remarks, m.mail_magazine")
					->field( "m.last_name, m.first_name, m.invite_cd, m.invite_member_no, m.black_flg, m.black_reason, m.black_dt, m.quit_reason")
					->field( "mm.nickname as p_nickname")
					->field( "m.tester_flg")	// 2020/04/21 [ADD]
					->field( "m.mail_error_count, m.mail_error_dt, m.agent_flg, m.gift_point, m.total_gift_point")
					->from( "mst_member m" )
					->from( "left join mst_member mm on mm.member_no = m.invite_member_no" )
					->where()
						->and( false, "m.member_no = ", $_GET["NO"], FD_NUM )
					->groupby( "m.member_no" );
			if (AUTH_MEMBER_MOBILE) {
				$sqls->field( "m.mobile, m.mobile_upd_dt, m.international_cd");
				// 2020/12/25 [ADD Start]
				if (AUTH_MOBILE_VALID_DAYS > 0) {
					$sqls->field( "m.mobile_checked_dt");
				}
				// 2020/12/25 [ADD End]
			}
			$_sql = $sqls->createSql("\n");

			
			$row = $template->DB->getRow($_sql, PDO::FETCH_ASSOC);
			// 2020/04/24 [ADD Start]データ不存在は通常あり得ないのでシステムエラー
			if (empty($row["member_no"])) {
				$template->dispProcError($template->message("A0003"), false);
				return;
			}
			// 2020/04/24 [ADD End]データ不存在は通常あり得ないのでシステムエラー
			$_POST["NO"]               = $row["member_no"];
			$_POST["NICKNAME"]         = $row["nickname"];
			$_POST["LAST_NAME"]        = $row["last_name"];
			$_POST["FIRST_NAME"]       = $row["first_name"];
			$_POST["MAIL"]             = $row["mail"];
			$_POST["SEX"]              = $row["sex"];
			$_POST["BIRTH"]            = format_date($row["birthday"]);
			$_POST["H_PLAY_POINT"]     = $row["point"];		// 2020/04/21 [UPD]
			$_POST["DRAW_POINT"]       = $row["draw_point"];
			$_POST["MAGAZINE"]         = $row["mail_magazine"];
			$_POST["INVITE_CD"]        = $row["invite_cd"];
			$_POST["INVITED_BY_NO"]    = $row["invite_member_no"];
			$_POST["INVITED_BY_NAME"]  = $row["p_nickname"];
			$_POST["BLACK_FLG"]        = $row["black_flg"];
			$_POST["BLACK_REASON"]     = $row["black_reason"];
			$_POST["BLACK_DT"]         = format_datetime($row["black_dt"]);
			$_POST["STATE"]            = $row["state"];
			$_POST["STATUS"]           = $row["state"];
			$_POST["TEMP_DT"]          = format_datetime($row["temp_dt"]);
			$_POST["JOIN_DT"]          = format_datetime($row["join_dt"]);
			$_POST["LAST_LOGIN_DT"]    = format_datetime($row["login_dt"]);
			$_POST["LAST_LOGIN_UA"]    = $row["login_ua"];
			$_POST["QUIT_DT"]          = format_datetime($row["quit_dt"]);
			$_POST["QUIT_REASON"]      = $row["quit_reason"];
			$_POST["REMARKS"]          = $row["remarks"];
			$_POST["TESTER_FLG"]       = $row["tester_flg"];		// 2020/04/21 [ADD]
			$_POST["MAIL_ERROR_COUNT"] = $row["mail_error_count"];
			$_POST["MAIL_ERROR_DT"]    = format_datetime($row["mail_error_dt"]);
			$_POST["AGENT_FLG"]        = $row["agent_flg"];
			$_POST["GIFT_POINT"]       = $row["gift_point"];
			$_POST["TOTAL_GIFT_POINT"] = $row["total_gift_point"];
			if (AUTH_MEMBER_MOBILE) {
				$_POST["MOBILE"]           = $row["mobile"];
				$_POST["MOBILE_UPD_DT"]    = format_datetime($row["mobile_upd_dt"]);
				$_POST["INTERNATIONAL_CD"] = $row["international_cd"];
				// 2020/12/25 [ADD Start]
				if (AUTH_MOBILE_VALID_DAYS > 0) {
					$_POST["MOBILE_CHECKED_DT"] = $row["mobile_checked_dt"];
					$chkDate = ((mb_strlen($row["mobile_checked_dt"]) > 0) ? $row["mobile_checked_dt"] : "");
					$date = new DateTime($chkDate);
					$_POST["MOBILE_CHECKED_TIME"] = $date->format("H:i:s");
				}
				// 2020/12/25 [ADD End]
			}
		}
	}
	// 2020/12/25 [ADD Start]
	if (($_POST["MOBILE_CHECKED_TIME"]) <= 0) {
		$date = new DateTime();
		$_POST["MOBILE_CHECKED_TIME"] = $date->format("H:i:s");
	}


	// 使用可能国際番号設定
	$aryInternational = array();
	if (AUTH_MEMBER_MOBILE) $aryInternational = $GLOBALS["usableInternationalCode"];

	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);

	$template->assign("NO"               , $_POST["NO"], true);
	$template->assign("DISP_MEMBER_NO"   , $template->formatMemberNo($_POST["NO"]), true);
	$template->assign("NICKNAME"         , $_POST["NICKNAME"], true);
	$template->assign("LAST_NAME"        , $_POST["LAST_NAME"], true);
	$template->assign("FIRST_NAME"       , $_POST["FIRST_NAME"], true);
	$template->assign("MAIL"             , $_POST["MAIL"], true);
	$template->assign("MOBILE"           , $_POST["MOBILE"], true);
	$template->assign("MOBILE_UPD_DT"    , $_POST["MOBILE_UPD_DT"], true);
	$template->assign("MOBILE_CHECKED_DT", $_POST["MOBILE_CHECKED_DT"], true);	// 2020/12/25 [ADD]
	$template->assign("MOBILE_CHECKED_TIME", $_POST["MOBILE_CHECKED_TIME"], true);	// 2020/12/25 [ADD]
	$template->assign("SEL_INTERNATIONAL_CD", makeOptionArray($aryInternational, $_POST["INTERNATIONAL_CD"],  true));
	$template->assign("TEMP_DT"          , $_POST["TEMP_DT"], true);
	$template->assign("MAGAZINE"         , makeRadioArray($GLOBALS["MagazineReadStatus"], "MAGAZINE", $_POST["MAGAZINE"]));
	$template->assign("RDO_SEX"          , makeRadioArray($GLOBALS["SexList"], "SEX", $_POST["SEX"]));
	$template->assign("BIRTH"            , $_POST["BIRTH"], true);
	$template->assign("INVITE_CD"        , $_POST["INVITE_CD"], true);
	$template->assign("INVITED_BY_NO"    , $_POST["INVITED_BY_NO"], true);
	$template->assign("INVITED_BY_NAME"  , $_POST["INVITED_BY_NAME"], true);
	$template->assign("RD_BLACK_FLG"     , makeRadioArray($GLOBALS["BlackMemberStatus"], "BLACK_FLG", $_POST["BLACK_FLG"]));
	$template->assign("BLACK_REASON"     , $_POST["BLACK_REASON"], true);
	$template->assign("BLACK_DT"         , $_POST["BLACK_DT"], true);
	$template->assign("REMARKS"          , $_POST["REMARKS"], true);
	$template->assign("H_PLAY_POINT"     , $_POST["H_PLAY_POINT"], true);		// 2020/04/21 [ADD]
	$template->assign("PLAY_POINT"       , number_formatEx($_POST["H_PLAY_POINT"]), true);		// 2020/04/21 [UPD]
	$template->assign("DRAW_POINT"       , $_POST["DRAW_POINT"], true);
	$template->assign("STATE"            , $_POST["STATE"], true);
	$template->assign("STATUS"           , makeRadioArray( _chkStatusArray( $GLOBALS["MemberStatus"], $_POST["STATE"]), "STATUS", $_POST["STATUS"]));
	$template->assign("JOIN_DT"          , $_POST["JOIN_DT"], true);
	$template->assign("LAST_LOGIN_DT"    , $_POST["LAST_LOGIN_DT"], true);
	$template->assign("LAST_LOGIN_UA"    , $_POST["LAST_LOGIN_UA"], true);
	$template->assign("QUIT_DT"          , $_POST["QUIT_DT"], true);
	$template->assign("QUIT_REASON"      , $_POST["QUIT_REASON"], true);
	$template->assign("MEMBER_PASS_MIN",    MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_PATTERN", MEMBER_PASS_PATTERN, true);	// 2020/04/20 [ADD]
	$template->assign("RD_TESTER"          , makeRadioArray($GLOBALS["TesterMember"], "TESTER_FLG", $_POST["TESTER_FLG"]));	// 2020/04/21 [ADD]
	$template->assign("MAIL_ERROR_COUNT"   , $_POST["MAIL_ERROR_COUNT"], true);
	$template->assign("MAIL_ERROR_DT"      , $_POST["MAIL_ERROR_DT"], true);
	$template->assign("RD_AGENT"           , makeRadioArray($GLOBALS["AgentMember"], "AGENT_FLG", $_POST["AGENT_FLG"]));
	$template->assign("GIFT_AGENT_DISPNAME", GIFT_AGENT_DISPNAME, true);
	$template->assign("GIFT_POINT"         , $_POST["GIFT_POINT"], true);
	$template->assign("TOTAL_GIFT_POINT"   , $_POST["TOTAL_GIFT_POINT"], true);

	// 表示制御
	$template->if_enable("WITHDRAWAL", (int)$_POST["STATE"] == 9);	// 2020/04/21 [UPD]
	$template->if_enable("NEW"  , mb_strlen($_POST["NO"]) == 0);
	$template->if_enable("EDIT" , mb_strlen($_POST["NO"]) > 0);
	$template->if_enable("CANT_EDIT" , !($_POST["STATE"] == 9));
	$template->if_enable("IS_PARENT", mb_strlen($_POST["INVITED_BY_NO"]) > 0);
	$template->if_enable("EXT_ERROR_DT", mb_strlen($_POST["MAIL_ERROR_DT"]) > 0);
	$template->if_enable("AUTH_MEMBER_MOBILE", AUTH_MEMBER_MOBILE);
	$template->if_enable("AUTH_MOBILE_VALID_DAYS", AUTH_MOBILE_VALID_DAYS > 0);		// 2020/12/25 [ADD]
	$template->if_enable("EXT_MOBILE_UPD_DT" , mb_strlen($_POST["MOBILE_UPD_DT"]) > 0);
	$template->if_enable("GIFT_AGENT"        , GIFT_AGENT);
	$template->if_enable("GIFT_LIMIT"        , GIFT_LIMIT);
	$template->if_enable("BLACK" , (int)$_POST["BLACK_FLG"] == 1);		// 2024/03/06 [ADD]

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
	getData($_POST , array("NO", "NICKNAME", "MAIL", "SEX", "BIRTH", "STATE", "STATUS", "DRAW_POINT", "BLACK_FLG", "BLACK_REASON", "QUIT_REASON"
						, "JOIN_DT", "LAST_LOGIN_DT", "LAST_LOGIN_UA", "WITHDRAWAL_DT", "WITHDRAWAL_REASON", "REMARKS", "MAGAZINE", "FIRST_NAME", "LAST_NAME"
						, "TEMP_DT", "QUIT_DT", "TESTER_FLG", "H_PLAY_POINT", "BLACK_DT", "INVITE_CD", "INVITED_BY_NO", "INVITED_BY_NAME"
						, "MAIL_ERROR_COUNT", "MAIL_ERROR_DT", "MOBILE", "MOBILE_UPD_DT", "INTERNATIONAL_CD", "PASS", "AGENT_FLG", "GIFT_POINT", "TOTAL_GIFT_POINT"
						, "MOBILE_CHECKED_DT", "MOBILE_CHECKED_TIME"	// 2020/12/25 [ADD]
						));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	if (AUTH_MEMBER_MOBILE) $_POST["MOBILE"] = (int)$_POST["MOBILE"];	// 先頭Zero除去
	$mobileChkDt = ((mb_strlen($_POST["MOBILE_CHECKED_DT"]) > 0) ? $_POST["MOBILE_CHECKED_DT"] . " " . $_POST["MOBILE_CHECKED_TIME"] : "");	// 2020/12/25 [ADD]


	// 更新処理
	$mode = "";

	// トランザクション開始
	$template->DB->autoCommit(false);

	// 2024/03/06 [UPD Start] ブラック解除追加
	if ($_GET["ACT"] == "release") {
		// ブラック解除
		$mode = "release";
		// 会員マスタ更新
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_member" )
				->set()
					->value( "state"          , 1, FD_NUM)
					->value( "black_flg"      , 0, FD_NUM)
					->value( "black_dt"       , null, FD_FUNCTION)
					->value( "quit_dt"        , null, FD_FUNCTION)
					->value( "upd_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "upd_dt"         , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "member_no ="      , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
		syslog(LOG_INFO, "[BlackRelease] member_no:" . $_GET["NO"] . " admin_no:" . $template->Session->AdminInfo["admin_no"]);

	}else{
		if (mb_strlen($_POST["NO"]) > 0) {
			// 更新
			$mode = "update";
			
			// 現在の会員状態取得
			$sql = (new SqlString())
					->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
						->field("member_no, draw_point")
						->field( "state, join_dt, black_flg" )
						->from("mst_member")
						->where()
							->and( "member_no =", $_POST["NO"], FD_NUM)
					->createSQL();
			$mem = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

			$joindate = "";
			$invCode = "";
			$_draw_fixed  = 0;
			$blackdt = "";
			$state = $_POST["STATUS"];
			// ブラック判定
			if( $mem["black_flg"] == 0 && $_POST["BLACK_FLG"] == 1){
				$blackdt = "current_timestamp";
				if ($state != "9") $state = "9";	// 強制的に退会にする
			}

			// 本登録チェック
			if ($state == "1" && $mem["state"] != "1") {
				$joindate = "current_timestamp";	// 入会日時
					// 招待コード発行
					$invCode = $template->makeRandStr(12);
					while(true){
						// 招待コードブッキングチェック
						$hasInviteCode = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
							->select()
							->field( "count(*)" )
							->from( "mst_member mm" )
							->where()
								->and( "mm.invite_cd = ", $invCode, FD_STR)
								->and( "mm.state = ", "1", FD_NUM)
							->createSql();
						$codeCount = $template->DB->getOne( $hasInviteCode);
						if( $codeCount > 0){
							$_codeBooking = true;
							$invCode = $template->makeRandStr(12);
						}else{
							break;
						}
					}
			}

			//ポイント差分
			if( $mem["draw_point"] != $_POST["DRAW_POINT"]){
				$_draw_fixed = $_POST["DRAW_POINT"] - $mem["draw_point"];
			}
			if( $_draw_fixed != 0){
				$DPOINT  = new PlayPoint($template->DB, false);
				if ( $DPOINT->addDrawPoint( $mem["member_no"], "91", $_draw_fixed, "", "管理者による手動変更", $template->Session->AdminInfo["admin_no"] )){
					//
				} else {
					DispDetail($template, $DPOINT->getError());
					return;
				}
			}

			// メールエラークリア
			$mailErrorDt = "";
			if (mb_strlen($_POST["MAIL_ERROR_DT"]) > 0 && $_POST["MAIL_ERROR_COUNT"] == 0) $mailErrorDt = "NULL";
			
			// 更新
			// 2020/12/25 [UPD Start]
			$sqls = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_member" )
					->set()
						->value( "nickname",          $_POST["NICKNAME"], FD_STR)
						->value( true, "last_name",   $_POST["LAST_NAME"], FD_STR)
						->value( true, "first_name",  $_POST["FIRST_NAME"], FD_STR)
						->value( "mail",              $_POST["MAIL"], FD_STR)
						->value(SQL_CUT, "mobile",    $_POST["MOBILE"], FD_STR)
						->value(SQL_CUT, "international_cd", $_POST["INTERNATIONAL_CD"], FD_STR)
						->value( "sex",               $_POST["SEX"], FD_NUM)
						->value( "state",             $state, FD_NUM)		// 2020/04/21 [UPD]
						->value( "draw_point",        $_POST["DRAW_POINT"], FD_NUM)
						->value( "birthday",          $_POST["BIRTH"], FD_DATEEX)
						->value( "remarks",           $_POST["REMARKS"], FD_STR)
						->value( "mail_magazine",     $_POST["MAGAZINE"], FD_NUM)
						->value( true, "quit_dt",     ($state=="9")? "current_timestamp":"", FD_FUNCTION)	// 2020/04/21 [UPD]
						->value( "upd_dt",            "current_timestamp", FD_FUNCTION)
						->value( "upd_no",            $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( true, "join_dt",     $joindate, FD_FUNCTION)
						->value( true, "invite_cd",   $invCode, FD_STR)
						->value( true, "black_flg",    $_POST["BLACK_FLG"], FD_NUM)
						->value( true, "black_reason", $_POST["BLACK_REASON"], FD_STR)
						->value( true, "black_dt",     $blackdt, FD_FUNCTION)
						->value("tester_flg",          $_POST["TESTER_FLG"], FD_NUM)	// 2020/04/21 [ADD]
						->value(SQL_CUT, "agent_flg",  $_POST["AGENT_FLG"], FD_NUM)
						->value(SQL_CUT, "gift_point", $_POST["GIFT_POINT"], FD_NUM)
						->value(SQL_CUT, "total_gift_point", $_POST["TOTAL_GIFT_POINT"], FD_NUM)
						->value( "mail_error_count",   $_POST["MAIL_ERROR_COUNT"], FD_NUM)
						->value(SQL_CUT, "mail_error_dt", $mailErrorDt, FD_FUNCTION)
					->where()
						->and( true, "member_no =", $_POST["NO"], FD_NUM);
			if (AUTH_MEMBER_MOBILE && AUTH_MOBILE_VALID_DAYS > 0) {
				$sqls->value("mobile_checked_dt", $mobileChkDt, FD_STR);	// 2020/12/25 [ADD]
			}
			$sql = $sqls->createSQL();
			// 2020/12/25 [UPD End]
			$template->DB->query($sql);
		
		} else {
			// 新規

			// 招待コード発行
			$_codeBooking = false;
			$invCode = $template->makeRandStr(12);
			while(true){
				// 招待コードブッキングチェック
				$hasInviteCode = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
					->field( "count(*)" )
					->from( "mst_member mm" )
					->where()
						->and( "mm.invite_cd = ", $invCode, FD_STR)
						->and( "mm.state = ", "1", FD_NUM)
					->createSql();
				$codeCount = $template->DB->getOne( $hasInviteCode);
				if( $codeCount > 0){
					$_codeBooking = true;
					$invCode = $template->makeRandStr(12);
				}else{
					$_codeBooking = false;
				}
				if( !$_codeBooking) break;
			}
			
			$mode = "new";
			$pass = $_POST["PASS"];
			$passHash = password_hash($pass, PASSWORD_DEFAULT);
			$registId = uniqid($template->makeRandStr(3));
			$registLimitDt = new DateTime("now");
			$registLimitDt->add(new DateInterval("P" . REGIST_LIMIT . "D"));
			
			// 2020/12/25 [UPD Start]
			$sqls = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_member" )
						->value( "mail",       $_POST["MAIL"], FD_STR)
						->value(SQL_CUT,"mobile", $_POST["MOBILE"], FD_STR)
						->value(SQL_CUT,"international_cd", $_POST["INTERNATIONAL_CD"], FD_STR)
						->value( "pass",       $passHash, FD_STR)
						->value( "nickname",   $_POST["NICKNAME"], FD_STR)
						->value( "sex",        $_POST["SEX"], FD_NUM)
						->value( "birthday",   $_POST["BIRTH"], FD_DATEEX)
						->value( "point",      "0", FD_NUM)
						->value( "draw_point", "0", FD_NUM)
						->value( "remarks",    $_POST["REMARKS"], FD_STR)
						->value( "state",      "1", FD_NUM)
						->value( "regist_id",  "0", FD_STR)
						->value( "invite_cd",   $invCode, FD_STR)
						->value( "join_dt",    "current_timestamp", FD_FUNCTION)
						->value("tester_flg",  $_POST["TESTER_FLG"], FD_NUM)	// 2020/04/21 [ADD]
						->value(SQL_CUT, "agent_flg",  $_POST["AGENT_FLG"], FD_NUM)
						->value( "add_dt",     "current_timestamp", FD_FUNCTION)
						->value( "add_no",     $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt",     "current_timestamp", FD_FUNCTION)
						->value( "upd_no",     $template->Session->AdminInfo["admin_no"], FD_NUM);
			if (AUTH_MEMBER_MOBILE && AUTH_MOBILE_VALID_DAYS > 0) {
				$sqls->value("mobile_checked_dt", $mobileChkDt, FD_STR);	// 2020/12/25 [ADD]
			}
			$sql = $sqls->createSQL();
			// 2020/12/25 [UPD End]
			$template->DB->query($sql);
		}
	}
	// 2024/03/06 [UPD End]

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end&ACT=" . $mode);
}

/**
 * CSVダウンロード処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcOutput($template) {

	// データ取得
	getData($_GET , array("ODR"
						, "S_MEMBER_NO", "S_NICKNAME", "S_MAIL", "S_BLACK_FLG", "S_INVITE_CD"
						, "S_PLAY_POINT_FROM", "S_PLAY_POINT_TO", "S_DRAW_POINT_FROM", "S_DRAW_POINT_TO"
						, "JOIN_DT_FROM", "JOIN_DT_TO", "S_STATUS"
						, "S_TESTER", "S_MOBILE", "S_AGENT"
						));

	// 初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "member_no desc";

	$outData = array();
	// 2020/12/25 [UPD Start] IP追加
	$hed = $GLOBALS["csvMemberHeader"];
	$needsIP = (mb_strlen(CSV_MEMBER_IP) > 0);
	if ($needsIP) array_push($hed, CSV_MEMBER_IP);
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	array_push($outData, '"' . implode('","', $hed) . '"');		// 2020/04/23 [UPD]
	// 2020/12/25 [UPD End] IP追加

	
	// 検索用日付
	$joinSt = ((mb_strlen($_GET["JOIN_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["JOIN_DT_FROM"]) : "");
	$joinEd = ((mb_strlen($_GET["JOIN_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["JOIN_DT_TO"]) : "");

	// 検索SQL生成
	// 2020/12/25 [UPD Start] IP追加
	$sqls = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "m.member_no, m.nickname, m.mail, m.sex, m.birthday, m.point, m.draw_point, m.remarks, m.state, m.login_dt, m.join_dt, m.quit_dt" )
			->field( "CONCAT('[', m.international_cd, '] ', m.mobile) as mobile" )		// 2024/01/10 [ADD]
			->from( "mst_member m" )
			->from( "left join mst_member mm on mm.member_no = m.invite_member_no" )
			->where()
				->and( true, "m.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "m.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
			// 2024/01/10 [UPD Start]
			//	->and( true, "m.mail like ", ["%",$_GET["S_MAIL"]], FD_STR )
				->and( true, "m.mail like ", ["%",$_GET["S_MAIL"],"%"], FD_STR )
			// 2024/01/10 [UPD End]
				->and( true, "m.mobile like ", [$_GET["S_MOBILE"], "%"], FD_STR )
				->and( true, "m.state = ", $_GET["S_STATUS"], FD_NUM )
				->and( true, "m.join_dt >= ", $joinSt, FD_DATEEX )
				->and( true, "m.join_dt <= ", $joinEd, FD_DATEEX )
				->and( true, "m.point >= ", $_GET["S_PLAY_POINT_FROM"], FD_NUM )
				->and( true, "m.point <= ", $_GET["S_PLAY_POINT_TO"], FD_NUM )
				->and( true, "m.draw_point >= ", $_GET["S_DRAW_POINT_FROM"], FD_NUM )
				->and( true, "m.draw_point <= ", $_GET["S_DRAW_POINT_TO"], FD_NUM )
				->and( true, "m.black_flg = ", $_GET["S_BLACK_FLG"], FD_NUM )
				->and( true, "m.tester_flg = ", $_GET["S_TESTER"], FD_NUM )		// 2020/04/20 [ADD]
				->and( true, "m.agent_flg = ", $_GET["S_AGENT"], FD_NUM )
				->and( true, "mm.invite_cd = ", $_GET["S_INVITE_CD"], FD_STR )
			->orderby( "m." .$_GET["ODR"] );
	if ($needsIP) {
		$sqls->field("SUBSTR(m.login_ua, INSTR(m.login_ua, '[')) as IP");
	}
	$sql = $sqls->createSql("\n");

	// 2020/12/25 [UPD End] IP追加
	$outRs = $template->DB->query($sql);

	while ($row = $outRs->fetch(PDO::FETCH_ASSOC)) {
		// 特殊項目のみ編集
		$row["member_no"] = $template->formatMemberNo($row["member_no"]);
		$row["sex"] = (array_key_exists($row["sex"], $GLOBALS["SexList"]))
											 ? $GLOBALS["SexList"][$row["sex"]] : "";
		$row["birthday"] = format_date($row["birthday"]);
		$row["state"] = (array_key_exists($row["state"], $GLOBALS["MemberStatus"]))
											 ? $GLOBALS["MemberStatus"][$row["state"]] : "";
		$row["login_dt"] = format_datetime($row["login_dt"], false, true);
		$row["join_dt"] = format_datetime($row["join_dt"], false, true);
		$row["quit_dt"] = format_datetime($row["quit_dt"], false, true);
		if ($needsIP) $row["IP"] = str_replace(array("[", "]"), "", $row["IP"]);	// 2020/12/25 [ADD]

		$row = str_replace('"', '""', $row);
		array_push($outData, '"' . implode('","', $row) . '"');
	}
	unset($outRs);
	unset($row);

	// 出力文字列編集
	$ret = mb_convert_encoding(implode("\r\n", $outData), FILE_CSV_OUTPUT_ENCODE);		// 2020/04/23 [UPD]
	if (CSV_OUTPUT_BOM_ENC == FILE_CSV_OUTPUT_ENCODE && CSV_OUTPUT_SET_BOM) $ret = pack('C*',0xEF,0xBB,0xBF) . $ret;	// BOMを付ける
	$currentDatetime = date("YmdHis");

	// CSV用出力設定
	header('Cache-Control: public');
	header('Pragma: public');	// キャッシュを制限しない設定にする
	header("Content-Disposition: attachment; filename=Member_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

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
		case "pointupdate":
			// 更新
			$title = $template->message("A0022");
			$msg = $template->message("A0551");
			break;
		
		// 2024/03/06 [Add Start]
		case "release":
			// ブラック解除
			$title = $template->message("A0022");
			$msg = $template->message("A0551");
			break;
		// 2024/03/06 [Add End]

		case "update":
			// 更新
			$title = $template->message("A0022");
			$msg = $template->message("A0551");
			break;

		default:
			// 新規登録
			$title = $template->message("A0021");
			$msg = $template->message("A0554");
	}

	// 完了画面表示
	$template->dispProcEnd($title, "", $msg);
}



/**
 * ポイント修正画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispDetail2($template, $message = "") {
	// データ取得
	getData($_GET , array("NO"));
	getData($_POST , array("POINT", "TYPE", "LIMIT_FLG", "ADJUST_POINT"
						, "LIMIT_POINT"		// 2020/04/22 [ADD]
						));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 ){
			$_load = true;
		}else{
			$_load = false;
		}
	}else{
		$_load = false;
	}
	
	if( $_load ){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "mm.member_no, mm.point")
				->from( "mst_member mm" )
				->where()
					->and( "mm.member_no = ",   $_GET["NO"], FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// 2020/04/24 [ADD Start]データ不存在は通常あり得ないのでシステムエラー
		if (empty($row["member_no"])) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		// 2020/04/24 [ADD End]データ不存在は通常あり得ないのでシステムエラー
		$row["adjust_point"] = "";
		$row["type"]      = "1";
		$row["limit_flg"] = "";
		$limitPoint = GetLimitPoint($template, $_GET["NO"]);	// 2020/04/22 [ADD]
	}else{
		$row["member_no"]    = $_POST["NO"];
		$row["point"]        = $_POST["POINT"];
		$row["adjust_point"] = $_POST["ADJUST_POINT"];
		$row["type"]         = $_POST["TYPE"];
		$row["limit_flg"]    = $_POST["LIMIT_FLG"];
		$limitPoint          = $_POST["LIMIT_POINT"];	// 2020/04/22 [ADD] 本当は持ちまわしは危険ですが更新時にチェックし直すから許して
	}
	
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail2.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	
	$template->assign("NO"               , $row["member_no"], true);
	$template->assign("DSP_POINT"       , number_formatEx($row["point"]), true);		// 2020/04/22 [ADD]
	$template->assign("POINT"            , $row["point"], true);
	$template->assign("ADJUST_POINT"     , $row["adjust_point"], true);
	$template->assign("RDO_TYPE"         , makeRadioArray($GLOBALS["pointSumType"], "TYPE", $row["type"]));
	$template->assign("CHK_LIMIT_FLG"    , ($row["limit_flg"]=="1")? 'checked=""':"");
	// 2020/04/22 [ADD Start] 期限付ポイント
	$template->assign("DSP_LIMIT_POINT"  , number_formatEx($limitPoint), true);
	$template->assign("LIMIT_POINT"      , $limitPoint, true);
	$template->if_enable("EXIST_LIMIT_POINT", $limitPoint > 0);
	// 2020/04/22 [ADD End] 期限付ポイント

	
	// 表示
	$template->flush();
}

/**
 * ポイント修正処理登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData2($template) {

	// データ取得
	getData($_GET , array("ACT", "NO"));
	getData($_POST , array("NO", "POINT", "ADJUST_POINT", "TYPE", "LIMIT_FLG"));
	
	// 入力チェック
	$message = checkInput2($template);
	if (mb_strlen($message) > 0) {
		DispDetail2($template, $message);
		return;
	}
	
	// 更新処理
	$mode = "pointupdate";
	$_sum = ($_POST["TYPE"] == 1)? $_POST["ADJUST_POINT"]: $_POST["ADJUST_POINT"]*-1;
	// 2020/04/22 [UPD Start]
	$procCd = "91";		// 処理コード「91：手動調整」
	$reason = "管理者による手動変更";	// 加減算理由

	$PPOINT  = new PlayPoint($template->DB);

	$ret = true;	// 
	if ($_POST["TYPE"] == "2") {		// 減算
		$limitFlg = ($_POST["LIMIT_FLG"] == "1");	// 御紹介の無いパラだが多分 True：期限付含む、false：期限付含めない
		// addPointでは単なる減算は可能だが有効期限付を含むポイント減算が出来ないので減算はusePointを使用
		$ret = $PPOINT->usePoint($_POST["NO"], abs($_sum), $procCd, "", $reason, $template->Session->AdminInfo["admin_no"], $limitFlg);
	} else {
		$ret = $PPOINT->addPoint( $_POST["NO"], $procCd, $_sum, "", "", $reason, $template->Session->AdminInfo["admin_no"] );
	}
	// 結果判定 ※エラー時はPlayPointのエラーメッセージをそのまま採用
	if (!$ret) {
		DispDetail2($template, $PPOINT->getError("<br />"));	// getErrorのデフォの区切りは\nです
		return;
	}

	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end&ACT=" . $mode);
}


/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {

	$errMessage = array();
	if ($_GET["ACT"] != "del") {

		//-- 検索SQL生成
		// メアド重複
		$sqlMailDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(member_no)" )
			->from( "mst_member" )
			->where()
				->and( "mail = ", $_POST["MAIL"], FD_STR)
				->and( "state = ", "1", FD_NUM)
				->and( true, "member_no <> ", $_POST["NO"], FD_NUM)
		->createSql();
		// ブラックメアド
		$sqlMailBlack = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(member_no)" )
			->from( "mst_member" )
			->where()
				->and( "mail = ", $_POST["MAIL"], FD_STR)
				->and( "black_flg = ", "1", FD_NUM)
				->and( true, "member_no <> ", $_POST["NO"], FD_NUM)
		->createSql();

		// ニックネーム重複
		$sqlNicknameDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_member" )
			->where()
				->and( "nickname = ", $_POST["NICKNAME"], FD_STR)
				->and( "state != ", "9", FD_NUM)
				->and( true, "member_no <> ", $_POST["NO"], FD_NUM)
		->createSql();

		// 国際番号 + 携帯番号重複
		$sqlMobileDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(member_no)" )
			->from( "mst_member" )
			->where()
				->and( "mobile = ", (int)$_POST["MOBILE"], FD_STR)
				->and(SQL_CUT, "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
				->and( "state = ", "1", FD_NUM)
				->and(SQL_CUT, "member_no <> ", $_POST["NO"], FD_NUM)
		->createSql();
		// ブラック国際番号 + 携帯番号
		$sqlMobileBlack = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(member_no)" )
			->from( "mst_member" )
			->where()
				->and( "mobile = ", (int)$_POST["MOBILE"], FD_STR)
				->and(SQL_CUT, "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
				->and( "black_flg = ", "1", FD_NUM)
				->and(SQL_CUT, "member_no <> ", $_POST["NO"], FD_NUM)
		->createSql();

		// caseが使えないのでチェックする値を加工する
		if (GIFT_AGENT) {
			// 入力値を設定
			$giftPoint = $_POST["GIFT_POINT"];
		} else {
			// チェックOKを設定
			$giftPoint = 0;
		}
		if (GIFT_LIMIT) {
			// 入力値を設定
			$totalGiftPoint = $_POST["TOTAL_GIFT_POINT"];
		} else {
			// チェックOKを設定
			$totalGiftPoint = 0;
		}

		$errMessage = (new SmartAutoCheck($template))
			//Update判定
			->setUpdateMode( mb_strlen($_POST["NO"]) > 0 )		//更新モードにする判定を入れる
				//ニックネーム
				->item($_POST["NICKNAME"])
					->required("A0501")
					->countSQL("A0532", $sqlNicknameDupli)	// ニックネーム重複
				//メールアドレス
				->item($_POST["MAIL"])
					->required("A0502")
					->mail("A0503")
					->countSQL("A0531", $sqlMailDupli)	// メアド重複
					->countSQL("A0533", $sqlMailBlack)	// ブラックメアド
				//国際番号
				->item($_POST["INTERNATIONAL_CD"])
					->case(AUTH_MEMBER_MOBILE)	// 会員携帯番号認証時
						->required("A0542")			// 必須
				//携帯番号
				->item($_POST["MOBILE"])
					->case(AUTH_MEMBER_MOBILE)	// 会員携帯番号認証時
						->required("A0537")			// 必須
						->number("A0538")			// 半角数字
						->maxLength("A0539", 15)	// 文字長の最高値
						->if("A0543", (mb_strlen($_POST["INTERNATIONAL_CD"]) + mb_strlen((int)$_POST["MOBILE"])) <= 16)	// 16文字以内
						->countSQL("A0540", $sqlMobileDupli)	// 携帯番号重複
						->countSQL("A0541", $sqlMobileBlack)	// ブラック携帯番号
				// 2020/12/25 [ADD Start] 携帯番号認証日
				->item($_POST["MOBILE_CHECKED_DT"])
					->any()
						->date("A0520")
				// 2020/12/25 [End] 携帯番号認証日
				//パスワード
				->item($_POST["PASS"])
					->isInsert()								//新規登録時のみチェック
						->required("A0504")
						->minLength("A0505", MEMBER_PASS_MIN)	//文字長の最低値
						->maxLength("A0506", 20)				//文字長の最高値
						->alnum("A0517")						//英数字
						->if("A0518", (!preg_match("/" . MEMBER_PASS_PATTERN . "/", $_POST["PASS"])))		// 入力制約（パターンに一致しない場合エラー）
				// 生年月日
				->item($_POST["BIRTH"])
					->any()
						->date("A0509")
				//抽選ポイント（更新時のみ）
				->item($_POST["DRAW_POINT"])
					->isUpdate()
						->required("A0512")
						->number('A0513')
						->maxLength('A0519', 9)
				//メルマガ購読（更新時のみ）
				->item($_POST["MAGAZINE"])
					->isUpdate()
						->required("A0514")
				//状態（更新時のみ）
				->item($_POST["STATUS"])
					->isUpdate()
						->required("A0515")
				//ギフト可能ポイント（更新時のみ）
				->item($giftPoint)
					->isUpdate()
						->required("A0544")
						->number('A0545')
				//ギフト累計ポイント（更新時のみ）
				->item($totalGiftPoint)
					->isUpdate()
						->required("A0546")
						->number('A0547')
				//メール送信エラー回数（更新時のみ）
				->item($_POST["MAIL_ERROR_COUNT"])
					->isUpdate()
						->required("A0535")
						->number('A0536')
		->report();
		
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * ポイント調整入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput2($template) {
	$errMessage = array();

	// 2020/04/22 [ADD Start] RegistData2で$_POST["NO"]が空だったらと気持ち悪事をして挙句Warningを吐いたのでそれらしく書いただけ
	// 会員存在チェック
	$extMemberSql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from( "mst_member" )
			->where()
				->and( "member_no = ", $_POST["NO"], FD_NUM )
				->and( "state != ", "9", FD_NUM)
		->createSql();
	// 2020/04/22 [ADD End] RegistData2で$_POST["NO"]が空だったらと気持ち悪事をして挙句Warningを吐いたのでそれらしく書いただけ

	$errMessage = (new SmartAutoCheck($template))
		// 2020/04/22 [ADD Start] RegistData2で$_POST["NO"]が空だったらと気持ち悪事をして挙句Warningを吐いたのでそれらしく書いただけ
		// 会員NO
		->item($_POST["NO"])
			->noCountSQL("A0003", $extMemberSql)
		// 2020/04/22 [ADD End] RegistData2で$_POST["NO"]が空だったらと気持ち悪事をして挙句Warningを吐いたのでそれらしく書いただけ
		//プレイポイント
		->item($_POST["ADJUST_POINT"])
			->required("A0510")
			->number('A0511')
	->report();

	// 2020/04/22 [ADD Start] 減算時のポイントチェック
	if (count($errMessage) <= 0 && $_POST["TYPE"] == "2") {
		$limitPoint = 0;
		// 会員の現在ポイント取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("point")
				->from( "mst_member" )
				->where()
					->and( "member_no = ", $_POST["NO"], FD_NUM )
			->createSql();
		$memberPoint = (int)$template->DB->getOne($sql);

		if ($_POST["LIMIT_FLG"] != "1") {		// 有効期限付ポイントを含めない
			// 有効期限付ポイント取得
			$limitPoint = GetLimitPoint($template, $_POST["NO"]);
		}
		if ((int)$_POST["ADJUST_POINT"] > ($memberPoint - $limitPoint)) $errMessage[] = $template->message("A0516");
	}
	// 2020/04/22 [ADD End] 減算時のポイントチェック
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * 状態表示制御用の配列成型
 * @access	private
 * @param	array	$ary		会員の状態リスト($GLOBALS["MemberStatus"])
 * @param	num		$status		会員の状態情報
 * @return	array				表示する状態リスト
 */
function _chkStatusArray( $ary, $status){
	$ret = [];
	if( $status == "0"){
		//仮登録の場合
		$ret[0] = $ary[0];
		$ret[1] = $ary[1];
	}else if( $status == "1"){
		//本登録の場合
		$ret[1] = $ary[1];
		$ret[9] = $ary[9];
	}else if( $status == "9"){
		$ret[9] = $ary[9];
	}else{
		$ret = $ary;
	}
	return $ret;
}

// 2020/04/22 [ADD Start] 減算時ポイント関連
/**
 * 有効期限付ポイント取得
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	int		$memberNo	会員NO
 * @return	int		会員の期限付ポイント
 */
function GetLimitPoint($template, $memberNo){
	// 有効期限付ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("IFNULL(sum(valid_point), 0)")
		->from("his_pointLimit")
		->where()
			->and( "member_no =", $memberNo, FD_NUM)
			->and( "valid_point >", "0", FD_NUM )
			->and( "limit_dt IS NOT NULL" )
		->createSQL("\n");
	return (int)$template->DB->getOne($sql);
}
// 2020/04/22 [ADD End] 減算時ポイント関連
?>
