<?php
/*
 * purchase.php
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
 * ポイント購入管理画面表示
 * 
 * ポイント購入管理画面の表示を行う
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "purchase_type asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->where()
				->and( "mpp.del_flg = ", 0, FD_NUM )
			->from("mst_purchasePoint mpp")
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mpp.purchase_type, mpp.amount, mpp.point")
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
		$template->assign("TYPE_LABEL"         , $template->getArrayValue($GLOBALS["viewPurchaseType"], $row["purchase_type"]), true);
		$template->assign("PURCHASE_TYPE"      , $row["purchase_type"], true);
		$template->assign("AMOUNT"             , $row["amount"], true);
		$template->assign("DISP_AMOUNT"        , number_formatEx($row["amount"]), true);
		$template->assign("DISP_POINT"         , number_formatEx($row["point"]), true);
		
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
	getData($_GET , array("AM", "PT"));
	getData($_POST , array("PURCHASE_TYPE", "AMOUNT", "POINT", "DEL_FLG", "UPD_DT"));
	
	if( mb_strlen($_GET["PT"]) > 0){
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
				->field("mpp.purchase_type, mpp.amount, mpp.point, mpp.del_flg, mpp.upd_dt")
				->from("mst_purchasePoint mpp")
				->where()
					->and( "mpp.purchase_type = ",  $_GET["PT"], FD_NUM )
					->and( "mpp.amount = "       ,  $_GET["AM"], FD_NUM )
					->and( "mpp.del_flg = "      ,  0, FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$row["upd_dt"]        = format_datetime($row["upd_dt"]);
	}else{
		//POST or 新規
		$row["purchase_type"] = $_POST["PURCHASE_TYPE"];
		$row["amount"]        = $_POST["AMOUNT"];
		$row["point"]         = $_POST["POINT"];
		$row["del_flg"]       = $_POST["DEL_FLG"];
		$row["upd_dt"]        = $_POST["UPD_DT"];
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("NEW_EDIT"     , mb_strlen($row["del_flg"]) == 0);
	$template->if_enable("UPD_EDIT"     , mb_strlen($row["del_flg"]) > 0);
	
	$template->assign("PURCHASE_TYPE"      , $row["purchase_type"], true);
	$template->assign("RD_PURCHASE_TYPE"   , makeRadioArray( $GLOBALS["viewPurchaseType"], "PURCHASE_TYPE", $row["purchase_type"]));
	$template->assign("TYPE_LABEL"         , $template->getArrayValue($GLOBALS["viewPurchaseType"], $row["purchase_type"]), true);
	$template->assign("AMOUNT"             , $row["amount"], true);
	$template->assign("DISP_AMOUNT"        , number_formatEx($row["amount"]), true);
	$template->assign("POINT"              , $row["point"], true);
	$template->assign("UPD_DT"             , $row["upd_dt"], true);
	$template->assign("DEL_FLG"            , $row["del_flg"], true);
	$template->assign("VIEWAMOUNT"         , $template->getArrayValue($GLOBALS["viewAmountType"], $row["purchase_type"]), true);
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
	getData($_GET , array("ACT", "AM", "PT"));
	getData($_POST , array("PURCHASE_TYPE", "AMOUNT", "POINT", "DEL_FLG", "UPD_DT"));
	
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
			->update( "mst_purchasePoint" )
				->set()
					->value( "del_flg" , 1, FD_NUM)
					->value( "del_no"  , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"  , "current_timestamp", FD_FUNCTION)
				->where()
					->and( false, "purchase_type = " , $_GET["PT"], FD_NUM )
					->and( false, "amount = "        , $_GET["AM"], FD_NUM )
			->createSQL();
		$template->DB->query($sql);
	}else{
		
		if (mb_strlen($_POST["DEL_FLG"]) > 0) {
			// 更新
			$mode = "update";

			// 購入方法・ポイントの組み合わせ重複チェック
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "count(*)" )
					->from("mst_purchasePoint")
					->where()
						->and( false, "del_flg = " , 0, FD_NUM )
						->and( false, "purchase_type = " , $_POST["PURCHASE_TYPE"], FD_NUM )
						->and( false, "amount <> "       , $_POST["AMOUNT"], FD_NUM )
						->and( false, "point = "         , $_POST["POINT"], FD_NUM )
				->createSql();
			$cnt = $template->DB->getOne( $sql);
			if( $cnt > 0){
				DispDetail($template, $template->message("A1781"));
				return;
			}

			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_purchasePoint" )
					->set()
						->value( "point"  , $_POST["POINT"], FD_NUM)
						->value( "upd_no" , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt" , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "purchase_type = " , $_POST["PURCHASE_TYPE"], FD_NUM)
						->and( false, "amount = "        , $_POST["AMOUNT"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}else{
			// 新規
			//ブッキングチェックを入れる
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "count(*)" )
					->from("mst_purchasePoint")
					->where()
						->and( false, "del_flg = " , 0, FD_NUM )
						->and( false, "purchase_type = " , $_POST["PURCHASE_TYPE"], FD_NUM)
						->and( false, "amount = "        , $_POST["AMOUNT"], FD_NUM)
				->createSql();
			$cnt = $template->DB->getOne( $sql);
			if( $cnt > 0){
				DispDetail($template, $template->message("A1780"));
				return;
			}
			
			// 購入方法・ポイントの組み合わせ重複チェック
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "count(*)" )
					->from("mst_purchasePoint")
					->where()
						->and( false, "del_flg = " , 0, FD_NUM )
						->and( false, "purchase_type = " , $_POST["PURCHASE_TYPE"], FD_NUM )
						->and( false, "point = "         , $_POST["POINT"], FD_NUM )
				->createSql();
			$cnt = $template->DB->getOne( $sql);
			if( $cnt > 0){
				DispDetail($template, $template->message("A1781"));
				return;
			}
			
			// 削除状態のデータがある場合は削除を解除して再利用する
			$sql = "insert into mst_purchasePoint ("
				 . "purchase_type, amount, point, del_flg, upd_no, upd_dt, add_no, add_dt"
				 . ") values ("
				 . $template->DB->conv_sql($_POST["PURCHASE_TYPE"], FD_STR)
				 . "," . $template->DB->conv_sql($_POST["AMOUNT"], FD_NUM)
				 . "," . $template->DB->conv_sql($_POST["POINT"], FD_NUM)
				 . "," . $template->DB->conv_sql(0, FD_NUM)
				 . "," . $template->DB->conv_sql($template->Session->AdminInfo["admin_no"], FD_NUM)
				 . ",current_timestamp"
				 . "," . $template->DB->conv_sql($template->Session->AdminInfo["admin_no"], FD_NUM)
				 . ",current_timestamp"
				 . ") on duplicate key update"
				 . " point = " . $template->DB->conv_sql($_POST["POINT"], FD_NUM)
				 . ",del_flg = " . $template->DB->conv_sql(0, FD_NUM)
				 . ",upd_no = " . $template->DB->conv_sql($template->Session->AdminInfo["admin_no"], FD_NUM)
				 . ",upd_dt = current_timestamp";
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
			$title = $template->message("A1762");
			$msg = $template->message("A1763");
			break;
		case "del":
			// 削除
			$title = $template->message("A1764");
			$msg = $template->message("A1765");
			break;
		default:
			// 新規登録
			$title = $template->message("A1760");
			$msg = $template->message("A1761");
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
			//タイプ
			->item($_POST["PURCHASE_TYPE"])
				->required("A1701")
				->maxLength("A1702", 2)
				->number('A1702')
			//金額
			->item($_POST["AMOUNT"])
				->required("A1703")
				->number('A1704')
			//ポイント
			->item($_POST["POINT"])
				->required("A1705")
				->number('A1706')
			
		->report();
		
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}



?>
