<?php
/*
 * leave.php
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
 * 退会画面表示
 * 
 * 退会画面の表示を行う
 * 
 * @package
 * @author   鶴野 美香
 * @version  2.0
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
		getData($_GET, array("M"));
		
		if( $retLogin){
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
		}else{
			
			switch ($_GET["M"]) {
				case "end":				// 完了画面表示
					DispEnd($template);
					break;
				default:				// TOPへ
					header("Location: " . URL_SSL_SITE . "/index.php");
			}
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
	getData($_POST , array("PASS", "REASON"));
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);
	$template->assign("MEMBER_PASS_MIN"       , MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_MAX"       , MEMBER_PASS_MAX, true);
	$template->assign("MAIL_LIMIT"            , MAIL_LIMIT, true);
	
	$template->assign("PASS"      , $_POST["PASS"], true);
	$template->assign("REASON"    , $_POST["REASON"], true);
	
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
	getData($_POST , array("PASS", "REASON"));

	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}

	// 画面表示開始
	$template->open(PRE_HTML . "_conf.html");
	$template->assignCommon();
	
	$template->assign("PASS"        , $_POST["PASS"], true);
	$template->assign("REASON"      , $_POST["REASON"], true);
	$template->assign("DISP_REASON" , $_POST["REASON"], true, true);
	
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
	getData($_POST , array("PASS", "REASON"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	
	$sql = (new SqlString($template->DB))
		->update( "mst_member" )
			->set()
				->value( "state"      , "9", FD_NUM)
				->value( "quit_reason", $_POST["REASON"], FD_STR)
				->value( "quit_dt"    , "current_timestamp", FD_FUNCTION)
				->value( "upd_dt"     , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"     , $template->Session->UserInfo["member_no"], FD_NUM)
			->where()
				->and(false, "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "mail = "     , $template->Session->UserInfo["mail"], FD_STR)
				->and(false, "pass = "     , $template->Session->UserInfo["pass"], FD_STR)
		->createSQL();
	$template->DB->query($sql);
	
	// セッションクリア
	$template->Session->clear(false);
	
	// 完了画面へ
	header("Location: " . URL_SITE . $template->Self . "?M=end");
	
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
	
	// DB認証チェック
	$sql = (new SqlString( $template->DB))
			->select()
				->field("member_no, nickname, mail, pass, last_name, first_name, state, point, login_dt")
				->from("mst_member")
				->where()
					->and(false, "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and(false, "mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and(false, "pass = ",      $template->Session->UserInfo["pass"], FD_STR)
					->and(false, "state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql);
	
	$errMessage = array();
	$errMessage = (new SmartAutoCheck($template))
		// パスワード
		->item($_POST["PASS"])
			->required("U0501")
			->password_verify("U0504", $row["pass"] )
		// 退会理由
		->item($_POST["REASON"])
			->required("U0505")
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
