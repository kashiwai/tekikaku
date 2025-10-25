/**
 * @fileOverview
 * Formオブジェクト操作ライブラリ
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
 * @since     2003/11/01 ver1.0 初版作成 岡本順子
 * @since     2007/11/15 ver1.1 関数追加 岡本順子  [noEnter,topFocus]
 * @using     ctl_string.js
 * @desc
 */

/*----------------------------
  フォーカス制御
-----------------------------*/
/**
 * フォーカス制御共通ハンドラ
 * @access	public
 * @param	なし
 * @return	なし
 * @desc	Enterキー押下で次入力項目へフォーカス移動を行う(※IE系のみ有効)
 */
function noEnter() {
	var appName = navigator.appName.toUpperCase();
	if (appName.indexOf("MICROSOFT") < 0) return;

	if (window.event.keyCode == 13) {
		var trap = true;
		if (document.activeElement.type) {
			if (document.activeElement.type == "textarea") trap = false;
			if (document.activeElement.type == "submit")   trap = false;
			if (document.activeElement.type == "button")   trap = false;
			if (document.activeElement.type == "reset")    trap = false;
		}

		if (!trap) return;
		for (f=0;f<window.document.forms.length;f++) {
			var frm = window.document.forms[f];
			var nextFind = false;
			for (i=0;i<frm.elements.length;i++) {
				var obj = frm.elements[i];
				if (!nextFind) {
					if (obj == document.activeElement) {
						nextFind = true;
					}
				} else if (obj.type) {
					var setFocus = true;
					if (obj.type == "hidden") setFocus = false;
					if (obj.type == "button") setFocus = false;
					if (obj.type == "reset")  setFocus = false;
					if (obj.disabled) setFocus = false;
					if (obj.readOnly) setFocus = false;
					if (setFocus) {
						obj.focus();
						break;
					}
				}
			}
			if (nextFind) break;
		}
		window.event.returnValue = false;
	}
	
	if (window.event.keyCode == 8) {
		var trapBS = true;
		if (document.activeElement.type) {
			if (document.activeElement.type == "text")     trapBS = false;
			if (document.activeElement.type == "textarea") trapBS = false;
			if (document.activeElement.type == "password") trapBS = false;
		}
    	if (trapBS) {
    		window.event.returnValue = false;
		}
	}
}
window.document.onkeydown = noEnter
/**
 * 先頭項目フォーカスセット
 * @access	public
 * @param	なし
 * @return	なし
 * @desc	なし
 */
function topFocus() {
	for (i=0;i<window.document.forms.length;i++) {
		var frm = window.document.forms[i];
		var index = -1;
		for (j=0;j<frm.elements.length;j++) {
			obj = frm.elements[j];
			if (obj.type) {
				if (obj.type != "hidden" && !obj.readOnly && !obj.disabled) {
					index = j;
					break;
				}
			}
		}
		if (index >= 0) {
			frm.elements[index].focus();
			break;
		}
	}
}
/**
 * フォーカス取得処理
 * @access	public
 * @param	object		obj		フォーカス取得オブジェクト
 * @return	なし
 * @desc	数値項目でカンマ区切り編集を行いたい項目はCSSクラス:moneyを適用すること
 */
function on_focus(obj) {
	change_color(obj, 'orange');
	// 数値入力項目の場合はカンマ削除
	if (obj.className == "money") obj.value = deleteComma(obj.value);
}
/**
 * フォーカス喪失処理
 * @access	public
 * @param	object		obj		フォーカス喪失オブジェクト
 * @return	なし
 * @desc	数値項目でカンマ区切り編集を行いたい項目はCSSクラス:moneyを適用すること
 *          また、CSSクラス:dtはyyyy/mm/ddの入力項目、CSSクラス:dtmはyyyy/mm/dd hh:nnの入力項目とみなし、
 *          年入力省略時は補完を行う
 */
function on_blur(obj) {
	change_color(obj, 'white');
	// 数値入力項目の場合はカンマ挿入
	if (obj.className == "money") obj.value = insertComma(obj.value);
	// 日付入力項目の場合は年省略時補間
	var match;
	if (obj.className == "dt") {
		var val = obj.value;
		if (match = val.match(/^([0-9]+)\/([0-9]+)\/([0-9]+)$/i)) {
			var y = eval(match[1]);
			if (y < 100) y += 2000;
			val = y + "/" + match[2] + "/" + match[3];
			if (!chk_date("", val, false)) return;
			obj.value = val;
		} else if (match = val.match(/^([0-9]+)\/([0-9]+)$/i)) {
			var Now = new Date();
			val = Now.getFullYear() + "/" + match[1] + "/" + match[2];
			if (!chk_date("", val, false)) return;
			obj.value = val;
		}
	}
	if (obj.className == "dtm") {
		var val = obj.value;
		if (match = val.match(/^([0-9]+)\/([0-9]+)\/([0-9]+)\s([01][0-9]|2[0-3]):([0-5][0-9])$/i)) {
			var y = eval(match[1]);
			if (y < 100) y += 2000;
			val = y + "/" + match[2] + "/" + match[3] + " " + match[4] + ":" + match[5];
			if (!chk_dttime("", val, false)) return;
			obj.value = val;
		} else if (match = val.match(/^([0-9]+)\/([0-9]+)\s([01][0-9]|2[0-3]):([0-5][0-9])$/i)) {
			var Now = new Date();
			val = Now.getFullYear() + "/" + match[1] + "/" + match[2] + " " + match[3] + ":" + match[4];
			if (!chk_dttime("", val, false)) return;
			obj.value = val;
		}
	}

}

/*----------------------------
  背景色操作
-----------------------------*/
/**
 * 入力項目色変え処理
 * @access	public
 * @param	object		obj		対象入力項目オブジェクト
 * @param	string		col		色
 * @return	なし
 * @desc	なし
 */
function change_color(obj, col) {
	if (obj.type == "text" || obj.type == "textarea" || obj.type == "password") {
		if (obj.readOnly) return;
		obj.style.backgroundColor = col;
	}
}

/*----------------------------
  一括操作系関数
-----------------------------*/
/**
 * 一括カンマ挿入処理
 * @access	public
 * @param	object		frm		カンマ挿入対象フォーム
 * @return	なし
 * @desc	なし
 */
function insertCommaOnForm(frm) {
	var obj;
	for (i=0;i<frm.elements.length;i++) {
		obj = frm.elements[i];
		// カンマ付数値入力項目の場合はカンマ挿入
		if (obj.className == "money") obj.value = insertComma(obj.value);
	}
}
/**
 * 一括カンマ削除処理
 * @access	public
 * @param	object		frm		カンマ削除対象フォーム
 * @return	なし
 * @desc	なし
 */
function deleteCommaOnForm(frm) {
	var obj;
	for (i=0;i<frm.elements.length;i++) {
		obj = frm.elements[i];
		// カンマ付入力項目の場合はカンマ削除
		if (obj.className == "money") obj.value = deleteComma(obj.value);
	}
}

/*----------------------------
  SelectBox操作
-----------------------------*/
/**
 * SelectBox項目追加
 * @access	public
 * @param	object		obj		対象SelectBoxオブジェクト
 * @param	string		txt		text
 * @param	string		val		value
 * @return	なし
 * @desc	なし
 */
function add_option(obj, txt, val) {
	obj.options[obj.options.length] = new Option(txt, val);
	if (document.layers) {
		top.resizeBy(-10, -10);
		top.resizeBy(10, 10);
	}
}
/**
 * SelectBox選択項目順序移動
 * @access	public
 * @param	object		obj		対象SelectBoxオブジェクト
 * @param	int			span	移動Span
 * @return	なし
 * @desc	なし
 */
function move_option(obj, span) {
	if (obj.selectedIndex == -1) return;
	if (span == 0) return;

	var nowidx = obj.selectedIndex;
	var newidx = nowidx + span;
	if (newidx < 0) newidx = 0;
	if (newidx > obj.options.length - 1) newidx = obj.options.length - 1;

	var txt = obj.options[nowidx].text;
	var val = obj.options[nowidx].value;
	// 現在の位置よりも上へ移動
	if (newidx < nowidx) {
		// 移動先位置～現在位置の直前までの項目を下へずらす
		for (i=nowidx; i>newidx; i--) {
			obj.options[i] = new Option(obj.options[i-1].text, obj.options[i-1].value);
		}
	// 現在の位置よりも下へ移動
	} else {
		// 現在位置の直後～移動先位置までの項目を上へずらす
		for (i=nowidx; i<newidx; i++) {
			obj.options[i] = new Option(obj.options[i+1].text, obj.options[i+1].value);
		}
	}
	// 新しい位置へ自身を配置
	obj.options[newidx] = new Option(txt, val);
	obj.selectedIndex = newidx;
}
/**
 * SelectBox選択項目削除
 * @access	public
 * @param	object		obj		対象SelectBoxオブジェクト
 * @return	なし
 * @desc	なし
 */
function del_option(obj) {
	if (obj.selectedIndex == -1) return;
	obj.options[obj.selectedIndex] = null;
}
/**
 * SelectBox項目全削除
 * @access	public
 * @param	object		obj		対象SelectBoxオブジェクト
 * @return	なし
 * @desc	なし
 */
function del_option_all(obj) {
	obj.options.length = 0;
}
