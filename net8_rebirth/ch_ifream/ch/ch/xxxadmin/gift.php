<?php
/*
 * gift.php
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
 * ポイントギフト設定管理画面表示
 * 
 * ポイントギフト設定管理画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2020/04/09 初版作成 片岡 充
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
			
			case "detail":			// 変更画面
				$mainWin = false;
				DispDetail($template);
				break;
				
			case "regist":			// 変更処理
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
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("*")
		->from("mst_gift")
		->limit(1)
		->createSql();
	$row = $template->DB->getRow($sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	$template->assign("RATE"          , $row["commission_rate"], true);
	$template->assign("DISP_POINT"    , number_formatEx( $row["min_point"]), true);
	$template->assign("DISP_LOT"      , number_formatEx( $row["lot"]), true);
	$template->assign("DISP_ROUNDING" , $GLOBALS["pointGiftRoundingList"][$row["commission_rounding"]], true);
	$template->assign("DISP_BEARER"   , $GLOBALS["pointGiftBearerList"][$row["bearer"]], true);
	
	$template->if_enable("LOT"    , !empty($row["lot"]));
	$template->if_enable("NO_LOT" , empty($row["lot"]));
	
	// 表示
	$template->flush();
}

/**
 * 変更画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispDetail($template, $message = "") {
	getData($_POST , array("POINT", "LOT", "RATE", "ROUNDING", "BEARER"));
	
	if (mb_strlen($message) > 0) {
		$row = array();
		$row["min_point"]           = $_POST["POINT"];
		$row["lot"]                 = $_POST["LOT"];
		$row["commission_rate"]     = $_POST["RATE"];
		$row["commission_rounding"] = $_POST["ROUNDING"];
		$row["bearer"]              = $_POST["BEARER"];
	}else{
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("*")
			->from("mst_gift")
			->limit(1)
			->createSql();
		$row = $template->DB->getRow($sql);	
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG"     , mb_strlen($message) > 0);
	
	$template->assign("POINT"         , $row["min_point"], true);
	$template->assign("LOT"           , $row["lot"], true);
	$template->assign("RATE"          , $row["commission_rate"], true);
	$template->assign("SEL_ROUNDING"  , makeOptionArray( $GLOBALS["pointGiftRoundingList"], $row["commission_rounding"], false));
	$template->assign("SEL_BEARER"    , makeOptionArray( $GLOBALS["pointGiftBearerList"], $row["bearer"], false));
	
	// 表示
	$template->flush();
}

/**
 * 変更処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData($template) {
	// データ取得
	getData($_POST , array("POINT", "LOT", "RATE", "ROUNDING", "BEARER"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_gift" )
			->set()
				->value( "min_point"          , $_POST["POINT"], FD_NUM)
				->value( "lot"                , $_POST["LOT"], FD_NUM)
				->value( "commission_rate"    , $_POST["RATE"], FD_NUM)
				->value( "commission_rounding", $_POST["ROUNDING"], FD_NUM)
				->value( "bearer"             , $_POST["BEARER"], FD_NUM)
				->value( "upd_no"             , $template->Session->AdminInfo["admin_no"], FD_NUM)
				->value( "upd_dt"             , "current_timestamp", FD_FUNCTION)
			->where()
				->and( "no =" , 1, FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end");
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
	
	$title = $template->message("A2990");
	$msg = $template->message("A2991");
	
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
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["POINT"])
			->required("A2901")
			->number('A2902')
		->item($_POST["LOT"])
			->required("A2903")
			->number('A2904')
		->item($_POST["RATE"])
			->required("A2905")
			->number('A2906')
		->item($_POST["ROUNDING"])
			->required("A2907")
			->number('A2907')
		->item($_POST["BEARER"])
			->required("A2908")
	->report();
	
	if (empty($errMessage)) {
		if ((int)$_POST["POINT"] < 1) {
			array_push($errMessage, $template->message("A2909"));
		}
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
