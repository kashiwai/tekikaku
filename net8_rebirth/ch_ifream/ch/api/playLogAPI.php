<?php
/*
 * playLogAPI.php
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
 * playLogを保存する
 * 
 * カメラ端末からきた情報をlog_playに保存する
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/20 流用新規 村上 俊行
 *           2020/07/16 修正     村上 俊行 Logger出力の中止
 *           2020/08/26 修正     村上 俊行 chatにbonus結果送信のロジックを追加
 *           2020/09/23 修正     村上 俊行 ログ出力項目の追加
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool
require_once('./Logger.php');							// Logger Class

//2020-08-24 USE_CHATが定義されていたらインクルード
if ( defined ('USE_CHAT') ){
	if ( USE_CHAT ) require_once('../../_sys/chatAPI.php');		// Chat Class
}

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
		EchoJson($DB);
		
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
function EchoJson($DB) {

	$jsonArray = array();
	$nowDate = Date("Y-m-d H:i:s");

	// データ取得
	getData($_POST, array(  "play_dt","machine_no", "member_no",
							"bonusUpdate", "bonusspan",
							"day_count", "total_count", "count", "bb_count", "rb_count","mc_in_credit","mc_out_credit",
							"in_point", "out_point", "in_credit", "out_credit","user_game", "user_BB", "user_RB",
							"renchan_count","maxrenchan_count","tenjo_count","ichigeki_credit", "day_max_credit"
	));

	//API設定
	$api = new APItool();

	//$logger = new Logger( sprintf("./log/playLogAPI_%d.log", $_POST["machine_no"] ) );
	$logger = new Logger( "" );
	$logger->format( "{%date%} [{%level%}] {%message%}" );
	$logger->info($_POST);

	//会員番号がない場合は正しい値ではないので処理させない
	if ( $_POST["member_no"] == "" ){
		$logger->error("no member_no error");
		$logger->error($_POST);

		$api->setError("no member_no error");
		$api->outputJson();
		return;
	}

	// トランザクション開始
	$DB->autoCommit(false);

	$sql = (new SqlString($DB))
		->select()
			->field("hit_data")
			->from("dat_machinePlay")
			->where()
				->and( "machine_no = ",  $_POST["machine_no"], FD_NUM )
		->createSQL("\n");
	$playRow = $DB->getRow($sql);
	
	//bonusがある場合のhit_data更新
	$bonusJson = $playRow["hit_data"];
	if ( $bonusJson == "" ){
		$bonusArray = array();
	} else {
		$bonusArray = json_decode( $playRow["hit_data"], true );
	}
	if ( $_POST["bonusUpdate"] != "" ){
		array_unshift( $bonusArray, array( "TYPE" => $_POST["bonusUpdate"], "COUNT" => $_POST["bonusspan"], "DATE" => $nowDate ) );
		$bonusJson = json_encode( $bonusArray );
		
		//2020-08-24 chatへ送信
		if ( defined ('USE_CHAT') ){
			if ( USE_CHAT ){
				$cht = array();
				$sql = (new SqlString($DB))
					->select()
						//2020-09-08 machine_statusを取得するように変更
						->field("dm.machine_no,dm.machine_status")
						->field("mm.model_name,mm.model_roman")
						->from("dat_machine dm")
						->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
						->where()
							->and( "machine_no =", $_POST["machine_no"], FD_NUM)
					->createSQL("\n");
				$nameRow = $DB->getRow($sql);

				//2020-09-08 machine_statusが1(稼働中）の時のみ送信
				if( $nameRow["machine_status"] == "1" ){
					$cht["machine_no"] = $_POST["machine_no"];
					$cht["model_name"] = $nameRow["model_name"];
					$cht["bonus"]      = strtoupper($_POST["bonusUpdate"]);
					$cht["count"]      = ($_POST["bonusUpdate"] == 'bb')? $_POST["bb_count"] : $_POST["rb_count"];

					$chat = new chatAPI();
					$chat->sendDM( "bonus", $cht );
				}
			}
		}
	}

	//実機当日データを更新
	$sql = (new SqlString($DB))
		->update("dat_machinePlay")
			->set()
				//2020-09-28 項目追加
				->value("day_count",     $_POST["day_count"], FD_NUM)
				->value("total_count",   $_POST["total_count"], FD_NUM)
				->value("count",         $_POST["count"], FD_NUM)
				->value("bb_count",      $_POST["bb_count"], FD_NUM)
				->value("rb_count",      $_POST["rb_count"], FD_NUM)
				//2019-06-18 実機ごとのcredit増減履歴追加
				->value("in_credit",     $_POST["mc_in_credit"], FD_NUM)
				->value("out_credit",    $_POST["mc_out_credit"], FD_NUM)
				->value("hit_data",      $bonusJson, FD_STR)
				//2020-09-23 項目追加
				->value("renchan_count",    $_POST["renchan_count"], FD_NUM)
				->value("maxrenchan_count", $_POST["maxrenchan_count"], FD_NUM)
				->value("tenjo_count",      $_POST["tenjo_count"], FD_NUM)
				->value("ichigeki_credit",  $_POST["ichigeki_credit"], FD_NUM)
				//2020-12-04 最大出玉数の更新
				->value("max_credit",    $_POST["day_max_credit"], FD_NUM)
				->value("upd_dt",        $nowDate, FD_DATE)
			->where()
				->and( "machine_no = ",  $_POST["machine_no"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);

	if( $ret == false ){
		//rollBackしてトランザクション終了
		$DB->rollBack();

		$logger->error("dat_machinePlay update error");
		$logger->error($sql);
		$logger->error($_POST);

		$api->setError("dat_machinePlay update error");
		$api->set("sql", $sql);
		$api->outputJson();
		return;
	}

	//プレイログを更新
	$sql = (new SqlString($DB))
		->update("log_play")
			->set()
				->value("in_point",      $_POST["in_point"], FD_NUM)
				->value("out_point",     $_POST["out_point"], FD_NUM)
				->value("in_credit",     $_POST["in_credit"], FD_NUM)
				->value("out_credit",    $_POST["out_credit"], FD_NUM)
				->value("play_count",    $_POST["user_game"], FD_NUM)
				->value("bb_count",      $_POST["user_BB"], FD_NUM)
				->value("rb_count",      $_POST["user_RB"], FD_NUM)
				//2020-09-23 項目追加
				->value("renchan_count",   $_POST["renchan_count"], FD_NUM)
				->value("tenjo_count",     $_POST["tenjo_count"], FD_NUM)
				->value("ichigeki_credit", $_POST["ichigeki_credit"], FD_NUM)
				->value("max_credit",      $_POST["day_max_credit"], FD_NUM)
			->where()
				->and( "play_dt = ",     $_POST["play_dt"], FD_DATE )
				->and( "machine_no = ",  $_POST["machine_no"], FD_NUM )
				->and( "member_no = ",   $_POST["member_no"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);

	$jsonArray["status"] = "ok";
	if( $ret == false ){
		//rollBackしてトランザクション終了
		$DB->rollBack();

		$logger->error("log_play update error");
		$logger->error($sql);
		$logger->error($_POST);

		$api->setError("log_play update error");
		$api->outputJson();
		return;
	}

	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}

?>
