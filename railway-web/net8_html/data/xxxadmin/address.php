<?php
/*
 * address.php
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
 * 宛先管理画面表示
 * 
 * 宛先管理画面の表示を行う
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
	getData($_GET , array("P", "ODR"
							, "S_MEMBER_NO","S_NICKNAME", "S_POSTAL", "S_NAME", "S_ADDRESS", "S_TEL"));
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "da.member_no desc";//"seq asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//検索判定
	if( ($_GET["S_MEMBER_NO"]!="") || ($_GET["S_NICKNAME"]!="") || ($_GET["S_POSTAL"]!="") || ($_GET["S_NAME"]!="") || ($_GET["S_ADDRESS"]!="") || ($_GET["S_TEL"]!="")){
		$_search = "show";
	}else{
		$_search = "";
	}
	
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("dat_address da" )
			->from("inner join mst_member mm on da.member_no = mm.member_no" )
			->where()
				->and( "da.del_flg <> 1 ")
				->and( true, "mm.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "mm.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
				->groupStart()
					->or( true, "da.syll like ", ["%",$_GET["S_NAME"],"%"], FD_STR )
					->or( true, "da.name like ", ["%",$_GET["S_NAME"],"%"], FD_STR )
				->groupEnd()
				->and( true, "da.postal like ", ["%",$_GET["S_POSTAL"],"%"], FD_STR )
				->groupStart()
					->or( true, "da.address1 like ", ["%",$_GET["S_ADDRESS"],"%"], FD_STR )
					->or( true, "da.address2 like ", ["%",$_GET["S_ADDRESS"],"%"], FD_STR )
					->or( true, "da.address3 like ", ["%",$_GET["S_ADDRESS"],"%"], FD_STR )
					->or( true, "da.address4 like ", ["%",$_GET["S_ADDRESS"],"%"], FD_STR )
				->groupEnd()
				->and( true, "da.tel like ", ["%",$_GET["S_TEL"],"%"], FD_STR )
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("da.member_no, da.seq, da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.upd_dt")
			->field("mm.mail, mm.last_name, mm.first_name, mm.nickname")
			->field("mm.black_flg, mm.tester_flg, mm.state as member_state")
			->orderby( $_GET["ODR"] )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_MEMBER_NO"         , $_GET["S_MEMBER_NO"], true);
	$template->assign("S_NICKNAME"          , $_GET["S_NICKNAME"], true);
	$template->assign("S_POSTAL"            , $_GET["S_POSTAL"], true);
	$template->assign("S_NAME"              , $_GET["S_NAME"], true);
	$template->assign("S_ADDRESS"           , $_GET["S_ADDRESS"], true);
	$template->assign("S_TEL"               , $_GET["S_TEL"], true);
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["member_state"] == 9);
		
		$template->assign("SEQ"          , $row["seq"], true);
		$template->assign("MEMBER_NO"    , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD", $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"     , $row["nickname"], true);
		$template->assign("SYLL"         , $row["syll"], true);
		$template->assign("NAME"         , $row["name"], true);
		$template->assign("POSTAL"       , $row["postal"], true);
		$template->assign("ADDRESS1"     , $row["address1"], true);
		$template->assign("ADDRESS2"     , $row["address2"], true);
		$template->assign("ADDRESS3"     , $row["address3"], true);
		$template->assign("ADDRESS4"     , $row["address4"], true);
		$template->assign("TEL"          , $row["tel"], true);
		
		$template->loop_next();
	}
	unset($rs);
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
	// データ取得
	getData($_GET , array("SEQ", "NO"));
	getData($_POST, array("SEQ", "MEMBER_NO", "SYLL", "NAME", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL"
						, "NICKNAME", "BLACK_FLG", "TESTER_FLG", "MEMBER_STATE"
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
		$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field("da.member_no, da.seq, da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg, da.upd_dt")
				->field("mm.mail, mm.last_name, mm.first_name, mm.nickname")
				->field("mm.black_flg, mm.tester_flg, mm.state as member_state")
				->from( "dat_address da" )
				->from("left join mst_member mm on mm.member_no = da.member_no" )
				->where()
					->and( "da.seq = ",   $_GET["SEQ"], FD_NUM )
					->and( "da.member_no = ",   $_GET["NO"], FD_NUM )
					->and( "da.del_flg <> 1 ")
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if (empty($row["member_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
	}else{
		$row["seq"]       = $_POST["SEQ"];
		$row["member_no"] = $_POST["MEMBER_NO"];
		$row["syll"]      = $_POST["SYLL"];
		$row["name"]      = $_POST["NAME"];
		$row["postal"]    = $_POST["POSTAL"];
		$row["address1"]  = $_POST["ADDRESS1"];
		$row["address2"]  = $_POST["ADDRESS2"];
		$row["address3"]  = $_POST["ADDRESS3"];
		$row["address4"]  = $_POST["ADDRESS4"];
		$row["tel"]       = $_POST["TEL"];
		$row["nickname"]  = $_POST["NICKNAME"];
		$row["black_flg"]    = $_POST["BLACK_FLG"];
		$row["tester_flg"]   = $_POST["TESTER_FLG"];
		$row["member_state"] = $_POST["MEMBER_STATE"];
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
	$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
	$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["member_state"] == 9);
	
	$template->assign("SEQ"            , $row["seq"], true);
	$template->assign("NO"             , $row["member_no"], true);
	$template->assign("MEMBER_NO"      , $row["member_no"], true);
	$template->assign("MEMBER_NO_PAD"  , $template->formatMemberNo($row["member_no"]), true);
	$template->assign("NICKNAME"       , $row["nickname"], true);
	$template->assign("SYLL"           , $row["syll"], true);
	$template->assign("NAME"           , $row["name"], true);
	$template->assign("POSTAL"         , $row["postal"], true);
	$template->assign("ADDRESS1"       , $row["address1"], true);
	$template->assign("ADDRESS2"       , $row["address2"], true);
	$template->assign("ADDRESS3"       , $row["address3"], true);
	$template->assign("ADDRESS4"       , $row["address4"], true);
	$template->assign("TEL"            , $row["tel"], true);

	$template->assign("BLACK_FLG"      , $row["black_flg"], true);
	$template->assign("TESTER_FLG"     , $row["tester_flg"], true);
	$template->assign("MEMBER_STATE"   , $row["member_state"], true);

	
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
	getData($_GET , array("SEQ", "NO", "ACT"));
	getData($_POST, array("SEQ", "MEMBER_NO", "SYLL", "NAME", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL"));
	
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
			->update( "dat_address" )
				->set()
					->value( "del_flg"          , 1, FD_NUM)
					->value( "del_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"           , "current_timestamp", FD_FUNCTION)
				->where()
						->and( "seq = "         , $_GET["SEQ"], FD_NUM )
						->and( "member_no = "   , $_GET["NO"], FD_NUM )
			->createSQL();
		$template->DB->query($sql);
		
		// デフォルト使用を更新
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("count(*)")
				->from("dat_address da")
				->where()
					->and( false, "da.member_no = ",  $_GET["NO"], FD_NUM)
					->and( false, "da.use_flg = ",   "1", FD_NUM)
					->and( false, "da.del_flg <> ",  "1", FD_NUM)
				->createSQL();
		$cnt = $template->DB->getOne($sql);
		
		if( $cnt == 0){
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_address" )
					->set()
						->value( "use_flg"           , 1, FD_NUM)
						->value( "upd_no"            , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"            , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "member_no = "  , $_GET["NO"], FD_NUM)
						->and( false, "del_flg <> ",  "1", FD_NUM)
					->limit(1)
					->orderby('seq asc')
				->createSQL();
			$template->DB->query($sql);
		}
		
	}else{
		if (mb_strlen($_POST["MEMBER_NO"]) > 0) {
			// 更新
			$mode = "update";
			
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_address" )
					->set()
						->value( "syll"         , $_POST["SYLL"], FD_STR)
						->value( "name"         , $_POST["NAME"], FD_STR)
						->value( "postal"       , $_POST["POSTAL"], FD_STR)
						->value( "address1"     , $_POST["ADDRESS1"], FD_STR)
						->value( "address2"     , $_POST["ADDRESS2"], FD_STR)
						->value( "address3"     , $_POST["ADDRESS3"], FD_STR)
						->value( "address4"     , $_POST["ADDRESS4"], FD_STR)
						->value( "tel"          , $_POST["TEL"], FD_STR)
						//
						->value( "upd_no"       , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
					->where()
							->and( "seq = "     , $_POST["SEQ"], FD_NUM )
							->and( "member_no = " , $_POST["MEMBER_NO"], FD_NUM )
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
			$title = $template->message("A2490");
			$msg = $template->message("A2491");
			break;
		case "del":
			// 削除
			$title = $template->message("A2492");
			$msg = $template->message("A2493");
			break;
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
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
		$errMessage = (new SmartAutoCheck($template))
			->item($_POST["SYLL"])
				->required("A2402")
			->item($_POST["NAME"])
				->required("A2403")
			->item($_POST["POSTAL"])
				->required("A2404")
				->number("A2405")
			->item($_POST["ADDRESS1"])
				->required("A2406")
			->item($_POST["ADDRESS2"])
				->required("A2407")
			->item($_POST["ADDRESS3"])
				->required("A2408")
			->item($_POST["TEL"])
				->required("A2409")
				->number("A2410")
		->report();
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}



?>
