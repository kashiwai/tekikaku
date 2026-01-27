<?php
/*
 * point_buy.php
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
 * ポイント購入画面表示
 *
 * ポイント購入画面の表示を行う
 *
 * @package
 * @author   鶴野 美香
 * @version  2.0
 * @since    2019/04/18 初版作成 鶴野 美香
             2020/04/28 修正     村上 俊行 p99決済用に修正
             2020/05/07 修正     村上 俊行 決済会社別に管理できるようにフォルダ構成を変更
                                           hidden項目をclassから取得するように変更
             2020/05/16 修正     岡本 静子 完了画面に遷移元判定追加
             2023-08/30 修正     村上 俊行 PayPal決済追加
 */

// インクルード
//require_once('../_etc/require_files_payment.php');	// 決済 requireファイル
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
		$PPOINT = new PlayPoint($template->DB, false);
		$PPOINT->pointReSession();
		
		// データ取得
		getData($_GET, array("M"));

		// 実処理
		switch ($_GET["M"]) {
			case "proc":			// 購入処理
				ProcData($template);
				break;
			case "conf":			// 購入確認
				DispConf($template);
				break;
			case "endin":			// 完了画面表示(内部遷移)
			case "end":				// 完了画面表示(外部遷移)
				DispEnd($template);
				break;

			default:				// 入力画面
				DispBuyScreen($template);
		}

	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 購入画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
$temp_k = 0;
function DispBuyScreen($template, $message = "") {
	global $temp_k;
	//ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point, men.point")
			->from("mst_member men")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	$_point = $row["point"];
	$_drawpoint = $row["draw_point"];
	
	// 購入タイプデータ
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mpp.purchase_type, mpp.amount, mpp.point")
			->from("mst_purchasePoint mpp")
			->where()
				->and( false, "mpp.del_flg <> ", "1", FD_NUM)
		->createSQL();
	$row = $template->DB->getAll($sql, PDO::FETCH_ASSOC);
	
	// 画面表示開始
	// 2020-04-28 決済会社別のフォルダ指定を追加
	$template->open(PAYMENT_DIR . PRE_HTML . ".html");
	$template->assignCommon();
	// 項目種別等
	$template->assign("POINT"      , number_format( $_point), true);
	$template->assign("MILE"       , number_format( $_drawpoint), true);
	$template->assign("MESSAGE"    , $message, true);
	$template->if_enable("ERRMSG"  , ($message != "" ? true : false));
	// リスト
	$template->loop_start("LIST");
	foreach( $GLOBALS["viewPurchaseType"] as $k => $v){
		$template->assign("PURCHASE_TYPE"    , $k, true);
		
		$temp_k = $k;
		$r = array_filter( $row, function($_v){
			global $temp_k;
			return $_v["purchase_type"]==$temp_k;
		});
		
		$template->loop_start("VALUELIST");
		foreach( $r as $rk => $rv){
			$template->assign("DISP_PURCHASE_POINT"   , number_format( $rv['point']), true);
			$template->assign("DISP_PURCHASE_AMOUNT"  , number_format( $rv['amount']), true);
			$template->assign("PURCHASE_POINT"   , $rv['point'], true);
			$template->assign("PURCHASE_AMOUNT"  , $rv['amount'], true);
			$template->assign("CURRENCY"         , $GLOBALS["viewAmountType"][ $rv['purchase_type']], true);
			$template->loop_next();
		}
		$template->loop_end("VALUELIST");
		//
		$template->loop_next();
	}
	$template->loop_end("LIST");
	
	// 表示
	$template->flush();
}


/**
 * 購入確認画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispConf($template, $message = "") {
	//2020-06-24 初期化追加
	$setting = array();
	// データ取得
	getData($_POST , array("buytype", "buyprice"));
	
	$SPOINT = new SettlementPoint( $template->DB );

	//ポイント取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("men.draw_point, men.point")
			->from("mst_member men")
			->where()
				->and( false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
				->and( false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
				->and( false, "men.pass = ",      $template->Session->UserInfo["pass"], FD_STR)
				->and( false, "men.state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	$_point = $row["point"];
	$_drawpoint = $row["draw_point"];
	
	// 購入タイプデータ
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mpp.purchase_type, mpp.amount, mpp.point")
			->from("mst_purchasePoint mpp")
			->where()
				->and( false, "mpp.purchase_type =" , $_POST["buytype"], FD_STR)
				->and( false, "mpp.amount =" , $_POST["buyprice"], FD_NUM)
				->and( false, "mpp.del_flg <> ", "1", FD_NUM)
		->createSQL();
	$mpp = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
	
	if( $_POST["buytype"] != 11){
		//決済情報のリクエスト情報作成
		if ( $SPOINT->request( $template->Session->UserInfo["member_no"], $_POST["buytype"], $_POST["buyprice"]) ){
			//正常終了
			$setting = $SPOINT->getAll();
			$_err = false;
		} else {
			//エラー
			$_err = true;
			DispBuyScreen($template, $template->message("U5065"));
			return;
		}
	}
	
	//fas icon
	if( $_POST["buytype"] == 11){
		$icon = "fa-coins";
	}else if( $_POST["buytype"] == 12){
		$icon = "fa-credit-card";
	}else if( $_POST["buytype"] == 13){
		$icon = "fa-store-alt";
	}else if( $_POST["buytype"] == 14){
		$icon = "fa-store-alt";
	}else if( $_POST["buytype"] == 15){
		$icon = "fa-credit-card";
	}else if( $_POST["buytype"] == 40){
		$icon = "fa-credit-card";
	}
	
	// 画面表示開始
	// 2020-04-28 決済会社別のフォルダ指定を追加
	$template->open(PAYMENT_DIR . PRE_HTML . "_conf.html");
	$template->assignCommon();
	//
	$template->if_enable("EXCHANGE"    , ($_POST["buytype"] == 11));
	// 2023-08-30 Paypal用に変更
	$template->if_enable("CHARGE"      , ($_POST["buytype"] != 11 && $_POST["buytype"] != 40));
	$template->if_enable("PAYPAL"      , ($_POST["buytype"] == 40));
	//
	$template->assign("buytype"        , $_POST["buytype"], true);
	$template->assign("buyprice"       , $_POST["buyprice"], true);

	// 決済会社別の設定
	// p99用データ設定
	if ($_POST["buytype"] == 40) {
		$template->assign("PAYMENT_URL"    , $setting["accessurl"], true);
	} else {
		$template->assign("PAYMENT_URL"    , PAYMENT_URL, true);
		$template->assign("PAYMENT_HIDDEN" , $SPOINT->hiddenTag(), false);			//hiddenタグを直接挿入
	}
	if ( is_array($setting) ){
		$template->assign("data"           , (array_key_exists("data",$setting)) ? $setting["data"] : "", true);
	}
	/*
	$template->assign("IP_CODE"        , $setting["IP_CODE"], true);
	$template->assign("cookie"         , $setting["crypt_cookie"], true);
	$template->assign("price"          , $setting["price"], true);
	$template->assign("sendid"         , $setting["crypt_sendid"], true);
	$template->assign("payment_code"   , $setting["payment_code"], true);
	$template->assign("email"          , $setting["email"], true);
	*/
	//
	$template->assign("DISP_buytype"   , $GLOBALS["pointHistoryProcessCode"][$_POST["buytype"]], true);
	$template->assign("icon_buytype"   , $icon, true);
	$template->assign("DISP_PURCHASE_AMOUNT", number_format( $_POST["buyprice"]), true);
	$template->assign("DISP_PURCHASE_POINT",  number_format( $mpp["point"]), true);
	$template->assign("FROM_CURRENCY"  , $GLOBALS["viewAmountType"][$_POST["buytype"]], true);
	
	// 項目種別等
	$template->assign("POINT"          , number_format( $_point), true);
	$template->assign("MILE"           , number_format( $_drawpoint), true);
	$template->assign("URL_SSL_SITE"   , URL_SSL_SITE, true);
	// 表示
	$template->flush();
}


/**
 * 購入処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ProcData($template) {
	// データ取得
	getData($_POST , array("buytype", "buyprice"));
	
	$SPOINT = new SettlementPoint( $template->DB );

	// チェック（指定のAmount量がDBにあるかどうか）
	$count_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("count(*)")
			->from("mst_purchasePoint mpp")
			->where()
				->and(false, "mpp.purchase_type = ", $_POST["buytype"], FD_NUM)
				->and(false, "mpp.amount = ",        $_POST["buyprice"], FD_NUM)
				->and(false, "mpp.del_flg <> ",      "1", FD_NUM)
		->createSQL();
	$cnt = $template->DB->getOne($count_sql);
	
	if( $cnt < 1){
		//error
		DispBuyScreen($template, $template->message("U5062"));//購入単位が存在しない
	}
	
	if( $_POST["buytype"] == 11){
		//決済情報のリクエスト情報作成
		if ( $SPOINT->request( $template->Session->UserInfo["member_no"], $_POST["buytype"], $_POST["buyprice"]) ){
			//Session更新処理
			$member_no = $template->Session->UserInfo["member_no"];
			// DB認証チェック
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
						->field("member_no, nickname, mail, pass, last_name, first_name, state, point, draw_point")
						->from("mst_member")
						->where()
							->and("member_no = ", $member_no, FD_NUM)
					->createSQL();
			$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
			$template->Session->UserInfo = $row;
		}
	}
	
	// 完了画面へ
	header("Location: " . URL_SITE . $template->Self . "?M=endin");
}

/**
 * 完了画面表示処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispEnd($template) {

	// データ取得
	getData($_GET , array("c"));

	//リファラーチェック
	if ( substr($_SERVER['HTTP_REFERER'], 0, strlen(URL_SSL_SITE)) != URL_SSL_SITE ){
		if ( !preg_match(PAYMENT_REFERER, $_SERVER['HTTP_REFERER']) ) {
			return;
		}
	}
	// 遷移元判定
	$outside = ($_GET["M"] == "end") ? true : false;

	// 画面表示開始
	$template->open(PAYMENT_DIR . PRE_HTML . "_end.html");
	$template->assignCommon();

	if ( mb_strlen($_GET["c"]) == 0 ){
		$template->if_enable("DONE"    , true);
		$template->if_enable("FAIL"    , false);

	} else {
		$code = $_GET["c"];
		if ( !preg_match("/^[0-9]{1,4}$/", $code ) ){
			$code = "----";
		}
		$template->if_enable("DONE"    , false);
		$template->if_enable("FAIL"    , true);
		$template->assign("ERROR_CODE", $code, true);
	}
	$template->if_enable("INSIDE"  , ($outside) ? false : true);		// 内部遷移
	$template->if_enable("OUTSIDE" , ($outside) ? true : false);		// 外部遷移

	$template->flush();
}


?>
