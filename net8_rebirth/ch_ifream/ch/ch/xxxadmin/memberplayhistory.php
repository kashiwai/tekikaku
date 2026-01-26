<?php
/*
 * memberplayhistory.php
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
 * プレイ履歴画面表示
 * 
 * プレイ履歴画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.02
 * @since    2019/01/30 初版作成 片岡 充
 * @since    2022/09/26 v1.01    岡本 静子
 *           2023/04/18 v1.02    岡本 静子 精算行動種別追加
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
		// 管理系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理
		$mainWin = true;
		switch ($_GET["M"]) {
			case "output":		// CSVダウンロード
				ProcOutput($template);
				break;
			default:			// 一覧画面
				DispList($template);
		}
		
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
	getData($_GET , array("P", "ODR", "S_MEMBER_NO", "S_NICKNAME", "S_MACHINE_NO", "S_START_DT_FROM", "S_START_DT_TO"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "start_dt desc";
	
	//検索判定
	$_search = "show";
	if($_GET["S_MEMBER_NO"] == "" && $_GET["S_NICKNAME"] == "" && $_GET["S_MACHINE_NO"] == "" && $_GET["S_START_DT_FROM"] == "" && $_GET["S_START_DT_TO"] =="") {
		// 検索条件未指定は当日以降
		$_GET["S_START_DT_FROM"] = GetRefTimeToday();
	}
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	// 検索用日付
	$startDt = ((mb_strlen($_GET["S_START_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_START_DT_FROM"]) : "");
	$endDt = ((mb_strlen($_GET["S_START_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_START_DT_TO"]) : "");
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_play hp")
			->from("inner join dat_machine dm on dm.machine_no = hp.machine_no" )
			->from("inner join mst_model mo on mo.model_no = dm.model_no" )
			->from("left join mst_member mm on mm.member_no = hp.member_no" )
			->where()
				->and( true, "hp.member_no = "  , $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "mm.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
				->and( true, "hp.machine_no = " , $_GET["S_MACHINE_NO"], FD_NUM )
				//期間系
				->groupStart()
					->groupStart()
						->and(true, "hp.start_dt >= " , $startDt, FD_DATEEX )
						->and(true, "hp.start_dt <= " , $endDt, FD_DATEEX )
					->groupEnd()
					->groupStart("or")
						->and(true, "hp.end_dt >= " , $startDt, FD_DATEEX )
						->and(true, "hp.end_dt <= " , $endDt, FD_DATEEX )
					->groupEnd()
				->groupEnd()
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("hp.machine_no, hp.point, hp.credit, hp.draw_point, hp.in_point, hp.in_credit, hp.out_credit, hp.out_draw_point")	//プレイデータ
			->field("hp.lost_point")		//--- 2022/09/26 Add by S.Okamoto 失効期限付ポイント追加
			->field("hp.play_count, hp.bb_count, hp.rb_count")
			->field("hp.out_action_type")	//--- 2023/04/18 Add by S.Okamoto 精算行動種別追加
			->field("hp.start_dt, hp.end_dt")																							//プレイデータ時刻
			->field("mo.model_no, mo.model_name, mo.del_flg as del_model, dm.machine_corner, dm.del_flg as del_machine")				//その他
			->field("hp.member_no, mm.nickname, mm.state, mm.tester_flg, mm.black_flg")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("MEMBERPLAYHISTORY_DOWNLOAD", MEMBERPLAYHISTORY_DOWNLOAD);		// CSVダウンロード
	
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_MEMBER_NO"         , $_GET["S_MEMBER_NO"], true);
	$template->assign("S_NICKNAME"          , $_GET["S_NICKNAME"], true);
	$template->assign("SEL_MACHINE_NO"      , makeOptionArray( $template->DB->getMachines(), $_GET["S_MACHINE_NO"]));
	$template->assign("S_START_DT_FROM"     , $_GET["S_START_DT_FROM"], true);
	$template->assign("S_START_DT_TO"       , $_GET["S_START_DT_TO"], true);
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));	// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));	// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));	// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);
		
		
		$template->assign("START_DT"           , format_datetime($row["start_dt"], false, true), true);
		$template->assign("END_DT"             , format_datetime($row["end_dt"], false, true), true);
		$template->assign("MACHINE_NO"         , $row["machine_no"], true);
		$template->assign("MACHINE_NO_PAD"     , $template->formatNoBasic($row["machine_no"]), true);
		$template->assign("MODEL_NO"           , $row["model_no"], true);
		$template->assign("MODEL_NAME"         , $row["model_name"], true);
		$template->assign("MEMBER_NO"          , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD"      , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"           , $row["nickname"], true);
		$template->assign("IN_CREDIT"          , number_formatEx( $row["in_credit"]), true);			// クレジット消費
		$template->assign("OUT_CREDIT"         , number_formatEx( $row["out_credit"]), true);			// クレジット払出
		$template->assign("IN_POINT"           , number_formatEx( $row["in_point"]), true);				// Bit消費
		$template->assign("OUT_DRAW_POINT"     , number_formatEx( $row["out_draw_point"]), true);		// Coin払出
		$template->assign("LOST_POINT"         , number_formatEx( $row["lost_point"]), true);			//--- 2022/09/26 Add by S.Okamoto 失効期限付ポイント追加
		$template->assign("PLAY_COUNT"         , number_formatEx( $row["play_count"]), true);
		$template->assign("BB_COUNT"           , number_formatEx( $row["bb_count"]), true);
		$template->assign("RB_COUNT"           , number_formatEx( $row["rb_count"]), true);
		//--- 2023/04/18 Add by S.Okamoto 精算行動種別追加
		$template->assign("OUT_ACTION_TYPE"    , $template->getArrayValue($GLOBALS["outActionType"], $row["out_action_type"]), true);
		// 差分
		$template->assign("CREDIT_DIFFERENCE"  , number_formatEx( $row["out_credit"] - $row["in_credit"]), true);

		$template->if_enable("VALID_MACHINE"   , $row["del_machine"] == "0");
		$template->if_enable("VALID_MODEL"     , $row["del_model"] == "0");

		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
}

/**
 * CSVダウンロード処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcOutput($template) {
	// データ取得
	getData($_GET , array("ODR", "S_MEMBER_NO", "S_NICKNAME", "S_MACHINE_NO", "S_START_DT_FROM", "S_START_DT_TO"));
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "start_dt desc";
	// 検索用日付
	$startDt = ((mb_strlen($_GET["S_START_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_START_DT_FROM"]) : "");
	$endDt = ((mb_strlen($_GET["S_START_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_START_DT_TO"]) : "");

	$viewUnitPlay = $template->getArrayValue($GLOBALS["viewUnitList"]  , "1");	// プレイポイント単位
	$viewUnitCredit = $template->getArrayValue($GLOBALS["viewUnitList"], "2");	// クレジット単位
	$viewUnitDraw = $template->getArrayValue($GLOBALS["viewUnitList"]  , "3");	// 抽選ポイント単位

	// ヘッダ項目定義
	$csvHeader = array("開始日時", "終了日時", "実機No", "機種名"
					, "会員No", "ニックネーム", "会員状態",  $viewUnitPlay . "消費"
					, $viewUnitCredit . "消費", $viewUnitCredit . "払出", $viewUnitCredit . "差分"
					, $viewUnitDraw . "払出", "ゲーム数", "BB", "RB"
					, "精算種別"				//--- 2023/04/18 Add by S.Okamoto 精算行動種別追加
				);
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $csvHeader) . '"');

	// 検索SQL生成
	$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("hp.start_dt, hp.end_dt, hp.machine_no, mo.model_name")
				->field("hp.member_no, mm.nickname, mm.state, mm.tester_flg, mm.black_flg")
				->field("hp.in_point, hp.in_credit, hp.out_credit, (hp.out_credit - hp.in_credit) as difference_credit")
				->field("hp.out_draw_point, hp.play_count, hp.bb_count, hp.rb_count")
				->field("hp.out_action_type")	//--- 2023/04/18 Add by S.Okamoto 精算行動種別追加
			->from("his_play hp")
			->from("inner join dat_machine dm on dm.machine_no = hp.machine_no" )
			->from("inner join mst_model mo on mo.model_no = dm.model_no" )
			->from("left join mst_member mm on mm.member_no = hp.member_no" )
			->where()
				->and(true, "hp.member_no = "  , $_GET["S_MEMBER_NO"], FD_NUM )
				->and(true, "mm.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
				->and(true, "hp.machine_no = " , $_GET["S_MACHINE_NO"], FD_NUM )
				//期間系
				->groupStart()
					->groupStart()
						->and(true, "hp.start_dt >= " , $startDt, FD_DATEEX )
						->and(true, "hp.start_dt <= " , $endDt, FD_DATEEX )
					->groupEnd()
					->groupStart("or")
						->and(true, "hp.end_dt >= " , $startDt, FD_DATEEX )
						->and(true, "hp.end_dt <= " , $endDt, FD_DATEEX )
					->groupEnd()
				->groupEnd()
			->orderby($_GET["ODR"] )
		->createSql("\n");
	$outRs = $template->DB->query($sql);
	while ($row = $outRs->fetch(PDO::FETCH_ASSOC)) {
		// 特殊項目のみ編集
		$row["start_dt"] = format_datetime($row["start_dt"], false, true);
		$row["end_dt"] = format_datetime($row["end_dt"], false, true);
		$row["machine_no"] = $template->formatNoBasic($row["machine_no"]);
		$row["member_no"] = $template->formatMemberNo($row["member_no"]);
		$row["state"] = $template->getArrayValue($GLOBALS["MemberStatus"], $row["state"]);
		//--- 2023/04/18 Add by S.Okamoto 精算行動種別追加
		$row["out_action_type"] = $template->getArrayValue($GLOBALS["outActionType"], $row["out_action_type"]);
		$addState = array();
		if ($row["black_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["BlackMemberStatus"], $row["black_flg"]);
		if ($row["black_flg"] == "0" && $row["tester_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["TesterMember"], $row["tester_flg"]);
		if (count($addState) > 0) {
			$row["nickname"] .= "（" . implode("、", $addState) . "）";
		}
		// 不要項目削除
		unset($row["tester_flg"]);
		unset($row["black_flg"]);
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
	header("Content-Disposition: attachment; filename=MemberPlay_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}

?>
