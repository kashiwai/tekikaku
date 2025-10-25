<?php
/*
 * shipping.php
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
 * 商品発送画面表示
 * 
 * 商品発送画面の表示を行う
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
		
		// データ取得
		getData($_GET, array("M"));

		// 実処理
		switch ($_GET["M"]) {
		
			case "select":			// 宛先選択画面表示
				DispConf($template);
				break;
			case "set":				// 選択処理
				ProcData($template);
				break;
			default:				// 一覧画面
				DispList($template);
		}
		
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
			->from("dat_win dw")
			->where()
				->and( false, "dw.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
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
			->field("dw.seq, dw.shipping_no, dw.member_no, dw.goods_no, dw.shipping_dt, dw.state, dw.add_dt")
			->field("dw.syll, dw.name, dw.postal, dw.address1, dw.address2, dw.address3, dw.address4, dw.tel, dw.remarks")
			->field("lng.goods_name")
			->from("inner join mst_goods mg on mg.goods_no = dw.goods_no")
			->join("inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->page( $_GET["P"], ADDRESS_VIEW)
			->orderby('dw.add_dt desc')
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
	if( $allrows > 0){
		// ページング
		$template->assign("ALLROW", (string)$allrows, true);		// 総件数
		$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		
		$template->loop_start("LIST");
		while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
			// データ
			$template->assign("SEQ"          , $row["seq"], true);
			$template->assign("NO"           , $row["goods_no"], true);
			$template->assign("STATE"        , $GLOBALS["shippingStatusList"][$row["state"]], true);
			$template->assign("GOODS_NO"     , $row["goods_no"], true);
			$template->assign("GOODS_NAME"   , $row["goods_name"], true);
			$template->assign("DISP_ADD_DT"  , format_date($row["add_dt"]), true);
			$template->assign("SHIPPING_NO"  , $row["shipping_no"], true);
			//
			$template->assign("POSTAL"       , $row["postal"], true);
			$template->assign("SYLL"         , $row["syll"], true);
			$template->assign("NAME"         , $row["name"], true);
			$template->assign("ADDRESS1"     , $row["address1"], true);
			$template->assign("ADDRESS2"     , $row["address2"], true);
			$template->assign("ADDRESS3"     , $row["address3"], true);
			$template->assign("ADDRESS4"     , $row["address4"], true);
			$template->assign("TEL"          , $row["tel"], true);
			//
			$template->if_enable("POSTAL"    , mb_strlen($row["postal"]) > 0);
			$template->if_enable("EDIT"      , $row["state"] < 2);
			$template->if_enable("NOEDIT"    , $row["state"] > 1);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	// 表示
	$template->flush();
	
}


/**
 * 選択画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispConf($template) {

	// データ取得
	getData($_GET , array("SEQ", "NO", "P"));
	getData($_POST , array("P", "TYPE"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
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
	$template->open(PRE_HTML . "_select.html");
	$template->assignCommon();
	$template->if_enable("LIMIT", $allrows < KEEP_ADDRESS_LIMIT);
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	$template->assign("SEQ"           , $_GET["SEQ"], true);
	$template->assign("GOODS_NO"      , $_GET["NO"], true);
	//
	if( $allrows > 0){
		// ページング
		$template->assign("ALLROW", (string)$allrows, true);		// 総件数
		$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage) );
		
		$template->loop_start("LIST");
		while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
			// データ
			$template->assign("ADDRESS_SEQ"   , $row["seq"], true);
			$template->assign("LASTNAME"      , $row["syll"], true);
			$template->assign("FIRSTNAME"     , $row["name"], true);
			$template->assign("ADDRESS1"      , $row["address1"], true);
			$template->assign("ADDRESS2"      , $row["address2"], true);
			$template->assign("ADDRESS3"      , $row["address3"], true);
			$template->assign("ADDRESS4"      , $row["address4"], true);
			$template->assign("POSTAL"        , $row["postal"], true);
			$template->assign("TEL"           , $row["tel"], true);
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

/**
 * 選択処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcData($template) {
	
	// データ取得
	getData($_GET , array("SEQ", "ASEQ", "NO"));
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	// 選択データ取得
	$sql = ( new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("da.seq, da.syll, da.name, da.postal, da.address1, da.address2, da.address3, da.address4, da.tel, da.use_flg")
			->from("dat_address da")
			->where()
				->and( false, "da.member_no = ",  $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "da.seq = ", $_GET["ASEQ"], FD_NUM)
				->and( false, "da.del_flg <> ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
	// セット
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "dat_win" )
			->set()
				->value( "state"     , "1", FD_NUM)
				->value( "syll"      , $row["syll"], FD_STR)
				->value( "name"      , $row["name"], FD_STR)
				->value( "postal"    , $row["postal"], FD_STR)
				->value( "address1"  , $row["address1"], FD_STR)
				->value( "address2"  , $row["address2"], FD_STR)
				->value( "address3"  , $row["address3"], FD_STR)
				->value( "address4"  , $row["address4"], FD_STR)
				->value( "tel"       , $row["tel"], FD_STR)
				->value( "upd_no"    , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"    , "current_timestamp", FD_FUNCTION)
			->where()
				->and( false, "goods_no  = "  , $_GET["NO"], FD_NUM)
				->and( false, "seq  = "       , $_GET["SEQ"], FD_NUM)
				->and( false, "member_no = "  , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "state < ", "2", FD_NUM)
		->createSQL();
	$template->DB->query($sql);
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// 一覧に戻る
	header("Location: " . URL_SSL_SITE . "shipping.php");
	
}

?>
