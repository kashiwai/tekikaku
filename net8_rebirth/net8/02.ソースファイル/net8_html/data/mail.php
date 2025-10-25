<?php
/*
 * mail.php
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
 * メールアドレス変更画面表示
 * 
 * メールアドレス変更を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2020/05/01 初版作成 片岡 充
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
		// データ取得
		getData($_GET, array("M"));
		getData($_POST, array("M"));
		
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		
		// 実処理
		switch ($_POST["M"]) {
			case "proc":									// 再設定要求処理
				$template->checkSessionUser(true, true);
				ProcReMail($template);
				break;
			default:
				switch ($_GET["M"]) {
					case "regist":							// 再設定処理
						DispChangeRegist($template);
						break;
					case "change":							// 再設定処理
						DispChange($template);
						break;
					case "comp":							// 再設定完了画面
						DispComp($template);
						break;
					case "send":							// 再設定要求完了画面
						$template->checkSessionUser(true, true);
						DispSend($template);
						break;
					default:								// 再設定要求画面
						$template->checkSessionUser(true, true);
						DispChangeRequest($template);
				}
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * メールアドレス変更要求入力画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispChangeRequest($template, $message = "") {
	// データ取得
	getData($_POST, array("MAIL", "MAILCONF"));
	
	// メールエラーカウント取得
	$errcountrow = getErrMailCount( $template, $template->Session->UserInfo["member_no"]);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("MAIL_LIMIT"      , MAIL_LIMIT, true);
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("OVERMAIL", $errcountrow >= MAGAZINE_MAIL_ERR_COUNT);
	$template->assign("NMAIL"      , $template->Session->UserInfo["mail"], true);
	$template->assign("MAIL"      , $_POST["MAIL"], true);
	$template->assign("MAILCONF"  , $_POST["MAILCONF"], true);
	// 表示
	$template->flush();
}

/**
 * メールアドレス変更要求完了画面
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
 * メールアドレス変更完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComp($template) {
	
	// 画面表示開始
	$template->open(PRE_HTML . "_change_end.html");
	$template->assignCommon();
	// 表示
	$template->flush();	
	
}

/**
 * 再設定要求
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcReMail($template) {
	// データ取得
	getData($_POST, array("MAIL", "MAILCONF"));
	
	// 入力チェック
	$message = checkInput($template);
	//エラー
	if (mb_strlen($message) != 0 ){
		DispChangeRequest($template, $message);
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
				->value( "identify_kbn"    , 1, FD_NUM)
				->value( "member_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "limit_dt"        , $limitDt, FD_DATE)
				->value( "incidental_info" , $_POST["MAIL"], FD_STR)
				->value( "add_ua"          , $_SERVER["HTTP_USER_AGENT"] . " [" . $_SERVER["REMOTE_ADDR"] . "]", FD_STR )
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
	$subject = CHANGE_MAIL_SUBJECT;
	$registURL = URL_SSL_SITE . $template->Self . "?M=change&RID=" . $registId;
	$search = array("%MAIL%", "%REGISTURL%", "%LIMITSPAN%", "%LIMITDATE%");
	$replace = array($_POST["MAIL"], $registURL, CHANGE_MEMBER_LIMIT, format_datetime($limitDt));
	$body = str_replace($search, $replace, CHANGE_MAIL_BODY);
	// メール送信
	$smartMailSend->setMailSendData(MAIL_FROM, $_POST["MAIL"], "", "", MAIL_ERROR);
	$smartMailSend->make($subject, $body);
	$smartMailSend->send();
	//---↑	
	
	header("Location: " . URL_SSL_SITE . "mail.php?M=send");
}


/**
 * メールアドレス再設定（パスワード入力画面）
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispChange($template, $message = "") {
	// データ取得
	getData($_GET, array("RID"));
	
	// チェック
	if (mb_strlen($_GET["RID"]) == 0) {
		DispChangeError( $template, $template->message("U0003"));
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
					->and( "dmi.identify_kbn = ", "1", FD_NUM)
					->and( "mm.state = ", "1", FD_NUM)
					->and( "mm.black_flg = ", "0", FD_NUM)
			->createSql();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		
		if (!empty($row["member_no"])) {
			// 有効期限チェック
			$now = new DateTime("now");
			$limit = new DateTime($row["limit_dt"]);
			if ($now > $limit){
				DispChangeError( $template, $template->message("U0603"));
				return;
			}
		} else {
			DispChangeError( $template, $template->message("U0003"));
			return;
		}
		
		//ブッキングチェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field( "count(member_no)" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["incidental_info"], FD_STR)
					->and( "state = ", "1", FD_NUM)
		->createSql();
		$bookingcheck = $template->DB->getOne($sql);
		if( $bookingcheck > 0){
			DispChangeError( $template, $template->message("U0606"));
			return;
		}
		
		//ブラックチェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field( "count(member_no)" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["incidental_info"], FD_STR)
					->and( "black_flg = ", "1", FD_NUM)
		->createSql();
		$blackcheck = $template->DB->getOne($sql);
		if( $blackcheck > 0){
			DispChangeError( $template, $template->message("U0607"));
			return;
		}
		
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_change_regist.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->assign("MEMBER_PASS_MIN"       , MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_MAX"       , MEMBER_PASS_MAX, true);
	$template->assign("RID"   , $_GET["RID"], true);
	
	// 表示
	$template->flush();
	
}

/**
 * メールアドレス再設定
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のメッセージ
 * @return	なし
 */
function DispChangeRegist($template, $message = "") {
	// データ取得
	getData($_GET,  array("RID"));
	getData($_POST, array("PASS"));
	
	// チェック
	if (mb_strlen($_GET["RID"]) == 0) {
		DispChangeError( $template, $template->message("U0003"));
		return;
	} else {
		//key存在チェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dmi.member_no, dmi.incidental_info, dmi.limit_dt" )
				->field("mm.nickname, mm.mail, mm.pass, mm.last_name, mm.first_name, mm.state, mm.point, mm.login_dt, mm.draw_point")
				->from( "dat_mail_identify dmi" )
				->from( "inner join mst_member mm on dmi.member_no = mm.member_no")
				->where()
					->and( "dmi.identify_key = ", $_GET["RID"], FD_STR)
					->and( "dmi.identify_kbn = ", "1", FD_NUM)
					->and( "mm.state = ", "1", FD_NUM)
					->and( "mm.black_flg = ", "0", FD_NUM)
			->createSql();
		$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		
		if (!empty($row["member_no"])) {
			// 有効期限チェック
			$now = new DateTime("now");
			$limit = new DateTime($row["limit_dt"]);
			if ($now > $limit){
				DispChangeError( $template, $template->message("U0603"));
				return;
			}
		} else {
			DispChangeError( $template, $template->message("U0003"));
			return;
		}
		
		//ブッキングチェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field( "count(member_no)" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["incidental_info"], FD_STR)
					->and( "state = ", "1", FD_NUM)
		->createSql();
		$bookingcheck = $template->DB->getOne($sql);
		if( $bookingcheck > 0){
			DispChangeError( $template, $template->message("U0606"));
			return;
		}
		
		//ブラックチェック
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field( "count(member_no)" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["incidental_info"], FD_STR)
					->and( "black_flg = ", "1", FD_NUM)
		->createSql();
		$blackcheck = $template->DB->getOne($sql);
		if( $blackcheck > 0){
			DispChangeError( $template, $template->message("U0607"));
			return;
		}
		
	}
	
	// パスワードチェック
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["PASS"])
			->password_verify("U0611", $row["pass"] )
		->report(false);
	//エラーがある場合は入力画面に戻す
	if (mb_strlen($errMessage) != 0 ){
		DispChange($template, $errMessage);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value( "mail"            , $row["incidental_info"], FD_STR)
				->value( "mail_error_count", "0", FD_NUM)
				->value( "mail_error_dt"   , null, FD_FUNCTION)
				->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"          , $row["member_no"], FD_NUM)
			->where()
				->and("member_no = ", $row["member_no"], FD_STR)
				->and("state = "    , "1", FD_NUM)
				->and("black_flg = ", "0", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->delete()
			->from("dat_mail_identify")
			->where()
				->and( "identify_key = ", $_GET["RID"], FD_STR)
				->and( "identify_kbn = ", "1", FD_NUM)
		->createSQL("\n");
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);

	if (isset($template->Session->UserInfo)) {		// ユーザセッション存在時
		// $row整形
		$newrow = array();
		$newrow['member_no']  = $row['member_no'];
		$newrow['nickname']   = $row['nickname'];
		$newrow['mail']       = $row['incidental_info'];
		$newrow['pass']       = $row['pass'];
		$newrow['last_name']  = $row['last_name'];
		$newrow['first_name'] = $row['first_name'];
		$newrow['state']      = $row['state'];
		$newrow['point']      = $row['point'];
		$newrow['login_dt']   = $row['login_dt'];
		$newrow['draw_point'] = $row['draw_point'];
		$template->Session->UserInfo = $newrow;
	}
	
	header("Location: " . URL_SSL_SITE . "mail.php?M=comp");
}


/**
 * メールアドレス再設定エラー表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラーメッセージ
 * @return	なし
 */
function DispChangeError($template, $message = "") {
	// 画面表示開始
	$template->open(PRE_HTML . "_error.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message, true);
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
	
	//メールアドレスブッキングチェック用SQL文
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mail = ", $_POST["MAIL"], FD_STR)
			->and( "state = ", "1", FD_NUM)
	->createSql();
	
	//ブラックメールアドレスチェック用SQL文
	$blacksql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mail = ", $_POST["MAIL"], FD_STR)
			->and( "black_flg = ", "1", FD_NUM)
	->createSql();
	
	$errMessage = array();
	//各種チェック
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["MAIL"])
			->required("U0608")
			->maxLength("U0610", MAIL_LIMIT)
			->mail("U0604")
		->item($_POST["MAILCONF"])
			->required("U0609")
			->eq("U0605", $_POST["MAIL"])
		->break()
		->item($_POST["MAIL"])
			->countSQL("U0606", $sql)
		->item($_POST["MAIL"])
			->countSQL("U0607", $blacksql)	//入力内容に不備があるため登録できません。 
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * メールの送信エラー回数を取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$mail			対象会員ナンバー
 * @return	int						エラー回数
 */
function getErrMailCount( $template, $mno){
	// メールエラーカウント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mail_error_count" )
			->from( "mst_member" )
			->where()
				->and( "member_no = ", $mno, FD_NUM)
				->and( "state = ", "1", FD_NUM)
	->createSql();
	$errcountrow = $template->DB->getOne($sql);
	return $errcountrow;
}

?>
