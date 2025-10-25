<?php
/*
 * sales.php
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
 * 売上管理画面表示
 * 
 * プレイ履歴画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
 */

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));			// テンプレートHTMLプレフィックス

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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();
		
		// 実処理
		DispList($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), true);
	}
}

/**
 * 一覧画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispList($template, $message = "") {
	
	getData($_POST , array("YEAR", "MONTH"));
	
	$year  = ( mb_strlen( $_POST["YEAR"]) > 0)? $_POST["YEAR"] : GetRefTimeToday("", "Y");
	$month = ( mb_strlen( $_POST["MONTH"]) > 0)? str_pad( $_POST["MONTH"], 2, 0, STR_PAD_LEFT) : GetRefTimeToday("", "m");
	
	// check
	if( !chk_date( $year ."-". $month."-01")){
		//err
		$message = "date err";
		$year  = GetRefTimeToday("", "Y");
		$month = GetRefTimeToday("", "m");
	}
	
	$start = $year ."-". $month . "-01";
	$end   = date('Y-m-d', strtotime('last day of ' . $start));

	// 対象年取得
	$endY = GetRefTimeToday("", "Y");	// 現在年
	$startY = $endY;	// 現在年を初期値にする
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("min(purchase_dt)" )
			->from("his_purchase" )
			->where()
				->and("result_status = ", "1", FD_NUM)
		->createSql("\n");
	$startDt = $template->DB->getOne($sql);	// 履歴の最小購入日時
	if (mb_strlen($startDt) > 0) {
		$startY = GetRefTimeToday($startDt, "Y");
	}
	
	$data = array();
	// 開始日～終了日までのデータを取得
	for ($calDate = strtotime($start); $calDate <= strtotime($end); $calDate = strtotime("+1 day", $calDate)) {
		// 取得用日付
		$stDtTime = GetRefTimeStart(date("Y-m-d", $calDate));
		$edDtTime = GetRefTimeEnd(date("Y-m-d"  , $calDate));
		// データ取得
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "hp.purchase_type, count(amount) as amount_count, sum(amount) as amount" )
				->from( "his_purchase hp" )
				->where()
					->and( true, "hp.purchase_dt >= ", $stDtTime, FD_STR)
					->and( true, "hp.purchase_dt <= ", $edDtTime, FD_STR)
					->and( true, "hp.result_status = ", "1", FD_NUM)
					->and( true, "hp.purchase_type <> ", "11", FD_NUM)
				->groupby("purchase_type")
				->orderby("purchase_type asc")
			->createSql("\n");
		$row = $template->DB->getAll( $sql, MDB2_FETCHMODE_ASSOC);
		foreach ($row as $v) {
			$data[date("Y-m-d", $calDate)][$v["purchase_type"]]["count"] = $v["amount_count"];		// [日付][種別][count]
			$data[date("Y-m-d", $calDate)][$v["purchase_type"]]["amount"] = $v["amount"];			// [日付][種別][amount]
		}
	}

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	// 条件指定用
	$template->assign("SEL_YEAR" , makeSelectYearTag($startY, $endY, $year, false));
	$template->assign("SEL_MONTH", makeSelectMonthTag($month, false));
	// 初期化
	$dispPurchaseType = $GLOBALS["viewPurchaseType"];
	unset($dispPurchaseType[11]);										// 「11：Coin交換」以外を表示
	$pickPurchaseType = "";												// 購入履歴情報パラメータ
	$purchaseList = "";													// グラフ用決済種別リスト
	//合計用データ
	$month_totals["total_count"] = 0;
	$month_totals["total_amount"] = 0;

	// 表見出し
	$template->loop_start("PURCHASE_NAME");
	foreach ($dispPurchaseType as $key => $value) {
		$month_totals[$key . "_count"] = 0;
		$month_totals[$key . "_amount"] = 0;
		$pickPurchaseType .= ((mb_strlen($pickPurchaseType) > 0) ? "&" : "") . "CHK_PURCHASE_TYPE%5B%5D=" . $key;
		$template->assign("PURCHASE_NAME", $value, true);			// 決済種別タイトル
		$purchaseList .= ((mb_strlen($purchaseList) > 0) ? "," : "") . "'". $value . "'";
		$template->loop_next();
	}
	$template->loop_end("PURCHASE_NAME");

	$template->block_start("PURCHASE_NAME2");	// ループしない合計のヘッダ(件数/金額)が置換内容と同じだと表示が増えるためブロック対応
	$template->loop_start("PURCHASE_NAME2");
	foreach ($dispPurchaseType as $key => $value) {
		$template->loop_next();
	}
	$template->loop_end("PURCHASE_NAME2");
	$template->block_end("PURCHASE_NAME2");

	//グラフ用データ
	$graphData = array();
	// デーブル
	$ymDate = $year."-".$month."-";
	$linkYm = $year."%2F".$month."%2F";
	$endDay = (int)date('j', strtotime('last day of ' . $start));
	$weeks  = date('w', strtotime($year."-".$month."-01"));
	$template->loop_start("LIST");
	for( $i=1; $i<=$endDay; $i++){
		$template->assign("DISP_DAYS"  , $i .'（<span class="'. (($weeks==0||$weeks==6)? 'weeklabel_'.$weeks:'') .'">'. $GLOBALS['weekList'][$weeks] .'</span>）');
		$template->assign("WEEK_CLASS" , " weeks_".$weeks);
		$template->assign("PICKDATE" , ($pickPurchaseType . "&S_STATUS=1&S_PURCHASE_DT_FROM=".$linkYm.str_pad( $i, 2, 0, STR_PAD_LEFT)."&S_PURCHASE_DT_TO=".$linkYm.str_pad( $i, 2, 0, STR_PAD_LEFT)), true);

		$date = $ymDate . str_pad( $i, 2, 0, STR_PAD_LEFT);
		$total_count = 0;
		$total_amount = 0;
		$graph["date"] = $date;			// グラフ用データ
		//金額
		$template->loop_start("PURCHASE_TYPE");
		foreach ($dispPurchaseType as $key => $value) {
			$count = 0;
			$amount = 0;
			if (isset($data[$date][$key])){
				$count = $data[$date][$key]["count"];
				$amount = $data[$date][$key]["amount"];
			}
			$template->assign("DISP_TYPE_COUNT"   , number_formatEx($count));
			$template->assign("DISP_TYPE_AMOUNT"  , number_formatEx($amount));
			// 合計
			$total_count += $count;
			$total_amount += $amount;
			$month_totals[$key . "_count"] += $count;
			$month_totals[$key . "_amount"] += $amount;
			$graph[$key] = $amount;		// グラフ用データ
			$template->loop_next();
		}
		$template->loop_end("PURCHASE_TYPE");

		$template->assign("DISP_TOTAL_COUNT"   , number_formatEx($total_count));
		$template->assign("DISP_TOTAL_AMOUNT"  , number_formatEx($total_amount));
		$month_totals["total_count"] += $total_count;
		$month_totals["total_amount"] += $total_amount;
		//グラフ用データ作成
		$graphData[] = $graph;

		$template->loop_next();

		$weeks++;
		if( $weeks>6) $weeks=0;
	}
	$template->loop_end("LIST");
	
	// 合計表示
	$template->assign("MONTH_TOTAL_PICKDATE"      , ($pickPurchaseType . "&S_STATUS=1&S_PURCHASE_DT_FROM=".$linkYm."01&S_PURCHASE_DT_TO=".$linkYm.str_pad( $endDay, 2, 0, STR_PAD_LEFT)), true);
	$template->assign("DISP_MONTH_TOTAL_COUNT"    , number_formatEx($month_totals["total_count"]), true);
	$template->assign("DISP_MONTH_TOTAL_AMOUNT"   , number_formatEx($month_totals["total_amount"]), true);
	$template->loop_start("PURCHASE_TYPE_TOTAL");
	foreach ($dispPurchaseType as $key => $value) {
		$template->assign("DISP_MONTH_TYPE_COUNT"   , number_formatEx($month_totals[$key . "_count"]), true);
		$template->assign("DISP_MONTH_TYPE_AMOUNT"  , number_formatEx($month_totals[$key . "_amount"]), true);
		$template->loop_next();
	}
	$template->loop_end("PURCHASE_TYPE_TOTAL");
	
	$nowday = (int)date('j');
	//--- 2021/04/01 Upd by S｡Okamoto 月初にグラフが表示されないので対応(1ヶ月分表示しているので特に月初を非表示にする必要なし)
	//if( $nowday < 3 || strtotime($year."-".$month."-01") > strtotime(date("Y-m-01"))){
	if( strtotime($year."-".$month."-01") > strtotime(date("Y-m-01"))){
		$template->if_enable("GRAPH_AREA", false);
	}
	$template->assign("PURCHASE_LIST", $purchaseList);
	// グラフ用データ
	$dates = "";
	$datas = "";
	foreach( $graphData as $k=>$v){
		$amountList = "";
		foreach ($dispPurchaseType as $key => $value) {
			$amount = (isset($data[$v["date"]][$key])) ? $data[$v["date"]][$key]["amount"] : 0;
			$amountList .= "," . $amount;
		}
		$y = (int)substr( $v["date"], 0, 4);
		$m = (int)substr( $v["date"], 5, 2) - 1;
		$d = (int)substr( $v["date"], 8, 2);
		$t = "new Date(". $y .",". $m .",". $d .")";
		$dates .= ",".$t;
		$datas .= "Click[". $k ."] = [". $t . $amountList ."];\n";
	}
	$template->assign("DAYS_COUNT" , count($graphData));
	$template->assign("DAYS_DATE"  , $dates);
	$template->assign("DATALIST", $datas);
	
	// 表示
	$template->flush();
	exit();
	
}

?>
