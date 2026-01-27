<?php
/*
 * playlist.php
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
 * @version  1.01
 * @since    2019/02/07 初版作成 片岡 充
 * @since    2022/09/26 v1.01    岡本 静子
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateUser(false);
		$template->checkSessionUser(true, true);
		$PPOINT = new PlayPoint($template->DB, false);
		$PPOINT->pointReSession();
		
		// 実処理
		DispList($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 一覧画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispList($template) {

	// データ取得
	getData($_GET , array("P"));
	
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	// 入力チェック
	$message = checkInput($template);
	
	//----------------------------------------------------------
	$sqls = new SqlString();
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("mst_member men")
			->from("inner join his_play hs on hs.member_no = men.member_no" )
			->where()
				->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and(false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and(false, "men.state = ", "1", FD_NUM)
			->createSQL();
	
	// カウント取得
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / PLAY_HISTORY_VIEW);		// 総ページ数	PLAY_HISTORY_VIEW
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// プレイ履歴データ取得
	$row_sql = $sqls->resetField()
			->field("hs.machine_no, hs.point, hs.credit, hs.draw_point, hs.in_point, hs.out_point, hs.in_credit, hs.out_credit, hs.out_draw_point")		//プレイデータ
			->field("hs.start_dt, hs.end_dt")																											//プレイデータ時刻
			->field("hs.lost_point")		//--- 2022/09/26 Add by S.Okamoto 失効期限付ポイント追加
			->field("mm.model_name, mm.model_roman")																					//その他
			->from("inner join dat_machine dm on dm.machine_no = hs.machine_no" )
			->from("inner join mst_model mm on mm.model_no = dm.model_no" )
			->orderby('start_dt desc')
			->page( $_GET["P"], PLAY_HISTORY_VIEW) //PLAY_HISTORY_VIEW
		->createSql("\n");
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	
	// リスト
	if( $allrows > 0){
		// ページング
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		$template->assign("ALLROW", (string)$allrows, true);	// 総件数
		$template->assign("P", (string)$_GET["P"], true);		// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
		// 台リスト
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			/* プレイデータ */
			$template->assign("MACHINE_NO"         , $row["machine_no"], true);
			$template->assign("MODEL_NAME"         , (FOLDER_LANG==DEFAULT_LANG)? $row["model_name"]:$row["model_roman"], true);
			$template->assign("IN_CREDIT"          , number_format( $row["in_credit"]), true);			// メダル消費?
			$template->assign("OUT_CREDIT"         , number_format( $row["out_credit"]), true);			// メダル払出?
			$template->assign("IN_POINT"           , number_format( $row["in_point"]), true);			// LP消費?
			$template->assign("OUT_POINT"          , number_format( $row["out_point"]), true);			// LP払出?
			$template->assign("OUT_DRAW_POINT"     , number_format( $row["out_draw_point"]), true);		// マイル払出?
			$template->assign("LOST_POINT"         , number_format( $row["lost_point"]), true);			//--- 2022/09/26 Add by S.Okamoto 失効期限付ポイント追加
			$template->assign("START_DT"           , format_datetime($row["start_dt"]), true);
			$template->assign("END_DT"             , format_datetime($row["end_dt"]), true);
			$template->assign("DATE"               , strtotime( $row["start_dt"]), true);
			// 差分
			$template->assign("CREDIT_DIFFERENCE"  , number_format( $row["out_credit"] - $row["in_credit"]), true);
			$template->assign("POINT_DIFFERENCE"   , number_format( $row["out_point"] - $row["in_point"]), true);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	
	// 表示
	$template->flush();
}


/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_GET["P"])
			->number('U0009')
		->report();
	if(!empty($errMessage)) $_GET["P"] = 0;
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
