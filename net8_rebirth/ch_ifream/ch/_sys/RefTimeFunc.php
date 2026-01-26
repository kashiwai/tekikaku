<?php
/*
 * RefTimeFunc.php
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
 * 基準時間共通関数群
 * 
 * 基準時間関連で共通使用する関数群
 * 
 * @package
 * @author		岡本静子
 * @version		1.00
 * @since		2020/07/07 v1.00	岡本静子	新規作成
 * @info
 */

/**
 * 基準時間の当日日付取得
 * @access	public
 * @param	string	$nowDt		現在日時
 * @param	string	$format		返却書式
 * @return	string	基準時間で変換した当日
 * @info
*/
function GetRefTimeToday($nowDt = "", $format = "Y/m/d") {

	if (mb_strlen($nowDt) <= 0) $nowDt = date("Y/m/d H:i");

	$sysDt = new DateTime($nowDt);
	$referenceTime = get_business_hours_config('REFERENCE_TIME');
	if (TimeToNum($sysDt->format("H:i")) < TimeToNum($referenceTime)) {
		// 基準時間が来ていないので前日
		$sysDt->modify("-1 day");
	}
	return $sysDt->format($format);

}

/**
 * 基準時間の当日日付取得(拡張)
 * @access	public
 * @param	string	$nowDt		現在日時
 * @param	string	$format		返却書式
 * @return	string	基準時間で変換した当日
 * @info	基準時間が0時の場合は使用開始時間で変換
*/
function GetRefTimeTodayExt($nowDt = "", $format = "Y/m/d") {

	if (mb_strlen($nowDt) <= 0) $nowDt = date("Y/m/d H:i");
	$referenceTime = get_business_hours_config('REFERENCE_TIME');
	$openTime = get_business_hours_config('GLOBAL_OPEN_TIME');
	$judgTime = TimeToNum($referenceTime);
	if ($judgTime == 0) $judgTime = TimeToNum($openTime);

	$sysDt = new DateTime($nowDt);
	if (TimeToNum($sysDt->format("H:i")) < $judgTime) {
		// 基準時間が来ていないので前日
		$sysDt->modify("-1 day");
	}
	return $sysDt->format($format);

}

/**
 * 日付指定の開始日時取得
 * @access	public
 * @param	string	$trgDate	指定日
 * @param	string	$format		返却書式
 * @return	string	開始日時
 * @info
*/
function GetRefTimeStart($trgDate = "", $format = "Y/m/d H:i:s") {

	if (mb_strlen($trgDate) <= 0) $trgDate = date("Y/m/d");

	$referenceTime = get_business_hours_config('REFERENCE_TIME');
	$startDt = new DateTime($trgDate . " " . $referenceTime);
	return $startDt->format($format);

}

/**
 * 日付指定の終了日時取得
 * @access	public
 * @param	string	$trgDate	指定日
 * @param	string	$format		返却書式
 * @return	string	終了日時
 * @info
*/
function GetRefTimeEnd($trgDate = "", $format = "Y/m/d H:i:s") {

	if (mb_strlen($trgDate) <= 0) $trgDate = date("Y/m/d");

	$referenceTime = get_business_hours_config('REFERENCE_TIME');
	$endDt = new DateTime($trgDate . " " . $referenceTime);
	$endDt->modify("+1 day");
	$endDt->modify("-1 second");
	return $endDt->format($format);

}

/**
 * 差日数指定の開始日時取得
 * @access	public
 * @param	int		$offset		差日数
 * @param	string	$format		返却書式
 * @return	string	開始日時
 * @info
*/
function GetRefTimeOffsetStart($offset = 0, $format = "Y/m/d H:i:s") {

	$referenceTime = get_business_hours_config('REFERENCE_TIME');
	$startDt = new DateTime(GetRefTimeToday() . " " . $referenceTime);
	if ($offset != 0) {
		// 差日数加算
		$startDt->modify("+" . $offset . " day");
	}
	return $startDt->format($format);

}

/**
 * 差日数指定の終了日時取得
 * @access	public
 * @param	int		$offset		差日数
 * @param	string	$format		返却書式
 * @return	string	終了日時
 * @info
*/
function GetRefTimeOffsetEnd($offset = 0, $format = "Y/m/d H:i:s") {

	// 指定翌日の開始取得
	$start = GetRefTimeOffsetStart($offset + 1);
	// -1秒して指定日の終了
	$endDt = new DateTime($start);
	$endDt->modify("-1 second");
	return $endDt->format($format);

}

/**
 * 時間を数値に変換
 * @access	public
 * @param	string	$time	時間
 * @return	numeric	変換した数値
 * @info	分を少数以下で表現
 */
function TimeToNum($time) {

	$timeWork = explode(":", $time);
	return $timeWork[0] + ($timeWork[1] / 60);

}

?>