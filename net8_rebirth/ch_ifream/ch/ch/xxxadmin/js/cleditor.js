/* 
 * 
 * 
 * @package
 * @version
 * 
 * @since	2017/01/25 ver1.0 初版作成
 * @info	CLEditor (http://premiumsoftware.net/cleditor/)
 *          CLEditorの設定まとめ (jQueryが必要なので必ずインクルードしておくこと)
 *          エディタを反映させたいテキストエリアのclassに「editor」を指定
 */


$(function() {
	//==============
	// editor反映
	// デフォ値から変更する場合のみ指定 (コメントアウト部がデフォルト設定)
	//==============
	$(".editor").cleditor({
//		width:     // width not including margins, borders or padding
//					500,
//		height:    // height not including margins, borders or padding
//					250,
		controls:  // controls to add to the toolbar
//					使用するコントロールのみ記述「 | 」はコントロールの区切り線
//					"bold italic underline strikethrough subscript superscript | font size " +
//					"style | color highlight removeformat | bullets numbering | outdent " +
//					"indent | alignleft center alignright justify | undo redo | " +
//					"rule image link unlink | cut copy paste pastetext | print source",
//					--------------------------------------------------------------------------
//					↓ カスタム ↓
					"bold italic underline strikethrough | font size " +
					"style | color highlight removeformat | " +
					"alignleft center alignright | undo redo | " +
					"image link unlink | cut copy paste pastetext | source",
//		colors:    // colors in the color popup
//					"FFF FCC FC9 FF9 FFC 9F9 9FF CFF CCF FCF " +
//					"CCC F66 F96 FF6 FF3 6F9 3FF 6FF 99F F9F " +
//					"BBB F00 F90 FC6 FF0 3F3 6CC 3CF 66C C6C " +
//					"999 C00 F60 FC3 FC0 3C0 0CC 36F 63F C3C " +
//					"666 900 C60 C93 990 090 399 33F 60C 939 " +
//					"333 600 930 963 660 060 366 009 339 636 " +
//					"000 300 630 633 330 030 033 006 309 303",
//		fonts:     // font names in the font popup
//					"Arial,Arial Black,Comic Sans MS,Courier New,Narrow,Garamond," +
//					"Georgia,Impact,Sans Serif,Serif,Tahoma,Trebuchet MS,Verdana",
//		sizes:     // sizes in the font size popup
//					"1,2,3,4,5,6,7",
//		styles:    // styles in the style popup
//					[["Paragraph", "<p>"], ["Header 1", "<h1>"], ["Header 2", "<h2>"],
//					["Header 3", "<h3>"],  ["Header 4","<h4>"],  ["Header 5","<h5>"],
//					["Header 6","<h6>"]],
//		useCSS:    // use CSS to style HTML when possible (not supported in ie)
//					false,
//		docType:   // Document type contained within the editor
//					'<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">',
//		docCSSFile:// CSS file used to style the document contained within the editor
//					"",
		bodyStyle: // style to assign to document body contained within the editor
//					"margin:4px; font:10pt Arial,Verdana; cursor:text"
//					--------------------------------------------------------------------------
//					↓ カスタム ↓
					"margin:4px; font:10pt 'Helvetica Neue',Helvetica,'ヒラギノ角ゴ ProN W3','Hiragino Kaku Gothic ProN','メイリオ',Meiryo,sans-serif; cursor:text"
	});

});


/*
 * 選択位置へ文字列挿入
 * @access	public
 * @param	string		str		挿入文字列
 * @return	なし
 * @info    対象項目を持つフォーム名は[MAINFORM]固定
 */
function insertStrCLEditor(str,objName) {

	var editor = $(".editor").cleditor()[0];

	// 表示モードにより挿入処理が異なる
	if (editor.sourceMode()) {
		// 表示がソースモードの場合は通常のtextareaの処理と同じ(最後にiframeに反映)
		var target = window.document.MAINFORM[objName];
		target.focus();

		if (document.selection != null){
			var sel = document.selection.createRange().text;
			if (sel) {
				document.selection.createRange().text = str;
			} else {
				document.selection.createRange().text = str;
			}
		} else if (target.selectionStart || target.selectionStart == '0') {
			var s = target.selectionStart;
			var e = target.selectionEnd;
			var str2 = target.value.substring(s, e);
			if (str2) {
				target.value = target.value.substring(0, s) + str2 + target.value.substring(e, target.value.length);
			} else {
				target.value = target.value.substring(0, s) + str + target.value.substring(e, target.value.length);
			}
			target.focus();
		} else {
			target.value += str;
		}

		// textareaの内容をiframeに反映
		editor.updateFrame();

	} else {
		// 表示がHTMLモードの場合はiframeに対しての挿入となる
		if (document.all) {
			// IE用
			insertHTMLforIE(str);
		} else {
			// FF等その他
			insertHTMLForMozilla(str);
		}
	}
}

function insertHTMLForMozilla(html) {
	// iframeのdocumentとwindowを取得
	var clWin = $(".cleditorMain iframe")[0].contentWindow;
	var clDoc = $(".cleditorMain iframe")[0].contentDocument;

	var fragment = clDoc.createDocumentFragment();
	var div = clDoc.createElement("div");
	div.innerHTML = html;

	// div配下のNodeをfragmentに移動
	while (div.firstChild) {
		fragment.appendChild(div.firstChild);
	}

	var selection = clWin.getSelection();
	range = selection.getRangeAt(0);
	// 選択範囲の削除
	range.deleteContents();

	var container = range.startContainer;
	var offset = range.startOffset;

	switch (container.nodeType) {
		case 1:
			// Element node
			container.insertBefore(fragment,
					container.childNodes[offset]);
			break;
		case 3:
			// Text node
			var node = container.splitText(offset);
			node.parentNode.insertBefore(fragment, node);
			break;
	}
}

function insertHTMLforIE(html) {
	// iframeのdocument,windowを取得
	var clWin = frames['frame'].window;
	var clDoc = frames['frame'].document;

	clWin.focus();

	range = clDoc.selection.createRange();
	try {
		range.pasteHTML(html);
	} catch (e) {
		alert(e);
	}
}