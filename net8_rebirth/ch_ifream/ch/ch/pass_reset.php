<?php
/*
 * pass_reset.php
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
 * パスワード再設定要求画面表示
 * 
 * パスワード再設定要求画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/13 初版作成 片岡 充
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
		
		// セッションクリア
		$template->Session->clear(false);
		
		// データ取得
		getData($_GET, array("M"));
		getData($_POST, array("M"));

		// 実処理
		switch ($_POST["M"]) {
			case "proc":									// パスワード再設定要求処理
				ProcRePass($template);
				break;
			default:
				switch ($_GET["M"]) {
					case "regist":							// パスワード再設定処理
						ProcRegistNewPass($template);
						break;
					case "change":							// パスワード再設定画面
						DispRePassInput($template);
						break;
					case "comp":							// パスワード再設定完了画面
						DispComp($template);
						break;
					case "send":							// パスワード再設定要求完了画面
						DispSend($template);
						break;
					default:								// パスワード再設定要求画面
						DispRePass($template);
				}
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * パスワード再設定要求完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispSend($template) {
	// 画面表示開始
	$template->open(PRE_HTML . "_end.html");
	$template->assignCommon();
	// 表示
	$template->flush();	
}

/**
 * パスワード再設定完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComp($template) {
	
	// 画面表示開始
	$template->open(PRE_HTML . "_regist_end.html");
	$template->assignCommon();
	
	// 表示
	$template->flush();
	
}

/**
 * パスワード再設定
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function ProcRegistNewPass($template, $message = "") {
	// データ取得
	getData($_GET,  array("RID"));
	getData($_POST, array("PASS", "PASS_CONF"));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispRePassInput($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//key存在チェック
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field( "dmi.member_no, dmi.incidental_info, dmi.limit_dt" )
			->from( "dat_mail_identify dmi" )
			->from( "inner join mst_member mm on dmi.member_no = mm.member_no")
			->where()
				->and( "dmi.identify_key = ", $_GET["RID"], FD_STR)
				->and( "dmi.identify_kbn = ", "2", FD_NUM)
				->and( "mm.state = ", "1", FD_NUM)
				->and( "mm.black_flg = ", "0", FD_NUM)
		->createSql();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	if (!empty($row["member_no"])) {
		// 有効期限チェック
		$now = new DateTime("now");
		$limit = new DateTime($row["limit_dt"]);
		if ($now > $limit){
			DispRePass($template, $template->message("U0602"));
			return;
		}
	} else {
		DispRePass($template, $template->message("U0003"));
		return;
	}
	
	$pass = $_POST["PASS"];
	$passHash = password_hash($pass, PASSWORD_DEFAULT);
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value( "pass",       $passHash, FD_STR)
				->value( "upd_dt",     "current_timestamp", FD_FUNCTION)
				->value( "upd_no",     "0", FD_NUM)
			->where()
				->and("member_no = ", $row["member_no"], FD_STR)
				->and("mail = "     , $row["incidental_info"], FD_STR)
				->and("state = "    , "1", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	$delsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->delete()
			->from( "dat_mail_identify" )
			->where()
				->and( "identify_key = ", $_GET["RID"], FD_STR)
				->and( "identify_kbn = ", "2", FD_NUM)
				->and( "member_no = ", $row["member_no"], FD_STR)
		->createSql();
	$template->DB->query($delsql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	
	header("Location: " . URL_SSL_SITE . "pass_reset.php?M=comp");
}


/**
 * パスワード再設定画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispRePassInput($template, $message = "") {
	// データ取得
	getData($_GET, array("RID"));
	
	// チェック
	if (mb_strlen($_GET["RID"]) == 0) {
		DispRePass($template, $template->message("U0003"));
		return;
	} else {
		//key存在チェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field( "dmi.member_no, dmi.incidental_info, dmi.limit_dt" )
				->from( "dat_mail_identify dmi" )
				->from( "inner join mst_member mm on dmi.member_no = mm.member_no")
				->where()
					->and( "dmi.identify_key = ", $_GET["RID"], FD_STR)
					->and( "dmi.identify_kbn = ", "2", FD_NUM)
					->and( "mm.state = ", "1", FD_NUM)
					->and( "mm.black_flg = ", "0", FD_NUM)
			->createSql();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		
		if (!empty($row["member_no"])) {
			// 有効期限チェック
			$now = new DateTime("now");
			$limit = new DateTime($row["limit_dt"]);
			if ($now > $limit){
				DispRePass($template, $template->message("U0602"));
				return;
			}
		} else {
			DispRePass($template, $template->message("U0003"));
			return;
		}
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_regist.html");
	$template->assignCommon();
	$template->assign("ERRMSG"          , $message);
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);		// メッセージ表示制御
	
	$template->assign("MEMBER_PASS_MIN" , MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_MAX" , MEMBER_PASS_MAX, true);
	$template->assign("MAIL_LIMIT"      , MAIL_LIMIT, true);
	$template->assign("MEMBER_PASS_PATTERN"   , MEMBER_PASS_PATTERN, true);
	$template->assign("RID"             , $_GET["RID"], true);
	$template->assign("PASS"            , "", true);
	$template->assign("PASS_CONF"       , "", true);
	// 表示
	$template->flush();
}


/**
 * パスワード再設定要求画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispRePass($template, $message = "") {
	// データ取得
	getData($_POST, array("MAIL"));
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("MAIL_LIMIT", MAIL_LIMIT, true);
	$template->assign("ERRMSG"    , $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);		// メッセージ表示制御
	
	$template->assign("MAIL"  , $_POST["MAIL"], true);
	
	// 表示
	$template->flush();
}

/**
 * パスワード再設定要求
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcRePass($template) {
	// データ取得
	getData($_POST, array("MAIL"));
	
	// 必須入力チェック
	$errMessage = (new SmartAutoCheck($template))
		// メールアドレス
		->item($_POST["MAIL"])
			->required("U0101")
			->maxLength("U0449", MAIL_LIMIT)
			->mail("U0404")
		->break()
	->report(false);
	
	//エラー
	if (mb_strlen($errMessage) != 0 ){
		DispRePass($template, $errMessage);
		return;
	}
	
	// DB認証チェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname, mail, pass, last_name, first_name, regist_id")
				->from("mst_member")
				->where()
					->and("mail = ", $_POST["MAIL"], FD_STR)
					->and("state = ", "1", FD_NUM)
				->limit(1)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	// データ不存在
	if (empty($row["member_no"])) {
		DispRePass($template, $template->message("U0601"));
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	// 重複チェック
	$_registIddBooking = false;
	$registId = substr( sha1(uniqid(mt_rand(),true)), 0, 20);
	while(true){
		// コードブッキングチェック
		$hasInviteCode = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "dat_mail_identify dmi" )
			->where()
				->and( "dmi.identify_key = ", $registId, FD_STR)
			->createSql();
		$codeCount = $template->DB->getOne( $hasInviteCode);
		
		if( $codeCount > 0){
			$_registIddBooking = true;
			$registId = substr( sha1(uniqid(mt_rand(),true)), 0, 20);
		}else{
			$_registIddBooking = false;
		}
		if( !$_registIddBooking) break;
	}
	//------------------------------------------------------
	$registLimitDt = new DateTime("now");
	$registLimitDt->add(new DateInterval("PT" . CHANGE_MEMBER_LIMIT . "H"));
	$limitDt = $registLimitDt->format("Y-m-d H:i:s");
	//------------------------------------------------------
	// レコード追加
	$nsql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->insert()
			->into( "dat_mail_identify" )
				->value( "identify_key"    , $registId, FD_STR)
				->value( "identify_kbn"    , 2, FD_NUM)
				->value( "member_no"       , $row["member_no"], FD_NUM)
				->value( "limit_dt"        , $limitDt, FD_DATE)
				->value( "incidental_info" , $_POST["MAIL"], FD_STR)
				->value( "add_ua", $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
				->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
		->createSQL();
	$template->DB->query($nsql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// メール送信処理
	//---↓ 手続き用メール送信
	require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
	// メール送信インスタンス生成
	$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
	// 内容置換
	$subject = CHANGE_PASS_SUBJECT;
	$registURL = URL_SSL_SITE . $template->Self . "?M=change&RID=" . $registId;
	$search = array("%MAIL%", "%REGISTURL%", "%LIMITSPAN%", "%LIMITDATE%");
	$replace = array($_POST["MAIL"], $registURL, CHANGE_MEMBER_LIMIT, format_datetime($limitDt));
	$body = str_replace($search, $replace, CHANGE_PASS_BODY);
	// メール送信
	$smartMailSend->setMailSendData(MAIL_FROM, $row["mail"], "", "", MAIL_ERROR);
	$smartMailSend->make($subject, $body);
	$smartMailSend->send();
	//---↑
	
	header("Location: " . URL_SSL_SITE . "pass_reset.php?M=send");
	
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	//各種チェック
	$errMessage = (new SmartAutoCheck($template))
		
		//パスワード
		->item($_POST["PASS"])
			->required("U0427")
			->minLength("U0447", MEMBER_PASS_MIN)	//文字長の最低値
			->maxLength("U0430", MEMBER_PASS_MAX)	//文字長の最高値
			->alnum("U0429")						//英数字
			->if("U0470", (preg_match("/" . MEMBER_PASS_PATTERN . "/", $_POST["PASS"])))		// 入力制約
		->item($_POST["PASS_CONF"])	->required("U0428")->eq("U0431", $_POST["PASS"])
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
