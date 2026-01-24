/**
 * @fileOverview
 * 文字列操作ライブラリ
 * 
 * (C)SmartRams Corp. 2008-2011 All Rights Reserved．
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
 * @version   1.0
 * @since     2008/12/30 ver1.0 初版作成 岡本順子  旧func_common.jsから文字列操作関連関数を切り出し
 * @using
 * @desc
 */

/*----------------------------
  汎用
-----------------------------*/
/**
 * RTrim
 * @access	public
 * @param	string		val		対象文字列
 * @return	string				編集後文字列
 * @desc	なし
 */
function RTrim(val) {
	var nLoop = 0;
	var ret = val;
	while (nLoop < val.length) {
		if ((ret.substring(ret.length - 1, ret.length) == " ") || (ret.substring(ret.length - 1, ret.length) == "　")) {
			ret = val.substring(0, val.length - (nLoop + 1));
		} else {
			break;
		}
		nLoop++;
	}
	return ret;
}
/**
 * LTrim
 * @access	public
 * @param	string		val		対象文字列
 * @return	string				編集後文字列
 * @desc	なし
 */
function LTrim(val) {
	var nLoop = 0;
	var ret = val;
	while (nLoop < val.length) {
		if ((ret.substring(0, 1) == " ") || (ret.substring(0, 1) == "　")) {
			ret = val.substring(nLoop + 1, val.length);
		} else {
			break;
		}
		nLoop++;
	}
	return ret;
}
/**
 * Trim
 * @access	public
 * @param	string		val		対象文字列
 * @return	string				編集後文字列
 * @desc	なし
 */
function Trim(val) {
	var ret = val;
	ret = LTrim(ret);
	ret = RTrim(ret);
	return ret;
}
/**
 * PadLeft
 * @access	public
 * @param	string		s		対象文字列
 * @param	integer		length	長さ
 * @param	string		c		詰める文字列
 * @return	string				編集後文字列
 * @desc	このインスタンス内の文字を右寄せし、指定した文字列の文字数になるまで左側に指定文字を埋め込みます。
 */
function PadLeft(s, length, c) {
	var ret = s;
	var i;
	for (i=1; i<=length; i++) {
		s = c + s;
	}
	ret = s.substr(s.length - length, length);
	return ret;
}
/**
 * PadRight
 * @access	public
 * @param	string		s		対象文字列
 * @param	integer		length	長さ
 * @param	string		c		詰める文字列
 * @return	string				編集後文字列
 * @desc	このインスタンス内の文字を左寄せし、指定した文字列の文字数になるまで右側に指定文字を埋め込みます。
 */
function PadRight(s, length, c) {
	var ret = s;
	var i;
	for (i=1; i<=length; i++) {
		s =+ c;
	}
	ret = s.substr(0, length);
	return ret;
}

/*----------------------------
  数値関連
-----------------------------*/
/**
 * 数字の全角→半角変換
 * @access	public
 * @param	object		pos		項目名(指定時は自動アラート)
 * @param	string		val		対象文字列
 * @return	string				変換後文字列
 * @desc	なし
 */
function convertNumZen2Han(val) {
	zenary = new Array("０","１","２","３","４","５","６","７","８","９","－","（","）");
	hanary = new Array("0","1","2","3","4","5","6","7","8","9","-","(",")");
	var i ;
	str = val;
	for (i=0; i< zenary.length; i++) {
		tmpary = str.split(zenary[i]) ;
		str = tmpary.join(hanary[i]) ;
	}
	return str ;
}
/**
 * 先頭から０の除去
 * @access	public
 * @param	string		val		対象文字列
 * @return	string				変換後文字列
 * @desc	なし
 */
function removeTopZero(val) {
	if (val.length <= 1) return val;
	var wk = "";
	var s = "";
	for (i=0; i<val.length; i++) {
		s = val.charAt(i);
		if (wk.length > 0 || s != "0") wk += s;
	}
	return wk;
}
/**
 * カンマ挿入
 * @access	public
 * @param	string		sourceStr	対象文字列
 * @return	string					カンマ挿入後文字列
 * @desc	なし
 */
function insertComma(sourceStr) {
	var destStr = sourceStr;
	var tmpStr = "";
	while (destStr != (tmpStr = destStr.replace(/^([+-]?\d+)(\d\d\d)/,"$1,$2"))) {
		destStr = tmpStr;
	}
	return destStr;
}
/**
 * カンマ削除
 * @access	public
 * @param	string		w	対象文字列
 * @return	string			カンマ削除後文字列
 * @desc	なし
 */
function deleteComma(w) {
	var z = w.replace(/,/g,"");
	return (z);
}
