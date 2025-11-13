<?php
/*
 * cameraListAPI.php
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
 * @since    2020/04/17 新規 村上 俊行
 *           2020/06/03 修正 村上 俊行 強制退出時間(秒)を返すように変更
 *           2020/06/08 修正 村上 俊行 version情報（新基盤、ROM区別）を返すように変更
 *           2020/07/16 修正 村上 俊行 Logger出力の中止
 *           2020/09/18 修正 村上 俊行 連チャン、天井ゲーム数の送信を追加
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

	getData($_POST, array("M") );
	getData($_GET,  array("M") );

	try {
		// API系表示コントロールのインスタンス生成
		$DB = new NetDB();
		// 実処理
		switch( $_POST["M"] ){
			case "add" :
				addList($DB);
				break;
			case "ext" :
				ExistList($DB);
				break;
		}
		switch( $_GET["M"] ){
			case "getno" :
				GetNoCamera($DB);
				break;
		}
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * 設定情報取得
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function GetNoCamera($DB) {

	// データ取得
	getData($_GET, array("MAC","ID","IP") );

	// MAC addressを小文字に統一（case-insensitive対応）
	$_GET["MAC"] = strtolower($_GET["MAC"]);

	$api = new APItool();

	// トークン認証（TOKENとMACHINE_NOがある場合）
	if (isset($_GET["TOKEN"]) && isset($_GET["MACHINE_NO"])) {
		if (!TokenAuth::verify($DB, $_GET["MACHINE_NO"], $_GET["TOKEN"])) {
			$api->setError("認証エラー: トークンが無効です");
			$api->outputJson();
			return;
		}
	}

	$sql = (new SqlString($DB))
		->select()
			->field("mac_address,ip_address")
		->from("mst_cameralist")
		->where()
			->and( "mac_address =", $_GET["MAC"], FD_STR)
			->and( "license_id =",  $_GET["ID"], FD_STR)
			->and( "del_flg =",    "0", FD_NUM)
		->createSQL("\n");

	$row = $DB->getRow($sql);
	if ( empty($row) || mb_strlen($row["mac_address"]) == 0 ){
		$api->setError("macアドレスが登録されていません。");
		$api->outputJson();
		return;
	}
	if ( mb_strlen($row["ip_address"]) == 0 ){
		//初回のみIPアドレスを更新する
		if ( !preg_match("/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/", $_GET["IP"]) ){
			$api->setError("IPアドレスが不正です。");
			$api->outputJson();
			return;
		}
		// トランザクション開始
		$DB->autoCommit(false);
		$sql = (new SqlString($DB))
			->update("mst_cameralist")
				->set()
					->value("ip_address",    $_GET["IP"], FD_STR)
					->value("upd_no",        "1", FD_NUM)
					->value("upd_dt",        "current_timestamp" , FD_FUNCTION)
				->where()
					->and( "mac_address = ", $_GET["MAC"], FD_STR)
			->createSQL("\n");
		$ret = $DB->query($sql);
		if ( !$ret ){
			$DB->rollBack();
			$api->setError("ip_addressの更新に失敗しました。");
			$api->outputJson();
			return;
		} else {
			//コミット
			$DB->autoCommit(true);
		}
	}

	//machine_noとcamera_noの取得
	$sql = (new SqlString($DB))
		->select()
			->field("dm.machine_no,mm.category,dm.signaling_id,dm.camera_no,mm.layout_data,mm.prizeball_data")
			->field("mm.renchan_games,mm.tenjo_games")
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
	if ( empty($row) || mb_strlen($row["machine_no"]) == 0 ){
		$api->setError("mst_cameraにmacアドレスが登録されていません。");
	} else {
		$api->set("machine_no", $row["machine_no"] );
		$api->set("category", $row["category"] );
		// Skip CD value to bypass license check
		// if ( !empty($license_cd) ) {
		//	$api->set("cd", $license_cd );
		// }
		//2020-06-03 自動精算時間設定(秒）
		if ( defined("PLAY_KEEP_TIME") ){
			$api->set("leavetime", PLAY_KEEP_TIME );
		} else {
			$api->set("leavetime", 180 );
		}
		//2020-09-18 連チャン、天井設定
		$api->set("renchan_games", $row["renchan_games"]  );
		$api->set("tenjo_games", $row["tenjo_games"]  );
		//2020-06-08 version情報を返す
//		try {
			$json = json_decode($row["layout_data"], true);
			$pjson = json_decode($row["prizeball_data"], true);
			// versionが空の場合はデフォルト値"1"を設定
			$version = !empty($json["version"]) ? "{$json["version"]}" : "1";
			$api->set("version", $version);
			// 2020-12-01 パチンコの場合のデータ拡張
			if ( $row["category"] == "1" ){
				$api->set("max",       $pjson["MAX"] );
				$api->set("max_rate",  $pjson["MAX_RATE"] );
				$api->set("navel",     $pjson["NAVEL"] );
				$api->set("tulip",     $pjson["TULIP"] );
				$api->set("attacker1", $pjson["ATTACKER1"] );
				$api->set("attacker2", $pjson["ATTACKER2"] );
				if ( array_key_exists("V_PRIZE",$pjson ) ){
					$api->set("v_prize",   "{$pjson["V_PRIZE"]}" );
				}
				if ( array_key_exists("AUTOCHANCE",$pjson ) ){
					$api->set("autochance",   "{$pjson["AUTOCHANCE"]}" );
				}
				if ( array_key_exists("EXTEND",$pjson ) ){
					if ( array_key_exists("CONTINUOUS_GAME",$pjson["EXTEND"] ) ){
						$api->set("continuous_game",   $pjson["EXTEND"]["CONTINUOUS_GAME"] );
					}
					if ( array_key_exists("TULIP_COUNT",$pjson["EXTEND"] ) ){
						$api->set("tulip_count",   $pjson["EXTEND"]["TULIP_COUNT"] );
					}
					if ( array_key_exists("ATTACKER2NOT",$pjson["EXTEND"] ) ){
						$api->set("attacker2not", "{$pjson["EXTEND"]["ATTACKER2NOT"]}" );
					}
				}
			}
//		} catch(Exception $e) {
//			$api->set("version", "1" );
//		}
	}

	$api->outputJson();
}

/**
 * cameraListnに追加
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function addList($DB) {

	// データ取得（LICENSE_IDとCAMERA_NOを追加）
	getData($_POST, array("MAC_ADDRESS", "IDENTIFING_NUMBER", "SYSTEM_NAME", "PRODUCT_NAME", "CPU_NAME", "CORE", "UUID", "LICENSE_ID") );
	// Windows側から要求されたcamera_no（オプション）
	$requested_camera_no = isset($_POST["CAMERA_NO"]) ? intval($_POST["CAMERA_NO"]) : 0;

	// MAC addressを小文字に統一（case-insensitive対応）
	$_POST["MAC_ADDRESS"] = strtolower($_POST["MAC_ADDRESS"]);

	$api = new APItool();

	// License IDがローカルから送られてきているかチェック
	if ( empty($_POST["LICENSE_ID"]) ){
		$api->setError("License IDが送信されていません。NET8License.pyでLicense IDを生成してください。");
		$api->outputJson();
		return;
	}

	$sql = (new SqlString($DB))
		->select()
			->field("mac_address,camera_no,license_id")
		->from("mst_cameralist")
		->where()
			->and( "mac_address =", $_POST["MAC_ADDRESS"], FD_STR)
			->and( "del_flg =",    "0", FD_NUM)
		->createSQL("\n");

	$row = $DB->getRow($sql);
	if ( mb_strlen($row["mac_address"]) == 0 ){
		// ローカルから送られてきたLicense IDを使用（サーバー側では生成しない）
		$license_id = $_POST["LICENSE_ID"];

		// トランザクション開始
		$DB->autoCommit(false);

		//lnk_machineを更新
		$sql = (new SqlString($DB))
			->insert()
				->into("mst_cameralist")
					->value("mac_address",          $_POST["MAC_ADDRESS"], FD_STR)
					->value("state",                "0", FD_NUM)
					->value("identifing_number",    $_POST["IDENTIFING_NUMBER"], FD_STR)
					->value("system_name",          $_POST["SYSTEM_NAME"], FD_STR)
					->value("product_name",         $_POST["PRODUCT_NAME"], FD_STR)
					->value("cpu_name",             $_POST["CPU_NAME"], FD_STR)
					->value("core",                 $_POST["CORE"], FD_NUM)
					->value("license_id",           $license_id, FD_STR)
					->value("uuid",                 $_POST["UUID"], FD_STR)
					->value("add_no",               "1" , FD_NUM)
					->value("add_dt",               "current_timestamp" , FD_FUNCTION)
			->createSQL("\n");
		$ret = $DB->query($sql);
		if ( !$ret ){
			$DB->rollBack();
			$api->setError("mst_cameralist insert error");
			$api->outputJson();
			return;
		} else {
			//コミット
			$DB->autoCommit(true);
		}
		$api->set("mode",  "insert" );
	} else {
		$license_id = $row["license_id"];
		$api->set("mode",  "update" );
	}

	//カメラ番号の取得（Windows側から要求されたcamera_noを渡す）
	$cameraNo = addCamera($DB, $requested_camera_no);
	$sql = (new SqlString($DB))
		->update("mst_cameralist")
			->set()
				->value("camera_no",     $cameraNo, FD_NUM)
			->where()
				->and( "mac_address = ", $_POST["MAC_ADDRESS"], FD_STR)
		->createSQL("\n");
	$ret = $DB->query($sql);
	if ( !$ret ){
		$api->setError("mst_cameralist update error");
	}

	// MACアドレスからマシン情報を取得してトークンを返す
	$machine_info = TokenAuth::getMachineByMac($DB, $_POST["MAC_ADDRESS"]);
	if ($machine_info) {
		$api->set("machine_no", $machine_info['machine_no']);
		$api->set("token", $machine_info['token']);
	}

	$api->set("camera_no",  $cameraNo );
	$api->set("license_id", $license_id );
	$api->outputJson();
}

function ExistList($DB){

	// データ取得
	getData($_POST, array("MAC_ADDRESS", "LICENSE_ID" ));

	// MAC addressを小文字に統一（case-insensitive対応）
	$_POST["MAC_ADDRESS"] = strtolower($_POST["MAC_ADDRESS"]);

	$api = new APItool();

	$sql = (new SqlString($DB))
		->select()
			->field("mac_address","license_id")
		->from("mst_cameralist")
		->where()
			->and( "mac_address =", $_POST["MAC_ADDRESS"], FD_STR)
			->and( "license_id =",  $_POST["LICENSE_ID"], FD_STR)
			->and( "del_flg =",    "0", FD_NUM)
		->createSQL("\n");

	$row = $DB->getRow($sql);
	if ( mb_strlen($row["mac_address"]) == 0 ){
		$api->setError("mst_cameralist sendkey error");
	} else {
		// トランザクション開始
		$DB->autoCommit(false);
		$sql = (new SqlString($DB))
			->update("mst_cameralist")
				->set()
					->value("state",         "1", FD_NUM)
				->where()
					->and( "mac_address = ", $_POST["MAC_ADDRESS"], FD_STR)
			->createSQL("\n");
		$ret = $DB->query($sql);
		if ( !$ret ){
			$api->setError("mst_cameralist update error");
		}
		$DB->autoCommit(true);
	}
	$api->outputJson();
}


/**
 * カメラの追加
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @param	int		$requested_camera_no	Windows側から要求されたcamera_no
 * @return	int		camera_no
 */
function addCamera($DB, $requested_camera_no = 0){

	$sql = (new SqlString($DB))
		->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("camera_no")
			->from("mst_camera")
			->where()
				->and( "camera_mac =", $_POST["MAC_ADDRESS"], FD_STR)
				->and( "del_flg =",    "0", FD_NUM)
		->createSQL();
	$cameraRow = $DB->getRow($sql);

	if ( mb_strlen($cameraRow["camera_no"]) == 0 ){
		// トランザクション開始
		$DB->autoCommit(false);
		//macアドレスが登録されていないので新規にmst_cameraに登録する。

		// Windows側から要求されたcamera_noを使用（0の場合は自動採番）
		if ($requested_camera_no > 0) {
			// 手動でcamera_noを指定してINSERT
			$sql = (new SqlString($DB))
				->insert()
					->into("mst_camera")
						->value("camera_no",   $requested_camera_no, FD_NUM)
						->value("camera_mac",  $_POST["MAC_ADDRESS"], FD_STR)
						->value("camera_name", "camera_entry_mode", FD_STR)
						->value("add_no",      API_CAMERA_ADD_NO, FD_NUM)
						->value("add_dt",      "current_timestamp" , FD_FUNCTION)
				->createSQL();
			$result = $DB->query($sql);
			if ( $result == false ){
				$DB->rollBack();
				return 0;
			}
			$camera_no = $requested_camera_no;
		} else {
			// 自動採番でINSERT（従来の動作）
			$sql = (new SqlString($DB))
				->insert()
					->into("mst_camera")
						->value("camera_mac",  $_POST["MAC_ADDRESS"], FD_STR)
						->value("camera_name", "camera_entry_mode", FD_STR)
						->value("add_no",      API_CAMERA_ADD_NO, FD_NUM)
						->value("add_dt",      "current_timestamp" , FD_FUNCTION)
				->createSQL();
			$result = $DB->query($sql);
			if ( $result == false ){
				$DB->rollBack();
				return 0;
			}
			$camera_no = $DB->lastInsertId('camera_no');
		}

		$DB->autoCommit(true);
		return $camera_no;
	} else {
		// 既に登録されている場合は、そのcamera_noを返す
		return $cameraRow["camera_no"];
	}

}

/**
 * ライセンスIDの生成
 * @access	private
 * @param	str	$mac_address			MACアドレス
 * @return	なし
 * @info	MACアドレスをライセンスIDをkeyにして暗号化する
 */
function getLicenseID($mac_address){
	$api = new APItool();
	$encdata = $api->pyEncrypt($mac_address, LICENSE_CODE);
	return $encdata;
}
?>
