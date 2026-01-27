/**
 * @fileOverview
 * 入力チェックライブラリ
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
 * @author    岡本 順子
 * @language  JavaScript
 * @version   1.1
 * @since     2003/11/01 ver1.0 岡本順子 初版作成
 * @since     2009/02/13 ver1.1 岡本順子 文字数基準フル桁入力チェック対象項目追加
 * @since     2016/09/06 ver2.0 鶴野美香 リッチアニメーションアラートに変更し、メッセージ全文をパラでもらうよう変更
 * @since     2020/05/29 修正 岡本 静子 言語毎対応が必要な電話番号、郵便番号チェックを各言語用に切出し
 * @using     ctl_string.js
 * @using     es6-promise.js / sweetalert2.min.js / sweetalert2.min.css => ver2.0から必要
 * @desc
 */



/*----------------------------
  入力チェック共通
-----------------------------*/
/**
 * エラー時アラート
 * @access	public
 * @param	string		msg		アラート文
 * @return	なし
 * @desc	なし
 */
function chkAlert(msg) {
	if (msg == "") return;
	swal({
//		title: "入力エラー",
		text: msg,
		type: 'error'
	});
}

/**
 * 必須チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_indis(msg, val, full) {
	if (Trim(val) == "") {
		if (msg != "") {
//			if (pos != "") alert(pos + "を入力してください。");
			chkAlert(msg);
		}
		return false;
	}
	if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * 固定長入力チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	object		full	文字数基準フル桁入力チェック対象項目
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
 function chk_fullLength(msg, val, full) {
    if (!full) return true;
	if (!full.maxLength) return true;
    if (Trim(val).length != full.maxLength) {
		if (msg != "") {
//			alert(pos + "は" + full.maxLength + "桁で入力してください。");
			chkAlert(msg);
		}
		return false;
	}
	return true; 
}

/*----------------------------
  数値チェック
-----------------------------*/
/**
 * 数値チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	マイナス不可
 */
function chk_numeric(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	val = val.replace(/,/g, "");		// カンマ除去
	if (val == "0") return true;						// 0 のみの場合はOK
	var ret = true;
    if (val.search(/^0/i) != -1) ret = false;			// 先頭が 0 の場合はNG
    if (val.search(/^[0-9]+$/i) == -1) ret = false;
	if (!ret) {
		if (msg != "") {
//			alert(pos + "は数値(マイナス不可)を入力してください。");
			chkAlert(msg);
		}
		return false;
	}

	return true;
}
/**
 * 数値チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	マイナス許可
 */
function chk_numericEx(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	val = val.replace(/,/g, "");		// カンマ除去
	if (val == "0") return true;						// 0 のみの場合はOK
	var ret = true;
    if (val.search(/^(-?)0/i) != -1) ret = false;		// 先頭が 0 の場合はNG
    if (val.search(/^(-?)[0-9]+$/i) == -1) ret = false;
	if (!ret) {
		if (msg != "") {
//			alert(pos + "は数値(マイナス可能)を入力してください。");
			chkAlert(msg);
		}
		return false;
	}

	return true;
}
/**
 * 小数チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val			対象文字列
 * @param	boolean		indis		true - 必須 / false(デフォルト) - 未入力可
 * @param	integer		precision	精度
 * @param	integer		scale		小数点
 * @return	boolean					true - チェックOK / false - チェックNG
 * @desc	整数部５桁.小数部２桁まで(デフォルト)
 */
function chk_decimal(msg, val, indis, precision, scale) {
	if (!precision) precision = 5;
	if (!scale) scale = 2;
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	val = val.replace(/,/g, "");		// カンマ除去
	reg = new RegExp("^[0-9]{1" + ((precision > 1) ? "," + precision : "") + "}(\\.[0-9]{1" + ((scale > 1) ? "," + scale : "") + "})?$", "i");
//	if (val.search(/^[0-9]{1,5}(\.[0-9]{1,2})?$/i) == -1) {
	if (val.search(reg) == -1) {
		if (msg != "") {
//			alert(pos + "は整数部" + precision + "桁、小数部" + scale + "桁までの数値(半角)を入力してください。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/**
 * 数値大小チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		num1	数値1 小さいのが正しいはずの日付
 * @param	string		num2	数値2 大きいのが正しいはずの日付
 * @param	boolean		equal	完全一致の数値を含むか否か
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	比較値は数値として正しい事
 */
function compare_numeric(msg, num1, num2, equal) {
	if (!equal) equal = false;
	var chkNum1 = parseFloat(num1.replace(/,/g, ""));		// カンマ除去 & 数値変換
	var chkNum2 = parseFloat(num2.replace(/,/g, ""));		// カンマ除去 & 数値変換

	var ret = (equal) ? (chkNum1 >= chkNum2) : (chkNum1 > chkNum2);
	if (ret) {
		if (msg != "") {
//			alert(pos + "の開始・終了が不正です。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/*----------------------------
  英数字チェック
-----------------------------*/
/**
 * 半角数字チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_number(msg, val, indis, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
    if (val.search(/[^0-9]/i) != -1) {
		if (msg != "") {
//			alert(pos + "は数字(半角)を入力してください。");
			chkAlert(msg);
		}
		return false;
    }
    if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * 英数字チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	string		sign	使用可能記号(エンコードして羅列 例：「_\-\/\s\.\*\(\)\[\]\:\;」)
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_alnum(msg, val, indis, sign, full) {
	if (!sign) sign = "";
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	reg = new RegExp("([^A-Za-z0-9" + sign + "]+)", "i");
//	if (val.search(/([^A-Za-z0-9_\-\/\s\.\*\(\)\[\]\:\;]+)/i) != -1) {
	if (val.search(reg) != -1) {
		if (msg != "") {
//			alert(pos + "は英数字(半角)を入力してください。");
			chkAlert(msg);
		}
		return false;
	}
    if (!chk_fullLength(msg, val, full)) return false;
	return true;
}

/*----------------------------
  文字チェック
-----------------------------*/
/**
 * 全角カナチェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	全角スペース・半角スペースも可
 */
function chk_syll(msg, val, indis, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (val.search(/^[　 ーァ-ヶ]+$/i) == -1) {
		if (msg != "") {
//			alert(pos + "は全角カナを入力してください。");
			chkAlert(msg);
		}
		return false;
	}
    if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * 半角カナチェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	半角スペースも可
 */
function chk_syllHalf(msg, val, indis, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (val.search(/^[ ｦ-ﾟ]+$/i) == -1) {
		if (msg != "") {
//			alert(pos + "は半角カナを入力してください。");
			chkAlert(msg);
		}
		return false;
	}
    if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * 半角文字チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_charHalf(msg, val, indis, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	for (var i = 0; i < val.length; i++) {
		var c = val.charCodeAt(i);
		// Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
		// Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
		if ((c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
		} else {
			if (msg != "") {
//				alert(pos + "は半角文字を入力してください。");
				chkAlert(msg);
			}
			return false;
		}
	}
	if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * 全角文字チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_charMulti(msg, val, indis, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
    for (var i = 0; i < val.length; i++) {
		var c = val.charCodeAt(i);
		// Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
		// Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
		if ((c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
			if (msg != "") {
//				alert(pos + "は全角文字を入力してください。");
				chkAlert(msg);
			}
			return false;
		}
	}
	if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/**
 * バイト数チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @param	object		obj		対象項目
 * @param	object		full	文字数基準フル桁入力チェック対象項目(対象外の場合は指定なし)
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	チェックはmaxlength指定を使用
 */
function chk_byte(msg, val, indis, obj, full) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (!obj.maxLength) return false;
	var cnt = 0;
	for (i = 0; i < val.length; i++) {
		var c = val.charCodeAt(i);
		// Shift_JIS: 0x0 ～ 0x80, 0xa0 , 0xa1 ～ 0xdf , 0xfd ～ 0xff
		// Unicode : 0x0 ～ 0x80, 0xf8f0, 0xff61 ～ 0xff9f, 0xf8f1 ～ 0xf8f3
		if ( (c >= 0x0 && c < 0x81) || (c == 0xf8f0) || (c >= 0xff61 && c < 0xffa0) || (c >= 0xf8f1 && c < 0xf8f4)) {
			cnt += 1;
		} else {
			cnt += 2;
		}
	}
	if (cnt > obj.maxLength) {
		if (msg != "") {
//			alert(pos + "は" + obj.maxLength + "byte以内で入力してください。");
			chkAlert(msg);
		}
		return false;
	}
	if (!chk_fullLength(msg, val, full)) return false;
	return true;
}
/*----------------------------
  DATEチェック
-----------------------------*/
/**
 * 暦日チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	区切り文字は ｢-｣｢/｣｢.｣ のいづれでも可
 */
function chk_date(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
//	var msg = pos + "の日付指定が不正です。";
	var match;
	var y, m, d;
	if (match = val.match(/^([0-9]+)[\-\/\.]([0-9]+)[\-\/\.]([0-9]+)$/i)) {
		y = eval(match[1]);
		if ((y < 1900) || (y > 3000)) {
			if (msg != "") {
				chkAlert(msg);
			}
			return false;
		}
	} else {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	ddTbl = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
	if (((y%4)==0 && (y%100)!=0) || (y%400)==0) ddTbl[1] = 29;
	m = eval(match[2]);
	if ((m < 1) || (m > 12)) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	d = eval(match[3]);
	if ((d < 1) || (d > ddTbl[m-1])) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
/**
 * 年月チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	区切り文字は ｢-｣｢/｣｢.｣ のいづれでも可
 */
function chk_month(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
//	var msg = pos + "の年月指定が不正です。";
	var match;
	var y, m;
	if (match = val.match(/^([0-9]+)[\-\/\.]([0-9]+)$/i)) {
		y = eval(match[1]);
		if ((y < 1900) || (y > 3000)) {
			if (msg != "") {
				chkAlert(msg);
			}
			return false;
		}
	} else {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	m = eval(match[2]);
	if ((m < 1) || (m > 12)) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
/**
 * 日チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_day(msg, val, indis) {
	if (chk_number(msg, val, indis) == false) return false;
	if (Trim(val) != "") {
		if (eval(val) < 1 || eval(val) > 31) {
			if (msg != "") {
//				alert(pos + "の日付が不正です。");
				chkAlert(msg);
			}
			return false;
		}
	}
	return true;
}
/**
 * 時間チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	HH:NN形式
 */
function chk_time(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (val.search(/^([01][0-9]|2[0-3]):([0-5][0-9])$/i) == -1) {
		if (msg != "") {
//			alert(pos + "の時間指定が不正です。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
/**
 * 暦日&時間チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	区切り文字は ｢-｣｢/｣｢.｣ のいづれでも可
 */
function chk_dttime(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
//	var msg = pos + "の日付及び時間指定が不正です。";
	var match;
	var y, m, d;
	if (match = val.match(/^([0-9]+)[\-\/\.]([0-9]+)[\-\/\.]([0-9]+)\s([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/i)) {
		y = eval(match[1]);
		if ((y < 1900) || (y > 3000)) {
			if (msg != "") {
				chkAlert(msg);
			}
			return false;
		}
	} else {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	ddTbl = new Array(31,28,31,30,31,30,31,31,30,31,30,31);
	if (((y%4)==0 && (y%100)!=0) || (y%400)==0) ddTbl[1] = 29;
	m = eval(match[2]);
	if ((m < 1) || (m > 12)) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	d = eval(match[3]);
	if ((d < 1) || (d > ddTbl[m-1])) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
/**
 * 日付大小チェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		date1	日付文字列1 小さいのが正しいはずの日付
 * @param	string		date2	日付文字列2 大きいのが正しいはずの日付
 * @param	boolean		equal	完全一致の日付を含むか否か
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	日付文字列はYYYY/MM/DD形式
 */
function compare_date(msg, date1, date2, equal) {
	if (!equal) equal = false;
	var ary1 = Trim(date1).split("/");
	var ary2 = Trim(date2).split("/");

	var dt1 = new Date(eval(removeTopZero(ary1[0])), eval(removeTopZero(ary1[1])) - 1, eval(removeTopZero(ary1[2])));
	var dt2 = new Date(eval(removeTopZero(ary2[0])), eval(removeTopZero(ary2[1])) - 1, eval(removeTopZero(ary2[2])));

	var ret = (equal) ? (dt1.getTime() >= dt2.getTime()) : (dt1.getTime() > dt2.getTime());
	if (ret) {
		if (msg != "") {
//			alert(pos + "の開始・終了が不正です。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/*----------------------------
  形式チェック
-----------------------------*/
/**
 * メールアドレスチェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_mail(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
//	if (!val.match(/^[\w_-]+\S*@\S+\.\S+$/)){
	if (!val.match(/^[\w\._-]+@+[^<>\s]+[\w_-]+\.+[\w_-]+$/)){
		if (msg != "") {
//			alert(pos + "の入力に誤りがあります。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
/**
 * URLチェック
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_url(msg, val, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	if (!val.match(/(http|https|ftp):\/\/[!#-9A-~]+\.+[a-z0-9]+/i)) {
		if (msg != "") {
//			alert(pos + "の入力に誤りがあります。");
			chkAlert(msg);
		}
		return false;
	}
	return true;
}


/**
 * 半角英数が混在しているかをチェックする
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chkNumberAndAlpha( msg, val) {
	if (!val.match(/[0-9].*[a-zA-Z]|[a-zA-Z].*[0-9]/)) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/**
 * 半角英字かチェックする
 * @access	public
 * @param	string	$target		チェック対象文字列
 * @return	boolean				ture-チェックOK / false-チェックNG
 */
function chk_alpha( msg, val){
	if (!val.match(/^[a-zA-Z]+$/)) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}

/**
 * パターンチェック 2020/04/20 [ADD]
 * @access	public
 * @param	string		msg		アラート文(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @param	string		pattern	マッチパターン
 * @param	boolean		indis	true - 必須 / false(デフォルト) - 未入力可
 * @return	boolean				true - チェックOK / false - チェックNG
 * @desc	なし
 */
function chk_pattern(msg, val, pattern, indis) {
	if (indis) {
		if (chk_indis(msg, val) == false) return false;
	} else {
		if (chk_indis("", val) == false) return true;
	}
	reg = new RegExp(pattern);

	if (val.search(reg) == -1) {
		if (msg != "") {
			chkAlert(msg);
		}
		return false;
	}
	return true;
}
