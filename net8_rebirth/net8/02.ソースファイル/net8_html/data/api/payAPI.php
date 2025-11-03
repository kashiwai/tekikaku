<?php
/*
 * payAPI.php
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
 * 精算API
 * 
 * 指定したユーザーの精算処理を行い、ハンドシェイクを解除する
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/20 新規 村上 俊行
 * @since    2020/06/24 修正 村上 俊行 Notice対応
 *           2020/07/16 修正 村上 俊行 Logger出力の中止
 *           2020/08/26 修正 村上 俊行 chatに精算時DM送信処理追加
 *           2020/09/23 修正 村上 俊行 ログ出力項目の追加
 *           2020/12/04 修正 村上 俊行 最大出玉数の追加
 *           2020/12/17 修正 村上 俊行 異常検知時のメンテナンスモード切替
 *           2021/06/01 修正 村上 俊行 出玉控除機能を追加
 *           2022/09/21 修正 村上 俊行 ポイント消化必須対応
 *           2023/04/14 修正 村上 俊行 精算行動終了コードを設定
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/APItool.php');					// APItool
require_once('../../_sys/PlayPoint.php');				// PlayPoint Class
require_once('../../_sys/ContactBox.php');				// ContactBox Class
require_once('./Logger.php');								// Logger Class

//2020-08-24 USE_CHATが定義されていたらインクルード
if ( defined ('USE_CHAT') ){
	if ( USE_CHAT ) require_once('../../_sys/chatAPI.php');				// Chat Class
}

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
		// API系表示コントロールのインスタンス生成
		$DB = new NetDB();
		// 実処理
		EchoJson($DB);
		
	} catch (Exception $e) {
		print $e->getMessage();
	}
}

/**
 * API処理
 * @access	private
 * @param	object	$DB			DBクラスオブジェクト
 * @return	なし
 */
function EchoJson($DB) {

	$jsonArray = array();
	$nowDate   = Date("Y-m-d H:i:s");
	
	// データ取得 2020-12-17 項目追加
	getData($_POST, array("machine_no","play_dt","member_no","in_point","out_point","in_credit","out_credit",
							"day_count", "total_count", "count", "bb_count", "rb_count",
							"tester_flg", "paymode", "exitcode",
							"user_game", "user_BB", "user_RB", "autodraw",
							"renchan_count","tenjo_count","ichigeki_credit", "day_max_credit",
							"abort_machine"
	));

	//$logger = new Logger( sprintf("./log/payAPI_%d.log", $_POST["machine_no"] ) );
	$logger = new Logger( "" );
	$logger->format( "{%date%} [{%level%}] {%message%}" );


	//API設定
	$api = new APItool();
	$PPOINT = new  PlayPoint( $DB, false );					//false:メソッド内でcommitしない

	//会員情報の取得 2022-09-21 読み込み場所変更
	$sql = (new SqlString($DB))
		->select()
//			->field("point,draw_point,mail")
			->field("point,draw_point,mail,invite_cd,nickname,deadline_point")
			->from("mst_member")
			->where()
				->and( "member_no =", $_POST["member_no"], FD_NUM)
		->createSQL("\n");
		
	$memberRow = $DB->getRow($sql);

	//台情報の取得
	$sql = (new SqlString($DB))
		->select()
//			->field("dm.machine_no,dm.owner_no,dm.convert_no")
			//2020-09-08 chat送信用にmachine_statusを追加
			->field("dm.machine_no,dm.owner_no,dm.convert_no,machine_status")
			->field("mc.point,mc.credit,mc.draw_point")
			->field("mm.model_name,mm.model_roman,mm.category")
			->from("dat_machine dm")
				->join("left", "mst_convertPoint mc", "dm.convert_no = mc.convert_no")
				->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->where()
				->and( "machine_no =", $_POST["machine_no"], FD_NUM)
		->createSQL("\n");
		
	$machineRow = $DB->getRow($sql);

	if ( mb_strlen($machineRow["machine_no"]) == 0 ){
		$logger->error("machine data not found");
		$logger->error($sql);
		$logger->error($_POST);

		$api->setError("machine data not found");
		$api->outputJson();
		return;
	}

	//精算処理（ポイント処理）
	//＊＊＊＊イベントなどで変換せずに別処理を行う可能性がある
	// 2022-09-26 初期化追加
	$lost_point = 0;
	$deductionCredit = 0;
	if ( $_POST["tester_flg"] == "0" ){
		$baseCredit = $_POST["credit"];
		//2021-06-01 パチンコのみで大当りがない場合はPACHI_RATE分を差し引く
		if ($machineRow["category"] == 1 && $_POST["user_BB"] > 0){
			//2021-06-03 SettingDBから取得するように変更
			$rate = $DB->getSystemSetting("PACHI_RATE") / 100;
			$baseCredit = $baseCredit * (1.0 - $rate);
			$deductionCredit = $rate;
		}
		// 2022-09-21 ポイント消化必須対応 追加
		// 期限付きポイントの失効処理
		$lostCredit = round(($memberRow["deadline_point"] / $machineRow["point"]) * $machineRow["credit"]);
		// 使用credit数が失効ポイント以下なら対象クレジットから失効分を減算
		if ($_POST["in_credit"] <= $lostCredit) {
			$baseCredit -= ($lostCredit - $_POST["in_credit"]);
			if ($baseCredit < 0) $baseCredit = 0;
			$lost_point = $PPOINT->calcPoint(POINT_CALC_MODE, $lostCredit - $_POST["in_credit"], $machineRow["credit"], $machineRow["point"]);
		} else {
			$lost_point = 0;
		}
		$draw_point = $PPOINT->calcPoint(POINT_CALC_MODE, $baseCredit, $machineRow["credit"], $machineRow["draw_point"]);
		//2020-06-03 settingの情報によって計算式を変更
		//$draw_point = $PPOINT->calcPoint(POINT_CALC_MODE, $_POST["credit"], $machineRow["credit"], $machineRow["draw_point"]);
		//2020-06-03 関数化に伴い削除
		//$draw_point = floor($_POST["credit"] / $machineRow["credit"]) * $machineRow["draw_point"];
		$draw_point_total = $draw_point;
		//強制精算がある場合の加算値
		if ( $_POST["autodraw"] > 0 ){
			$draw_point_total += $_POST["autodraw"];
		}
	} else {
		$draw_point = 0;
		$draw_point_total = 0;
	}

	// トランザクション開始
	$DB->autoCommit(false);

	//実機当日データを更新
	$sql = (new SqlString($DB))
		->update("dat_machinePlay")
			->set()
				//2020-09-28 日ゲーム数を追加
				->value("day_count",   $_POST["day_count"], FD_NUM)
				->value("total_count",   $_POST["total_count"], FD_NUM)
				->value("count",         $_POST["count"], FD_NUM)
				->value("bb_count",      $_POST["bb_count"], FD_NUM)
				->value("rb_count",      $_POST["rb_count"], FD_NUM)
				//2019-06-18 実機ごとのcredit増減履歴追加
				->value("in_credit",     $_POST["mc_in_credit"], FD_NUM)
				->value("out_credit",    $_POST["mc_out_credit"], FD_NUM)
				//2020-09-23 項目追加
				->value("renchan_count",   $_POST["renchan_count"], FD_NUM)
				->value("tenjo_count",     $_POST["tenjo_count"], FD_NUM)
				->value("ichigeki_credit", $_POST["ichigeki_credit"], FD_NUM)
				//2020-12-04 最大出玉数の更新
				->value("max_credit",    $_POST["day_max_credit"], FD_NUM)
				->value("upd_dt",        $nowDate, FD_DATE)
			->where()
				->and( "machine_no = ",  $_POST["machine_no"], FD_NUM )
		->createSQL("\n");
	$ret = $DB->query($sql);

	//2020-12-17 異常終了検知したらメンテナンスにする
	if ( mb_strlen($_POST["abort_machine"]) > 0 ){
		$sql = (new SqlString($DB))
			->update("dat_machine")
					->value("machine_status",  "2", FD_NUM)
					->value("upd_dt",          $nowDate, FD_DATE)
				->where()
					->and( "machine_no = ",    $_POST["machine_no"], FD_NUM )
			->createSQL("\n");
		$abret = $DB->query($sql);
		
		//2021-01-20 状況確認通知
		if ( defined ('MAIL_MACHINE_CHECK') ){
			require_once(DIR_LIB . "SmartMailSend.php");	// メール送信クラスライブラリ
			// メール送信インスタンス生成
			$smartMailSend = new SmartMailSend(MAIL_PROTOCOL, $GLOBALS["MailParam"]);
			// 内容置換 2021/01/26 置換追加
			$subject = MACHINE_CHECK_SUBJECT;
			$body = str_replace(array("%MACHINE_NO%", "%MODEL_NAME%", "%ABORT_MACHINE%")
							, array($_POST["machine_no"], $machineRow["model_name"], $_POST["abort_machine"])
							, MACHINE_CHECK_BODY);
			// メール送信
			$smartMailSend->setMailSendData(MAIL_MACHINE_CHECK, MAIL_MACHINE_CHECK, "", "", MAIL_ERROR);
			$smartMailSend->make($subject, $body);
			$smartMailSend->send();
		}
	}
	
	//プレイ履歴の保存
	$sql = (new SqlString($DB))
		->insert()
			->into("his_play")
				->value("machine_no",      $machineRow["machine_no"], FD_NUM)
				->value("start_dt",        $_POST["play_dt"], FD_DATE)
				->value("end_dt",          $nowDate, FD_DATE)
				->value("member_no",       $_POST["member_no"], FD_NUM)
				->value("owner_no",        $machineRow["owner_no"], FD_NUM)
				->value("convert_no",      $machineRow["convert_no"], FD_NUM)
				->value("point",           $machineRow["point"], FD_NUM)
				->value("credit",          $machineRow["credit"], FD_NUM)
				->value("draw_point",      $machineRow["draw_point"], FD_NUM)
				->value("in_point",        $_POST["in_point"], FD_NUM)
				->value("out_point",       $_POST["out_point"], FD_NUM)
				->value("in_credit",       $_POST["in_credit"], FD_NUM)
				->value("out_credit",      $_POST["out_credit"], FD_NUM)
				->value("out_draw_point",  $draw_point_total, FD_NUM)
				->value("lost_point",      $lost_point, FD_NUM)
				->value("play_count",      $_POST["user_game"], FD_NUM)
				->value("bb_count",        $_POST["user_BB"], FD_NUM)
				->value("rb_count",        $_POST["user_RB"], FD_NUM)
				// 2023-04-14追加
				->value("out_action_type", $_POST["exitcode"], FD_STR)
		->createSQL("\n");
	$result = $DB->query($sql);
	if ( $result->rowCount() == 0 ){
		$DB->rollBack();

		$logger->error("his_play insert error");
		$logger->error($sql);
		$logger->error($_POST);

		$api->setError("his_play insert error");
		$api->outputJson();
		return;
	}

	//2020-06-24 初期化タイミング変更
	$cbMessage = array();

	//連絡Box登録
	if ( $_POST["paymode"] == 'auto' ){
		//自動精算の時だけ処理する
		$contactType = "03";
		//2020-06-24 初期化タイミング変更
		//$cbMessage = array();
		foreach( $GLOBALS["contactBoxLang"] as $lang => $templateStr ){
			$templateStr = $GLOBALS["contactBoxLang"][$lang][$contactType];
			if ( $lang == DEFAULT_LANG ){
				$templateStr = str_replace( "%MODEL_NAME%", $machineRow["model_name"], $templateStr );
			} else {
				$templateStr = str_replace( "%MODEL_NAME%", $machineRow["model_roman"], $templateStr );
			}
			$templateStr = str_replace( "%COIN%",       $draw_point_total, $templateStr );
			$templateStr = str_replace( "%LABEL_3%",    $GLOBALS["viewUnitList"]["3"], $templateStr );
			$cbMessage[$lang] = $templateStr;
		}
		
		$CB = new ContactBox($DB, false);
		if ( $CB->addOneRecord( $_POST["member_no"], $contactType, $_POST["machine_no"], $cbMessage, "", API_PAY_UPD_NO ) == false ){
			$DB->rollBack();
			$api->setError("ContactBox insert error");
			$api->outputJson();
			return;
		}
	}

	//接続設定の解除
	$sql = (new SqlString($DB))
		->update("lnk_machine")
			->set()
				->value("assign_flg",   0, FD_NUM)
				->value("member_no",    "", FD_STR)
				->value("onetime_id",   "", FD_STR)
				// 2020-08-17 exit_flgの初期化
				->value("exit_flg",     0,  FD_NUM)
				->value("end_dt",       $nowDate, FD_DATE)
			->where()
				->and( "machine_no =", $_POST["machine_no"], FD_NUM)
		->createSQL("\n");
		
	$result = $DB->query($sql);
	if ( $result->rowCount() == 0 ){
		$DB->rollBack();

		$logger->error("lnk_machine update error");
		$logger->error($sql);
		$logger->error($_POST);

		$api->setError("lnk_machine update error");
		$api->outputJson();
		return;
	}

	/*
	//台情報の取得
	$sql = (new SqlString($DB))
		->select()
//			->field("point,draw_point,mail")
			->field("point,draw_point,mail,invite_cd,nickname")
			->from("mst_member")
			->where()
				->and( "member_no =", $_POST["member_no"], FD_NUM)
		->createSQL("\n");
		
	$memberRow = $DB->getRow($sql);
	*/

	//抽選ポイントの更新
	if ( $draw_point_total > 0 ){
		if ( !$PPOINT->addDrawPoint($_POST["member_no"], "11", $draw_point_total,$_POST["machine_no"] ) ){
			$api->setError($PPOINT->getError());
			$api->outputJson();
			return;
		}
	}
	// 2022-09-21 ポイント消化必須対応 追加
	// mst_memberのdeadline_pointを初期化
	$sql = (new SqlString($DB))
		->update("mst_member")
			->set()
				->value("deadline_point", 0, FD_NUM)
			->where()
				->and( "member_no =", $_POST["member_no"], FD_NUM)
		->createSQL("\n");
		
	$result = $DB->query($sql);
	if ( $result == false ){
		$DB->rollBack();
		$this->_setError("mst_member update error");
		return;
	}

	//コミット
	$DB->autoCommit(true);

	if ( API_PROXY != "" ){
		pushAPI($_POST["member_no"],$memberRow['mail'],$_POST["machine_no"]);
	}

	//2020-08-26 chatが導入されていたら処理
	if ( defined ('USE_CHAT') ){
		//2020-09-08 machine_status == "1"(稼働中)の時のみ送信する
		if ( USE_CHAT && $machineRow["machine_status"] == "1"){
			$cht = array();
			$cht["member_no"]     = $_POST["member_no"];
			$cht["nickname"]      = $memberRow["nickname"];
			$cht["username"]      = $memberRow["invite_cd"];
			$cht["credit"]        = $_POST["credit"];
			$cht["in_credit"]     = $_POST["in_credit"];
			$cht["out_credit"]    = $_POST["out_credit"];
			$cht["out_drawpoint"] = $draw_point_total;
			$cht["machine_no"]    = $_POST["machine_no"];
			$cht["model_name"]    = $machineRow["model_name"];

			$chat = new chatAPI();
			$res = $chat->highPointDM($cht);
			$api->set( "chat" , $res );
		}
	}

	//戻り値設定
	$pay = array();
	$pay["play_point"]       = $memberRow["point"];
	$pay["credit"]           = $_POST["credit"];
	$pay["draw_point"]       = $draw_point;
	$pay["autodraw"]         = $_POST["autodraw"];
	$pay["total_draw_point"] = $memberRow["draw_point"] + $draw_point_total;
	//$pay["cdMessage_ja"]     = $cbMessage["ja"];
	//$pay["cdMessage_en"]     = $cbMessage["en"];
	//2020-06-24 notice対応
	$pay["cdMessage_ja"]     = ( array_key_exists("ja",$cbMessage ) ) ? $cbMessage["ja"] : "";
	$pay["cdMessage_en"]     = ( array_key_exists("en",$cbMessage ) ) ? $cbMessage["en"] : "";
	// 2021-06-01 出玉控除用追加
	$pay["deduction_credit"]  = $deductionCredit;

	$api->set( "pay" , $pay );
	$api->outputJson();


/*
	$jsonArray["status"] = "ok";
	$jsonArray["pay"]["play_point"]       = $memberRow["point"];
	$jsonArray["pay"]["credit"]           = $_POST["credit"];
	$jsonArray["pay"]["draw_point"]       = $draw_point;
	$jsonArray["pay"]["total_draw_point"] = $memberRow["draw_point"];

	outputJson( $jsonArray );
*/
}

/**
 * Proxyへの終了通知
 * @access	private
 * @param	string	$jsonArray	json変換用連想配列
 * @return	なし
 */
function pushAPI( $member_no, $mailAddress, $machine_no) {


	$url = API_PROXY . "pay/";
	$data = json_encode(
		array(
			'member_no'  => $member_no,
			'mail'       => $mailAddress,
			'machine_no' => $machine_no,
		)
	);
	$options = array('http' => array(
		'method' => 'POST',
		'header'=>  'Content-type: application/json; charset=UTF-8',
		'content' => $data
	));
	$contents = file_get_contents($url, false, stream_context_create($options));
}

/**
 * JSON出力処理
 * @access	private
 * @param	string	$jsonArray	json変換用連想配列
 * @return	なし
 */
function outputJson( $jsonArray ) {
	$json = json_encode( $jsonArray );
	
	// キャッシュコントロール
	header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
	header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
	header("Cache-Control: no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
	//jsonを返す指定
	header("Content-Type: application/json; charset=utf-8");

	print $json;
}

?>
