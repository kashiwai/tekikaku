<?php
/*
 * checkpoint.php
 * 
 * (C)SmartRams Co.,Ltd. 2016 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * checkpoint.php
 * 
 * 失効ポイントの有無を取得する
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2022/09/22 流用新規 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool

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
		
		// 実処理
		EchoJson($template);
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * API処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function EchoJson($template) {

	// データ取得
	getData($_GET , array("no", "in_credit"));

	//API設定
	$api = new APItool();

	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("deadline_point")
			->from("mst_member")
			->where()
				->and( "member_no = ", $template->Session->UserInfo["member_no"], FD_NUM )
		->createSQL("\n");
	$memberRow = $template->DB->getRow($sql);

	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.machine_no,machine_status")
			->field("mc.point,mc.credit,mc.draw_point")
			->from("dat_machine dm")
				->join("left", "mst_convertPoint mc", "dm.convert_no = mc.convert_no")
			->where()
				->and( "machine_no =", $_GET["no"], FD_NUM)
		->createSQL("\n");
	$machineRow = $template->DB->getRow($sql);

	// 期限付きポイントの失効処理
	// $lostCredit = round(($memberRow["deadline_point"] / $machineRow["point"]) * $machineRow["credit"]);
	$lostCredit = floor(($memberRow["deadline_point"] / $machineRow["point"]) * $machineRow["credit"]);
	
	$PPOINT = new  PlayPoint( $template->DB, false );	
	$lost_point = $PPOINT->calcPoint(POINT_CALC_MODE, $lostCredit - $_GET["in_credit"], $machineRow["credit"], $machineRow["point"]);
	
	$api->set("status", $lostCredit > $_GET["in_credit"]);
	$api->set("deadlinePoint", $memberRow["deadline_point"]);
	$api->set("lostCredit", $lostCredit);
	$api->set("lostPoint", $lost_point);
	$api->set("point", $machineRow["point"]);
	$api->set("credit", $machineRow["credit"]);

	$api->outputJson();
}

?>
