<?php
/*
 * info.php
 *
 * (C)SmartRams Co.,Ltd. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 *
 * 各種静的画面表示
 *
 * 各種静的画面の表示を行う
 *
 * @package
 * @author   鶴野 美香
 * @version  1.0
 * @since    2019/03/28 初版作成 鶴野 美香
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 * @info
 */
function main() {

	try {
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		// ユーザ情報更新処理
		$template->checkSessionUser(false, false);
		
		DispScreen($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispScreen($template) {

	// データ取得
	getData($_GET , array("M"));

	// 画面表示開始
	$template->open(PRE_HTML . "_" . $_GET["M"] . ".html", true);
	$template->assignCommon();

	// 表示
	$template->flush();
}

?>
