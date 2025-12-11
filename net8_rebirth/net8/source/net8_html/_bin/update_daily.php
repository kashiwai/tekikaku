#!/usr/bin/php -q
<?php
/*
 * update_daily.php
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
 * 日次データ更新
 * 
 * 実機プレイデータの更新、履歴移行を行う
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/03/07 初版作成 村上俊行
 *           2020/09/23 修正     村上俊行  ログ項目追加による修正
 *           2020/12/07 修正     村上俊行  最高出玉追加による更新処理追加
 *           2020/12/22 修正     岡本静子  過去最大RB回数追加、過去最大BB更新変更
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
	$filepath = DIR_BIN . "update_daily.txt";
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

	$currentDatetime = date("Y/m/d H:i:s");
	$currentDate = GetRefTimeOffsetStart(-1, "Y/m/d");

	// DB接続
	$DB = new NetDB();

	//実機プレイデータを履歴に移行
	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select2insert()
			->into("his_machinePlay")
				->tofield("play_dt",     $currentDate, FD_DATE)
				->tofield("machine_no",  "machine_no")
				//2020-09-28 項目追加
				->tofield("day_count",   "day_count")
				->tofield("total_count", "total_count")
				->tofield("count",       "count")
				->tofield("bb_count",    "bb_count")
				->tofield("rb_count",    "rb_count")
				->tofield("in_credit",   "in_credit")
				->tofield("out_credit",  "out_credit")
				->tofield("hit_data",    "hit_data")
				//2020-09-23 項目追加
				->tofield("renchan_count",    "renchan_count")
				->tofield("maxrenchan_count", "maxrenchan_count")
				->tofield("tenjo_count",      "tenjo_count")
				->tofield("ichigeki_credit",  "ichigeki_credit")
				//2020-12-07 項目追加
				->tofield("max_credit",  "max_credit")
				->tofield("past_max_credit",  "past_max_credit")
				//2020-12-22 項目追加
				->tofield("past_max_bb",  "past_max_bb")
				->tofield("past_max_rb",  "past_max_rb")

				->tofield("add_dt",      $currentDatetime, FD_DATE)
			->from("dat_machinePlay")
			->where()
				->and("machine_no >=",   "0", FD_NUM)
		->createSQL("\n");
	$result = $DB->query($sql);

	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("machine_no,hit_data")
			->field("max_credit,past_max_credit,bb_count,past_max_bb")
			//2020-12-22 項目追加
			->field("rb_count,past_max_rb")
			->from("dat_machinePlay")
			->where()
				->and("machine_no >=",   "0", FD_NUM)
		->createSQL("\n");

	$records = $DB->getAll($sql);
	foreach( $records as $rec ){
		$olddata = json_decode($rec["hit_data"],true );
		$newdata = array();
		for( $i=0; $i<10; $i++ ){
			if( isset($olddata[$i]) ){
				$newdata[] = $olddata[$i];
			}
		}
		$json = json_encode( $newdata );
		
		//最高出玉設定
		if ( $rec['max_credit'] > $rec['past_max_credit'] ){
			$past_credit = $rec['max_credit'];
		} else {
			$past_credit = $rec['past_max_credit'];
		}
		//2020-12-22 過去最大RB回数追加、過去最大BB更新変更
		//最高BB
		if ( $rec['bb_count'] + $rec['rb_count'] > $rec['past_max_bb'] + $rec['past_max_rb'] ){
			$past_bb = $rec['bb_count'];
			$past_rb = $rec['rb_count'];
		} else {
			$past_bb = $rec['past_max_bb'];
			$past_rb = $rec['past_max_rb'];
		}
		
		$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
			->update("dat_machinePlay")
				//2020-09-28 項目追加
				->value("day_count", "0", FD_NUM)
				->value("total_count", "0", FD_NUM)
				->value("count",       "0", FD_NUM)
				->value("bb_count",    "0", FD_NUM)
				->value("rb_count",    "0", FD_NUM)
				->value("in_credit",   "0", FD_NUM)
				->value("out_credit",  "0", FD_NUM)
				->value("hit_data",    $json, FD_STR)
				//2020-09-23 項目追加
				->value("renchan_count",    "0", FD_NUM)
				->value("maxrenchan_count", "0", FD_NUM)
				->value("tenjo_count",      "0", FD_NUM)
				->value("ichigeki_credit",  "0", FD_NUM)
				//2020-12-07 項目追加
				->value("max_credit",       "0", FD_NUM)
				->value("past_max_credit",  $past_credit, FD_NUM)
				->value("past_max_bb",      $past_bb, FD_NUM)
				//2020-12-22 項目追加
				->value("past_max_rb",      $past_rb, FD_NUM)

				->value("upd_dt",      $currentDatetime, FD_DATE)
			->where()
				->and("machine_no =",  $rec["machine_no"], FD_NUM)
		->createSQL("\n");
		$result = $DB->query($sql);
	}
	$DB->disconnect();	// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

?>
