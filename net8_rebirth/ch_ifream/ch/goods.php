<?php
/*
 * goods.php
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
 * 商品一覧画面表示
 * 
 * 商品一覧画面の表示を行う
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
			case "regist":			// 登録処理
				ProcData($template);
				break;
			case "comp":			// 完了画面表示
				DispList($template, $template->message("U1799"));
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
	getData($_GET , array("P", "ODR", "VIEW", "TYPE"));
	
	$template->Session->one_time = bin2hex(openssl_random_pseudo_bytes(16));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	if( $_GET["VIEW"] == ""){
		$_GET["VIEW"] = MODEL_LIST_VIEW;
	}else{
		if (array_search( $_GET["VIEW"], $GLOBALS["viewcountList"]) === false) $_GET["VIEW"] = MODEL_LIST_VIEW;
	}
	//オーダーを分解
	$order = array_keys($GLOBALS["goodsOrderTypeItem"]);
	if (mb_strlen($_GET["ODR"]) > 0){
		$order_target = explode(" ", str_replace('+', ' ', $_GET["ODR"] ));
		if ( !array_key_exists( $order_target[0], $GLOBALS["goodsOrderTypeItem"]) || ($order_target[1] != "asc" && $order_target[1] != "desc" )) {
			$order_target = array();
			$order_target[] = $order[0];
			$_GET["ODR"] = $order[0] . " desc";
		}
	}else{
		$order_target = array();
		$order_target[] = $order[0];
		$_GET["ODR"] = $order[0] . " desc";
	}
	//タイプ処理
	$_type_active_list = array("","","");
	if( $_GET["TYPE"] != "" && $_GET["TYPE"] != "IN" && $_GET["TYPE"] != "OUT") $_GET["TYPE"] = "";
	if( $_GET["TYPE"] == "" )    $_type_active_list[0] = "active";
	if( $_GET["TYPE"] == "IN" )  $_type_active_list[1] = "active";
	if( $_GET["TYPE"] == "OUT" ) $_type_active_list[2] = "active";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point")
			->from("mst_member men")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	$_point = $row["draw_point"];
	
	$now = date("Y/m/d H:i:s");
	
	$sqls = new SqlString();
	// カウントSQL
	$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("mst_goods mg")
			->join( "inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->where()
				// 公開日チェック
				->and( false, "mg.release_dt <= ", $now, FD_STR)
				// 抽選後 ENDED_DRAW_LISTVIEW 日以上たったら非表示
				->and( false, "mg.draw_dt + INTERVAL ". ENDED_DRAW_LISTVIEW ." DAY > ", $now, FD_STR)
				->and( false, "mg.del_flg <> ", "1", FD_NUM)
		->createSQL();
	
	if( $_GET["TYPE"] == "IN" ){
		$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->where()
				// 応募期間中
				->and( false, "mg.recept_start_dt <= ", $now, FD_STR)
				->and( false, "mg.recept_end_dt > ", $now, FD_STR)
			->createSQL();
	}else if( $_GET["TYPE"] == "OUT" ){
		$count_sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->where()
				// 抽選終了
				//->and( false, "mg.draw_dt <= ", $now, FD_STR)
				// 応募終了
				//->and( false, "mg.recept_end_dt <= ", $now, FD_STR)
				// 抽選済み
				->and( false, "mg.draw_state = ", 1, FD_NUM)
			->createSQL();
	}
	
	// カウント取得
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / (int)$_GET["VIEW"]);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	// 行SQL
	$row_sql = $sqls
			->resetField()
			->field("mg.goods_no, mg.goods_cd, mg.goods_image, mg.draw_point, mg.release_dt, mg.recept_start_dt, mg.recept_end_dt, mg.draw_dt, mg.win_count, mg.recept_count, mg.draw_min_count, mg.request_count, mg.draw_state, mg.sold_out_flg, mg.del_flg")
			->field("lng.goods_name, lng.goods_info")
			->field("count( dr.goods_no) as mcnt")
			->field("(select count(*) from dat_request dr2 where dr2.member_no = ". $template->Session->UserInfo["member_no"] ." and mg.goods_no = dr2.goods_no ) as scnt")
			->from("left join dat_request dr on dr.goods_no = mg.goods_no" )
			->groupby( "mg.goods_no" )
			->page( $_GET["P"], (int)$_GET["VIEW"])
			->orderby( "mg.draw_state asc")
			->orderby( "mg.sold_out_flg asc")
			->orderby( str_replace('+', ' ', $_GET["ODR"] ))
		->createSql("\n");
	// 商品データ取得
	$rs = $template->DB->query($row_sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("ERRMSG",    $message);
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("NONE",  $allrows < 1);
	$template->if_enable("LISTS", $allrows > 0);
	
	$template->assign("ONETIME_SESSION_KEY", $template->Session->one_time, true);
	
	$template->assign("TYPE_ALL"       , $_type_active_list[0], true);
	$template->assign("TYPE_IN"        , $_type_active_list[1], true);
	$template->assign("TYPE_OUT"       , $_type_active_list[2], true);
	
	$template->assign("ODR"            , str_replace(' ', '+', $_GET["ODR"]), true);
	$template->assign("SEL_ODR"        , makeOptionArray($GLOBALS["goodsOrderTypeItem"], $order_target[0], false));
	$template->assign("VIEW"           , $_GET["VIEW"], true);
	$template->assign("SEL_VIEW"       , makeOptionArray($GLOBALS["viewcountList"], $_GET["VIEW"], false));
	$template->assign("POINT_LABEL"    , number_format( $_point), true);
	
	// リスト
	if( $allrows > 0){
		// ページング
		$template->assign("ALLROW", (string)$allrows, true);		// 総件数
		$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
		$template->assign("ALLP", (string)$allpage, true);			// 総ページ数
		$template->assign("PAGING" , HtmlPagingTag( (($_SERVER['QUERY_STRING']!="")? "?".$_SERVER['QUERY_STRING']."&":"?"), $_GET["P"], $allpage));
		
		$template->loop_start("LIST");
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			// データ
			$template->assign("NO"                  , $row["goods_no"], true);
			$template->assign("GOODS_CD"            , $row["goods_cd"], true);
			$template->assign("GOODS_INFO"          , $row["goods_info"], false, true);
			$template->assign("DRAW_DT_LABEL"       , format_datetime($row["draw_dt"]), true);
			$template->assign("DRAW_MIN_COUNT"      , number_format( $row["draw_min_count"]), true);
			$template->assign("REQUEST_COUNT"       , number_format( $row["request_count"]), true);
			$template->assign("GOODS_NAME"          , $row["goods_name"], true);
			$template->assign("GOODS_IMAGE"         , $row["goods_image"], true);
			$template->assign("RECEPT_START_LABEL"  , format_datetime($row["recept_start_dt"]), true);
			$template->assign("RECEPT_END_LABEL"    , format_datetime($row["recept_end_dt"]), true);
			$template->assign("DRAW_POINT_LABEL"    , number_format( $row["draw_point"]), true);
			$template->assign("WIN_COUNT_LABEL"     , number_format( $row["win_count"]), true);
			$template->assign("RECEPT_COUNT_LABEL"  , number_format( $row["recept_count"]), true);
			$template->assign("NOW_COUNT_LABEL"     , number_format( $row["mcnt"]), true);
			$template->assign("SELF_COUNT_LABEL"    , number_format( $row["scnt"]), true);
			// 開催状態 + ボタン操作
			$_goods_status_flg;
			$_btn_stats_flgs = array( false, false, false, false); // 0:期間外 1:SOLD OUT  2:応募する  3:応募する（不活性）
			if( $row["draw_state"] == 9){
				//停止
				$_goods_status_flg = 9;
				if( strtotime($now) < strtotime( $row["recept_start_dt"]) ){
					$_btn_stats_flgs[0] = true;
				}else{
					if( strtotime($now) < strtotime( $row["recept_end_dt"])){
						$_btn_stats_flgs[3] = true;
					}else{
						$_btn_stats_flgs[0] = true;
					}
				}
			}else{
				if( strtotime($now) < strtotime( $row["recept_start_dt"]) ){
					//開催前
					$_goods_status_flg = 0;
					$_btn_stats_flgs[0] = true;
				}else{
					if( strtotime($now) < strtotime( $row["recept_end_dt"])){
						//受付中
						$_goods_status_flg = 1;
						//商品判定
						if( $row["request_count"] <= $row["scnt"] || $row["recept_count"] <= $row["mcnt"] || $row["sold_out_flg"] > 0){
							$_btn_stats_flgs[1] = true;
						}else{
							//ポイント
							if( $_point < $row["draw_point"]){
								$_btn_stats_flgs[3] = true;
							}else{
								$_btn_stats_flgs[2] = true;
							}
						}
					}else{
						//受付終了
						if( strtotime( $row["draw_dt"]) <= strtotime($now) && $row["draw_state"] == 1){
							//抽選終了
							$_goods_status_flg = 3;
							$_btn_stats_flgs[0] = true;
						}else{
							//未抽選
							$_goods_status_flg = 2;
							$_btn_stats_flgs[0] = true;
						}
					}
				}
			}
			
			$template->assign("GOODS_STATUS_LABEL"  , $GLOBALS["goodsStatusList"][ $_goods_status_flg], true);
			$template->if_enable("GOODS_STATUS_BLOCK_OUT",        $_btn_stats_flgs[0]);
			$template->if_enable("GOODS_STATUS_BLOCK_SOLDOUT",    $_btn_stats_flgs[1]);
			$template->if_enable("GOODS_STATUS_BLOCK_READY",      $_btn_stats_flgs[2]);
			$template->if_enable("GOODS_STATUS_BLOCK_NOT_ENOUGH", $_btn_stats_flgs[3]);
			
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	
	// 表示
	$template->flush();
}


/**
 * 送信処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcData($template) {
	
	// データ取得
	getData($_GET , array("NO", "T", "P", "ODR", "VIEW", "TYPE"));
	
	//ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point")
			->field("(select draw_point from mst_goods mg where mg.goods_no = ". $_GET["NO"] .") as goods_draw_point")
			->from("mst_member men")
			->where()
				->and( false, "men.member_no = " , $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = "      , $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = "      , $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = "     , "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	$_chk = false;
	if( $row["draw_point"] < $row["goods_draw_point"]) $_chk = true;
	
	
	// 入力チェック
	$message = checkInput($template, $_chk);
	if (mb_strlen($message) > 0) {
		DispList($template, $message);
		return;
	}
	
	//商品応募処理
	// トランザクション開始
	$template->DB->autoCommit(false);
	
	// 応募最大数取得L
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mg.recept_count, mg.request_count, mg.sold_out_flg")
			->field("(select count(*) from dat_request dr where dr.goods_no = ". $_GET["NO"] .") as mcnt")
			->field("(select count(*) from dat_request dr2 where dr2.member_no = ". $template->Session->UserInfo["member_no"] ." and mg.goods_no = dr2.goods_no ) as scnt")
			->from("mst_goods mg" )
			->where()
				->and( false, "mg.goods_no = ", $_GET["NO"], FD_NUM)
		->createSql("\n");	
	$mgrow = $template->DB->getRow( $sql);
	
	if( $mgrow["sold_out_flg"] > 0 || $mgrow["request_count"] <= $mgrow["scnt"] ){
		DispList($template, $template->message("U1703"));
		return;
	}
	if( $_GET["T"] != $template->Session->one_time){
		DispList($template, $template->message("U1704"));
		return;
	}
	$template->Session->one_time = "";
	
	// 売り切れ
	if( $mgrow["recept_count"] <= $mgrow["mcnt"]+1){
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_goods")
				->set()
					->value( "sold_out_flg" , 1, FD_NUM)
				->where()
					->and( false, "goods_no = ", $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->query($sql);
	}
	
	// dat_request に登録
	// 新規
	// seq カウントアップ
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("dat_request dr")
			->where()
				->and( false, "dr.goods_no = ", $_GET["NO"], FD_NUM)
			->createSQL();
	$seqcnt = $template->DB->getOne($sql) + 1;
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->insert()
			->into( "dat_request" )
				->value( "goods_no"       , $_GET["NO"], FD_NUM)
				->value( "seq"            , $seqcnt, FD_NUM)
				->value( "member_no"      , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "request_dt"     , "current_timestamp", FD_FUNCTION)
				->value( "upd_no"         , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "upd_dt"         , "current_timestamp", FD_FUNCTION)
				->value( "add_no"         , $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "add_dt"         , "current_timestamp", FD_FUNCTION)
		->createSQL();
	$template->DB->query($sql);
	
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("mst_member men")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = "     , $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = "     , $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = "    , "1", FD_NUM)
			->createSQL();
	$mcnt = $template->DB->getOne($sql);
	if( $mcnt > 0){
		$DPOINT  = new PlayPoint($template->DB, false);
		if ( $DPOINT->addDrawPoint( $template->Session->UserInfo["member_no"], "52", -($row["goods_draw_point"]), $_GET["NO"], "", $template->Session->UserInfo["member_no"] )){
			//
		} else {
			$template->DB->autoCommit(true);
			DispList($template, $message);
			return;
		}
	}
	
	//再取得
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("point, draw_point")
				->from("mst_member")
				->where()
					->and("member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and("mail = ",      $template->Session->UserInfo["mail"], FD_STR)
					->and("state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	$template->Session->UserInfo["draw_point"] = $row["draw_point"];
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	
	// 商品一覧に戻る
	$_p    = (mb_strlen($_GET["P"]) > 0)? "&P=".$_GET["P"]:"";
	$_odr  = (mb_strlen($_GET["ODR"]) > 0)? "&ODR=".$_GET["ODR"]:"";
	$_view = (mb_strlen($_GET["VIEW"]) > 0)? "&VIEW=".$_GET["VIEW"]:"";
	$_type = (mb_strlen($_GET["TYPE"]) > 0)? "&TYPE=".$_GET["TYPE"]:"";
	header("Location: " . URL_SSL_SITE . "goods.php?M=comp".$_p.$_odr.$_view.$_type);
	
}


/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template, $_chk) {
	
	$errMessage = array();
	
	$errMessage = (new SmartAutoCheck($template))
		->item($_GET["NO"])
			->required("U1702")
		->item( $_chk)
			->eq("U1701", !true)
	->report();

	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
