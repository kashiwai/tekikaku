<?php
/*
 * sessionAPI.php
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
 * sessionを保持する
 * 
 * プレイ中はアクセスがないのでセッションが切れてしまう問題の解決用
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/20 流用新規 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool
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
		// API系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		// 実処理
		EchoJson($template);
		
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * API処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function EchoJson($template) {

	//API設定
	$api = new APItool();

	//セッションを強制close
	session_write_close();

	$api->outputJson();
}

?>
