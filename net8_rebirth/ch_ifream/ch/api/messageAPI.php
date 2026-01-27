<?php
/*
 * messageAPI.php
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
 * messageを送る
 * 
 * カメラ端末からアクセスされた時に該当メッセージがあれば送る
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/06/20 流用新規 村上 俊行
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
		$DB = new NetDB();
		// 実処理
		getMessage($DB);
		
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
function getMessage($DB) {

	$jsonArray = array();
	$nowDate = Date("Y-m-d H:i:s");

	// データ取得
	getData($_GET, array( "MACHINE_NO", "LAST_DATE", "LANG" ));

	//日付指定がない場合は現時刻の１分前とする
	if ( mb_strlen($_GET["LAST_DATE"]) == 0 ){
		$_GET["LAST_DATE"] = date("Y-m-d H:i:s",strtotime("-1 minute"));
	}
	$limitTime = date("Y-m-d H:i:s");

	//**** メッセージを取得する時に言語判定からフォーマットを取得する必要がある

	//API設定
	$api = new APItool();
	$api->set("checkdate", $_GET["LAST_DATE"]);

	// lnk_machineにアサインされているかをチェック
	$sql = (new SqlString($DB))
		->select()
			->field("assign_flg, exit_flg")
			->from("lnk_machine")
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$lnkRow = $DB->getRow($sql);
	//assignが接続中に外れているのを確認した場合
	//2020-08-17 exit_flgの判定を追加
	//if ( $lnkRow["assign_flg"] != "1"){
	if ( $lnkRow["assign_flg"] != "1" || $lnkRow["exit_flg"] == "1"){
		$api->set("userleave", "1");
	} else {
		$api->set("userleave", "0");
	}

	$asql = (new SqlString($DB))
		->delete()
			->from("dat_client_message")
			->where()
				->and( "machines =  ",     "*", FD_STR )
		->createSQL("\n");

	$sql = (new SqlString($DB))
		->select()
			->field("message_time,message_text,stop_time")
			->from("dat_client_message")
			->where()
				->and( "message_time > ",  $_GET["LAST_DATE"], FD_DATE )
				->and( "message_time <= ", $limitTime, FD_DATE )
				->groupStart()
//					->or( "machines =  ",     "*", FD_STR )
					->or( "machines in ",     ["1", "2", "3"], FD_STR )
					->or( "machines like ",   "%".$_GET["MACHINE_NO"]."%", FD_STR )
				->groupEnd()
			->orderby("message_time desc")
		->createSQL("\n");
	$messageRow = $DB->getRow($sql);
	if( $messageRow == false ){
		$api->set("nextdate",     $limitTime );
		$api->set("sql",          $asql);
		$api->set("message_text", "");
		$api->set("stop_time",    '');
		$api->outputJson();
		return;
	}
	if ( $messageRow["stop_time"] == null ){
		$messageRow["stop_time"] = '';
	}
	//値を設定
	$api->set("nextdate",     $limitTime );
	//$api->set("sql",          $sql);
	$api->set("message_text", $messageRow["message_text"]);
	$api->set("stop_time",    $messageRow["stop_time"]);

	$api->outputJson();
}

?>
