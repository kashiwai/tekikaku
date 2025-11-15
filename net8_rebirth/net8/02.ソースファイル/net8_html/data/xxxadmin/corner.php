<?php
/*
 * corner.php
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
 * コーナー設定画面表示
 * 
 * コーナー設定画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.1
 * @since    2019/01/30 初版作成 片岡 充
 * @since    2023/10/23 v1.01    岡本 静子 お知らせ表示設定追加
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
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "corner_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_corner mc")
			->where()
				->and( "mc.del_flg = ",   0, FD_NUM )
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("mc.corner_no, mc.corner_name, mc.corner_roman, count( dmc.machine_no) as mcnt")
			->field("mc.notice_flg")		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
			->from("left join dat_machineCorner dmc on dmc.corner_no = mc.corner_no" )
			->groupby( "mc.corner_no" )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( "mc.".$_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));			// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));			// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));			// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);						// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		
		$template->assign("CORNER_NO_PAD"    , $template->formatNoBasic($row["corner_no"]), true);
		$template->assign("CORNER_NO"        , $row["corner_no"], true);
		$template->assign("CORNER_NAME"      , $row["corner_name"], true);
		$template->assign("CORNER_ROMAN"     , $row["corner_roman"], true);
		
		$template->if_enable("EXISTS_LIST"   , (int)$row["mcnt"] > 0);
		$template->if_enable("NO_EXISTS_LIST", (int)$row["mcnt"] == 0);
		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
		$template->if_enable("DISP_NOTICE"    , $row["notice_flg"] == 1);
		$template->if_enable("NO_DISP_NOTICE" , $row["notice_flg"] != 1);
		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
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
	//--- 2023/10/23 Upd S by S.Okamoto お知らせ表示設定追加
	//getData($_POST , array("CORNER_NO", "CORNER_NAME", "CORNER_ROMAN", "REMARKS"));
	getData($_POST , array("CORNER_NO", "CORNER_NAME", "CORNER_ROMAN", "NOTICE_FLG", "REMARKS"));
	//--- 2023/10/23 Upd E

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
				->field("corner_no, corner_name, corner_roman, remarks")
				->field("notice_flg")		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
				->from( "mst_corner" )
				->where()
					->and( "corner_no = ",   $_GET["NO"], FD_NUM )
			->createSql();
		
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
	}else{
		$row["corner_no"]    = $_POST["CORNER_NO"];
		$row["corner_name"]  = $_POST["CORNER_NAME"];
		$row["corner_roman"] = $_POST["CORNER_ROMAN"];
		$row["notice_flg"]   = $_POST["NOTICE_FLG"];		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
		$row["remarks"]      = $_POST["REMARKS"];
	}
	if (mb_strlen($row["notice_flg"]) == 0) $row["notice_flg"] = 0;		//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("UPD"   ,  mb_strlen($row["corner_no"]) > 0);
	
	$template->assign("DEFAULT_END_DATE", DEFAULT_END_DATE, true);
	$template->assign("NO"              , $row["corner_no"], true);
	$template->assign("CORNER_NO"       , $row["corner_no"], true);
	$template->assign("CORNER_NAME"     , $row["corner_name"], true);
	$template->assign("CORNER_ROMAN"    , $row["corner_roman"], true);
	//--- 2023/10/23 Add by S.Okamoto お知らせ表示設定追加
	$template->assign("CHK_NOTICE_FLG"  , ($row["notice_flg"] == 1) ? " checked" : "", true);
	$template->assign("REMARKS"         , $row["remarks"], true, true);
	
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
	//--- 2023/10/23 Upd S by S.Okamoto お知らせ表示設定追加
	//getData($_POST , array("CORNER_NO", "CORNER_NAME", "CORNER_ROMAN", "REMARKS"));
	getData($_POST , array("CORNER_NO", "CORNER_NAME", "CORNER_ROMAN", "NOTICE_FLG", "REMARKS"));
	
	// 初期処理
	if (mb_strlen($_POST["NOTICE_FLG"]) == 0) $_POST["NOTICE_FLG"] = 0;
	//--- 2023/10/23 Upd E

	// 入力チェック
	if ($_GET["ACT"] != "del") {
		$message = checkInput($template);
		if (mb_strlen($message) > 0) {
			DispDetail($template, $message);
			return;
		}
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";

		// 紐づけ削除
		// dat_machineConnerから関連しているマシン番号を取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dmc.machine_no, dm.machine_corner")
				->from( "dat_machineCorner dmc" )
				->from( "left join dat_machine dm on dm.machine_no = dmc.machine_no" )
				->where()
					->and( "corner_no = ",   $_GET["NO"], FD_NUM )
			->createSql();
		$cornerMachines = $template->DB->getAll( $sql, PDO::FETCH_ASSOC);

		// 実機データ更新
		foreach ($cornerMachines as $idx => $value) {
			if (mb_strlen($value["machine_corner"]) == 0) continue;
			$temp = explode(",", $value["machine_corner"]);
			$key = array_search($_GET["NO"], $temp);
			if ($key !== false) {
				unset($temp[$key]);
			}

			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update("dat_machine")
					->set()
						->value( "machine_corner", (empty($temp)) ? "" : implode(",", $temp), FD_STR)
						->value( "upd_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"    , "current_timestamp", FD_FUNCTION)
					->where()
						->and( "machine_no = " , $value["machine_no"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}

		// 実機所属コーナーデータ物理削除
		$dmcDeleteSql = "delete from dat_machineCorner where corner_no = " . $template->DB->conv_sql($_GET["NO"], FD_NUM);
		$template->DB->query($dmcDeleteSql);

		// コーナー論理削除
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_corner" )
				->set()
					->value( "del_flg"   , 1, FD_NUM)
					->value( "del_no"    , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"    , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "corner_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);

	}else{
		if (mb_strlen($_POST["CORNER_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_corner" )
					->set()
						->value( "corner_name"      , $_POST["CORNER_NAME"], FD_STR)
						->value( "corner_roman"     , $_POST["CORNER_ROMAN"], FD_STR)
						->value( "notice_flg"       , $_POST["NOTICE_FLG"], FD_NUM)		//--- 2023/10/23 ADd by S.Okamoto お知らせ表示設定追加
						->value( "remarks"          , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"           , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "corner_no =" , $_POST["CORNER_NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
			
		}else{
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_corner" )
						->value( "corner_name"      , $_POST["CORNER_NAME"], FD_STR)
						->value( "corner_roman"     , $_POST["CORNER_ROMAN"], FD_STR)
						->value( "notice_flg"       , $_POST["NOTICE_FLG"], FD_NUM)		//--- 2023/10/23 ADd by S.Okamoto お知らせ表示設定追加
						->value( "remarks"          , $_POST["REMARKS"], FD_STR)
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
			$title = "コーナー更新完了";
			$msg = "コーナー情報を更新しました。";
			break;
		case "del":
			// 削除
			$title = "コーナー削除完了";
			$msg = "コーナーを削除しました。";
			break;
		default:
			// 新規登録
			$title = "コーナー登録完了";
			$msg = "新しいコーナーを登録しました。";
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
	
	$errMessage = (new SmartAutoCheck($template))
		//コーナー名
		->item($_POST["CORNER_NAME"])
			->required($template->message("A1201"))
			->maxLength($template->message("A1202"), 20)					//文字長の最高値
		//コーナー名（英語）
		->item($_POST["CORNER_ROMAN"])
			->maxLength($template->message("A1203"), 50)					//文字長の最高値
	->report();

	if (empty($errMessage)) {
		// コーナー名重複
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("count(*)")
				->from("mst_corner")
				->where()
					->and(false, "del_flg = ", 0, FD_NUM)
					->and(true, "corner_no <> ", $_POST["CORNER_NO"], FD_NUM)
					->and(false, "corner_name = ", $_POST["CORNER_NAME"], FD_STR)
			->createSql();
		$cnt = $template->DB->getOne($sql);
		if ($cnt > 0) {
			array_push($errMessage, $template->message("A1211"));
		}
		// コーナー名(英語)重複
		if (mb_strlen($_POST["CORNER_ROMAN"]) > 0) {
			$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
				->select()
					->field("count(*)")
					->from("mst_corner")
					->where()
						->and(false, "del_flg = ", 0, FD_NUM)
						->and(true, "corner_no <> ", $_POST["CORNER_NO"], FD_NUM)
						->and(false, "corner_roman = ", $_POST["CORNER_ROMAN"], FD_STR)
				->createSql();
			$cnt = $template->DB->getOne($sql);
			if ($cnt > 0) {
				array_push($errMessage, $template->message("A1212"));
			}
		}

	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
