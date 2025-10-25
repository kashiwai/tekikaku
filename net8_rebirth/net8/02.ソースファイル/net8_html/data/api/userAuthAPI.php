<?php
/*
 * userAuthAPI.php
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
 * ゲーム機ステータス設定API
 * 
 * 端末の状態情報を受け取りDBに記録
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/05 流用新規 村上 俊行
 * @since    2020/06/25          村上 俊行 Notice修正
 * @since    2020/07/17          村上 俊行 Coin(draw_point)も送信する
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

	$nowDate = Date("Y-m-d H:i:s");

	// データ取得
	getData($_GET, array("MACHINENO", "PLAYDT", "MEMBERNO", "ONETIMEAUTHID"));

	//API設定
	$api = new APItool();

	//DBの登録情報をチェック
	$sql = (new SqlString($DB))
		->select()
			->field("machine_no, member_no,start_dt")
			->from("lnk_machine")
			->where()
				->and( "machine_no =", $_GET["MACHINENO"], FD_STR)
				->and( "onetime_id =", $_GET["ONETIMEAUTHID"], FD_STR)
		->createSQL("\n");
		
	$row = $DB->getRow($sql);

	//2020-06-25 Notice修正
	//if( $row["member_no"] == "" ){
	if ( !array_key_exists("member_no", $row) ) {
		//NG
		$api->setError("not assign[onetimeauthid]");
	} else {
		$memberNo = sha1(sprintf("%06d", $row["member_no"]));
		if ( $_GET["MEMBERNO"] != $memberNo ){
			//sha1されたmember_noが一致しない場合はNG
			$api->setError("not assign[member_no]");
		} else {
			// gamecount RB BB などは台の累計を出力しなければならない

			//台情報を取得（現在は仮設定）
			$sql = (new SqlString($DB))
				->select()
					->field("dm.machine_no,dm.release_date,dm.end_date")
					->field("mc.point,mc.credit,mc.draw_point")
					->field("dmp.day_count,dmp.total_count,dmp.count,dmp.bb_count,dmp.rb_count,in_credit,out_credit")
					->field("dmp.maxrenchan_count,dmp.past_max_credit,past_max_bb,past_max_rb")
					->field("md.category")
					->from("dat_machine dm")
						->join("left", "mst_convertPoint mc", "dm.convert_no = mc.convert_no")
						->join("left", "dat_machinePlay dmp", "dm.machine_no = dmp.machine_no")
						->join("left", "mst_model md",        "dm.model_no = md.model_no")
					->where()
						->and( "dm.machine_no =",  $row["machine_no"], FD_NUM)
				->createSQL("\n");
				
			$machineRow = $DB->getRow($sql);
			if ( $machineRow["machine_no"] != $row["machine_no"] ){
				$api->setError("dat_machine read error");
				$api->outputJson();
			}

			//最新のポイント情報を取得（現在は仮設定）
			$sql = (new SqlString($DB))
				->select()
					->field("member_no,point,draw_point,tester_flg")
					->from("mst_member")
					->where()
						->and( "member_no =",  $row["member_no"], FD_NUM)
				->createSQL("\n");
				
			$memberRow = $DB->getRow($sql);

			if ( $memberRow["member_no"] != $row["member_no"] ){
				$api->setError("mst_member read error");
				$api->outputJson();
			}

			$game["member_no"]      = $row["member_no"];
			$game["playpoint"]      = $memberRow["point"];
			//2020-07-17 coinを送信
			$game["drawpoint"]      = $memberRow["draw_point"];
//			$game["play_dt"]        = $nowDate;
			$game["play_dt"]        = $row["start_dt"];
			$game["credit"]         = 0;
			$game["tester_flg"]     = $memberRow["tester_flg"];					//2019-06-11追加

			$game["day_count"]      = $machineRow["day_count"];
			$game["total_count"]    = $machineRow["total_count"];
			$game["count"]          = $machineRow["count"];
			$game["bb_count"]       = $machineRow["bb_count"];
			$game["rb_count"]       = $machineRow["rb_count"];
			//2019-06-18 実機ごとのcredit増減履歴追加
			$game["mc_in_credit"]   = $machineRow["in_credit"];
			$game["mc_out_credit"]  = $machineRow["out_credit"];

			$game["maxrenchan_count"] = $machineRow["maxrenchan_count"];
			$game["past_max_credit"]  = $machineRow["past_max_credit"];
			$game["past_max_bb"]      = $machineRow["past_max_bb"];
			$game["past_max_rb"]      = $machineRow["past_max_rb"];


			//テスターフラグによってコンバートポイントの値を変化させる
			if ( $memberRow["tester_flg"] == "0" ){
				$game["conv_point"]     = $machineRow["point"];
				$game["conv_credit"]    = $machineRow["credit"];
				$game["conv_drawpoint"] = $machineRow["draw_point"];
			} else {
				//変換先元は０にしてクレジットはsettingの値を使う
				$game["conv_point"]     = "0";
				$game["conv_credit"]    = $GLOBALS["Dummy_Credit_Array"][$machineRow["category"]];
				$game["conv_drawpoint"] = "0";
			}

			
			$api->set("game", $game);
			//各種初期化処理
			if ( mb_strlen($_GET["PLAYDT"]) == 0 ){
				//PLAY_DTがない（初回プレイの場合のみ）
				initTable($DB, $row["machine_no"], $game );
			}
		}
	}

	$api->outputJson();
}



function initTable( $DB, $machine_no, $jsonArray ) {

	//プレイログの初期化
	$sql = (new SqlString($DB))
		->insert()
			->into("log_play")
				->value("play_dt",      $jsonArray["play_dt"])
				->value("machine_no",   $machine_no)
				->value("member_no",    $jsonArray["member_no"])
				->value("start_point",  $jsonArray["playpoint"])
		->createSQL("\n");
		
	$ret = $DB->query($sql);

}

?>
