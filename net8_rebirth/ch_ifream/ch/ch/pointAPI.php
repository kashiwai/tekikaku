<?php
/*
 * point API.php
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
 * ポイントの加減算API
 * 
 * ポイントの加減算を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2020/03/06 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル

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
	
	$ary = array();
	$ary["status"] = "ok";
	
	try {
		getData($_GET, array("mail", "point", "draw_point"));
		//
		$template = new TemplateUser(false);
		$template->checkSessionUser(true, false);
		//
		if( is_object( $template->Session->UserInfo)){
			//
			$sqls = new SqlString();
			$sql = $sqls->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("men.member_no, men.mail, men.point, men.draw_point")
					->from("mst_member men")
					->where()
						->and(false, "men.mail = ",  $_GET["mail"], FD_STR)
						->and(false, "men.state = ", "1", FD_NUM)
					->createSQL();
			$row = $template->DB->getRow( $sql);
			//
			if( count( $row) > 0){
				//返却値設定
				$ary["before"] = array();
				$ary["before"]["point"] = $row["point"];
				$ary["before"]["draw_point"] = $row["draw_point"];
				$ary["after"] = array();
				$ary["after"]["point"] = $row["point"];
				$ary["after"]["draw_point"] = $row["draw_point"];
				//変更処理
				$PPOINT = new PlayPoint($template->DB);
				//ポイント
				if( mb_strlen( $_GET["point"]) > 0){
					if( is_numeric( $_GET["point"])){
						$addPoint = intval( $_GET["point"]);
						$PPOINT->addPoint( $row["member_no"], "93", $addPoint, "", "", $template->getArrayValue( $GLOBALS["pointHistoryProcessCode"], "93"), $row["member_no"] );
						//返却値設定
						$ary["after"]["point"] += $addPoint;
					}
				}
				//抽選ポイント
				if( mb_strlen( $_GET["draw_point"]) > 0){
					if( is_numeric( $_GET["draw_point"])){
						$addDraw = intval( $_GET["draw_point"]);
						$PPOINT->addDrawPoint( $row["member_no"], "93", $addDraw, "", $template->getArrayValue( $GLOBALS["pointHistoryProcessCode"], "93"), $row["member_no"] );
						//返却値設定
						$ary["after"]["draw_point"] += $addDraw;
					}
				}
			}else{
				$ary["status"] = "ng";
			}
		}else{
			$ary["status"] = "ng";
		}
	} catch (Exception $e) {
		$ary["status"] = "ng";
	}
	
	header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
	header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	//jsonを返す指定
	header("Content-Type: application/json; charset=utf-8");
	print(json_encode( $ary));
	
}
?>
