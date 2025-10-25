<?php
/*
 * model.php
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
 * 機種管理画面表示
 * 
 * 機種管理画面の表示を行う
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
	getData($_GET , array("P", "ODR", "S_MODEL_NO", "S_MODEL_CD", "S_MODEL_NAME", "S_CATEGORY", "S_MAKER_NO", "S_TYPE_NO", "S_UNIT_NO"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "model_no asc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));

	// DB
	$sqls = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
		->select()
		->field( "count(*)" )
		->from("mst_model mo")
		->where()
			->and( true, "mo.model_no = "      , $_GET["S_MODEL_NO"], FD_NUM )
			->and( true, "mo.model_cd like "   , ["%",$_GET["S_MODEL_CD"],"%"], FD_STR )
			->and( true, "mo.model_name like " , ["%",$_GET["S_MODEL_NAME"],"%"], FD_STR )
			->and( true, "mo.maker_no = "      , $_GET["S_MAKER_NO"], FD_NUM )
			->and( true, "mo.type_no = "       , $_GET["S_TYPE_NO"], FD_NUM )
			->and( true, "mo.unit_no = "       , $_GET["S_UNIT_NO"], FD_NUM )
			->and( false, "mo.del_flg = "      , 0, FD_STR );

	if (mb_strlen($_GET["S_CATEGORY"]) > 0) {
		$sqls->and( true, "mo.category = "     , $_GET["S_CATEGORY"], FD_NUM );
	} else {
		$sqls->and( true, "mo.category in "    , array_keys($GLOBALS["categoryList"]), FD_NUM );
	}
	$csql = $sqls->createSql();
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	//検索判定
	if (($_GET["S_MODEL_NO"]!="") || ($_GET["S_MODEL_CD"]!="") || ($_GET["S_MODEL_NAME"]!="") || ($_GET["S_CATEGORY"]!="")
		 || ($_GET["S_MAKER_NO"]!="") || ($_GET["S_TYPE_NO"]!="") || ($_GET["S_UNIT_NO"]!="")) {
		$_search = "show";
	}else{
		$_search = "";
	}
	
	$rsql = $sqls
			->resetField()
			->field("mo.model_no, mo.model_cd, mo.category, mo.model_name, mo.model_roman, mo.type_no, mo.unit_no")
			->field("count( dm.machine_no) as mcnt")
			->field("mt.type_name, mt.type_roman")
			->field("mu.unit_name, mu.unit_roman")
			->field("ma.maker_name, ma.maker_roman")
			->from("left join dat_machine dm on dm.model_no = mo.model_no and dm.del_flg = 0" )
			->from("left join mst_type mt on mt.type_no = mo.type_no and mt.del_flg = 0" )
			->from("left join mst_unit mu on mu.unit_no = mo.unit_no and mu.del_flg = 0" )
			->from("left join mst_maker ma on ma.maker_no = mo.maker_no and ma.del_flg = 0" )
			->groupby( "mo.model_no" )
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 検索関連
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_MODEL_NO"          , $_GET["S_MODEL_NO"], true);
	$template->assign("S_MODEL_CD"          , $_GET["S_MODEL_CD"], true);
	$template->assign("S_MODEL_NAME"        , $_GET["S_MODEL_NAME"], true);
	$template->assign("SEL_CATEGORY"        , makeOptionArray($GLOBALS["categoryList"], $_GET["S_CATEGORY"]));
	$template->assign("SEL_MAKER"           , makeOptionArray($template->DB->getMakerList(1), $_GET["S_MAKER_NO"]));
	$template->assign("SEL_TYPE"            , makeOptionArray($template->DB->getTypeList(), $_GET["S_TYPE_NO"]));
	$template->assign("SEL_UNIT"            , makeOptionArray($template->DB->getUnitList(), $_GET["S_UNIT_NO"]));
	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?".$_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , number_formatEx($allrows));			// 総件数
	$template->assign("P"       , number_formatEx($_GET["P"]));			// 現在ページ番号
	$template->assign("ALLP"    , number_formatEx($allpage));			// 総ページ数
	// ソート
	$template->assign("ODR"     , $_GET["ODR"]);						// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		
		$template->assign("MODEL_NO_PAD"     , $template->formatNoBasic($row["model_no"]), true);
		$template->assign("MODEL_NO"         , $row["model_no"], true);
		$template->assign("MODEL_CD"         , $row["model_cd"], true);
		$template->assign("CATEGORY_LABEL"   , $template->getArrayValue($GLOBALS["categoryList"], $row["category"]), true);
		$template->assign("MODEL_NAME"       , $row["model_name"], true);
		$template->assign("MODEL_ROMAN"      , $row["model_roman"], true);
		$template->assign("TYPE_NAME"        , $row["type_name"], true);
		$template->assign("UNIT_NAME"        , $row["unit_name"], true);
		$template->assign("MAKER_NAME"       , $row["maker_name"], true);
		
		$template->if_enable("EXISTS_LIST"   , $row["mcnt"] > 0);
		$template->if_enable("NO_EXISTS_LIST", $row["mcnt"] == 0);
		
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
	getData($_GET , array("NO", "ACT"));
	getData($_POST , array("OLD_CATEGORY", "CATEGORY", "MODEL_NO", "MODEL_NAME", "MODEL_ROMAN", "TYPE_NO", "UNIT_NO", "MAKER_NO"
							,"PRIZEBALL_DATA_MAX" ,"PRIZEBALL_DATA_MAX_RATE", "PRIZEBALL_DATA_NAVEL", "PRIZEBALL_DATA_TULIP", "PRIZEBALL_DATA_ATTACKER1", "PRIZEBALL_DATA_ATTACKER2"
							,"PRIZEBALL_DATA_V_PRIZE", "PRIZEBALL_DATA_AUTOCHANCE", "PRIZEBALL_DATA_TULIP_COUNT", "PRIZEBALL_DATA_ATTACKER2NOT"		// 2020/12/12 [ADD] パチ対応
							,"IMAGE_LIST", "IMAGE_DETAIL", "IMAGE_REEL", "REMARKS", "BOARD_VER", "LIMITCREDIT", "LIMITTIME", "LAYOUT_DATA"
							, "MODEL_CD", "RENCHAN_GAMES", "TENJO_GAMES"
							));
	
	if (mb_strlen($_GET["NO"]) > 0 && (mb_strlen($message) == 0 || $_GET["ACT"] == "del")) {
		$_load = true;
	}else{
		$_load = false;
	}

	// 初期値設定
	$arySettingList = array();
	if (isset($GLOBALS["ModelSettingList"])) {
		$arySettingList = makeValueKeyArray($GLOBALS["ModelSettingList"]);
		if (mb_strlen($_GET["NO"]) <= 0 && mb_strlen($message) <= 0) {
			$_POST["SETTING_LIST"] = $GLOBALS["ModelSettingList"];
		}
	}
	// 2021/01 [ADD Start] 連チャン、天井ゲーム数初期値設定
	if (mb_strlen($_GET["NO"]) <= 0 && mb_strlen($message) <= 0) {
		$_POST["RENCHAN_GAMES"] = 0;
		$_POST["TENJO_GAMES"] = 9999;
	}
	// 2021/01 [ADD End] 連チャン、天井ゲーム数初期値設定

	if( $_load ){
		$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mo.model_no, mo.model_cd, mo.category, mo.model_name, mo.model_roman, mo.maker_no, mo.type_no, mo.unit_no, mo.image_list, mo.image_detail, mo.image_reel, mo.prizeball_data, mo.layout_data, mo.remarks, mo.upd_dt, mo.del_flg")
				->field("mo.setting_list")
				->field("mo.renchan_games, mo.tenjo_games")
				->field("mt.type_name, mt.type_roman")
				->field("mu.unit_name, mu.unit_roman")
				->field("ma.maker_name, ma.maker_roman")
				->from("mst_model mo" )
				->from("left join mst_type mt on mt.type_no = mo.type_no and mt.del_flg = 0" )
				->from("left join mst_unit mu on mu.unit_no = mo.unit_no and mu.del_flg = 0" )
				->from("left join mst_maker ma on ma.maker_no = mo.maker_no and ma.del_flg = 0" )
				->where()
					->and( "model_no = ",   $_GET["NO"], FD_NUM )
			->createSql();
		$row = $template->DB->getRow( $sql, MDB2_FETCHMODE_ASSOC);
		if ($row == null) {
			$template->dispProcError($template->message("A0003"), false);
			return;
		}
		
		// 賞球設定データ成形
		$prizeball_data_json = json_decode( $row["prizeball_data"], true);
		// 2020/12/12 [UPD Start] パチ対応
		$prizeball_data_json = (is_null($prizeball_data_json) ? array() : $prizeball_data_json);
		$row["prizeball_data_max"]        = $template->getArrayValue($prizeball_data_json, "MAX");
		$row["prizeball_data_max_rate"]   = $template->getArrayValue($prizeball_data_json, "MAX_RATE");
		$row["prizeball_data_navel"]      = $template->getArrayValue($prizeball_data_json, "NAVEL");
		$row["prizeball_data_tulip"]      = $template->getArrayValue($prizeball_data_json, "TULIP");
		$row["prizeball_data_attacker1"]  = $template->getArrayValue($prizeball_data_json, "ATTACKER1");
		$row["prizeball_data_attacker2"]  = $template->getArrayValue($prizeball_data_json, "ATTACKER2");
		//$row["prizeball_data_extend"]     = ($prizeball_data_json["EXTEND"]!="")? json_encode( $prizeball_data_json["EXTEND"]):"";
		$row["prizeball_data_v_prize"]    = $template->getArrayValue($prizeball_data_json, "V_PRIZE");
		$row["prizeball_data_autochance"] = $template->getArrayValue($prizeball_data_json, "AUTOCHANCE");
		$extend = $template->getArrayValue($prizeball_data_json, "EXTEND");
		$extend = (is_array($extend) ? $extend : array());
		$row["prizeball_data_tulip_count"]  = $template->getArrayValue($extend, "TULIP_COUNT");
		$row["prizeball_data_attacker2not"] = $template->getArrayValue($extend, "ATTACKER2NOT");
		// 2020/12/12 [UPD End] パチ対応
		// レイアウトデータ成形
		$layout_data_json = json_decode( $row["layout_data"], true);
		$layout_data_list = array();
		foreach ( $GLOBALS["LayoutNameList"] as $key => $value){
			if((!empty($layout_data_json) && isset($layout_data_json["hide"])) && in_array( $key, $layout_data_json["hide"], true)){
				//checked
				$layout_data_list[ $key] = 1;
			}else{
				$layout_data_list[ $key] = 0;
			}
		}
		//基盤バージョン
		$row["board_ver"]   = (isset($layout_data_json["version"]) ? $layout_data_json["version"]: "");
		$row["limitcredit"] = (isset($layout_data_json["limitcredit"]) ? $layout_data_json["limitcredit"] : "");
		$row["limittime"]   = (isset($layout_data_json["limittime"]) ? $layout_data_json["limittime"] : "");
		//PUSHオーダー
		$pushorderList = array();
		for( $i=1; $i<=PUSH_ORDER_COUNT; $i++){
			if(isset($layout_data_json["bonus_push"][$i])){
				$row["image_push".trim($i)]  = $layout_data_json["bonus_push"][$i]["path"];
				$row["image_label".trim($i)] = $layout_data_json["bonus_push"][$i]["label"];
			}else{
				$row["image_push".trim($i)] = "";
				$row["image_label".trim($i)] = "";
			}
		}
		$row["old_category"] = $row["category"];
		// 設定リスト
		$row["setting_list"] = (mb_strlen($row["setting_list"]) > 0) ? explode(",", $row["setting_list"]) : array();
		
	}else{
		//POST or 新規
		$row["model_no"]     = $_POST["MODEL_NO"];
		$row["model_cd"]     = $_POST["MODEL_CD"];
		$row["category"]     = $_POST["CATEGORY"];
		$row["old_category"] = $_POST["CATEGORY"];
		$row["model_name"]   = $_POST["MODEL_NAME"];
		$row["model_roman"]  = $_POST["MODEL_ROMAN"];
		$row["type_no"]      = $_POST["TYPE_NO"];
		$row["unit_no"]      = $_POST["UNIT_NO"];
		$row["maker_no"]     = $_POST["MAKER_NO"];
		$row["image_list"]   = $_POST["IMAGE_LIST"];
		$row["image_detail"] = $_POST["IMAGE_DETAIL"];
		$row["image_reel"]   = $_POST["IMAGE_REEL"];
		$row["layout_data"]  = $_POST["LAYOUT_DATA"];
		$row["remarks"]      = $_POST["REMARKS"];
		$row["renchan_games"]= $_POST["RENCHAN_GAMES"];
		$row["tenjo_games"]  = $_POST["TENJO_GAMES"];
		//
		$row["prizeball_data_max"]        = $_POST["PRIZEBALL_DATA_MAX"];
		$row["prizeball_data_max_rate"]   = $_POST["PRIZEBALL_DATA_MAX_RATE"];
		$row["prizeball_data_navel"]      = $_POST["PRIZEBALL_DATA_NAVEL"];
		$row["prizeball_data_tulip"]      = $_POST["PRIZEBALL_DATA_TULIP"];
		$row["prizeball_data_attacker1"]  = $_POST["PRIZEBALL_DATA_ATTACKER1"];
		$row["prizeball_data_attacker2"]  = $_POST["PRIZEBALL_DATA_ATTACKER2"];
		// 2020/12/12 [UPD Start] パチ対応
		//$row["prizeball_data_extend"]     = $_POST["PRIZEBALL_DATA_EXTEND"];
		$row["prizeball_data_v_prize"]      = $_POST["PRIZEBALL_DATA_V_PRIZE"];
		$row["prizeball_data_autochance"]   = $_POST["PRIZEBALL_DATA_AUTOCHANCE"];
		$row["prizeball_data_tulip_count"]  = $_POST["PRIZEBALL_DATA_TULIP_COUNT"];
		$row["prizeball_data_attacker2not"] = $_POST["PRIZEBALL_DATA_ATTACKER2NOT"];
		// 2020/12/12 [UPD End] パチ対応
		//
		$layout_data_list = array();
		foreach ( $GLOBALS["LayoutNameList"] as $key => $value){
			$layout_data_list[ $key] = 0;
		}
		//基盤バージョン
		$row["board_ver"]    = $_POST["BOARD_VER"];
		$row["limitcredit"]  = $_POST["LIMITCREDIT"];
		$row["limittime"]    = $_POST["LIMITTIME"];
		//PUSHオーダー
		$pushorderList = array();
		for( $i=1; $i<=PUSH_ORDER_COUNT; $i++){
			$row["image_push".trim($i)]  = ((isset($_POST["IMAGE_PUSH"][trim($i)])) ? $_POST["IMAGE_PUSH"][trim($i)] : "");
			$row["image_label".trim($i)] = ((isset($_POST["PUSH_LABEL"][trim($i)])) ? $_POST["PUSH_LABEL"][trim($i)] : "");
		}
		// 設定リスト
		$row["setting_list"] = ((!empty($_POST["SETTING_LIST"]) && is_array($_POST["SETTING_LIST"])) ? $_POST["SETTING_LIST"] : array());
		
	}

	// 実機紐づき状態チェック
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "dat_machine" )
		->where()
			->and( "model_no = ", $row["model_no"], FD_NUM)
			->and( "del_flg = ", "0", FD_NUM)
	->createSql();
	$cntM = (mb_strlen($row["model_no"]) > 0) ? $template->DB->getOne($sql) : 0;
	// 実機が紐づいている場合はカテゴリ変更不可
	$addOpt = ($cntM > 0) ? " disabled" : "";

	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG"       , mb_strlen($message) > 0);
	$template->if_enable("UPD"          , mb_strlen($row["model_no"]) > 0);
	$template->if_enable("DEL"          , $cntM == 0);
	$template->if_enable("EDIT_IMG"     , $row["image_list"] != "");
	// 2020/12/12 [UPD Start] パチも使用に変更、リールでもなくなったのだが
	//$template->if_enable("EDIT_IMG_SLOT", $row["category"] == "2" && $row["image_reel"] != "");
	$template->if_enable("EDIT_IMG_REEL", $row["image_reel"] != "");
	// 2020/12/12 [UPD End] パチも使用に変更、リールでもなくなったのだが
	$template->if_enable("USE_ADD_SPECIAL", isset($GLOBALS["AddSettingBySpecial"]) && count($GLOBALS["AddSettingBySpecial"]) > 0);
	$template->if_enable("TRACK_BONUS_BREAKDOWN", TRACK_BONUS_BREAKDOWN);	// ボーナス内訳記録
	
	$aryExt = explode("/", str_replace(" ", "", UPFILE_IMG_EXT));
	$template->assign("IMG_EXT", "." . implode(",.", $aryExt));
	$template->assign("UPFILE_IMG_EXT"  , UPFILE_IMG_EXT, true);
	$template->assign("UPFILE_IMG_MAX"  , UPFILE_IMG_MAX, true);
	$template->assign("UPFILE_IMG_MAXBYTE" , (UPFILE_IMG_MAX * 1024 * 1024), true);
	$template->assign("PUSHORDER_IMG_MAX"  , PUSHORDER_IMG_MAX, true);
	$template->assign("PUSHORDER_IMG_MAXBYTE" , (PUSHORDER_IMG_MAX * 1024), true);
	$imgType = array_column($GLOBALS["ImgExtension"], "mine");
	$template->assign("UPFILE_IMG_TYPE" , "'" . implode("','", $imgType) . "'");
	
	$template->assign("NO"              , $row["model_no"], true);
	$template->assign("OLD_CATEGORY"    , $row["old_category"], true);
	$template->assign("RDO_CATEGORY"    , makeRadioArray($GLOBALS["categoryList"], "CATEGORY", $row["category"], $addOpt));
	$template->assign("MODEL_NO"        , $row["model_no"], true);
	$template->assign("MODEL_CD"        , $row["model_cd"], true);
	$template->assign("MODEL_NAME"      , $row["model_name"], true);
	$template->assign("MODEL_ROMAN"     , $row["model_roman"], true);
	$template->assign("SEL_UNIT"        , makeOptionArray($template->DB->getUnitList(), $row["unit_no"], true, ""));
	$template->assign("SEL_MAKER"       , makeOptionArrayAddClass($template->DB->getMakerList(1, true), $row["maker_no"], true));
	$template->assign("SEL_TYPE"        , makeOptionArrayAddClass($template->DB->getTypeList(true), $row["type_no"],  false));
	
	$template->assign("IMAGE_LIST"      , $row["image_list"], true);
	$template->assign("IMAGE_DETAIL"    , $row["image_detail"], true);
	$template->assign("IMAGE_REEL"      , $row["image_reel"], true);
	$template->assign("LAYOUT_DATA"     , $row["layout_data"], true);
	$template->assign("REMARKS"         , $row["remarks"], true);
	$template->assign("RENCHAN_GAMES"   , $row["renchan_games"], true);
	$template->assign("TENJO_GAMES"     , $row["tenjo_games"], true);

	// 基盤設定
	$attr = [];		// 表示制御用追加属性
	foreach ($GLOBALS["boardTypeList"] as $key => $value) {
		$categoryKeys = [];
		foreach ($GLOBALS["CategoryBoardAvailability"] as $cate => $useBoard) {
			if (in_array($key, $useBoard)) $categoryKeys[] = $cate;
		}
		if (empty($categoryKeys)) continue;
		if (count($categoryKeys) > 1) {
			// パチ・スロどちらも使用可
			$attr[$key] = "data-type=\"\"";
		} else {
			if (in_array("1", $categoryKeys)) $attr[$key] = "data-type=\"pachi\"";		// パチのみ
			if (in_array("2", $categoryKeys)) $attr[$key] = "data-type=\"slot\"";		// スロのみ
		}
		// 追加設定判定
		if (isset($GLOBALS["AddSettingByBoard"][$key]) && $GLOBALS["AddSettingByBoard"][$key] === true) {
			$attr[$key] .= " data-add=\"1\"";
			// 特殊追加設定
			if (isset($GLOBALS["AddSettingBySpecial"]) && count($GLOBALS["AddSettingBySpecial"]) > 0) {
				$special = array();
				foreach ($GLOBALS["AddSettingBySpecial"] as $item => $set) {
					$special[] = $item . "-" . (in_array($key, $set) ? "1" : "0");
				}
				$attr[$key] .= " data-spec=\"" . implode(",", $special) . "\"";
			}
		} else {
			$attr[$key] .= " data-add=\"0\"";
		}
	}
	$template->assign("SEL_BOARD"       , makeOptionArray($GLOBALS["boardTypeList"], $row["board_ver"], false, "", false, "", true, $attr));
	$template->assign("LIMITCREDIT"     , $row["limitcredit"], true);
	$template->assign("LIMITTIME"       , $row["limittime"], true);

	// 設定リスト
	$template->assign("CHK_SETTING_LIST", makeCheckBoxArray($arySettingList, "SETTING_LIST[]", $row["setting_list"], 0, "", "&nbsp;&nbsp;", "", true));

	// PUSHオーダー
	$template->loop_start("PUSHORDERLIST");
	for( $i=1; $i<=PUSH_ORDER_COUNT; $i++){
		$template->assign("PUSH_NO"     , $i);
		$template->assign("PUSH_LABEL"  , $row["image_label".trim($i)], true);
		$template->assign("IMG_PUSH"    , $row["image_push".trim($i)], true);
		$template->if_enable("EDIT_IMG_PUSH", $row["image_push".trim($i)] != "");
		$template->loop_next();
	}
	$template->loop_end("PUSHORDERLIST");
	
	// 賞球設定データ
	$template->assign("PRIZEBALL_DATA_MAX"       , $row["prizeball_data_max"], true);
	$template->assign("PRIZEBALL_DATA_MAX_RATE"  , $row["prizeball_data_max_rate"], true);
	$template->assign("PRIZEBALL_DATA_NAVEL"     , $row["prizeball_data_navel"], true);
	$template->assign("PRIZEBALL_DATA_TULIP"     , $row["prizeball_data_tulip"], true);
	$template->assign("PRIZEBALL_DATA_ATTACKER1" , $row["prizeball_data_attacker1"], true);
	$template->assign("PRIZEBALL_DATA_ATTACKER2" , $row["prizeball_data_attacker2"], true);
	// 2020/12/12 [UPD Start] パチ対応
	//$template->assign("PRIZEBALL_DATA_EXTEND"    , $row["prizeball_data_extend"], true);
	$template->assign("RDO_PRIZEBALL_DATA_V_PRIZE"     , makeRadioArray($GLOBALS["thereIsStatus"], "PRIZEBALL_DATA_V_PRIZE", $row["prizeball_data_v_prize"]));
	$template->assign("RDO_PRIZEBALL_DATA_AUTOCHANCE"  , makeRadioArray($GLOBALS["thereIsStatus"], "PRIZEBALL_DATA_AUTOCHANCE", $row["prizeball_data_autochance"]));
	$template->assign("PRIZEBALL_DATA_TULIP_COUNT"     , $row["prizeball_data_tulip_count"], true);
	$template->assign("RDO_PRIZEBALL_DATA_ATTACKER2NOT", makeRadioArray($GLOBALS["thereIsStatus"], "PRIZEBALL_DATA_ATTACKER2NOT", $row["prizeball_data_attacker2not"]));
	// 2020/12/12 [UPD End] パチ対応
	// レイアウトデータ
	$template->loop_start("LAYOUTLIST");
	foreach ( $layout_data_list as $key => $value){
		$template->assign("LAYOUT_NAME"      , $key, true);
		$template->assign("LAYOUT_CHECK"     , ($value==1)? "checked":"", true);
		$template->assign("LAYOUT_LABEL"     , $GLOBALS["LayoutNameList"][$key], true);
		$template->loop_next();
	}
	$template->loop_end("LAYOUTLIST");
	
	$template->assign("PUSH_ORDER_COUNT", PUSH_ORDER_COUNT, true);
	
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
	
	// データ取得
	getData($_GET , array("ACT", "NO"));
	getData($_POST , array("OLD_CATEGORY", "CATEGORY", "MODEL_NO", "MODEL_NAME", "MODEL_ROMAN", "TYPE_NO", "UNIT_NO", "MAKER_NO"
							,"PRIZEBALL_DATA_MAX" ,"PRIZEBALL_DATA_MAX_RATE", "PRIZEBALL_DATA_NAVEL", "PRIZEBALL_DATA_TULIP", "PRIZEBALL_DATA_ATTACKER1", "PRIZEBALL_DATA_ATTACKER2"
							,"PRIZEBALL_DATA_V_PRIZE", "PRIZEBALL_DATA_AUTOCHANCE", "PRIZEBALL_DATA_TULIP_COUNT", "PRIZEBALL_DATA_ATTACKER2NOT"		// 2020/12/12 [ADD] パチ対応
							,"IMAGE_LIST", "IMAGE_DETAIL", "IMAGE_REEL", "REMARKS", "BOARD_VER", "LIMITCREDIT", "LIMITTIME", "LAYOUT_DATA"
							, "MODEL_CD", "RENCHAN_GAMES", "TENJO_GAMES"
							));
	// レイアウトデータ取得
	$layoutkeys = array_keys($GLOBALS["LayoutNameList"]);
	getData($_POST , $layoutkeys);
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	$mode = "";
	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		
		// 実機が紐づいている場合は削除不可(入力チェック内で存在チェックしている)
		
		// 先に画像を削除
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->select()
				->field("image_list, image_detail, image_reel, layout_data")
				->from("mst_model")
				->where()
					->and(false, "model_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$delimage = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
		if ($delimage != null) {
			// リスト画像
			if (file_exists(DIR_IMG_MODEL . $delimage["image_list"])) {
				chmod(DIR_IMG_MODEL . $delimage["image_list"], 0755);
				unlink(DIR_IMG_MODEL . $delimage["image_list"]);
			}
			// 詳細画像
			if (file_exists(DIR_IMG_MODEL . $delimage["image_detail"])) {
				chmod(DIR_IMG_MODEL . $delimage["image_detail"], 0755);
				unlink(DIR_IMG_MODEL . $delimage["image_detail"]);
			}
			// リール画像
			if (file_exists(DIR_IMG_MODEL . $delimage["image_reel"])) {
				chmod(DIR_IMG_MODEL . $delimage["image_reel"], 0755);
				unlink(DIR_IMG_MODEL . $delimage["image_reel"]);
			}
			// プッシュオーダー
			if (mb_strlen($delimage["layout_data"]) > 0) {
				$layout_data_json = json_decode($delimage["layout_data"], true);
				if (isset($layout_data_json["bonus_push"])) {
					foreach ($layout_data_json["bonus_push"] as $key => $img) {
						if ($key == 0) continue;	// デフォ画像は削除しない
						if (isset($img["path"]) && file_exists(DIR_IMG_MODEL . $img["path"])) {
							chmod(DIR_IMG_MODEL . $img["path"], 0755);
							unlink(DIR_IMG_MODEL . $img["path"]);
						}
					}
				}
			}
		}
		
		$sql = (new SqlString())->setAutoConvert([$template->DB,"conv_sql"])
			->update( "mst_model" )
				->set()
					->value( "del_flg"  , 1, FD_NUM)
					->value( "del_no"   , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"   , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "model_no =" , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);

	}else{
		
		// 賞球データ
		if( $_POST["CATEGORY"] == "1"){
			/* 2020/12/12 [DEL Start] パチ対応
			if( $_POST["PRIZEBALL_DATA_EXTEND"] != ""){
				if( !json_decode( $_POST["PRIZEBALL_DATA_EXTEND"], true)){
					DispDetail($template, $template->message("A1435"));
					return;
				}
			}
			2020/12/12 [DEL End] パチ対応 */
			$_POST["PRIZEBALL_DATA"] = createPrizeballDataJsonString( $_POST);
		}else{
			$_POST["PRIZEBALL_DATA"] = "";
		}
		
		// レイアウトデータ部分生成
		// changePanel 処理
		// 2020/12/12 [UPD Start] パチ対応
		$_json_layout_hides = array();
		$_json_bonus_push = array();
		if( $_POST["CATEGORY"] == "2"){
			foreach ( $layoutkeys as $value){
				if( $_POST[ $value] == 1 ) array_push( $_json_layout_hides, $value);
			}
			$allHide = true;
			foreach( $GLOBALS["layout_hideList"] as $value){
				if( $_POST[ $value] == 0 ) $allHide = false;
			}
			if( $allHide ) array_unshift( $_json_layout_hides, "changePanel");
			// 基盤データ基礎
			$_json_board_base  = [
				'{"label":"select", "path":"noselect_bonus.png"}'
			];
			$_json_bonus_push = [0 => ["label" => "select", "path" => "noselect_bonus.png"]];
		}
		// レイアウトデータ基礎部分生成
		$_layout_base = [
			"video_portrait" => 0,
			"video_mode" => 4,
			"drum" => 0,
			"bonus_push" => $_json_bonus_push,
			"version" => 1,
			"hide" => $_json_layout_hides
		];
		// 基盤別設定 ※パチは1固定
		$boardVer = (($_POST["CATEGORY"] == "2") ? (int)$_POST["BOARD_VER"] : 1);
		foreach( $GLOBALS["boardVersionData"][$boardVer] as $key => $value){
			$_layout_base[ $key] = $value;
		}
		// 2020/12/12 [UPD End] パチ対応
		
		// 画像処理
		$upfile = [];
		// リスト画像処理
		$upfile["list"] = "";
		if (isset($_FILES['IMAGE_LIST_NEW']['tmp_name']) && !empty($_FILES['IMAGE_LIST_NEW']['tmp_name'])) {
			try {
				if (!isset($_FILES['IMAGE_LIST_NEW']['error']) || !is_int($_FILES['IMAGE_LIST_NEW']['error'])) {
					throw new RuntimeException($template->message("A1411"));
				}
				
				switch ($_FILES['IMAGE_LIST_NEW']['error']) {
					case UPLOAD_ERR_OK: // OK
						break;
					case UPLOAD_ERR_NO_FILE:   // ファイル未選択
						if (mb_strlen($_POST["MODEL_NO"]) == 0) {
							throw new RuntimeException($template->message("A1410"));
						}
						break;
					case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
					// case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過(容量違いの画像が複数あるのでformには設定していない)
						throw new RuntimeException($template->message("A1444"));
					default:
						throw new RuntimeException($template->message("A1411"));
				}
				
				// ファイルサイズチェック
				if ($_FILES['IMAGE_LIST_NEW']['size'] > (UPFILE_IMG_MAX * 1024 * 1024)) {
					throw new RuntimeException($template->message("A1444"));
				}
				
				// MIMEタイプチェック(拡張子)
				$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
				if (!$ext = array_search(mime_content_type($_FILES['IMAGE_LIST_NEW']['tmp_name']), $chkMime, true)) {
					throw new RuntimeException($template->message("A1443"));
				}
				
				// 保存
				$upfile["list"] = sha1(mt_rand() . time());
				if (move_uploaded_file($_FILES['IMAGE_LIST_NEW']['tmp_name'], sprintf(DIR_IMG_MODEL . '%s.%s', $upfile["list"], $ext))) {
					$upfile["list"] = $upfile["list"] . "." . $ext;
					if (mb_strlen($_POST["IMAGE_LIST"]) > 0) {
						if (file_exists(DIR_IMG_MODEL . $_POST["IMAGE_LIST"])) {
							chmod(DIR_IMG_MODEL . $_POST["IMAGE_LIST"], 0755);
							unlink(DIR_IMG_MODEL . $_POST["IMAGE_LIST"]);
						}
					}
				} else {
					$upfile["list"] = "";
					throw new RuntimeException($template->message("A1411"));
				}

			} catch (RuntimeException $e) {
				DispDetail($template, $e->getMessage());
				return;
			}
		}
		// 詳細画像処理
		$upfile["detail"] = "";
		if (isset($_FILES['IMAGE_DETAIL_NEW']['tmp_name']) && !empty($_FILES['IMAGE_DETAIL_NEW']['tmp_name'])) {
			try {
				if (!isset($_FILES['IMAGE_DETAIL_NEW']['error']) || !is_int($_FILES['IMAGE_DETAIL_NEW']['error'])) {
					throw new RuntimeException($template->message("A1413"));
				}
				
				switch ($_FILES['IMAGE_DETAIL_NEW']['error']) {
					case UPLOAD_ERR_OK: // OK
						break;
					case UPLOAD_ERR_NO_FILE:   // ファイル未選択
						if (mb_strlen($_POST["MODEL_NO"]) == 0) {
							throw new RuntimeException($template->message("A1412"));
						}
						break;
					case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
					// case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過(容量違いの画像が複数あるのでformには設定していない)
						throw new RuntimeException($template->message("A1446"));
					default:
						throw new RuntimeException($template->message("A1413"));
				}
				
				// ファイルサイズチェック
				if ($_FILES['IMAGE_DETAIL_NEW']['size'] > (UPFILE_IMG_MAX * 1024 * 1024)) {
					throw new RuntimeException($template->message("A1446"));
				}
				
				// MIMEタイプチェック(拡張子)
				$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
				if (!$ext = array_search(mime_content_type($_FILES['IMAGE_DETAIL_NEW']['tmp_name']), $chkMime, true)) {
					throw new RuntimeException($template->message("A1445"));
				}
				
				// 保存
				$upfile["detail"] = sha1(mt_rand() . time());
				if (move_uploaded_file($_FILES['IMAGE_DETAIL_NEW']['tmp_name'], sprintf(DIR_IMG_MODEL . '%s.%s', $upfile["detail"], $ext))) {
					$upfile["detail"] = $upfile["detail"] . "." . $ext;
					if (mb_strlen($_POST["IMAGE_DETAIL"]) > 0) {
						if (file_exists(DIR_IMG_MODEL . $_POST["IMAGE_DETAIL"])) {
							chmod(DIR_IMG_MODEL . $_POST["IMAGE_DETAIL"], 0755);
							unlink(DIR_IMG_MODEL . $_POST["IMAGE_DETAIL"]);
						}
					}
				} else {
					$upfile["detail"] = "";
					throw new RuntimeException($template->message("A1413"));
				}

			} catch (RuntimeException $e) {
				DispDetail($template, $e->getMessage());
				return;
			}
		}
		// リール画像処理
		$upfile["reel"] = "";
		// 2020/12/12 [UPD Start] パチも使用に変更、リールでもなくなったのだが
		// スロのみ
		//if ($_POST["CATEGORY"] == "2") {
		if (isset($_FILES['IMAGE_REEL_NEW']['tmp_name']) && !empty($_FILES['IMAGE_REEL_NEW']['tmp_name'])) {
			try {
				if (!isset($_FILES['IMAGE_REEL_NEW']['error']) || !is_int($_FILES['IMAGE_REEL_NEW']['error'])) {
					throw new RuntimeException($template->message("A1415"));
				}
				
				switch ($_FILES['IMAGE_REEL_NEW']['error']) {
					case UPLOAD_ERR_OK: // OK
						break;
					case UPLOAD_ERR_NO_FILE:   // ファイル未選択
						if (mb_strlen($_POST["MODEL_NO"]) == 0) {
							throw new RuntimeException($template->message("A1414"));
						}
						break;
					case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
					// case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過(容量違いの画像が複数あるのでformには設定していない)
						throw new RuntimeException($template->message("A1448"));
					default:
						throw new RuntimeException($template->message("A1415"));
				}
				
				// ファイルサイズチェック
				if ($_FILES['IMAGE_REEL_NEW']['size'] > (UPFILE_IMG_MAX * 1024 * 1024)) {
					throw new RuntimeException($template->message("A1448"));
				}
				
				// MIMEタイプチェック(拡張子)
				$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
				if (!$ext = array_search(mime_content_type($_FILES['IMAGE_REEL_NEW']['tmp_name']), $chkMime, true)) {
					throw new RuntimeException($template->message("A1447"));
				}
				
				// 保存
				$upfile["reel"] = sha1(mt_rand() . time());
				if (move_uploaded_file($_FILES['IMAGE_REEL_NEW']['tmp_name'], sprintf(DIR_IMG_MODEL . '%s.%s', $upfile["reel"], $ext))) {
					$upfile["reel"] = $upfile["reel"] . "." . $ext;
					if (mb_strlen($_POST["IMAGE_REEL"]) > 0) {
						if (file_exists(DIR_IMG_MODEL . $_POST["IMAGE_REEL"])) {
							chmod(DIR_IMG_MODEL . $_POST["IMAGE_REEL"], 0755);
							unlink(DIR_IMG_MODEL . $_POST["IMAGE_REEL"]);
						}
					}
				} else {
					$upfile["reel"] = "";
					throw new RuntimeException($template->message("A1415"));
				}

			} catch (RuntimeException $e) {
				DispDetail($template, $e->getMessage());
				return;
			}
		}
		/*
		} else {
			if ($_POST["OLD_CATEGORY"] == "2") {
				// スロからパチになった場合はリール画像を削除する
				if (file_exists(DIR_IMG_MODEL . $_POST["IMAGE_REEL"])) {
					chmod(DIR_IMG_MODEL . $_POST["IMAGE_REEL"], 0755);
					unlink(DIR_IMG_MODEL . $_POST["IMAGE_REEL"]);
				}
			}
		}
		*/
		// 2020/12/12 [UPD End] パチも使用に変更、リールでもなくなったのだが

		// 追加設定
		$layout_data_json = (empty($_POST["LAYOUT_DATA"])) ? array() : json_decode($_POST["LAYOUT_DATA"], true);
		if (isset($GLOBALS["AddSettingByBoard"][(int)$_POST["BOARD_VER"]]) && $GLOBALS["AddSettingByBoard"][(int)$_POST["BOARD_VER"]] === true) {
			// プッシュオーダー画像処理
			foreach ($_POST["PUSH_LABEL"] as $key => $label) {
				$upfile["pushorder"] = "";
				if (isset($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key]) && !empty($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key])) {
					try {
						if (!isset($_FILES['IMAGE_PUSH_NEW']['error'][$key]) || !is_int($_FILES['IMAGE_PUSH_NEW']['error'][$key])) {
							throw new RuntimeException(str_replace('%i%', $key, $template->message("A1442")));
						}
						
						switch ($_FILES['IMAGE_PUSH_NEW']['error'][$key]) {
							case UPLOAD_ERR_OK: // OK
								break;
							case UPLOAD_ERR_NO_FILE:   // ファイル未選択
								break;
							case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
							// case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過(容量違いの画像が複数あるのでformには設定していない)
								throw new RuntimeException($template->message("A1450"));
							default:
								throw new RuntimeException(str_replace('%i%', $key, $template->message("A1442")));
						}
						
						// ファイルサイズチェック
						if ($_FILES['IMAGE_PUSH_NEW']['size'][$key] > (PUSHORDER_IMG_MAX * 1024)) {
							throw new RuntimeException($template->message("A1450"));
						}
						
						// MIMEタイプチェック(拡張子)
						$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
						if (!$ext = array_search(mime_content_type($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key]), $chkMime, true)) {
							throw new RuntimeException($template->message("A1449"));
						}
						
						// 保存
						$upfile["pushorder"] = sha1(mt_rand() . time());
						if (move_uploaded_file($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key], sprintf(DIR_IMG_MODEL . '%s.%s', $upfile["pushorder"], $ext))) {
							$upfile["pushorder"] = $upfile["pushorder"] . "." . $ext;
							if (mb_strlen($_POST["IMAGE_PUSH"][$key]) > 0) {
								if (file_exists(DIR_IMG_MODEL . $_POST["IMAGE_PUSH"][$key])) {
									chmod(DIR_IMG_MODEL . $_POST["IMAGE_PUSH"][$key], 0755);
									unlink(DIR_IMG_MODEL . $_POST["IMAGE_PUSH"][$key]);
								}
							}
						} else {
							$upfile["pushorder"] = "";
							throw new RuntimeException(str_replace('%i%', $key, $template->message("A1442")));
						}

					} catch (RuntimeException $e) {
						DispDetail($template, $e->getMessage());
						return;
					}
				}
				if (mb_strlen($upfile["pushorder"]) > 0) {
					$_layout_base["bonus_push"][] = array("label" => $label, "path" => $upfile["pushorder"]);
				} else {
					if (mb_strlen($_POST["IMAGE_PUSH"][$key]) > 0 && file_exists(DIR_IMG_MODEL . $_POST["IMAGE_PUSH"][$key])) {
						$_layout_base["bonus_push"][] = array("label" => $label, "path" => $_POST["IMAGE_PUSH"][$key]);
					}
				}
			}
			// その他項目
			$_layout_base["version"] = $_POST["BOARD_VER"];
			if (mb_strlen( $_POST["LIMITCREDIT"]) > 0) {
				$_layout_base["limitcredit"] = $_POST["LIMITCREDIT"];
			}
			if (mb_strlen( $_POST["LIMITTIME"]) > 0) {
				$_layout_base["limittime"] = $_POST["LIMITTIME"];
			}

			// クリアされた画像の削除
			// 新たに登録するjsonの情報にpathが無い場合はクリアしたとみなし、削除する
			if (isset($layout_data_json["bonus_push"])) {
				$newimg = array_column($_layout_base["bonus_push"], "path");
				foreach ($layout_data_json["bonus_push"] as $oldimg) {
					if (!in_array($oldimg["path"], $newimg) && file_exists(DIR_IMG_MODEL . $oldimg["path"])) {
						chmod(DIR_IMG_MODEL . $oldimg["path"], 0755);
						unlink(DIR_IMG_MODEL . $oldimg["path"]);
					}
				}
			}
		} else {
			// 画像データがある場合は削除する
			if (isset($layout_data_json["bonus_push"])) {
				foreach ($layout_data_json["bonus_push"] as $key => $img) {
					if ($key == 0) continue;	// デフォ画像は削除しない
					if (isset($img["path"]) && file_exists(DIR_IMG_MODEL . $img["path"])) {
						chmod(DIR_IMG_MODEL . $img["path"], 0755);
						unlink(DIR_IMG_MODEL . $img["path"]);
					}
				}
			}
		}
		// 設定リスト
		$settingList = "";
		if (isset($GLOBALS["AddSettingBySpecial"]["settinglist"]) && in_array((int)$_POST["BOARD_VER"], $GLOBALS["AddSettingBySpecial"]["settinglist"])) {
			$settingList = implode(",",  $_POST["SETTING_LIST"]);
		}

		$layout = json_encode($_layout_base);	// 2020/12/12 [ADD]
		if (mb_strlen($_POST["MODEL_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_model" )
					->set()
						->value( "category"        , $_POST["CATEGORY"], FD_NUM)
						->value( "model_cd"        , $_POST["MODEL_CD"], FD_STR)
						->value( "model_name"      , $_POST["MODEL_NAME"], FD_STR)
						->value( "model_roman"     , $_POST["MODEL_ROMAN"], FD_STR)
						->value( "type_no"         , $_POST["TYPE_NO"], FD_NUM)
						->value( "unit_no"         , ($_POST["CATEGORY"]==1)? "":$_POST["UNIT_NO"], FD_NUM)
						->value(true, "renchan_games"   , $_POST["RENCHAN_GAMES"], FD_NUM)
						->value(true, "tenjo_games"     , $_POST["TENJO_GAMES"], FD_NUM)
						->value( "setting_list"    , $settingList, FD_STR)
						->value( "maker_no"        , $_POST["MAKER_NO"], FD_NUM)
						->value(true, "image_list"   , (isset($upfile["list"])) ? $upfile["list"] : "", FD_STR)
						->value(true, "image_detail" , (isset($upfile["detail"])) ? $upfile["detail"] : "", FD_STR)
						->value(true, "image_reel"   , (isset($upfile["reel"])) ? $upfile["reel"] : "", FD_STR)
						->value( "prizeball_data"  , $_POST["PRIZEBALL_DATA"], FD_STR)
						// 2020/12/12 [UPD Start]
						//->value( "layout_data"     , json_encode( $_layout_base), FD_STR)
						->value( "layout_data"     , $layout, FD_STR)
						// 2020/12/12 [UPD End]
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
					->where()
						->and( false, "model_no =" , $_POST["MODEL_NO"], FD_NUM)
				->createSQL();
			$template->DB->query($sql);
		}else{
			// 新規
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "mst_model" )
						->value( "category"        , $_POST["CATEGORY"], FD_NUM)
						->value( "model_cd"        , $_POST["MODEL_CD"], FD_STR)
						->value( "model_name"      , $_POST["MODEL_NAME"], FD_STR)
						->value( "model_roman"     , $_POST["MODEL_ROMAN"], FD_STR)
						->value( "type_no"         , $_POST["TYPE_NO"], FD_NUM)
						->value( "unit_no"         , $_POST["UNIT_NO"], FD_NUM)
						->value(true, "renchan_games"   , $_POST["RENCHAN_GAMES"], FD_NUM)
						->value(true, "tenjo_games"     , $_POST["TENJO_GAMES"], FD_NUM)
						->value( "setting_list"    , $settingList, FD_STR)
						->value( "maker_no"        , $_POST["MAKER_NO"], FD_NUM)
						->value(true, "image_list"   , (isset($upfile["list"])) ? $upfile["list"] : "", FD_STR)
						->value(true, "image_detail" , (isset($upfile["detail"])) ? $upfile["detail"] : "", FD_STR)
						->value(true, "image_reel"   , (isset($upfile["reel"])) ? $upfile["reel"] : "", FD_STR)
						->value( "prizeball_data"  , $_POST["PRIZEBALL_DATA"], FD_STR)
						// 2020/12/12 [UPD Start]
						//->value( "layout_data"     , json_encode( $_layout_base), FD_STR)
						->value( "layout_data"     , $layout, FD_STR)
						// 2020/12/12 [UPD End]
						->value( "remarks"         , $_POST["REMARKS"], FD_STR)
						->value( "upd_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "upd_dt"          , "current_timestamp", FD_FUNCTION)
						->value( "add_no"          , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value( "add_dt"          , "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->query($sql);
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
		case "update":
			// 更新
			$title = $template->message("A1462");
			$msg = $template->message("A1463");
			break;
		case "del":
			// 削除
			$title = $template->message("A1464");
			$msg = $template->message("A1465");
			break;
		default:
			// 新規登録
			$title = $template->message("A1460");
			$msg = $template->message("A1461");
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}


/**
 * 賞球設定データJSON作成
 * @access	private
 * @param	arrayt	$_post			連想配列
 * @return	string					JSON形式の文字列
 */
function createPrizeballDataJsonString( $_post) {
	// 2020/12/12 [UPD Start] パチ対応
	/*
	$ret = '{';
	$ret .=  '"MAX":'      .$_post["PRIZEBALL_DATA_MAX"];
	$ret .= ',"MAX_RATE":' .$_post["PRIZEBALL_DATA_MAX_RATE"];
	$ret .= ',"NAVEL":'    .$_post["PRIZEBALL_DATA_NAVEL"];
	$ret .= ',"TULIP":'    .$_post["PRIZEBALL_DATA_TULIP"];
	$ret .= ',"ATTACKER1":'.$_post["PRIZEBALL_DATA_ATTACKER1"];
	$ret .= ',"ATTACKER2":'.$_post["PRIZEBALL_DATA_ATTACKER2"];
	$ret .= ($_post["PRIZEBALL_DATA_EXTEND"]!="")? ',"EXTEND":'.$_post["PRIZEBALL_DATA_EXTEND"]:'';
	$ret .= ',"V_PRIZE":'    . $_post["RDO_PRIZEBALL_DATA_V_PRIZE"];
	$ret .= ',"AUTOCHANCE":' . $_post["RDO_PRIZEBALL_DATA_AUTOCHANCE"];
	$ret .= '}';
	return $ret;
	*/
	$ret = ["MAX"          => (int)$_post["PRIZEBALL_DATA_MAX"]
			, "MAX_RATE"   => (int)$_post["PRIZEBALL_DATA_MAX_RATE"]
			, "NAVEL"      => (int)$_post["PRIZEBALL_DATA_NAVEL"]
			, "TULIP"      => (int)$_post["PRIZEBALL_DATA_TULIP"]
			, "ATTACKER1"  => (int)$_post["PRIZEBALL_DATA_ATTACKER1"]
			, "ATTACKER2"  => (int)$_post["PRIZEBALL_DATA_ATTACKER2"]
			, "V_PRIZE"    => (int)$_post["PRIZEBALL_DATA_V_PRIZE"]
			, "AUTOCHANCE" => (int)$_post["PRIZEBALL_DATA_AUTOCHANCE"]
			];
	$extAry = array();
	if (mb_strlen($_post["PRIZEBALL_DATA_TULIP_COUNT"]) > 0) $extAry["TULIP_COUNT"] = (int)$_post["PRIZEBALL_DATA_TULIP_COUNT"];
	if (mb_strlen($_post["PRIZEBALL_DATA_ATTACKER2NOT"]) > 0) $extAry["ATTACKER2NOT"] = (int)$_post["PRIZEBALL_DATA_ATTACKER2NOT"];
	if (count($extAry) > 0) $ret["EXTEND"] = $extAry;
	return json_encode($ret);
	// 2020/12/12 [UPD End] パチ対応
}

/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	$errMessage = array();
	
	// 実機存在
	$sqlExtMachine = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field( "count(*)" )
		->from( "dat_machine" )
		->where()
			->and( "model_no = ", $_GET["NO"], FD_NUM)
			->and( "del_flg = ", "0", FD_NUM)
	->createSql();
	
	if ($_GET["ACT"] != "del") {
		// イメージチェック用
		$_chk_img1 = ($_FILES['IMAGE_LIST_NEW']['tmp_name'] != "") ? $_FILES['IMAGE_LIST_NEW']['tmp_name'] : $_POST["IMAGE_LIST"];
		$_chk_img2 = ($_FILES['IMAGE_DETAIL_NEW']['tmp_name'] != "") ? $_FILES['IMAGE_DETAIL_NEW']['tmp_name'] : $_POST["IMAGE_DETAIL"];
		$_chk_img3 = ($_FILES['IMAGE_REEL_NEW']['tmp_name'] != "") ? $_FILES['IMAGE_REEL_NEW']['tmp_name'] : $_POST["IMAGE_REEL"];
		
		// 機種名重複
		$sqlNameDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_model" )
			->where()
				->and( "model_name = ", $_POST["MODEL_NAME"], FD_STR)
				->and( "del_flg = ", "0", FD_NUM)
				->and( true, "model_no <> ", $_POST["MODEL_NO"], FD_NUM)
		->createSql();

		// 機種名(ローマ字)重複
		$sqlRomanDupli = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from( "mst_model" )
			->where()
				->and( "model_roman = ", $_POST["MODEL_ROMAN"], FD_STR)
				->and( "del_flg = ", "0", FD_NUM)
				->and( true, "model_no <> ", $_POST["MODEL_NO"], FD_NUM)
		->createSql();

		// 実機数
		$extM = (mb_strlen($_POST["MODEL_NO"]) > 0) ? $template->DB->getOne($sqlExtMachine) : 0;

		$errMessage = (new SmartAutoCheck($template))
			// カテゴリ
			// 実機が紐づいている場合は変更不可
			->item($_POST["CATEGORY"])
				->case(mb_strlen($_POST["MODEL_NO"]) > 0 && $extM > 0)
					->if("A1457", $_POST["OLD_CATEGORY"] == $_POST["CATEGORY"])
			//機種CD
			->item($_POST["MODEL_CD"])
				->required("A1420")
				->alnum("A1454", 3)		// 半角英数字(-_も可)
				->maxLength("A1455", 20)
			//機種名
			->item($_POST["MODEL_NAME"])
				->required("A1402")
				->maxLength("A1403", 50)
				->countSQL("A1451", $sqlNameDupli)
			//機種名（英語）
			->item($_POST["MODEL_ROMAN"])
				->required("A1404")
				->maxLength("A1405", 200)
				->countSQL("A1456", $sqlRomanDupli)
			//タイプ
			->item($_POST["TYPE_NO"])
				->required("A1406")
				->case( $_POST["CATEGORY"] == 1 )
					->if("A1421", $_POST["TYPE_NO"] < 5)
				->case( $_POST["CATEGORY"] == 2 )
					->if("A1422", $_POST["TYPE_NO"] > 4)
			//号機
			->item($_POST["UNIT_NO"])
				->case( $_POST["CATEGORY"] == 2 )
					->required("A1407")
			// 2021/01 [UPD Start] 必須になって、連荘は0以上になった
			// 連チャンゲーム数
			->item($_POST["RENCHAN_GAMES"])
				->case(TRACK_BONUS_BREAKDOWN)	// ボーナス内訳記録
					->required("A1468")
					->number("A1467")
			// 天井ゲーム数
			->item($_POST["TENJO_GAMES"])
				->case(TRACK_BONUS_BREAKDOWN)	// ボーナス内訳記録
					->required("A1475")
					->number("A1469")
					->if("A1470", (int)$_POST["TENJO_GAMES"] > 0)
			// 2021/01 [UPD End]
			//メーカー
			->item($_POST["MAKER_NO"])
				->required("A1409")
			//リスト画像
			->item($_chk_img1)
				->required("A1410")
			//詳細画像
			->item($_chk_img2)
				->required("A1412")
			//リール画像
			->item($_chk_img3)
				// 2020/12/12 [UPD Start] パチも使用に変更、リールでもなくなったのだが
				//->case( $_POST["CATEGORY"] == 2 )
					->required("A1414")
				// 2020/12/12 [UPD End] パチも使用に変更、リールでもなくなったのだが
			//賞球設定データ
			// 2020/12/12 [UPD Start] パチ対応
			->item($_POST["PRIZEBALL_DATA_MAX"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1423")
					->number('A1424')
			->item($_POST["PRIZEBALL_DATA_MAX_RATE"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1425")
					->number('A1426')
					->if("A1471", (int)$_POST["PRIZEBALL_DATA_MAX_RATE"] >= 0)		// 0以上
					->if("A1471", (int)$_POST["PRIZEBALL_DATA_MAX_RATE"] <= 100)	// 100以下
			->item($_POST["PRIZEBALL_DATA_NAVEL"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1427")
					->numberEx('A1428')
			->item($_POST["PRIZEBALL_DATA_TULIP"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1429")
					->numberEx('A1430')
			->item($_POST["PRIZEBALL_DATA_ATTACKER1"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1431")
					->numberEx('A1432')
			->item($_POST["PRIZEBALL_DATA_ATTACKER2"])
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1433")
					->numberEx('A1434')
			->item($_POST["PRIZEBALL_DATA_V_PRIZE"])	// V入賞モード
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1472")
			->item($_POST["PRIZEBALL_DATA_AUTOCHANCE"])	// 自動チャンスボタン
				->case( $_POST["CATEGORY"] == 1 )
					->required("A1473")
			->item($_POST["PRIZEBALL_DATA_TULIP_COUNT"])	// 電チュー賞球回転数
				->case( $_POST["CATEGORY"] == 1 )
				->any()
					->number("A1474")
			// 2020/12/12 [UPD End] パチ対応
		->report();

		if (empty($errMessage)) {
			// 追加設定チェック
			if (isset($GLOBALS["AddSettingByBoard"][(int)$_POST["BOARD_VER"]]) && $GLOBALS["AddSettingByBoard"][(int)$_POST["BOARD_VER"]] === true) {
				// 設定リスト
				if (isset($GLOBALS["AddSettingBySpecial"]["settinglist"]) && in_array((int)$_POST["BOARD_VER"], $GLOBALS["AddSettingBySpecial"]["settinglist"])) {
					if (empty($_POST["SETTING_LIST"]) || !is_array($_POST["SETTING_LIST"])) $errMessage[] = $template->message("A1466");
				}
				// 強制払出クレジット
				if (mb_strlen($_POST["LIMITCREDIT"]) > 0) {
					if (!chk_numeric($_POST["LIMITCREDIT"], 7)) $errMessage[] = $template->message("A1436");
				}
				// 制限時間
				if (mb_strlen($_POST["LIMITTIME"]) > 0) {
					if (!chk_numeric($_POST["LIMITTIME"], 5)) $errMessage[] = $template->message("A1437");
				}
				// プッシュオーダー画像(ラベル重複、ラベルと画像どちらか入力があれば必須)
				$aryPushImg = array();
				foreach ($_POST["PUSH_LABEL"] as $key => $value) {
					if (mb_strlen($value) > 0) {
						$aryPushImg[] = $value;
						if (empty($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key]) && empty($_POST["IMAGE_PUSH"][$key])) {
							$errMessage[] = str_replace('%i%', $key, $template->message("A1441"));
						}
					} else {
						if ((isset($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key]) && !empty($_FILES['IMAGE_PUSH_NEW']['tmp_name'][$key])) || !empty($_POST["IMAGE_PUSH"][$key])) {
							$errMessage[] = str_replace('%i%', $key, $template->message("A1440"));
						}
					}
				}
				// 重複チェック
				if (count($aryPushImg) > 0) {
					if (max(array_count_values($aryPushImg)) > 1) {
						$errMessage[] = $template->message("A1453");
					}
				}
			}
		}
	} else {
		$errMessage = (new SmartAutoCheck($template))
			->item($_GET["NO"])
			->countSQL("A1452", $sqlExtMachine)		// 実機存在
		->report();
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
