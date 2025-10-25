<?php
/*
 * purchasehistory.php
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
 * 購入履歴情報画面表示
 * 
 * 購入履歴情報画面の表示を行う
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
	getData($_GET , array("P", "ODR",
							"S_MEMBER_NO","S_NICKNAME", "S_RECEPT_DT_FROM", "S_RECEPT_DT_TO", "S_PURCHASE_TYPE", "S_STATUS", 
							"S_AMOUNT_FROM", "S_AMOUNT_TO", "S_POINT_FROM", "S_POINT_TO", "S_PURCHASE_DT_FROM", "S_PURCHASE_DT_TO"));
	//
	$get_purchase_type = array();
	if (empty($_GET["CHK_PURCHASE_TYPE"])) $_GET["CHK_PURCHASE_TYPE"] = array();
	if (isset($_GET["CHK_PURCHASE_TYPE"]) && count($_GET["CHK_PURCHASE_TYPE"]) > 0) {
		// 値のある配列のみ抽出(keyのみ存在して値が空の配列に対応する為)
		$get_purchase_type = array_filter($_GET["CHK_PURCHASE_TYPE"], "strlen");
		$out = array();
		foreach($get_purchase_type as $v){
			$out[] = "CHK_PURCHASE_TYPE%5B%5D=".$v;
		}
		$_GET["CHK_PURCHASE_TYPE"] = implode('&', $out);
	}
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "recept_dt desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P", "CHK_PURCHASE_TYPE"), $_GET["CHK_PURCHASE_TYPE"]);
	
	// 検索判定
	if( ($_GET["S_MEMBER_NO"]!="") || ($_GET["S_NICKNAME"]!="") || ($_GET["S_RECEPT_DT_FROM"]!="") || ($_GET["S_RECEPT_DT_TO"]!="") || ($_GET["S_PURCHASE_TYPE"]!="") || ($_GET["S_STATUS"]!="") || 
	($_GET["S_AMOUNT_FROM"]!="") || ($_GET["S_AMOUNT_TO"]!="") || ($_GET["S_POINT_FROM"]!="") || ($_GET["S_POINT_TO"]!="") || ($_GET["S_PURCHASE_DT_FROM"]!="") || ($_GET["S_PURCHASE_DT_TO"]!=""))
	{
		$_search = "show";
	}else{
		$_search = "";
	}
	if( count($get_purchase_type)>0) $_search = "show";
	
	// 検索用日付
	$receptSt = ((mb_strlen($_GET["S_RECEPT_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_RECEPT_DT_FROM"]) : "");
	$receptEd = ((mb_strlen($_GET["S_RECEPT_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_RECEPT_DT_TO"]) : "");
	$purchaseSt = ((mb_strlen($_GET["S_PURCHASE_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_PURCHASE_DT_FROM"]) : "");
	$purchaseEd = ((mb_strlen($_GET["S_PURCHASE_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_PURCHASE_DT_TO"]) : "");
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("his_purchase hp")
			->from("inner join mst_member mm on mm.member_no = hp.member_no")
			->where()
				->and( true, "mm.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM )
				->and( true, "mm.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR )
				->and( true, "hp.recept_dt >= ", $receptSt, FD_DATEEX )
				->and( true, "hp.recept_dt <= ", $receptEd, FD_DATEEX )
				->and( true, "hp.amount >= ", $_GET["S_AMOUNT_FROM"], FD_NUM )
				->and( true, "hp.amount <= ", $_GET["S_AMOUNT_TO"], FD_NUM )
				->and( true, "hp.point >= ", $_GET["S_POINT_FROM"], FD_NUM )
				->and( true, "hp.point <= ", $_GET["S_POINT_TO"], FD_NUM )
				->and( true, "hp.purchase_dt >= ", $purchaseSt, FD_DATEEX )
				->and( true, "hp.purchase_dt <= ", $purchaseEd, FD_DATEEX )
				->and( true, "hp.result_status = ", $_GET["S_STATUS"], FD_NUM )
		->createSql();
	
	if( count($get_purchase_type)>0){
		$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
					->and( true, "hp.purchase_type in ", $get_purchase_type, FD_NUM )
			->createSql();
	}
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("hp.purchase_no, hp.member_no, hp.recept_dt, hp.purchase_type, hp.amount, hp.point, hp.result_status, hp.result_message, hp.purchase_dt")
			->field("mm.mail, mm.last_name, mm.first_name, mm.nickname, mm.state, mm.tester_flg, mm.black_flg")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("PURCHASEHISTORY_DOWNLOAD", PURCHASEHISTORY_DOWNLOAD);		// CSVダウンロード
	
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_MEMBER_NO"         , $_GET["S_MEMBER_NO"], true);
	$template->assign("S_NICKNAME"          , $_GET["S_NICKNAME"], true);
	$template->assign("S_RECEPT_DT_FROM"    , $_GET["S_RECEPT_DT_FROM"], true);
	$template->assign("S_RECEPT_DT_TO"      , $_GET["S_RECEPT_DT_TO"], true);
	$template->assign("CHK_PURCHASE_TYPE"   , makeCheckBoxArray( $GLOBALS["viewPurchaseType"], "CHK_PURCHASE_TYPE[]", $get_purchase_type, 0, "", " ", "", true));
	$template->assign("S_AMOUNT_FROM"       , $_GET["S_AMOUNT_FROM"], true);
	$template->assign("S_AMOUNT_TO"         , $_GET["S_AMOUNT_TO"], true);
	$template->assign("S_POINT_FROM"        , $_GET["S_POINT_FROM"], true);
	$template->assign("S_POINT_TO"          , $_GET["S_POINT_TO"], true);
	$template->assign("S_PURCHASE_DT_FROM"  , $_GET["S_PURCHASE_DT_FROM"], true);
	$template->assign("S_PURCHASE_DT_TO"    , $_GET["S_PURCHASE_DT_TO"], true);
	$template->assign("SEL_STATUS"          , makeOptionArray( $GLOBALS["purchaseResultStatus"], $_GET["S_STATUS"]));
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));	// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));	// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));	// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);

		$template->assign("MEMBER_NO_PAD"        , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("MEMBER_NO"            , $row["member_no"], true);
		$template->assign("NICKNAME"             , $row["nickname"], true);
		$template->assign("RECEPT_DT"            , format_datetime($row["recept_dt"]), true);
		$template->assign("DISP_PURCHASE_TYPE"   , $GLOBALS["viewPurchaseType"][ $row["purchase_type"]], true);
		$template->assign("DISP_AMOUNT"          , number_formatEx( $row["amount"]), true);
		$template->assign("DISP_AMOUNT_CURRENCY" , $GLOBALS["viewAmountType"][ $row["purchase_type"]], true);
		$template->assign("DISP_POINT"           , number_formatEx( $row["point"]), true);
		$template->assign("DISP_RESULT_STATUS"   , $GLOBALS["purchaseResultStatus"][ $row["result_status"]], true);
		$template->assign("RESULT_MESSAGE"       , $row["result_message"], true);
		$template->assign("PURCHASE_DT"          , format_datetime($row["purchase_dt"]), true);
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
	getData($_GET , array("P", "ODR",
							"S_MEMBER_NO","S_NICKNAME", "S_RECEPT_DT_FROM", "S_RECEPT_DT_TO", "S_PURCHASE_TYPE", "S_STATUS", 
							"S_AMOUNT_FROM", "S_AMOUNT_TO", "S_POINT_FROM", "S_POINT_TO", "S_PURCHASE_DT_FROM", "S_PURCHASE_DT_TO"));
	$get_purchase_type = array();
	if (empty($_GET["CHK_PURCHASE_TYPE"])) $_GET["CHK_PURCHASE_TYPE"] = array();
	if (isset($_GET["CHK_PURCHASE_TYPE"]) && count($_GET["CHK_PURCHASE_TYPE"]) > 0) $get_purchase_type = array_filter($_GET["CHK_PURCHASE_TYPE"], "strlen");

	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "recept_dt desc";

	// 検索用日付
	$receptSt = ((mb_strlen($_GET["S_RECEPT_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_RECEPT_DT_FROM"]) : "");
	$receptEd = ((mb_strlen($_GET["S_RECEPT_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_RECEPT_DT_TO"]) : "");
	$purchaseSt = ((mb_strlen($_GET["S_PURCHASE_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["S_PURCHASE_DT_FROM"]) : "");
	$purchaseEd = ((mb_strlen($_GET["S_PURCHASE_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["S_PURCHASE_DT_TO"]) : "");

	// プレイポイント単位
	$viewUnitPlay = $template->getArrayValue($GLOBALS["viewUnitList"]  , "1");

	// ヘッダ項目定義
	$csvHeader = array("購入受付日時", "購入日時", "会員No", "ニックネーム", "会員状態"
					, "購入方法", "購入金額", "購入" . $viewUnitPlay, "ステータス");
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $csvHeader) . '"');

	// 検索SQL生成
	$sqls = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("hp.recept_dt, hp.purchase_dt, hp.member_no, mm.nickname, mm.state, mm.tester_flg, mm.black_flg")
				->field("hp.purchase_type, hp.amount, hp.point, hp.result_status")
			->from("his_purchase hp")
			->from("inner join mst_member mm on mm.member_no = hp.member_no")
			->where()
				->and(true, "mm.member_no = ", $_GET["S_MEMBER_NO"], FD_NUM)
				->and(true, "mm.nickname like ", ["%",$_GET["S_NICKNAME"],"%"], FD_STR)
				->and(true, "hp.recept_dt >= ", $receptSt, FD_DATEEX)
				->and(true, "hp.recept_dt <= ", $receptEd, FD_DATEEX)
				->and(true, "hp.amount >= ", $_GET["S_AMOUNT_FROM"], FD_NUM)
				->and(true, "hp.amount <= ", $_GET["S_AMOUNT_TO"], FD_NUM)
				->and(true, "hp.point >= ", $_GET["S_POINT_FROM"], FD_NUM)
				->and(true, "hp.point <= ", $_GET["S_POINT_TO"], FD_NUM)
				->and(true, "hp.purchase_dt >= ", $purchaseSt, FD_DATEEX)
				->and(true, "hp.purchase_dt <= ", $purchaseEd, FD_DATEEX)
				->and(true, "hp.result_status = ", $_GET["S_STATUS"], FD_NUM)
			->orderby($_GET["ODR"]);

	if( count($get_purchase_type)>0){
		$sqls->where()
			->and( true, "hp.purchase_type in ", $get_purchase_type, FD_NUM);
	}
	$sql = $sqls->createSql("\n");

	$outRs = $template->DB->query($sql);
	while ($row = $outRs->fetch(MDB2_FETCHMODE_ASSOC)) {
		// 特殊項目のみ編集
		$row["recept_dt"] = format_datetime($row["recept_dt"], false, true);
		$row["purchase_dt"] = format_datetime($row["purchase_dt"], false, true);
		$row["member_no"] = $template->formatMemberNo($row["member_no"]);
		$addState = array();
		if ($row["black_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["BlackMemberStatus"], $row["black_flg"]);
		if ($row["black_flg"] == "0" && $row["tester_flg"] == "1") $addState[] = $template->getArrayValue($GLOBALS["TesterMember"], $row["tester_flg"]);
		if (count($addState) > 0) {
			$row["nickname"] .= "（" . implode("、", $addState) . "）";
		}
		$row["state"] = $template->getArrayValue($GLOBALS["MemberStatus"], $row["state"]);
		$row["purchase_type"] = $template->getArrayValue($GLOBALS["viewPurchaseType"], $row["purchase_type"]);
		$row["result_status"] = $template->getArrayValue($GLOBALS["purchaseResultStatus"], $row["result_status"]);

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
	header("Content-Disposition: attachment; filename=Purchase_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}

?>
