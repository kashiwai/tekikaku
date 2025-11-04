<?php
/*
 * giftaddset.php
 * 
 * (C)SmartRams Co.,Ltd. 2020 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * ギフト枠加算設定画面表示
 * 
 * ギフト枠加算設定画面の表示を行う
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since    2020/09/07 初版作成 岡本静子
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "addset_type asc, base_val asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	

	// ポイント変換設定取得
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field( "convert_no, convert_name" )
		->where()
			->and( "del_flg = ", 0, FD_NUM )
		->from("mst_convertPoint")
		->createSql();
	$cnvPoint = $template->DB->getAll($sql, PDO::FETCH_KEY_PAIR);

	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->where()
				->and( "del_flg = ", 0, FD_NUM )
			->from("mst_gift_addset")
		->createSql();

	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;

	$rsql = $sqls
			->resetField()
			->field("addset_no, addset_type, add_point, base_val")
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
	$template->assign("ALLROW"  , number_formatEx($allrows));	// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));	// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));	// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);

	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("ADDSET_NO"       , $row["addset_no"], true);
		$template->assign("ADDSET_NO_PAD"   , $template->formatNoBasic($row["addset_no"]), true);
		$template->assign("ADDSET_TYPE_NAME", $template->getArrayValue($GLOBALS["GiftAddSetTypeList"], $row["addset_type"]), true);
		if ($row["addset_type"] == 1) {		// プレイゲーム数
			$baseVal = $template->getArrayValue($cnvPoint, $row["base_val"]);
		} else {
			$baseVal = number_formatEx($row["base_val"]);
		}
		$template->assign("BASE_VAL"        , $baseVal, true);
		$template->assign("VALUNIT"         , $template->getArrayValue($GLOBALS["GiftAddSetValUnitList"], $row["addset_type"]), true);
		$template->assign("ADD_POINT"       , number_formatEx($row["add_point"]), true);
		
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
	getData($_GET , array("NO"));
	getData($_POST , array("ADDSET_TYPE", "BASE_VAL", "ADD_POINT"));

	if (mb_strlen($_GET["NO"]) > 0 && mb_strlen($message) == 0) {
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("addset_no, addset_type, add_point, base_val")
				->from("mst_gift_addset")
				->where()
					->and("addset_no = ",  $_GET["NO"], FD_NUM )
					->and("del_flg = "  ,  0, FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$_POST["ADDSET_TYPE"] = $row["addset_type"];
		$_POST["BASE_VAL"] = $row["base_val"];
		$_POST["ADD_POINT"] = $row["add_point"];
	}
	// ポイント変換設定取得
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field( "convert_no, convert_name" )
		->where()
			->and( "del_flg = ", 0, FD_NUM )
		->from("mst_convertPoint")
		->createSql();
	$cnvPoint = $template->DB->getAll($sql, PDO::FETCH_KEY_PAIR);

	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("NEW_EDIT"     , mb_strlen($_GET["NO"]) <= 0);
	$template->if_enable("UPD_EDIT"     , mb_strlen($_GET["NO"]) > 0);

	$template->assign("ADDSET_NO_PAD"   , $template->formatNoBasic($_GET["NO"]), true);
	$template->assign("NO"              , $_GET["NO"], true);
	$template->assign("ADDSET_TYPE"     , $_POST["ADDSET_TYPE"], true);
	$template->assign("ADDSET_TYPE_NAME", $template->getArrayValue($GLOBALS["GiftAddSetTypeList"], $_POST["ADDSET_TYPE"]), true);
	$template->assign("SEL_ADDSET_TYPE" , makeOptionArray($GLOBALS["GiftAddSetTypeList"], $_POST["ADDSET_TYPE"], false));
	$template->assign("SEL_BASE_VAL"    , makeOptionArray($cnvPoint, $_POST["BASE_VAL"], false));
	$template->assign("BASE_VAL"        , $_POST["BASE_VAL"], true);
	$template->assign("ADD_POINT"       , $_POST["ADD_POINT"], true);
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
	getData($_GET, array("NO", "ACT"));
	getData($_POST, array("ADDSET_TYPE", "BASE_VAL", "ADD_POINT"));

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
			->update("mst_gift_addset" )
				->set()
					->value("del_flg", 1, FD_NUM)
					->value("del_no" , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value("del_dt" , "current_timestamp", FD_FUNCTION)
				->where()
					->and("addset_no = ", $_GET["NO"], FD_NUM )
					->and("del_flg = "  , 0, FD_NUM )
			->createSQL("\n");
		$template->DB->exec($sql);
	}else{

		if (mb_strlen($_GET["NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_gift_addset" )
					->set()
						->value("add_point", $_POST["ADD_POINT"], FD_NUM)
						->value("base_val" , $_POST["BASE_VAL"], FD_NUM)
						->value("upd_no"   , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt"   , "current_timestamp", FD_FUNCTION)
					->where()
						->and("addset_no = ", $_GET["NO"], FD_NUM )
						->and("del_flg = "  , 0, FD_NUM )
				->createSQL("\n");
			$template->DB->exec($sql);
		}else{
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into("mst_gift_addset" )
						->value("addset_type", $_POST["ADDSET_TYPE"], FD_NUM)
						->value("add_point"  , $_POST["ADD_POINT"], FD_NUM)
						->value("base_val"   , $_POST["BASE_VAL"], FD_NUM)
						->value("upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt", "current_timestamp", FD_FUNCTION)
						->value("add_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("add_dt", "current_timestamp", FD_FUNCTION)
				->createSQL("\n");
			$template->DB->exec($sql);
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
			$title = $template->message("A2750");
			$msg = $template->message("A2751");
			break;
		case "del":
			// 削除
			$title = $template->message("A2752");
			$msg = $template->message("A2753");
			break;
		default:
			// 新規登録
			$title = $template->message("A2748");
			$msg = $template->message("A2749");
	}
	// 完了画面表示
	$template->dispProcEnd($title, "", $msg);
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	string				エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();

	// 累計ポイント重複チェック
	$sqlDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_gift_addset")
		->where()
			->and("addset_type = ", $_POST["ADDSET_TYPE"], FD_NUM)
			->and("base_val = "   , $_POST["BASE_VAL"], FD_NUM)
			->and("del_flg != "        , "1", FD_NUM)
			->and(SQL_CUT, "addset_no != "    , $_GET["NO"], FD_NUM)
	->createSql("\n");

	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			// 集計タイプ
			->item($_POST["ADDSET_TYPE"])
				->required("A2740")
			// 基準値
			->item($_POST["BASE_VAL"])
				->required("A2741")
				->number('A2742')
				->if("A2743", (int)$_POST["BASE_VAL"] > 0)	// 1以上
				->countSQL("A2747" , $sqlDupli)	// 重複
			// 加算ポイント
			->item($_POST["ADD_POINT"])
				->required("A2744")
				->number('A2745')
				->if("A2746", (int)$_POST["ADD_POINT"] > 0)	// 1以上
		->report();
	}
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
