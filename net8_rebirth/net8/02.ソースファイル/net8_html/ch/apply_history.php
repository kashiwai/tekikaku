<?php
/*
 * apply_history.php
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
 * 応募履歴画面表示
 * 
 * 応募履歴画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/13 初版作成 片岡 充
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
 * @param	string	$message		エラー時メッセージ
 * @return	なし
 */
function DispList($template, $message = "") {
	// データ取得
	getData($_GET , array("P", "TYPE"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	if ( !preg_match("/^[0-9]+$/", $_GET["TYPE"])) $_GET["TYPE"] = "";
	if ( !array_key_exists( $_GET["TYPE"], $GLOBALS["drawResultList"])) $_GET["TYPE"] = "";
	
	//----------------------------------------------------------
	$sqls = new SqlString();
	// カウントSQL
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_request dr")
			->where()
				->and( false, "dr.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
				->and( true, "dr.result = ", $_GET["TYPE"], FD_NUM)
			->createSQL();
	
	// カウント取得
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADDRESS_VIEW);			// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// 行SQL
	$row_sql = $sqls
			->resetField()
			->field("dr.seq, dr.member_no, dr.goods_no, dr.result, dr.request_dt")
			->field("lng.goods_name, mg.draw_point")
			->from("inner join mst_goods mg on mg.goods_no = dr.goods_no")
			->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->page( $_GET["P"], ADDRESS_VIEW)
			->orderby('dr.request_dt desc')
		->createSql("\n");
	// 商品データ取得
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("LIMIT", $allrows < KEEP_ADDRESS_LIMIT);
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	// 絞り込み
	$template->assign("SEL_TYPE"   , makeOptionArray($GLOBALS["drawResultList"], $_GET["TYPE"], true, SELECT_VALUE_NONE));
	//
	if( $allrows > 0){
		// ページング
		$template->assign("ALLROW", (string)$allrows, true);		// 総件数
		$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);			// 総ページ数
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			// データ
			$template->assign("SEQ"          , $row["seq"], true);
			$template->assign("NO"           , $row["goods_no"], true);
			$template->assign("RESULT"       , $GLOBALS["drawResultList"][$row["result"]], true);
			$template->assign("GOODS_NO"     , $row["goods_no"], true);
			$template->assign("GOODS_NAME"   , $row["goods_name"], true);
			$template->assign("DRAW_POINT"   , number_formatEx($row["draw_point"]), true);
			$template->assign("REQUEST_DT"   , format_datetime($row["request_dt"]), true);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	// 表示
	$template->flush();
	
}

?>
