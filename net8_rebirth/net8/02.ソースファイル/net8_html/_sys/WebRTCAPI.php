<?php
/*
 * WebRTCAPI.php
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
 * webRTC API
 * 
 * webRTC用のAPI作成に必要な各種命令などを行う
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2019/02/08 初版作成 村上俊行
 * @since	 2020/09/18 修正     村上俊行 シグナリングサーバのPort対応
 * @info
 */


class WebRTCAPI {

	// メンバ変数定義
	private $_signaling = array();								//シグナリングサーバリスト
	private $_stun = array();									//stunサーバリスト
	private $_turn = array();									//turnサーバリスト
	private $_errorMessage = array();							//エラーメッセージ
	
	private $_oneTimeAuthID = "";								//発行したoneTimeAuthID
	private $_oneTimeAuthPASS = "";
	private $_turnUserID = "";									//APIで受け取ったturnUserID
	private $_turnUserPass = "";								//APIで受け取ったtuenUserPass

	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct() {
		// メンバ変数へ格納
		
		$this->_signaling["servers"] = $GLOBALS["RTC_Signaling_Servers"];
		$this->_signaling["api"]     = $GLOBALS["RTC_Signaling_APIURL"];
		$this->_stun["servers"]      = $GLOBALS["RTC_Stun_Servers"];
		$this->_turn["servers"]      = $GLOBALS["RTC_Turn_Servers"];
		$this->_turn["api"]          = $GLOBALS["RTC_Turn_APIURL"];
	}

	/**
	 * エラー出力
	 * @access  public
	 * @return  string		エラーメッセージ
	 */
	public function errorMessage() {
		return( implode( "\n", $this->_errorMessage ) );
	}

	/**
	 * IceServers配列の生成
	 * @access  public
	 * @return  string		camera			カメラのコードがないとsignoが取得できない
	 * @return  boolean		serverflg		false:user側 true:サーバ側（固定の値）
	 */
	public function getIceServers( $camera, $serverflg=false ) {
		$servers = array();
		foreach( $this->_stun["servers"] as $stun ){
			$servers[] = "{ urls: 'stun:{$stun}'}";
		}
		foreach( $this->_turn["servers"] as $turn ){
			if ( $this->getTurnIDPASS( $serverflg ) ){
				$servers[] = "{ urls: 'turn:{$turn}','username':'{$this->_turnUserID}','credential':'{$this->_turnUserPASS}'}";
			}
		}
		return( "[".implode( ",", $servers )."]");
	}

	/**
	 * ワンタイムID生成
	 * @access  public
	 * @return  string		ID
	 */
	public function getOneTimeAuthID() {
		//ユニークな文字列生成
		$this->_oneTimeAuthID = sha1(uniqid(rand(), true));

		return( $this->_oneTimeAuthID );
	}
	/**
	 * ワンタイムPASS生成
	 * @access  public
	 * @return  string		PASS
	 */
	public function getOneTimeAuthPASS() {
		$this->_oneTimeAuthPASS = sha1(uniqid(rand(), true));

		return( $this->_oneTimeAuthPASS );
	}

	/**
	 * シグナリングサーバにoneTimeAuthIDを登録する
	 * @access  public
	 * @param   string  $id			oneTimeAuthID
	 * @return  boolean				true:正常終了 false:APIでエラー
	 */
	public function addKeySignaling( $id, $targetSig ) {
		$result = true;
		foreach( $this->_signaling["servers"] as $idx => $sig ) {
			if ( $targetSig != $idx ) continue;
			//2020-09-18 port付きになったのでportを外す
			$sigurl = explode(":", $sig);
			$url  = sprintf( $this->_signaling["api"], $sigurl[0] );
			//$url  = sprintf( $this->_signaling["api"], $sig );
			$url .= sprintf( "?M=add&oneTimeAuthID=%s", $id );
			$ret = $this->_geturl( $url );
			if ( $ret["status"] != "ok" ){
				$this->_errorMessage[] = $url." [{$ret["error"]}]\n";
				$result = false;
			}
		}
		return( $result );
	}

	/**
	 * turnサーバからturnUserIDとturnUserPassを取得する
	 * @access  public
	 * @return  boolean				true:正常終了 false:APIでエラー
	 */
	public function getTurnIDPASS( $serverflg=false) {
		$result = true;
		foreach( $this->_turn["servers"] as $idx => $turn ) {
			$turnurl = explode( ":", $turn );
			$url  = sprintf( $this->_turn["api"], $turnurl[0] );
			if ( $serverflg ) {
				$url .= sprintf( "?M=server" );
			} else {
				$url .= sprintf( "?M=get" );
			}
			$ret = $this->_geturl( $url );
			if ( $ret["status"] != "ok" ){
				$this->_errorMessage[] = $url." [{$ret["error"]}]\n";
				$result = false;
			}
			//2020-06-24 Notice対応
			$this->_turnUserID   = ( array_key_exists("turnUserID", $ret) ) ? $ret["turnUserID"] : "";
			$this->_turnUserPASS = ( array_key_exists("turnUserPass", $ret) ) ? $ret["turnUserPass"] : "";
			//$this->_turnUserID   = $ret["turnUserID"];
			//$this->_turnUserPASS = $ret["turnUserPass"];
		}
		return( $result );
	}

	/**
	 * webRTC対応ブラウザのチェック
	 * @access  public
	 * @param   boolean  $mode			true:結果を連想配列で受け取る false:使用可能どうかをbooleanで返す
	 * @return  boolean($mode=false)	true:使用可能 false:不可
	 * @return  array($mode=true)		連想配列 "name":ブラウザ名 "version":バージョン "status":true:使用可能 false:不可
	 */
	public function checkBrowser($mode=true) {
		//ブラウザチェック
		$browserFlg = false;
		$browserName = "";
		$browserVersion = 0;
		//Chrome 30以上
		if ( preg_match("/Chrome\/([0-9]+)/", $_SERVER['HTTP_USER_AGENT'], $argv ) ){
			$browserName = "Chrome";
			$browserVersion = $argv[1];
			if ( $browserVersion >= 30 ) $browserFlg = true;
		}
		//Firefox 22以上
		if ( preg_match("/Firefox\/([0-9]+)/", $_SERVER['HTTP_USER_AGENT'], $argv ) ){
			$browserName = "Firefox";
			$browserVersion = $argv[1];
			if ( $browserVersion >= 22 ) $browserFlg = true;
		}
		//Safari 11.0以上
		if ( preg_match("/Version\/([0-9\.]+) Safari\//", $_SERVER['HTTP_USER_AGENT'], $argv ) ){
			$browserName = "Safari";
			$browserVersion = $argv[1];
			if ( $argv[1] >= 11.0 ) $browserFlg = true;
		}
		//Mobile Safari 11.0以上
		if ( preg_match("/Version\/([0-9\.]+) Mobile\/[0-9A-Za-z]+ Safari\//", $_SERVER['HTTP_USER_AGENT'], $argv ) ){
			$browserName = "Mobile Safari";
			$browserVersion = $argv[1];
			if ( $argv[1] >= 11.0 ) $browserFlg = true;
		}
		//Edge ( ChromeがUSER AGENTに含まれているので Edgeの文字があればNGとする）
		if ( preg_match("/Edge\//", $_SERVER['HTTP_USER_AGENT'] ) ){
			$browserFlg = false;
		}
		if ( $mode == false ){
			return( $browserFlg );
		}

		$ary["status"] = $browserFlg;
		$ary["name"] = $browserName;
		$ary["version"] = $browserVersion;

		return( $ary );
	}

	/**
	 * curlでAPIにアクセスする
	 * @access  public
	 * @param   string  $url		API url
	 * @return  array				APIからの結果を連想配列で返す
	 */
	private function _geturl( $url ) {
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => false,  // Changed to false to get response body on error
			CURLOPT_TIMEOUT => 10,
			CURLOPT_CONNECTTIMEOUT => 5,
		]);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$response = curl_exec($ch);
		$errno = curl_errno($ch);
		$error_msg = curl_error($ch);
		$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);

		if ( CURLE_OK !== $errno ){
			error_log("WebRTCAPI _geturl error - URL: {$url}, errno: {$errno}, error: {$error_msg}, http_code: {$http_code}");
			$response = json_encode([
				"status" => "ng",
				"error" => "_geturl error",
				"curl_errno" => $errno,
				"curl_error" => $error_msg,
				"http_code" => $http_code,
				"url" => $url
			]);
		}

		// レスポンスがJSON形式でない場合（Peer IDなど）
		$decoded = json_decode($response, true);
		if (json_last_error() === JSON_ERROR_NONE) {
			return $decoded;
		} else {
			// JSONでない場合は、成功とみなしてステータスをokにする
			return ["status" => "ok", "data" => $response];
		}
	}

}

?>