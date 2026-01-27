<?php
/*
 * sendTwilio.php
 * 
 * (C)SmartRams Corp. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * SMS送信用の関数
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since	 2020/04/22 初版作成 片岡 充
 * @info	送信サービス：Twilio
 */

use Twilio\Rest\Client;
// 2020/12/28 [UPD Start]
//require_once('../vendor/Twilio/autoload.php');
require_once(DIR_BASE . 'data/vendor/Twilio/autoload.php');
// 2020/12/28 [UPD End]

/**
 * SMS送信処理
 * @access	private
 * @param	string	$icd	国際番号
 * @param	string	$to		送信番号
 * @param	string	$pin	PINコード
 * @return	boolean			送信時真を返却
 */
function sendSMS($icd, $to, $pin){
	$result = false;

	$toNo = toInternational($icd, $to);	// 国際番号変換
	$body = str_replace(array("%PIN%"), array($pin), SMS_MESSAGE);
	// PINコード送信
	if (mb_strlen($toNo) > 0) {
		//インスタンス
		$client = new Client(TWILIO_SID, TWILIO_TOKEN);
		//送信
		$message = $client->messages->create(
			$toNo,
			array(
				"from" => TWILIO_NUMBER,
				"body" => $body
			)
		);
		// status判定(undelivered：配信されなかった、failed：送信できなかった) 
		if ($message->status != "undelivered" && $message->status != "failed") $result = true;
	}

	return $result;
}

/**
 * 電話番号を国際電話番号に変換
 * @access	private
 * @param	string	$icd		国際番号
 * @param	string	$number		電話番号
 * @return	string				国際電話番号
 */
function toInternational($icd, $number) {
	if (array_key_exists($icd, $GLOBALS["usableInternationalCode"]) && chk_number($number)) {
		return $icd . (int)$number;
	} else {
		return "";
	}
}

?>