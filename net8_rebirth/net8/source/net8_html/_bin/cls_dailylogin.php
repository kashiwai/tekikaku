#!/usr/bin/php -q
<?php
/*
 * cls_dailylogin.php
 * 
 * (C)SmartRams Co.,Ltd. 2020 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * 連続ログイン回数クリア
 * 
 * 実機プレイデータの更新、履歴移行を行う
 * 
 * @package
 * @author		岡本静子
 * @version		1.00
 * @since		2020/07/02	v1.00	岡本静子	新規作成
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
 */
function main() {

	// 多重起動回避
	$filepath = DIR_BIN . "cls_dailylogin.txt";
	// ファイル内容取得
	if (file_exists($filepath)) {
		$old_pid = file_get_contents($filepath);
		if (mb_strlen($old_pid) > 0) {
			$check = exec("ps ax | awk '$1==" . trim($old_pid) . " {print $5}'");
			// 前回のプロセスが起動中なら、処理を抜ける
			if (isset($check) && mb_strlen($check) > 0) exit;
		}
	}
	// プロセスID更新(start)
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, getmypid());
		fclose($fp);
	}

	$preStartDt = GetRefTimeOffsetStart(-1);	// 前日の営業開始
	$nowStartDt = GetRefTimeOffsetStart( 0);	// 当日の営業開始

	// DB接続
	$DB = new NetDB();
	// トランザクション開始
	$DB->autoCommit(false);

	// 前日の営業開始から当日の営業開始までにログイン履歴が存在しない会員のログイン日数をクリアする
	$sql = (new SqlString($DB))
		->update("mst_member")
			->set()
				->value("login_days", "0", FD_NUM)
				->value("upd_no"    , BATCH_UPD_NO, FD_NUM)
				->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
			->where()
				->and("state = ", "1", FD_NUM)
				->subQuery("member_no not",
							(new SqlString($DB))
								->select()
									->field("member_no")
									->from("his_member_login")
									->where()
										->and("login_dt >= ", $preStartDt, FD_DATE)
										->and("login_dt < " , $nowStartDt, FD_DATE)
								->createSQL("\n")
							)
		->createSQL("\n");
	$DB->exec($sql);

	// 0クリされた当日ログイン会員の日数を1にする
	$sql = (new SqlString($DB))
		->update("mst_member")
			->set()
				->value("login_days", "1", FD_NUM)
				->value("upd_no"    , BATCH_UPD_NO, FD_NUM)
				->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
			->where()
				->and("state = "     , "1", FD_NUM)
				->and("login_days = ", "0", FD_NUM)
				->and("login_dt >= " , $nowStartDt, FD_DATE)
		->createSQL("\n");
	$DB->exec($sql);

	// コミット(トランザクション終了)
	$DB->autoCommit(true);
	$DB->disconnect();	// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

?>
