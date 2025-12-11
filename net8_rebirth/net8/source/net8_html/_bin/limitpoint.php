#!/usr/bin/php -q
<?php
/*
 * limitpoint.php
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
 * ポイント有効期限処理
 * 
 * ポイント有効期限処理を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files_batch.php');			// requireファイル

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
	// 多重起動回避
	$filepath = DIR_BIN . "limitpoint.txt";
	$lastRunTime = "";		// 前回実行時間
	// ファイル内容取得
	if (file_exists($filepath)) {
		$old_pid = file_get_contents($filepath);
		if (mb_strlen($old_pid) > 0) {
			$check = exec("ps ax | awk '$1==" . trim($old_pid) . " {print $5}'");
			// 前回のプロセスが起動中なら、処理を抜ける
			if (isset($check) && mb_strlen($check) > 0) exit;
		}
		$lastRunTime = date("Y-m-d H:i:s", filemtime($filepath));
	}
	// プロセスID更新(start)
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, getmypid());
		fclose($fp);
	}

	// DBクラスインスタンス生成
	$db = new NetDB();

	// ポイント有効期限処理
	$db->autoCommit(false);		// トランザクション開始
	
	$now    = date("Y-m-d H:i:00");
	$f_chk  = date('Y-m-d H:i:00', strtotime( "+". (POINT_LIMIT_ALERT_DAYS) ." day"));
	$lastRunTime = ((mb_strlen($lastRunTime) > 0) ? date('YmdHi00', strtotime( "+". (POINT_LIMIT_ALERT_DAYS) ." day", strtotime($lastRunTime))) : "");
	
	$PPT     = new PlayPoint($db, false);
	$ret_chk = $PPT->checkExpired($f_chk);
	$ret_exp = $PPT->removeExpired($now, BATCH_UPD_NO);
	
	$contact = new ContactBox( $db, false);		// ContactBox classインスタンス生成
	// 「有効期限切れ注意」を連絡ボックスに送信
	foreach( $ret_chk as $row){
		// 有効期限切れ POINT_LIMIT_ALERT_DAYS日前のデータのみ
		if(mb_strlen($lastRunTime) <= 0 || date("YmdHi00", strtotime($row["limit_dt"])) > $lastRunTime){
			//連絡Box登録
			$contact_message = array();
			$search  = array( "%LIMIT_DT%", "%POINT%", "%LABEL_1%");
			foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
				$replace = array( POINT_LIMIT_ALERT_DAYS, $row["valid_point"], $GLOBALS["unitLangList"][$k]["1"]);
				$contact_message[$k] = str_replace( $search, $replace, $v["06"]);
			}
			$contact->addOneRecord( $row["member_no"], "06", $row["member_no"], $contact_message, "", BATCH_UPD_NO);
		}
	}
	
	// 「有効期限切れ」を連絡ボックスに送信
	foreach( $ret_exp as $row){
		//連絡Box登録
		$contact_message = array();
		$search  = array( "%POINT%", "%LABEL_1%");
		foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
			$replace = array( $row["valid_point"], $GLOBALS["unitLangList"][$k]["1"]);
			$contact_message[$k] = str_replace( $search, $replace, $v["05"]);
		}
		$contact->addOneRecord( $row["member_no"], "05", $row["member_no"], $contact_message, "", BATCH_UPD_NO);
	}
	
	$db->autoCommit(true);	// コミット(トランザクション終了)
	$db->disconnect();		// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}
?>
