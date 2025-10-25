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
 * 商品応募状況確認画面表示
 * 
 * 商品応募状況確認画面の表示を行う
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
		getData($_GET, array("M", "NO"));

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
	getData($_GET, array("NO"));
	
	$now = date("Y/m/d H:i:s");
	$itemsql = ($sqls = new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mg.goods_no, mg.recept_start_dt, mg.recept_end_dt, mg.draw_dt, mg.request_count, mg.recept_count, mg.sold_out_flg, mg.draw_state")
			->field("lng.goods_name")
			->field("(select count(*) from dat_request dr2 where mg.goods_no = dr2.goods_no ) as mcnt")
			->field("(select count(*) from dat_request dr2 where dr2.member_no = ". $template->Session->UserInfo["member_no"] ." and mg.goods_no = dr2.goods_no ) as scnt")
			->from("mst_goods mg")
			->join( "inner", "mst_goods_lang lng", "mg.goods_no = lng.goods_no and lng.lang = " . $template->DB->conv_sql(FOLDER_LANG, FD_STR))
			->where()
				->and( false, "mg.goods_no = ", $_GET["NO"], FD_NUM)
				->and( false, "mg.del_flg <> ", "1", FD_NUM)
		->createSQL();
	$itemrow = $template->DB->getRow($itemsql, MDB2_FETCHMODE_ASSOC);
	
	if (empty($itemrow["goods_no"])) {		// 商品データ不存在
		$template->dispProcError($template->message("U0003"));
		return;
	}
	
	$sql = ($sqls = new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dr.member_no, dr.seq, dr.request_dt, dr.result")
			->field("mm.nickname")
			->from("dat_request dr")
			->from("left join mst_member mm on mm.member_no = dr.member_no" )
			->from("left join mst_goods mg on mg.goods_no = dr.goods_no" )
			->where()
				->and( false, "dr.goods_no = ", $_GET["NO"], FD_NUM)
				->and( false, "mg.del_flg <>" , "1", FD_NUM)
			->orderby('seq asc')
		->createSQL();
	$rs = $template->DB->query($sql);
	
	
	// 応募状況フラグセット
	$_goods_status_flg;
	$_sold_out_flg = false;
	if( $itemrow["draw_state"] == 9){
		//停止
		$_goods_status_flg = 9;
	}else{
		if( strtotime($now) < strtotime( $itemrow["recept_start_dt"]) ){
			//開催前
			$_goods_status_flg = 0;
		}else{
			if( strtotime($now) < strtotime( $itemrow["recept_end_dt"])){
				//受付中
				$_goods_status_flg = 1;
				if( $itemrow["request_count"] <= $itemrow["scnt"] || $itemrow["recept_count"] <= $itemrow["mcnt"] || $itemrow["sold_out_flg"] > 0){
					$_sold_out_flg = true;
				}
			}else{
				//受付終了
				if( strtotime( $itemrow["draw_dt"]) <= strtotime($now) && $itemrow["draw_state"] == 1){
					//抽選終了
					$_goods_status_flg = 3;
				}else{
					//未抽選
					$_goods_status_flg = 2;
				}
			}
		}
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("NONE",  $itemrow["mcnt"] < 1);
	$template->if_enable("LISTS", $itemrow["mcnt"] > 0);
	$template->if_enable("SOLD_OUT_BLOCK"  , $_sold_out_flg);
	$template->assign("DRAW_STATUS_LABEL"  , $GLOBALS["goodsStatusList"][ $_goods_status_flg], false);
	$template->assign("GOODS_NAME"         , $itemrow["goods_name"], true);
	
	// リスト
	if( $itemrow["mcnt"] > 0){
		$template->loop_start("LIST");
		while ($row = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
			// データ
			$template->assign("SEQ_NO"         , $row["seq"], true);
			$template->assign("NAME"           , $row["nickname"], true);
			$template->assign("RESULT"         , $GLOBALS["drawResultList"][ $row["result"]], true);
			$template->assign("REQUEST_DT"     , format_datetime($row["request_dt"]), true);
			//
			$template->loop_next();
		}
		$template->loop_end("LIST");
	}
	// 表示
	$template->flush();
}

?>
