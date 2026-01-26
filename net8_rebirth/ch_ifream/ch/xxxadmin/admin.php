<?php
/*
 * admin.php
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
 * 管理者設定画面表示
 * 
 * 管理者設定画面の表示を行う
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
	getData($_GET , array("M", "NO", "P", "ODR"));

	// 初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "admin_name asc";
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;

	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));

	// 詳細表示フラグ
	$_detail = false;
	// 検索SQL生成
	$sqls = new SqlString();
	
	if( $template->Session->AdminInfo["auth_flg"] == 1) {
		$_detail = true;
		//自分のみ
		$csql = $sqls
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "count(*)" )
				->from( "mst_admin" )
				->where()
					->and( "admin_no = ", $template->Session->AdminInfo["admin_no"], FD_NUM )
					->and("del_flg != ", "1", FD_NUM)		// 2020/04/20 [ADD]
			->createSql();
	} else {
		//自分より下位権限
		$csql = $sqls
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "count(*)" )
				->from( "mst_admin" )
				->where()
					->and( "auth_flg <= ", $template->Session->AdminInfo["auth_flg"], FD_NUM )
					->and( "del_flg != ",  "1")
			->createSql();
	}
	
	$allrows = $template->DB->getOne($csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);	// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	//一覧
	$lsql = $sqls
			->resetField()
			->field( "admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu, login_dt, login_ua, upd_dt")
			->orderby( $_GET["ODR"] )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
		->createSql();
	//取得
	$rs = $template->DB->query($lsql);
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);	// エラーメッセージ表示制御

	// 明細処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		// 行出力
		$template->assign("SELFROW"       , ( !$_detail && $row["admin_no"] == $template->Session->AdminInfo["admin_no"] )? "class=\"selfrow\"":"");
		$template->assign("ADMIN_NO"      , $row["admin_no"], true);
		$template->assign("ADMIN_NAME"    , $row["admin_name"], true);
		$template->assign("ADMIN_ID"      , $row["admin_id"], true);
		$template->assign("AUTH_FLG"      , $template->getArrayValue($GLOBALS["AdminAuthStatus"], $row["auth_flg"]), true);
		$template->assign("LOGIN_DT"      , format_datetime($row["login_dt"]), true);
		$template->assign("UPD_DT"        , format_datetime($row["upd_dt"]), true);
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);

	// ページ処理
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW", (string)$allrows);		// 総件数
	$template->assign("P", (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP", (string)$allpage);		// 総ページ数
	$template->assign("ODR", $_GET["ODR"]);				// ソート順

	// 表示制御
	$template->if_enable("OVER_ADMIN", $template->Session->AdminInfo["auth_flg"] > 1);		// 2020/04/20 [ADD] 管理以上権限表示制御追加

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
	getData($_GET  , array("NO", "ACT"));
	getData($_POST , array("DENY_MENU"), false);	// 2020/04/20 [UPD] くそ過ぎるので修正
	getData($_POST , array("ADMIN_NAME", "ADMIN_ID", "ADMIN_PASS", "SAVE_AUTH_FLG"
							, "LOGIN_DT", "LOGIN_UA", "AUTH_FLG"
						));

	// 権限チェック
	if (mb_strlen($_GET["NO"]) > 0) {
		$sqls = new SqlString();
		
		if( $template->Session->AdminInfo["auth_flg"] == 1) {
			// 自身のみ許可
			if( $_GET["NO"] == $template->Session->AdminInfo["admin_no"]){
				$_sql = $sqls
						->setAutoConvert( [$template->DB,"conv_sql"] )
						->select()
						->field( "admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu, login_dt, login_ua, upd_dt")
						->from( "mst_admin" )
						->where()
							->and( "admin_no = ",  $template->Session->AdminInfo["admin_no"], FD_NUM )
							->and( "auth_flg <= ", $template->Session->AdminInfo["auth_flg"], FD_NUM )
							->and( "del_flg != ",  "1")
					->createSql();
			} else {
				$template->dispProcError($template->message("A0003"), false);
				return;
			}
		} else {
			// 下位権限のみ
			$_sql = $sqls
					->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
					->field( "admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu, login_dt, login_ua, upd_dt")
					->from( "mst_admin" )
					->where()
						->and( "admin_no = ", $_GET["NO"], FD_NUM )
						->and( "auth_flg <= ", $template->Session->AdminInfo["auth_flg"], FD_NUM )
						->and( "del_flg != ",  "1")
				->createSql();
		}
		
		$row = $template->DB->getRow($_sql, PDO::FETCH_ASSOC);
		// 2020/04/24 [ADD Start]データ不存在は通常あり得ないのでシステムエラー
		if (empty($row["admin_no"])) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		// 2020/04/24 [ADD End]データ不存在は通常あり得ないのでシステムエラー
		
		if (mb_strlen($message) == 0 || $_GET["ACT"] == "del") { // 2020/04/20 [UPD]
			$_POST["ADMIN_NAME"]    = $row["admin_name"];
			$_POST["ADMIN_ID"]      = $row["admin_id"];
			$_POST["AUTH_FLG"]      = $row["auth_flg"];
			$_POST["SAVE_AUTH_FLG"] = $row["auth_flg"];		// 2020/04/20 [ADD]
			//$_POST["ADMIN_PASS"]    = $row["admin_pass"];
			// 2020/04/20 [UPD Start] 拒否を許可に変換
			$denyMenu = (mb_strlen($row["deny_menu"]) > 0) ? explode(",", $row["deny_menu"]) : array();
			$_POST["DENY_MENU"] = array();
			//拒否メニューの配列の値を許可の形に変換する
			foreach ($GLOBALS["AuthMenuID"] as $key => $value){
				if( !in_array($key, $denyMenu)) $_POST["DENY_MENU"][] = $key;
			}
			// 2020/04/20 [UPD End] 拒否を許可に変換
		}
		$_POST["LOGIN_DT"] = format_datetime($row["login_dt"]);
		$_POST["LOGIN_UA"] = $row["login_ua"];
		
		if (mb_strlen($row["admin_no"]) == 0) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
	// 2020/04/20 [ADD Start]
	} else {
		if (mb_strlen($message) == 0) {
			// 新規時の使用可能メニューは全チェック
			$_POST["DENY_MENU"] = array_keys($GLOBALS["AuthMenuID"]);
		}
	// 2020/04/20 [ADD End]
	}

	// 2020/04/20 [UPD Start] 毎回変換されたらたまったもんじゃない
	/*
	//拒否メニューの配列の値を許可の形に変換する
	$allow_menu = array();
	if( is_array( $_POST["DENY_MENU"])){
		foreach ( $GLOBALS["AuthMenuID"] as $key => $value){
			if( !in_array($key, $_POST["DENY_MENU"])) $allow_menu[] = $key;
		}
	}
	*/
	$_POST["DENY_MENU"] = ((is_array($_POST["DENY_MENU"])) ? $_POST["DENY_MENU"] : array());
	// 2020/04/20 [UPD End] 毎回変換されたらたまったもんじゃない

	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->assign("ADMIN_PASS_MIN", ADMIN_PASS_MIN, true);

	$template->assign("NO"            , $_GET["NO"], true);
	$template->assign("ADMIN_NAME"    , $_POST["ADMIN_NAME"], true);
	$template->assign("ADMIN_ID"      , $_POST["ADMIN_ID"], true);
	$template->assign("DENY_MENU"     , makeCheckBoxArray($GLOBALS["AuthMenuID"], "DENY_MENU[]", $_POST["DENY_MENU"], 0, "", "&nbsp;&nbsp;", "", true, 2));		// 2020/04/20 [UPD]
	$template->assign("AUTH_LIST"     , AuthFlgListHtml( $template->Session->AdminInfo["auth_flg"]));
	$template->assign("SAVE_AUTH_FLG" , $_POST["SAVE_AUTH_FLG"], true);		// 2020/04/20 [ADD]
	
	// 以下、表示のみ
	$template->assign("LOGIN_DT" , $_POST["LOGIN_DT"], true);
	$template->assign("LOGIN_UA" , $_POST["LOGIN_UA"], true);

	// 表示制御
	$template->if_enable("NEW"  , mb_strlen($_GET["NO"]) == 0);
	$template->if_enable("DEL"  , mb_strlen($_GET["NO"]) != 0 && $_GET["NO"] != $template->Session->AdminInfo["admin_no"]);
	$template->if_enable("UPD"  , mb_strlen($_GET["NO"]) != 0);		// 2020/04/20 [ADD] EDITが使えないので追加

	if( $template->Session->AdminInfo["auth_flg"] == 1) {
		//一般
		$template->if_enable("EDIT" , false);			//自分の場合、とりあえずは削除を表示 2020/04/20 [UPD] 一般はパスワード変更のみの為、非表示に変更
		$template->if_enable("EDIT_AUTH" , false);	//権限操作非表示 2020/04/20 [UPD] 条件記述が無効の為、修正
		$template->if_enable("EDIT_NO_AUTH" , true);	//編集権限無し項目 2020/04/20 [ADD]
	}else{
		$template->if_enable("EDIT" , $_POST["SAVE_AUTH_FLG"] != "9");
		$template->if_enable("EDIT_AUTH" , $template->Session->AdminInfo["auth_flg"] >= $_POST["SAVE_AUTH_FLG"]/*自分の権限以下だったら管理権限操作可能*/);
		$template->if_enable("EDIT_NO_AUTH", $template->Session->AdminInfo["auth_flg"] < $_POST["SAVE_AUTH_FLG"]);	//編集権限無し項目 2020/04/20 [ADD] 自権限より上の権限者データは編集不可
	}

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
	getData($_GET  , array("NO", "ACT"));
	getData($_POST , array("DENY_MENU"), false);
	getData($_POST , array("ADMIN_NAME", "ADMIN_ID", "ADMIN_PASS", "AUTH_FLG", "SAVE_AUTH_FLG"
						));

	// 初期処理
	$adminPass = (mb_strlen($_POST["ADMIN_PASS"]) > 0) ? password_hash($_POST["ADMIN_PASS"], PASSWORD_DEFAULT) : "";
	$authMenu = (!empty($_POST["DENY_MENU"])) ? implode(",", $_POST["DENY_MENU"]) : "";
	//入力はallowなので逆に
	$_menu_update = AuthList_Toggle( $_POST["DENY_MENU"]);

	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}

	// トランザクション開始
	$template->DB->autoCommit(false);

	// 更新処理
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		//権限チェック
		$message = checkAuth( $template, $template->Session->AdminInfo["admin_no"], $_GET["NO"]);
		if (mb_strlen($message) > 0) {
			DispDetail($template, $message);
			return;
		}
		
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_admin" )
				->set()
					->value( "del_flg",    "1", FD_NUM)
					->value( "del_dt",     "current_timestamp", FD_FUNCTION)
					->value( "del_no",     $template->Session->AdminInfo["admin_no"], FD_NUM)
				->where()
					->and( "admin_no =",   $_GET["NO"], FD_NUM)
					->and( "auth_flg <= ", $template->Session->AdminInfo["auth_flg"], FD_NUM )
			->createSQL();
		$template->DB->query($sql);
		
	} else {
		if (mb_strlen($_GET["NO"]) > 0) {
			// 更新
			$mode = "update";
			//権限チェック
			$message = checkAuth( $template, $template->Session->AdminInfo["admin_no"], $_GET["NO"]);
			if (mb_strlen($message) > 0) {
				DispDetail($template, $message);
				return;
			}

			$sqls = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_admin" )
					->set()
						->value( "admin_name",       $_POST["ADMIN_NAME"], FD_STR)
						->value( "admin_id",         $_POST["ADMIN_ID"], FD_STR)
						->value( true, "auth_flg",   $_POST["AUTH_FLG"], FD_NUM)
						->value( true, "admin_pass", $adminPass, FD_STR)
						->value( "upd_no",           $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt",           "current_timestamp", FD_FUNCTION)
					->where()
						->and( "admin_no =", $_GET["NO"], FD_NUM)
						->and( "auth_flg <= ", $template->Session->AdminInfo["auth_flg"], FD_NUM );
			if( $template->Session->AdminInfo["auth_flg"] != 1) $sqls->value( "deny_menu",        $_menu_update, FD_STR);
			$sql = $sqls->createSQL();
			$template->DB->query($sql);
			
		} else {
			// 新規
			$mode = "new";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_admin" )
						->value( "admin_name", $_POST["ADMIN_NAME"], FD_STR)
						->value( "admin_id",   $_POST["ADMIN_ID"], FD_STR)
						->value( "admin_pass", $adminPass, FD_STR)
						->value( "auth_flg",   $_POST["AUTH_FLG"], FD_NUM)
						->value( "deny_menu",  $_menu_update, FD_STR)
						->value( "del_flg",    "0", FD_NUM)
						->value( "add_no",     $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt",     "current_timestamp", FD_FUNCTION)
						->value( "upd_no",     $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt",     "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($sql);
		}
	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	// セッション更新
	if ($_GET["NO"] == $template->Session->AdminInfo["admin_no"]) {
		// 2020/04/20 [UPD Start] 一般管理の画面にそもそもで管理権限は無い
		$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu")
					->from("mst_admin")
					->where()
						->and("admin_no = ", $_GET["NO"], FD_NUM)
						->and("del_flg = ", "0", FD_NUM)
				->createSQL();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$template->Session->AdminInfo = $row;
		// 2020/04/20 [UPD Start] 一般管理の画面にそもそもで管理権限は無い
	}

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
			$title = $template->message("A0022");
			$msg = $template->message("A0352");
			break;

		case "del":
			// 削除
			$title = $template->message("A0023");
			$msg = $template->message("A0353");
			break;

		default:
			// 新規登録
			$title = $template->message("A0021");
			$msg = $template->message("A0351");
	}

	// 完了画面表示
	$template->dispProcEnd($title, "", $msg);
}

/**
 * 権限チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	integer	$adminNo		更新者の管理者No
 * @param	integer	$targetNo		更新対象管理者No
 * @return	string					エラーメッセージ
 * @info	概ね無駄な感じだが邪魔はしなさそうなので放置
 */
 
function checkAuth( $template, $adminNo, $targetNo) {
	$errMessage = "";
	// 閲覧可否チェック
	$ssql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "auth_flg")
			->from( "mst_admin" )
			->where()
				->and( "admin_no = ",   $adminNo, FD_NUM )
				->and( "admin_name = ", $template->Session->AdminInfo["admin_name"], FD_STR )
				->and( "admin_pass = ", $template->Session->AdminInfo["admin_pass"], FD_STR )
		->createSql();
	$row = $template->DB->getRow($ssql, PDO::FETCH_ASSOC);
	
	if( !$row){
		$errMessage = $template->message("A0004");
		return $errMessage;
	}
	
	$tsql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(admin_no)")
			->from( "mst_admin" )
			->where()
				->and( "admin_no = ",  $targetNo, FD_NUM )
				->and( "auth_flg <= ", $row["auth_flg"], FD_NUM )
		->createSql();
	$cnt = $template->DB->getOne($tsql);
	
	if ($cnt == 0) {
		// 権限無し
		$errMessage = $template->message("A0004");
	}
	
	return $errMessage;
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$menuName = basename(get_self(), ".php");	// アカウント設定

	$errMessage = array();
	if ($_GET["ACT"] != "del") {
		// 氏名
		if (mb_strlen($_POST["ADMIN_NAME"]) == 0) {
			$errMessage[] = $template->message("A0301");
		}
		// ユーザID
		if (mb_strlen($_POST["ADMIN_ID"]) == 0) {
			$errMessage[] = $template->message("A0302");
		} else {
			if (!chk_alnum($_POST["ADMIN_ID"])) {
				$errMessage[] = $template->message("A0304");
			} else {
				$sql = (new SqlString())
					->setAutoConvert([$template->DB, "conv_sql"])
					->select()
						->field("count(*)" )
					->from("mst_admin")
					->where()
						->and(true, "admin_no != ", $_GET["NO"], FD_NUM)
						->and("admin_id = ", $_POST["ADMIN_ID"], FD_STR)
						->and("del_flg != ",  "1", FD_NUM)
				->createSql();
				$idCnt = $template->DB->getOne($sql);
				if ((int)$idCnt > 0) $errMessage[] = $template->message("A0308");
			}
		}
		// パスワード
		if (mb_strlen($_GET["NO"]) == 0 && mb_strlen($_POST["ADMIN_PASS"]) == 0) {
			$errMessage[] = $template->message("A0303");
		}
		if (mb_strlen($_POST["ADMIN_PASS"]) > 0) {
			if (!chk_alnum($_POST["ADMIN_PASS"])) $errMessage[] = $template->message("A0305");
			if (mb_strlen($_POST["ADMIN_PASS"]) < ADMIN_PASS_MIN) $errMessage[] = $template->message("A0307");
			if (mb_strlen($_POST["ADMIN_PASS"]) > 20) $errMessage[] = $template->message("A0306");
		}
		// 管理権限
		if ($_POST["SAVE_AUTH_FLG"] == "2" && $_POST["SAVE_AUTH_FLG"] != $_POST["AUTH_FLG"]) {
			$sql = (new SqlString())
				->setAutoConvert([$template->DB, "conv_sql"])
				->select()
					->field("count(*)")
				->from("mst_admin")
				->where()
					->and(true, "admin_no != ", $_GET["NO"], FD_NUM)
					->and("auth_flg = ", "2", FD_NUM)
					->and("del_flg != ",  "1", FD_NUM)
			->createSql();
			$authCnt = $template->DB->getOne($sql);
			if ((int)$authCnt <= 0) $errMessage[] = $template->message("A0309");
		}
		// 許可メニュー：アカウント設定
		if ($template->Session->AdminInfo["auth_flg"] != 1) {
			$denyAdm = (!in_array($menuName, (array)$_POST["DENY_MENU"]));	// アカウント設定拒否
			// ログイン者のアカウント設定は外せない
			if ($denyAdm && $_GET["NO"] == $template->Session->AdminInfo["admin_no"]) $errMessage[] = $template->message("A0312");

			// 許可メニューにアカウント設定が無い若しくは管理の管理権限変更時にも管理にアカウント設定が残るかチェック
			if (count($errMessage) <= 0 && ($denyAdm || ($_POST["SAVE_AUTH_FLG"] == "2" && $_POST["SAVE_AUTH_FLG"] != $_POST["AUTH_FLG"]))) {
				$sql = (new SqlString())
					->select()
						->field("count(*) as adm_cnt")
						->field("IFNULL(sum(IFNULL(deny_menu LIKE " . $template->DB->conv_sql("%" . $menuName . "%", FD_STR) . ", 0)), 0) as deny_cnt")
					->from("mst_admin")
					->where()
						->and("auth_flg = ", "2", FD_NUM)
						->and("del_flg != ", "1", FD_NUM)
						->and(true, "admin_no != ", $_GET["NO"], FD_NUM)
				->createSql("\n");
				$cntRow = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
				if ($cntRow["adm_cnt"] == $cntRow["deny_cnt"]) $errMessage[] = $template->message("A0313");
			}
		}
	} else {
		if ($_GET["NO"] == $template->Session->AdminInfo["admin_no"]) {
			// ログイン者のアカウントは削除不可
			$errMessage[] = $template->message("A0311");
		} else {
			// 削除対象が管理の場合管理が1件も無くなる削除は不可
			$sql = (new SqlString())
				->setAutoConvert([$template->DB, "conv_sql"])
				->select()
					->field("my.auth_flg, count(*) as auth_cnt")
				->from("mst_admin my")
				->from("inner join mst_admin au on my.auth_flg = au.auth_flg and au.del_flg != 1")
				->where()
					->and("my.admin_no = ", $_GET["NO"], FD_NUM)
					->and("my.del_flg != ",  "1", FD_NUM)
				->groupby( "my.auth_flg")
				->createSql();
			$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
			if (mb_strlen($row["auth_flg"]) <= 0 || ($row["auth_flg"] == "2" && (int)$row["auth_cnt"] <= 1)) $errMessage[] = $template->message("A0310");
			// システム管理者による削除時は管理にアカウント設定が残るかチェック ※元から無い場合も引っ掛ります
			if (count($errMessage) <= 0 && $template->Session->AdminInfo["auth_flg"] == 9) {
				$sql = (new SqlString())
					->select()
						->field("count(*) as adm_cnt")
						->field("IFNULL(sum(IFNULL(deny_menu LIKE " . $template->DB->conv_sql("%" . $menuName . "%", FD_STR) . ", 0)), 0) as deny_cnt")
					->from("mst_admin")
					->where()
						->and("auth_flg = ", "2", FD_NUM)
						->and("del_flg != ", "1", FD_NUM)
						->and("admin_no != ", $_GET["NO"], FD_NUM)
				->createSql("\n");
				$cntRow = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
				if ($cntRow["adm_cnt"] == $cntRow["deny_cnt"]) $errMessage[] = $template->message("A0313");
			}
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * Authメニューの配列の値を許可の形に変換する
 * @access	private
 * @param	object	$denylist		deny_menu
 * @return	string					許可リスト
 */
function AuthList_Toggle( $denylist){
	$allow_menu = array();
	if( is_array( $denylist)){
		foreach ( $GLOBALS["AuthMenuID"] as $key => $value){
			if( !in_array($key, $denylist)){
				$allow_menu[] = $key;
			}
		}
	}else{
		foreach ( $GLOBALS["AuthMenuID"] as $key => $value){
			$allow_menu[] = $key;
		}
	}
	if( empty($allow_menu)){
		return "";
	}else{
		return implode(",", $allow_menu);
	}
}

/**
 * 管理権限のチェックボックスを生成する
 * @access	private
 * @param	string	$authflg		auth_flg
 * @return	string					HTML文字列
 */
function AuthFlgListHtml( $authflg){
	/* 自分の権限より上の権限は非表示にする */
	$ary = array();
	foreach( $GLOBALS["AdminAuthStatus"] as $key=>$value){
		if( (int)$authflg >= $key) $ary[$key] = $value;
	}
	return makeRadioArray( $ary, "AUTH_FLG", $_POST["AUTH_FLG"], "", false, false);
}

?>
