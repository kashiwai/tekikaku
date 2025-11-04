<?php
/*
 * pointconvert.php
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
 * ポイント変換管理画面表示
 * 
 * ポイント変換管理画面の表示を行う
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

	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "convert_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->where()
				->and( "mcp.del_flg = ", 0, FD_NUM )
			->from("mst_convertPoint mcp")
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mcp.convert_no, mcp.convert_name, mcp.point, mcp.credit, mcp.draw_point, mcp.del_flg, mcp.upd_dt")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
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
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("CONVERT_NO_PAD"     , $template->formatNoBasic( $row["convert_no"]), true);
		$template->assign("CONVERT_NO"         , $row["convert_no"], true);
		$template->assign("CONVERT_NAME"       , $row["convert_name"], true);
		$template->assign("POINT"              , number_formatEx($row["point"]), true);
		$template->assign("CREDIT"             , number_formatEx($row["credit"]), true);
		$template->assign("DRAW_POINT"         , number_formatEx($row["draw_point"]), true);
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
	// データ取得
	getData($_GET , array("NO", "ACT"));
	getData($_POST , array("CONVERT_NO", "CONVERT_NAME", "POINT", "CREDIT", "DRAW_POINT", "UPD_DT"));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 ){
			$_load = true;
		}else{
			$_load = false;
		}
	}else{
		$_load = false;
	}
	
	if( $_load || $_GET["ACT"] == "del" ){
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mcp.convert_no, mcp.convert_name, mcp.point, mcp.credit, mcp.draw_point, mcp.add_dt, mcp.upd_dt")
				->from("mst_convertPoint mcp")
				->where()
					->and( "mcp.convert_no = ",   $_GET["NO"], FD_NUM )
					->and( "mcp.del_flg = "   ,   0, FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$row["upd_dt"] = format_datetime($row["upd_dt"]);	// 2020/05/15 [ADD]
	}else{
		//POST or 新規
		$row["convert_no"]    = $_POST["CONVERT_NO"];
		$row["convert_name"]  = $_POST["CONVERT_NAME"];
		$row["point"]         = $_POST["POINT"];
		$row["credit"]        = $_POST["CREDIT"];
		$row["draw_point"]    = $_POST["DRAW_POINT"];
		$row["upd_dt"]        = $_POST["UPD_DT"];	// 2020/05/15 [ADD]
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("UPD"          , mb_strlen($row["convert_no"]) > 0);
	$template->assign("NO"              , $row["convert_no"], true);
	$template->assign("CONVERT_NO_PAD"  , $template->formatNoBasic( $row["convert_no"]), true);
	$template->assign("CONVERT_NO"      , $row["convert_no"], true);
	$template->assign("CONVERT_NAME"    , $row["convert_name"], true);
	$template->assign("POINT"           , $row["point"], true);
	$template->assign("CREDIT"          , $row["credit"], true);
	$template->assign("DRAW_POINT"      , $row["draw_point"], true);
	$template->assign("UPD_DT"          , $row["upd_dt"], true);	// 2020/05/15 [UPD]
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
	getData($_POST , array("CONVERT_NO", "CONVERT_NAME", "POINT", "CREDIT", "DRAW_POINT", "UPD_DT"));
	
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
		
		// パラチェック
		if (mb_strlen($_GET["NO"]) == 0) {
			DispDetail($template, $template->message("A0003"));
			return;
		}
		
		// 実機が紐づいているデータは削除不可
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("count(machine_no)")
				->from("dat_machine")
				->where()
					->and( "del_flg = ", 0, FD_NUM )
					->and( "convert_no = ", $_GET["NO"], FD_NUM )
			->createSql();
		$cnt = $template->DB->getOne($sql);
		if ($cnt > 0) {
			DispDetail($template, $template->message("A1609"));
			return;
		}
		
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_convertPoint" )
				->set()
					->value( "del_flg"    , 1, FD_NUM)
					->value( "del_no"     , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"     , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "convert_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}else{
		
		if (mb_strlen($_POST["CONVERT_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_convertPoint" )
					->set()
						->value( "convert_name" , $_POST["CONVERT_NAME"], FD_STR)
						->value( "point"        , $_POST["POINT"], FD_NUM)
						->value( "credit"       , $_POST["CREDIT"], FD_NUM)
						->value( "draw_point"   , $_POST["DRAW_POINT"], FD_NUM)
						->value( "upd_no"       , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "convert_no =" , $_POST["CONVERT_NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}else{
			
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_convertPoint" )
						->value( "convert_name"    , $_POST["CONVERT_NAME"], FD_STR)
						->value( "point"           , $_POST["POINT"], FD_NUM)
						->value( "credit"          , $_POST["CREDIT"], FD_NUM)
						->value( "draw_point"      , $_POST["DRAW_POINT"], FD_NUM)
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
			$title = $template->message("A1662");
			$msg = $template->message("A1663");
			break;
		case "del":
			// 削除
			$title = $template->message("A1664");
			$msg = $template->message("A1665");
			break;
		default:
			// 新規登録
			$title = $template->message("A1660");
			$msg = $template->message("A1661");
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

	// 2020/05/15 [ADD Start]
	// 名称
	$sqlNameDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_convertPoint")
		->where()
			->and( "convert_name = ", $_POST["CONVERT_NAME"], FD_STR)
			->and( "del_flg != ", "1", FD_NUM)
			->and( true, "convert_no <> ", $_POST["CONVERT_NO"], FD_NUM)
	->createSql("\n");
	// 2020/05/15 [ADD End]
	
	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			
			//名称
			->item($_POST["CONVERT_NAME"])
				->required("A1601")
				->maxLength("A1602", 20)				//文字長の最高値
				->countSQL("A1611" , $sqlNameDupli)		// 2020/05/15 [ADD] 重複
			//ポイント
			->item($_POST["POINT"])
				->required("A1603")
				->number('A1604')
			//クレジット
			->item($_POST["CREDIT"])
				->required("A1605")
				->number('A1606')
				->if("A1610", (int)$_POST["CREDIT"] > 0)	// 2020/05/15 [ADD] 1以上
			//抽選ポイント
			->item($_POST["DRAW_POINT"])
				->required("A1607")
				->number('A1608')
		->report();
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
