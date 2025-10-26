<?php
/*
 * login.php
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
 * ログイン画面表示
 * 
 * ログイン画面の表示を行う
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

		// データ取得
		getData($_POST, array("M"));

		// 実処理
		switch ($_POST["M"]) {
			case "proc":			// ログイン認証処理
				ProcLogin($template);
				break;

			// 2020/12/28 [ADD Start]
			case "send_pin":		// PINコード送信
				ProcSendPin($template);
				break;
			case "check_pin":		// PINコード確認
				ProcCheckPin($template);
				break;
			// 2020/12/28 [ADD End]

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
	getData($_GET , array("NO"));
	getData($_POST, array("MAIL", "PASS", "NO", "TRANS"));
	
	// 遷移先の判定
	$trans = (isset($_POST["TRANS"])) ? trim($_POST["TRANS"]) : "";
	if ($trans == "") {
		$trans = URL_SITE;
		if (isset($_COOKIE["login_transfer"])) $trans .= trim($_COOKIE["login_transfer"]);
		if (strpos($trans, "my_home") !== false) $trans = str_replace(URL_SITE, URL_SSL_SITE, $trans);
	}
	
	$_deny = array( "regist.php", "pass_reset.php", "mail.php");
	foreach( $_deny as $k){
		if(strpos( $trans, $k) !== false){
			$trans = "";
			break;
		}
	}
	
	//-- cookieのドメインを指定するとWindows Safariで上手く動かないので注意
	if (isset($_COOKIE["login_transfer"])) setcookie("login_transfer", "", 0, "/");
	if (isset($_COOKIE["use_cmail"])){
		if( $_COOKIE["use_cmail"] == 1 && $_POST["MAIL"] == ""){
			$_POST["MAIL"] = $_COOKIE["user_mail"];
		}
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG"    , $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);		// メッセージ表示制御
	$template->assign("MEMBER_PASS_MIN"       , MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_MAX"       , MEMBER_PASS_MAX, true);
	$template->assign("MAIL_LIMIT"            , MAIL_LIMIT, true);
	
	$template->assign("CMAIL_CHECKED"  , (isset($_COOKIE["use_cmail"]) && $_COOKIE["use_cmail"] == 1)? "checked":"");
	
	$template->assign("MAIL"  , $_POST["MAIL"], true);
	$template->assign("PASS"  , "", true);
	$template->assign("NO"    , (mb_strlen($message)>0)? $_POST["NO"]:$_GET["NO"], false);
	$template->assign("TRANS" , $trans, false);
	
	// 表示
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
	getData($_POST, array("MAIL", "PASS", "NO", "TRANS", "CMAIL"));
	
	// 必須入力チェック
	$errMessage = (new SmartAutoCheck($template))
		// メールアドレス
		->item($_POST["MAIL"])
			->required("U0101")
			->maxLength("U0449", MAIL_LIMIT)
			->mail("U0404")
		->break()
		// パスワード
		->item($_POST["PASS"])
			->required("U0102")
			//->maxLength("U0430", MEMBER_PASS_MAX)
		->report(false);
	//エラーがある場合はLoginに戻す
	if (mb_strlen($errMessage) != 0 ){
		DispLogin($template, $errMessage);
		return;
	}
	
	//Cookie
	if( $_POST["CMAIL"] == 1){
		setcookie("use_cmail", 1, time()+60*60*24*30, "/");
		setcookie("user_mail", $_POST["MAIL"], time()+60*60*24*30, "/");
	}else{
		setcookie("use_cmail", 0, time()+60*60*24*30, "/");
	}
	
	// DB認証チェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname, mail, pass, last_name, first_name, state, point, login_dt, draw_point")
				->field("mobile, international_cd, mobile_checked_dt")	// 2020/12/25 [ADD]
				->from("mst_member")
				->where()
					->and("mail = ", $_POST["MAIL"], FD_STR)
					->and("state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	if (empty($row["member_no"])) {
		DispLogin($template, $template->message("U0103"));
		return;
	}
	
	$errMessage = (new SmartAutoCheck($template))
		// データ未存在
		->item($row["member_no"])
			->required("U0103")
		->break()
		// パスワード
		->item($_POST["PASS"])
			->password_verify("U0103", $row["pass"] )
		->report(false);
	//エラーがある場合はLoginに戻す
	if (mb_strlen($errMessage) != 0 ){
		DispLogin($template, $errMessage);
		return;
	}

	// 2020/12/25 [ADD Start]
	$needsAuthMobile = false;
	// チェック要否確認
	if (AUTH_MEMBER_MOBILE && AUTH_MOBILE_VALID_DAYS > 0) {
		
		$toDay = GetRefTimeToday();						// 基準時間の当日
		$compDt = new DateTime($toDay);
		$compDt->modify("-" . AUTH_MOBILE_VALID_DAYS . " day");
		$compDay = (int)$compDt->format("Ymd");
		$checkDt = ((mb_strlen($row["mobile_checked_dt"]) > 0) ? (int)GetRefTimeToday($row["mobile_checked_dt"], "Ymd") : 0);	// 確認日の営業日

		$needsAuthMobile = ($checkDt <= $compDay);
	}

	if ($needsAuthMobile) {		// 認証要
		$ret = SendPin($template, $row["member_no"], $row["mobile"], $row["international_cd"]);
		if ($ret == 0) {
			DispPin($template, $row["member_no"]);
		} else {
			DispSendError($template);
		}
	} else {
		ExecLogin($template, $row["member_no"]);
	}
	// 2020/12/25 [ADD End]

/* 2020/12/25 [DEL Start] ログイン処理に引越 
	$addLoginDays = 0;		// 連続ログイン日数加算用
	$last = ((mb_strlen($row["login_dt"]) > 0) ? (int)GetRefTimeToday($row["login_dt"], "Ymd") : 0);	// 最終ログインの営業日
	$toDay = (int)GetRefTimeToday(date("Y/m/d H:i"), "Ymd");	// 基準時間の当日

	// ログボ獲得処理
	if($last < $toDay){		//獲得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("point, limit_days, special_point, special_limit_days, special_start_dt, special_end_dt")
					->from("mst_grantPoint")
					->where()
						->and("proc_cd = ", "02", FD_STR)
				->createSQL();
		$logrow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

		//期間限定チェック
		$s = (int)date('Ymd', strtotime( $logrow["special_start_dt"]));
		$e = (int)date('Ymd', strtotime( $logrow["special_end_dt"]));
		if( $s <= $toDay && $toDay <= $e){
			$addPoint = $logrow["special_point"];
			$limit    = ($logrow["special_limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$logrow["special_limit_days"]." day"));
		}else{
			$addPoint = $logrow["point"];
			$limit    = ($logrow["limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$logrow["limit_days"]." day"));
		}
		$PPOINT = new PlayPoint($template->DB);
		$PPOINT->addPoint( $row["member_no"], "02", $addPoint, "", $limit, $template->getArrayValue( $GLOBALS["grantPointStatusList"], "02"), $row["member_no"] );
		$row["point"] += $addPoint;

		$addLoginDays = 1;	// 連続ログイン日数加算
	}
	
	
	// セッションインスタンス生成
	$template->Session = new SmartSession(URL_SSL_SITE . "", SESSION_SEC, SESSION_SID, DOMAIN, true);
	$template->Session->start();
	$template->Session->UserInfo = $row;
	
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	// ログイン成功時、最終ログインUAを更新
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("mst_member")
				->set()
					->value("login_dt"  , "current_timestamp", FD_FUNCTION )
					->value("login_ua"  , $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
					->value("login_days", "login_days + " . $addLoginDays, FD_FUNCTION)
				->where()
					->and("member_no = ",$row["member_no"],FD_NUM)
			->createSQL();
	$template->DB->query($sql);
	
	// ログイン履歴登録
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select2insert()
				->into("his_member_login")
					->tofield("member_no", "member_no")
					->tofield("login_dt", "login_dt")
					->tofield("login_ua", "login_ua")
				->from("mst_member")
				->where()
					->and("member_no = ",$row["member_no"],FD_NUM)
			->createSQL();
	$template->DB->query($sql);

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	if ( $_POST["NO"] != ""){
		header("Location: " . URL_SSL_SITE . "play/?NO=". $_POST["NO"]);
	} else {
		if( $_POST["TRANS"] != ""){
			header("Location: " . $_POST["TRANS"]);
		}else{
			// トップ画面表示
			header("Location: " . URL_SSL_SITE . "");
		}
	}
 2020/12/25 [DEL Start] ログイン処理に引越 */
}

/**
 * PINコード送信
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcSendPin($template) {
	// データ取得
	getData($_POST, array("MNO", "NO", "TRANS", "PIN"));

	// 会員情報取得
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no")
				->field("mobile, international_cd, mobile_checked_dt")
				->from("mst_member")
				->where()
					->and("member_no = ", $_POST["MNO"], FD_STR)
					->and("state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	if (empty($row["member_no"])) {
		DispLogin($template, $template->message("U0103"));
		return;
	}

	$_POST["PIN"] = "";
	// PIN送信
	$ret = SendPin($template, $row["member_no"], $row["mobile"], $row["international_cd"]);
	if ($ret == 0) {
		DispPin($template, $row["member_no"]);
	} else {
		DispSendError($template);
	}

}

/**
 * PINコード確認
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcCheckPin($template) {
	// データ取得
	getData($_POST, array("MNO", "NO", "TRANS", "PIN"));
	// 入力チェック
	$message = checkPin($template);
	if (mb_strlen($message) > 0) {
		DispPin($template, $_POST["MNO"], $message);
		return;
	}

	// トランザクション開始
	$template->DB->autoCommit(false);

	// 携帯番号認証日更新
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value("mobile_checked_dt", "current_timestamp", FD_FUNCTION)
				->value("upd_no"       , $_POST["MNO"], FD_NUM)
				->value("upd_dt"       , "current_timestamp", FD_FUNCTION)
			->where()
				->and(false, "member_no = ", $_POST["MNO"], FD_NUM)
				->and(false, "state = ", "1", FD_NUM)
		->createSQL();
	$template->DB->exec($sql);

	// 識別データ削除
	$delsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->delete()
			->from("dat_sms_identify")
			->where()
				->and( "member_no = ", $_POST["MNO"], FD_NUM)
				->and( "identify_kbn = ", "3", FD_NUM)
		->createSql();
	$template->DB->exec($delsql);

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	// ログイン
	ExecLogin($template, $_POST["MNO"]);

}

/**
 * PIN入力画面表示
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	int		$memberNo	会員No
 * @param	string	$message	再表示時のメッセージ
 * @return	なし
 */
function DispPin($template, $memberNo, $message = "") {
	getData($_POST, array("PIN", "NO", "TRANS"));

	// 2021/01/27 [ADD Start] 登録番号表示
	// 会員情報取得
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname")
				->field("mobile, international_cd")
				->from("mst_member")
				->where()
					->and("member_no = ", $memberNo, FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	$hidTel = "";
	$internationalCd = "+";
	if (!empty($row["member_no"])) {
		$mobile = $row["mobile"];
		$internationalCd = $row["international_cd"];
		// 携帯番号を伏字にする
		$len = 2;
		$hidTel = str_pad("", mb_strlen($mobile) - $len, "*") . substr($mobile, $len * -1);
	}
	// 2021/01/27 [ADD End] 登録番号表示


	// PIN入力画面表示
	$template->open(PRE_HTML . "_pin.html");
	$template->assignCommon();
	$template->assign("ERRMSG"    , $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);		// メッセージ表示制御
	$template->assign("SMS_PINCODE_LENGTH", SMS_PINCODE_LENGTH, true);

	$template->assign("PIN"   , $_POST["PIN"], false);
	$template->assign("NO"    , $_POST["NO"], false);
	$template->assign("TRANS" , $_POST["TRANS"], false);
	$template->assign("MNO"   , $memberNo, false);
	// 2021/01/27 [ADD Start] 登録番号表示
	$template->assign("HID_TEL"         , $hidTel, false);
	$template->assign("INTERNATIONAL_CD", $internationalCd, false);
	// 2021/01/27 [ADD End] 登録番号表示

	// 表示
	$template->flush();
}

/**
 * PIN送信エラー画面表示
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	なし
 */
function DispSendError($template) {
	getData($_POST, array("PIN", "NO", "TRANS"));

	// PIN入力画面表示
	$template->open(PRE_HTML . "_senderr.html");
	$template->assignCommon();

	// 表示
	$template->flush();
}

/**
 * PIN送信
 * @access	private
 * @param	object	$template			テンプレートクラスオブジェクト
 * @param	int		$memberNo			会員No
 * @param	string	$mobile				携帯番号
 * @param	string	$internationalCd	国際番号
 * @return	int		エラーレベル(0：無、1：送信エラー、2：送信例外、9：送信施行不可)
 */
function SendPin($template, $memberNo, $mobile, $internationalCd) {
	$errorLv = 0;	// 0：無、1：送信エラー、2：送信例外、9：送信施行不可

	// PIN発行
	$nowDT = new DateTime("now");
	$identifyKbn = 3;
	$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
			->field("*")
			->from("dat_sms_identify")
			->where()
				->and("member_no = "   , $memberNo, FD_NUM)
				->and("identify_kbn = ", $identifyKbn, FD_NUM)
				->and("limit_dt >= "   , $nowDT->format("Y-m-d H:i"), FD_DATE)
		->createSql();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

	if (!empty($row["member_no"])) {		// 有効期限内データ存在
		$pinCode = $row["pin"];
	} else {
		// PINコード新規発行
		$pinCode = makeRandPin(SMS_PINCODE_LENGTH);
		$limitDt = new DateTime("now");
		$limitDt->add(new DateInterval(SMS_PINCODE_LIMIT));
		// トランザクション開始
		$template->DB->autoCommit(false);
		// 登録
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->insert()
				->into( "dat_sms_identify" )
					->value( "member_no"   , $memberNo, FD_NUM)
					->value( "identify_kbn", $identifyKbn, FD_NUM)
					->value( "pin"         , $pinCode, FD_NUM)
					->value( "limit_dt"    , $limitDt->format("Y-m-d H:i"), FD_DATE)
					->value( "add_dt"      , "current_timestamp", FD_FUNCTION)
					->value( "upd_dt"      , "current_timestamp", FD_FUNCTION)
			->createSQL();
		$sql .= " on duplicate key update" . "\n"
			. " pin = VALUES(pin)" . "\n"
			. ", limit_dt = VALUES(limit_dt)" . "\n"
			. ", upd_dt = VALUES(upd_dt)" . "\n";
		$template->DB->exec($sql);
		// コミット(トランザクション終了)
		$template->DB->autoCommit(true);
	}

	// PIN送信
	if (mb_strlen($mobile) <= 0 || mb_strlen($internationalCd) <= 0) $errorLv = 9;
	if ($errorLv <= 0) {
		require_once(DIR_SYS . 'sms/send' . SMS_SERVICE_NAME . '.php');		// SMS送信関数
		try {
			$ret = sendSMS($internationalCd, $mobile, $pinCode);
			if (!$ret) $errorLv = 1;
		} catch (Exception $e) {
			syslog(LOG_ERR, $e->getMessage());		// エラー内容をsyslogへ出力
			$errorLv = 2;
		}
	}

	return $errorLv;

}

/**
 * ログイン処理
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	int		$memberNo	会員No
 * @return	なし
 */
function ExecLogin($template, $memberNo) {

	// 会員情報取得
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname, mail, pass, last_name, first_name, state, point, login_dt, draw_point")
				->from("mst_member")
				->where()
					->and("member_no = ", $memberNo, FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

	$addLoginDays = 0;		// 連続ログイン日数加算用
	$last = ((mb_strlen($row["login_dt"]) > 0) ? (int)GetRefTimeToday($row["login_dt"], "Ymd") : 0);	// 最終ログインの営業日
	$toDay = (int)GetRefTimeToday(date("Y/m/d H:i"), "Ymd");	// 基準時間の当日

	// ログボ獲得処理
	if($last < $toDay){		//獲得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("point, limit_days, special_point, special_limit_days, special_start_dt, special_end_dt")
					->from("mst_grantPoint")
					->where()
						->and("proc_cd = ", "02", FD_STR)
				->createSQL();
		$logrow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

		//期間限定チェック
		$s = (int)date('Ymd', strtotime( $logrow["special_start_dt"]));
		$e = (int)date('Ymd', strtotime( $logrow["special_end_dt"]));
		if( $s <= $toDay && $toDay <= $e){
			$addPoint = $logrow["special_point"];
			$limit    = ($logrow["special_limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$logrow["special_limit_days"]." day"));
		}else{
			$addPoint = $logrow["point"];
			$limit    = ($logrow["limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$logrow["limit_days"]." day"));
		}
		$PPOINT = new PlayPoint($template->DB);
		$PPOINT->addPoint( $row["member_no"], "02", $addPoint, "", $limit, $template->getArrayValue( $GLOBALS["grantPointStatusList"], "02"), $row["member_no"] );
		$row["point"] += $addPoint;

		$addLoginDays = 1;	// 連続ログイン日数加算
	}
	
	
	// セッションインスタンス生成
	$template->Session = new SmartSession(URL_SSL_SITE . "", SESSION_SEC, SESSION_SID, DOMAIN, true);
	$template->Session->start();
	$template->Session->UserInfo = $row;
	
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	// ログイン成功時、最終ログインUAを更新
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("mst_member")
				->set()
					->value("login_dt"  , "current_timestamp", FD_FUNCTION )
					->value("login_ua"  , $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
					->value("login_days", "login_days + " . $addLoginDays, FD_FUNCTION)
				->where()
					->and("member_no = ",$row["member_no"],FD_NUM)
			->createSQL();
	$template->DB->query($sql);
	
	// ログイン履歴登録
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select2insert()
				->into("his_member_login")
					->tofield("member_no", "member_no")
					->tofield("login_dt", "login_dt")
					->tofield("login_ua", "login_ua")
				->from("mst_member")
				->where()
					->and("member_no = ",$row["member_no"],FD_NUM)
			->createSQL();
	$template->DB->query($sql);

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	if ( $_POST["NO"] != ""){
		header("Location: " . URL_SSL_SITE . "play/?NO=". $_POST["NO"]);
	} else {
		if( $_POST["TRANS"] != ""){
			header("Location: " . $_POST["TRANS"]);
		}else{
			// トップ画面表示
			header("Location: " . URL_SSL_SITE . "");
		}
	}

}

/**
 * PINコード入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkPin($template) {
	$errMessage = array();

	// SMS識別データ
	$sqls = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(member_no)")
		->from("dat_sms_identify")
		->where()
			->and("member_no = "   , $_POST["MNO"], FD_NUM)
			->and("identify_kbn = ", "3", FD_NUM);
	$sqlSms = $sqls->createSql("\n");

	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["PIN"])	//-- PIN
			->required("U0104")		// 必須
			->number("U0105")		// 半角数字
			->length("U0106", SMS_PINCODE_LENGTH)	// 桁数
			->noCountSQL("U0107", $sqlSms)	// 存在
	->report();

	// PINコードチェック
	if (count($errMessage) <= 0) {
		$sql = $sqls
				->resetField()
				->field("pin, limit_dt")
				->createSql("\n");
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		if ($row["pin"] != $_POST["PIN"]) {		// PIN不一致
			$errMessage[] = $template->message("U0107");
		} else {
			// 有効期限チェック
			$nowDT = new DateTime("now");
			$limitDt = new DateTime( $row["limit_dt"]);
			if( $limitDt < $nowDT) $errMessage[] = $template->message("U0108");
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

/**
 * ランダム文字列生成 (数字)
 * @access	public
 * @param	int		$length		生成する文字数
 * @return	string	生成した文字列
 */
function makeRandPin($length = 6) {
	static $chars = '0123456789';
	static $chars2 = '123456789';
	$str = '';
	for ($i = 0; $i < $length; ++$i) {
		if( $i==0){
			$str .= $chars2[mt_rand(0, 8)];
		}else{
			$str .= $chars[mt_rand(0, 9)];
		}
	}
	return $str;
}

?>
