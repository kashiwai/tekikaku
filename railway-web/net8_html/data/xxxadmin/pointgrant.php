<?php
/*
 * pointgrant.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * ポイント付与設定管理画面表示
 * 
 * ポイント付与設定管理画面の表示を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2016/09/02 初版作成 岡本 静子
 * @since    2019/01/30 改修 片岡 充 PHP7対応ライブラリの書き方に準拠
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "machine_no asc";
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("*")
			->from("mst_grantPoint mgp")
		->createSql();
	$rs = $template->DB->query($sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->assign("PROC_LABEL"               , $template->getArrayValue( $GLOBALS["grantPointStatusList"], $row["proc_cd"]), true);
		$template->assign("PROC_CD"                  , $row["proc_cd"], true);
		$template->assign("POINT"                    , number_formatEx($row["point"]), true);
		$template->assign("LIMIT_DAYS"               , $row["limit_days"], true);
		$template->assign("SPECIAL_POINT"            , number_formatEx($row["special_point"]), true);
		$template->assign("SPECIAL_START_DT"         , format_date($row["special_start_dt"]), true);
		$template->assign("SPECIAL_END_DT"           , format_date($row["special_end_dt"]), true);
		$template->assign("SPECIAL_LIMIT_DAYS"       , $row["special_limit_days"], true);
		$template->if_enable("LIMIT_DAYS"            , (int)$row["limit_days"] > 0);
		$template->if_enable("NO_LIMIT_DAYS"         , (int)$row["limit_days"] == 0);
		$template->if_enable("SPECIAL_LIMIT_DAYS"    , (int)$row["special_limit_days"] > 0);
		$template->if_enable("NO_SPECIAL_LIMIT_DAYS" , (int)$row["special_limit_days"] == 0);
		$template->if_enable("EDIT_SPECIAL"          , mb_strlen($row["special_start_dt"]) > 0);
		$template->if_enable("EDIT_SPECIAL_NONE"     , empty($row["special_start_dt"]));
		
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
	getData($_POST , array("PROC_CD", "POINT", "LIMIT_DAYS", "SPECIAL_POINT"
							, "SPECIAL_START_DT", "SPECIAL_END_DT", "SPECIAL_LIMIT_DAYS", "USE_SPECIAL"));
	
	$useSpecial = 0;
	if( mb_strlen( $_POST["PROC_CD"])>0){
		$row = array();
		$row["proc_cd"]            = $_POST["PROC_CD"];
		$row["point"]              = $_POST["POINT"];
		$row["limit_days"]         = $_POST["LIMIT_DAYS"];
		$row["special_point"]      = $_POST["SPECIAL_POINT"];
		$row["special_limit_days"] = $_POST["SPECIAL_LIMIT_DAYS"];
		$row["special_start_dt"]   = $_POST["SPECIAL_START_DT"];
		$row["special_end_dt"]     = $_POST["SPECIAL_END_DT"];
		$useSpecial                = $_POST["USE_SPECIAL"];
	}else{
		// 個別データ取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field("*")
				->from("mst_grantPoint mgp")
				->where()
					->and(false, "proc_cd =", $_GET["NO"], FD_STR)
			->createSql();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		// 期間指定がある場合は、期間限定付与を「使用する」とみなす
		if (mb_strlen($row["special_start_dt"]) > 0) $useSpecial = "1";
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG"          , mb_strlen($message) > 0);
	
	$template->assign("PROC_LABEL"           , $template->getArrayValue( $GLOBALS["grantPointStatusList"], $row["proc_cd"]), true);
	$template->assign("PROC_CD"              , $row["proc_cd"], true);
	$template->assign("POINT"                , $row["point"], true);
	$template->assign("LIMIT_DAYS"           , $row["limit_days"], true);
	$template->assign("SPECIAL_POINT"        , $row["special_point"], true);
	$template->assign("SPECIAL_LIMIT_DAYS"   , $row["special_limit_days"], true);
	$template->assign("SPECIAL_START_DT"     , $row["special_start_dt"], true);
	$template->assign("SPECIAL_END_DT"       , $row["special_end_dt"], true);
	$template->assign("CHK_SPECIAL_0"        , ($useSpecial == "0") ? " checked" : "", true);
	$template->assign("CHK_SPECIAL_1"        , ($useSpecial == "1") ? " checked" : "", true);
	
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
	getData($_POST , array("PROC_CD", "POINT", "LIMIT_DAYS", "SPECIAL_POINT"
							, "SPECIAL_START_DT", "SPECIAL_END_DT", "SPECIAL_LIMIT_DAYS", "USE_SPECIAL"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// 期間限定を使用しない場合は値をクリア
	if ($_POST["USE_SPECIAL"] == "0") {
		$_POST["SPECIAL_POINT"] = 0;
		$_POST["SPECIAL_LIMIT_DAYS"] = 0;
		$_POST["SPECIAL_START_DT"] = "";
		$_POST["SPECIAL_END_DT"] = "";
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_grantPoint" )
			->set()
				->value( "point"              , (mb_strlen($_POST["POINT"]) > 0) ? $_POST["POINT"] : 0, FD_NUM)
				->value( "limit_days"         , (mb_strlen($_POST["LIMIT_DAYS"]) > 0) ? $_POST["LIMIT_DAYS"] : 0, FD_NUM)
				->value( "special_point"      , (mb_strlen($_POST["SPECIAL_POINT"])) ? $_POST["SPECIAL_POINT"] : 0, FD_NUM)
				->value( "special_limit_days" , (mb_strlen($_POST["SPECIAL_LIMIT_DAYS"]) > 0) ? $_POST["SPECIAL_LIMIT_DAYS"] : 0, FD_NUM)
				->value( "special_start_dt"   , $_POST["SPECIAL_START_DT"], FD_DATEEX)
				->value( "special_end_dt"     , $_POST["SPECIAL_END_DT"], FD_DATEEX)
				->value( "upd_no"             , $template->Session->AdminInfo["admin_no"], FD_NUM)
				->value( "upd_dt"             , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "proc_cd =" , $_POST["PROC_CD"], FD_STR)
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
	
	$title = $template->message("A2190");
	$msg = $template->message("A2191");
	
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
			->required("A2101")
			->number('A2102')
		->item($_POST["LIMIT_DAYS"])
			->any()
			->number("A2110")
		->item($_POST["SPECIAL_POINT"])
			->case( $_POST["USE_SPECIAL"] == "1")
				->required("A2103")
				->number('A2104')
		->item($_POST["SPECIAL_LIMIT_DAYS"])
			->any()
			->number("A2112")
		->item($_POST["SPECIAL_START_DT"])
			->case( $_POST["USE_SPECIAL"] == "1")
				->required("A2105")
				->date('A2106')
		->item($_POST["SPECIAL_END_DT"])
			->case( $_POST["USE_SPECIAL"] == "1")
				->required("A2107")
				->date('A2108')
	->report();
	
	// 期間のFROM-TOチェック
	if (empty($errMessage) && $_POST["USE_SPECIAL"] == "1") {
		$from = new DateTime($_POST["SPECIAL_START_DT"]);
		$to = new DateTime($_POST["SPECIAL_END_DT"]);
		if ($from > $to) {
			array_push($errMessage, $template->message("A2113"));
		}
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
