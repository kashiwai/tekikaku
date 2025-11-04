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
 * システム設定画面表示
 *
 * システム設定画面の表示を行う
 *
 * @package
 * @author   鶴野 美香
 * @version  1.0
 * @since    2021/06/03 初版作成 鶴野 美香
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));			// テンプレートHTMLプレフィックス
// この画面でしか使用しないのでここで定義する
// 設定種別
$systemSettingType = array(
	1 => "全体",
	2 => "スロット",
	3 => "パチンコ"
);
// 設定値の書式
$systemSettingFormat = array(
	1 => "文字列",
	2 => "整数",
	3 => "数値(マイナス不可、小数可)",
	4 => "日付"
);

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
	getData($_GET , array("M", "NO"));

	// 検索SQL生成
	$sqls = new SqlString();

	//一覧
	$sql = $sqls
			->select()
			->field("setting_no, setting_type, setting_name, setting_key, setting_val")
			->from("mst_setting")
			->where()
				->and("del_flg = ", "0", FD_NUM )
			->orderby("setting_type, setting_no")
		->createSql();
	//取得
	$rs = $template->DB->query($sql);
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);	// エラーメッセージ表示制御

	// 明細処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		// 行出力
		$template->assign("SETTING_NO"   , $row["setting_no"], true);
		$template->assign("SETTING_TYPE" , $template->getArrayValue($GLOBALS["systemSettingType"], $row["setting_type"]), true);
		$template->assign("SETTING_NAME" , $row["setting_name"], true);
		$template->assign("SETTING_KEY"  , $row["setting_key"], true);
		$template->assign("SETTING_VAL"  , $row["setting_val"], true);
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
	getData($_GET  , array("NO", "ACT"));
	getData($_POST , array("SETTING_TYPE", "SETTING_NAME", "SETTING_KEY", "SETTING_FORMAT", "SETTING_VAL", "REMARKS"));

	if (mb_strlen($_GET["NO"]) == 0) {
		$template->dispProcError($template->message("A0003"), false);
		return;
	}

	if (mb_strlen($message) == 0) {
		$sqls = new SqlString();

		$sql = $sqls
				->select()
				->field("setting_no, setting_type, setting_name, setting_key, setting_format, setting_val, remarks")
				->from("mst_setting")
				->where()
					->and("del_flg = ", "0", FD_NUM )
					->and("setting_no = ",  $_GET["NO"], FD_NUM)
			->createSql();

		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

		if (empty($row["setting_no"])) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}

		$_POST["SETTING_TYPE"]   = $row["setting_type"];
		$_POST["SETTING_NAME"]   = $row["setting_name"];
		$_POST["SETTING_KEY"]    = $row["setting_key"];
		$_POST["SETTING_FORMAT"] = $row["setting_format"];
		$_POST["SETTING_VAL"]    = $row["setting_val"];
		$_POST["REMARKS"]        = $row["remarks"];

	}

	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);

	$template->assign("NO"                 , $_GET["NO"], true);
	$template->assign("SETTING_TYPE"       , $_POST["SETTING_TYPE"]);
	$template->assign("TXT_SETTING_TYPE"   , $template->getArrayValue($GLOBALS["systemSettingType"], $_POST["SETTING_TYPE"]));
	$template->assign("SETTING_NAME"       , $_POST["SETTING_NAME"], true);
	$template->assign("SETTING_KEY"        , $_POST["SETTING_KEY"], true);
	$template->assign("SETTING_FORMAT"     , $_POST["SETTING_FORMAT"]);
	$template->assign("TXT_SETTING_FORMAT" , $template->getArrayValue($GLOBALS["systemSettingFormat"], $_POST["SETTING_FORMAT"]));
	$template->assign("SETTING_VAL"        , $_POST["SETTING_VAL"], true);
	$template->assign("REMARKS"            , $_POST["REMARKS"], true);

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
	getData($_POST , array("SETTING_TYPE", "SETTING_NAME", "SETTING_KEY", "SETTING_FORMAT", "SETTING_VAL", "REMARKS"));

	if (mb_strlen($_GET["NO"]) == 0) {
		$template->dispProcError($template->message("A0003"), false);
		return;
	}

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
	if (mb_strlen($_GET["NO"]) > 0) {
		// 更新
		$mode = "update";
		$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->update("mst_setting")
				->set()
					->value("setting_val",    $_POST["SETTING_VAL"], FD_STR)
					->value("upd_no",         $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value("upd_dt",         "current_timestamp", FD_FUNCTION)
				->where()
					->and("setting_no =", $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}

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

	$title = $template->message("A0022");
	$msg = $template->message("A3104");

	// 完了画面表示
	$template->dispProcEnd($title, "", $msg);
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();

	// 設定値
	if (mb_strlen($_POST["SETTING_VAL"]) == 0) {
		$errMessage[] = $template->message("A3101");
	} else {
		if (mb_strlen($_POST["SETTING_VAL"]) > 200) {
			$errMessage[] = $template->message("A3103");
		} else {
			$formatErr = false;
			switch ($_POST["SETTING_FORMAT"]) {
				case '1':
					// 文字列
					break;
				case '2':
					// 整数
					if (!chk_numberEx($_POST["SETTING_VAL"])) {
						$formatErr = true;
					}
					break;
				case '3':
					// 数値(小数可)
					if (!chk_numeric($_POST["SETTING_VAL"], 10, 3)) {
						$formatErr = true;
					}
					break;
				case '4':
					// 日付
					if (!chk_date($_POST["SETTING_VAL"])) {
						$formatErr = true;
					}
					break;
				default:
					break;
			}
			if ($formatErr) $errMessage[] = $template->message("A3102");
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
