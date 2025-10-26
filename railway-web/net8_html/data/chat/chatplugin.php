<?php
/*
 * chat.php
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
 * chatテスト
 * 
 * 
 * @package
 * @author   村上 俊行
 * @version  1.0
 * @since    2020/07/16 流用新規 村上 俊行
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル
require_once('../../_sys/chatAPI.php');					// chatAPI
// 項目定義
define("PRE_DONE_JS",  "chat/done.js");					// テンプレートJS
define("PRE_NG_JS",    "chat/ng.js");					// テンプレートJS
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
		if ( $template->checkSessionUser(false, false) ){
			// 実処理
			putChatJS($template);
		} else {
			//セッションがない場合の処理
			noScript($template);
		}
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

function putChatJS($template){
	//メンバー情報取得
	$row = getMemberInfo($template);
	if ( !$row ){
		//セッションから会員情報が取得できない場合はchatが使用できないscriptを送信
		noScript($template);
		return;
	}

	/*
	$userid = $row["invite_cd"];
	$pass   = substr($row["pass"], 8, 24);

	$chat = new chatAPI();
	if ( !$chat->connect($userid,$pass) ){
		//chatにloginできない
	} else {
		//chatにloginできた
		$template->open(PRE_DONE_JS);
		$template->assign("URL"      , $chat->getBaseURL(), false);
		$template->assign("TOKEN"    , $chat->getToken(),   false);
	}
	*/

	$chat = new chatAPI();
	if ( !$chat->connect() ){
		print( "admin account error" );
		return;
	}
	//情報設定
	$opt = array();
	$opt["email"]    = $row["mail"];
	$opt["name"]     = $row["nickname"];
	$opt["password"] = substr($row["pass"], 8, 24);
	$opt["username"] = $row["invite_cd"];
	//Login & 自動更新
	$ret = $chat->updateUserAccount($opt);
	if ( array_key_exists("status", $ret ) ){
		if ( $ret["status"] == "success" ){
			//chatにloginできた
			
			//部屋情報を取得
			$channels = array();
			$chs = $chat->channelList();
			//print_r( $rooms );
			foreach( $chs["channels"] as $ch ){
				$ary = array();
				if ( array_key_exists("fname", $ch) ){
					$ary["name"]  = $ch["name"];
					$ary["fname"] = $ch["fname"];
					$ary["usersCount"]  = $ch["usersCount"];
					$ary["unread"] = 0;
					$ary["type"]  = 'c';
					$channels[$ary["name"]] = $ary;
				}
			}
			$chat->connect($opt["username"],$opt["password"]);
			$rooms = $chat->roomList();
			//print_r( $rooms );
			foreach( $rooms["update"] as $room ){
				if ( $room["t"] == "d" ){
					//userlistから自分を削除して相手のIDを取得
					$result = array_diff($room["usernames"], array($opt["username"]));
					$result = array_values($result);
					if ( count($result) > 0 ){
						$usr = $chat->userInfo($result[0]);
						//新規DMテスト用
						//if ( $usr["user"]["name"] == 'mura02' ) continue;
						//print_r( $usr );
						$ary = array();
						$ary["name"]  = $room["_id"];
						$ary["fname"] = $usr["user"]["name"];
						$ary["usersCount"]  = 0;
						$ary["unread"] = 0;
						$ary["type"]  = 'd';
						$channels[$ary["name"]] = $ary;
					}
				}
			}
			$template->open(PRE_DONE_JS);
			$template->assign("URL"      , $chat->getBaseURL(), false);
			$template->assign("TOKEN"    , $ret["data"]["authToken"],   false);
			$template->assign("NICKNAME" , $row["nickname"],   false);
			$template->assign("CHANNELS" , json_encode($channels),   false);
			$opt["userId"] = $ret["data"]["userId"];
			$opt["authToken"] = $ret["data"]["authToken"];
			$_SESSION["rcAuthData"] = $opt;
		} else {
			return;
		}
	} else {
		return;
	}

	$template->flush();
}

function noScript($template){
	$template->open(PRE_NG_JS);
	$template->flush();
}


function getMemberInfo($template){
	//セッション情報から
	$sql = (new SqlString())
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("member_no, nickname, mail, pass, invite_cd")
				->from("mst_member")
				->where()
					->and("member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and("state = ", "1", FD_NUM)
			->createSQL();
	$row = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);
	if ( !array_key_exists("member_no", $row) ) {
		return false;
	} else {
		return $row;
	}
}


?>
