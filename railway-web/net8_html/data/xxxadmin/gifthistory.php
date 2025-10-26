<?php
/*
 * gifthistory.php
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
 * ギフト送信履歴情報画面表示
 * 
 * ギフト送信履歴情報画面の表示を行う
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
		
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理
		$mainWin = true;
		// 2021/01 [UPD Start] CSV追加
		switch ($_GET["M"]) {
			case "output":		// CSVダウンロード
				ProcOutput($template);
				break;
			default:			// 一覧画面
				DispList($template);
		}
		// 2021/01 [UPD End] CSV追加
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), $mainWin);
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
	
	// データ取得
	getData($_GET , array("P", "ODR", "S_MEMBER_NO","S_RECEIVE_MEMBER_NO", "S_GIFT_DT_FROM", "S_GIFT_DT_TO", "S_GIFT_POINT_FROM", "S_GIFT_POINT_TO"
							, "S_AGENT", "S_RECEIVE_AGENT", "S_EITHER_MEMBER_NO", "S_EITHER_AGENT"		// 2021/01 [ADD] VIPチェック、送信受取会員追加
						));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "hg.gift_dt desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// 検索判定
	if( ($_GET["S_MEMBER_NO"]!="") || ($_GET["S_RECEIVE_MEMBER_NO"]!="") || ($_GET["S_GIFT_DT_FROM"]!="") || ($_GET["S_GIFT_DT_TO"]!="") || ($_GET["S_GIFT_POINT_FROM"]!="") || ($_GET["S_GIFT_POINT_TO"]!="")
		 || ($_GET["S_AGENT"] != "") || ($_GET["S_RECEIVE_AGENT"] != "") || ($_GET["S_EITHER_MEMBER_NO"] != "") || ($_GET["S_EITHER_AGENT"] != "")) {	// 2021/01 [UPD] VIPチェック、送信受取会員追加
		$_search = "show";
	}else{
		$_search = "";
	}
	
	// 検索用日付
	$dtSt = ((mb_strlen($_GET["S_GIFT_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_GIFT_DT_FROM"]) : "");
	$dtEd = ((mb_strlen($_GET["S_GIFT_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_GIFT_DT_TO"]) : "");
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("his_gift hg")
			->where()
				->and(true, "hg.member_no = ",         $_GET["S_MEMBER_NO"], FD_NUM)
				->and(true, "hg.receive_member_no = ", $_GET["S_RECEIVE_MEMBER_NO"], FD_NUM)
				->and(true, "hg.gift_dt >= ",          $dtSt, FD_DATEEX)
				->and(true, "hg.gift_dt <= ",          $dtEd, FD_DATEEX)
				->and(true, "hg.gift_point >= ",       $_GET["S_GIFT_POINT_FROM"], FD_NUM)
				->and(true, "hg.gift_point <= ",       $_GET["S_GIFT_POINT_TO"], FD_NUM)
				// 2021/01 [ADD Start] VIPチェック、送信受取会員追加
				->and(true, "hg.agent_flg = ",         $_GET["S_AGENT"], FD_NUM)
				->and(true, "hg.receive_agent_flg = ", $_GET["S_RECEIVE_AGENT"], FD_NUM)
				->groupStart()
					->groupStart()
						->and(true, "hg.member_no = ", $_GET["S_EITHER_MEMBER_NO"], FD_NUM)
						->and(true, "hg.agent_flg = ", $_GET["S_EITHER_AGENT"], FD_NUM)
					->groupEnd()
					->groupStart("or")
						->and(true, "hg.receive_member_no = ", $_GET["S_EITHER_MEMBER_NO"], FD_NUM)
						->and(true, "hg.receive_agent_flg = ", $_GET["S_EITHER_AGENT"], FD_NUM)
					->groupEnd()
				->groupEnd()
				// 2021/01 [ADD End] VIPチェック追加
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$tsql = $sqls->resetField()
			->field("sum(gift_point) as total_gift_point, sum(commission_point) as total_commission_point, sum(receive_point) as total_receive_point")
		->createSql("\n");
	
	$rsql = $sqls->resetField()
			->field("hg.*")
			->field("mm.nickname, mmr.nickname as receive_nickname")
			->field("mm.black_flg, mm.tester_flg, mm.state")
			->field("mmr.black_flg as receive_black_flg, mmr.tester_flg as receive_tester_flg, mmr.state as receive_state")
			->from("inner join mst_member mm on hg.member_no = mm.member_no" )
			->from("inner join mst_member mmr on hg.receive_member_no = mmr.member_no" )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$totals = $template->DB->getRow( $tsql);
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_MEMBER_NO"         , $_GET["S_MEMBER_NO"], true);
	$template->assign("S_RECEIVE_MEMBER_NO" , $_GET["S_RECEIVE_MEMBER_NO"], true);
	$template->assign("S_GIFT_DT_FROM"      , $_GET["S_GIFT_DT_FROM"], true);
	$template->assign("S_GIFT_DT_TO"        , $_GET["S_GIFT_DT_TO"], true);
	$template->assign("S_GIFT_POINT_FROM"   , $_GET["S_GIFT_POINT_FROM"], true);
	$template->assign("S_GIFT_POINT_TO"     , $_GET["S_GIFT_POINT_TO"], true);
	// 2021/01 [ADD Start] VIPチェック、送信受取会員、CSV追加
	$template->assign("S_EITHER_MEMBER_NO"  , $_GET["S_EITHER_MEMBER_NO"], true);
	$template->assign("CHK_S_AGENT"         , (mb_strlen($_GET["S_AGENT"]) > 0) ? 'checked="checked"' : "");
	$template->assign("CHK_S_RECEIVE_AGENT" , (mb_strlen($_GET["S_RECEIVE_AGENT"]) > 0) ? 'checked="checked"' : "");
	$template->assign("CHK_S_EITHER_AGENT"  , (mb_strlen($_GET["S_EITHER_AGENT"]) > 0) ? 'checked="checked"' : "");
	$template->assign("GIFT_AGENT_DISPNAME" , GIFT_AGENT_DISPNAME, true);
	$template->if_enable("GIFT_AGENT"       , GIFT_AGENT);
	$template->if_enable("GIFTHISTORY_DOWNLOAD", GIFTHISTORY_DOWNLOAD);		// CSVダウンロード
	// 2021/01 [ADD End] VIPチェック、送信受取会員、CSV追加
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	// 合計値
	$template->assign("DISP_TOTAL_COMM_POINT"   , number_formatEx( $totals["total_commission_point"]), true);
	$template->assign("DISP_TOTAL_GIFT_POINT"   , number_formatEx( $totals["total_gift_point"]), true);
	$template->assign("DISP_TOTAL_RECEIVE_POINT", number_formatEx( $totals["total_receive_point"]), true);
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->assign("MEMBER_NO_PAD"        , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("MEMBER_NO"            , $row["member_no"], true);
		$template->assign("NICKNAME"             , $row["nickname"], true);
		$template->assign("RECEIVE_MEMBER_NO_PAD", $template->formatMemberNo($row["receive_member_no"]), true);
		$template->assign("RECEIVE_MEMBER_NO"    , $row["receive_member_no"], true);
		$template->assign("RECEIVE_NICKNAME"     , $row["receive_nickname"], true);
		$template->assign("GIFT_DT"              , format_datetime($row["gift_dt"]), true);
		$template->assign("DISP_GIFT_POINT"      , number_formatEx( $row["gift_point"]), true);
		$template->assign("DISP_RATE"            , number_formatEx( $row["commission_rate"]), true);
		$template->assign("DISP_COMMISSION_POINT", number_formatEx( $row["commission_point"]), true);
		$template->assign("DISP_RECEIVE_POINT"   , number_formatEx( $row["receive_point"]), true);
		$template->assign("DISP_BEARER"          , $GLOBALS["pointGiftBearerList"][$row["bearer"]], true);

		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_AGENT"   , $row["agent_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);
		$template->if_enable("IS_RECEIVE_BLACK"   , $row["receive_black_flg"] == 1);
		$template->if_enable("IS_RECEIVE_TESTER"  , $row["receive_black_flg"] == 0 && $row["receive_tester_flg"] == 1);
		$template->if_enable("IS_RECEIVE_AGENT"   , $row["receive_agent_flg"] == 1);
		$template->if_enable("IS_RECEIVE_RETIRED" , $row["receive_black_flg"] == 0 && $row["receive_tester_flg"] == 0 && $row["receive_state"] == 9);

		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
	
}

// 2021/01 [ADD End]
/**
 * CSVダウンロード処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcOutput($template) {
	// データ取得
	getData($_GET , array("P", "ODR", "S_MEMBER_NO","S_RECEIVE_MEMBER_NO", "S_GIFT_DT_FROM", "S_GIFT_DT_TO", "S_GIFT_POINT_FROM", "S_GIFT_POINT_TO"
							, "S_AGENT", "S_RECEIVE_AGENT", "S_EITHER_MEMBER_NO", "S_EITHER_AGENT"
						));

	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "hg.gift_dt desc";

	// 検索用日付
	$dtSt = ((mb_strlen($_GET["S_GIFT_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_GIFT_DT_FROM"]) : "");
	$dtEd = ((mb_strlen($_GET["S_GIFT_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_GIFT_DT_TO"]) : "");

	// 抽選ポイント単位
	$viewUnitDraw = $template->getArrayValue($GLOBALS["viewUnitList"]  , "3");

	// ヘッダ項目定義
	$csvHeader = array("送信会員", "送信会員状態", "送信日時", "送信" . $viewUnitDraw, "手数料負担者", "手数料率"
					, "手数料" . $viewUnitDraw, "受取会員", "受取会員状態", "受取" . $viewUnitDraw);
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $csvHeader) . '"');

	// 検索SQL生成
	$sqls = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("hg.member_no, mm.state, hg.gift_dt, hg.gift_point")
				->field("hg.bearer, hg.commission_rate, hg.commission_point")
				->field("hg.receive_member_no, mmr.state as receive_state, hg.receive_point")
				->field("mm.nickname, mmr.nickname as receive_nickname")
				->field("mm.black_flg, mm.tester_flg, hg.agent_flg")
				->field("mmr.black_flg as receive_black_flg, mmr.tester_flg as receive_tester_flg, hg.receive_agent_flg")
			->from("his_gift hg")
			->from("inner join mst_member mm on hg.member_no = mm.member_no" )
			->from("inner join mst_member mmr on hg.receive_member_no = mmr.member_no" )
			->where()
				->and(true, "hg.member_no = ",         $_GET["S_MEMBER_NO"], FD_NUM)
				->and(true, "hg.receive_member_no = ", $_GET["S_RECEIVE_MEMBER_NO"], FD_NUM)
				->and(true, "hg.gift_dt >= ",          $dtSt, FD_DATEEX)
				->and(true, "hg.gift_dt <= ",          $dtEd, FD_DATEEX)
				->and(true, "hg.gift_point >= ",       $_GET["S_GIFT_POINT_FROM"], FD_NUM)
				->and(true, "hg.gift_point <= ",       $_GET["S_GIFT_POINT_TO"], FD_NUM)
				->and(true, "hg.agent_flg = ",         $_GET["S_AGENT"], FD_NUM)
				->and(true, "hg.receive_agent_flg = ", $_GET["S_RECEIVE_AGENT"], FD_NUM)
				->groupStart()
					->groupStart()
						->and(true, "hg.member_no = ", $_GET["S_EITHER_MEMBER_NO"], FD_NUM)
						->and(true, "hg.agent_flg = ", $_GET["S_EITHER_AGENT"], FD_NUM)
					->groupEnd()
					->groupStart("or")
						->and(true, "hg.receive_member_no = ", $_GET["S_EITHER_MEMBER_NO"], FD_NUM)
						->and(true, "hg.receive_agent_flg = ", $_GET["S_EITHER_AGENT"], FD_NUM)
					->groupEnd()
				->groupEnd()
			->orderby($_GET["ODR"]);
	$sql = $sqls->createSql("\n");

	$outRs = $template->DB->query($sql);
	while ($row = $outRs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// 特殊項目のみ編集
		$row["gift_dt"] = format_datetime($row["gift_dt"], false, true);
		$row["member_no"] = "[" . $template->formatMemberNo($row["member_no"]) . "] " . $row["nickname"];
		$addState = array();
		if ($row["black_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["BlackMemberStatus"], $row["black_flg"]);
		if ($row["black_flg"] == "0" && $row["tester_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["TesterMember"], $row["tester_flg"]);
		if ($row["agent_flg"] == "1") $addState[] = GIFT_AGENT_DISPNAME;
		if (count($addState) > 0) {
			$row["member_no"] .= "（" . implode("、", $addState) . "）";
		}
		$row["state"] = $template->getArrayValue($GLOBALS["MemberStatus"], $row["state"]);
		$row["bearer"] = $template->getArrayValue($GLOBALS["pointGiftBearerList"], $row["bearer"]);
		$row["receive_member_no"] = "[" . $template->formatMemberNo($row["receive_member_no"]) . "] " . $row["receive_nickname"];
		$addState = array();
		if ($row["receive_black_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["BlackMemberStatus"], $row["receive_black_flg"]);
		if ($row["receive_black_flg"] == "0" && $row["receive_tester_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["TesterMember"], $row["receive_tester_flg"]);
		if ($row["receive_agent_flg"] == "1") $addState[] = GIFT_AGENT_DISPNAME;
		if (count($addState) > 0) {
			$row["receive_member_no"] .= "（" . implode("、", $addState) . "）";
		}
		$row["receive_state"] = $template->getArrayValue($GLOBALS["MemberStatus"], $row["receive_state"]);
		// 不要項目削除
		unset($row["nickname"]);
		unset($row["black_flg"]);
		unset($row["tester_flg"]);
		unset($row["agent_flg"]);
		unset($row["receive_nickname"]);
		unset($row["receive_black_flg"]);
		unset($row["receive_tester_flg"]);
		unset($row["receive_agent_flg"]);
		// 出力領域に設定
		$row = str_replace('"', '""', $row);
		array_push($outData, '"' . implode('","', $row) . '"');
	}
	unset($outRs);
	unset($row);

	// 出力文字列編集
	$ret = mb_convert_encoding(implode("\r\n", $outData), FILE_CSV_OUTPUT_ENCODE);
	if (CSV_OUTPUT_BOM_ENC == FILE_CSV_OUTPUT_ENCODE && CSV_OUTPUT_SET_BOM) $ret = pack('C*',0xEF,0xBB,0xBF) . $ret;	// BOMを付ける
	$currentDatetime = date("YmdHis");

	// CSV用出力設定
	header('Cache-Control: public');
	header('Pragma: public');	// キャッシュを制限しない設定にする
	header("Content-Disposition: attachment; filename=Gift_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}
// 2021/01 [ADD End]

?>
