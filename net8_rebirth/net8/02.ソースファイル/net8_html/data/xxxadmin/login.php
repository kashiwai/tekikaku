<?php
/*
 * login.php
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
 * ログイン画面表示
 * 
 * ログイン画面の表示を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2016/08/16 初版作成 岡本 静子
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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateAdmin(false);

		// データ取得
		getData($_POST, array("M"));

		// 実処理
		switch ($_POST["M"]) {
			case "proc":			// ログイン認証処理
				ProcLogin($template);
				break;

			default:				// ログイン画面
				DispLogin($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * ログイン画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispLogin($template, $message = "") {
	// データ取得
	getData($_POST, array("ID", "PASS"));

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG" , $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);		// メッセージ表示制御

	$template->assign("ID"  , $_POST["ID"], true);
	$template->assign("PASS", "", true);

	$template->flush();
}

/**
 * ログイン認証処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcLogin($template) {
	// データ取得
	getData($_POST, array("ID", "PASS"));

	// 必須入力チェック
	$errMessage = (new SmartAutoCheck($template))
			// ID
			->item($_POST["ID"])
				->required("A0101")
				->alnum("A0104", 3)
				->break()
			// パスワード
			->item($_POST["PASS"])
				->required("A0102")
		->report(false);
	//エラーがある場合はLoginに戻す
	if (mb_strlen($errMessage) != 0 ){
		DispLogin($template, $errMessage);
		return;
	}

	// DB認証チェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("admin_no, admin_name, admin_id, admin_pass, auth_flg, deny_menu")
				->from("mst_admin")
				->where()
					->and("admin_id = ", $_POST["ID"], FD_STR)
					->and("del_flg = ", "0", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

	$errMessage = (new SmartAutoCheck($template))
					// データ未存在
					->item($row["admin_no"])
						->required("A0103")
						->break()
					// パスワード
					->item($_POST["PASS"])
						->password_verify("A0103", $row["admin_pass"] )
					->report(false);

	//エラーがある場合はLoginに戻す
	if (mb_strlen($errMessage) != 0 ){
		DispLogin($template, $errMessage);
		return;
	}

	// セッションインスタンス生成
	$template->Session = new SmartSession(URL_ADMIN . "login.php", SESSION_SEC_ADMIN, SESSION_SID_ADMIN, DOMAIN, true);
	$template->Session->start();
	$template->Session->AdminInfo = $row;

	// トランザクション開始
	$template->DB->autoCommit(false);

	// ログイン成功時、最終ログインUAを更新
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("mst_admin")
				->set()
					->value("login_dt", "current_timestamp", FD_FUNCTION )
					->value("login_ua", $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
				->where()
					->and("admin_no=",$row["admin_no"],FD_NUM)
			->createSQL();

	$template->DB->query($sql);

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	// TOP画面へ遷移
	header("Location: " . URL_ADMIN . "index.php");

}

?>
