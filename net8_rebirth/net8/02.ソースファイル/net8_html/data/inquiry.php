<?php
/*
 * inquiry.php
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
 * お問い合わせ画面表示
 * 
 * お問い合わせ画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/07 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		$retLogin = $template->checkSessionUser(true, false);
		
		// データ取得
		getData($_GET, array("M", "MACHINENO", "DATE"));
		
		// 実処理
		switch ($_GET["M"]) {
			case "conf":			// 確認画面表示
				DispConf($template);
				break;

			case "regist":			// 登録処理
				ProcData($template);
				break;

			case "end":				// 完了画面表示
				DispEnd($template);
				break;

			default:				// 入力画面
				DispInput($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 入力画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispInput($template, $message = "") {
	// データ取得
	getData($_POST , array("INQUIRY_TITLE", "INQUIRY_BODY", "MACHINENO", "TGDATE", "MAIL"));
	
	if( $_POST["TGDATE"] == "" && $_GET["DATE"] != "") $_POST["TGDATE"] = date("Y/m/d", $_GET["DATE"]);
	
	$_login_flg  = false;
	if( $template->checkSessionUser(true, false)){
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("men.member_no, men.mail")
				->from("mst_member men")
				->where()
					->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and(false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
					->and(false, "men.state = ", "1", FD_NUM)
				->createSQL();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		$_login_flg  = true;
		
		if( mb_strlen($_POST["MAIL"]) == 0){
			$_POST["MAIL"] = $row["mail"];
		}
	}
	
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);
	$template->if_enable("LOGIN"  , $_login_flg);
	$template->if_enable("NOLOGIN", !$_login_flg);
	
	$template->assign("MAIL_LIMIT"            , MAIL_LIMIT, true);
	
	$template->assign("SEL_INQUIRY_TITLE" , makeOptionArray($GLOBALS["InquiryTitleList"], $_POST["INQUIRY_TITLE"], false));
	$template->assign("INQUIRY_BODY"      , $_POST["INQUIRY_BODY"], true);
	$template->assign("MACHINENO"         , ($_POST["MACHINENO"]!="")? $_POST["MACHINENO"]: $_GET["MACHINENO"] , true);
	$template->assign("TGDATE"            , $_POST["TGDATE"], true);
	$template->assign("MAIL"              , $_POST["MAIL"], true);
	
	// 表示
	$template->flush();
}

/**
 * 確認画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispConf($template) {

	// データ取得
	getData($_POST , array("INQUIRY_TITLE", "INQUIRY_BODY", "MACHINENO", "TGDATE", "MAIL"));

	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}

	// 画面表示開始
	$template->open(PRE_HTML . "_conf.html");
	$template->assignCommon();

	$template->assign("INQUIRY_TITLE"      , $_POST["INQUIRY_TITLE"], true);
	$template->assign("DISP_INQUIRY_TITLE" , $template->getArrayValue($GLOBALS["InquiryTitleList"], $_POST["INQUIRY_TITLE"]), true);
	$template->assign("INQUIRY_BODY"       , $_POST["INQUIRY_BODY"], true);
	$template->assign("DISP_INQUIRY_BODY"  , $_POST["INQUIRY_BODY"], true, true);
	$template->assign("TGDATE"             , $_POST["TGDATE"], true);
	$template->assign("MACHINENO"          , $_POST["MACHINENO"], true);
	$template->assign("MAIL"               , $_POST["MAIL"], true);
	
	$template->if_enable("MACHINENO_BLOCK" , mb_strlen($_POST["MACHINENO"]) > 0);
	$template->if_enable("DATE_BLOCK"      , mb_strlen($_POST["TGDATE"]) > 0);

	// 表示
	$template->flush();
}

/**
 * 送信処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcData($template) {
	
	// データ取得
	getData($_POST , array("INQUIRY_TITLE", "INQUIRY_BODY", "MACHINENO", "TGDATE", "MAIL"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	
	$_login_flg  = false;
	if( $template->checkSessionUser(true, false)){
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("men.member_no, men.mail, men.nickname")
				->from("mst_member men")
				->where()
					->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and(false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
					->and(false, "men.state = ", "1", FD_NUM)
				->createSQL();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		$_login_flg  = true;
	}
	
	//--- メール送信
	require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
	// メール送信インスタンス生成
	$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
	// 内容置換
	$subject = INQUIRY_SUBJECT;
	// %MAIL%  ⇒ メールアドレス
	$search  = array("%TITLE%", "%BODY%", "%MACHINENO%", "%TGDATE%", "%MAIL%", "%MEMBER_NO%", "%NICKNAME%");
	$replace = array($template->getArrayValue($GLOBALS["InquiryTitleList"], $_POST["INQUIRY_TITLE"]), $_POST["INQUIRY_BODY"], $_POST["MACHINENO"], $_POST["TGDATE"], $_POST["MAIL"], $row["member_no"], $row["nickname"]);
	$body = str_replace($search, $replace, INQUIRY_BODY);
	// メール送信
	$smartMailSend->setMailSendData(MAIL_FROM, MAIL_INFO, "", "", MAIL_ERROR);
	$smartMailSend->make( $subject, $body);
	$smartMailSend->send();
	//---↑
	
	header("Location: " . URL_SSL_SITE . "inquiry.php?M=end");
	exit();
	
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispEnd($template) {
	// 画面表示開始
	$template->open(PRE_HTML . "_end.html");
	$template->assignCommon();
	// 表示
	$template->flush();
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
		->item($_POST["MAIL"])
			->required("U0703")
			->maxLength("U0449", MAIL_LIMIT)
			->mail("U0706")
		// お問い合わせ種別
		->item($_POST["INQUIRY_TITLE"])
			->required("U0701")
		// 台ID
		->item($_POST["MACHINENO"])
			->any()
			->number("U0707")
		// 対象日時
		->item($_POST["TGDATE"])
			->any()
			->date("U0705")
		// お問い合わせ内容
		->item($_POST["INQUIRY_BODY"])
			->required("U0702")
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
