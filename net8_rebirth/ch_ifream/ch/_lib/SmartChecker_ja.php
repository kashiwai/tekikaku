<?php
/*
 * SmartChecker_ja.php
 * 
 * (C)SmartRams Corp. 2009 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 国別汎用チェック処理関数モジュール(日本)
 * 
 * 国別汎用チェック処理関数群
 * 
 * @package 
 * @author  岡本 静子
 * @version PHP7.x.x
 * @since   2020/05/29 初版作成 岡本 静子 SmartCheckerより言語毎対応が必要なチェックを抜出
 * @info	
 */

//@@@@@@@@@@ 形式チェック関連関数
/**
 * 電話番号チェック(999-999-9999)
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	boolean	$p_hyphen		ハイフン有無フラグ
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_tel($p_target, $p_hyphen = true) {
	if ($p_hyphen) {
		return preg_match("/^0[0-9]{1,5}(\-[0-9]{1,4})?\-[0-9]{4}$/", $p_target);
	} else {
		return preg_match("/^0[0-9]{9,10}$/", $p_target);
	}
}

/*
 * 郵便番号チェック(999-9999)
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @param	boolean	$p_hyphen		ハイフン有無フラグ
 * @return	boolean					true - チェックOK / false - チェックNG
 * @info    なし
 */
function chk_postal($p_target, $p_hyphen = true) {
	if ($p_hyphen) {
		return preg_match("/^[0-9]{3}\-[0-9]{4}$/", $p_target);
	} else {
		return preg_match("/^[0-9]{7}$/", $p_target);
	}
}

/**
 * 携帯電話番号チェック(070/080/090+数字8桁)
 * @access	public
 * @param	string	$p_target		チェック対象文字列
 * @return	boolean					ture-チェックOK / false-チェックNG
 * @info	
 */
function chk_mobile($p_target, $p_hyphen = true) {
	return preg_match("/^0[789]0[0-9]{8}$/", $p_target);
}

?>
