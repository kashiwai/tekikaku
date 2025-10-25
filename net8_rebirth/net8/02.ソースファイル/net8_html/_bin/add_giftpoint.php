#!/usr/bin/php -q
<?php
/*
 * add_giftpoint.php
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
 * ギフト可能ポイント加算処理
 * 
 * ギフト可能ポイントの加算処理を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.01
 * @since    2020/09/02 初版作成  岡本静子
 * @since    2020/09/02 v1.01改修 岡本静子 base時間より前設定を後設定にするよう変更(cron設定もあわせて変更)
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
	$filepath = DIR_BIN . "add_giftpoint.txt";
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

	// DBクラスインスタンス生成
	$db = new NetDB();
	// トランザクション開始
	$db->autoCommit(false);

	// 集計対象開始、終了日時設定
	if (TimeToNum(REFERENCE_TIME) == 0) {		// 使用開始時間基準
		$format = "Y/m/d H:i:s";
		$workDt = new DateTime(GetRefTimeTodayExt() . " " . GLOBAL_OPEN_TIME);
		$workDt->modify("-1 day");				//--- 2023/12/19 Add by S.Okamoto 前日を設定
		$startDt = $workDt->format($format);
		$workDt->modify("+1 day");
		$workDt->modify("-1 second");
		$endDt = $workDt->format($format);
	} else {		// Baseお時間基準
		//--- 2023/12/19 Upd S by S.Okamoto 前日を設定
		/*
		$startDt = GetRefTimeOffsetStart();
		$endDt = GetRefTimeOffsetEnd();
		*/
		$startDt = GetRefTimeOffsetStart(-1);
		$endDt = GetRefTimeOffsetEnd(-1);
		//--- 2023/12/19 Upd E
	}

	//-- プレイ集計
	// 設定取得
	$sql = (new SqlString($db))
		->select()
			->field("addset_no, addset_type, add_point, base_val")
			->from("mst_gift_addset")
			->where()
				->and("addset_type = ", 1, FD_NUM)	// プレイ
				->and("del_flg = "    , 0, FD_NUM)
			->orderby("addset_no asc")
		->createSql("\n");
	$rs = $db->query($sql);
	// 設定毎に処理する
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// ゲーム数取得
		$sql = (new SqlString($db))
			->select()
				->field("his.member_no, SUM(his.play_count) as game_count")
			->from("his_play his")
			->join("inner", "mst_member mem", "his.member_no = mem.member_no")
			->where()
				->and("his.convert_no = "    , $row["base_val"], FD_NUM)
				->and("his.end_dt", "between", $startDt, FD_DATE, $endDt, FD_DATE)
				->and("his.play_count >= ", 1, FD_NUM)	// 1以上
				->and("mem.state = "      , 1, FD_NUM)	// 本会員
				->and("mem.tester_flg = " , 0, FD_NUM)	// 一般会員(テスター以外)
				->and("mem.agent_flg = "  , 0, FD_NUM)	// 一般会員(エージェント以外)
			->groupby("his.member_no")
		->createSql("\n");
		$rsGame = $db->query($sql);
		while ($rowGame = $rsGame->fetch(MDB2_FETCHMODE_ASSOC)) {
			// 加算ポイント算出
			$addPoint = $row["add_point"] * ceil($rowGame["game_count"] / GIFT_GAME_THRESHOLD);
			// ポイント加算
			AddGifiPoint($db, $addPoint, $rowGame["member_no"], $row);
		}
		unset($rsGame);
	}
	unset($rs);

	//-- 購入集計
	// 設定取得
	$sql = (new SqlString($db))
		->select()
			->field("addset_no, addset_type, add_point, base_val")
			->from("mst_gift_addset")
			->where()
				->and("addset_type = ", 2, FD_NUM)	// 購入
				->and("del_flg = "    , 0, FD_NUM)
			->orderby("base_val desc")
		->createSql("\n");
	$addSet = $db->getAll($sql, MDB2_FETCHMODE_ASSOC);

	// 購入履歴取得
	$sql = (new SqlString($db))
		->select()
			->field("his.member_no, SUM(his.amount) as  price_count")
		->from("his_purchase his")
		->join("inner", "mst_member mem", "his.member_no = mem.member_no")
		->where()
			->and("his.purchase_dt"       , "between", $startDt, FD_DATE, $endDt, FD_DATE)
			->and("his.result_status = "  , "1", FD_NUM)
			->and("his.purchase_type != " , "11", FD_STR)
			->and("his.amount >= "        , 1, FD_NUM)		// 1以上
			->and("mem.state = "      , 1, FD_NUM)	// 本会員
			->and("mem.tester_flg = " , 0, FD_NUM)	// 一般会員(テスター以外)
			->and("mem.agent_flg = "  , 0, FD_NUM)	// 一般会員(エージェント以外)
		->groupby("his.member_no")
	->createSql("\n");
	$rs = $db->query($sql);
	// 会員毎に処理する
	while ($memRow = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// 金額設定
		$amount = $memRow["price_count"];
		// 設定全件 若しくは 金額がなくなるまで処理する
		for ($i = 0; $i < count($addSet); $i++) {
			// 加算ポイント算出
			$addPoint = $addSet[$i]["add_point"] * floor($amount / $addSet[$i]["base_val"]);
			// ポイント加算
			AddGifiPoint($db, $addPoint, $memRow["member_no"], $addSet[$i]);
			// 残金額設定
			$amount = $amount % $addSet[$i]["base_val"];
			if ($amount <= 0) break;	// 残が0以下は終了
		}
	}
	unset($rs);

	//-- ギフト受信
	// 設定取得
	$sql = (new SqlString($db))
		->select()
			->field("addset_no, addset_type, add_point, base_val")
			->from("mst_gift_addset")
			->where()
				->and("addset_type = ", 3, FD_NUM)	// ギフト受信
				->and("del_flg = "    , 0, FD_NUM)
			->orderby("base_val desc")
		->createSql("\n");
	$addSet = $db->getAll($sql, MDB2_FETCHMODE_ASSOC);

	// ギフト履歴取得
	$sql = (new SqlString($db))
		->select()
			->field("his.receive_member_no, SUM(his.receive_point) as  point_count")
		->from("his_gift his")
		->join("inner", "mst_member mem", "his.receive_member_no = mem.member_no")
		->where()
			->and("his.gift_dt"          , "between", $startDt, FD_DATE, $endDt, FD_DATE)
			->and("his.agent_flg = "     , "1", FD_NUM)	// エージェント
			->and("his.receive_point >= ", 1, FD_NUM)		// 1以上
			->and("mem.state = "      , 1, FD_NUM)	// 本会員
			->and("mem.tester_flg = " , 0, FD_NUM)	// 一般会員(テスター以外)
			->and("mem.agent_flg = "  , 0, FD_NUM)	// 一般会員(エージェント以外)
		->groupby("his.receive_member_no")
	->createSql("\n");
	$rs = $db->query($sql);
	// 会員毎に処理する
	while ($memRow = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// ポイント設定
		$point = $memRow["point_count"];
		// 設定全件 若しくは ポイントがなくなるまで処理する
		for ($i = 0; $i < count($addSet); $i++) {
			// 加算ポイント算出
			$addPoint = $addSet[$i]["add_point"] * floor($point / $addSet[$i]["base_val"]);
			// ポイント加算
			AddGifiPoint($db, $addPoint, $memRow["receive_member_no"], $addSet[$i]);
			// 残ポイント設定
			$point = $point % $addSet[$i]["base_val"];
			if ($point <= 0) break;	// 残が0以下は終了
		}
	}
	unset($rs);


	// コミット(トランザクション終了)
	$db->autoCommit(true);
	// DB解放
	$db->disconnect();

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

/**
 * ギフトポイント加算
 * @access	private
 * @param	object	$db			DBクラスオブジェクト
 * @param	int		$addPoint	加算ポイント
 * @param	int		$memberNo	会員No
 * @param	array	$setRow		加算設定
 * @return	なし
 */
function AddGifiPoint($db, $addPoint, $memberNo, $setRow) {
	if ($addPoint <= 0) return;		// 加算ポイント0以下は処理しない

	// 可能ポイント加算
	$sql = (new SqlString($db))
		->update("mst_member")
		->set()
			->value("gift_point"      , "gift_point + " . $db->conv_sql($addPoint, FD_NUM), FD_FUNCTION)
			->value("total_gift_point", "total_gift_point + " . $db->conv_sql($addPoint, FD_NUM), FD_FUNCTION)
			->value("upd_no"    , BATCH_UPD_NO, FD_NUM)
			->value("upd_dt"    , "current_timestamp", FD_FUNCTION)
		->where()
			->and("member_no = ", $memberNo, FD_NUM)
		->createSql("\n");
	$db->exec($sql);

	// 履歴登録
	$sql = (new SqlString($db))
		->insert()
			->into("his_gift_add")
				->value( "proc_dt"   , "CURRENT_DATE", FD_FUNCTION)
				->value("member_no"  , $memberNo, FD_NUM)
				->value("addset_no"  , $setRow["addset_no"], FD_NUM)
				->value("addset_type", $setRow["addset_type"], FD_NUM)
				->value("base_val"   , $setRow["base_val"], FD_NUM)
				->value("add_point"  , $addPoint, FD_NUM)
		->createSql("\n");
	$db->exec($sql);

	return;
}
?>
