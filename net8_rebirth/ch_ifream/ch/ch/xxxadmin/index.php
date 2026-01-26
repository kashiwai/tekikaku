<?php
/*
 * index.php
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
 * 管理画面TOP表示
 * 
 * 管理画面TOPの表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/06/21 初版作成 片岡 充
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

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
		// 管理系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();

		// トップ画面
		DispTop($template);

	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * TOP画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {
	
	// 各項目の期間定義
	// 日付設定
	$now = date("Y/m/d H:i:s");
	$today = GetRefTimeOffsetStart(0);		// 当日開始
	$todayEnd = GetRefTimeOffsetStart(1);	// 当日終了(含まないので翌日の開始)
	$yestaday = GetRefTimeOffsetStart(-1);	// 前日開始
	$this_month = date("Y/m/01 H:i:s", strtotime($today));	// 当月開始
	$last_month = date("Y/m/01 H:i:s", strtotime($this_month . " -1 months"));	// 前月開始
	// パラメータ等の加工用にタイムスタンプも設定
	$nowTimestamp = strtotime($now);
	$tdTimestamp = strtotime($today);		// 当日
	$ydTimestamp = strtotime($yestaday);	// 昨日
	$lmTimestamp = strtotime($last_month);	// 前月
	//
	$memberRegistDate = array(
		//昨日
		 array( "name"=>"_y", "s"=>$yestaday, "e"=>$today)
		//当月
		,array( "name"=>"_m", "s"=>$this_month, "e"=>$todayEnd)
		//先月
		,array( "name"=>"_l", "s"=>$last_month, "e"=>$this_month)
	);
	
	$mem_join_count_sql  = array();
	$mem_leave_count_sql = array();
	$his_purchase_count_sql  = array();
	$his_purchase_amount_sql = array();
	$his_play_count_sql  = array();
	$his_play_credit_sql = array();
	$goods_blocks_sql = array();
	$win_blocks_sql = array();
	// 2021/01 [ADD Start] エージェントギフト
	$agentGiftSendCount_sql = array();
	$agentGiftSendPoint_sql = array();
	$agentGiftRecvCount_sql = array();
	$agentGiftRecvPoint_sql = array();
	// 2021/01 [ADD End] エージェントギフト

	//
	foreach( $memberRegistDate as $check_date){
		// 登録会員数
		$mem_join_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_member mm")
			->where()
				->and( false, "mm.join_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "mm.join_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as join" . $check_date["name"];
		// 退会会員数
		$mem_leave_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("mst_member mm")
			->where()
				->and( false, "mm.quit_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "mm.quit_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "mm.state = ", "9", FD_NUM)
			->createSql().") as leave" . $check_date["name"];
		// 売上件数
		$his_purchase_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_purchase hp")
			->where()
				->and( false, "hp.purchase_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.purchase_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hp.result_status = ", "1", FD_NUM)
				->and( false, "hp.purchase_type != ", "11", FD_STR)		// 抽選ポイントを除く
			->createSql().") as amount_count" . $check_date["name"];
		// 売上金額
		$his_purchase_amount_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.amount)" )
			->from("his_purchase hp")
			->where()
				->and( false, "hp.purchase_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.purchase_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hp.result_status = ", "1", FD_NUM)
				->and( false, "hp.purchase_type != ", "11", FD_STR)		// 抽選ポイントを除く
			->createSql().") as amount_value" . $check_date["name"];
		// 総ゲーム数
		$his_play_count_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.play_count)" )
			->from("his_play hp")
			->where()
				->and( false, "hp.end_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.end_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as play_count" . $check_date["name"];
		// 差枚数
		$his_play_credit_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hp.in_credit) - sum(hp.out_credit)" )
			->from("his_play hp")
			->where()
				->and( false, "hp.end_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hp.end_dt <  ", $check_date["e"], FD_DATE)
			->createSql().") as credit" . $check_date["name"];
		// 2021/01 [ADD Start] エージェントギフト
		// エージェント送信件数
		$agentGiftSendCount_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_gift hg")
			->where()
				->and( false, "hg.gift_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hg.gift_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hg.agent_flg = ", 1, FD_DATE)
			->createSql().") as send_count" . $check_date["name"];
		// エージェント送信ポイント
		$agentGiftSendPoint_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hg.gift_point)" )
			->from("his_gift hg")
			->where()
				->and( false, "hg.gift_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hg.gift_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hg.agent_flg = ", 1, FD_DATE)
			->createSql().") as send_point" . $check_date["name"];
		// エージェント受取件数
		$agentGiftRecvCount_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_gift hg")
			->where()
				->and( false, "hg.gift_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hg.gift_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hg.receive_agent_flg = ", 1, FD_DATE)
			->createSql().") as recv_count" . $check_date["name"];
		// エージェント受取ポイント
		$agentGiftRecvPoint_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "sum(hg.receive_point)" )
			->from("his_gift hg")
			->where()
				->and( false, "hg.gift_dt >= ", $check_date["s"], FD_DATE)
				->and( false, "hg.gift_dt <  ", $check_date["e"], FD_DATE)
				->and( false, "hg.receive_agent_flg = ", 1, FD_DATE)
			->createSql().") as recv_point" . $check_date["name"];
		// 2021/01 [ADD End] エージェントギフト
	}
	
	//-- 商品はリアル日時で判定
	// 応募期間中の商品
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.recept_start_dt <= ", $now, FD_DATE)
			->and( false, "mg.recept_end_dt >= "  , $now, FD_DATE)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as in_goods";
	// 自動抽選待ちの商品		応募終了～ and draw_state = 0 and draw_type = 1
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.draw_dt <= "   , $now, FD_DATE)
			->and( false, "mg.draw_state = " , 0, FD_NUM)
			->and( false, "mg.draw_type = "  , 1, FD_NUM)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as wait_goods";
	// 手動抽選待ちの商品	抽選日～ and draw_state = 0 and draw_type = 2
	$goods_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("mst_goods mg")
		->where()
			->and( false, "mg.draw_dt <= "   , $now, FD_DATE)
			->and( false, "mg.draw_state = " , 0, FD_NUM)
			->and( false, "mg.draw_type = "  , 2, FD_NUM)
			->and( false, "mg.del_flg = ", "0", FD_NUM)
		->createSQL().") as wait_manual_goods";

	// 発送先入力待ち
	$win_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_win dw")
		->where()
			->and( false, "dw.state = " , 0, FD_NUM)
		->createSQL().") as wait_input";
	// 発送待ち
	$win_blocks_sql[] = "(".(new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("count(*)")
		->from("dat_win dw")
		->where()
			->and( false, "dw.state in " , ["1", "2"], FD_NUM)
		->createSQL().") as wait_send";
	
	// 会員登録件数
	$sql = "select "
		.     implode(",", $mem_join_count_sql);
	$joinCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 会員退会件数
	$sql = "select "
		.     implode(",", $mem_leave_count_sql);
	$leaveCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 売上件数
	$sql = "select "
		.     implode(",", $his_purchase_count_sql);
	$purchaseCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 売上金額
	$sql = "select "
		.     implode(",", $his_purchase_amount_sql);
	$amountCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// ゲーム数
	$sql = "select "
		.     implode(",", $his_play_count_sql);
	$playCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 差枚数
	$sql = "select "
		.     implode(",", $his_play_credit_sql);
	$creditCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 2021/01 [ADD Start] エージェントギフト
	if (ADMTOP_GIFT_AGENT) {
		// エージェント送信件数
		$sql = "select "
			.     implode(",", $agentGiftSendCount_sql);
		$agentGiftSendCount = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// エージェント送信ポイント
		$sql = "select "
			.     implode(",", $agentGiftSendPoint_sql);
		$agentGiftSendPoint = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// エージェント受取件数
		$sql = "select "
			.     implode(",", $agentGiftRecvCount_sql);
		$agentGiftRecvCount = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// エージェント受取ポイント
		$sql = "select "
			.     implode(",", $agentGiftRecvPoint_sql);
		$agentGiftRecvPoint = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	}
	// 2021/01 [ADD End] エージェントギフト
	// 抽選
	$sql = "select "
		.     implode(",", $goods_blocks_sql);
	$goodsCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	// 発送
	$sql = "select "
		.     implode(",", $win_blocks_sql);
	$winCounts = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);

	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	//
	$template->assign("DISP_YESTADAY" , date("m/d", $ydTimestamp) . "(".$GLOBALS["weekList"][ date('w', $ydTimestamp)].")", true);
	$template->assign("DISP_MONTH"    , date("Y/m", strtotime($this_month)), true);
	$template->assign("DISP_LASTMONTH", date("Y/m", strtotime($last_month)), true);
	$template->assign("DISP_REGIST_MEMBER_Y", number_format( $joinCounts["join_y"]), true);
	$template->assign("DISP_REGIST_MEMBER_M", number_format( $joinCounts["join_m"]), true);
	$template->assign("DISP_REGIST_MEMBER_L", number_format( $joinCounts["join_l"]), true);
	$template->assign("DISP_UNSUB_MEMBER_Y" , number_format( $leaveCounts["leave_y"]), true);
	$template->assign("DISP_UNSUB_MEMBER_M" , number_format( $leaveCounts["leave_m"]), true);
	$template->assign("DISP_UNSUB_MEMBER_L" , number_format( $leaveCounts["leave_l"]), true);
	$template->assign("DISP_SALES_COUNT_Y", number_format( $purchaseCounts["amount_count_y"]), true);
	$template->assign("DISP_SALES_COUNT_M", number_format( $purchaseCounts["amount_count_m"]), true);
	$template->assign("DISP_SALES_COUNT_L", number_format( $purchaseCounts["amount_count_l"]), true);
	$template->assign("DISP_SALES_VALUE_Y", number_format( $amountCounts["amount_value_y"]), true);
	$template->assign("DISP_SALES_VALUE_M", number_format( $amountCounts["amount_value_m"]), true);
	$template->assign("DISP_SALES_VALUE_L", number_format( $amountCounts["amount_value_l"]), true);
	$template->assign("DISP_GAME_COUNT_Y", number_format( $playCounts["play_count_y"]), true);
	$template->assign("DISP_GAME_COUNT_M", number_format( $playCounts["play_count_m"]), true);
	$template->assign("DISP_GAME_COUNT_L", number_format( $playCounts["play_count_l"]), true);
	$template->assign("DISP_CREDIT_Y", number_format( $creditCounts["credit_y"]), true);
	$template->assign("DISP_CREDIT_M", number_format( $creditCounts["credit_m"]), true);
	$template->assign("DISP_CREDIT_L", number_format( $creditCounts["credit_l"]), true);
	// 2021/01 [ADD Start] エージェントギフト
	if (ADMTOP_GIFT_AGENT) {
		$template->assign("DISP_AGENT_GIFTSEND_COUNT_Y", number_format( $agentGiftSendCount["send_count_y"]), true);
		$template->assign("DISP_AGENT_GIFTSEND_COUNT_M", number_format( $agentGiftSendCount["send_count_m"]), true);
		$template->assign("DISP_AGENT_GIFTSEND_COUNT_L", number_format( $agentGiftSendCount["send_count_l"]), true);
		$template->assign("DISP_AGENT_GIFTSEND_POINT_Y", number_format( $agentGiftSendPoint["send_point_y"]), true);
		$template->assign("DISP_AGENT_GIFTSEND_POINT_M", number_format( $agentGiftSendPoint["send_point_m"]), true);
		$template->assign("DISP_AGENT_GIFTSEND_POINT_L", number_format( $agentGiftSendPoint["send_point_l"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_COUNT_Y", number_format( $agentGiftRecvCount["recv_count_y"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_COUNT_M", number_format( $agentGiftRecvCount["recv_count_m"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_COUNT_L", number_format( $agentGiftRecvCount["recv_count_l"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_POINT_Y", number_format( $agentGiftRecvPoint["recv_point_y"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_POINT_M", number_format( $agentGiftRecvPoint["recv_point_m"]), true);
		$template->assign("DISP_AGENT_GIFTRECV_POINT_L", number_format( $agentGiftRecvPoint["recv_point_l"]), true);
	}
	$template->assign("GIFT_AGENT_DISPNAME" , GIFT_AGENT_DISPNAME, true);
	$template->if_enable("GIFT_AGENT"       , GIFT_AGENT);
	$template->if_enable("ADMTOP_GIFT_AGENT", ADMTOP_GIFT_AGENT);
	// 2021/01 [ADD End] エージェントギフト
	// 応募状況
	$template->assign("DSP_NOW"               , date("Y/m/d H:i", $nowTimestamp), true);
	$template->assign("DISP_IN_GOODS"         , number_format( $goodsCounts["in_goods"]), true);
	$template->assign("DISP_WAIT_GOODS"       , number_format( $goodsCounts["wait_goods"]), true);
	$template->assign("DISP_WAIT_MANUAL_GOODS", number_format( $goodsCounts["wait_manual_goods"]), true);
	// 配送状況
	$template->assign("DISP_WAIT_INPUT_ADDRESS", number_format( $winCounts["wait_input"]), true);
	$template->assign("DISP_WAIT_SEND"         , number_format( $winCounts["wait_send"]), true);
	
	// リンク関連
	$now             = urlencode(date("Y/m/d" , $nowTimestamp));	// 商品なのでリアル日時
	$yestaday        = urlencode(date("Y/m/d" , $ydTimestamp));
	$today           = urlencode(date("Y/m/d" , $tdTimestamp));
	$this_month      = urlencode(date("Y/m/01", $tdTimestamp));
	$last_month      = urlencode(date("Y/m/01", $lmTimestamp));
	$last_month_last = urlencode(date("Y/m/t" , $lmTimestamp));

	$template->assign("DT_FROM_Y", $yestaday, true);
	$template->assign("DT_TO_Y"  , $yestaday, true);
	$template->assign("DT_FROM_M", $this_month, true);
	$template->assign("DT_TO_M"  , $today, true);
	$template->assign("DT_FROM_L", $last_month, true);
	$template->assign("DT_TO_L"  , $last_month_last, true);
	$template->assign("DT_NOW"   , $now, true);
	
	// === 追加: 日付・利益計算 ===
	// 今日の日付（わかりやすく表示）
	$template->assign("DISP_TODAY_DATE", date("Y年m月d日", $tdTimestamp), true);
	$template->assign("DISP_TODAY_WEEKDAY", $GLOBALS["weekList"][date('w', $tdTimestamp)], true);
	$template->assign("DISP_YESTERDAY_DATE", date("Y年m月d日", $ydTimestamp), true);
	$template->assign("DISP_YESTERDAY_WEEKDAY", $GLOBALS["weekList"][date('w', $ydTimestamp)], true);

	// 利益計算（売上金額 - ユーザーへの払い出し相当）
	// クレジット差分がマイナス = ユーザーが勝った = 運営の支出
	// クレジット差分がプラス = ユーザーが負けた = 運営の収入
	$creditY = intval($creditCounts["credit_y"]);
	$creditM = intval($creditCounts["credit_m"]);
	$creditL = intval($creditCounts["credit_l"]);
	$salesY = intval($amountCounts["amount_value_y"]);
	$salesM = intval($amountCounts["amount_value_m"]);
	$salesL = intval($amountCounts["amount_value_l"]);

	// ゲーム収支（クレジット差分を金額換算：1クレジット = 設定値円）
	// CREDIT_RATE が定義されていない場合は 1円とする
	$creditRate = defined('CREDIT_RATE') ? CREDIT_RATE : 1;
	$gameIncomeY = $creditY * $creditRate;
	$gameIncomeM = $creditM * $creditRate;
	$gameIncomeL = $creditL * $creditRate;

	// 総利益 = 売上 + ゲーム収支
	$profitY = $salesY + $gameIncomeY;
	$profitM = $salesM + $gameIncomeM;
	$profitL = $salesL + $gameIncomeL;

	$template->assign("DISP_GAME_INCOME_Y", ($gameIncomeY >= 0 ? "+" : "") . number_format($gameIncomeY), true);
	$template->assign("DISP_GAME_INCOME_M", ($gameIncomeM >= 0 ? "+" : "") . number_format($gameIncomeM), true);
	$template->assign("DISP_GAME_INCOME_L", ($gameIncomeL >= 0 ? "+" : "") . number_format($gameIncomeL), true);
	$template->assign("DISP_PROFIT_Y", number_format($profitY), true);
	$template->assign("DISP_PROFIT_M", number_format($profitM), true);
	$template->assign("DISP_PROFIT_L", number_format($profitL), true);

	// 利益の正負判定用
	$template->if_enable("PROFIT_Y_POSITIVE", $profitY >= 0);
	$template->if_enable("PROFIT_M_POSITIVE", $profitM >= 0);
	$template->if_enable("PROFIT_L_POSITIVE", $profitL >= 0);
	$template->if_enable("PROFIT_Y_NEGATIVE", $profitY < 0);
	$template->if_enable("PROFIT_M_NEGATIVE", $profitM < 0);
	$template->if_enable("PROFIT_L_NEGATIVE", $profitL < 0);

	// 表示
	$template->flush();
}

?>
