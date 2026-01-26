/**
 * @fileOverview
 * 国別入力チェックライブラリ(日本)
 * 
 * (C)SmartRams Corp. 2003-2011 All Rights Reserved．
 * 
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * @package
 * @author    岡本 静子
 * @language  JavaScript
 * @version   1.1
 * @since     2020/05/29 初版作成 岡本 静子 chk_inputより言語毎対応が必要なチェックを抜出
 * @using     chk_input.js
 * @desc
 */

/*----------------------------
  形式チェック
-----------------------------*/
/**
 * 郵便番号チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	boolean		chk 	ハイフンのチェック可否 true - チェックする / false(デフォルト) - チェックしない
  * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_postal(msg, val, indis, chk) {
	if (!chk) chk = false;
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (chk) {
		if (val.search(/^[0-9]{3}\-[0-9]{4}$/i) == -1) {
			if (msg != "") {
				chkAlert(msg);
			}
			return false;
		}
	} else {
		if (val.search(/^[0-9]{3}[0-9]{4}$/i) == -1) {
			if (msg != "") {
				chkAlert(msg);
			}
			return false;
		}
	}
	return true;
}
/**
 * 電話番号チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	boolean		hyphen	true(デフォルト) - 有り / false - 無し
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_tel(msg, val, indis, hyphen) {
	if (hyphen !== true && hyphen !== false) hyphen = true;
	
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}

	if (hyphen) {
		reg = new RegExp("^0[0-9]{1,5}(\-[0-9]{1,4})?\-[0-9]{4}$", "i");
	} else {
		reg = new RegExp("^0[0-9]{9,10}$", "i");
	}
	
	if (val.search(reg) == -1) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/**
 * 携帯電話番号(070/080/090+数字8桁)チェック 2020/05/28 add
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_mobile(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	
	reg = new RegExp("^0[789]0[0-9]{8}$", "i");
	
	if (val.search(reg) == -1) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
