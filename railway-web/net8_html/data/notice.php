<?php
/*
 * notice.php
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
 * お知らせ情報画面表示
 * 
 * お知らせ情報表示/移動を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/07 初版作成 片岡 充
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
		// 画面表示
		DispPage($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispPage($template) {
	
	// データ取得
	getData($_GET , array("NO"));
	
	$refToDay = GetRefTimeTodayExt();	// 基準時間＞使用開始時間の当日
	//----------------------------------------------------------
	// お知らせ情報取得
	$notice_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dnl.lang, dnl.top_image, dnl.title, dnl.sub_title, dnl.contents")
			->field("dn.notice_no, dn.notice_name, dn.link_type, dn.link_url, dn.disp_order, dn.start_dt, dn.end_dt, dn.del_flg, dn.upd_dt")
			->from( "dat_notice_lang dnl" )
			->from( "inner join dat_notice dn on dn.notice_no = dnl.notice_no and dn.del_flg <> 1"
													. " and dn.start_dt <= " . $template->DB->conv_sql($refToDay, FD_DATE)
													. " and dn.end_dt >= " . $template->DB->conv_sql($refToDay, FD_DATE)
					)
			->where()
				->and(false, "dnl.notice_no = ", $_GET["NO"], FD_NUM)
				->and(false, "dnl.lang = ", FOLDER_LANG, FD_STR)
				->and(false, "dnl.contents is not ", "", FD_STR)
		->createSql("\n");
	$notice = $template->DB->getAll($notice_sql, MDB2_FETCHMODE_ASSOC);
	
	if(count($notice)<=0){
		header("Location: " . URL_SITE);
		exit();
	}else{
		if( $notice[0]["link_type"] != 2){
			header("Location: " . URL_SITE);
			exit();
		}else{
			// 画面表示開始
			$template->open(PRE_HTML . ".html");
			$template->assignCommon();
			$template->assign("DIR_IMG_NOTICE_DIR"   , DIR_IMG_NOTICE_DIR);
			// 各種情報（単位）
			$template->assign("TITLE",      $notice[0]["title"], true);
			$template->assign("SUB_TITLE",  $notice[0]["sub_title"], true);
			$template->assign("CONTENTS",   $notice[0]["contents"]);
			$template->assign("TOP_IMAGE",  $notice[0]["top_image"], true);
			
			$template->flush();
		}
	}
}

?>
