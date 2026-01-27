/**
 * @fileOverview
 * レイヤー操作ライブラリ
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
 * @version   1.0
 * @since     2003/11/01 ver1.0 初版作成 岡本順子
 * @using
 * @desc
 */

/*----------------------------
  表示制御
-----------------------------*/
/**
 * レイヤーの表示確認
 * @access	public
 * @param	string		id		レイヤー名
 * @return	boolean				true - 表示 / false - 非表示
 * @desc	なし
 */
function isDisplayLAYER(id) {
	var ret = false;
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		if (document.getElementById(id).style.visibility == "visible") ret = true;
	} else if (document.all) { 			// IE4用
		if (document.all(id).style.visibility == "visible") ret = true;
	} else if (document.layers) {		// NN4用
		if (document.layers[id].visibility == "show") ret = true;
	}
	return ret;
}
/**
 * レイヤーの表示
 * @access	public
 * @param	string		id		レイヤー名
 * @return	なし
 * @desc	なし
 */
function showLAYER(id) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		document.getElementById(id).style.visibility = "visible";
	} else if (document.all) { 			// IE4用
		document.all(id).style.visibility = "visible";
	} else if (document.layers) {		// NN4用
		document.layers[id].visibility = "show";
	}
}
/**
 * レイヤーの非表示
 * @access	public
 * @param	string		id		レイヤー名
 * @return	なし
 * @desc	なし
 */
function hideLAYER(id) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		document.getElementById(id).style.visibility = "hidden";
	} else if (document.all) { 			// IE4用
		document.all(id).style.visibility = "hidden";
	} else if (document.layers) {		// NN4用
		document.layers[id].visibility = "hide";
	}
}
/**
 * レイヤーの移動
 * @access	public
 * @param	string		id		レイヤー名
 * @param	integer		x		Window左辺からの水平方向px距離
 * @param	integer		y		Window上辺からの垂直方向px距離
 * @return	なし
 * @desc	なし
 */
function moveLAYER(id, x, y) {
	if (document.getElementById){		// NN6以上,Mozilla,IE5以上用
		document.getElementById(id).style.left = x;
		document.getElementById(id).style.top = y;
	} else if(document.all) { 			// IE4用
		document.all(id).style.pixelLeft = x;
		document.all(id).style.pixelTop = y;
	} else if(document.layers) {		// NN4用
		document.layers[id].moveTo(x, y);
	}
}

/*----------------------------
  プロパティ操作
-----------------------------*/
/**
 * レイヤーの色変更
 * @access	public
 * @param	string		id		レイヤー名
 * @param	string		col		色
 * @return	なし
 * @desc	なし
 */
function changeColorLAYER(id, col) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		document.getElementById(id).style.backgroundColor = col;
	} else if (document.all) { 			// IE4用
		document.all(id).style.backgroundColor = col;
	} else if (document.layers) {		// NN4用
		document.layers[id].backgroundColor = col;
	}
}
/**
 * レイヤー内文字列書換
 * @access	public
 * @param	string		id		レイヤー名
 * @param	string		val		書換文字列
 * @return	なし
 * @desc	なし
 */
function changeInnerLAYER(id, val) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		if (document.getElementById(id)) document.getElementById(id).innerHTML = val;
	} else if (document.all) { 			// IE4用
		if (document.all(id)) document.all(id).innerHTML = val;
	} else if (document.layers) {		// NN4用
		if (document.layers[id]) {
			document.layers[id].document.open();
			document.layers[id].document.write(val);
			document.layers[id].document.close();
		}
	}
}
/**
 * レイヤー内文字列書換
 * @access	public
 * @param	string		id		レイヤー名
 * @param	string		val		書換文字列
 * @return	なし
 * @desc	2009/03/15 Add J.Okamoto
 */
function changeInnerText(id, val) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		if (document.getElementById(id)) document.getElementById(id).innerText = val;
	} else if (document.all) { 			// IE4用
		if (document.all(id)) document.all(id).innerText = val;
	} else if (document.layers) {		// NN4用
		if (document.layers[id]) {
			document.layers[id].document.open();
			document.layers[id].document.write(val);
			document.layers[id].document.close();
		}
	}
}

/*----------------------------
  オブジェクト取得
-----------------------------*/
/**
 * レイヤー取得
 * @access	public
 * @param	string		id		レイヤー名
 * @return	object              レイヤーオブジェクト
 * @desc	なし
 */
function getLAYER(id) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		return document.getElementById(id);
	} else if (document.all) { 			// IE4用
		return document.all(id);
	} else if (document.layers) {		// NN4用
		if (document.layers[id]) return document.layers[id];
	}
	return false;
}
/**
 * レイヤー存在確認
 * @access	public
 * @param	string		id		レイヤー名
 * @return	boolean				true - 存在 / false - 未存在
 * @desc	なし
 */
function existsLAYER(id) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		if (document.getElementById(id)) return true;
	} else if (document.all) { 			// IE4用
		if (document.all(id)) return true;
	} else if (document.layers) {		// NN4用
		if (document.layers[id]) return true;
	}
	return false;
}
/**
 * レイヤー内文字列取得
 * @access	public
 * @param	string		id		レイヤー名
 * @return	string				レイヤー内文字列
 * @desc	なし
 */
function getInnerLAYER(id) {
	if (document.getElementById) {		// NN6以上,Mozilla,IE5以上用
		return document.getElementById(id).innerHTML;
	} else if (document.all) { 			// IE4用
		return document.all(id).innerHTML;
	} else if (document.layers) {		// NN4用
	}
	return "";
}
