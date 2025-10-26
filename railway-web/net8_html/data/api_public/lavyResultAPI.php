<?php
/*
 * lavyResultAPI.php
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
 * @since    2019/04/18 流用新規 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool
require_once('../../_sys/PlayPoint.php');				// PlayPoint Class
require_once('./Logger.php');						// Logger Class

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
	getData($_POST, array( "cookie", "site_code", "sendid", "sendpoint", "price", "rel" ));

	//API設定
	$api = new APItool();

	$logger = new Logger( "./log/lavyResultAPI.log" );
	$logger->format( "{%date%} [{%level%}] {%message%}" );

	$decode_data   = $api->decrypt( $_POST["cookie"] );
	$decode_array  = explode('|', $decode_data );
	$decode_cookie = $decode_array[0];
	$decode_sendid = $decode_array[1];

	$logger->info("cookie:{$_POST["cookie"]} sitecode:{$_POST["site_code"]} sendid:{$_POST["sendid"]} price:{$_POST["price"]} rel:{$_POST["rel"]}");
	$logger->info("decode_cookie:{$decode_cookie} decode_sendid:{$decode_sendid}");

	//入力データのチェック
	$checkFlg = true;
	if ( !preg_match("/^[0-9]+$/", $decode_cookie ) ){
		$api->setError("purchase_no field error");
		$checkFlg = false;
	}
	if ( !preg_match("/^[0-9]+$/", $decode_sendid ) ){
		$api->setError("sendid field error");
		$checkFlg = false;
	}
	if ( !preg_match("/^[0-9]+$/", $_POST["price"] ) ){
		$api->setError("price field error");
		$checkFlg = false;
	}
	if ( !preg_match("/^[0-9]$/", $_POST["rel"] ) ){
		$api->setError("rel field error");
		$checkFlg = false;
	}
	if ( !$checkFlg ){
		$api->outputJson();
		$logger->error("check error");
		return;
	}

	//購入履歴が存在しているかを確認
	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("purchase_no,member_no,purchase_type,amount,point")
			->from("his_purchase")
			->where()
				->and( "purchase_no = ",    $decode_cookie, FD_NUM)
				->and( "member_no = ",      $decode_sendid, FD_NUM)
				->and( "amount = ",         $_POST["price"], FD_NUM)
				->and( "result_status = ",  "0", FD_NUM)
		->createSQL("\n");
	$purchaseRow = $DB->getRow($sql);
	if ( $purchaseRow["purchase_no"] == "" ){
		//読み込みエラー
		$api->setError("data not found");
		$api->outputJson();
		$logger->error("data error");
		return;
	}

	if ( $_POST["rel"] == "1" ){
		//決済成功
		$resultCode = "1";
		$message = "";
	} else if ( $_POST["rel"] == "0" ){
		//決済失敗
		$resultCode = "9";
		$message = "result NG";
	} else {
		//その他
		$api->setError("data not found");
		$api->outputJson();
		$logger->error("data not found error");
		return;
	}

	// トランザクション開始
	$DB->autoCommit(false);

	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->update("his_purchase")
			->set()
				->value("result_status",    $resultCode, FD_NUM)
				->value("result_message",   $message, FD_STR)
				->value("purchase_dt",      Date("Y-m-d H:i:s"), FD_DATE)
			->where()
				->and( "purchase_no = ",    $purchaseRow["purchase_no"], FD_NUM)
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( $ret->rowCount() == 0 ){
		$api->setError("update error");
		$api->outputJson();
		$logger->error("update error");
		return;
	}

	//決済成功時のみポイントを計算する
	if ( $resultCode == "1" ){
		//point classをno commit modeで作成
		$PPOINT  = new PlayPoint( $DB, false );
		
		//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
		if ( $PPOINT->addAmount2Point( $purchaseRow["member_no"], $purchaseRow["purchase_type"], $purchaseRow["amount"], $purchaseRow["purchase_no"], "" ) ){
			
		} else {
			$api->setError( $PPOINT->getError() );
		}
	}

	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}


/**
 * API処理(request)
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function requestBuyPointJson($DB) {

	// データ取得
	getData($_GET, array( "MEMBER_NO", "T" ));

	$codes = explode("-", $_GET["T"]);					//11-1000 のような形式で来る
	$purchase_type = $codes[0];
	$amount        = $codes[1];

	//API設定
	$api = new APItool();

	// トランザクション開始
	$DB->autoCommit(false);

	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("mail")
			->from("mst_member")
			->where()
				->and( "member_no = ",  $_GET["MEMBER_NO"], FD_NUM )
		->createSQL("\n");
	$memberRow = $DB->getRow($sql);

	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->insert()
			->into("his_purchase")
				->value("member_no",      $_GET["MEMBER_NO"], FD_NUM)
				->value("recept_dt",      Date("Y-m-d H:i:s"), FD_DATE)
				->value("purchase_type",  $purchase_type, FD_NUM)
				->value("amount",         $amount, FD_NUM)
				->value("point",          $amount, FD_NUM)
				->value("result_status",  "0", FD_NUM)
		->createSQL("\n");
		
	$result = $DB->query($sql);

	if ( $result->rowCount() == 0 ){
		$DB->rollBack();
		$api->setError( $PPOINT->getError() );
	} else {
		$api->set( "IP_CODE",       SETTLE_IP_CODE );
		$api->set( "cookie",        $DB->lastInsertId('purchase_no') );
		$api->set( "price",         $amount );
		$api->set( "sendid",        $_GET["MEMBER_NO"] );
		$api->set( "payment_code" , $GLOBALS["Payment_Code_ConvertArray"][$purchase_type] );
		$api->set( "email" ,        $memberRow["mail"] );
	}
	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}

/**
 * API処理(add)
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function AddPointJson($DB) {

	// データ取得
	getData($_GET, array( "MEMBER_NO", "T" ));

	$codes = explode("-", $_GET["T"]);					//11-1000 のような形式で来る
	$purchase_type = $codes[0];
	$amount        = $codes[1];

	//ポイント購入でクレジットやコンビニ支払いなどを行った場合の参照No
	$keyno = "999999";

	//API設定
	$api = new APItool();
	$PPOINT  = new PlayPoint( $DB );
	
	//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
	if ( $PPOINT->addAmount2Point( $_GET["MEMBER_NO"], $purchase_type, $amount, $keyno, "" ) ){
		
	} else {
		$api->setError( $PPOINT->getError() );
	}

	$api->outputJson();

}

/**
 * API処理(use)
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function UsePointJson($DB) {

	// データ取得
	getData($_GET, array( "MEMBER_NO", "PLAYPOINT", "MACHINE_NO" ));

	//API設定
	$api = new APItool();
	$PPOINT = new PlayPoint( $DB );

	$logger = new Logger( sprintf("./log/playpointAPI_%d.log", $_GET["MACHINE_NO"] ) );
	$logger->format( "{%date%} [{%level%}] {%message%}" );

	//加算値を設定
	$usePoint = $_GET["PLAYPOINT"];
	//ポイント使用（会員番号,使用ポイント,処理コード[51:プレイ], 参照key[プレイなので実機No]）
	if ( !$PPOINT->usePoint( $_GET["MEMBER_NO"], $_GET["PLAYPOINT"], "51", $_GET["MECHINE_NO"] ) ){
		$api->setError($PPOINT->getError());

		$logger->error($PPOINT->getError());
		$logger->error($_GET);

	}

	$api->outputJson();

}

function AddDrawPointJson($DB) {

	$jsonArray = array();
	$nowDate = Date("Y-m-d H:i:s");

	// データ取得
	getData($_GET, array( "MEMBER_NO", "DRAW_POINT" ));

	//API設定
	$api = new APItool();
	$PPOINT  = new PlayPoint( $DB );
	
	//point加算（会員番号,加算種別,金額,参照key,有効期限）
	if ( !$PPOINT->addDrawPoint( $_GET["MEMBER_NO"], "11", $_GET["DRAW_POINT"], $_GET["MACHINE_NO"] ) ){
		$api->setError( $PPOINT->getError() );
	}

	$api->outputJson();

}

?>
