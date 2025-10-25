#!/usr/bin/php -q
<?php
/*
 * grant_coupon.php
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
 * クーポン自動処理
 * 
 * クーポンの自動処理を行う
 * 
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/01/30 初版作成 片岡 充
 */

// インクルード
require_once('../_etc/require_files_batch.php');			// requireファイル

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

	// 多重起動回避
	$filepath = DIR_BIN . "grant_coupon.txt";
	// ファイル内容取得
	if (file_exists($filepath)) {
		$old_pid = file_get_contents($filepath);
		if (mb_strlen($old_pid) > 0) {
			$check = exec("ps ax | awk '$1==" . trim($old_pid) . " {print $5}'");
			// 前回のプロセスが起動中なら、処理を抜ける
			if (isset($check) && mb_strlen($check) > 0) exit;
		}
	}
	// プロセスID更新(start)
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, getmypid());
		fclose($fp);
	}

	// DBのインスタンス生成
	$db = new NetDB();

	// トランザクション開始
	$db->autoCommit(false);
	
	//発行するクーポンを取得
	$sqls = new SqlString();
	$sql = $sqls->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field( "dc.*" )
					->from("dat_coupon dc")
					->where()
						->and("dc.del_flg <> ", 1, FD_NUM)
						->and("dc.plan_dt <= "     , date("Y-m-d H:i:s"), FD_DATEEX )
						->and("dc.coupon_state = " , 0, FD_NUM)
			->createSql();
	$rs = $db->query( $sql);
	
	while ($couponRow = $rs->fetch(MDB2_FETCHMODE_ASSOC)) {
		//対象者
		$rows = $db->getMemberRows( array_change_key_case( $couponRow, CASE_UPPER), false);		// 2020/05/02 [UPD]
		//クーポン更新
		$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
			->update("dat_coupon")
				->set()
					->value( false, "coupon_state" , 1, FD_NUM)
					->value( false, "grant_count"  , count( $rows), FD_NUM)
					->value( false, "grant_dt"    , "current_timestamp", FD_FUNCTION)
					->value( false, "upd_no"       , BATCH_UPD_NO, FD_NUM)
					->value( false, "upd_dt"       , "current_timestamp", FD_FUNCTION)
				->where()
					->and( false, "coupon_no =" , $couponRow["coupon_no"], FD_NUM)
			->createSQL();
		$db->query($sql);
		
		if( count( $rows) > 0){
			//クーポンログ
			$params = "(coupon_no, member_no, add_dt)";
			$values = array();
			foreach( $rows as $row){
				$str =   "(" . $db->conv_sql( $couponRow["coupon_no"], FD_NUM)
						."," . $db->conv_sql( $row["member_no"], FD_NUM)
						.", current_timestamp)";
				$values[] = $str;
			}
			$sql = "insert into log_coupon ". $params ." values ". implode(',', $values) .";";
			$db->query($sql);
			
			// ポイント加算
			$PPOINT = new PlayPoint($db, false);
			$limit = ($couponRow["limit_days"]=="")? "":date('Y-m-d H:i', strtotime( "+".$couponRow["limit_days"]." day"));
			foreach( $rows as $v){
				$PPOINT->addPoint( $v["member_no"], "05", $couponRow["point"], $couponRow["coupon_no"], $limit, (array_key_exists("05", $GLOBALS["pointHistoryProcessCode"]) ? $GLOBALS["pointHistoryProcessCode"]["05"]: ""), "");	// 2020/05/02 [UPD]
			}
			
			//言語別タイトル取得
			$sql = (new SqlString())->setAutoConvert( [$db,"conv_sql"] )
				->select()
					->field("dcl.lang, dcl.title, dcl.contents")
					->from("dat_coupon_lang dcl")
					->where()
						->and( false, "dcl.coupon_no = ", $couponRow["coupon_no"], FD_NUM)
				->createSql();
			$temp = $db->getAll( $sql);
			$langrow = array();
			foreach( $temp as $v){
				$ret = array();
				$ret["title"]    = $v["title"];
				$ret["contents"] = $v["contents"];
				$langrow[$v["lang"]] = $ret;
			}
			
			//連絡Box登録
			$contact_message = array();
			$search  = array( "%TITLE%", "%POINT%", "%LABEL_1%");
			foreach( $GLOBALS["contactBoxLang"] as $k=>$v){
				if( $langrow[$k]["title"] != ""){
					$title = $langrow[$k]["title"];
				}else{
					$title = $langrow[FOLDER_LANG]["title"];
				}
				//
				$replace = array( $title, $couponRow["point"], $GLOBALS["unitLangList"][$k]["1"]);
				$contact_message[$k] = str_replace( $search, $replace, $v["01"]);
			}
			$contact = new ContactBox( $db, false);
			$contact->addRecords( $rows, "01", $couponRow["coupon_no"], $contact_message, "", "");
			
		}
		
		sleep(1);
	}
	unset($rs);
	// コミット(トランザクション終了)
	$db->autoCommit(true);


	$db->disconnect();	// DB解放

	// 多重起動回避　処理が終わったらPID開放
	if ($fp = @fopen($filepath, "w")) {
		fwrite($fp, "");
		fclose($fp);
	}

}

?>
