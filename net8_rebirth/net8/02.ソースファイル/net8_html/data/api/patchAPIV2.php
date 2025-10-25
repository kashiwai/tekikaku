<?php
/*
 * patchAPIV2.php
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
 * カメラ端末パッチ情報API
 * 
 * 現在のlastupdateのファイルのタイムスタンプjsonを送る
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2020/04/20 流用 村上 俊行
 */

//テスト用設定


// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool
require_once('../../_etc/webRTC_camera_files.php');		// patch対象ファイル設定

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
		putJson($DB);
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * json出力
 * @access	private
 * @param	object		$DB			DBクラスオブジェクト
 * @return	なし
 */
function putJson($DB) {

	$api = new APItool();

	// データ取得
	getData($_GET, array( "MAC" ));
	//machine_noの取得
	$machine_no = getMachineNo($DB, $_GET["MAC"]);

	$ary = array();
	$skp = array();
	foreach ( $GLOBALS["webRTCV2_camera_Files"] as $filename ){
		$path = $GLOBALS["webRTCV2_lastupdate_path"].$filename;
		if ( file_exists($path) ){
			$rec["path"]     = $filename;
			$rec["datetime"] = date("Y-m-d H:i:s", filemtime($path) );
			
			//除外リストチェック
			if ( array_key_exists( $machine_no, $GLOBALS["webRTCV2_Exclusion_List"] ) ){
				//対象machine_noで除外リストにファイル名が存在していたらパッチリストに登録しない
				if ( in_array( $filename, $GLOBALS["webRTCV2_Exclusion_List"][$machine_no] ) ){
					array_push( $skp, $rec );
					continue;
				}
			}
			array_push( $ary, $rec );
		}
	}
	
	$api->set("files", $ary );
	$api->set("skips", $skp );
	
	$api->outputJson();
}

/**
 * MACアドレスからMACHINE_NOを取得
 * @access	
 * @param	object		$DB			DBクラスオブジェクト
 * @param	String		$MAC		MACアドレス
 * @return	string		machine_no	マシン番号
 */
function getMachineNo($DB, $MAC){

	if ( $MAC == '' ) return( "0" );

	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("dm.machine_no,mm.category,dm.signaling_id,dm.camera_no")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->where()
				->subQuery("camera_no",
							(new SqlString())
								->setAutoConvert( [$DB,"conv_sql"] )
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
	if ( !array_key_exists("machine_no", $row) ){
		return( "0" );
	} else {
		if ( mb_strlen($row["machine_no"]) == 0 ){
			return( "0" );
		} else {
			return( $row["machine_no"] );
		}
	}

}


?>
