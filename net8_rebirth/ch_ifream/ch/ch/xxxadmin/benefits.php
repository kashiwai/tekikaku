<?php
/*
 * benefits.php
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
 * 特典管理画面表示
 * 
 * 特典管理画面の表示を行う
 * 
 * @package
 * @author   岡本静子
 * @version  1.0
 * @since    2020/09/15 初版作成 岡本静子
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
			case "detail":			// 詳細画面
				$mainWin = false;
				DispDetail($template);
				break;
				
			case "regist":			// 登録処理
				$mainWin = false;
				RegistData($template);
				break;
				
			case "end":				// 完了画面
				$mainWin = false;
				DispComplete($template);
				break;
				
			case "use":				// 使用一覧
				$mainWin = false;
				DispUseList($template);
				break;
				
			case "output":		// CSVダウンロード
				ProcOutput($template);
				break;
				
			default:				// 一覧画面
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
	getData($_GET , array("P", "ODR"));
	getData($_GET , array("S_BENEFITS_NO", "S_POINT_FROM", "S_POINT_TO", "S_END_DT_FROM", "S_END_DT_TO"
						));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "hed.benefits_no desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString($_GET, array("P"));
	
	//検索判定
	if (checkKeys($_GET, array("S_BENEFITS_NO", "S_POINT_FROM", "S_POINT_TO", "S_END_DT_FROM", "S_END_DT_TO"))) {
		$_search = "show";	//表示用クラス名
	}else{
		$_search = "";
	}
	
	// 検索用日付
	$endSt = ((mb_strlen($_GET["S_END_DT_FROM"]) > 0) ? $_GET["S_END_DT_FROM"] . " 00:00:00" : "");
	$endEd = ((mb_strlen($_GET["S_END_DT_TO"]) > 0) ? $_GET["S_END_DT_TO"] . " 23:59:59" : "");
	
	// DB
	$sqls = new SqlString();
	$csql = $sqls->setAutoConvert([$template->DB,"conv_sql"])
			->select()
			->field("count(*)")
			->from("dat_benefits hed")
			->where()
				->and(SQL_CUT, "hed.benefits_no = ", $_GET["S_BENEFITS_NO"], FD_NUM)
				->and(SQL_CUT, "hed.point >= "     , $_GET["S_POINT_FROM"], FD_DATE)
				->and(SQL_CUT, "hed.point <= "     , $_GET["S_POINT_TO"], FD_DATE)
				->and(SQL_CUT, "hed.end_dt >= "    , $endSt, FD_DATE)
				->and(SQL_CUT, "hed.end_dt <= "    , $endEd, FD_DATE)
				->and("hed.del_flg = "             , 0, FD_NUM )
		->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne($csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("hed.benefits_no, hed.end_dt, hed.issued")
			->field("hed.point, hed.limit_days, hed.stop_dt")
			->field("(select count(*) from dat_benefitsDetail dtl where dtl.benefits_no = hed.benefits_no and dtl.member_no IS NOT NULL) as ucnt")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 検索
	$template->assign("S_OPEN"       , $_search, true);
	$template->assign("S_BENEFITS_NO", $_GET["S_BENEFITS_NO"], true);
	$template->assign("S_POINT_FROM" , $_GET["S_POINT_FROM"], true);
	$template->assign("S_POINT_TO"   , $_GET["S_POINT_TO"], true);
	$template->assign("S_END_DT_FROM", $_GET["S_END_DT_FROM"], true);
	$template->assign("S_END_DT_TO"  , $_GET["S_END_DT_TO"], true);
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));	// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));	// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));	// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {

		$template->assign("BENEFITS_NO_PAD", $template->formatNoBasic($row["benefits_no"]), true);
		$template->assign("BENEFITS_NO"    , $row["benefits_no"], true);
		$template->assign("POINT"          , number_formatEx($row["point"]), true);
		$template->assign("LIMIT_DAYS"     , number_formatEx($row["limit_days"]), true);
		$template->assign("END_DT"         , format_datetime($row["end_dt"]), true);
		$template->assign("ISSUED"         , number_formatEx($row["issued"]), true);
		$template->assign("USE_CNT"        , number_formatEx($row["ucnt"]), true);
		$template->assign("STOP_DT"        , format_datetime($row["stop_dt"]), true);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
}

/**
 * 詳細画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispDetail($template, $message = "") {
	// データ取得
	getData($_GET , array("NO"));
	getData($_POST, array("POINT", "LIMIT_DAYS", "END_DT", "END_TIME_HR", "END_TIME_MIN", "ISSUED"
						, "USE_COUNT"
						));
	
	if(mb_strlen($_GET["NO"]) > 0 && mb_strlen($message) <= 0){
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("hed.benefits_no, hed.end_dt, hed.issued")
				->field("hed.point, hed.limit_days, hed.stop_dt")
				->field("(select count(*) from dat_benefitsDetail dtl where dtl.benefits_no = hed.benefits_no and dtl.member_no IS NOT NULL) as ucnt")
			->from("dat_benefits hed")
			->where()
				->and("hed.benefits_no = ", $_GET["NO"], FD_NUM)
				->and("hed.del_flg = "    , 0, FD_NUM )
			->createSql("\n");
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		$_POST["POINT"]        = $row["point"];
		$_POST["LIMIT_DAYS"]   = $row["limit_days"];
		$_POST["END_DT"]       = format_date($row["end_dt"]);
		$_POST["END_TIME_HR"]  = get_time($row["end_dt"], 0);
		$_POST["END_TIME_MIN"] = get_time($row["end_dt"], 1);
		$_POST["ISSUED"]       = $row["issued"];
		$_POST["USE_COUNT"]    = $row["ucnt"];
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);

	$template->assign("NO"              , $_GET["NO"], true);
	$template->assign("POINT"           , $_POST["POINT"], true);
	$template->assign("LIMIT_DAYS"      , $_POST["LIMIT_DAYS"], true);
	$template->assign("END_DT"          , $_POST["END_DT"], true);
	$template->assign("SEL_END_TIME_HR" , makeSelectHourTag($_POST["END_TIME_HR"]));
	$template->assign("SEL_END_TIME_MIN", makeSelectMinuteTag($_POST["END_TIME_MIN"], MINUTE_SPAN));
	$template->assign("ISSUED"          , $_POST["ISSUED"], true);
	$template->assign("USE_COUNT"       , $_POST["USE_COUNT"], true);
	$template->assign("DSP_USE_COUNT"   , number_formatEx($_POST["USE_COUNT"]), true);
	
	$now = new DateTime();
	$template->assign("TODAY"  , $now->format("Y/m/d"), true);
	$template->assign("HOURS"  , $now->format("H"), true);
	$template->assign("MINUTES", $now->format("i"), true);


	// 表示制御
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$isUpd = (mb_strlen($_GET["NO"]) > 0);
	$template->if_enable("NEW" , !$isUpd);
	$template->if_enable("UPD" , $isUpd);
	$template->if_enable("DEL" , $isUpd && $row["ucnt"] <= 0);
	$template->if_enable("STOP", $isUpd && $row["ucnt"] > 0 && $row["ucnt"] < $_POST["ISSUED"] && (int)$now->format("YmdHi") < (int)(str_replace("/", "", $_POST["END_DT"]) . $_POST["END_TIME_HR"] . $_POST["END_TIME_MIN"]));
	
	// 表示
	$template->flush();
}

/**
 * 登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData($template) {
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	// データ取得
	getData($_GET  , array("ACT", "NO"));
	getData($_POST, array("POINT", "LIMIT_DAYS", "END_DT", "END_TIME_HR", "END_TIME_MIN", "ISSUED"
						, "USE_COUNT"
						));
	
	// 入力チェック
	if ($_GET["ACT"] != "stop") {
		$message = checkInput($template);
		if (mb_strlen($message) > 0) {
			DispDetail($template, $message);
			return;
		}
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("dat_benefits")
				->set()
					->value("del_flg", 1, FD_NUM)
					->value("del_no" , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value("del_dt" , "current_timestamp", FD_FUNCTION)
				->where()
					->and(false, "benefits_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
	} elseif ($_GET["ACT"] == "stop") {
		// 利用停止
		$mode = "stop";
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update("dat_benefits")
				->set()
					->value("stop_flg", 1, FD_NUM)
					->value("stop_no" , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value("stop_dt" , "current_timestamp", FD_FUNCTION)
				->where()
					->and(false, "benefits_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
	} else {
		// 新規
		$endDt = $_POST["END_DT"] . " " . $_POST["END_TIME_HR"] . ":" . $_POST["END_TIME_MIN"] . ":59";
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->insert()
				->into("dat_benefits")
					->value("end_dt"    , $endDt,  FD_DATE)
					->value("issued"    , $_POST["ISSUED"],  FD_NUM)
					->value("point"     , $_POST["POINT"],  FD_NUM)
					->value("limit_days", $_POST["LIMIT_DAYS"],  FD_NUM)
					->value( "upd_no"   , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "upd_dt"   , "current_timestamp", FD_FUNCTION)
					->value( "add_no"   , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "add_dt"   , "current_timestamp", FD_FUNCTION)
			->createSQL();
		$template->DB->exec($sql);
		// 特典コード登録
		$sql = "select last_insert_id()";
		$benefitsNo = $template->DB->getOne($sql);
		$issued = (int)$_POST["ISSUED"];
		for ($i = 0; $i < $issued; $i++) {
			$cnt = 0;
			do {
				// 特典コード発行
				$benefitsCd = substr(str_shuffle(BENEFITS_CODE_STR), 0, BENEFITS_CODE_LENGTH);
				// 存在チェック
				$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
					->select()
						->field("count(*)")
					->from("dat_benefits hed")
						->join( "inner", "dat_benefitsDetail dtl", "hed.benefits_no = dtl.benefits_no")
					->where()
						->and("hed.del_flg = "    , 0, FD_NUM )
						->and("dtl.benefits_cd = ", $benefitsCd, FD_STR)
					->createSql();
				$cnt = (int)$template->DB->getOne($sql);
			} while ($cnt > 0);
			// 登録
			$insSql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into("dat_benefitsDetail")
						->value("benefits_no", $benefitsNo, FD_NUM)
						->value("benefits_cd", $benefitsCd, FD_STR)
				->createSQL();
			$template->DB->exec($insSql);
		}
	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	// 完了画面表示
	header("Location: " . URL_ADMIN . $template->Self . "?M=end&ACT=" . $mode);
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {
	// データ取得
	getData($_GET , array("ACT"));
	
	switch ($_GET["ACT"]) {
		case "del":
			// 削除
			$title = $template->message("A2836");
			$msg = $template->message("A2836");
			break;
		case "stop":
			// 利用停止
			$title = $template->message("A2834");
			$msg = $template->message("A2835");
			break;
		default:
			// 新規登録
			$title = $template->message("A2832");
			$msg = $template->message("A2833");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}

/**
 * 使用一覧表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispUseList($template) {
	// データ取得
	getData($_GET , array("NO"));
	
	// 特典データ取得
	$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
			->field("hed.benefits_no, hed.end_dt, hed.issued")
			->field("hed.point, hed.limit_days, hed.stop_dt, hed.add_dt")
			->field("(select count(*) from dat_benefitsDetail dtl where dtl.benefits_no = hed.benefits_no and dtl.member_no IS NOT NULL) as ucnt")
		->from("dat_benefits hed")
		->where()
			->and("hed.benefits_no = ", $_GET["NO"], FD_NUM)
			->and("hed.del_flg = "    , 0, FD_NUM )
		->createSql("\n");
	$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
	if ($row == null) {
		$template->dispProcError($template->message("A0003"), false);
		return;
	}
	// 特典明細データ取得
	$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
			->field("dtl.benefits_no, dtl.benefits_cd, dtl.member_no, dtl.use_dt")
			->field("mm.nickname, mm.black_flg, mm.tester_flg, mm.state")
		->from("dat_benefitsDetail dtl")
			->join( "inner", "mst_member mm", "dtl.member_no = mm.member_no")
		->where()
			->and("dtl.benefits_no = ", $_GET["NO"], FD_NUM)
		->orderby("dtl.use_dt desc")
		->createSql("\n");
	$rs = $template->DB->query($sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . "_uselist.html");
	$template->assignCommon();

	// ヘッダ
	$template->assign("BENEFITS_NO_PAD", $template->formatNoBasic($row["benefits_no"]), true);
	$template->assign("END_DT"         , format_datetime($row["end_dt"]), true);
	$template->assign("POINT"          , number_formatEx($row["point"]), true);
	$template->assign("LIMIT_DAYS"     , number_formatEx($row["limit_days"]), true);
	$template->assign("ADD_DT"         , format_datetime($row["add_dt"]), true);
	$template->assign("STOP_DT"        , format_datetime($row["stop_dt"]), true);
	$template->assign("ISSUED"         , number_formatEx($row["issued"]), true);
	$template->assign("USE_CNT"        , number_formatEx($row["ucnt"]), true);

	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("BENEFITS_CD"  , $row["benefits_cd"], true);
		$template->assign("MEMBER_NO_PAD", $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"     , $row["nickname"], true);

		$template->if_enable("IS_BLACK"    , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"   , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED"  , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);
		
		$template->assign("USE_DT"         , format_datetime($row["use_dt"]), true);
		
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);
	
	// 表示
	$template->flush();
}

/**
 * 検索フォームを開くために、$_GETに検索項目が含まれているかをチェックする
 * @access	private
 * @param	array	$arr			チェック対象配列
 * @param	array	$chks			検索項目名リスト
 * @return	boolean					true / false  含まれている場合 true を返す
 */
function checkKeys( $arr, $chks){
	foreach( $chks as $key){
		if( array_key_exists( $key , $arr)){
			if( $arr[$key] != ""){
				return true;
			}
		}
	}
	return false;
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();

	if ($_GET["ACT"] != "del") {
		$errMessage = (new SmartAutoCheck($template))
			->item($_POST["POINT"])			// 付与ポイント
				->required("A2818")
				->number("A2819")
			->item($_POST["LIMIT_DAYS"])	// ポイント有効期限
				->required("A2820")
				->number("A2821")
			->item($_POST["END_DT"])		// 終了日
				->required("A2822")
				->date("A2823")
			->item($_POST["END_TIME_HR"])	// 終了時間
				->required("A2824")
			->item($_POST["END_TIME_MIN"])	// 終了分
				->required("A2825")
			->item($_POST["ISSUED"])		// 発行数
				->required("A2827")
				->number("A2828")
				->if("A2829", (int)$_POST["ISSUED"] > 0)
		->report();
		// ※自業自得という事でサーバサイドでは終了時刻が未来である事のチェックはしない
	} else {
		// 使用がある場合は削除不可
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("count(*)")
				->from("dat_benefitsDetail dat")
				->where()
					->and("dat.benefits_no = ", $_GET["NO"], FD_NUM)
					->and("dat.member_no ", "IS NOT NULL", FD_FUNCTION)
			->createSql();
		$errMessage = (new SmartAutoCheck($template))
			->item($_GET["NO"])			// 使用済
					->countSQL("A2831", $sql)
		->report();
	}

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

/**
 * 特典コードダウンロード処理
 * @access	private
 * @param	object	$template	テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcOutput($template) {
	// データ取得
	getData($_GET , array("ODR", "S_BENEFITS_NO", "S_POINT_FROM", "S_POINT_TO", "S_END_DT_FROM", "S_END_DT_TO"
						, "NO"));
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "hed.benefits_no desc";

	// 検索用日付
	$endSt = ((mb_strlen($_GET["S_END_DT_FROM"]) > 0) ? $_GET["S_END_DT_FROM"] . " 00:00:00" : "");
	$endEd = ((mb_strlen($_GET["S_END_DT_TO"]) > 0) ? $_GET["S_END_DT_TO"] . " 23:59:59" : "");

	// ヘッダ項目定義
	$csvHeader = array("特典コード");
	// ヘッダ文字列設定(対象不存在でもヘッダのみ出力)
	$outData = array();
	array_push($outData, '"' . implode('","', $csvHeader) . '"');

	// 検索SQL生成
	$sql = (new SqlString())
			->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("dtl.benefits_cd")
			->from("dat_benefits hed")
			->join("inner", "dat_benefitsDetail dtl", "hed.benefits_no = dtl.benefits_no" )
			->where()
				->and(SQL_CUT, "hed.benefits_no = ", $_GET["NO"], FD_NUM)
				->and(SQL_CUT, "hed.benefits_no = ", $_GET["S_BENEFITS_NO"], FD_NUM)
				->and(SQL_CUT, "hed.point >= "     , $_GET["S_POINT_FROM"], FD_DATE)
				->and(SQL_CUT, "hed.point <= "     , $_GET["S_POINT_TO"], FD_DATE)
				->and(SQL_CUT, "hed.end_dt >= "    , $endSt, FD_DATE)
				->and(SQL_CUT, "hed.end_dt <= "    , $endEd, FD_DATE)
				->and("hed.del_flg = "             , 0, FD_NUM )
			->orderby($_GET["ODR"])
		->createSql("\n");

	$outRs = $template->DB->query($sql);
	while ($row = $outRs->fetch(PDO::FETCH_ASSOC)) {
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
	header("Content-Disposition: attachment; filename=BenefitsCode_" . $currentDatetime . ".csv");
	header("Content-Type: application/octet-stream");
	print $ret;

}

?>
