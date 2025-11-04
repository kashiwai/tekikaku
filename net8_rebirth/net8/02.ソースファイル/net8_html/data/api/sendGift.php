<?php
/*
 * sendGift.php
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
 * SMS送信とDB登録を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/04/10 初版作成 片岡 充
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
		// API系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		if( $template->checkSessionUser(true, false)){
			
			// データ取得
			getData($_GET, array("t","icd"));
			
			//入力チェック
			$message = checkInput( $template);
			if (mb_strlen($message) > 0) {
				$json = array("status"=>"ng", "error"=>$message);
			}else{
				// 実処理
				$status = "ng";
				$ret = sendSmsProc($_GET["icd"], $_GET["t"], registDB( $template));
				if ($ret) $status = "ok";
				$json = array("status"=>$status, "error"=>$message);
			}
		}else{
			$json = array("status"=>"ng", "error"=>"not login");
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
		print $e->getMessage();
	}
}

/**
 * DB登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					送信PINコード
 */
function registDB($template) {
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	//PINコードDB取得
	$nowDT = new DateTime("now");
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("*")
			->from("dat_giftSMS" )
			->where()
				->and( "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
			->limit(1)
			->orderby( "add_dt desc" )
		->createSql();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	if( !empty( $row)){
		// 既存あり
		$limitDt = new DateTime( $row["limit_dt"]);
		if( $limitDt >= $nowDT){
			// 有効期限内
			$pinCode = $row["pin"];
		}else{
			// PINコード新規発行
			$pinCode = makeRandPin(SMS_PINCODE_LENGTH);
			$newLimitDt = new DateTime("now");
			$newLimitDt->add(new DateInterval(SMS_PINCODE_LIMIT));
			// DB更新
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_giftSMS" )
					->set()
						->value( "pin"             , $pinCode, FD_NUM)
						->value( "limit_dt"        , $newLimitDt->format("Y-m-d H:i"), FD_DATE)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
					->where()
						->and( "member_no = "      , $template->Session->UserInfo["member_no"], FD_NUM)
					->limit(1)
					->orderby( "add_dt desc" )
				->createSQL();
			$template->DB->query($sql);
		}
	}else{
		//新規
		$pinCode = makeRandPin(SMS_PINCODE_LENGTH);
		$limitDt = new DateTime("now");
		$limitDt->add(new DateInterval(SMS_PINCODE_LIMIT));
		// DB登録
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->insert()
				->into( "dat_giftSMS" )
					->value( "member_no"       , $template->Session->UserInfo["member_no"], FD_NUM)
					->value( "pin"             , $pinCode, FD_NUM)
					->value( "limit_dt"        , $limitDt->format("Y-m-d H:i"), FD_DATE)
					->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
			->createSQL();
		$template->DB->query($sql);
	}
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	return $pinCode;
}

/**
 * SMS送信処理
 * @access	private
 * @param	string	$idc			国際番号
 * @param	string	$to				送信アドレス
 * @param	string	$pin			PINコード
 * @return	string					ステータスコード
 */
function sendSmsProc($idc, $to, $pin) {
	//外部送信関数呼出
	return( sendSMS($idc, $to, $pin));
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
	$errMessage = (new SmartAutoCheck($template))
		->item($_GET["icd"])	//-- 国際番号
			->required("U1908")			// 必須
			->if("U1908", array_key_exists($_GET["icd"], $GLOBALS["usableInternationalCode"]))	// 存在
		->item($_GET["t"])
			->required("U1906")
			->number("U1907")	// 半角数字
			->if("U1909", (mb_strlen($_GET["icd"]) + mb_strlen((int)$_GET["t"])) <= 16)	// 16文字以内
	->report();
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}


?>
