<?php
/*
 * logout.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * ログアウト処理
 * 
 * ログアウト処理を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  2.0
 * @since    2016/09/01 初版作成 岡本 静子
 * @since    2019/02/07 ネットパチンコ用改修 片岡
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル

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
	
	// 管理系表示コントロールのインスタンス生成
	$template = new TemplateUser(true, URL_SSL_SITE);
	
	// セッションクリア
	$template->Session->clear(true);
	
}

?>
