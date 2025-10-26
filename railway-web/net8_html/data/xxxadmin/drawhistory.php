<?php
/*
 * drawhistory.php
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
 * 抽選ポイント履歴画面表示
 * 
 * 抽選ポイント履歴画面の表示を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2020/07/30 初版作成 岡本 静子
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
	getData($_GET , array("P", "ODR", "NO"
						, "S_MEMBER_NO", "S_PROC_CD", "S_PROC_DT_FROM", "S_PROC_DT_TO"
						));

	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "his.proc_dt desc";

	// 条件未指定は当日
	$_search = "show";
	if($_GET["S_MEMBER_NO"] == "" && $_GET["S_PROC_CD"] == "" && $_GET["S_PROC_DT_FROM"] == "" && $_GET["S_PROC_DT_TO"] == "") {
		$today = GetRefTimeToday();
		$_GET["S_PROC_DT_FROM"] = $today;
		$_GET["S_PROC_DT_TO"] = $today;
	}

	//ページングクエリ作成
	$_que = HtmlPagingQueryString($_GET, array("P"));

	// 検索用日付
	$procDtSt = ((mb_strlen($_GET["S_PROC_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_PROC_DT_FROM"]) : "");
	$procDtEd = ((mb_strlen($_GET["S_PROC_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_PROC_DT_TO"]) : "");

	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_drawPoint his")
			->join( "inner", "mst_member mem", "his.member_no = mem.member_no")
			->where()
				->and(true, "his.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM)
				->and(true, "his.proc_cd = "  , $_GET["S_PROC_CD"], FD_STR)
				->and(true, "his.proc_dt >= " , $procDtSt, FD_DATE)
				->and(true, "his.proc_dt <= " , $procDtEd, FD_DATE)
		->createSql("\n");

	// カウント取得
	$allrows = $template->DB->getOne($csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;

	// データ取得
	$rsql = $sqls
			->resetField()
			->field("his.proc_dt, his.member_no, his.proc_cd")
			->field("his.key_no, his.after_draw_point")
			->field("IF(his.type = " .  $template->DB->conv_sql(2, FD_NUM) . ", his.draw_point, 0) as sub_point")
			->field("IF(his.type = " .  $template->DB->conv_sql(1, FD_NUM) . ", his.draw_point, 0) as add_point")
			->field("mem.nickname, mem.black_flg, mem.state, mem.tester_flg")
			->page($_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby($_GET["ODR"] )
		->createSql("\n");
	$rs = $template->DB->query($rsql);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("POINTHISTORY_DOWNLOAD", POINTHISTORY_DOWNLOAD);		// CSVダウンロード

	// 検索
	$template->assign("S_OPEN"        , $_search, true);
	$template->assign("S_MEMBER_NO"   , $_GET["S_MEMBER_NO"], true);
	$template->assign("SEL_PROC_CD"   , makeOptionArray($GLOBALS["drawPointHistoryProcessCode"], $_GET["S_PROC_CD"], true));
	$template->assign("S_PROC_DT_FROM", $_GET["S_PROC_DT_FROM"], true);
	$template->assign("S_PROC_DT_TO"  , $_GET["S_PROC_DT_TO"], true);

	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));			// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));			// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));			// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$isPlay = ($row["proc_cd"] == "11" && mb_strlen($row["key_no"]) > 0);
		$baseProcDt = GetRefTimeToday($row["proc_dt"], "Y/m/d");

		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);
		$template->assign("PROC_DT"      , format_datetime($row["proc_dt"]), true);
		$template->assign("MEMBER_NO"    , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD", $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"     , $row["nickname"], true);
		$template->assign("PROC_NAME"    , $template->getArrayValue($GLOBALS["drawPointHistoryProcessCode"], $row["proc_cd"]), true);
		$template->assign("MACHINE_NO"   , $row["key_no"], true);
		$template->assign("BASE_PROC_DT" , urlencode($baseProcDt), true);
		$template->assign("SUB_POINT"    , number_formatEx($row["sub_point"]), true);
		$template->assign("ADD_POINT"    , number_formatEx($row["add_point"]), true);
		$template->assign("AFTER_POINT"  , number_formatEx($row["after_draw_point"]), true);

		$template->if_enable("IS_PLAY"  , $isPlay);
		$template->if_enable("NONE_PLAY", !$isPlay);

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
	getData($_GET , array("P", "ODR", "NO"
						, "S_MEMBER_NO", "S_PROC_CD", "S_PROC_DT_FROM", "S_PROC_DT_TO"
						));
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "his.proc_dt desc";

	// 検索用日付
	$procDtSt = ((mb_strlen($_GET["S_PROC_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_PROC_DT_FROM"]) : "");
	$procDtEd = ((mb_strlen($_GET["S_PROC_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_PROC_DT_TO"]) : "");

	// ヘッダ項目定義
	$csvHeader = array("処理日時", "会員No", "ニックネーム", "会員状態"
					, "処理種別", "消費", "増加", "残高");
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $csvHeader) . '"');

	// 検索SQL生成
	$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("his.proc_dt, his.member_no, mem.nickname, mem.state, mem.black_flg, mem.tester_flg")
				->field("his.proc_cd")
				->field("IF(his.type = " .  $template->DB->conv_sql(2, FD_NUM) . ", his.draw_point, 0) as sub_point")
				->field("IF(his.type = " .  $template->DB->conv_sql(1, FD_NUM) . ", his.draw_point, 0) as add_point")
				->field("his.after_draw_point")
			->from("his_drawPoint his")
			->join( "inner", "mst_member mem", "his.member_no = mem.member_no")
			->where()
				->and(true, "his.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM)
				->and(true, "his.proc_cd = "  , $_GET["S_PROC_CD"], FD_STR)
				->and(true, "his.proc_dt >= " , $procDtSt, FD_DATE)
				->and(true, "his.proc_dt <= " , $procDtEd, FD_DATE)
			->orderby($_GET["ODR"] )
		->createSql("\n");
	$outRs = $template->DB->query($sql);
	while ($row = $outRs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// 特殊項目のみ編集
		$row["proc_dt"] = format_datetime($row["proc_dt"], false, true);
		$row["member_no"] = $template->formatMemberNo($row["member_no"]);
		$addState = array();
		if ($row["black_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["BlackMemberStatus"], $row["black_flg"]);
		if ($row["black_flg"] == "0" && $row["tester_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["TesterMember"], $row["tester_flg"]);
		if (count($addState) > 0) {
			$row["nickname"] .= "（" . implode("、", $addState) . "）";
		}
		$row["state"] = $template->getArrayValue($GLOBALS["MemberStatus"], $row["state"]);
		$row["proc_cd"] = $template->getArrayValue($GLOBALS["drawPointHistoryProcessCode"], $row["proc_cd"]);
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
	header("Content-Disposition: attachment; filename=DrawPoint_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}

?>
