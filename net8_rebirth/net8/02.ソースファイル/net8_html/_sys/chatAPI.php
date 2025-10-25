<?php
/*
 * chatAPI.php
 * 
 * (C)SmartRams Corp. 2020 All Rights Reserved．
 *
 * 本技術情報には当社の機密情報が含まれておりますので、当社の
 * 書面による承諾がなく第３者に開示することはできません。
 * また、当社の承諾を得た場合であっても、本技術情報は外国為替
 * 及び外国貿易管理法に定める特定技術に該当するため、非居住者
 * に提供する場合には、同法に基づく許可を要することがあります。
 *                                          有限会社 スマート・ラムズ
 *-------------------------------------------------------------------
 * 
 * Rocket.chat API
 * 
 * Rocket.chat REST APIで処理を行う
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since	 2020/07/16 初版作成 村上俊行
 * @since	 2020/09/11 修正     村上俊行      営業時間による送信禁止機能を追加
 * @since	 2020/10/12 修正     村上俊行      retry回数の調整
 * @info
 */


class chatAPI {

	// メンバ変数定義
	private $_baseurl   = CHAT_BASEURL;		//Rocket.chat REST API base
	private $_adminuser = CHAT_APIUSER;
	private $_adminpass = CHAT_APIPASS;
	
	private $_data = array();
	
	private $_authtoken = "";
	private $_authuserid = "";
	private $_token = "";
	private $_userid = "";

	private $_rooms = array();
	
	private $_errorMessage = array();
	
	//実行結果保存
	public  $createLog = array();
	public  $updateLog = array();


	/**
	 * コンストラクタ
	 * @access  public
	 * @return  						インスタンス
	 */
	public function __construct() {
		// メンバ変数へ格納
		
	}

	/**
	 * 営業時間チェックなど送信チェック
	 * @access  public
	 * @param   string  $token		ログイントークン
	 * @return  bool				true: 送信可（営業時間内） false:送信不可（営業時間外）
	 */
	public function isOpentime(){
		$flg = true;
		$nowTime = date("H:i");

		//指定時刻より1分の幅を持つ
		if ( GLOBAL_CLOSE_TIME < $nowTime && GLOBAL_OPEN_TIME > $nowTime){
			$flg = false;
		}

		return $flg;
	}

	/**
	 * authヘッダー用のデータ作成
	 * @access  public
	 * @param   string  $token		ログイントークン
	 * @param   string  $userid		userid
	 */
	public function setAuthData($token, $userid){
		$this->_authtoken  = $token;
		$this->_authuserid = $userid;
		$this->_token     = "X-Auth-Token: ".$this->_authtoken;
		$this->_userid    = "X-User-Id: ".$this->_authuserid;
	}


	/**
	 * エラー設定
	 * @access  public
	 * @param   string  $message	エラーメッセージ
	 */
	private function error($message){
		$this->_errorMessage[] = $message;
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
	 * 指定アカウントを作成(adminのみ）
	 * @access  public
	 * @param   string  $opt		設定連想配列
	 * 						username: 	account名
	 *						email:		メールアドレス
	 *						password:	パスワード
	 *						name:		表示する名前
	 * @return  array or bool		array: login結果 false: エラー
	 */
	public function updateUserAccount($opt){
		$err = array();
		//usernameがない場合はエラー
		if ( !array_key_exists("username", $opt ) ) {
			$err["success"] = false;
			$err["error"]   = "username not found";
			return $err;
		}
		$uid = $this->userExists($opt["username"]);
		if ( !$uid ){
			//userが存在しない場合
			//user作成
			$ret = $this->create($opt);
			if ( !$ret["success"] ){
				$err["success"] = false;
				$err["error"]   = "create error";
				return $err;
			}
		}
		$ret = $this->login($opt["username"], $opt["password"]);
		if ( $ret["status"] != "success" ){
			//エラーになっている場合はパスワードが変わっている
			$upd = array();
			$upd["userId"] = $uid;
			$upd["data"] = array();
			$upd["data"]["email"]    = $opt["email"];
			$upd["data"]["password"] = $opt["password"];
			//user情報を更新
			$ret = $this->userUpdate($upd);
			if ( !$ret["success"] ){
				$err["success"] = false;
				$err["error"]   = "update error";
				return $err;
			}
			//再ログイン
			$ret = $this->login($opt["username"], $opt["password"]);
		}
		
		return $ret;
	}


	/**
	 * connect処理
	 * @access  public
	 * @param   string  $user		ユーザーID（省略した場合は_adminuser)
	 * @param   string  $pass		パスワード（要略した場合は_adminpass)
	 * @return  bool				true: 成功 false: 失敗
	 */
	public function connect($user="", $pass=""){
		if ( $user == "" ) $user = $this->_adminuser;
		if ( $pass == "" ) $pass = $this->_adminpass;
		$opt = $this->login($user, $pass);
		if ( $opt["status"] == "success" ){
			$this->_data = $opt;
			//print_r( $opt );
			$this->_authtoken = $opt["data"]["authToken"];
			$this->_authuserid = $opt["data"]["userId"];
			//接続ヘッダーを作成
			$this->_token     = "X-Auth-Token: ".$opt["data"]["authToken"];
			$this->_userid    = "X-User-Id: ".$opt["data"]["userId"];
			return true;
		} else {
			$this->_admindata = array();
			$this->_token     = "";
			$this->_userid    = "";
			return false;
		}

	}

	/**
	 * login処理
	 * @access  public
	 * @param   string  $user		ユーザーID
	 * @param   string  $pass		パスワード
	 * @return  array	$ret		結果連想配列
	 */
	public function login($userid, $password){
		$opt = array();
		$opt["user"] = $userid;
		$opt["password"] = $password;
		$json = json_encode($opt, true);
		
		$retry = 0;
		$ret["status"] = "";
		while ( $ret["status"] != "success" && $retry < 5 ){
			$ret = $this->_curl_post("/api/v1/login", $json);
			sleep(1);
			$retry++;
		}
		return $ret;
	}

	public function logout(){
		$opt = array();
		$json = json_encode($opt, true);
		
		$ret = $this->_curl_post("/api/v1/logout", $json);

		return $ret;
	}

	/**
	 * create処理
	 * @access  public
	 * @param   array	$opt		オプション設定
	 * @return  array	$ret		結果連想配列
	 */
	public function create($opt){
		$json = json_encode($opt, true);
		$ret = $this->_curl_post("/api/v1/users.create", $json);
		$this->createLog = $ret;
		return $ret;
	}


	public function removeToken($opt){
		$json = json_encode($opt, true);
		$ret = $this->_curl_post("/api/v1/users.removePersonalAccessToken", $json);
		return $ret;
	}

	public function readedMessage($opt){
		$json = json_encode($opt, true);
		//print( $json );
		$data = "?roomId=".$opt["rid"];
		//$ret = $this->_curl_post("/api/v1/subscriptions.read", $json);
		//$ret = $this->_curl_get("/api/v1/subscriptions.read", $data);
		$ret = $this->_curl_get("/api/v1/subscriptions.get", $data);
		print_r( $ret );
		return $ret;
	}

	/**
	 * DMを読む（本人でログイン後でないと取得できない）
	 * @access  public
	 * @param   array	$opt		オプション設定 roomId(DMのrid)
	 * @return  array	$ret		結果連想配列
	 */
	public function getHistory($opt){
		$json = json_encode($opt, true);
		//print( $json );
		$data = "?roomId=".$opt["roomId"];
		$ret = $this->_curl_get("/api/v1/im.messages", $data);
		return $ret;
	}

	/**
	 * userの更新
	 * @access  public
	 * @param   array	$opt		オプション設定
	 * @return  array	$ret		結果連想配列
	 */
	public function userUpdate($opt){
		$json = json_encode($opt, true);
		$ret = $this->_curl_post("/api/v1/users.update", $json);
		$this->updateLog = $ret;
		return $ret;
	}

	/**
	 * userの存在チェック
	 * @access  public
	 * @param   string	$username		username
	 * @return  string or bool	$ret	id: userが存在する false: userが存在しない
	 */
	public function userExists($username){
		$ret = $this->userStatus($username);
		if ( array_key_exists("error", $ret) ){
			return false;
		} else {
			return $ret["_id"];
		}
	}

	/**
	 * userステータス取得
	 * @access  public
	 * @param   string	$roomname	部屋（チャンネル名）
	 * @return  array	$ret		結果連想配列
	 */
	public function userStatus($username){
		$opt = array();
		array_push($opt, "username=". $username);
		$data = "?" . implode("&", $opt);

		$ret = $this->_curl_get("/api/v1/users.getStatus", $data);

		return $ret;
	}

	public function userInfo($username){
		$opt = array();
		array_push($opt, "userId=". $username);
		$data = "?" . implode("&", $opt);

		$ret = $this->_curl_get("/api/v1/users.info", $data);

		return $ret;
	}

	/**
	 * room情報取得
	 * @access  public
	 * @param   string	$roomname	部屋（チャンネル名）
	 * @return  array	$ret		結果連想配列
	 */
	public function roomInfo($roomname){
		$opt = array();
		array_push($opt, "roomName=". $roomname);
		$data = "?" . implode("&", $opt);

		$ret = $this->_curl_get("/api/v1/rooms.info", $data);
		print_r( $ret );
		//ルームのIDを設定
		if ( $ret["success"] == 1 ){
			$this->rooms[$roomname] = $ret["room"]["_id"];
		}

		return $ret;
	}

	/**
	 * channel一覧取得
	 * @access  public
	 * @return  array	$ret		結果連想配列
	 */
	public function channelList(){
		$opt = array();

		$ret = $this->_curl_get("/api/v1/channels.list","");

		return $ret;
	}

	/**
	 * room一覧取得
	 * @access  public
	 * @return  array	$ret		結果連想配列
	 */
	public function roomList(){
		$opt = array();

		$ret = $this->_curl_get("/api/v1/rooms.get","");

		return $ret;
	}

	/**
	 * roomに発言する
	 * @access  public
	 * @param   string	$roomname	部屋（チャンネル名） #チャンネル名 @username でもOK
	 * @param   string	$message	発言メッセージ
	 * @return  array	$ret		結果連想配列
	 */
	public function postMessage($roomname, $message){
		$opt = array();
		//$opt["message"]["rid"] = $this->rooms[$roomname];
		$opt["channel"] = $roomname;
		$opt["text"] = $message;
		$json = json_encode($opt, true);
		
		$ret = $this->_curl_post("/api/v1/chat.postMessage", $json);

		return $ret;
	}

	/***** 一括処理 *****/
	/**
	 * DMを送信
	 * @access  public
	 * @param   array	$opt		必要
	 * @return  string	$ret		結果
	 */
	public function sendDM($method, $opt){
		//2020-09-11 営業時間など設定による送信チェック
		if ( !$this->isOpentime() ) return( "not opentime" );
		if ( !array_key_exists( $method, $GLOBALS["chatBotSend"] ) ) return ( "not define" );
		//有効でなければ処理しない
		if ( !$GLOBALS["chatBotSend"][$method]["active"] ) return( "deactive" );
		//送信
		return( $this->send( $GLOBALS["chatBotSend"][$method]["sendto"], $GLOBALS["chatBotSend"][$method]["template"], $opt) );
	}
	/**
	 * creditを一定値以上取得したらDMを送信
	 * @access  public
	 * @param   array	$opt		必要
	 * @return  string	$ret		結果
	 */
	public function highPointDM($opt){
		//2020-09-11 営業時間など設定による送信チェック
		if ( !$this->isOpentime() ) return( "not opentime" );
		//有効でなければ処理しない
		if ( !$GLOBALS["chatBotSend"]["pay"]["active"] ) return( "deactive" );
		//比較対象データを設定
		//$credit = $opt["out_credit"] - $opt["in_credit"];
		$credit = $opt["credit"];
		$opt["diff_credit"] = $credit;
		//比較
		if ( $credit < $GLOBALS["chatBotSend"]["pay"]["credit"] ){
			return("not send credit {$credit} < {$GLOBALS["chatBotSend"]["pay"]["credit"]}");
		}
		if ( $opt["out_drawpoint"] < $GLOBALS["chatBotSend"]["pay"]["drawpoint"] ) {
			return("not send drawpoint {$opt["out_drawpoint"]} < {$GLOBALS["chatBotSend"]["pay"]["drawpoint"]}");
		}
		//送信
		return( $this->send( $GLOBALS["chatBotSend"]["pay"]["sendto"], $GLOBALS["chatBotSend"]["pay"]["template"], $opt) );

		/*
		if ( !$this->connect(CHAT_INFOUSER, CHAT_INFOPASS) ){
			//エラー
			return( "connect error");
		} else {
			$sends = explode( " ", $GLOBALS["chatBotSend"]["pay"]["sendto"] );
			foreach( $sends as $sendto ){
				$mes = $this->convertMessage($GLOBALS["chatBotSend"]["pay"]["template"], $opt);
				$ret = $this->postMessage($sendto, $mes);
			}
			$this->logout();
		}
		return( "send" );
		*/
	}

	public function send($sendStr, $template, $opt){
		//送信
		if ( !$this->connect(CHAT_INFOUSER, CHAT_INFOPASS) ){
			//エラー
			return( "connect error");
		} else {
			$sends = explode( " ", $sendStr );
			foreach( $sends as $sendto ){
				$mes = $this->convertMessage($template, $opt);
				$ret = $this->postMessage($sendto, $mes);
			}
			$this->logout();
			return( "send" );
		}
	}

	public function convertMessage($tmp, $opt){
		$s = $tmp;
		foreach( $opt as $k => $value ){
			$s = str_replace("%{$k}%", $value, $s);
		}
		return( $s );
	}


	/**
	 * URLを返す
	 * @access  public
	 * @return  string			接続先URL
	 */
	public function getBaseURL(){ return $this->_baseurl; }

	/**
	 * tokenを返す
	 * @access  public
	 * @return  string			接続token
	 */
	public function getToken(){ return $this->_authtoken; }

	/**
	 * useridを返す
	 * @access  public
	 * @return  string			接続userid
	 */
	public function getUserId(){ return $this->_authuserid; }


	/**
	 * cURL用送信用ヘッダー作成
	 * @access  private
	 * @param   bool	$json	true: json返却 false: その他 
	 * @return  array			header array
	 */
	private function _getHeader($json=false) {
		$headers = [];
		if ( $json == true ) array_push($headers, "Content-type:application/json" );
		// X-の追加ヘッダー処理
		if ( $this->_token != "" ) array_push($headers, $this->_token );
		if ( $this->_userid != "" ) array_push($headers, $this->_userid );

		//print_r( $headers );
		return $headers;
	}
	/**
	 * curlでAPIにアクセスする
	 * @access  public
	 * @param   string  $url		API url
	 * @param   string  $data		API url
	 * @return  array				APIからの結果を連想配列で返す
	 */
	private function _curl_post( $url, $data ) {
		$headers = $this->_getHeader(true);
		$ch = curl_init();
		//print_r( $headers );
		//print( $data );
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => true,
		]);
		//json送信GETパラメータで送信
		curl_setopt($ch, CURLOPT_URL, $this->_baseurl.$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		$response =  curl_exec($ch);
		$errno = curl_errno($ch);
		//print( $errno."\n" );
		//print( $response."\n" );
		curl_close($ch);
		if ( CURLE_OK !== $errno ){
			print( $response );
			$response = "{ \"status\": \"ng\", \"error\": \"_geturl error\" \"errno\": {$errno}}";
		}
		return( json_decode($response, true) );
	}

	/**
	 * curlでAPIにアクセスする
	 * @access  public
	 * @param   string  $url		API url
	 * @param   string  $data		API url
	 * @return  array				APIからの結果を連想配列で返す
	 */
	private function _curl_get( $url, $data ) {
		$headers = $this->_getHeader(false);
		$ch = curl_init();
		curl_setopt_array($ch, [
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FAILONERROR => true,
		]);
		//GETパラメータで送信
		$accessUrl = $this->_baseurl.$url.$data;
		//print( $accessUrl . "\n" );
		curl_setopt($ch, CURLOPT_URL, $accessUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$response =  curl_exec($ch);
		$errno = curl_errno($ch);
		//print( $errno."\n" );
		curl_close($ch);
		if ( CURLE_OK !== $errno ){
			$response = "{ \"status\": \"ng\", \"error\": \"_geturl error\"}";
		}
		return( json_decode($response, true) );
	}
}

?>