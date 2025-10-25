<?php
/*
 * APItool.php
 * 
 * (C)SmartRams Corp. 2008 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * APItool
 * 
 * API用に必要な命令
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/02/27 初版作成 村上俊行
 * @info
 */


class APItool {

	private $nowDate;										//現在の日時
	private $_debug = false;								//デバッグモード
	private $_jsonArray;									//jsonの戻り値生成用配列
	private $_log;											//logのfp
	
	private $OK = "ok";										//OK文字
	private $NG = "ng";										//NG文字
	public $DEBUG   = 10;									//debug
	public $INFO    = 20;									//info
	public $WARNING = 30;

	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct() {
		//初期化
		$this->init();
	}

	/**
	 * 初期化処理
	 * @access  public
	 * @return  						インスタンス
	 */
	public function init() {
		$this->nowDate = Date("Y-m-d H:i:s");
		$this->_jsonArray = array();
		$this->_jsonArray["status"] = $this->OK;
		
		return($this);
	}

	public function set( $key, $value ) {
		$this->_jsonArray[$key] = $value;
		return( $this );
	}

	public function setError( $message="" ){
		$this->_jsonArray["status"] = $this->NG;
		if ( $message != "" ){
			$this->_jsonArray["error"] = $message;
		}
		return( $this );
	}

	/**
	 * JSON出力処理
	 * @access	private
	 * @param	string	$jsonArray	json変換用連想配列
	 * @return	なし
	 */
	function outputJson( $jsonArray="" ) {
		if ( $jsonArray != "" ){
			$json = json_encode( $jsonArray );
		} else {
			//debugModeによるunset
//			if ( !$this->_debug ) {
//				@unset( $this->_jsonArray["sql"] );
//			}
			$json = json_encode( $this->_jsonArray );
		}
		
		// キャッシュコントロール
		header("Expires: Thu, 01 Dec 1994 16:00:00 GMT");
		header("Last-Modified: ". gmdate("D, d M Y H:i:s"). " GMT");
		header("Cache-Control: no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
		//jsonを返す指定
		header("Content-Type: application/json; charset=utf-8");

		print $json;
		
		return( $this );
	}

	function encrypt($data, $method=ENCRYPT_METHOD, $key=ENCRYPT_KEY) {

		// 暗号化キー
		//$key = ENCRYPT_KEY;
		// 暗号化方式(openssl_get_cipher_methods関数で使用可能な方式を要確認)
		//$method = ENCRYPT_METHOD;

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
	function decrypt($data, $method=ENCRYPT_METHOD, $key=ENCRYPT_KEY) {

		//uri用にbase64でデコード
		$decdata = base64_decode( $data );

		// 暗号化キー
		//$key = ENCRYPT_KEY;
		// 暗号化方式(openssl_get_cipher_methods関数で使用可能な方式を要確認)
		//$method = ENCRYPT_METHOD;

		// 復号化
		$decrypted = openssl_decrypt($decdata, $method, $key);

		return $decrypted;
	}

	function pyEncrypt($data, $passphrase, $orgiv="") {
		$secret_key = $passphrase;
		if ($orgiv != ""){
			$iv = $orgiv;
		} else {
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length("aes-256-cbc"));
		}
		$encrypted_64 = openssl_encrypt($data, "aes-256-cbc", $secret_key, 0, $iv);
		$iv_64 = base64_encode($iv);
		$encdata = $encrypted_64;
		if ($orgiv == ""){
			$encdata .= " ".$iv_64;
		}
		return base64_encode(json_encode($encdata));
	} 

	function pyDecrypt($data, $passphrase, $orgiv="") {
		$secret_key = $passphrase;
		$encdata = explode(" ", base64_decode($data));
		if ( $orgiv == "" ){
			$iv = base64_decode($encdata[1]);
		} else {
			$iv = $orgiv;
		}
		$encrypted_64 = $encdata[0];
		$data_encrypted = base64_decode($encrypted_64);
		$decrypted = openssl_decrypt($data_encrypted, "aes-256-cbc", $secret_key, OPENSSL_RAW_DATA, $iv);
		return $decrypted;
	}


}

?>