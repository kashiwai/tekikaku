/* 
 * ウィンドウ操作ライブラリ(クロスブラウザ対応)
 * 
 * @package 
 * @author  岡本 順子
 * @vervion JavaScript
 *          
 * @since   2003/11/01 ver1.0 初版作成 岡本順子
 * @info    
 */

/*
 * サブウィンドウOpen
 * @access	public
 * @param	string		src		対象アドレス
 * @param	string		pram	パラメータ(省略可、複数時は｢&｣で連結)
 * @param	string		name	ウィンドウ名(省略可)
 * @param	string		width	width(省略可)
 * @param	string		height	height(省略可)
 * @param	string		scroll	スクロール可否(省略可、指定時は｢yes/no｣)
 * @return	なし
 * @info    なし
 */
function win_open(src, pram, name, width, height, scroll) {
	if (!pram) pram = "";
	if (!name || name=="") name = "win_sub";
	if (!width || width=="") width = "780";
	if (!height || height=="") height = "520";
	if (!scroll || scroll=="") scroll = "yes";

	var target = src;
	if (pram != "") target += "?" + pram;
	var option = 'width=' + width;
	option += ',height=' + height;
	option += ',scrollbars=' + scroll;
	option += ',resizable=yes';
	option += ',location=no';		// 運用時はno、デバック時はyes
	option += ',status=no';			// 運用時はno、デバック時はyes
	option += ',toolbar=no';
	option += ',menubar=no';
	//*** 2007/11/15 Upd Okamoto Start ***
	//option += ',top=0';
	//option += ',left=0';
	var top  = Math.floor((screen.height - parseInt(width))  / 2);
	var left = Math.floor((screen.width  - parseInt(height)) / 2);
	if (top  < 0) top  = 0;
	if (left < 0) left = 0;
	option += ',top=' + top;
	option += ',left=' + left;
	//*** 2007/11/13 Upd End *************
	var subwin = window.open(target, name, option);
	subwin.window.focus();
}
/*
 * 親ウィンドウ確認
 * @access	public
 * @param	なし
 * @return	なし
 * @info    なし
 */
function is_opener() {
	var ua = navigator.userAgent;
	if (!!window.opener) {
		if (ua.indexOf('MSIE 4')!=-1 && ua.indexOf('Win')!=-1) {
			return !window.opener.closed;
		} else {
			return typeof window.opener.document  == 'object';
		}
	} else {
		return false;
	}
}
/*
 * 親ウィンドウリロード
 * @access	public
 * @param	なし
 * @return	なし
 * @info    なし
 */
function reload_mainwin(close) {
	if (is_opener()) {
		window.opener.location.reload();
	}
	if (close) window.close();
}
