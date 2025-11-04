<?php
/*
 * pay.php
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
 * 決済履歴画面表示
 * 
 * 決済履歴画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/08 初版作成 片岡 充
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
		
		// データ取得
		getData($_GET, array("M"));
		
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
	$sqls = new SqlString();
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("mst_member men")
			->from("inner join his_purchase hp on hp.member_no = men.member_no" )
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
	$allpage = ceil($numrows / PAY_HISTORY_VIEW);		// 総ページ数	PAY_HISTORY_VIEW
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// 履歴データ取得
	$row_sql = $sqls->resetField()
			->field("hp.purchase_no, hp.recept_dt, hp.purchase_type, hp.amount, hp.point, hp.result_status, hp.result_message, hp.purchase_dt")
			->orderby('recept_dt desc')
			->page( $_GET["P"], PAY_HISTORY_VIEW)
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
		// リスト
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			$template->assign("PURCHASE_TYPE_LABEL" , $GLOBALS["viewPurchaseType"][ $row['purchase_type']], true);
			$template->assign("RESULT_STATUS_LABEL" , $GLOBALS["purchaseResultStatus"][ $row['result_status']], true);
			$template->assign("RESULT_MESSAGE"      , $row["result_message"], true);
			$template->assign("POINT"               , number_format( $row["point"]), true);
			$template->assign("AMOUNT"              , number_format( $row["amount"]), true);
			$template->assign("CURRENCY"            , $GLOBALS["viewAmountType"][ $row['purchase_type']], true);
			$template->assign("RECEPT_DT"           , format_datetime($row["recept_dt"]), true);
			$template->assign("PURCHASE_DT"         , format_datetime($row["purchase_dt"]), true);
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	// 表示
	$template->flush();
}

?>
