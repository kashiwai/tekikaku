<?php
/*
 * paypalReturn.php
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
 * PayPal決済通知
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2023/08/28 流用新規 村上 俊行
 */

// インクルード
require_once('../_etc/require_files_payment.php');			// 決済用requireファイル
require_once('../_sys/PlayPoint.php');						// PlayPoint Class

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
	//リファラーチェック
	if (!array_key_exists("HTTP_REFERER", $_SERVER)) {
		return;
	}
	if ( !preg_match(PAYPAL_PAYMENT_REFERER, $_SERVER['HTTP_REFERER']) ) {
		return;
	}

	try {
		// API系表示コントロールのインスタンス生成
		$DB = new NetDB();
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		$template->checkSessionUser(true, true);
		
		$err = updateSettle($DB);

		DispEnd($template, $err);
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
	getData($_GET, array( "token" ));

	$capture = set_paypal_capture($_GET["token"]);
	if ($capture == ""){
		// エラー
		return "p01";
	}
	// 決済クラス作成
	$SPOINT = new SettlementPoint($DB);
	// 決済データの更新 invoice_id == purchase_no
	$err = $SPOINT->updatePurchasePayPal($capture);

	return($err);
	
}

/**
 * 完了画面表示処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispEnd($template, $err) {

	// 画面表示開始
	$template->open(PAYMENT_DIR . "point_buy_end.html");
	$template->assignCommon();

	if ( $err == "1" ){
		$template->if_enable("DONE"    , true);
		$template->if_enable("FAIL"    , false);

	} else {
		$template->if_enable("DONE"    , false);
		$template->if_enable("FAIL"    , true);
		$template->assign("ERROR_CODE", $err, true);
	}
	$template->if_enable("INSIDE"  , false);	// 内部遷移
	$template->if_enable("OUTSIDE" , true);		// 外部遷移

	$template->flush();
}

?>
