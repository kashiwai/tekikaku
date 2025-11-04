<?php
/*
 * profile.php
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
 * プロフィール確認・設定画面表示
 * 
 * プロフィール確認・設定画面の表示を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/08 初版作成 片岡 充
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
		DispProfile($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * プロフ画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispProfile($template) {
	
	//----------------------------------------------------------
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
		->field("mm.last_name, mm.first_name, mm.birthday, mm.sex, mm.invite_cd, mm.mail_magazine")
		->from("mst_member mm")
		->where()
			->and(false, "mm.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
			->and(false, "mm.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
			->and(false, "mm.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
			->and(false, "mm.state = ", "1", FD_NUM)
		->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	$_prof_edit = ( mb_strlen($row["last_name"])>0 || mb_strlen($row["first_name"])>0 )? true:false;
	$birth = "";
	if( mb_strlen($row["birthday"]) > 0) $birth = date( DISP_DATE_FORMAT, strtotime( $row["birthday"]));
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->if_enable("PROFILE_EDIT"    , !$_prof_edit);		// プロフィール未設定メッセージ
	
	$template->assign("INV_CODE"           , $row["invite_cd"], true);
	$template->assign("DISP_NAME"          , trim($row["last_name"]." ".$row["first_name"]), true);
	$template->assign("DISP_NAME_EN"       , trim($row["first_name"]." ".$row["last_name"]), true);
	$template->assign("DISP_BIRTHDAY"      , $birth, true);
	$template->assign("DISP_SEX"           , $template->getArrayValue($GLOBALS["SexList"], $row["sex"]), true);
	$template->assign("DISP_MAGAZINE"      , $template->getArrayValue($GLOBALS["MagazineReadStatus"], $row["mail_magazine"]), true);
	
	// 表示
	$template->flush();
}


?>
