<?php
/*
 * draw.php
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
 * 抽選ポイント履歴画面表示
 * 
 * 抽選ポイント履歴画面の表示を行う
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
	getData($_GET , array("P", "TYPE"));

	// 入力チェック
	$message = checkInput($template);
	
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	if ( !array_key_exists( $_GET["TYPE"], $GLOBALS["drawPointHistoryProcessCode"])) $_GET["TYPE"] = "";
	if( mb_strlen($_GET["TYPE"]) > 0){
		if ( !preg_match("/^[0-9]+$/", $_GET["TYPE"])) {
			$_leftjoin = "inner join his_drawPoint hdp on hdp.member_no = men.member_no";
		}else{
			$_leftjoin = 'inner join his_drawPoint hdp on hdp.member_no = men.member_no and hdp.proc_cd = "'. $_GET["TYPE"] . '"';
		}
	}else{
		$_leftjoin = "inner join his_drawPoint hdp on hdp.member_no = men.member_no";
	}
	
	//----------------------------------------------------------
	$sqls = new SqlString();
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.point, count(*) as cnt, count(hdp.member_no) as hcnt")
			->from("mst_member men")
			->from( $_leftjoin)
			->where()
				->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and(false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and(false, "men.state = ", "1", FD_NUM)
			->createSQL();
	
	// カウント取得
	$allrows = $template->DB->getRow($count_sql, PDO::FETCH_ASSOC);
	$numrows = (int)$allrows["hcnt"];
	$mypoint = $allrows["point"];
	$allrows = $numrows;
	
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / POINT_HISTORY_VIEW);		// 総ページ数	PAY_HISTORY_VIEW
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// 履歴データ取得
	$row_sql = $sqls->resetField()
			->field("men.point, hdp.proc_dt, hdp.type, hdp.proc_cd, hdp.before_draw_point, hdp.draw_point, hdp.after_draw_point")
			->orderby('proc_dt desc')
			->page( $_GET["P"], POINT_HISTORY_VIEW)
		->createSql("\n");
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	$template->assign("POINT_LABEL", number_format( $template->Session->UserInfo["draw_point"]), true);
	// 項目種別等
	$template->assign("SEL_TYPE" , makeOptionArray($GLOBALS["drawPointHistoryProcessCode"], $_GET["TYPE"], true, SELECT_VALUE_NONE));
	// リスト
	if( $allrows > 0){
		// ページング
		$template->assign("PAGING"   , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		$template->assign("ALLROW", (string)$allrows, true);		// 総件数
		$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);			// 総ページ数
		// 個別
		$template->loop_start("LIST");
		if( $allrows > 0){
			while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
				$padd = 0;
				$psum = 0;
				if( $row["type"] == "1"){
					$padd = $row["draw_point"];
				}else if( $row["type"] == "2"){
					$psum = $row["draw_point"];
				}
				$template->assign("POINT_ADD"         , number_format( $padd), true);
				$template->assign("POINT_SUM"         , number_format( $psum), true);
				$template->assign("AFTER_POINT"       , number_format( $row["after_draw_point"]), true);
				$template->assign("TYPE_LABEL"        , $GLOBALS["drawPointHistoryProcessCode"][ $row['proc_cd']], true);
				$template->assign("PROC_DT"           , format_datetime($row["proc_dt"]), true);
				$template->loop_next();
			}
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
