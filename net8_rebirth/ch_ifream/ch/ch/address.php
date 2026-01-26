<?php
/*
 * address.php
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
 * 宛先確認・設定画面表示
 * 
 * 宛先確認・設定画面の表示を行う
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
 * 宛先確認・設定画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispList($template) {

	// データ取得
	getData($_GET , array("P"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//----------------------------------------------------------
	$sqls = new SqlString();
	// カウントSQL
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_address da")
			->where()
				->and( false, "da.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "da.del_flg <> ", "1", FD_NUM)
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
			->field("da.seq, da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg")
			->page( $_GET["P"], ADDRESS_VIEW)
			->orderby('da.seq asc')
		->createSql("\n");
	// 商品データ取得
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("LIMIT", $allrows < KEEP_ADDRESS_LIMIT);
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	if( $allrows > 0){
		// ページング
		$template->assign("ALLROW", (string)$allrows, true);	// 総件数
		$template->assign("P", (string)$_GET["P"], true);		// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			// データ
			$template->assign("SEQ"           , $row["seq"], true);
			$template->assign("LASTNAME"      , $row["syll"], true);
			$template->assign("FIRSTNAME"     , $row["name"], true);
			$template->assign("ADDRESS1"      , $row["address1"], true);
			$template->assign("ADDRESS2"      , $row["address2"], true);
			$template->assign("ADDRESS3"      , $row["address3"], true);
			$template->assign("ADDRESS4"      , $row["address4"], true);
			$template->assign("POSTAL"        , $row["postal"], true);
			$template->assign("TEL"           , $row["tel"], true);
			//
			$template->if_enable("SELECTED"   , $row["use_flg"] == 1);
			$template->if_enable("NOT_SELECT" , $row["use_flg"] != 1);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	// 表示
	$template->flush();
}

?>
