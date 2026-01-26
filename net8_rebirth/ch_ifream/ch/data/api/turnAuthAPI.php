<?php
/**
 * ターンサーバ oneTimeAuthIDの取得
 * 
 * oneTimeAuthIDの登録
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since    2019/02/14 初版作成 村上俊行
 */

/*
	解説
	turnserverへの認証ID登録はturnadminを使用するが、phpの外部コマンドでこの命令を実行しても
	正しくユーザーが追加されない（DBに書き込みされない）。
	rootでしかこのコマンドは有効にならないようなので、セキュリティの関係上外部から操作しない。
	代わりにrootで一定時間ごとにキーを生成し、生存期間（6時間から24時間）を設定した
	user,passを取得する。
*/


define( "TURN_NEWKEY",    "/etc/turnserver/turnkey/tkey.txt" );			//一番生存時間が長いキー情報
define( "AUTHIDNAME",     "turnUserID" );								//受け取りID名
define( "AUTHPASSNAME",   "turnUserPass" );								//受け取りPASS名

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
		$ret = array();
		switch($_GET["M"]){
			case "get":							//ID取得
				$ret = GetUserData();
				break;
			default :							//modeエラー
				$ret["status"] = "ng";
				$ret["error"]  = "mode no define";
		}
	} catch (Exception $e) {
		$ret["status"] = "ng";
		$ret["error"]  = $e->getMessage();
	}

	outputJson( $ret );

}

/**
 * IDの登録
 * @access	private
 * @param	なし
 * @return	array	結果配列
 */
function GetUserData() {

	$ret = array();
	$output = "";
	
	if ( !file_exists( TURN_NEWKEY ) ){
		$ret["status"] = "ng";
		$ret["error"]  = "not id file";
		return( $ret );
	}
	$rec = file_get_contents( TURN_NEWKEY );
	$itm = explode( " ", $rec );
	//$itm[0] = userid $itm[1] = pass
	$ret["status"]     = "ok";
	$ret[AUTHIDNAME]   = $itm[0];
	$ret[AUTHPASSNAME] = $itm[1];
	
	return( $ret );
}

/**
 * json出力
 * @access	private
 * @param	array	json化したい連想配列
 * @return	なし
 */
function outputJson( $ret ) {
	$json = json_encode( $ret );
	
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
