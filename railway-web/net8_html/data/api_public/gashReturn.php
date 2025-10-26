<?php
/*
 * p99result.php
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
 * 決済結果情報通知API
 * 
 * 決済会社からの結果通知から購入履歴を更新する
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2020/04/28 流用新規 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files_payment.php');			// 決済用requireファイル
require_once('../../_sys/PlayPoint.php');						// PlayPoint Class

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
		$DB = new NetDB();
		
		updateSettle($DB);

	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * API処理(get)
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function updateSettle($DB) {

	// データ取得
	getData($_POST, array( "data" ));

	// 決済クラス作成
	$SPOINT = new SettlementPoint($DB);
	// 決済データの更新
	$err = $SPOINT->updatePurchase($_POST);

	/* データ確認用 */
	if (defined('GASH_BASE64_LOG')){
		$fp = fopen(GASH_BASE64_LOG, "a");
		fputs( $fp, date("Y/m/d H:i:s") . " html  " . $err . "\n");
		fclose($fp);
	}

	if ( $err == "0" ){
		jumpEnd();
	} else {
		jumpFail($err);
	}
}

/**
 * 正常終了リダイレクト
 * @access	private
 * @return	なし
 */
function jumpEnd(){
	// 完了画面へ
	header("Location: " . URL_SITE . "point_buy.php?M=end");
}
/**
 * 正常終了リダイレクト
 * @access	private
 * @param	string	$code			エラーコード
 * @return	なし
 */
function jumpFail($code){
	// 完了画面へ
	header("Location: " . URL_SITE . "point_buy.php?M=end&c={$code}");
}

?>
