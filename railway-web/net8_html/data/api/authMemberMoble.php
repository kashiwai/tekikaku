<?php
/*
 * authMemberMoble.php
 * 
 * (C)SmartRams Co.,Ltd. 2020 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 会員携帯番号認証
 * 
 * 認証用コードSMS送信とDB登録を行う
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since	 2020/06/18 初版作成 岡本静子
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/sms/send' . SMS_SERVICE_NAME . '.php');		// SMS送信関数
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
		getData($_GET, array("no","icd", "id","step", "rno"));

		//入力チェック
		$message = checkInput($template);
		if (mb_strlen($message) > 0) {
			$json = array("status"=>"ng", "error"=>$message);
		}else{
			// 実処理
			$status = "ng";
			$ret = sendSmsProc($_GET["icd"], $_GET["no"], registDB($template));
			if ($ret) $status = "ok";
			$json = array("status"=>$status, "error"=>$message);
		}
		
		header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		//jsonを返す指定
		header("Content-Type: application/json; charset=utf-8");
		print json_encode( $json);
		
	} catch (Exception $e) {
		syslog(LOG_ERR, $e->getMessage());		// エラー内容をsyslogへ出力
		print $e->getMessage();
	}
}

/**
 * DB登録処理
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	string				送信PINコード
 */
function registDB($template) {
	$mno = "";			// 対象会員
	$identifyKbn = "";	// 識別区分
	if ($_GET["step"] != "regist") {	// 更新
		$mno = $template->Session->UserInfo["member_no"];	// ログインユーザ
		$identifyKbn = "2";		// 携帯番号変更
	} else {	// 新規
		// 会員識別取得
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "member_no" )
			->from( "mst_member" )
			->where()
				->and( "regist_id = ", $_GET["id"], FD_STR)
				->and( "state = ", "0", FD_NUM)
				->and( true, "member_no <> ", $mno, FD_NUM)
		->createSql();
		$mno = $template->DB->getOne($sql);
		$identifyKbn = "1";		// 会員登録
	}
	
	//PINコードDB取得
	$nowDT = new DateTime("now");
	$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
			->field("*")
			->from("dat_sms_identify")
			->where()
				->and("member_no = "   , $mno, FD_NUM)
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
					->value( "member_no"   , $mno, FD_NUM)
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
	return $pinCode;
}

/**
 * SMS送信処理
 * @access	private
 * @param	string	$icd			国際番号
 * @param	string	$to				送信アドレス
 * @param	string	$pin			PINコード
 * @return	string					ステータスコード
 */
function sendSmsProc($icd, $to, $pin) {
	//外部送信関数呼出
	return(sendSMS($icd, $to, $pin));
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

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	$isUpdate = ($_GET["step"] != "regist");
	$mno = "";
	if ($isUpdate) {	// 更新時はログイン必須
		if(!$template->checkSessionUser(true, false)){
			$ret = $template->message("U0434");
			return $ret;
		}
		$mno = $template->Session->UserInfo["member_no"];
	}

	// 携帯番号重複
	$sqlMobileDupli = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mobile = ", (int)$_GET["no"], FD_STR)
			->and( "international_cd = ", $_GET["icd"], FD_STR)
			->and( "state = ", "1", FD_NUM)
			->and( true, "member_no <> ", $mno, FD_NUM)
	->createSql();
	// ブラック携帯番号
	$sqlMobileBlack = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "mobile = ", (int)$_GET["no"], FD_STR)
			->and( "international_cd = ", $_GET["icd"], FD_STR)
			->and( "black_flg = ", "1", FD_NUM)
			->and( true, "member_no <> ", $mno, FD_NUM)
	->createSql();
	// 会員識別
	$sqlRid = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(member_no)" )
		->from( "mst_member" )
		->where()
			->and( "regist_id = ", $_GET["id"], FD_STR)
			->and( "state = ", "0", FD_NUM)
			->and( true, "member_no <> ", $mno, FD_NUM)
	->createSql();

	$errMessage = (new SmartAutoCheck($template))
		->setUpdateMode($isUpdate)
		->item($_GET["id"])		//-- 識別ID
			->isInsert()			// 新規時
				->required("U0445")		// 必須
				->noCountSQL("U0445", $sqlRid)	// 存在
		->item($_GET["icd"])	//-- 国際番号
			->required("U0451")			// 必須
			->if("U0451", array_key_exists($_GET["icd"], $GLOBALS["usableInternationalCode"]))	// 存在
		->item($_GET["no"])		//-- 携帯番号
			->required("U0436")			// 必須
			->number("U0437")			// 半角数字
			->if("U0452", (mb_strlen($_GET["icd"]) + mb_strlen((int)$_GET["no"])) <= 16)	// 16文字以内
			->countSQL("U0438", $sqlMobileDupli)	// 携帯番号重複
			->countSQL("U0439", $sqlMobileBlack)	// ブラック携帯番号
			->case(mb_strlen($_GET["rno"]) > 0)
				->not("U0450", $_GET["rno"])	// 変わって無い
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


?>
