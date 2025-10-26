<?php
/*
 * gameafter.php
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
 * ゲーム終了時メンバー情報更新
 * 
 * ゲーム終了時にメンバー情報の更新を行う
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

		UpdateMemberData($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * メンバー情報の更新
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function UpdateMemberData($template) {
	
	if ( $template->checkSessionUser(false,false) ){
		$member_no = $template->Session->UserInfo["member_no"];
	} else {
		header("Location: /");
		return;
	}

	// DB認証チェック
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname, mail, pass, last_name, first_name, state, point, draw_point")
				->from("mst_member")
				->where()
					->and("member_no = ", $member_no, FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	
	$template->Session->UserInfo = $row;

	/*
	// 2020-06-05 lnk_machine に自分の情報が残っている場合はリセットする
	// トランザクション開始
	$template->DB->autoCommit(false);
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update("lnk_machine")
			->set()
				->value("member_no",     "0",  FD_NUM)
				->value("onetime_id",    "",  FD_STR)
			->where()
				->and( "member_no = ",   $member_no, FD_NUM )
				->and( "assign_flg = ",  "1", FD_NUM )
		->createSQL();
	$ret = $template->DB->query($sql);
	if ( !$ret ){
		$template->DB->rollBack();
	} else {
		//コミット
		$template->DB->autoCommit(true);
	}
	*/
	
	// 2020-08-17 exitボタンによる重複起動対策
	// トランザクション開始
	$template->DB->autoCommit(false);
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update("lnk_machine")
			->set()
				->value("exit_flg",     "1",  FD_NUM)
			->where()
				->and( "member_no = ",   $member_no, FD_NUM )
				->and( "assign_flg = ",  "1", FD_NUM )
		->createSQL();
	$ret = $template->DB->query($sql);
	if ( !$ret ){
		$template->DB->rollBack();
	} else {
		//コミット
		$template->DB->autoCommit(true);
	}

	header("Location: /");
}

?>
