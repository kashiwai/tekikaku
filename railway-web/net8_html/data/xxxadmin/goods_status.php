<?php
/*
 * goods_status.php
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
 * 申込状況画面表示
 * 
 * 申込状況画面の表示を行う
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
		
		// 実処理
		DispList($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), true);
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
						, "S_GOODS_NO", "S_MEMBER_NO", "REQUEST_DT_FROM", "REQUEST_DT_TO"
						));

	$get_draw_state = array();
	if (empty($_GET["CHK_DRAW_STATE"])) $_GET["CHK_DRAW_STATE"] = array();
	if (isset($_GET["CHK_DRAW_STATE"]) && count($_GET["CHK_DRAW_STATE"]) > 0) {
		// 値のある配列のみ抽出(keyのみ存在して値が空の配列に対応する為)
		$get_draw_state = array_filter($_GET["CHK_DRAW_STATE"], "strlen");
		$out = array();
		foreach ($get_draw_state as $v){
			$out[] = "CHK_DRAW_STATE%5B%5D=".$v;
		}
		$_GET["CHK_DRAW_STATE"] = implode('&', $out);
	}

	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "request_dt desc";
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P", "CHK_DRAW_STATE"), $_GET["CHK_DRAW_STATE"]);
	
	if( mb_strlen( $_GET["NO"]) > 0){
		$_GET["S_GOODS_NO"] = $_GET["NO"];
	}
	
	//検索判定
	if( ($_GET["S_GOODS_NO"]!="") || ($_GET["S_MEMBER_NO"]!="")|| ($_GET["REQUEST_DT_FROM"]!="") || ($_GET["REQUEST_DT_TO"]!="")){
		$_search = "show";
	}else{
		$_search = "";
	}
	if( count($get_draw_state)>0) $_search = "show";
	
	// 検索用日付
	$requestSt = ((mb_strlen($_GET["REQUEST_DT_FROM"]) > 0) ? GetRefTimeStart($_GET["REQUEST_DT_FROM"]) : "");
	$requestEd = ((mb_strlen($_GET["REQUEST_DT_TO"]) > 0) ? GetRefTimeEnd($_GET["REQUEST_DT_TO"]) : "");
	
	// DB
	$sqls = new SqlString();
	if( $_search != ""){
		$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "count(*)" )
				->from("dat_request dr")
				->from("inner join mst_member mm on mm.member_no = dr.member_no")
				->from("inner join mst_goods mg on mg.goods_no = dr.goods_no")
				->join( "inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
				->where()
					->and(true, "dr.goods_no = ",    $_GET["S_GOODS_NO"], FD_NUM)
					->and(true, "dr.member_no = ",   $_GET["S_MEMBER_NO"], FD_NUM)
					->and(true, "dr.request_dt >= ", $requestSt, FD_DATEEX)
					->and(true, "dr.request_dt <= ", $requestEd, FD_DATEEX)
			->createSql();
		
		if (count($get_draw_state) > 0){
			$csql = $sqls
					->setAutoConvert( [$template->DB,"conv_sql"] )
					->and( true, "dr.result in ", $get_draw_state, FD_NUM )
			->createSql();
		}
		
	}else{
		$csql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
				->field( "count(*)" )
				->from("dat_request dr")
				->from("inner join mst_member mm on mm.member_no = dr.member_no")
				->from("inner join mst_goods mg on mg.goods_no = dr.goods_no")
				->join( "inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
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
			->field("dr.seq, dr.member_no, dr.goods_no, dr.request_dt, dr.result, dr.upd_dt")
			->field("mm.nickname, mm.black_flg, mm.state, mm.tester_flg")
			->field("mg.draw_type, mg.draw_state")
			->field("lng.goods_name")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	// 検索
	$template->assign("S_OPEN"           , $_search, true);
	$template->assign("S_GOODS_NO"       , $_GET["S_GOODS_NO"], true);
	$template->assign("S_MEMBER_NO"      , $_GET["S_MEMBER_NO"], true);
	$template->assign("CHK_DRAW_STATE"   , makeCheckBoxArray( $GLOBALS["drawResultList"], "CHK_DRAW_STATE[]", $get_draw_state, 0, "", "　", "", true));
	$template->assign("REQUEST_DT_FROM"  , $_GET["REQUEST_DT_FROM"], true);
	$template->assign("REQUEST_DT_TO"    , $_GET["REQUEST_DT_TO"], true);
	
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
		$template->if_enable("IS_BLACK"   , $row["black_flg"] == 1);
		$template->if_enable("IS_TESTER"  , $row["black_flg"] == 0 && $row["tester_flg"] == 1);
		$template->if_enable("IS_RETIRED" , $row["black_flg"] == 0 && $row["tester_flg"] == 0 && $row["state"] == 9);

		$template->assign("GOODS_NO"       , $row["goods_no"], true);
		$template->assign("GOODS_NO_PAD"   , $template->formatNoBasic($row["goods_no"]), true);
		$template->assign("GOODS_NAME"     , $row["goods_name"], true);
		$template->assign("MEMBER_NO"      , $row["member_no"], true);
		$template->assign("MEMBER_NO_PAD"  , $template->formatMemberNo($row["member_no"]), true);
		$template->assign("NICKNAME"       , $row["nickname"], true);
		$template->assign("REQUEST_DT"     , format_datetime($row["request_dt"]), true);
		$template->assign("GOODS_STATE"    , $template->getArrayValue($GLOBALS["drawStatusList"], $row["draw_state"]), true);
		$template->assign("DISP_RESULT"    , $template->getArrayValue( $GLOBALS["drawResultList"], $row["result"]), true);

		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
	
}

?>
