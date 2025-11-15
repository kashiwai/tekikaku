<?php
/*
 * index.php (Public Viewer - ログイン不要版)
 *
 * 視聴専用ページ（複数人が同時視聴可能、ログイン不要）
 *
 * @version  2.0
 * @since    2025/11/06 ログイン不要版作成
 */

// インクルード
require_once('../../_etc/require_files.php');			// requireファイル

require_once('../../_sys/WebRTCAPI.php');				// requireファイル
require_once('../../_etc/webRTC_setting.php');			// webRTCセッティングファイル

// 項目定義
define("PUBLIC_HTML",  "play/index_public_millionnet8");	// MillionNet8ベースの視聴専用テンプレート
define("ERR_HTML",     "play/no_assign");				// テンプレートHTMLプレフィックス(エラー時）

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
		// 画面表示
		DispTop($template);

	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * VIEW画面表示（視聴専用・ログイン不要）
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {

	//webRTCのauth設定
	$webRTC = new WebRTCAPI();

	// データ取得
	getData($_GET, array("NO"));

	// マシン番号チェック
	if (empty($_GET["NO"])) {
		DispError($template, "マシン番号が指定されていません");
		return;
	}

	$jsDir = "/data/play_v2/js";

	//ブラウザチェック
	$browserStatus = $webRTC->checkBrowser(true);
	if ( $browserStatus["status"] == false ){
		DispError( $template, "お使いのブラウザはサポートされていません。Chrome、Edge、Safariをご利用ください。" );
		return;
	}

	// 視聴専用：member_no=0のハッシュを使用（userAuthAPI.phpで検証される）
	$memberNo = sha1(sprintf("%06d", 0));  // sha1("000000") = "c984aed014aec7623a54f0591da07a85fd4b762d"

	//ワンタイムパスの発行
	$oneTimeAuthID = $webRTC->getOneTimeAuthID();

	// lnk_machineテーブルを視聴専用モードに設定（userAuthAPI.phpで検証される）
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "lnk_machine" )
			->set()
				->value( "member_no", "0", FD_NUM)       // 視聴専用: member_no=0
				->value( "assign_flg", "9", FD_NUM)      // カメラ待機中
				->value( "onetime_id", $oneTimeAuthID, FD_STR)
			->where()
				->and( "machine_no =", $_GET["NO"], FD_NUM)
		->createSQL("\n");

	$template->DB->query($sql);

	//台情報の取得
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.machine_no,dm.signaling_id,dm.camera_no,mm.category,mm.prizeball_data,mm.layout_data,mc.camera_name,mm.image_reel")
			->field("mm.model_name,mm.model_roman,mm.model_cd")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no" )
			->where()
				->and( "dm.machine_no =", $_GET["NO"], FD_NUM)
				->and( "dm.del_flg =", "0", FD_NUM)
		->createSQL("\n");

	$machineRow = $template->DB->getRow($sql);

	if (empty($machineRow) || empty($machineRow["machine_no"])) {
		DispError($template, "指定されたマシンが見つかりません");
		return;
	}

	$prizeball_data = json_decode( $machineRow["prizeball_data"], true );
	$layout_data    = json_decode( $machineRow["layout_data"], true );
	if ( !isset($layout_data["hide"]) ) $layout_data["hide"] = array();

	// 押し順対応[pushorder]が非表示に無い場合、押し順非対応[nonepushorder]を非表示にする
	if (!in_array("pushorder", $layout_data["hide"])) {
		$layout_data["hide"][] = "nonepushorder";
	}

	//接続先の情報
	$camera  = $machineRow["camera_name"];
	$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$machineRow["signaling_id"]]);
	$sighost = $sig[0];
	$sigport = $sig[1];

	//シグナリングサーバへ登録
	if ( !$webRTC->addKeySignaling( $oneTimeAuthID, $machineRow["signaling_id"] ) ){
		DispError( $template, "シグナリングサーバへの接続に失敗しました" );
		return;
	}

	// 画面表示開始（MillionNet8ベースの視聴専用テンプレート使用）
	$template->open(PUBLIC_HTML . ".html");

	$template->assignCommon();

	// WebRTC接続に必要な変数のみ
	$template->assign("CAMERA_ID"       , $camera);
	$template->assign("MACHINE_NO"      , $machineRow["machine_no"]);
	$template->assign("MODEL_NAME"      , $machineRow["model_name"]);
	$template->assign("PEERJSKEY"       , $GLOBALS["RTC_PEER_APIKEY"]);
	$template->assign("MEMBERNO"        , $memberNo );
	$template->assign("AUTHID"          , $oneTimeAuthID);
	$template->assign("SIGHOST"         , $sighost);
	$template->assign("SIGPORT"         , $sigport);
	$template->assign("ICESERVERS"      , $webRTC->getIceServers($camera) );

	$template->flush();
}

/**
 * エラー処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		エラーメッセージ
 * @return	なし
 */
function DispError( $template, $message ) {
	echo "<html><head><meta charset='UTF-8'><title>エラー</title></head><body>";
	echo "<h1>エラー</h1>";
	echo "<p>" . htmlspecialchars($message) . "</p>";
	echo "<p><a href='/'>トップページに戻る</a></p>";
	echo "</body></html>";
}

?>
