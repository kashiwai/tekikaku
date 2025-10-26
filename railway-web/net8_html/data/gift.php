<?php
/*
 * gift.php
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
 * ギフト送信画面表示
 * 
 * ギフト送信画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/04/09 初版作成 片岡 充
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
		$template->checkSessionUser(true, true);
		
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理
		switch ($_GET["M"]) {
			case "end":				// 完了画面
				DispComplete($template);
				break;
			
			case "send":			// ギフト送信処理
				ProcSend($template);
				break;
			
			case "conf":			// ギフト送信確認画面
				DispConf($template);
				break;

			case "geticd":			// 受信者取得
				GetReceiver($template);
				break;
			
			default:				// ギフト送信画面
				DispDetail($template);
		}
		
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}


/**
 * ギフト送信画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispDetail($template, $message="") {
	// データ取得
	getData($_POST , array("INV_CODE", "SEND_COIN", "AGREE"));
	
	//ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point")
			->field("mg.min_point, mg.lot, mg.commission_rate, mg.commission_rounding, mg.bearer")
			->field("men.agent_flg, men.gift_point, men.total_gift_point")
			->from("mst_member men")
			->from("inner join mst_gift mg on no = 1")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	$_point = $row["draw_point"];
	$giftPoint = (int)$row["gift_point"];	// ギフト送信可能ポイント
	if (!GIFT_AGENT) $giftPoint = (int)$_point;		// エージェント対応以外は所持抽選ポイント
	// 当日送信済ポイント
	$daySend = GetDaySendPoint($template, $row["agent_flg"]);

	// ギフト上限設定取得
	$dayLimit = -1;
	$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
			->field("total_gift_point, gift_limit")
		->from("mst_gift_limit")
		->where()
			->and("del_flg = ", "0", FD_NUM)
		->orderby("total_gift_point asc")
		->createSQL();
	$rsLimit = $template->DB->query($sql);
	$existLimit = ($rsLimit->rowCount() > 0);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG"    , $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);		// メッセージ表示制御
	$template->if_enable("ENABLE_MIN" , (int)$row["min_point"] > 0);
	$template->if_enable("ENABLE_LOT" , (int)$row["lot"] > 0);
	
	$template->assign("INVITE_CODE_LENGTH", INVITE_CODE_LENGTH, true);
	$template->assign("POINT"             , $_point, true);
	$template->assign("POINT_LABEL"       , number_format( $_point), true);
	$template->assign("INV_CODE"          , $_POST["INV_CODE"], true);
	$template->assign("SEND_COIN"         , $_POST["SEND_COIN"], true);
	$template->assign("MIN_POINT"         , $row["min_point"], true);
	$template->assign("LOT"               , $row["lot"], true);
	$template->assign("RATE"              , $row["commission_rate"], true);
	$template->assign("ROUNDING"          , $row["commission_rounding"], true);
	$template->assign("BEARER"            , $row["bearer"], true);
	$template->assign("DISP_MIN_POINT"    , number_format( $row["min_point"]), true);
	$template->assign("DISP_LOT"          , number_format( $row["lot"]), true);
	$template->assign("DISP_BEARER"       , $GLOBALS["pointGiftBearerList"][$row["bearer"]], true);
	$template->assign("CHK_AGREE"         , ($_POST["AGREE"]==1)? "checked":"", true);

	// ギフト送信エージェント
	$template->assign("DSP_GIFT_POINT"      , number_formatEx($giftPoint), true);
	$template->assign("DSP_TOTAL_GIFT_POINT", number_formatEx($row["total_gift_point"]), true);
	$template->assign("AGENT_FLG"           , $row["agent_flg"], true);

	$isLimitSet = false;
	$dspLimit = $template->exist_loop("GIFT_LIMIT");

	if ($existLimit || $dspLimit) {		// 上限設定存在 若しくは 表示タグ有
		if ($dspLimit) $template->loop_start("GIFT_LIMIT");
		while ($rowLimit = $rsLimit->fetch(MDB2_FETCHMODE_ASSOC)) {
			$isLimitSet = true;
			$template->assign("SET_TOTAL_GIFT_POINT", number_formatEx($rowLimit["total_gift_point"]), true);
			$template->assign("SET_GIFT_LIMIT"      , number_formatEx($rowLimit["gift_limit"]), true);

			if ($row["agent_flg"] != "1" && $dayLimit < 0 && (int)$rowLimit["total_gift_point"] >= (int)$row["total_gift_point"]) $dayLimit = (int)$rowLimit["gift_limit"];

			if ($dspLimit) $template->loop_next();
		}
		if ($dspLimit) $template->loop_end("GIFT_LIMIT");
	}
	unset($rsLimit);
	$template->assign("GIFT_POINT"  , $giftPoint, true);				// ギフト送信可能ポイント
	$template->assign("DAY_SEND"    , $daySend, true);					// 送信済ポイント
	$template->assign("DSP_DAY_SEND", number_formatEx($daySend), true);	// 送信済ポイント
	$template->assign("GIFT_LIMIT"  , $dayLimit, true);		// 日上限

	$template->if_enable("EXIST_SEND" , $daySend > 0);
	$template->if_enable("EXIST_LIMIT", $isLimitSet);
	$template->if_enable("NONE_AGENT" , GIFT_AGENT && $row["agent_flg"] != "1");
	
	// 表示
	$template->flush();
}


/**
 * ギフト送信確認画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispConf($template, $message="") {
	// データ取得
	getData($_POST , array("INV_CODE", "SEND_COIN", "AGREE", "INTERNATIONAL_CD","TEL", "INIT"));
	
	$_POST["INIT"] = "";
	if (mb_strlen($message) > 0) {
		//ProcSendがエラーの場合
	}else{
		//各種チェック
		_checks($template, $message);
		if (AUTH_MEMBER_MOBILE) {
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mm.member_no, mm.international_cd, mm.mobile")
			->from("mst_member mm")
			->where()
				->and( false, "mm.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
			->createSQL();
			$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
			if (mb_strlen($row["member_no"]) > 0) {
				$_POST["INTERNATIONAL_CD"] = $row["international_cd"];
				$_POST["TEL"] = $row["mobile"];
				$_POST["INIT"] = "1";
			} else {
				$_POST["INTERNATIONAL_CD"] = DEFAULT_INTERNATIONAL_CODE;
			}
		}
	}
	$memberRow = $_POST["MEMBER"];
	
	$template->open(PRE_HTML . "_conf.html");
	$template->assignCommon();
	$template->assign("ERRMSG"    , $message);
	$template->if_enable("ERRMSG" , mb_strlen($message) > 0);		// メッセージ表示制御
	
	$template->assign("SMS_PINCODE_LENGTH", SMS_PINCODE_LENGTH, true);
	$template->assign("GIFT_TEL_LIMIT"       , GIFT_TEL_LIMIT, true);
	
	$template->assign("INV_CODE"     , $_POST["INV_CODE"], true);
	$template->assign("SEND_COIN"    , $_POST["SEND_COIN"], true);
	$template->assign("TEL"          , $_POST["TEL"], true);
	$template->assign("INIT"         , $_POST["INIT"], true);
	$template->assign("SEND_NICKNAME", $memberRow["nickname"], true);
	$template->assign("SEL_INTERNATIONAL_CD", makeOptionArray($GLOBALS["usableInternationalCode"], $_POST["INTERNATIONAL_CD"], false));
	
	// 表示
	$template->flush();
}


/**
 * ギフト送信処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcSend($template) {
/* --
	作成者が$_POSTが好きらしくHTTP POST variablesでは無い$_POST変数が
	各種チェック[_checks]で設定されているので注意して下さい
		$_POST["GIFT"]				送信会員の抽選ポイントとギフト設定マスタ[mst_gift]の情報
		$_POST["MEMBER"]			受信会員の情報
		$_POST["SEND_COIN_TOTAL"]	手数料送信者負担時「入力したポイント＋手数料」、受信者負担時「入力したポイント」
		$_POST["COMM"]				手数料
-- */
	// データ取得
	getData($_POST , array("INV_CODE", "SEND_COIN", "AGREE", "PIN", "INTERNATIONAL_CD"));
	
	//各種チェック
	_checks($template);
	//入力チェック
	$message = checkInput2($template);
	if (mb_strlen($message) > 0) {
		DispConf($template, $message);
		return;
	}
	
	//PINコードDBチェック
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("*")
			->from("dat_giftSMS" )
			->where()
				->and( "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( "pin = ",       $_POST["PIN"], FD_NUM)
			->limit(1)
		->createSql();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	if( empty($row)){	// 不存在
		$message = $template->message("U1924");
	} else {
		// 有効期限確認
		if (strtotime($row["limit_dt"]) < time()) $message = $template->message("U1925");
	}
	if (mb_strlen($message) > 0) {
		DispConf($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//DBログ登録
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->insert()
			->into( "his_gift" )
				->value( "member_no"        , $template->Session->UserInfo["member_no"], FD_NUM)	//送った人
				->value( "agent_flg"        , $_POST["GIFT"]["agent_flg"], FD_NUM)					//送信者エージェントフラグ
				->value( "gift_dt"          , "current_timestamp", FD_FUNCTION)						//送った日時
				->value( "gift_point"       , $_POST["SEND_COIN_TOTAL"], FD_NUM)					//送ったポイント
				->value( "commission_rate"  , $_POST["GIFT"]["commission_rate"], FD_NUM)			//その時のレート
				->value( "commission_point" , $_POST["COMM"], FD_NUM)								//その時の手数料
				->value( "bearer"           , $_POST["GIFT"]["bearer"], FD_NUM)						//その時の負担者
				->value( "receive_member_no", $_POST["MEMBER"]["member_no"], FD_NUM)				//受け取った人
				->value( "receive_agent_flg", $_POST["MEMBER"]["agent_flg"], FD_NUM)				//受取者エージェントフラグ
				->value( "receive_point"    , $_POST["SEND_COIN_TOTAL"] - $_POST["COMM"], FD_NUM)	//受け取ったポイント
		->createSQL();
	$template->DB->query($sql);
	
	//dat_giftSMSから該当データ削除
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->delete()
			->from( "dat_giftSMS" )
			->where()
				->and( "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
		->createSql();
	$template->DB->exec( $sql);
	
	//それぞれに減算と加算
	$PPOINT = new PlayPoint($template->DB, false);
	//送った人
	$PPOINT->addDrawPoint( $template->Session->UserInfo["member_no"], "53", -( $_POST["SEND_COIN_TOTAL"] ), $_POST["MEMBER"]["member_no"]);
	//受け取った人
	$PPOINT->addDrawPoint( $_POST["MEMBER"]["member_no"], "13", ($_POST["SEND_COIN_TOTAL"] - $_POST["COMM"]), $template->Session->UserInfo["member_no"]);
	//通知（送信）
	$contact_message = array();
	$search  = array( "%RECEIVE%", "%POINT%", "%COMM%","%LABEL_3%");
	foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
		$replace = array( $_POST["MEMBER"]["nickname"], number_formatEx($_POST["SEND_COIN_TOTAL"]), number_formatEx($_POST["COMM"]), $GLOBALS["unitLangList"][$k]["3"]);
		$contact_message[$k] = str_replace( $search, $replace, $v["07"]);
	}
	$contact = new ContactBox( $template->DB, false);
	$contact->addOneRecord( $template->Session->UserInfo["member_no"], "07", $_POST["MEMBER"]["member_no"], $contact_message);
	//通知（受取）
	$contact_message = array();
	$search  = array( "%SENDER%", "%POINT%", "%COMM%", "%LABEL_3%");
	foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
		$replace = array( $template->Session->UserInfo["nickname"], number_formatEx($_POST["SEND_COIN_TOTAL"] - $_POST["COMM"]), number_formatEx($_POST["COMM"]), $GLOBALS["unitLangList"][$k]["3"]);
		$contact_message[$k] = str_replace( $search, $replace, $v["08"]);
	}
	$contact = new ContactBox( $template->DB, false);
	$contact->addOneRecord( $_POST["MEMBER"]["member_no"], "08", $template->Session->UserInfo["member_no"], $contact_message);
	
	// エージェント対応はギフト可能ポイントを減算
	if (GIFT_AGENT) {
		$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->update( "mst_member")
				->set()
					->value("gift_point", "IF(gift_point > " . $template->DB->conv_sql($_POST["SEND_COIN_TOTAL"], FD_NUM)
															 . ", gift_point - " . $template->DB->conv_sql($_POST["SEND_COIN_TOTAL"], FD_NUM) . ", 0)"
							 , FD_FUNCTION)
					->value("upd_no"    , $template->Session->UserInfo["member_no"], FD_NUM)
					->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
				->where()
					->and("member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
	}

	//再取得
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("point, draw_point")
				->from("mst_member")
				->where()
					->and("member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and("mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and("state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	$template->Session->UserInfo["draw_point"] = $row["draw_point"];
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	//完了画面
	header("Location: " . URL_SSL_SITE . "gift.php?M=end");
	
}


/**
 * 完了画面
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {
	
	$template->open(PRE_HTML . "_end.html");
	$template->assignCommon();
	// 表示
	$template->flush();
	
}


/**
 * ポイント取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	ary						対象者レコード
 */
function checkPoint($template) {
	$_ret = array();
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point")
			->field("mg.min_point, mg.lot, mg.commission_rate, mg.commission_rounding, mg.bearer")
			->field("men.agent_flg, men.gift_point, men.total_gift_point")
			->from("mst_member men")
			->from("inner join mst_gift mg on no = 1")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = ", "1", FD_NUM)
			->limit(1)
		->createSQL();
	$_ret = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	return $_ret;
}


/**
 * 招待コード対象者存在チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	object	$code			招待コード
 * @return	ary						対象者レコード
 */
function checkMemberFromCode($template, $code) {
	// 招待コード保持ナンバー取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field( "mm.member_no, mm.nickname, mm.invite_cd" )
			->field( "mm.agent_flg" )
			->from( "mst_member mm" )
			->where()
				->and( "mm.invite_cd = ", $code, FD_STR)
				->and( "mm.state = ", "1", FD_NUM)
			->limit(1)
			->createSql();
	$_ret = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	return $_ret;
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
		->item($_POST["INV_CODE"])
			->required("U1901")
			->alnum("U1902")
			->minLength("U1902", INVITE_CODE_LENGTH)
			->maxLength("U1902", INVITE_CODE_LENGTH)
		->item($_POST["SEND_COIN"])
			->required("U1903")
			->number('U1904')
		->item($_POST["AGREE"])
			->required("U1990")
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * PIN形式入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput2($template) {
	$errMessage = array();
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_POST["PIN"])
			->required("U1921")
			->number("U1922")
			->minLength("U1923", SMS_PINCODE_LENGTH)
			->maxLength("U1923", SMS_PINCODE_LENGTH)
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


/**
 * 入力チェックまとめ
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 */
function _checks($template) {
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		exit();
	}

	// 受取者存在チェック
	$memberRow = checkMemberFromCode( $template, $_POST["INV_CODE"]);
	if( empty( $memberRow)){
		$message = $template->message("U1905");
		DispDetail($template, $message);
		exit();
	}else{
		if( $template->Session->UserInfo["member_no"] == $memberRow["member_no"]){
			$message = $template->message("U1905");
			DispDetail($template, $message);
			exit();
		}
	}
	$_POST["MEMBER"] = $memberRow;

	// ギフト送信関連情報取得
	$row = checkPoint( $template);

	// ロット単位チェック
	if( $row["lot"] > 0){
		if( $_POST["SEND_COIN"] % $row["lot"] != 0 ){
			$message = $template->message("U1912");
			DispDetail($template, $message);
			exit();
		}
	}
	// 手数料率 ※エージェントは手数料なしなので0とする
	if ($memberRow["agent_flg"] != "0" || $row["agent_flg"] != "0") $row["commission_rate"] = 0;
	$_POST["GIFT"] = $row;
	//Comm計算
	$_comm = $_POST["SEND_COIN"] * ($row["commission_rate"] / 100);
	if( $row["commission_rounding"] == 1){
		$_comm = floor( $_comm);
	}else if( $row["commission_rounding"] == 2){
		$_comm = ceil( $_comm);
	}else if( $row["commission_rounding"] == 3){
		$_comm = round( $_comm);
	}
	$_POST["COMM"] = $_comm;
	// 数量チェック
	if( $_POST["SEND_COIN"] < $row["min_point"]){
		$message = $template->message("U1910");
		DispDetail($template, $message);
		exit();
	}
	
	if( $row["bearer"] == 2){
		if( $_POST["SEND_COIN"] > $row["draw_point"]){
			$message = $template->message("U1911");
			DispDetail($template, $message);
			exit();
		}
		$_POST["SEND_COIN_TOTAL"] = $_POST["SEND_COIN"];
	}else if( $row["bearer"] == 1){
		if( $_POST["SEND_COIN"] + $_comm > $row["draw_point"]){
			$message = $template->message("U1913");
			DispDetail($template, $message);
			exit();
		}
		$_POST["SEND_COIN_TOTAL"] = $_POST["SEND_COIN"] + $_comm;
	}
	
	// 送信可能制限 ※エージェント対応時 且 送信者がエージェント以外のみ
	if (GIFT_AGENT && $row["agent_flg"] != "1" && $_POST["SEND_COIN_TOTAL"] > $row["gift_point"]) {
		if ($row["bearer"] == 1) {
			$message = $template->message("U1917");
		} else {
			$message = $template->message("U1915");
		}
		DispDetail($template, $message);
		exit();
	}
	// 日制限
	if ($row["agent_flg"] != "1") {		// 送信者がエージェント以外
		$dayLimit = -1;
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("total_gift_point, gift_limit")
			->from("mst_gift_limit")
			->where()
				->and("del_flg = ", "0", FD_NUM)
			->orderby("total_gift_point asc")
			->createSQL();
		$rsLimit = $template->DB->query($sql);
		while ($rowLimit = $rsLimit->fetch(MDB2_FETCHMODE_ASSOC)) {
			if ((int)$rowLimit["total_gift_point"] >= (int)$row["total_gift_point"]) {
				$dayLimit = (int)$rowLimit["gift_limit"];
				break;
			}
		}
		// 送信者当日送信済ポイント
		$daySend = GetDaySendPoint($template, $row["agent_flg"]);

		if ($dayLimit >= 0 && $_POST["SEND_COIN_TOTAL"] + $daySend > $dayLimit) {
			$message = $template->message("U1916");
			DispDetail($template, $message);
			exit();
		}
	}

}

/**
 * 当日送信済ポイント取得
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @param	int		$AgentFlg	エージェントフラグ
 * @return	当日送信済ポイント ※エージェントは0
 */
function GetDaySendPoint($template, $AgentFlg) {
	$result = 0;
	if ($AgentFlg != 1) {
		$today = GetRefTimeToday();
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("IFNULL(SUM(gift_point), 0)")
			->from("his_gift")
			->where()
				->and("member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and("gift_dt", 'between', GetRefTimeStart($today), FD_DATE, GetRefTimeEnd($today), FD_DATE)
			->createSQL();
		$result = (int)$template->DB->getOne($sql);
	}
	return $result;
}

/**
 * 受信者取得
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	当日送信済ポイント ※エージェントは0
 */
function GetReceiver($template) {
	try {
		// データ取得
		getData($_GET, array("icd"));
		// 受信会員取得
		$memberRow = checkMemberFromCode($template, $_GET["icd"]);
		if (empty($memberRow)) {
			$json = array("status"=>"ng", "error"=>$template->message("U1905"));
		} else {
			$json = array("status"=>"ok", "agent_flg"=>$memberRow["agent_flg"]);
		}
		header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		// jsonを返す指定
		header("Content-Type: application/json; charset=utf-8");
		print json_encode($json);

	} catch (Exception $e) {
		print $e->getMessage();
	}
	return;
}

?>
