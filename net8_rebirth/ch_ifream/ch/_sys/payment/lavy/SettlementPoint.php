<?php
/*
 * SettlementPoint.php
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
 * PlayPoint class
 * 
 * PplayPointの処理に関する各種命令
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/03/06 初版作成 村上俊行
 * @info
 */


class SettlementPoint {

	// メンバ変数定義
	private $_DB;												//DBインスタンス
	private $_nowDate;											//設定で使う本日日時
	private $_nowPoint;
	private $_error = "";
	private $_setting = array();
	
	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct( $DB ) {
		// メンバ変数へ格納
		$this->_DB = $DB;
		$this->_nowDate = Date("Y-m-d H:i:s");
		$this->setting = array();
	}

	public function getAll() {
		return( $this->_setting );
	}

	public function set($name, $value){
		$this->_setting[$name] = $value;
	}

	/**
	 * 決済request用のタグを返す
	 * @access  public
	 * @return  							hiddenタグ
	 */
	public function hiddenTag(){
		$tags =  "<input type=\"hidden\" name=\"IP_CODE\" value=\"{%IP_CODE%}\">\n"
				."<input type=\"hidden\" name=\"cookie\" value=\"{%COOKIE%}\">\n"
				."<input type=\"hidden\" name=\"price\" value=\"{%PRICE%}\">\n"
				."<input type=\"hidden\" name=\"sendid\" value=\"{%SENDID%}\">\n"
				."<input type=\"hidden\" name=\"payment_code\" value=\"{%PAYMENT_CODE%}\">\n"
				."<input type=\"hidden\" name=\"email\" value=\"{%EMAIL%}\">\n";
		return $tags;
	}

	/**
	 * 決済request用のレコードを作る
	 * @access  public
	 * @param	int		$member_no			会員NO
	 * @param	int		$purchase_type		購入方法
	 * @param	int		$amount				購入金額
	 * @param	int		$keyno				参照番号
	 * @param	int		$limit_dt			ポイント有効期限
	 * @param	string	$reason				加減算理由
	 * @param	int		$add_no				登録者
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
		if ( mb_strlen($purchaseRow["point"]) == 0 ){
			$this->setError( "mst_purchasePoint not found" );
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
			$this->setError( "mst_member not found" );
			return( false );
		}

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
					->value("recept_dt",      Date("Y-m-d H:i:s"), FD_DATE)
					->value("purchase_type",  $purchase_type, FD_NUM)
					->value("amount",         $amount, FD_NUM)
					->value("point",          $purchaseRow["point"], FD_NUM)
					->value("result_status",  $status, FD_NUM)
					->value(true, "purchase_dt", $purchase_dt, FD_DATE)
			->createSQL("\n");
		$result = $this->_DB->query($sql);

		if ( $result->rowCount() == 0 ){
			$this->_DB->rollBack();
			$this->setError( "his_purchase insert error" );
			return( false );
		} else {
			$lastno = $this->_DB->lastInsertId('purchase_no');
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
						$this->setError( $PPOINT->getError() );
						$this->_DB->rollBack();
						return( false );
					}
					//point加算（会員番号,加算種別,金額,参照key,有効期限）※通常購入は期限なしで設定
					if ( !$PPOINT->addAmount2Point( $member_no, $purchase_type, $amount, $lastno, "" ) ){
						$this->setError( $PPOINT->getError() );
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
				//その他決済の場合
				$cryptCookie = $this->encrypt( $lastno."|". $member_no );
				$this->set( "IP_CODE",          SETTLE_IP_CODE );
				$this->set( "cookie",           $lastno );
				$this->set( "price",            $amount );
				$this->set( "sendid",           $member_no );
				$this->set( "payment_code" ,    $GLOBALS["Payment_Code_ConvertArray"][$purchase_type] );
				$this->set( "email" ,           $memberRow["mail"] );
				$this->set( "crypt_cookie" ,    $cryptCookie );
				$this->set( "crypt_sendid" ,    sprintf( SETTLE_SENDID_FORMAT, $member_no ) );

				if ( SETTLE_DEBUG ){
					$dummyurl = "../api_public/lavyResultAPI.php?cookie={$cryptCookie}&price={$amount}&rel=1";
				} else {
					$dummyurl = "";
				}
				$this->set( "dummyurl" ,       $dummyurl );
			}
		}
		
		//コミット
		$this->_DB->autoCommit(true);

		return( true );
	}

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