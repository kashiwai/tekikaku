<?php
/*
 * shipping.php
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
 * 発送管理画面表示
 * 
 * 発送管理画面の表示を行う
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
	getData($_GET , array("P", "ODR", "NO"
						, "S_GOODS_NO", "S_MEMBER_NO", "SHIPPING_DT_FROM", "SHIPPING_DT_TO", "S_SHIPPING_NO"
						));
	
	//
	$get_state = array();
	if (empty($_GET["CHK_STATUS"])) $_GET["CHK_STATUS"] = array();
	if( isset($_GET["CHK_STATUS"]) && count($_GET["CHK_STATUS"])>0){
		// 値のある配列のみ抽出(keyのみ存在して値が空の配列に対応する為)
		$get_state = array_filter($_GET["CHK_STATUS"], "strlen");
		$out = array();
		foreach( $get_state as $v){
			$out[] = "CHK_STATUS%5B%5D=".$v;
		}
		$_GET["CHK_STATUS"] = implode('&', $out);
	}
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "add_dt desc";
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P", "CHK_STATUS"), $_GET["CHK_STATUS"]);
	
	if( mb_strlen( $_GET["NO"]) > 0){
		$_GET["S_GOODS_NO"] = $_GET["NO"];
	}
	
	//検索判定
	if( ($_GET["S_GOODS_NO"]!="") || ($_GET["S_MEMBER_NO"]!="") || ($_GET["S_SHIPPING_NO"]!="") || ($_GET["SHIPPING_DT_FROM"]!="") || ($_GET["SHIPPING_DT_TO"]!="")){
		$_search = "show";
	}else{
		$_search = "";
	}
	if( count($get_state)>0) $_search = "show";
	
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("dat_win dw")
			->from("inner join mst_member mm on mm.member_no = dw.member_no")
			->from("inner join mst_goods mg on mg.goods_no = dw.goods_no")
			->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->where()
				->and( true, "dw.goods_no = ",     $_GET["S_GOODS_NO"], FD_NUM )
				->and( true, "dw.member_no = ",    $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "dw.shipping_no = ",  $_GET["S_SHIPPING_NO"], FD_NUM )
				->and( true, "dw.shipping_dt >= ", $_GET["SHIPPING_DT_FROM"], FD_DATE )
				->and( true, "dw.shipping_dt <= ", $_GET["SHIPPING_DT_TO"], FD_DATE )
		->createSql();
	
	if( count($get_state)>0){
		$csql = $sqls
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->and( true, "dw.state in ", $get_state, FD_NUM )
		->createSql();
	}
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("dw.seq, dw.shipping_no, dw.member_no, dw.goods_no, dw.shipping_dt, dw.state, dw.add_dt")
			->field("mm.nickname, mm.black_flg, mm.state as member_state, mm.tester_flg")
			->field("lng.goods_name")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	// 検索
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_GOODS_NO"          , $_GET["S_GOODS_NO"], true);
	$template->assign("S_MEMBER_NO"         , $_GET["S_MEMBER_NO"], true);
	$template->assign("CHK_STATUS"          , makeCheckBoxArray( $GLOBALS["shippingStatusList"], "CHK_STATUS[]", $get_state, 0, "", " ", "", true));
	$template->assign("SHIPPING_DT_FROM"    , $_GET["SHIPPING_DT_FROM"], true);
	$template->assign("SHIPPING_DT_TO"      , $_GET["SHIPPING_DT_TO"], true);
	$template->assign("S_SHIPPING_NO"       , $_GET["S_SHIPPING_NO"], true);
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));			// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));			// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));			// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["member_state"] == 9);

		$template->assign("SEQ"            , $row["seq"], true);
		$template->assign("GOODS_NO"       , $row["goods_no"], true);
		$template->assign("GOODS_NO_PAD"   , $template->formatNoBasic($row["goods_no"]), true);
		$template->assign("GOODS_NAME"     , $row["goods_name"], true);
		$template->assign("MEMBER_NO"      , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD"  , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"       , $row["nickname"], true);
		$template->assign("SHIPPING_DT"    , format_date($row["shipping_dt"]), true);
		$template->assign("SHIPPING_NO"    , $row["shipping_no"], true);
		$template->assign("STATUS"         , $template->getArrayValue( $GLOBALS["shippingStatusList"], $row["state"]), true);
		$template->assign("ADD_DT"         , format_date($row["add_dt"]), true);
		//
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
	getData($_GET , array("SEQ", "NO"));
	getData($_POST, array("SEQ", "NO", "OLD_STATE"
					, "STATE", "SHIPPING_DT", "SHIPPING_NO", "IN_REMARKS"
					, "SYLL", "NAME", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL", "REMARKS"
	));
	
	$_load = false;
	if( mb_strlen($message) > 0 ){
		$_GET["SEQ"] = $_POST["SEQ"];
		$_GET["NO"]  = $_POST["NO"];
	}else{
		if( mb_strlen($_GET["NO"]) > 0){
			$_load = true;
		}
	}
	
	if( $_load ){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dw.seq, dw.goods_no, dw.member_no, dw.add_dt, dw.state, dw.shipping_dt, dw.shipping_no, dw.in_remarks")
				->field("dw.syll, dw.name, dw.postal, dw.address1, dw.address2, dw.address3, dw.address4, dw.tel, dw.remarks")
				->field("mm.nickname, mm.state as member_state, mm.black_flg, mm.tester_flg")
				->field("mg.goods_no, mg.goods_cd, lng.goods_name")
				->from("dat_win dw")
				->from("inner join mst_member mm on mm.member_no = dw.member_no")
				->from("inner join mst_goods mg on mg.goods_no = dw.goods_no")
				->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
				->where()
					->and( "dw.seq = ",      $_GET["SEQ"], FD_NUM )
					->and( "dw.goods_no = ", $_GET["NO"], FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$row["old_state"] = $row["state"];
	}else{
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dw.seq, dw.goods_no, dw.member_no, dw.add_dt")
				->field("mm.nickname, mm.state as member_state, mm.black_flg, mm.tester_flg")
				->field("mg.goods_no, mg.goods_cd, lng.goods_name")
				->from("dat_win dw")
				->from("inner join mst_member mm on mm.member_no = dw.member_no")
				->from("inner join mst_goods mg on mg.goods_no = dw.goods_no")
				->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
				->where()
					->and( "dw.seq = ",      $_GET["SEQ"], FD_NUM )
					->and( "dw.goods_no = ", $_GET["NO"], FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		//POST
		$row["old_state"]   = $_POST["OLD_STATE"];
		$row["state"]       = $_POST["STATE"];
		$row["shipping_dt"] = $_POST["SHIPPING_DT"];
		$row["shipping_no"] = $_POST["SHIPPING_NO"];
		$row["in_remarks"]  = $_POST["IN_REMARKS"];
		$row["syll"]        = $_POST["SYLL"];
		$row["name"]        = $_POST["NAME"];
		$row["postal"]      = $_POST["POSTAL"];
		$row["address1"]    = $_POST["ADDRESS1"];
		$row["address2"]    = $_POST["ADDRESS2"];
		$row["address3"]    = $_POST["ADDRESS3"];
		$row["address4"]    = $_POST["ADDRESS4"];
		$row["tel"]         = $_POST["TEL"];
		$row["remarks"]     = $_POST["REMARKS"];
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("EDIT"         , (int)$row["old_state"] < 3);
	$template->if_enable("IS_BLACK"     , $row["black_flg"] == 1);
	$template->if_enable("IS_TESTER"    , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
	$template->if_enable("IS_RETIRED"   , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["member_state"] == 9);
	$template->if_enable("IS_WARNING"   , $row["black_flg"] == 1 || $row["tester_flg"] == 1 || $row["member_state"] == 9);

	$template->assign("OLD_STATE"       , $row["old_state"], true);
	$template->assign("SEQ"             , $row["seq"], true);
	$template->assign("NO"              , $row["goods_no"], true);
	$template->assign("GOODS_NO_PAD"    , $template->formatNoBasic($row["goods_no"]), true);
	$template->assign("GOODS_CD"        , $row["goods_cd"], true);
	$template->assign("GOODS_NAME"      , $row["goods_name"], true);
	$template->assign("MEMBER_NO_PAD"   , $template->formatMemberNo($row["member_no"]), true);
	$template->assign("NICKNAME"        , $row["nickname"], true);
	$template->assign("ADD_DT"          , format_date($row["add_dt"]), true);
	$template->assign("SEL_STATE"       , makeOptionArray($GLOBALS["shippingStatusList"], $row["state"], false));

	$template->assign("SHIPPING_DT"     , format_date($row["shipping_dt"]), true);
	$template->assign("SHIPPING_NO"     , $row["shipping_no"], true);
	$template->assign("IN_REMARKS"      , $row["in_remarks"], true);
	$template->assign("SYLL"            , $row["syll"], true);
	$template->assign("NAME"            , $row["name"], true);
	$template->assign("POSTAL"          , $row["postal"], true);
	$template->assign("ADDRESS1"        , $row["address1"], true);
	$template->assign("ADDRESS2"        , $row["address2"], true);
	$template->assign("ADDRESS3"        , $row["address3"], true);
	$template->assign("ADDRESS4"        , $row["address4"], true);
	$template->assign("TEL"             , $row["tel"], true);
	$template->assign("REMARKS"         , $row["remarks"], true);
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
	getData($_POST, array("SEQ", "NO", "OLD_STATE"
					, "STATE", "SHIPPING_DT", "SHIPPING_NO", "IN_REMARKS"
					, "SYLL", "NAME", "POSTAL", "ADDRESS1", "ADDRESS2", "ADDRESS3", "ADDRESS4", "TEL", "REMARKS"
	));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// 発送日と発送Noの両方に入力が有り、状態が「キャンセル」以外の場合は「発送済」で更新する
	$state = $_POST["STATE"];
	if ($_POST["STATE"] != "9" && mb_strlen($_POST["SHIPPING_DT"]) > 0 && mb_strlen($_POST["SHIPPING_NO"]) > 0) {
		$state = "3";
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//更新
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_win" )
			->set()
				->value( "state"        , $state, FD_NUM)
				->value( "shipping_dt"  , $_POST["SHIPPING_DT"], FD_DATEEX)
				->value( "shipping_no"  , $_POST["SHIPPING_NO"], FD_STR)
				->value( "in_remarks"   , $_POST["IN_REMARKS"], FD_STR)
				//住所系
				->value( "syll"         , $_POST["SYLL"], FD_STR)
				->value( "name"         , $_POST["NAME"], FD_STR)
				->value( "postal"       , $_POST["POSTAL"], FD_STR)
				->value( "address1"     , $_POST["ADDRESS1"], FD_STR)
				->value( "address2"     , $_POST["ADDRESS2"], FD_STR)
				->value( "address3"     , $_POST["ADDRESS3"], FD_STR)
				->value( "address4"     , $_POST["ADDRESS4"], FD_STR)
				->value( "tel"          , $_POST["TEL"], FD_STR)
				->value( "remarks"      , $_POST["REMARKS"], FD_STR)
				//
				->value( "upd_no"       , $template->Session->AdminInfo["admin_no"], FD_NUM)
				->value( "upd_dt"       , "current_timestamp", FD_FUNCTION)
			->where()
					->and(false, "seq = "     , $_POST["SEQ"], FD_NUM )
					->and(false, "goods_no = ", $_POST["NO"], FD_NUM )
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
	// 更新
	$title = $template->message("A2390");
	$msg = $template->message("A2391");

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
	$tempMessage = (new SmartAutoCheck($template));

	if ($_POST["STATE"] == "3") {
		// 発送済
		$tempMessage = $tempMessage
			->item($_POST["SHIPPING_DT"])
				->required("A2302")
				->date("A2303")
			->item($_POST["SHIPPING_NO"])
				->required("A2304")
				->alnum("A2305", 3);
	} else {
		$tempMessage = $tempMessage
			->item($_POST["SHIPPING_DT"])
				->any()
				->date("A2303")
			->item($_POST["SHIPPING_NO"])
				->any()
				->alnum("A2305", 3);
	}
	
	if ((int)$_POST["STATE"] > 0 && (int)$_POST["STATE"] < 9) {
		$tempMessage = $tempMessage
			->item($_POST["SYLL"])
				->required("A2306")
			->item($_POST["NAME"])
				->required("A2308")
			->item($_POST["POSTAL"])
				->required("A2310")
				->number("A2311")
			->item($_POST["ADDRESS1"])
				->required("A2312")
			->item($_POST["ADDRESS2"])
				->required("A2313")
			->item($_POST["ADDRESS3"])
				->required("A2314")
			->item($_POST["TEL"])
				->required("A2315")
				->number("A2316");
	} else {
		$tempMessage = $tempMessage
			->item($_POST["POSTAL"])
				->any()
				->number("A2311")
			->item($_POST["TEL"])
				->any()
				->number("A2316");
	}
	$errMessage = $tempMessage->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
