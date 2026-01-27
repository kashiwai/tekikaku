<?php
/*
 * playhistory.php
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
	
	getData($_GET , array("NO", "PLAY_DATE_FROM"));
	// 2023/03 [ADD Start]
	getData($_GET , array("S_CATEGORY", "S_SIGNALING_ID"));
	getData($_GET , array("S_MODEL_NO", "S_MODEL_NAME", "S_MODEL_CD", "S_MAKER_NO", "S_OWNER_NO", "S_CORNER_NO", "RELEASE_DATE_FROM", "RELEASE_DATE_TO", "END_DATE_FROM", "END_DATE_TO", "S_STATUS" ));
	// 2023/03 [ADD End]

	// 前々日を指定可能MAX日とする
	$max_dt = new DateTimeImmutable(GetRefTimeToday());
	$max_dt = $max_dt->modify("-2 days");
	if (mb_strlen($_GET["PLAY_DATE_FROM"]) > 0) {
		$date = new DateTimeImmutable($_GET["PLAY_DATE_FROM"] . " 00:00:00");
		if ($date > $max_dt) $date = $max_dt;
	} else {
		// 初期値は前日から指定日数前
		$date = new DateTimeImmutable(GetRefTimeToday());
		$date = $date->modify("-" . (string)PLAYHISTORY_PERIOD . " days");
	}
	// FROMから指定日数後をTOとする
	$e_date = $date->modify("+" . (string)(PLAYHISTORY_PERIOD - 1) . " days");
	// TOの最大は前日まで
	if ($e_date > $max_dt->modify("+1 days")) $e_date = $max_dt->modify("+1 days");

	// 2023/03 [ADD Start]
	// 各種リスト取得
	$cornerList  = getCornerList( $template);				// コーナーマスタからデータを取得
	$ownerList   = getOwnerList( $template);				// オーナーマスタからデータを取得
	$signalList  = array();
	foreach( $GLOBALS["RTC_Signaling_Servers"] as $key=>$val){
		$signalList[ $key] = $key;
	}
	// コーナー用SQL処理
	$_corner_from = "";
	if( $_GET["S_CORNER_NO"] != "") $_corner_from = "left join dat_machineCorner dmc on dmc.machine_no = dm.machine_no";
	
	// 検索用日付
	$releaseSt = ((mb_strlen($_GET["RELEASE_DATE_FROM"]) > 0) ? GetRefTimeStart($_GET["RELEASE_DATE_FROM"], "Y/m/d") : "");
	$releaseEd = ((mb_strlen($_GET["RELEASE_DATE_TO"]) > 0) ? GetRefTimeEnd($_GET["RELEASE_DATE_TO"], "Y/m/d") : "");
	$endSt = ((mb_strlen($_GET["END_DATE_FROM"]) > 0) ? GetRefTimeStart($_GET["END_DATE_FROM"], "Y/m/d") : "");
	$endEd = ((mb_strlen($_GET["END_DATE_TO"]) > 0) ? GetRefTimeEnd($_GET["END_DATE_TO"], "Y/m/d") : "");

	// 対象実機NO取得
	$nsql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "dm.machine_no" )
			->from("dat_machine dm")
			->from("left join mst_model mo on mo.model_no = dm.model_no" )
			->from("left join mst_owner ow on ow.owner_no = dm.owner_no" )
			->from("left join mst_maker ma on ma.maker_no = mo.maker_no" )
			->from( $_corner_from )
			->where()
				->and(true, "dm.machine_no = ",  $_GET["NO"], FD_NUM)
				->and(true, "dm.model_no = "        , $_GET["S_MODEL_NO"], FD_NUM )
				->and(true, "dm.owner_no = "        , $_GET["S_OWNER_NO"], FD_NUM )
				->and(true, "dm.release_date >= "   , $releaseSt, FD_DATEEX )
				->and(true, "dm.release_date <= "   , $releaseEd, FD_DATEEX )
				->and(true, "dm.end_date >= "       , $endSt, FD_DATEEX )
				->and(true, "dm.end_date <= "       , $endEd, FD_DATEEX )
				->and(true, "dm.machine_status = "  , $_GET["S_STATUS"], FD_STR )
				->and(true, "dm.signaling_id = "    , $_GET["S_SIGNALING_ID"], FD_STR )
				->and(true, "mo.category = "        , $_GET["S_CATEGORY"], FD_NUM )
				->and(true, "mo.model_cd like "     , ["%",$_GET["S_MODEL_CD"],"%"], FD_STR )
				->and(true, "mo.model_name like "   , ["%",$_GET["S_MODEL_NAME"],"%"], FD_STR )
				->and(true, "mo.maker_no = "        , $_GET["S_MAKER_NO"], FD_NUM )
				->and(true, "dmc.corner_no = "      , $_GET["S_CORNER_NO"], FD_NUM )
				->and( "dm.del_flg != "              , "1", FD_NUM )
		->createSql();
	$mNoAry = $template->DB->getCol($nsql);
	// 2023/03 [ADD End]

	$sqls = new SqlString();
	// 2023/03 [UPD Start]
	$sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("play_dt, SUM( total_count) as total_count, SUM( bb_count) as bb_count, SUM( rb_count) as rb_count, SUM( in_credit) as in_credit, SUM( out_credit) as out_credit")
				->from("his_machinePlay")
				->where()
					->and( "play_dt >= ",  $date->format('Y-m-d'), FD_DATE)
					->and(true, "play_dt <= ",  $e_date->format('Y-m-d'), FD_DATE)
					//->and(true, "machine_no = ",  $_GET["NO"], FD_NUM)
				->groupby( "play_dt")
				->orderby( "play_dt desc");
		//->createSql("\n");
	// 空配列はSQL_CUTの対象外のようなので自力で
	if (count($mNoAry) > 0) {
		$sqls->where()
			->and("machine_no IN ",  $mNoAry, FD_NUM);
	} else {
		// 対象実機無し
		$sqls->where()
			->and("1 = 0");
	}
	$sql = $sqls->createSql("\n");
	// 2023/03 [UPD End]
	$row = $template->DB->getAll( $sql, PDO::FETCH_ASSOC);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	// 条件指定用
	$template->assign("S_OPEN"            , "show");
	$template->assign("SEL_MACHINE_NO"    , makeOptionArray( $template->DB->getMachines(), $_GET["NO"]));
	$template->assign("PLAY_DATE_FROM"    , $date->format("Y/m/d"));
	$template->assign("PLAYHISTORY_PERIOD", PLAYHISTORY_PERIOD);
	$template->assign("MAX_DT"            , $max_dt->format("Y/m/d"));
	$template->assign("TO_MAX"            , $max_dt->modify("+1 days")->format("Y/m/d"));

	// 2023/03 [ADD Start]
	$template->assign("RELEASE_DATE_FROM"   , $_GET["RELEASE_DATE_FROM"], true);
	$template->assign("RELEASE_DATE_TO"     , $_GET["RELEASE_DATE_TO"], true);
	$template->assign("END_DATE_FROM"       , $_GET["END_DATE_FROM"], true);
	$template->assign("END_DATE_TO"         , $_GET["END_DATE_TO"], true);
	$template->assign("S_MODEL_CD"          , $_GET["S_MODEL_CD"], true);
	$template->assign("S_MODEL_NAME"        , $_GET["S_MODEL_NAME"], true);
	$template->assign("SEL_OWNER_NO"        , makeOptionArray( $ownerList,  $_GET["S_OWNER_NO"],  true));
	$template->assign("SEL_CORNER_NO"       , makeOptionArray( $cornerList, $_GET["S_CORNER_NO"], true));
	$template->assign("SEL_MAKER"           , makeOptionArray( $template->DB->getMakerList(1), $_GET["S_MAKER_NO"], true));
	$template->assign("SEL_CATEGORY"        , makeOptionArray($GLOBALS["categoryList"], $_GET["S_CATEGORY"],  true));
	$template->assign("SEL_SIGNALING_ID"    , makeOptionArray($signalList,  $_GET["S_SIGNALING_ID"], true));
	$template->assign("SEL_STATUS"          , makeOptionArray( $GLOBALS["machineStatusList"], $_GET["S_STATUS"], true));
	// 2023/03 [ADD End]

	
	// グラフ用データ
	$dates = "";
	$datas = "";
	$tdata = "";
	$cdata = "";
	foreach( array_reverse( $row) as $k=>$v){
		$y = (int)substr( $v["play_dt"], 0, 4);
		$m = (int)substr( $v["play_dt"], 5, 2) - 1;
		$d = (int)substr( $v["play_dt"], 8, 2);
		$t = "new Date(". $y .",". $m .",". $d .")";
		$dates .= ",".$t;
		$datas .= "Click[". $k ."] = [". $t .",". $v["bb_count"] .",". $v["rb_count"] ."];\n";
		$tdata .= "totalClick[". $k ."] = [". $t .",". $v["total_count"] ."];\n";
		$cdata .= "creditClick[". $k ."] = [". $t .",". ((int)$v["in_credit"] - (int)$v["out_credit"]) ."];\n";
	}
	$template->assign("DAYS_COUNT" , count($row));
	$template->assign("DAYS_DATE"  , $dates);
	$template->assign("TOTAL_DATA" , $tdata);
	$template->assign("CREDIT_DATA", $cdata);
	$template->assign("BOUNUS_DATA", $datas);
	
	
	// 表示
	$template->flush();
	
	
}

// 2023/03 [ADD Start]
/**
 * オーナーマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getOwnerList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("owner_no, owner_nickname")
				->orderby("owner_no asc")
				->from("mst_owner")
				->where()
					->and(false, "del_flg != ", "1", FD_NUM)
			->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row['owner_no']] = $row['owner_nickname'];
	}
	unset($rs);
	return $arr;
}

/**
 * コーナーマスタからデータを取得
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	array					連想配列
 */
function getCornerList( $template){
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("corner_no, corner_name, corner_roman")
			->from("mst_corner")
			->where()
				->and(false, "del_flg != ", "1", FD_NUM)
		->createSQL();
	$arr = array();
	$rs = $template->DB->query($sql);
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$arr[ $row["corner_no"]] = $row["corner_name"];
	}
	unset($rs);
	return $arr;
}

// 2023/03 [ADD End]

?>
