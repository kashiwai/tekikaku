/**
 * @fileOverview
 * システム共通ライブラリ
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
 * @since     2016/09/06 ver2.0 鶴野美香 ページ遷移アンカー変更、ソートアイコンを画像からCSSでの実装へ変更
 * @using
 * @desc
 */

/*----------------------------
  システム独自関数
-----------------------------*/
/**
 * ページ遷移アンカーの描写
 * @access	public
 * @param	なし
 * @return	なし
 * @desc	なし
 */
function writePageMove() {
	if (!window.document.MAINFORM["P"])    return;
	if (!window.document.MAINFORM["ALLP"]) return;

	var span = 3;		// 現在ページを基準に前後何ページ分まで表示するか
	var nowp = eval(window.document.MAINFORM["P"].value);		// 現在ページ番号
	var allp = eval(window.document.MAINFORM["ALLP"].value);	// 総ページ数

	// 現在ページ番号を基準にMAXで前後N件ずつ表示
	var min = (nowp > span) ? nowp - span : 1;
	var max = (allp < nowp + span) ? allp : nowp + span;

	var disp = "";
	disp += "<li><a href=\"javascript:movePage(-1);\" aria-label=\"前のページへ\"><span aria-hidden=\"true\">«</span></a></li>";
	if (min > 1) disp += "<li><a href=\"javascript:void(0);\">...</a></li>";
	for (i=min;i<=max;i++) {
		if (i == nowp) {
			// 現在ページ
			disp += "<li class=\"active\"><a href=\"javascript:void(0);\">" + i + "</a></li>";
		} else {
			disp += "<li><a href=\"javascript:movePage(" + (i - nowp) + ");\">" + i + "</a></li>";
		}
	}
	if (max < allp) disp += "<li><a href=\"javascript:void(0);\">...</a></li>";
	disp += "<li><a href=\"javascript:movePage(1);\" aria-label=\"次のページへ\"><span aria-hidden=\"true\">»</span></a></li>";

	if (allp == 1) {
		// 全体で1ページしか無い場合はページ遷移をulごと非表示
		// レイアウト崩れを防ぐ為、ダミーの「<li>」を記述してvisibilityで非表示にする
		disp = "<li><a href=\"#\">1</a></li>";
		if (!!document.getElementById("PAGEMOVE_TOP")) document.getElementById("PAGEMOVE_TOP").style.visibility = "hidden";		// 上部用レイヤー
		if (!!document.getElementById("PAGEMOVE_BTM")) document.getElementById("PAGEMOVE_BTM").style.visibility = "hidden";		// 下部用レイヤー
	}
	if (!!document.getElementById("PAGEMOVE_TOP")) changeInnerLAYER("PAGEMOVE_TOP", disp);
	if (!!document.getElementById("PAGEMOVE_BTM")) changeInnerLAYER("PAGEMOVE_BTM", disp);
}

/**
 * 並び替えアイコンの差替え
 * @access	public
 * @param	なし
 * @return	なし
 * @desc	なし
 * @info	現在適用されているソート項目に合致するaタグの名前に「active」というCSSクラス名を付与しているだけなので
 *			デザイン等はCSS側で実装すること
 */
function sortImageActive() {
	if (!window.document.MAINFORM["ODR"]) return;
	var odr = window.document.MAINFORM["ODR"].value;
	var a = document.getElementsByTagName("a");
	for (i=0; i<a.length; i++) {
		if (a[i].name == odr) {
			a[i].className = a[i].className + " active";
		}
	}
}

/*
 * 一覧内の画像切替
 * @access	public
 * @param	integer		row		行番号
 * @param	integer		col		列番号
 * @return	なし
 * @info    なし
 */
function changeThum(row,col) {
	var i = 1;
	while(document.getElementById("thum-" + row + "-" + i)) {
		if (i == col) {
			document.getElementById("thum-" + row + "-" + i).style.display = "";
			
			var elems = $("#toggle-" + row).children("li");
			for (j = 0; j < elems.length; j++) {
				if ((j + 1) == col) {
					elems[j].className = "active";
				} else {
					elems[j].className = "";
				}
			}
		} else {
			document.getElementById("thum-" + row + "-" + i).style.display = "none";
		}
		i++;
	}
}

/*----------------------------
  項目クリア関数
-----------------------------*/
/**
 * NO・名称セット項目クリア
 * @access	public
 * @param	string		pos		対象項目名接頭句
 * @return	なし
 * @desc	対象項目を持つフォーム名は[MAINFORM]固定
 * 			posで指定された接頭句を持つNO･NMの各項目の値をクリアします
 */
function clear_both(pos) {
	var frm = window.document.MAINFORM;
	frm[pos + "NO"].value = "";
	frm[pos + "NM"].value = "";
}
/**
 * 単一項目クリア
 * @access	public
 * @param	string		pos		対象項目名
 * @return	なし
 * @desc	対象項目を持つフォーム名は[MAINFORM]固定
 * 			posで指定された項目の値をクリアします
 */
function clear_one(pos) {
	var frm = window.document.MAINFORM;
	frm[pos].value = "";
}
/**
 * 選択項目クリア
 * @access	public
 * @param	string		pos		対象項目名
 * @return	なし
 * @desc	対象項目を持つフォーム名は[MAINFORM]固定
 * 			posで指定されたSelect項目の値をクリアします
 */
function clear_select(pos) {
	var frm = window.document.MAINFORM;
	frm[pos].selectedIndex = -1;
}

/**
 * （IE11）URLSearchParams定義
 * @access	public
 * @param	string		searchString		document.location.search
 * @return	なし
 * @desc	IE11でURLSearchParamsが使えないので、IE11の場合のみ再定義
 * 			メソッドとしては、toStringとsetのみ定義。
 */
window.URLSearchParams = window.URLSearchParams || function (searchString) {
	var self = this;
	self.searchString = (searchString!="")? searchString.split('?')[1]:"";
	//set
	self.set = function (name, value) {
		var addstr = name +"="+ value;
		if( self.searchString != "") addstr = "&" + addstr;
		self.searchString = self.searchString + addstr;
	}
	//toString
	self.toString = function(){
		return self.searchString;
	}
}

