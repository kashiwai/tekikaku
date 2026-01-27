/**
 * @fileOverview
 * 一覧画面
 */

// リセット
function reset() {
	$('#MAINFORM')
		.find("input, select")
		.not(":button, :submit, :reset, :hidden, :radio, :checkbox")
		.val("")
		.prop("checked", false)
		.prop("selected", false);
	$('#MAINFORM').find(":checkbox").prop("checked", false);
	$('#MAINFORM').find(":radio").filter("[value='']").prop("checked", true);
}

// 自画面遷移
function trans(frm) {
	if (!frm) frm = window.document.MAINFORM;
	frm.submit();
}

// 並び替え
function reSort(fld, frm) {
	if (!frm) frm = window.document.MAINFORM;
	// 検索条件のリセット
	frm.reset();
	// ページパラメータのクリア
	frm["P"].value = "1";
	frm["ODR"].value = fld;
	// Transfer
	trans();
}

// ページ遷移
function movePage(span, frm) {
	if (!frm) frm = window.document.MAINFORM;
	// 検索条件のリセット
	frm.reset();
	// ページパラメータのクリア
	frm["P"].value = eval(frm["P"].value) + span;
	// Transfer
	trans();
}
