<?php
/*
 * information.php
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
 * お知らせ一覧画面表示
 * 
 * お知らせ一覧画面の表示を行う
 * 
 * @package
 * @author   岡本 静子
 * @version  1.0
 * @since    2023/10/04 初版作成 岡本 静子
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

		// データ取得
		getData($_GET, array("M"));

		// 画面表示
		DispList($template);

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
function DispList($template) {
	global $noticeTypeLang;

	$refToDay = GetRefTimeTodayExt();		// 基準時間＞使用開始時間の当日
	//----------------------------------------------------------
	// お知らせ情報取得
	$notice_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dn.notice_no")
			->field("IFNULL(dnl.list_title, dn.notice_name) as notice_list_title")
			->field("dn.link_type, dn.link_url, dn.upd_dt")
			->field("'notice' as notice_type")
			->from( "dat_notice_lang dnl" )
			->from( "inner join dat_notice dn on dn.notice_no = dnl.notice_no and dn.del_flg <> 1"
													. " and dn.start_dt <= " . $template->DB->conv_sql($refToDay, FD_DATE)
													. " and dn.end_dt >= " . $template->DB->conv_sql($refToDay, FD_DATE)
					)
			->where()
				->and(false, "dnl.top_image is not ", "", FD_STR)
				->and(false, "dnl.lang = ", FOLDER_LANG, FD_STR)
		->createSql("\n");

	// コーナー情報取得
	$corner_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("corner_no as notice_no")
			->field("(case when '" . FOLDER_LANG . "' = '" . DEFAULT_LANG . "' then corner_name else corner_roman end) as notice_list_title")
			->field("2 as link_type, '' as link_url, upd_dt")
			->field("'corner' as notice_type")
			->from( "mst_corner" )
			->where()
				->and(false, "notice_flg = ", 1, FD_NUM)
				->and(false, "del_flg = ", 0, FD_NUM)
		->createSql("\n");

	// 結合
	$sql = $notice_sql . " UNION ALL " . $corner_sql
		 . " order by upd_dt desc";
	$notice_row = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// リスト処理
	$notice_count = 0;
	$template->loop_start("LIST");
	foreach( $notice_row as $notice){
		$template->assign("NOTICE_DT"       , format_date($notice["upd_dt"]), true);
		$template->assign("NOTICE_TITLE"    , $notice["notice_list_title"], true);
		$template->assign("NOTICE_TYPE"     , $notice["notice_type"], true);
		$template->assign("NOTICE_TYPE_NAME", $noticeTypeLang[FOLDER_LANG][$notice["notice_type"]], true);
		$template->assign("LINK_URL"        , ($notice["link_type"]==1)
													? $notice["link_url"]
													:(($notice["link_type"]==2)
														? (($notice["notice_type"]=='corner')
															? "index.php?CN=".$notice["notice_no"]
															: "notice.php?NO=".$notice["notice_no"])
														:'#')
													);
		$template->assign("OTHER_LINK"      , ($notice["link_type"]==1)? ' target="_blank"': "", false);
		$template->if_enable("IS_LINK"      , !($notice["link_type"]==0));
		$template->if_enable("IS_CORNER"    , $notice["notice_type"]=='corner');
		$notice_count++;
		$template->loop_next();
	}
	$template->loop_end("LIST");
	$template->if_enable("IS_DATA", !empty($notice_row));
	$template->if_enable("NO_DATA", empty($notice_row));

	$template->flush();

}


?>
