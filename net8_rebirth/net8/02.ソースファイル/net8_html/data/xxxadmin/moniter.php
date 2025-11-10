<?php
/*
 * moniter.php
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
 * メーカー管理画面表示
 * 
 * メーカー管理画面の表示を行う
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
		// ユーザ系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();
		$template->Session = new SmartSession(URL_ADMIN . "login.php", SESSION_SEC_ADMIN, SESSION_SID_ADMIN, DOMAIN, true);
		
		//権限チェック
		if( mb_strlen( checkAuth( $template)) > 0) header("Location: " . URL_ADMIN);
		
		// 実処理
		$mainWin = true;
		DispList($template);
		
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
	
	$sqls = new SqlString();
	$sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("dm.machine_no, dm.model_no, dm.owner_no, dm.camera_no, dm.signaling_id, dm.convert_no, dm.release_date, dm.end_date, dm.machine_corner, dm.machine_status, dm.del_flg, dm.upd_dt")
				->field("mo.model_cd, mo.model_name, mo.maker_no, mo.image_list")
			->from("dat_machine dm")
			->from("left join mst_model mo on mo.model_no = dm.model_no" )
			->from("left join mst_owner ow on ow.owner_no = dm.owner_no" )
			->where()
				->and( false, "dm.del_flg = "  , 0, FD_NUM )
				->and( false, "dm.machine_status <> ", 0, FD_NUM )							//準備中をはじく
				->and( false, "dm.release_date <= ", "current_timestamp", FD_FUNCTION)		//期間中の台のみ
				->and( false, "dm.end_date >= "    , "current_timestamp", FD_FUNCTION)
			->groupby( "dm.machine_no" )
			->orderby( "dm.camera_no" )
		->createSql("\n");
	$rs = $template->DB->query($sql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 緊急メッセージリストの初期化（未定義の場合は空配列）
	if (!isset($GLOBALS["emgMessageList"])) {
		$GLOBALS["emgMessageList"] = [];
	}

	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("MACHINE_NO", $row["machine_no"], true);
		$template->assign("MODEL_NAME", $row["model_name"], true);
		// 緊急メッセージリスト処理個別用
		$template->loop_start("SUBEMGLIST");
		foreach( $GLOBALS["emgMessageList"] as $key => $msg){
			$template->assign("KEY" , $key, true);
			$template->assign("BTN" , $msg[0], true);
			$template->assign("MSG" , $msg[1], true);
			$template->loop_next();
		}
		$template->loop_end("SUBEMGLIST");

		$template->loop_next();
	}
	$template->loop_end("LIST");

	// 緊急メッセージリスト全体ボタン用
	$template->loop_start("EMGLIST");
	foreach( $GLOBALS["emgMessageList"] as $key => $msg){
		$template->assign("KEY" , $key, true);
		$template->assign("BTN" , $msg[0], true);
		$template->assign("MSG" , $msg[1], true);
		$template->loop_next();
	}
	$template->loop_end("EMGLIST");
	
	// 緊急メッセージリスト JS用
	$template->loop_start("JSEMGLIST");
	foreach( $GLOBALS["emgMessageList"] as $key => $msg){
		$template->assign("KEY" , $key, true);
		$template->assign("BTN" , $msg[0], true);
		$template->assign("MSG" , $msg[1], true);
		$template->loop_next();
	}
	$template->loop_end("JSEMGLIST");
	
	// 表示
	$template->flush();
}


/**
 * 権限チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
 
function checkAuth( $template) {
	$errMessage = "";
	//自分にコーナー管理（機種管理：2）の権限があるかどうかをチェック
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "deny_menu" )
			->from( "mst_admin" )
			->where()
				->and( "admin_no = ", $template->Session->AdminInfo["admin_no"], FD_NUM )
		->createSql();
	$_ary = explode(',', $template->DB->getOne($sql));
	//
	if( in_array('2', $_ary, true)) $errMessage = $template->message("A0005");
	//
	return $errMessage;
}


?>
