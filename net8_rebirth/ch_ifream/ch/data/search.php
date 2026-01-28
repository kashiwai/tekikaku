<?php
/*
 * search.php
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
 * 台検索画面表示
 * 
 * 台検索画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/13 初版作成 片岡 充
 */

// 言語設定（URL lang パラメータから取得）デフォルト: 中国語
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'zh';
$allowedLangs = ['zh', 'ko', 'en', 'ja'];
if (!in_array($lang, $allowedLangs)) {
	$lang = 'zh';
}
if (!defined('FOLDER_LANG')) {
	define("FOLDER_LANG", $lang);
}

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
	getData($_GET, array("P", "NAME", "MAKER", "GENERATION", "FREE", "ODR", "VIEW"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;

	// VIEW値のバリデーション
	if( $_GET["VIEW"] == "" || !isset($_GET["VIEW"])){
		$_GET["VIEW"] = defined('MODEL_LIST_VIEW') ? MODEL_LIST_VIEW : 20;
	}else{
		if (!isset($GLOBALS["viewcountList"]) || !is_array($GLOBALS["viewcountList"]) || array_search( $_GET["VIEW"], $GLOBALS["viewcountList"]) === false) {
			$_GET["VIEW"] = defined('MODEL_LIST_VIEW') ? MODEL_LIST_VIEW : 20;
		}
	}

	// VIEW値が整数であることを保証
	$_GET["VIEW"] = (int)$_GET["VIEW"];
	if ($_GET["VIEW"] <= 0) $_GET["VIEW"] = 20;
	

	//オーダーを分解
	// orderTypeList が未定義の場合はデフォルト値を設定
	if (!isset($GLOBALS["orderTypeList"]) || !is_array($GLOBALS["orderTypeList"])) {
		$GLOBALS["orderTypeList"] = array(
			"mm.add_dt" => "登録日順"
		);
	}

	$order = array_keys($GLOBALS["orderTypeList"]);
	if (mb_strlen($_GET["ODR"]) > 0){
		$order_target = explode(" ", str_replace('+', ' ', $_GET["ODR"] ));
		if ( !array_key_exists( $order_target[0], $GLOBALS["orderTypeList"]) || ($order_target[1] != "asc" && $order_target[1] != "desc" )) {
			$order_target = array();
			$order_target[] = $order[0];
			$_GET["ODR"] = $order[0] . " asc";
		}
	}else{
		$order_target[] = $order[0];
		$_GET["ODR"] = $order[0] . " asc";
	}
	
	//時間チェック 営業時間が日をまたぐこと確定としてのチェック
	$open  = false;
	$today = date('H:i:s');
	if( strtotime( GLOBAL_OPEN_TIME.':00') <= strtotime( $today)){
		$open = true;
	}else{
		if( strtotime( GLOBAL_CLOSE_TIME.':00') > strtotime( $today)){
			$open = true;
		}
	}
	
	$_login_flg  = false;
	$testerFlg = false;
	if( $template->checkSessionUser(true, false)){
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("men.tester_flg")
				->from("mst_member men")
				->where()
					->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and(false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
					->and(false, "men.state = ", "1", FD_NUM)
				->createSQL();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$testerFlg = ($row["tester_flg"] == "1");
		$_login_flg  = true;
	}
	
	// メーカーマスタからデータを取得
	// DB認証チェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("maker_no, maker_name, maker_roman")
				->orderby("maker_no asc")
				->from("mst_maker")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
					->and(false, "disp_flg = ", "1", FD_NUM)
			->createSQL();
	$makerList = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$makerList[ $row['maker_no']] = (FOLDER_LANG==DEFAULT_LANG)? $row['maker_name'] : $row['maker_roman'];
	}
	
	// 号機マスタからデータを取得
	// DB認証チェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("unit_no, unit_name, unit_roman")
				->orderby("sort_no asc")
				->from("mst_unit")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
			->createSQL();
	$geneList = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$geneList[ $row['unit_no']] = (FOLDER_LANG==DEFAULT_LANG)? $row['unit_name'] : $row["unit_roman"];
	}
	
	//英語用
	if( FOLDER_LANG == DEFAULT_LANG){
		$modelName = $_GET["NAME"];
		$modelRoman = "";
	}else{
		$modelName = "";
		$modelRoman = $_GET["NAME"];
	}
	
	//----------------------------------------------------------
	//	検索条件に従って台データをDBから取得
	$sqls = (new SqlString($template->DB));
	$template->SearchMachineBase($sqls, $testerFlg);
	$sqls->and(true,  "mm.model_name like "  , ["%",$modelName,"%"], FD_STR )
		->and(true,  "mm.model_roman like " , ["%",$modelRoman,"%"], FD_STR )		//英語版では model_roman と比較
		->and(true,  "mm.maker_no = "      , $_GET["MAKER"], FD_NUM)
		->and(true,  "mm.unit_no = "       , $_GET["GENERATION"], FD_NUM);
	if ($_GET["FREE"]==1) {		// 空き台のみ
		$sqls->and(SQL_CUT, "dm.machine_status <> ", (!$testerFlg) ? "2":"", FD_NUM)	// テスター以外はメンテ中を除く
			->groupStart()
				->and("lm.assign_flg = "  , "0", FD_NUM)		// 未割当
				->groupStart("or")
					->and("lm.assign_flg = ", "1", FD_NUM)		// 割当済
					->and("lm.member_no = " , ($_login_flg) ? $template->Session->UserInfo["member_no"] : "NULL", FD_FUNCTION)		// 自会員
				->groupEnd()
			->groupEnd();
	}
	
	// カウント取得
	$count_sql = $sqls->resetField()->field("count(*)")->createSQL();
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;

	// ゼロ除算対策
	$viewCount = (int)$_GET["VIEW"];
	if ($viewCount <= 0) $viewCount = 20;

	$allpage = ceil($numrows / $viewCount);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	$cnt_disp = 0;										// 表示件数
	
	// 台データ取得
	$template->SearchMachineField($sqls);
	$row_sql = $sqls->orderby( str_replace('+', ' ', $_GET["ODR"] ))
				->orderby( "mm.add_dt desc")
				->page( $_GET["P"], (int)$_GET["VIEW"])
					->createSql("\n");
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 検索条件部分
	$template->assign("NAME"           , $_GET["NAME"], true);
	$template->assign("SEL_MAKER"      , makeOptionArray( $makerList, $_GET["MAKER"], true, SELECT_VALUE_NONE));
	$template->assign("SEL_GENERATION" , makeOptionArray( $geneList, $_GET["GENERATION"], true, SELECT_VALUE_NONE));
	$template->assign("CHK_FREE"       , ($_GET["FREE"]=="1")? 'checked=""':"", true);
	$template->assign("ODR"            , $_GET["ODR"]);
	$template->assign("SEL_ODR"        , makeOptionArray($GLOBALS["orderTypeList"], $order_target[0], false));
	$template->assign("VIEW"           , $_GET["VIEW"], true);
	$template->assign("SEL_VIEW"       , makeOptionArray($GLOBALS["viewcountList"], $_GET["VIEW"], false));
	
	// 台リスト
	$template->AssignMachineList($rs, $open, $_login_flg, $testerFlg);
	
	// ページング
	$queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '';
	$template->assign("PAGING" , HtmlPagingTag( (($queryString!="")? "?".$queryString."&":"?"), $_GET["P"], $allpage) );
	// ページング用
	$template->assign("CURL"           , ($queryString!="")? "?".$queryString."&":"?");
	// ページ処理
	$template->assign("ALLROW", (string)$allrows, true);	// 総件数
	$template->assign("P", (string)$_GET["P"], true);		// 現在ページ番号
	$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
	
	// 表示
	$template->flush();
}

?>
