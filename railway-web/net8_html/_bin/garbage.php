#!/usr/bin/php -q
<?php
/*
 * garbage.php
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
 * 不要データ削除
 * 
 * 不要データの削除処理を行う
 * 
 * @package
 * @author		岡本静子
 * @version		1.00
 * @since		2020/07/02	v1.00	岡本静子	新規作成
 */

// インクルード
require_once('../_etc/require_files_batch.php');			// requireファイル

// 単純削除定義(日付 + 単純条件で削除可能なもの)
$deletionSimplicity = array( array( "save"  => 30						// 保存日数
								, "table"   => "mst_member"				// 対象テーブル
								, "col"     => "regist_dt"				// 条件日付項目
								, "other"   => "and state = 0"			// その他条件
								, "remarks" => "有効期限切：仮登録会員"	// 備考
								)
/*
						, array( "save"     => 365
								, "table"   => "his_member_login"
								, "col"     => "login_dt"
								, "other"   => ""
								, "remarks" => "保存期間：会員ログイン履歴"
								)
*/
						, array( "save"     => 90
								, "table"   => "dat_address"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：宛先データ"
								)
						, array( "save"     => 90
								, "table"   => "mst_maker"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：メーカマスタ"
								)
						, array( "save"     => 90
								, "table"   => "mst_camera"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：カメラマスタ"
								)
						, array( "save"     => 90
								, "table"   => "mst_owner"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：オーナーマスタ"
								)
						, array( "save"     => 90
								, "table"   => "mst_corner"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：コーナーマスタ"
								)
						, array( "save"     => 90
								, "table"   => "mst_convertPoint"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：ポイント変換マスタ"
								)
						, array( "save"     => 90
								, "table"   => "mst_purchasePoint"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：ポイント購入マスタ"
								)
						, array( "save"     => 30
								, "table"   => "dat_giftSMS"
								, "col"     => "limit_dt"
								, "other"   => ""
								, "remarks" => "有効期限切：ギフトSMS認証データ"
								)
						, array( "save"     => 90
								, "table"   => "dat_magazine"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：メルマガ"
								)
						, array( "save"     => 90
								, "table"   => "mst_cameralist"
								, "col"     => "del_dt"
								, "other"   => "and del_flg = 1"
								, "remarks" => "削除：カメラ端末管理"
								)
						, array( "save"     => 30
								, "table"   => "dat_mail_identify"
								, "col"     => "limit_dt"
								, "other"   => ""
								, "remarks" => "有効期限切：メール識別データ"
								)
						, array( "save"     => 30
								, "table"   => "dat_sms_identify"
								, "col"     => "limit_dt"
								, "other"   => ""
								, "remarks" => "有効期限切：SMS識別データ"
								)
							);

// 親子削除定義(親子関係で単純に削除可能なもの) ※その他、リンク条件親別名：par, 子別名：chi
$deletionRelation = array( array( "save"        => MSG_HISTORY_VIEW_DAYS + 30	// 保存日数
								, "col"         => "delivery_dt"				// 条件日付項目
								, "other"       => ""							// その他条件
								, "parent"      => "dat_contactBox"				// 親テーブル
								, "child"       => "dat_contactBox_lang"		// 子テーブル
								, "join"        => "par.member_no = chi.member_no and par.seq = chi.seq"	// リンク条件
								, "remarks"     => "期限切：連絡Box"			// 備考
								)
						, array( "save"         => 90
								, "col"         => "del_dt"
								, "other"       => "and par.del_flg = 1"
								, "parent"      => "dat_coupon"
								, "child"       => "dat_coupon_lang"
								, "join"        => "par.coupon_no = chi.coupon_no"
								, "remarks"     => "削除：クーポン"
								)
						, array( "save"         => 180
								, "col"         => "send_end_dt"
								, "other"       => "and par.magazine_state = 2"
								, "parent"      => "dat_magazine"
								, "child"       => "dat_magazineTarget"
								, "join"        => "par.magazine_no = chi.magazine_no"
								, "remarks"     => "完了：メルマガ"
								)
							);

// 親子削除親画像付き定義(親子関係で単純に削除可能なものに画像付き) ※その他、リンク条件親別名：par, 子別名：chi
$deletionRelationImage = array( array( "save"   => 90	// 保存日数
								, "col"         => "del_dt"			// 条件日付項目
								, "other"       => "and par.del_flg = 1"	// その他条件
								, "parent"      => "mst_goods"			// 親テーブル
								, "child"       => "mst_goods_lang"		// 子テーブル
								, "join"        => "par.goods_no = chi.goods_no"	// リンク条件
								, "image_path"  => array(DIR_IMG_GOODS)		// 画像ベースパス
								, "image_col"   => array("par.goods_image")	// 画像項目名(別名必須)
								, "remarks"     => "削除：商品マスタ"		// 備考
								)
						, array( "save"         => 90
								, "col"         => "del_dt"
								, "other"       => "and par.del_flg = 1"
								, "parent"      => "dat_notice"
								, "child"       => "dat_notice_lang"
								, "join"        => "par.notice_no = chi.notice_no"
								, "image_path"  => array(DIR_IMG_NOTICE)
								, "image_col"   => array("chi.top_image")
								, "remarks"     => "削除：お知らせデータ"
								)
							);

// 親子孫削除定義(三世代で単純に削除可能なもの) ※その他、リンク条件親別名：par, 子別名：chi, 孫別名：gra
$deletionThreeRelation = array( array( "save"   => 180							// 保存日数
								, "col"         => "grant_dt"					// 条件日付項目
								, "other"       => "and par.coupon_state = 1"	// その他条件
								, "parent"      => "dat_coupon"			// 親テーブル
								, "child"       => "dat_coupon_lang"	// 子テーブル
								, "join"        => "par.coupon_no = chi.coupon_no"	// リンク条件
								, "grandchild"  => "log_coupon"			// 孫テーブル
								, "grand_join"  => "par.coupon_no = gra.coupon_no"	// 孫リンク条件
								, "remarks"     => "完了：クーポン"		// 備考
								)
							);

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
	$filepath = DIR_BIN . "garbage.txt";
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

	// 初期処理
	global $deletionSimplicity, $deletionRelation, $deletionRelationImage, $deletionThreeRelation;
	$currentDatetime = date("Y/m/d H:i:s");		// 基準日時取得
	// DBのインスタンス生成
	$db = new NetDB();

	//-- 単純削除
	$sqlBase = "delete from %%TABLE%%" . "\n"
			. " where %%COL%% <= %%SAVE%%" . "\n"
			. " %%OTHER%%";
	$db->autoCommit(false);	//トランザクション開始
	foreach ($deletionSimplicity as $delInfo) {
		if ($delInfo["save"] == 0) continue;

		$saveLimit = date("Y/m/d H:i:s", strtotime("-" 
						. $delInfo["save"] . " day " . $currentDatetime));	// 保存期限
		$sql = str_replace("%%TABLE%%", $delInfo["table"], $sqlBase);
		$sql = str_replace("%%COL%%"  , $delInfo["col"], $sql);
		$sql = str_replace("%%SAVE%%" , $db->conv_sql($saveLimit, FD_DATE), $sql);
		$sql = str_replace("%%OTHER%%", $delInfo["other"], $sql);
		$db->exec($sql);
	}
	$db->autoCommit(true);	// コミット(トランザクション終了)

	//-- 親子削除
	$sqlBase = "delete par.*, chi.*" . "\n"
			. " from %%PTABLE%% par left join %%CTABLE%% chi" . "\n"
			. " on %%JOIN%%" . "\n"
			. " where par.%%COL%% <= %%SAVE%%" . "\n"
			. " %%OTHER%%";
	$db->autoCommit(false);	//トランザクション開始
	foreach ($deletionRelation as $delInfo) {
		if ($delInfo["save"] == 0) continue;

		$saveLimit = date("Y/m/d H:i:s", strtotime("-" 
						. $delInfo["save"] . " day " . $currentDatetime));	// 保存期限
		$sql = str_replace("%%PTABLE%%", $delInfo["parent"], $sqlBase);
		$sql = str_replace("%%CTABLE%%", $delInfo["child"], $sql);
		$sql = str_replace("%%JOIN%%"  , $delInfo["join"], $sql);
		$sql = str_replace("%%COL%%"   , $delInfo["col"], $sql);
		$sql = str_replace("%%SAVE%%"  , $db->conv_sql($saveLimit, FD_DATE), $sql);
		$sql = str_replace("%%OTHER%%" , $delInfo["other"], $sql);
		$db->exec($sql);
	}
	$db->autoCommit(true);	// コミット(トランザクション終了)

	//-- 親子削除親画像付き
	$sqlBase = "delete par.*, chi.*" . "\n"
			. " from %%PTABLE%% par left join %%CTABLE%% chi" . "\n"
			. " on %%JOIN%%" . "\n"
			. " where par.%%COL%% <= %%SAVE%%" . "\n"
			. " %%OTHER%%";
	$sqlImgBase = "select CONCAT_WS(" . $db->conv_sql(",", FD_STR) . ", %%IMGCOLS%%) as img" . "\n"
			. " from %%PTABLE%% par left join %%CTABLE%% chi" . "\n"
			. " on %%JOIN%%" . "\n"
			. " where par.%%COL%% <= %%SAVE%%" . "\n"
			. " %%OTHER%%";

	$db->autoCommit(false);	//トランザクション開始
	foreach ($deletionRelationImage as $delInfo) {
		if ($delInfo["save"] == 0) continue;
		$saveLimit = date("Y/m/d H:i:s", strtotime("-" 
						. $delInfo["save"] . " day " . $currentDatetime));	// 保存期限

		//-- 画像削除
		// 画像名
		$imgCol = array();
		foreach($delInfo["image_col"] as $col) {
			$imgCol[] = "IFNULL(" . $col . ", '')";
		}
		$imgCols = implode(", ", $imgCol);

		// 対象画像取得
		$sql = str_replace("%%PTABLE%%" , $delInfo["parent"], $sqlImgBase);
		$sql = str_replace("%%CTABLE%%" , $delInfo["child"], $sql);
		$sql = str_replace("%%JOIN%%"   , $delInfo["join"], $sql);
		$sql = str_replace("%%COL%%"    , $delInfo["col"], $sql);
		$sql = str_replace("%%SAVE%%"   , $db->conv_sql($saveLimit, FD_DATE), $sql);
		$sql = str_replace("%%OTHER%%"  , $delInfo["other"], $sql);
		$sql = str_replace("%%IMGCOLS%%", $imgCols, $sql);
		$rs = $db->query($sql);
		while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
			foreach(explode(",", $row["img"]) as $fileName) {
				if (mb_strlen($fileName) <= 0) continue;
				foreach ($delInfo["image_path"] as $path) {
					$target = $path . $fileName;
					// ファイル削除
					deleteFile($target);
				}
			}
		}
		unset($rs);

		//-- データ削除
		$sql = str_replace("%%PTABLE%%", $delInfo["parent"], $sqlBase);
		$sql = str_replace("%%CTABLE%%", $delInfo["child"], $sql);
		$sql = str_replace("%%JOIN%%"  , $delInfo["join"], $sql);
		$sql = str_replace("%%COL%%"   , $delInfo["col"], $sql);
		$sql = str_replace("%%SAVE%%"  , $db->conv_sql($saveLimit, FD_DATE), $sql);
		$sql = str_replace("%%OTHER%%" , $delInfo["other"], $sql);

		$db->exec($sql);
	}
	$db->autoCommit(true);	// コミット(トランザクション終了)

	//-- 親子孫削除
	$sqlBase = "delete par.*, chi.*, gra.*" . "\n"
			. "from %%PTABLE%% par left join %%CTABLE%% chi" . "\n"
			. "on %%JOIN%%" . "\n"
			. "left join %%GTABLE%% gra" . "\n"
			. "on %%G_JOIN%%" . "\n"
			. " where par.%%COL%% <= %%SAVE%%" . "\n"
			. " %%OTHER%%";
	$db->autoCommit(false);	//トランザクション開始
	foreach ($deletionThreeRelation as $delInfo) {
		if ($delInfo["save"] == 0) continue;

		$saveLimit = date("Y/m/d H:i:s", strtotime("-" 
						. $delInfo["save"] . " day " . $currentDatetime));	// 保存期限
		$sql = str_replace("%%PTABLE%%", $delInfo["parent"], $sqlBase);
		$sql = str_replace("%%CTABLE%%", $delInfo["child"], $sql);
		$sql = str_replace("%%GTABLE%%", $delInfo["grandchild"], $sql);
		$sql = str_replace("%%JOIN%%"  , $delInfo["join"], $sql);
		$sql = str_replace("%%G_JOIN%%", $delInfo["grand_join"], $sql);
		$sql = str_replace("%%COL%%"   , $delInfo["col"], $sql);
		$sql = str_replace("%%SAVE%%"  , $db->conv_sql($saveLimit, FD_DATE), $sql);
		$sql = str_replace("%%OTHER%%" , $delInfo["other"], $sql);
		$db->exec($sql);
	}
	$db->autoCommit(true);	// コミット(トランザクション終了)

	$db->disconnect();	// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

/**
 * ファイル削除
 * @access	private
 * @param	string	$trgfile		対象ファイル
 * @return	なし
 */
function deleteFile($trgfile){
	if (is_file($trgfile)) @unlink($trgfile);
}

?>
