<?php
/**
 * シグナリングサーバ oneTimeAuthIDの登録
 * 
 * oneTimeAuthIDの登録
 * 
 * @package
 * @author   村上俊行
 * @version  1.0
 * @since    2016/08/05 初版作成 村上俊行
 */


define( "SQLITE_DSN", "sqlite:/usr/src/peerjs-server/db/authdb.db" );				//DB保管場所
define( "AUTHIDNAME", "oneTimeAuthID" );					//受け取りID名

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
			case "add":							//ID追加処理
				$ret = EntryData();
				break;
			case "get":							//ID取得処理
				$ret = GetData();
				break;
			case "del":							//ID削除処理
				$ret = DeleteData();
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
 * IDの取得
 * @access	private
 * @param	なし
 * @return	ID値を返す
 */
function getID() {
	// $_GETのAUTHIDNAME の値が正しいかをチェック
	if ( !preg_match("/^[0-9A-Za-z]+$/", $_GET[AUTHIDNAME] ) ){
		return( "" );
	} else {
		return( $_GET[AUTHIDNAME] );
	}
}

/**
 * IDの登録
 * @access	private
 * @param	なし
 * @return	array	結果配列
 */
function EntryData() {

	$ret = array();

	$record = array();
	$record["onetimeauthid"] = getID();
	$record["add_date"]      = date("Y-m-d H:i:s");

	if ( $record["onetimeauthid"] == "" ){
		$ret["status"] = "ng";
		$ret["error"]  = "id error";
		return( $ret );
	}

	$pdo = Sqlite3_open();
    $query = $pdo->prepare("INSERT INTO dat_auth (onetimeauthid, add_date) VALUES (:onetimeauthid, :add_date)");
    $query->execute( $record );

	$ret = array();
	$ret["status"] = "ok";
	
	return( $ret );
}

/**
 * ID一覧の取得
 * @access	private
 * @param	なし
 * @return	array	結果配列
 */
function GetData() {

	$ret = array();

	$pdo = Sqlite3_open();

    $result = $pdo->query("SELECT * FROM dat_auth;", PDO::FETCH_ASSOC);

	$ret["records"] = array();
	foreach($result as $row) {
		$ret["records"][] = $row;
	}

	$ret["status"] = "ok";

	return( $ret );
}

/**
 * IDの削除
 * @access	private
 * @param	なし
 * @return	array	結果配列
 */
function DeleteData() {

	$ret = array();

	$record = array();
	$record["onetimeauthid"] = getID();
	if ( $record["onetimeauthid"] == "" ){
		$ret["status"] = "ng";
		$ret["error"]  = "id error";
		return( $ret );
	}

	$pdo = Sqlite3_open();

    $query = $pdo->prepare("DELETE FROM dat_auth WHERE onetimeauthid=:onetimeauthid");
    $query->execute( $record );

	$ret["status"] = "ok";

	return( $ret );

}

/**
 * Sqlite3のOPEN、ない場合は作成される
 * @access	private
 * @param	なし
 * @return	object	PDOインスタンス
 */
function Sqlite3_open() {
	$pdo = new PDO(SQLITE_DSN);
	$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	//指定したファイルの中に該当テーブルがない場合に作成する
	$pdo->exec(
		"CREATE TABLE IF NOT EXISTS dat_auth(
        	onetimeauthid id varchar(64),
        	add_date varchar(20)
		)"
    );

	return( $pdo );
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
