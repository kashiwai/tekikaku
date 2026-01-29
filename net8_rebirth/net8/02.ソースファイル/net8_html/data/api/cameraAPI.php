<?php
/*
 * cameraAPI.php
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
 * カメラ端末状態設定API
 * 
 * カメラの状態を設定
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/20 新規 村上 俊行
 */

//テスト用設定


// インクルード
require_once('../../_etc/require_files.php');			// requireファイル

require_once('../../_sys/APItool.php');					// APItool
//require_once('../../_sys/Logger.php');					// Logger
require_once('./Logger.php');						// Logger
require_once('./TokenAuth.php');					// トークン認証

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


	getData($_GET, array("M") );

	try {
		// API系表示コントロールのインスタンス生成
		$DB = new NetDB();

		// 自動ハートビート更新 + machine_status自動更新（全API呼び出し時に実行）
		// Win側コード変更不要 - 既存のAPI呼び出しで自動的にlast_heartbeatとmachine_statusを記録
		if (isset($_GET["MACHINE_NO"]) && !empty($_GET["MACHINE_NO"])) {
			try {
				// 現在のmachine_statusを確認（メンテナンス中は保護）
				$checkSql = (new SqlString($DB))
					->select()
						->field("machine_status")
						->from("dat_machine")
						->where()
							->and("machine_no = ", $_GET["MACHINE_NO"], FD_NUM)
					->createSQL("\n");
				$currentStatus = $DB->getRow($checkSql);

				// メンテナンス(3)以外は稼働(1)に自動更新
				if (!empty($currentStatus) && $currentStatus['machine_status'] != '3') {
					$sql = (new SqlString($DB))
						->update("dat_machine")
							->set()
								->value("last_heartbeat", "CURRENT_TIMESTAMP", FD_FUNCTION)
								->value("machine_status", "1", FD_NUM) // 稼働中に自動更新
							->where()
								->and("machine_no = ", $_GET["MACHINE_NO"], FD_NUM)
						->createSQL("\n");
				} else {
					// メンテナンス中はheartbeatだけ更新（statusは保護）
					$sql = (new SqlString($DB))
						->update("dat_machine")
							->set()
								->value("last_heartbeat", "CURRENT_TIMESTAMP", FD_FUNCTION)
							->where()
								->and("machine_no = ", $_GET["MACHINE_NO"], FD_NUM)
						->createSQL("\n");
				}
				$DB->query($sql);
			} catch (Exception $e) {
				// ハートビート更新失敗は既存処理に影響を与えない
				error_log("Heartbeat update failed for machine_no={$_GET['MACHINE_NO']}: " . $e->getMessage());
			}
		}

		// 実処理
		switch( $_GET["M"] ){
			case "start" :
				StartCamera($DB);
				break;
			case "end" :
				EndCamera($DB);
				break;
			case "log" :
				LogCamera($DB);
				break;
			case "getno" :
				GetNoCamera($DB);
				break;
			case "reset" :
				ResetLink($DB);
				break;
			case "status" :
				GetStatus($DB);
				break;
			case "setting" :
				SetSetting($DB);
				break;
			case "reboot" :
				SetReboot($DB);
				break;
		}
		
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * 開始処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function StartCamera($DB) {

	// データ取得
	getData($_GET, array("MACHINE_NO") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);

	//lnk_machineを更新
	$sql = (new SqlString($DB))
		->update("lnk_machine")
			->set()
				->value("assign_flg",    "0", FD_NUM)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("lnk_machine update error");
		return;
/*		
		$api = new APItool();
		
		$api
			->setError()
			->outputJson();


*/		
	}
	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}

/**
 * 終了処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function EndCamera($DB) {

	// データ取得
	getData($_GET, array("MACHINE_NO") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);

	//lnk_machineを更新
	$sql = (new SqlString($DB))
		->update("lnk_machine")
			->set()
				->value("assign_flg",    "9", FD_NUM)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("lnk_machine update error");
		return;
	}
	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}

/**
 * ログ処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function LogCamera($DB) {

	// データ取得
	getData($_GET, array("machine_no", "level", "message") );

	$api = new APItool();
	//$logger = new Logger( sprintf("./log/cameraAPI_%d.log", $_GET["machine_no"] ) );
	$logger = new Logger( "" );
	$logger->format( "{%date%} [{%level%}] {%message%}" );

	$logger->outputMessage( $_GET["level"], $_GET["message"] );

	$api->outputJson();

}

/**
 * 終了処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function GetNoCamera($DB) {

	// データ取得
	getData($_GET, array("MAC") );

	$api = new APItool();

	$sql = (new SqlString($DB))
		->select()
			->field("dm.machine_no,mm.category,dm.signaling_id,dm.camera_no")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->where()
				->subQuery("camera_no",
							(new SqlString($DB))
								->select()
									->field("camera_no")
									->from("mst_camera")
									->where()
										->and( "camera_mac =", $_GET["MAC"], FD_STR)
										->and( "del_flg =",    "0", FD_NUM)
								->createSQL()
				)
		->createSQL("\n");

	$row = $DB->getRow($sql);
	if ( mb_strlen($row["machine_no"]) == 0 ){
		$api->setError("mst_cameraにmacアドレスが登録されていません。");
	} else {
		$api->set("machine_no", $row["machine_no"] );
		$api->set("category", $row["category"] );
	}

	$api->outputJson();
}

/**
 * リセット処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function ResetLink($DB) {

	// データ取得
	getData($_GET, array("MACHINE_NO") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);

	$limitDate = Date('Y-m-d H:i:s',strtotime("now -60 seconds"));

	//lnk_machineを更新
	$sql = (new SqlString($DB))
		->update("lnk_machine")
			->set()
				->value("assign_flg",    "0", FD_NUM)
				->value("onetime_id",    "",  FD_STR)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
				->and( "assign_flg = ",  "1", FD_NUM )
				->and( "start_dt < ",    $limitDate, FD_DATE )
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("lnk_machine update error");
		return;
	}
	//コミット
	$DB->autoCommit(true);

	//2020-06-08 setting切替
	//メンテナンス中の設定されている時のみしか取得できない
	$sql = (new SqlString($DB))
		->select()
			->field("machine_no,upd_setting")
			->from("dat_machine")
			->where()
				->and( "machine_no = ",    $_GET["MACHINE_NO"], FD_NUM )
				->and( "upd_setting <> ",  "0", FD_NUM )
				->and( "machine_status =", "2", FD_NUM )
		->createSQL("\n");
	$row = $DB->getRow($sql);
	//2020-06-24 Notine対応
	if ( array_key_exists("machine_no",$row ) ){
		if ( mb_strlen($row["machine_no"]) == 0 ){
			$api->set("setting",  "0" );
		} else {
			$api->set("setting",  $row["upd_setting"] );
		}
	} else {
		$api->set("setting",  "0" );
	}

	//2020-09-18 再起動設定
	$sql = (new SqlString($DB))
		->select()
			->field("reboot_sw")
			->from("dat_machine")
			->where()
				->and( "machine_no = ",    $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$row = $DB->getRow($sql);
	if ( $row["reboot_sw"] != "0" ){
		$api->set("reboot", $row["reboot_sw"]);
	} else {
		$api->set("reboot", "0");
	}

	$api->outputJson();
}

/**
 * 状況取得処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function GetStatus($DB) {
	// データ取得
	getData($_GET, array("MACHINE_NO") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);
	// lnk_machineにアサインされているかをチェック
	$sql = (new SqlString($DB))
		->select()
			->field("assign_flg,member_no,exit_flg")
			->from("lnk_machine")
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$lnkRow = $DB->getRow($sql);
	//assignが接続中に外れているのを確認した場合
	//if ( $lnkRow["assign_flg"] == "1" && $lnkRow["member_no"] == "0"){
	if ( $lnkRow["assign_flg"] == "1" && $lnkRow["exit_flg"] == "1"){
		$api->set("userleave", "1");
	} else {
		$api->set("userleave", "0");
	}

	$api->outputJson();
}

/**
 * 設定変更処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function SetSetting($DB) {
	// データ取得
	getData($_GET, array("MACHINE_NO", "LEVEL") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);

	//前回履歴の終了日時更新
	$sql = (new SqlString($DB))
		->update("his_machineSetting")
			->set()
				->value("end_dt",        "current_timestamp", FD_FUNCTION)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
				->and( "end_dt is null ")
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("his_machineSetting update error");
		return;
	}

	//今回履歴追加
	$sql = (new SqlString($DB))
		->insert()
			->into("his_machineSetting")
				->value("machine_no",        $_GET["MACHINE_NO"], FD_NUM)
				->value("start_dt",          "current_timestamp", FD_FUNCTION)
				->value("real_setting",      $_GET["LEVEL"],      FD_NUM)
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("his_machineSetting update error");
		return;
	}

	//dat_machineを更新
	$sql = (new SqlString($DB))
		->update("dat_machine")
			->set()
				->value("real_setting",  $_GET["LEVEL"], FD_NUM)
				->value("upd_setting",   "0", FD_NUM)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("dat_machine update error");
		return;
	}
	//コミット
	$DB->autoCommit(true);

	//カメラ開始を設定する
	StartCamera($DB);

}

/**
 * 再起動確認処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function SetReboot($DB) {
	// データ取得
	getData($_GET, array("MACHINE_NO") );

	$api = new APItool();

	// トークン認証
	if (isset($_GET["TOKEN"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	// トランザクション開始
	$DB->autoCommit(false);

	//dat_machineを更新
	$sql = (new SqlString($DB))
		->update("dat_machine")
			->set()
				->value("reboot_sw",     "0", FD_NUM)
				->value("reboot_dt",     "current_timestamp", FD_FUNCTION)
			->where()
				->and( "machine_no = ",  $_GET["MACHINE_NO"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$DB->rollBack();
		$api->setError("dat_machine update error");
		return;
	}
	//コミット
	$DB->autoCommit(true);

	$api->outputJson();
}

?>
