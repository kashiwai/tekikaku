<?php
/*
 * SettlementPoint.php (gash payment system)
 * 
 * (C)SmartRams Corp. 2019 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * SettlementPoint class
 * 
 * 決済会社別による処理
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/03/06 初版作成 村上俊行
 *           2020/04/28 修正     村上俊行    p99用に関数を修正
 *           2020/05/08 修正     村上俊行    複合化＆更新処理をメソッド化
 *           2023/09/01 修正     村上俊行    PayPal決済機能追加
 * @info
 */


class SettlementPoint {

	// メンバ変数定義
	private $_DB;												//DBインスタンス
	private $_nowDate;											//設定で使う本日日時
	private $_nowPoint;
	private $_error = "";
	private $_setting = array();
	private $_json_data = array();
	private $_erqc = "";
	
	public  $_settlejson = "";
	public  $_settleError = "";
	
	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct( $DB ) {
		// メンバ変数へ格納
		$this->_DB = $DB;
		$this->_nowDate = Date("Y-m-d H:i:s");
		$this->_setting = array();
	}

	/**
	 * 現在設定しているセッティング情報を返す
	 * @access  public
	 * @return  							setting配列
	 */
	public function getAll() {
		return( $this->_setting );
	}

	/**
	 * セッティング情報に値を設定
	 * @access  public
	 * @param	int		$name				名称
	 * @param	int		$value				値
	 * @return  							なし
	 */
	public function set($name, $value){
		$this->_setting[$name] = $value;
	}

	/**
	 * 決済request用のタグを返す
	 * @access  public
	 * @return  							hiddenタグ
	 */
	public function hiddenTag(){
		$tags = "<input type=\"hidden\" name=\"data\" value=\"{%data%}\">\n";
		return $tags;
	}

	/**
	 * 決済request用のレコードを作る
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$purchase_type		購入方法
	 * @param	int		$amount				購入金額
	 * @return  							true: 成功 false: 失敗
	 */
	public function request( $member_no, $purchase_type, $amount){

		//ポイント購入マスタ取得
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("point")
				->from("mst_purchasePoint")
				->where()
					->and( "purchase_type = ",  $purchase_type, FD_STR )
					->and( "amount = ",         $amount, FD_NUM )
			->createSQL("\n");
		$purchaseRow = $this->_DB->getRow($sql);
		if (count($purchaseRow) == 0) {
			$this->_setError( "mst_purchasePoint record not found" );
			return( false );
		}
		if ( mb_strlen($purchaseRow["point"]) == 0 ){
			$this->_setError( "mst_purchasePoint not found" );
			return( false );
		}

		// トランザクション開始
		$this->_DB->autoCommit(false);

		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("member_no,mail,point,draw_point")
				->from("mst_member")
				->where()
					->and( "member_no = ",  $member_no, FD_NUM )
			->createSQL("\n");
		$memberRow = $this->_DB->getRow($sql);
		if ( $member_no != $memberRow["member_no"] ){
			$this->_setError( "mst_member not found" );
			return( false );
		}

		$recept_dt = Date("Y-m-d H:i:s");
		if ( $purchase_type == "11" ){
			//抽選ポイントの場合
			$status = "1";
			$purchase_dt = Date("Y-m-d H:i:s");
			if ( $memberRow["draw_point"] < $amount ) $status = "9";
		} else {
			//決済の場合
			$status = "0";
			$purchase_dt = "";
		}
		
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->insert()
				->into("his_purchase")
					->value("member_no",      $member_no, FD_NUM)
					->value("recept_dt",      $recept_dt, FD_DATE)
					->value("purchase_type",  $purchase_type, FD_NUM)
					->value("amount",         $amount, FD_NUM)
					->value("point",          $purchaseRow["point"], FD_NUM)
					->value("result_status",  $status, FD_NUM)
					->value(true, "purchase_dt", $purchase_dt, FD_DATE)
			->createSQL("\n");
		$result = $this->_DB->query($sql);

		if ( $result->rowCount() == 0 ){
			$this->_DB->rollBack();
			$this->_setError( "his_purchase insert error" );
			return( false );
		} else {
			$lastno = $this->_DB->lastInsertId('purchase_no');
			$this->set( "_type",            $purchase_type );
			$this->set( "_url",             PAYMENT_URL );
			if ( $purchase_type == "11" ){
				//抽選ポイントの場合
				
				$this->set( "IP_CODE",          "drawpoint" );
				$this->set( "cookie",           $lastno );
				$this->set( "price",            $amount );
				$this->set( "sendid",           $member_no );

				if ( $status == "1" ){
					//point classをno commit modeで作成
					$PPOINT  = new PlayPoint( $this->_DB, false );

					//point加算（会員番号,加算種別,金額,参照key,有効期限）
					if ( !$PPOINT->addDrawPoint( $member_no, "51", $amount*-1 , $lastno ) ){
						$this->_setError( $PPOINT->getError() );
						$this->_DB->rollBack();
						return( false );
					}
					//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
					if ( !$PPOINT->addAmount2Point( $member_no, $purchase_type, $amount, $lastno, "" ) ){
						$this->_setError( $PPOINT->getError() );
						$this->_DB->rollBack();
						return( false );
					}
					$this->set( "point",            intval($memberRow["point"]) + intval($purchaseRow["point"]) );
					$this->set( "before_drawpoint", intval($memberRow["draw_point"]) );
					$this->set( "drawpoint",        intval($memberRow["draw_point"]) - intval($amount) );
				} else {
					$this->set( "point",            intval($memberRow["point"]) );
					$this->set( "before_drawpoint", intval($memberRow["draw_point"]) );
					$this->set( "drawpoint",        intval($memberRow["draw_point"]) );
				}
			} else {
				if ($purchase_type == "40"){
					// PayPal専用処理
					// access_urlを取得
					$access_url = get_paypal_orders_url($amount, $lastno, $member_no);
					$this->set( "accessurl",     $access_url);			// URLのみで処理できるのでURL値をセット
					$this->set( "cookie",        $lastno );				//互換用にcookieに購入番号をセット
					$this->set( "sendid",        $member_no );			//互換用にsendidにmember_noをセット
					if ($access_url == "") {
						$this->_DB->rollBack();
						return( false );
					}
				} else {
					//その他決済の場合
					$coid_date = str_replace("-", "", $recept_dt);
					$coid_date = str_replace(":", "", $coid_date);
					$coid_date = str_replace(" ", "", $coid_date);
					$coid = $coid_date."P".sprintf("%09d", $lastno);

					//p99 payment class
					$trans = new Trans( null );
					// 取引情報コード
					$trans->nodes["MSG_TYPE"] = "0100"; 				// 取引情報コード
					$trans->nodes["PCODE"] = "300000"; 					// トランザクション処理コード 
					$trans->nodes["CID"] = CONTENTSERVICE_ID;			// マーチャントゲームコード
					$trans->nodes["COID"] = $coid;						// マーチャントオーダー番号
					$trans->nodes["CUID"] = "TWD";						// 通貨
					//$trans->nodes["CUID"] = "PIN";						// 通貨
					$trans->nodes["PAID"] = $GLOBALS["Payment_Code_ConvertArray"][$purchase_type];
																		// 支払いコレクターコード (支払方法）
					$trans->nodes["AMOUNT"] = $amount;					// 取引金額
					
					$trans->nodes["RETURN_URL"] = PAYMENT_RETURN_URL;	// 結果受取URL
					$trans->nodes["ORDER_TYPE"] = "M";					// 収納代行業者指定
					$trans->nodes["PRODUCT_NAME"] = SITE_TITLE . " POINT";		// 商品名 ( 固定 )
					$trans->nodes["PRODUCT_ID"] = $lastno;				// 商品コード ( purchase_no )
					
					$trans->nodes["USER_ACCTID"] = $member_no;			// UserAccount ( member_no )
					// keyからERQCを作成
					$erqc = $trans->GetERQC( MERCHANT_PASSWORD, MERCHANT_KEY1, MERCHANT_KEY2);
					// トランザクションに設定
					$trans->nodes["ERQC"] = $erqc;
					
					// 送信用データの取得
					$data = $trans->GetSendData();

					$this->set( "data",          $data );
					$this->set( "cookie",        $lastno );				//互換用にcookieに購入番号をセット
					$this->set( "sendid",        $member_no );			//互換用にsendidにmember_noをセット
				}
			}
		}
		
		//コミット
		$this->_DB->autoCommit(true);

		return( true );
	}

	/**
	 * 決済request用結果処理(p99 order)
	 * @access  public
	 * @param	array	$post				$_POST情報
	 * @return  							true: "0" false: その他 1桁は基本エラー それ以外は決済会社のエラー
	 */
	function updatePurchase($post){
	
		if ( PAYMENT_NAME == "gash"){
			//URLエンコードで+がspaceに変換されるのでもとに戻す。
			$trans = new Trans( str_replace(" ", "+", $post["data"]) );
			//$trans = new Trans( $post["data"] );
			$json_data = $trans->nodes;
			$str_decode = base64_decode( $post["data"] );
			$this->_json_data = $json_data;
		}
		if ( PAYMENT_NAME == "p99"){
			try {
				$str        = urldecode($post["data"]);
				$str_decode = base64_decode($str);
				$json_data  = json_decode($str_decode,true);
			} catch (Exception $e) {
				return "1";
			}

			$this->_json_data = $json_data;
		}
		//ERPCの作成
		$trans = new Trans( null );
		$trans->nodes["CID"]    = $json_data["CID"];
		$trans->nodes["COID"]   = $json_data["COID"];
		$trans->nodes["RRN"]    = $json_data["RRN"];
		$trans->nodes["CUID"]   = $json_data["CUID"];
		$trans->nodes["AMOUNT"] = $json_data["AMOUNT"];
		$trans->nodes["RCODE"]  = $json_data["RCODE"];
		$this->_erpc = $trans->GetERPC( MERCHANT_KEY1, MERCHANT_KEY2);

		//購入履歴が存在しているかを確認
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("purchase_no,member_no,purchase_type,amount,point,result_status")
				->from("his_purchase")
				->where()
					->and( "purchase_no = ",    $json_data["PRODUCT_ID"], FD_NUM)
					->and( "member_no = ",      $json_data["USER_ACCTID"], FD_NUM)
					->and( "amount = ",         $json_data["AMOUNT"], FD_NUM)
	//				->and( "result_status = ",  "0", FD_NUM)
			->createSQL("\n");
		$purchaseRow = $this->_DB->getRow($sql);
		if ( mb_strlen($purchaseRow["purchase_no"]) == 0 ){
			//読み込みエラー
			return "2";
		}

		if ( $json_data["PAY_STATUS"] == "S" ){
			//決済成功
			$resultCode = "1";
			$message = $str_decode;
			$mode = "end";
		} else {
			//決済失敗
			$resultCode = "9";
			$message = $str_decode;
			$mode = "fail";
		}
		//ERPC比較
		if ( $this->_erpc != $json_data["ERPC"] ){
			$resultCode = "9";
			$message = $str_decode . "calc:".$this->_erpc;
			$mode = "fail";
		}

		if ( $purchaseRow["result_status"] != "0" ){
			//既に更新済み（gashResultAPI.phpで実行済み）
			$err = $json_data["RCODE"];
		} else {
			$orderCode = ($resultCode == "1")? "2" : $resultCode;
		
			// トランザクション開始
			$this->_DB->autoCommit(false);

			$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
				->update("his_purchase")
					->set()
						//Settle待ち
						->value("result_status",    $orderCode, FD_NUM)
						->value("result_message",   $message, FD_STR)
						->value("purchase_dt",      "current_timestamp", FD_FUNCTION)
					->where()
						->and( "purchase_no = ",    $purchaseRow["purchase_no"], FD_NUM)
				->createSQL("\n");
			$ret = $this->_DB->query($sql);

			if ( $ret->rowCount() == 0 ){
				$this->_DB->rollBack();
				return "3";
			}

			//ここで非同期処理用にコミット
			$this->_DB->autoCommit(true);

			if ( $orderCode == "2" ){

				//Settle
				if ( $this->settle() ){
					$settleResult = "1";
				} else {
					$settleResult = "9";
				}

				//API 未実行の場合
				// トランザクション開始
				$this->_DB->autoCommit(false);

				$resultMessage = "{\"result\":[".$message . "," . $this->_settlejson . "]}";

				$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
					->update("his_purchase")
						->set()
							->value("result_status",    $resultCode, FD_NUM)
							->value("result_message",   $resultMessage, FD_STR)
							->value("purchase_dt",      "current_timestamp", FD_FUNCTION)
						->where()
							->and( "purchase_no = ",    $purchaseRow["purchase_no"], FD_NUM)
					->createSQL("\n");
				$ret = $this->_DB->query($sql);
				if ( $ret->rowCount() == 0 ){
					$this->_DB->rollBack();
					return "3";
				}

				$err = "0";
				//決済成功時のみポイントを計算する
				if ( $resultCode == "1" && $settleResult == "1"){
					//point classをno commit modeで作成
					$PPOINT  = new PlayPoint( $this->_DB, false );
					
					//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
					if ( $PPOINT->addAmount2Point( $purchaseRow["member_no"], $purchaseRow["purchase_type"], $purchaseRow["amount"], $purchaseRow["purchase_no"], "" ) ){
						//コミット
						$this->_DB->autoCommit(true);
					} else {
						$this->_DB->rollBack();
						$err = "9";
					}
				} else {
					//コミット
					$this->_DB->autoCommit(true);
					$err = $json_data["RCODE"];
				}
				//settileでerrorの場合はエラーコードを変更
				if ( $settleResult == "9" ) $err = $this->_settleError;
			}
		}

		//エラーコードを返す
		if ( $json_data["RCODE"] != "0" ){
			$err = $json_data["RCODE"];
		}
		return $err;
	}

	/**
	 * 決済request用結果処理(PayPal)
	 * @access  public
	 * @param	array	$capture			$capture結果情報
	 * @return  							true: "0" false: その他 1桁は基本エラー それ以外は決済会社のエラー
	 */

	function updatePurchasePayPal($capture) {

		// 既に決済済みの場合は処理しない
		if (array_key_exists("issue", $capture)){
			return $capture["issue"];
		}
		if (array_key_exists("error", $capture)){
			return $capture["error"];
		}


		//購入履歴が存在しているかを確認
		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->select()
				->field("purchase_no,member_no,purchase_type,amount,point,result_status")
				->from("his_purchase")
				->where()
					->and( "purchase_no = ",    $capture["invoice_id"], FD_NUM)
					->and( "member_no = ",      $capture["custom_id"], FD_NUM)
					->and( "amount = ",         $capture["amount"]["value"], FD_NUM)
			->createSQL("\n");
		$purchaseRow = $this->_DB->getRow($sql);
		if ( mb_strlen($purchaseRow["purchase_no"]) == 0 ){
			//読み込みエラー
			return "2";
		}
		// 既に実行済みの場合はステータスを返す
		if ($purchaseRow["result_status"] != "0"){
			return $purchaseRow["result_status"];
		}

		if ($capture["status"] == "COMPLETED") {
			$status = "1";
		} else {
			$status = "9";
		}
		// トランザクション開始
		$this->_DB->autoCommit(false);

		$sql = (new SqlString())->setAutoConvert( [$this->_DB,"conv_sql"] )
			->update("his_purchase")
				->set()
					//Settle待ち
					->value("result_status",    $status, FD_NUM)
					->value("result_message",   $capture["status"] . ":" . $capture["id"] , FD_STR)
					->value("purchase_dt",      "current_timestamp", FD_FUNCTION)
				->where()
					->and( "purchase_no = ",    $capture["invoice_id"], FD_NUM)
			->createSQL("\n");
		$ret = $this->_DB->query($sql);

		if ( $ret->rowCount() == 0 ){
			$this->_DB->rollBack();
			return "3";
		}

		$err = $status;
		//決済成功時のみポイントを計算する
		if ($status == "1"){
			//point classをno commit modeで作成
			$PPOINT  = new PlayPoint( $this->_DB, false );
			
			//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
			if ( $PPOINT->addAmount2Point( $purchaseRow["member_no"], $purchaseRow["purchase_type"], $purchaseRow["amount"], $purchaseRow["purchase_no"], "" ) ){
				//コミット
				$this->_DB->autoCommit(true);
			} else {
				$this->_DB->rollBack();
				$err = "8";
			}
		} else {
			//コミット
			$this->_DB->autoCommit(true);
			$err = $status;
		}

		return $err;
	}

	/**
	 * settleの問い合わせと結果を取得(p99のみ）
	 * @access  public
	 * @return  							true: 成功  false: 失敗
	 */
	function settle(){
		
		// 取引情報コード
		$trans = new Trans( null );
		$trans->nodes["MSG_TYPE"] = "0500"; 						// 取引情報コード
		$trans->nodes["PCODE"]    = "300000"; 						// トランザクション処理コード 
		$trans->nodes["CID"]      = CONTENTSERVICE_ID;				// マーチャントゲームコード
		$trans->nodes["COID"]     = $this->_json_data["COID"];		// マーチャントオーダー番号
		$trans->nodes["CUID"]     = $this->_json_data["CUID"];		// 通貨
		$trans->nodes["PAID"]     = $this->_json_data["PAID"];		// 支払いコレクターコード (支払方法）
		$trans->nodes["AMOUNT"]   = $this->_json_data["AMOUNT"];	// 取引金額
		// keyからERQCを作成
		$trans->nodes["ERQC"]     = $trans->GetERQC( MERCHANT_PASSWORD, MERCHANT_KEY1, MERCHANT_KEY2);

		//送信
		
		if ( PAYMENT_NAME == "gash" ){
			$serviceURL = PAYMENT_SETTLE_URL;
			
			// 進行請款
			$client = new SoapClient($serviceURL);
			$result =  $client->getResponse( array( "data" => $trans->GetSendData() ) );
			
			// 取得結果
			$transData = $result->getResponseResult;
		
		}
		if ( PAYMENT_NAME == "p99" ){
			$post = "data=" . $trans->GetSendData();

			$curl=curl_init(PAYMENT_URL);
			curl_setopt($curl, CURLOPT_HEADER,FALSE);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			
			$result = curl_exec($curl);
			curl_close($curl);

			$transData = urldecode($result);
		}

		//結果を判定
		$trans = new Trans( $transData );

		
		$isSuccess = ($trans->nodes["RCODE"] == "0000");
		$isCorrect = false;
		
		if ( $isSuccess ) {
			$isCorrect = ( $trans->VerifyERPC( MERCHANT_KEY1, MERCHANT_KEY2 ) );
		}
		//結果情報をプロパティに設定
		$this->_settlejson = json_encode($trans->nodes);
		$this->_settleError = $trans->nodes["RCODE"];
		
		return $isCorrect;
	}

	/**
	 * 複合化後json取得
	 * @access	public
	 * @return	array				複合化後JSON配列
	 * @info
	 */
	function getJsonData(){
		return $this->_json_data;
	}

	/**
	 * パスワード暗号化処理
	 * @access	private
	 * @param	string	$data		複合化したい文字列
	 * @return	string				複合化文字列
	 * @info
	 */
	function encrypt($data) {

		// 暗号化キー
		$key = ENCRYPT_KEY;
		// 暗号化方式(openssl_get_cipher_methods関数で使用可能な方式を要確認)
		$method = ENCRYPT_METHOD;

		// 暗号化
		$encrypted = openssl_encrypt($data, $method, $key);
		//uri用にbase64でエンコード
		$encrypted = base64_encode( $encrypted );

		return $encrypted;
	}

	/**
	 * パスワード複合化処理
	 * @access	private
	 * @param	string	$data		複合化したい文字列
	 * @return	string				複合化文字列
	 * @info
	 */
	function decrypt($data) {

		//uri用にbase64でデコード
		$decdata = base64_decode( $data );

		// 暗号化キー
		$key = ENCRYPT_KEY;
		// 暗号化方式(openssl_get_cipher_methods関数で使用可能な方式を要確認)
		$method = ENCRYPT_METHOD;

		// 復号化
		$decrypted = openssl_decrypt($decdata, $method, $key);

		return $decrypted;
	}




	/**
	 * エラーの取得
	 * @access  public
	 * @param	string		$del			区切り文字 "\n"
	 * @return  							エラー文字列
	 */
	public function getError() {
		return( $this->error );
	}

	/**
	 * エラーの設定
	 * @access  public
	 * @param	string		$message		エラーメッセージ
	 * @return  							なし
	 */
	private function _resetError() {
		$this->error = "";
	}

	/**
	 * エラーの設定
	 * @access  public
	 * @param	string		$message		エラーメッセージ
	 * @return  							なし
	 */
	private function _setError( $message ) {
		$this->error = $message;
	}

}

?>