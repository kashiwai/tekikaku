<?php
/*
 * playpointAPI.php
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
 * playpointAPIを管理する
 * 
 * playpointの加算、取得の管理
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/20 流用新規 村上 俊行
 *           2020/07/16 修正     村上 俊行 Logger出力の中止
 *           2020/07/17 修正     村上 俊行 drawpointも返すように修正
 *           2023/09/01 修正     村上 俊行 PayPal決済機能追加
 */

// インクルード
require_once('../../_etc/require_files_payment.php');			// 決済用requireファイル
require_once('../../_sys/APItool.php');					// APItool
require_once('../../_sys/PlayPoint.php');				// PlayPoint Class
//require_once('../../_sys/SettlementPoint.php');			// 決済 Class
require_once('./Logger.php');							// Logger Class

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
		
		getData($_GET, array("M"));
		
		switch( $_GET["M"] ){
			case "get":
				// 実処理
				GetPointJson($DB);
				break;
			case "request":
				requestBuyPointJson($DB);
				break;
			case "add":
				AddPointJson($DB);
				break;
			case "use":
				UsePointJson($DB);
				break;
			case "lpadd":
				AddDrawPointJson($DB);
				break;
		}
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
function GetPointJson($DB) {

	// データ取得
	getData($_GET, array( "MEMBER_NO", "PURCHASE_NO" ));

	//API設定
	$api = new APItool();

	//購入履歴のステータスが更新されているかを確認
	$sql = (new SqlString($DB))
		->select()
			->field("member_no,result_status,purchase_dt")
			->from("his_purchase")
			->where()
				->and( "purchase_no = ", $_GET["PURCHASE_NO"], FD_NUM)
				->and( "member_no = ",   $_GET["MEMBER_NO"], FD_NUM)
		->createSQL("\n");
	$purchaseRow = $DB->getRow($sql);
	if ( $purchaseRow["member_no"] == "" ){
		//読み込みエラー
		$api->setError("his_purchase read error");
		$api->outputJson();
		return;
	} else if ( $purchaseRow["result_status"] == "1" ){
		//成功
		//会員情報からポイントを取得
		$sql = (new SqlString($DB))
			->select()
				//2020-07-17 coin(drawpoint)を追加
				->field("member_no,point,draw_point")
				//->field("member_no,point")
				->from("mst_member")
				->where()
					->and( "member_no =", $_GET["MEMBER_NO"], FD_NUM)
			->createSQL("\n");
			
		$row = $DB->getRow($sql);

		$jsonArray["status"] = "ok";
		if ( mb_strlen($row["member_no"]) == 0 ){
			$api->setError("mst_member read error");
		} else {
			$game["playpoint"] = $row["point"];
			//2020-07-17 coin(drawpoint)を追加
			$game["drawpoint"] = $row["draw_point"];
			
			$api->set("game", $game );
		}
	} else if ( $purchaseRow["result_status"] == "9" ){
		//失敗
	} else {
		//結果待ち
	}
	$api->set("result", $purchaseRow["result_status"] );

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
	$SPOINT = new SettlementPoint( $DB );
	
	//決済情報のリクエスト情報作成
	if ( $SPOINT->request( $_GET["MEMBER_NO"], $purchase_type, $amount ) ){
		//正常終了
		$setting = $SPOINT->getAll();
		foreach( $setting as $keyname => $value ){
			$api->set( $keyname, $value );
		}
	} else {
		//エラー
		$api->setError( $SPOINT->getError() );
	}
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

	//$logger = new Logger( sprintf("./log/playpointAPI_%d.log", $_GET["MACHINE_NO"] ) );
	$logger = new Logger( "" );
	$logger->format( "{%date%} [{%level%}] {%message%}" );

	//加算値を設定
	$usePoint = $_GET["PLAYPOINT"];
	//ポイント使用（会員番号,使用ポイント,処理コード[51:プレイ], 参照key[プレイなので実機No]）
	if ( !$PPOINT->usePoint( $_GET["MEMBER_NO"], $_GET["PLAYPOINT"], "51", (array_key_exists("MACHINE_NO", $_GET)) ? $_GET["MACHINE_NO"] : "" ) ){
		$api->setError($PPOINT->getError());

		$logger->error($PPOINT->getError());
		$logger->error($_GET);

	}
	// 2022-09-26 
	$api->set( "exppoint", $PPOINT->_deadlinePoint );

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
