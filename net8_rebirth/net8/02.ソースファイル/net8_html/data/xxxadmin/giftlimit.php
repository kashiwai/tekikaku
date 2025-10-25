<?php
/*
 * giftlimit.php
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
 * ギフト日制限設定画面表示
 * 
 * ギフト日制限設定画面の表示を行う
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since    2020/09/03 初版作成 岡本静子
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "total_gift_point asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->where()
				->and( "mst.del_flg = ", 0, FD_NUM )
			->from("mst_gift_limit mst")
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mst.no, mst.total_gift_point, mst.gift_limit")
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
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->assign("NO"              , $row["no"], true);
		$template->assign("NO_PAD"          , $template->formatNoBasic($row["no"]), true);
		$template->assign("TOTAL_GIFT_POINT", number_formatEx($row["total_gift_point"]), true);
		$template->assign("GIFT_LIMIT"      , number_formatEx($row["gift_limit"]), true);
		
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
	getData($_POST , array("TOTAL_GIFT_POINT", "GIFT_LIMIT"));
	
	if (mb_strlen($_GET["NO"]) > 0 && mb_strlen($message) == 0) {
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("no, total_gift_point, gift_limit")
				->from("mst_gift_limit")
				->where()
					->and("no = "     ,  $_GET["NO"], FD_NUM )
					->and("del_flg = ",  0, FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$_POST["TOTAL_GIFT_POINT"] = $row["total_gift_point"];
		$_POST["GIFT_LIMIT"] = $row["gift_limit"];
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("NEW_EDIT"     , mb_strlen($_GET["NO"]) <= 0);
	$template->if_enable("UPD_EDIT"     , mb_strlen($_GET["NO"]) > 0);
	
	$template->assign("NO_PAD"              , $template->formatNoBasic($_GET["NO"]), true);
	$template->assign("NO"                  , $_GET["NO"], true);
	$template->assign("TOTAL_GIFT_POINT"    , $_POST["TOTAL_GIFT_POINT"], true);
	$template->assign("GIFT_LIMIT"          , $_POST["GIFT_LIMIT"], true);
	if (mb_strlen($_GET["NO"]) > 0) $template->assign("DSP_TOTAL_GIFT_POINT", number_formatEx($_POST["TOTAL_GIFT_POINT"]), true);
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
	getData($_GET , array("NO", "ACT"));
	getData($_POST , array("TOTAL_GIFT_POINT", "GIFT_LIMIT"));
	
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
			->update("mst_gift_limit" )
				->set()
					->value("del_flg", 1, FD_NUM)
					->value("del_no" , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value("del_dt" , "current_timestamp", FD_FUNCTION)
				->where()
					->and("no = "     , $_GET["NO"], FD_NUM )
					->and("del_flg = ", 0, FD_NUM )
			->createSQL("\n");
		$template->DB->exec($sql);
	}else{
		
		if (mb_strlen($_GET["NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_gift_limit" )
					->set()
						->value("gift_limit"      , $_POST["GIFT_LIMIT"], FD_NUM)
						->value("upd_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt", "current_timestamp", FD_FUNCTION)
					->where()
						->and("no = "     , $_GET["NO"], FD_NUM )
						->and("del_flg = ", 0, FD_NUM )
				->createSQL("\n");
			$template->DB->exec($sql);
		}else{
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into("mst_gift_limit" )
						->value("total_gift_point", $_POST["TOTAL_GIFT_POINT"], FD_NUM)
						->value("gift_limit"      , $_POST["GIFT_LIMIT"], FD_NUM)
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
			$title = $template->message("A2728");
			$msg = $template->message("A2729");
			break;
		case "del":
			// 削除
			$title = $template->message("A2730");
			$msg = $template->message("A2731");
			break;
		default:
			// 新規登録
			$title = $template->message("A2726");
			$msg = $template->message("A2727");
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

	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			//Update判定
			->setUpdateMode(mb_strlen($_GET["NO"]) > 0 )	//更新モード設定
			// 累計ポイント
			->item($_POST["TOTAL_GIFT_POINT"])
				->required("A2720")
				->number('A2721')
				->if("A2722", (int)$_POST["TOTAL_GIFT_POINT"] > 0)	// 1以上
			// 上限ポイント
			->item($_POST["GIFT_LIMIT"])
				->required("A2723")
				->number('A2724')
		->report();

		if (empty($errMessage)) {
			// 累計ポイント重複チェック
			$sqlDupli = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field("count(*)")
				->from("mst_gift_limit")
				->where()
					->and("total_gift_point = ", $_POST["TOTAL_GIFT_POINT"], FD_NUM)
					->and("del_flg != "        , "1", FD_NUM)
					->and(SQL_CUT, "no != "    , $_GET["NO"], FD_NUM)
			->createSql("\n");
			$errMessage = (new SmartAutoCheck($template))
				// 累計ポイント
				->item($_POST["TOTAL_GIFT_POINT"])
					->countSQL("A2725" , $sqlDupli)	// 重複
			->report();
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
