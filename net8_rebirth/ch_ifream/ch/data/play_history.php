<?php
/*
 * play_history.php
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
 * @author   NET8 Team
 * @version  1.0
 * @since    2025/01/13 初版作成
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

	//----------------------------------------------------------
	// カウント取得
	$sqls = new SqlString();
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*) as cnt")
			->from("his_play hp")
			->from("inner join dat_machine dm on dm.machine_no = hp.machine_no")
			->from("inner join mst_model mm on mm.model_no = dm.model_no")
			->where()
				->and(false, "hp.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
			->createSQL();

	// カウント取得
	$row = $template->DB->getRow($count_sql, PDO::FETCH_ASSOC);
	$numrows = (int)$row["cnt"];
	$allrows = $numrows;

	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / 20);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;

	// 履歴データ取得
	$row_sql = $sqls->resetField()
			->field("hp.machine_no, hp.start_dt, hp.end_dt, hp.point, hp.credit, hp.draw_point")
			->field("hp.play_count, hp.bb_count, hp.rb_count")
			->field("dm.machine_cd, mm.model_name, mm.model_roman")
			->orderby('hp.start_dt desc')
			->page( $_GET["P"], 20)
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
		$template->assign("PAGING"   , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		$template->assign("ALLROW", (string)$allrows, true);	// 総件数
		$template->assign("P", (string)$_GET["P"], true);		// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);		// 総ページ数

		// リスト
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$template->assign("MACHINE_NO"        , $row["machine_no"], true);
			$template->assign("MACHINE_CD"        , $row["machine_cd"], true);
			$template->assign("MODEL_NAME"        , (FOLDER_LANG==DEFAULT_LANG)? $row["model_name"]:$row["model_roman"], true);
			$template->assign("START_DT"          , format_datetime($row["start_dt"]), true);
			$template->assign("END_DT"            , format_datetime($row["end_dt"]), true);
			$template->assign("PLAY_COUNT"        , number_format($row["play_count"]), true);
			$template->assign("BB_COUNT"          , number_format($row["bb_count"]), true);
			$template->assign("RB_COUNT"          , number_format($row["rb_count"]), true);
			$template->assign("CREDIT"            , number_format($row["credit"]), true);
			$template->assign("POINT"             , number_format($row["point"]), true);
			$template->assign("DRAW_POINT"        , number_format($row["draw_point"]), true);

			$template->loop_next();
		}
		$template->loop_end("LIST");
	}

	$template->flush();
}

?>
