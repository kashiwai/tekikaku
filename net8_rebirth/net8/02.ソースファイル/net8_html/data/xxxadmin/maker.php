<?php
/*
 * maker.php
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
 * メーカー管理画面表示
 * 
 * メーカー管理画面の表示を行う
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "maker_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_maker mm")
			->where()
				->and( "mm.del_flg != ", "1", FD_NUM)		// 2020/04/23 [ADD]
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mm.maker_no, mm.maker_name, mm.maker_roman, mm.pachi_flg, mm.slot_flg, mm.disp_flg,  mm.upd_dt, count( dm.machine_no) as mcnt")	// 2020/04/23 [UPD]
			->from("left join mst_model mo on mo.maker_no = mm.maker_no and mo.del_flg <> 1" )
			->from("left join dat_machine dm on dm.model_no = mo.model_no and dm.del_flg <> 1" )
			->groupby( "mm.maker_no" )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( "mm.".$_GET["ODR"] )
		->createSql("\n");

	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?".$_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		
		$template->assign("MAKER_NO_PAD"    , $template->formatNoBasic($row["maker_no"]), true);
		$template->assign("MAKER_NO"        , $row["maker_no"], true);
		$template->assign("MAKER_NAME"      , $row["maker_name"], true);
		$template->assign("MAKER_ROMAN"     , $row["maker_roman"], true);
		$template->assign("PACH_FLG_LABEL"  , $template->getArrayValue($GLOBALS["thereIsStatus"],   $row["pachi_flg"]), true);
		$template->assign("SLOT_FLG_LABEL"  , $template->getArrayValue($GLOBALS["thereIsStatus"],   $row["slot_flg"]), true);
		$template->assign("DISP_FLG_LABEL"  , $template->getArrayValue($GLOBALS["makerDispStatus"], $row["disp_flg"]), true);
		$template->assign("UPD_DT"          , $row["upd_dt"], true);
		
		$template->if_enable("EXISTS_LIST"   , $row["mcnt"] > 0);
		$template->if_enable("NO_EXISTS_LIST", $row["mcnt"] == 0);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);
	
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
	getData($_GET , array("NO", "ACT"));	// 2020/04/23 [UPD]
	getData($_POST , array("MAKER_NO", "MAKER_NAME", "MAKER_ROMAN", "PACH_FLG", "SLOT_FLG", "DISP_FLG"));
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 || $_GET["ACT"] == "del"){		// 2020/04/22 [UPD] 初回表示若しくは削除チェックエラー時に修正
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
				->field("maker_no, maker_name, maker_roman, pachi_flg, slot_flg, upd_dt, disp_flg")
				->from( "mst_maker" )
				->where()
					->and( "maker_no = ",   $_GET["NO"], FD_NUM )
					->and( "del_flg != ", "1", FD_NUM)		// 2020/04/23 [ADD]
			->createSql();
		
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// 2020/04/24 [ADD Start]データ不存在は通常あり得ないのでシステムエラー
		if (empty($row["maker_no"])) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		// 2020/04/24 [ADD End]データ不存在は通常あり得ないのでシステムエラー
	}else{
		$row["maker_no"]    = $_POST["MAKER_NO"];
		$row["maker_name"]  = $_POST["MAKER_NAME"];
		$row["maker_roman"] = $_POST["MAKER_ROMAN"];
		$row["pachi_flg"]   = $_POST["PACH_FLG"];
		$row["slot_flg"]    = $_POST["SLOT_FLG"];
		$row["disp_flg"]    = $_POST["DISP_FLG"];
	}
	

	// 2020/05/15 [ADD Start]
	// パチンコ機種存在
	$sqlExtPach = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_model mm" )
		->where()
			->and( "mm.maker_no = ", $row["maker_no"], FD_NUM)
			->and( "mm.category = ", "1", FD_NUM)
			->and( "mm.del_flg != ", "1", FD_NUM)
	->createSql();
	$cntP = (mb_strlen($row["maker_no"]) > 0) ? $template->DB->getOne($sqlExtPach) : 0;
	// 機種存在 且 ありの場合変更不可
	$addOptP = ($cntP > 0 && $row["pachi_flg"] == "1") ? " disabled" : "";

	// スロット機種存在
	$sqlExtSlot = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_model mm" )
		->where()
			->and( "mm.maker_no = ", $row["maker_no"], FD_NUM)
			->and( "mm.category = ", "2", FD_NUM)
			->and( "mm.del_flg != ", "1", FD_NUM)
	->createSql();
	$cntS = (mb_strlen($row["maker_no"]) > 0) ? $template->DB->getOne($sqlExtSlot) : 0;
	// 機種存在 且 ありの場合変更不可
	$addOptS = ($cntS > 0 && $row["slot_flg"] == "1") ? " disabled" : "";

	// 機種存在 且 表示するの場合変更不可
	$addOptD = ( ($cntP + $cntS) > 0 && $row["disp_flg"] == "1") ? " disabled" : "";
	// 2020/05/15 [ADD End]


	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);
	$template->if_enable("CAN_DEL",  mb_strlen($row["maker_no"]) > 0 && $cntP <= 0 && $cntS <= 0);	// 2020/05/15 [UPD]
	
	$template->assign("NO"              , $row["maker_no"], true);
	$template->assign("MAKER_NO"        , $row["maker_no"], true);
	$template->assign("MAKER_NAME"      , $row["maker_name"], true);
	$template->assign("MAKER_ROMAN"     , $row["maker_roman"], true);
	$template->assign("PACH_FLG_RADIO"  , makeRadioArray($GLOBALS["thereIsStatus"],   "PACH_FLG", $row["pachi_flg"], $addOptP));	// 2020/05/15 [UPD]
	$template->assign("SLOT_FLG_RADIO"  , makeRadioArray($GLOBALS["thereIsStatus"],   "SLOT_FLG", $row["slot_flg"], $addOptS));	// 2020/05/15 [UPD]
	$template->assign("DISP_FLG_RADIO"  , makeRadioArray($GLOBALS["makerDispStatus"], "DISP_FLG", $row["disp_flg"], $addOptD));	// 2020/05/15 [UPD]
	
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
	getData($_POST , array("MAKER_NO", "MAKER_NAME", "MAKER_ROMAN", "PACH_FLG", "SLOT_FLG", "DISP_FLG"));
	
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
			->update( "mst_maker" )
				->set()
					->value( "del_flg"          , 1, FD_NUM)
					->value( "del_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"           , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "maker_no ="        , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}else{
		if (mb_strlen($_POST["MAKER_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_maker" )
					->set()
						->value( "maker_no"         , $_POST["MAKER_NO"], FD_NUM)
						->value( "maker_name"       , $_POST["MAKER_NAME"], FD_STR)
						->value( "maker_roman"      , $_POST["MAKER_ROMAN"], FD_STR)
						->value(SQL_CUT, "pachi_flg", $_POST["PACH_FLG"], FD_NUM)		// 2020/04/23 [UPD]
						->value(SQL_CUT, "slot_flg" , $_POST["SLOT_FLG"],  FD_NUM)		// 2020/04/23 [UPD]
						->value( "disp_flg"         , $_POST["DISP_FLG"],  FD_NUM)
						->value( "upd_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"           , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "maker_no =" , $_POST["MAKER_NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}else{
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_maker" )
						->value( "maker_name"       , $_POST["MAKER_NAME"], FD_STR)
						->value( "maker_roman"      , $_POST["MAKER_ROMAN"], FD_STR)
						->value(SQL_CUT, "pachi_flg", $_POST["PACH_FLG"],  FD_NUM)		// 2020/04/23 [ADD]
						->value(SQL_CUT, "slot_flg" , $_POST["SLOT_FLG"],  FD_NUM)		// 2020/04/23 [ADD]
						->value( "disp_flg"         , $_POST["DISP_FLG"],  FD_NUM)		// 2020/04/23 [ADD]
						->value( "upd_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"           , "current_timestamp", FD_FUNCTION)
						->value( "add_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"           , "current_timestamp", FD_FUNCTION)
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
			$title = $template->message("A1362");
			$msg = $template->message("A1363");
			break;
		case "del":
			// 削除
			$title = $template->message("A1364");
			$msg = $template->message("A1365");
			break;
		default:
			// 新規登録
			$title = $template->message("A1360");
			$msg = $template->message("A1361");
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

	// 2020/04/23 [ADD Start]
	// メーカ名重複チェックSQL（メーカー名が入力されている場合のみ）
	$sqlNameDupli = null;
	if (mb_strlen($_POST["MAKER_NAME"]) > 0) {
		$sqlNameDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_maker" )
			->where()
				->and( "maker_name = ", $_POST["MAKER_NAME"], FD_STR)
				->and( "del_flg != ", "1", FD_NUM)
				->and( true, "maker_no <> ", $_POST["MAKER_NO"], FD_NUM)
		->createSql();
	}

	// メーカ名(ローマ字)重複チェックSQL（ローマ字名が入力されている場合のみ）
	$sqlRomanDupli = null;
	if (mb_strlen($_POST["MAKER_ROMAN"]) > 0) {
		$sqlRomanDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_maker" )
			->where()
				->and( "maker_roman = ", $_POST["MAKER_ROMAN"], FD_STR)
				->and( "del_flg != ", "1", FD_NUM)
				->and( true, "maker_no <> ", $_POST["MAKER_NO"], FD_NUM)
		->createSql();
	}

	// 2020/05/15 [UPD Start] 実機存在では無く機種存在に変更
	// 機種存在
	$sqlExtModel = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_model mm" )
		->where()
			->and( "mm.maker_no = ", $_GET["NO"], FD_NUM)
			->and( "mm.del_flg != ", "1", FD_NUM)
	->createSql();
	// 2020/05/15 [UPD End]
	// 2020/04/23 [ADD End]

	// 2020/05/15 [ADD Start]
	$isUpdate = (mb_strlen($_POST["MAKER_NO"]) > 0);
	// パチンコ機種存在
	$sqlExtPach = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_model mm" )
		->where()
			->and( "mm.maker_no = ", $_GET["NO"], FD_NUM)
			->and( "mm.category = ", "1", FD_NUM)
			->and( "mm.del_flg != ", "1", FD_NUM)
	->createSql();
	// スロット機種存在
	$sqlExtSlot = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "mst_model mm" )
		->where()
			->and( "mm.maker_no = ", $_GET["NO"], FD_NUM)
			->and( "mm.category = ", "2", FD_NUM)
			->and( "mm.del_flg != ", "1", FD_NUM)
	->createSql();
	// 2020/05/15 [ADD End]


	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			->setUpdateMode($isUpdate)
			//メーカーNo
			->item($_POST["MAKER_NO"])
				->any()
				->number('A1310')
			//メーカー名
			->item($_POST["MAKER_NAME"])
				->required("A1301")
				->maxLength("A1302", 20)			// 文字長の最高値
				->case($sqlNameDupli !== null)
					->countSQL("A1308", $sqlNameDupli)	// メーカ名重複 2020/04/23 [ADD]
			//メーカー名（英語）
			->item($_POST["MAKER_ROMAN"])
				->required("A1303")
				->maxLength("A1304", 50)			// 文字長の最高値
				->case($sqlRomanDupli !== null)
					->countSQL("A1309", $sqlRomanDupli)	// メーカ名(ローマ字)重複 2020/04/23 [ADD]
			//フラグ
			->item($_POST["PACH_FLG"])
				->case($GLOBALS["CategoryUseList"]["PACH"])
					->required("A1305")
					->isUpdate()				// 更新のみ
						->case($_POST["PACH_FLG"] == "0")		// なし
						->countSQL("A1313", $sqlExtPach)		// パチンコ機種存在
			->item($_POST["SLOT_FLG"])
				->case($GLOBALS["CategoryUseList"]["SLOT"])
					->required("A1306")
					->isUpdate()				// 更新のみ
						->case($_POST["SLOT_FLG"] == "0")		// なし
						->countSQL("A1314", $sqlExtSlot)		// スロット機種存在
			->item($_POST["DISP_FLG"])	//-- 表示フラグ
				->isUpdate()				// 更新のみ
				->case($_POST["DISP_FLG"] == "0")		// 表示しない
					->countSQL("A1312", $sqlExtModel)		// 機種存在
		->report();
	// 2020/04/23 [ADD Start]
		// エラーが無い場合はカテゴリの「あり」が1つは存在する事を確認
		if (count($errMessage) <= 0) {
			$useCnt = 0;
			foreach($GLOBALS["CategoryUseList"] as $key => $val ){
				if ($val) $useCnt += (int)$_POST[$key . "_FLG"];
			}
			if ($useCnt <= 0) $errMessage[] = $template->message("A1307");
		}
	} else {
		$errMessage = (new SmartAutoCheck($template))
			//メーカーNo
			->item($_POST["MAKER_NO"])
			->countSQL("A1311", $sqlExtModel)		// 機種存在
		->report();
	}
	// 2020/04/23 [ADD End]
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


?>
