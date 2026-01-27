<?php
/*
 * regist.php
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
 * 新規会員登録画面表示
 * 
 * 新規会員登録画面の表示/登録を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/08 初版作成 片岡 充
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
		getData($_GET, array("M"));
		
		switch ($_GET["M"]) {
			case "regist":				// 新規登録処理
				ProcData($template);
				break;
			case "send":				// 仮登録メール送信完了画面
				DispMailSend($template);
				break;
			case "mail_conf":			// メアド、電話番号確認完了画面表示(メール内URLからの遷移)
				DispMailConf($template);
				break;
			case "comp":				// 完了画面
				ProcComp($template);
				break;
			case "auth":				// 携帯番号認証
				ProcAuth($template);
				break;
			default:					// 入力画面
				DispInput($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 新規登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ProcData($template, $message = "") {
	
	// データ取得
	getData($_POST, array("NICKNAME", "MAIL", "MAILCONF", "PASS"
						, "CODE", "MAGAZINE", "CHECK1", "CHECK2"
						, "BENEFITS_CD"
						));
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispInput($template, $message);
		return;
	}
	
	// 招待コード取得
	$invcoder = "";
	if (mb_strlen($_POST["CODE"]) > 0) {
		$getNoInvitedBy = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "member_no" )
				->from( "mst_member mm" )
				->where()
					->and( "mm.invite_cd = ", $_POST["CODE"], FD_STR)
					->and( "mm.state = ", "1", FD_NUM)
				->limit(1)
			->createSql();
		$invcoder = $template->DB->getOne($getNoInvitedBy);
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//	新規登録処理
	$mode = "new";
	$pass = $_POST["PASS"];
	$passHash = password_hash($pass, PASSWORD_DEFAULT);
	
	$registId = substr( sha1(uniqid(mt_rand(),true)), 0, 20);
	$registLimitDt = new DateTime("now");
	$registLimitDt->add(new DateInterval("P" . REGIST_LIMIT . "D"));
	$limitDt = $registLimitDt->format("Y-m-d H:i:s");
	
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->insert()
			->into( "mst_member" )
				->value( "mail"            , $_POST["MAIL"], FD_STR)
				->value( "pass"            , $passHash, FD_STR)
				->value( "nickname"        , $_POST["NICKNAME"], FD_STR)
				->value( "mail_magazine"   , $_POST["MAGAZINE"], FD_NUM)
				->value( "point"           , "0" , FD_NUM)
				->value( "draw_point"      , "0" , FD_NUM)
				->value(SQL_CUT, "invite_member_no", $invcoder, FD_NUM)
				->value(SQL_CUT, "benefits_cd"     , $_POST["BENEFITS_CD"], FD_STR)
				->value( "regist_id"       , $registId, FD_STR)
				->value( "regist_dt"       , $limitDt, FD_DATE)
				->value( "temp_dt"         , "current_timestamp", FD_FUNCTION)
				->value( "state"           , "0", FD_NUM)
				->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
				->value( "add_no"          , "0", FD_NUM)
				->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"          , "0", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	
	// メール送信処理
	//---↓ 手続き用メール送信
	require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
	// メール送信インスタンス生成
	$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
	
	// 内容置換
	$subject = MEMBER_REGIST_SUBJECT;
	$registURL = URL_SSL_SITE . $template->Self . "?M=mail_conf&RID=" . $registId;
	// %MAIL%  ⇒ メールアドレス                /  %REGISTURL% ⇒ 本登録用URL
	// %LIMITSPAN% ⇒ 仮登録メール有効期間(日)  /  %LIMITDATE% ⇒ 仮登録メール有効日時
	$search = array("%MAIL%", "%REGISTURL%", "%LIMITSPAN%", "%LIMITDATE%");
	$replace = array($_POST["MAIL"], $registURL, REGIST_LIMIT, format_datetime($limitDt));
	$body = str_replace($search, $replace, MEMBER_REGIST_BODY);
	
	// メール送信
	$smartMailSend->setMailSendData(MAIL_FROM, $_POST["MAIL"], "", "", MAIL_ERROR);
	$smartMailSend->make($subject, $body);
	$smartMailSend->send();
	//---↑	
	
	header("Location: " . URL_SSL_SITE . "regist.php?M=send");
}

/**
 * 仮登録メール送信完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispMailSend($template) {
	// 画面表示開始
	$template->open(PRE_HTML . "_temp.html");
	$template->assignCommon();
	
	// 表示
	$template->flush();
}


/**
 * 入力画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispInput($template, $message = "") {
	// データ取得
	getData($_POST, array("NICKNAME", "MAIL", "MAILCONF", "PASS"
						, "CODE", "MAGAZINE", "CHECK1", "CHECK2"
						, "BENEFITS_CD"
						));
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	$template->assign("INVITE_CODE_LENGTH"    , INVITE_CODE_LENGTH, true);
	$template->assign("MEMBER_PASS_MIN"       , MEMBER_PASS_MIN, true);
	$template->assign("MEMBER_PASS_MAX"       , MEMBER_PASS_MAX, true);
	$template->assign("NICKNAME_LIMIT"        , NICKNAME_LIMIT, true);
	$template->assign("MAIL_LIMIT"            , MAIL_LIMIT, true);
	$template->assign("MEMBER_PASS_PATTERN"   , MEMBER_PASS_PATTERN, true);
	
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	
	$template->assign("NICKNAME"    , $_POST["NICKNAME"], true);
	$template->assign("MAIL"        , $_POST["MAIL"], true);
	$template->assign("MAILCONF"    , $_POST["MAILCONF"], true);
	$template->assign("PASS"        , $_POST["PASS"], true);
	$template->assign("CODE"        , $_POST["CODE"], true);
	$template->assign("RDO_MAGAZINE", makeRadioArray($GLOBALS["MagazineReadStatus"], "MAGAZINE", $_POST["MAGAZINE"]));
	$template->assign("CHK_CHECK1"  , ($_POST["CHECK1"]=="1")? 'checked=""':"", true);
	$template->assign("CHK_CHECK2"  , ($_POST["CHECK2"]=="1")? 'checked=""':"", true);
	
	$template->assign("BENEFITS_CD"         , $_POST["BENEFITS_CD"], true);
	$template->assign("BENEFITS_CODE_LENGTH", BENEFITS_CODE_LENGTH, true);
	$template->assign("BENEFITS_CODE_STR"   , BENEFITS_CODE_STR, true);
	$template->if_enable("MEMBER_BENEFITS"  , MEMBER_BENEFITS);
	
	// 表示
	$template->flush();
}

/**
 * メアド、電話番号確認完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispMailConf($template) {
	// データ取得
	getData($_GET , array("RID"));
	// チェック
	$message = "";
	if (mb_strlen($_GET["RID"]) == 0) {
		$message = $template->message("U0003");
	} else {
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "member_no, mail, state, regist_dt, invite_member_no, black_flg" )
			->from( "mst_member" )
			->where()
				->and( "regist_id = ", $_GET["RID"], FD_STR)
			->orderby( "member_no desc" )
		->createSql();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		
		if( !empty( $row)){
			
			//取得したメアドをチェック
			$check_sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "member_no, state, black_flg" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["mail"], FD_STR)
					->and( "state = ", 1, FD_NUM)
			->createSql();
			$check_row = $template->DB->getAll($check_sql);
			if( !empty( $check_row)){
				$message = $template->message("U0412");
			}
			//取得したメアドのブラック会員チェック
			$check_sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "member_no, state, black_flg" )
				->from( "mst_member" )
				->where()
					->and( "mail = ", $row["mail"], FD_STR)
					->and( "black_flg = ", 1, FD_NUM)
			->createSql();
			$check_row = $template->DB->getAll($check_sql);
			if( !empty( $check_row)){
				$message = $template->message("U0411");
			}
			
			if (mb_strlen($row["member_no"]) > 0) {
				if ((int)$row["state"] == 1) {
					// 登録済
					$message = $template->message("U0412");
				} else {
					if ((int)$row["black_flg"] == 1) {
						// ブラック会員
						$message = $template->message("U0411");
					}else{
						// 有効期限チェック
						$now = new DateTime("now");
						$limit = new DateTime($row["regist_dt"]);
						if ($now > $limit) $message = $template->message("U0411");
					}
				}
			} else {
				$message = $template->message("U0003");
			}
		}else{
			$message = $template->message("U0411");
		}
	}
	// チェックエラー存在時
	if( mb_strlen($message) > 0){
		ProcComp($template, $message);
		return;
	}

	if (!AUTH_MEMBER_MOBILE) {		// 携帯番号認証不要時はそのまま本登録
		MemberRegist($template, $_GET["RID"]);
		return;
	}

	$mail = (isset($row["mail"]) ? $row["mail"] : "");
	DispMobileConf($template, $_GET["RID"], $mail, DEFAULT_INTERNATIONAL_CODE);

}

/**
 * 携帯番号確認画面表示
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	string	$rid		識別ID
 * @param	string	$mail		メールアドレス
 * @param	string	$icd		国際番号
 * @param	string	$mobile		携帯番号
 * @param	string	$pin		PINコード
 * @param	string	$message	エラー時メッセージ
 * @return	なし
 */
function DispMobileConf($template, $rid, $mail, $icd, $mobile = "", $pin = "", $message = "") {
	// 画面表示開始
	$template->open(PRE_HTML . "_mobile_conf.html");
	$template->assignCommon();

	$template->assign("ERRMSG", $message);
	$template->assign("RID"   , $rid   , true);
	$template->assign("MAIL"  , $mail  , true);
	$template->assign("PIN"   , $pin    , true);
	$template->assign("SEL_INTERNATIONAL_CD", makeOptionArray($GLOBALS["usableInternationalCode"], $icd, false));
	$template->assign("MOBILE", $mobile, true);
	$template->assign("SMS_PINCODE_LENGTH", SMS_PINCODE_LENGTH, true);

	// 表示
	$template->flush();
}

/**
 * 携帯番号認証
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ProcAuth($template, $message = "") {
	// データ取得
	getData($_POST , array("MOBILE", "PIN", "RID", "MAIL", "INTERNATIONAL_CD"));

	// 入力チェック(重症)
	$message = checkAuthCritical($template);
	if (mb_strlen($message) > 0) {
		ProcComp($template, $message);
		return;
	}

	// 入力チェック(軽症)
	$message = checkAuth($template);
	if (mb_strlen($message) > 0) {
		DispMobileConf($template, $_POST["RID"], $_POST["MAIL"], $_POST["INTERNATIONAL_CD"], $_POST["MOBILE"], $_POST["PIN"], $message);
		return;
	}

	// 本登録
	MemberRegist($template, $_POST["RID"], $_POST["INTERNATIONAL_CD"], $_POST["MOBILE"]);


}

/**
 * 本登録完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function ProcComp($template, $message = "") {
	// データ取得
	getData($_GET , array("mail", "bc"));

	// 画面表示開始
	$template->open(PRE_HTML . "_mail_conf.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->assign("MAIL"  , $_GET["mail"], true);
	$template->if_enable("ERROR", mb_strlen($message) > 0);
	$template->if_enable("NO_ERROR", mb_strlen($message) == 0);
	// 特典NG
	$template->if_enable("NO_BENEFITS"    , mb_strlen($_GET["bc"]) > 0);
	$template->if_enable("MEMBER_BENEFITS", MEMBER_BENEFITS);

	// 表示
	$template->flush();
}

/**
 * 本登録
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	int		$registId	識別ID
 * @param	string	$internalCd	国際番号
 * @param	string	$mobile		携帯番号
 * @return	なし
 */
function MemberRegist($template, $registId, $internalCd = "", $mobile = "") {

	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("member_no, mail, invite_member_no")
		->field("benefits_cd")
		->from("mst_member")
		->where()
			->and("regist_id = ", $registId, FD_STR)
			->and("state = "           , 0, FD_NUM)
		->orderby("member_no desc")
	->createSql("\n");
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	if (mb_strlen($row["member_no"]) <= 0) {
		ProcComp($template, $template->message("U0432"));
		return;
	}

	// 2020/12/25 [ADD Start]
	$checkedDt = "";
	if (AUTH_MEMBER_MOBILE) {		// 携帯番号認証時
		$checkedDt = "current_timestamp";
	}
	// 2020/12/25 [ADD End]

	// トランザクション開始
	$template->DB->autoCommit(false);
	
	// 招待コード発行
	$_codeBooking = false;
	$invCode = $template->makeRandStr(12);
	while(true){
		// 招待コードブッキングチェック
		$hasInviteCode = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_member mm" )
			->where()
				->and( "mm.invite_cd = ", $invCode, FD_STR)
				->and( "mm.state = ", "1", FD_NUM)
			->createSql();
		$codeCount = $template->DB->getOne( $hasInviteCode);
		if( $codeCount > 0){
			$_codeBooking = true;
			$invCode = $template->makeRandStr(12);
		}else{
			$_codeBooking = false;
		}
		if( !$_codeBooking) break;
	}
	
	//本登録
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "mst_member" )
			->set()
				->value("state"          , 1, FD_NUM)
				->value("join_dt"        , "current_timestamp", FD_FUNCTION)
				->value("regist_id"      , "", FD_STR)
				->value("regist_dt"      , "current_timestamp", FD_FUNCTION)
				->value("invite_cd"      , $invCode, FD_STR)
				->value(SQL_CUT, "mobile", $mobile, FD_STR)
				->value(SQL_CUT, "international_cd", $internalCd, FD_STR)
				->value(SQL_CUT, "mobile_checked_dt", $checkedDt, FD_FUNCTION)	// 2020/12/25 [ADD]
			->where()
				->and("member_no =", $row["member_no"], FD_NUM)
				->and("state = "   , 0, FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	

	if (AUTH_MEMBER_MOBILE) {		// 携帯番号認証時は識別データ削除
		// 識別データ削除
		$delsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->delete()
				->from("dat_sms_identify")
				->where()
					->and( "member_no = ", $row["member_no"], FD_NUM)
					->and( "identify_kbn = ", "1", FD_NUM)
			->createSql();
		$template->DB->exec($delsql);
	}

	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	$mno = $row["member_no"];
	$ino = $row["invite_member_no"];
	$inv = 0;
	if( mb_strlen($ino) > 0){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("count(*)")
					->from("mst_member")
					->where()
						->and("member_no = ", $ino, FD_NUM)
						->and("state = ", 1, FD_NUM)
				->createSQL();
		$inv = $template->DB->getOne($sql, PDO::FETCH_ASSOC);
	}
	
	// 会員登録ボーナス
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("proc_cd, point, limit_days, special_point, special_limit_days, special_start_dt, special_end_dt")
				->from("mst_grantPoint")
				->where()
					->and("proc_cd = ", "01", FD_STR)
					->or("proc_cd = ",  "03", FD_STR)
					->or("proc_cd = ",  "04", FD_STR)
			->createSQL();
	$rs = $template->DB->query($sql);
	$PPOINT = new PlayPoint($template->DB, false);
	$key_no = "";
	while ($invrow = $rs->fetch(PDO::FETCH_ASSOC)) {
		$key_no = "";
		$_update = true;
		//期間限定チェック
		$toDay = (int)GetRefTimeToday(date("Y/m/d H:i"), "Ymd");	// 基準時間の当日
		$s = (int)date('Ymd', strtotime( $invrow["special_start_dt"]));
		$e = (int)date('Ymd', strtotime( $invrow["special_end_dt"]));
		if( $s <= $toDay && $toDay <= $e){
			$addPoint = $invrow["special_point"];
			$limit    = ($invrow["special_limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$invrow["special_limit_days"]." day"));
		}else{
			$addPoint = $invrow["point"];
			$limit    = ($invrow["limit_days"]==0)? "":date('Y-m-d H:i', strtotime( "+".$invrow["limit_days"]." day"));
		}

		// 付与判定
		if( $invrow["proc_cd"] == "03"){
			if( mb_strlen($ino) > 0 && $addPoint > 0){
				$target = $ino;
				$key_no = $mno;
				if( $inv == 0 ) $_update = false;
				//招待コード対象者に通知
				$contact_message = array();
				$search  = array( "%POINT%", "%LABEL_1%");
				foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
					$replace = array( $addPoint, $GLOBALS["unitLangList"][$k]["1"]);
					$contact_message[$k] = str_replace( $search, $replace, $v["04"]);
				}
				$contact = new ContactBox( $template->DB, false);
				$contact->addOneRecord( $ino, "04", $mno, $contact_message);
				
			}else{
				$_update = false;
			}
		}else if( $invrow["proc_cd"] == "04"){
			if( mb_strlen($ino) > 0){
				$target = $mno;
				$key_no = $ino;
				if( $inv == 0 ) $_update = false;
			}else{
				$_update = false;
			}
		}else{
			$target = $mno;
		}
		// ポイント付与
		if( $_update && $addPoint > 0){
			$PPOINT->addPoint( $target, $invrow["proc_cd"], $addPoint, $key_no, $limit, $template->getArrayValue( $GLOBALS["grantPointStatusList"], $invrow["proc_cd"]), $mno);
		}
	}
	
	$bc = "";
	if (MEMBER_BENEFITS && mb_strlen($row["benefits_cd"]) > 0) {		// 会員特典対応 且 特典コード指定時
		//特典情報取得
		$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
			->field("hed.benefits_no, hed.point, hed.limit_days")
			->field("dtl.benefits_cd")
			->from("dat_benefits hed")
			->join("inner", "dat_benefitsDetail dtl", "hed.benefits_no = dtl.benefits_no")
			->where()
				->and("dtl.benefits_cd = ", $row["benefits_cd"], FD_STR)
				->and("hed.del_flg = "    , "0", FD_NUM)
				->and("hed.stop_flg = "   , "0", FD_NUM)
				->and("hed.end_dt > "     , "current_timestamp", FD_FUNCTION)
				->and("dtl.member_no ", "IS NULL", FD_FUNCTION)
			->createSql("\n");
		$bRow = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$addPoint = -1;
		if (!empty($bRow["benefits_no"]) && $bRow["benefits_cd"] == $row["benefits_cd"]) {
			// 特典ポイント潰し込み
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_benefitsDetail" )
					->set()
						->value("member_no", $row["member_no"], FD_NUM)
						->value("use_dt"   , "current_timestamp", FD_FUNCTION)
					->where()
						->and("benefits_no = ", $bRow["benefits_no"], FD_NUM)
						->and("benefits_cd = ", $row["benefits_cd"], FD_STR)
				->createSQL();
			$template->DB->exec($sql);
			// 特典ポイント付与
			$addPoint = $bRow["point"];
			$limit    = ($bRow["limit_days"] == 0) ? "" : date('Y-m-d H:i', strtotime("+" . $bRow["limit_days"] . " day"));
			if ($addPoint > 0) {
				$PPOINT->addPoint($row["member_no"], "07", $addPoint, "", $limit, $template->getArrayValue($GLOBALS["pointHistoryProcessCode"], "07"), $row["member_no"]);
			}
			// 連絡Box登録
			$contact_message = array();
			$search  = array( "%POINT%", "%LABEL_1%");
			foreach($GLOBALS["contactBoxLang"] as $k=>$v){
				$replace = array($addPoint, $GLOBALS["unitLangList"][$k]["1"]);
				$contact_message[$k] = str_replace( $search, $replace, $v["10"]);
			}
			$contact = new ContactBox($template->DB, false);
			$contact->addOneRecord($row["member_no"], "10", $bRow["benefits_no"], $contact_message);
		}
		// 特典NG
		if ($addPoint < 0) $bc = "d";
	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// メール送信処理
	//---↓ 手続き用メール送信
	require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
	// メール送信インスタンス生成
	$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
	// 内容置換
	$subject = MEMBER_REGIST_COMPLETE_SUBJECT;
	// %MAIL%  ⇒ メールアドレス
	$search = array("%MAIL%");
	$replace = array($row["mail"]);
	$body = str_replace($search, $replace, MEMBER_REGIST_COMPLETE_BODY);
	// メール送信
	$smartMailSend->setMailSendData(MAIL_FROM, $row["mail"], "", "", MAIL_ERROR);
	$smartMailSend->make($subject, $body);
	$smartMailSend->send();
	//---↑

	header("Location: " . URL_SSL_SITE . "regist.php?M=comp&bc=" . $bc . "&mail=". $row["mail"]);

}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
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
	
	//ニックネームブッキング
	$nicksql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member mm" )
		->where()
			->and( "nickname = ", $_POST["NICKNAME"], FD_STR)
			->groupStart()
				->or( "mm.state = ", "0", FD_NUM)
				->or( "mm.state = ", "1", FD_NUM)
			->groupEnd()
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
	
	//特典コードチェック用SQL文
	$bSql = (new SqlString())
		->setAutoConvert([$template->DB,"conv_sql"])
		->select()
		->field("count(*)")
		->from("dat_benefits hed")
		->join("inner", "dat_benefitsDetail dtl", "hed.benefits_no = dtl.benefits_no")
		->where()
			->and("dtl.benefits_cd = ", $_POST["BENEFITS_CD"], FD_STR)
			->and("hed.del_flg = "    , "0", FD_NUM)
			->and("hed.stop_flg = "   , "0", FD_NUM)
			->and("hed.end_dt > "     , "current_timestamp", FD_FUNCTION)
			->and("dtl.member_no ", "IS NULL", FD_FUNCTION);
	$benefitsSql = $bSql->createSql("\n");
	
	//各種チェック
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["NICKNAME"])
			->required("U0461")
			->maxLength("U0448", NICKNAME_LIMIT)
		->item($_POST["MAIL"])
			->required("U0402")
			->maxLength("U0449", MAIL_LIMIT)
			->mail("U0404")
		->item($_POST["MAILCONF"])
			->required("U0403")
			->eq("U0405", $_POST["MAIL"])
		//パスワード
		->item($_POST["PASS"])
			->required("U0427")
			->minLength("U0447", MEMBER_PASS_MIN)	//文字長の最低値
			->maxLength("U0430", MEMBER_PASS_MAX)	//文字長の最高値
			->alnum("U0429")						//英数字
			->if("U0470", (preg_match("/" . MEMBER_PASS_PATTERN . "/", $_POST["PASS"])))		// 入力制約
		->item($_POST["CODE"])
			->any()
				->alnum("U0462")
				->minLength("U0462", INVITE_CODE_LENGTH)
				->maxLength("U0462", INVITE_CODE_LENGTH)
		->item($_POST["BENEFITS_CD"])	// 特典コード
			->any()
				->noCountSQL("U0408", $benefitsSql)
		->item($_POST["CHECK1"])
			->case( $GLOBALS["footerviews"]["TERMS"] == 1 )
				->required("U0401")
		->item($_POST["CHECK2"])
			->case( $GLOBALS["footerviews"]["POLICY"] == 1 )
				->required("U0491")
		->break()
		->item($_POST["MAIL"])		->countSQL("U0406", $sql)
		->item($_POST["NICKNAME"])	->countSQL("U0463", $nicksql)
		->item($_POST["MAIL"])		->countSQL("U0481", $blacksql)
	->report();
	
	// 招待コードチェック
	if (mb_strlen($_POST["CODE"]) > 0) {
		$getNoInvitedBy = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "member_no" )
				->from( "mst_member mm" )
				->where()
					->and( "mm.invite_cd = ", $_POST["CODE"], FD_STR)
					->and( "mm.state = ", "1", FD_NUM)
				->limit(1)
			->createSql();
		$invcoder = $template->DB->getOne($getNoInvitedBy);
		if( $invcoder == NULL ){
			array_push( $errMessage, $template->message("U0464"));
		}
	}
	// 特典コードチェック
	if (count($errMessage) <= 0 && mb_strlen($_POST["BENEFITS_CD"]) > 0) {
		$sql = $bSql->resetField()
				->field("dtl.benefits_cd")
			->createSql("\n");
		$bCode = $template->DB->getOne($sql);
		if ($bCode != $_POST["BENEFITS_CD"]) $errMessage[] = $template->message("U0408");
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

/**
 * 携帯番号認証入力チェック(重症)
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	string				エラーメッセージ
 */
function checkAuthCritical($template) {
	$errMessage = array();

	// 会員識別
	$sqlRid = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(member_no)")
		->from("mst_member")
		->where()
			->and("regist_id = ", $_POST["RID"], FD_STR)
			->and("state = "    , "0", FD_NUM)
			->and("mail = "     , $_POST["MAIL"], FD_STR)
	->createSql("\n");
	// メアド重複
	$sqlMailDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mail = "  , $_POST["MAIL"], FD_STR)
			->and( "state = " , "1", FD_NUM)
	->createSql("\n");
	// ブラックメアド
	$sqlMailBlack = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mail = "     , $_POST["MAIL"], FD_STR)
			->and( "black_flg = ", "1", FD_NUM)
	->createSql("\n");

	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["RID"])		//-- 識別ID
			->required("U0445")				// 必須
			->noCountSQL("U0445", $sqlRid)	// 存在
		->item($_POST["MAIL"])	//-- メールアドレス
			->required("U0445")				// 必須：パラ不正改竄の筈なので登録情報が確認できないにする
			->chk_mail("U0445", false)		// 形式：パラ不正改竄の筈なので登録情報が確認できないにする
			->countSQL("U0406", $sqlMailDupli)	// 重複
			->countSQL("U0446", $sqlMailBlack)	// ブラック
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

/**
 * 携帯番号認証入力チェック(軽症)
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	string				エラーメッセージ
 */
function checkAuth($template) {
	$errMessage = array();

	// 携帯番号重複
	$sqlMobileDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
			->and( "mobile = ", $_POST["MOBILE"], FD_STR)
			->and( "state = " , "1", FD_NUM)
	->createSql("\n");
	// ブラック携帯番号
	$sqlMobileBlack = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "international_cd = ", $_POST["INTERNATIONAL_CD"], FD_STR)
			->and( "mobile = "   , $_POST["MOBILE"], FD_STR)
			->and( "black_flg = ", "1", FD_NUM)
	->createSql("\n");
	// SMS識別データ
	$sqls = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(mm.member_no)")
		->from("mst_member mm")
		->join( "inner", "dat_sms_identify ds", "mm.member_no = ds.member_no")
		->where()
			->and("mm.regist_id = "   , $_POST["RID"], FD_STR)
			->and("mm.state = "       , "0", FD_NUM)
			->and("mm.mail = "        , $_POST["MAIL"], FD_STR)
			->and("ds.identify_kbn = ", "1", FD_NUM);
	$sqlSms = $sqls->createSql("\n");
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["PIN"])	//-- PIN
			->required("U0440")		// 必須
			->number("U0441")		// 半角数字
			->length("U0442", SMS_PINCODE_LENGTH)	// 桁数
			->noCountSQL("U0443", $sqlSms)	// 存在
		->item($_POST["INTERNATIONAL_CD"])	//-- 国際番号
			->required("U0451")				// 必須
			->if("U0451", array_key_exists($_POST["INTERNATIONAL_CD"], $GLOBALS["usableInternationalCode"]))	// 存在
		->item($_POST["MOBILE"])	//-- 携帯番号
			->required("U0436")				// 必須
			->number("U0437", false)		// 半角数字
			->if("U0452", (mb_strlen($_POST["INTERNATIONAL_CD"]) + mb_strlen((int)$_POST["MOBILE"])) <= 16)	// 16文字以内
			->countSQL("U0438", $sqlMobileDupli)	// 重複
			->countSQL("U0439", $sqlMobileBlack)	// ブラック
	->report();

	// PINコードチェック
	if (count($errMessage) <= 0) {
		$sql = $sqls
				->resetField()
				->field("ds.pin, ds.limit_dt")
				->createSql("\n");
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		if ($row["pin"] != $_POST["PIN"]) {		// PIN不一致
			$errMessage[] = $template->message("U0443");
		} else {
			// 有効期限チェック
			$nowDT = new DateTime("now");
			$limitDt = new DateTime( $row["limit_dt"]);
			if( $limitDt < $nowDT) $errMessage[] = $template->message("U0444");
		}
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
