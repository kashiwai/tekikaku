<?php
/*
 * notice.php
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
 * お知らせページ管理画面表示
 *
 * お知らせページ管理画面の表示を行う
 *
 * @package
 * @author   片岡 充
 * @version  1.01
 * @since    2019/01/30 初版作成 片岡 充
 * @since    2023/11/01 v1.01    岡本 静子 一覧タイトル追加
 */

// デバッグ用エラー表示（問題解決後に削除）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// インクルード
require_once('../../_etc/require_files_admin.php');			// requireファイル

// GCSヘルパー読み込み（エラーハンドリング付き）
$gcsHelperPath = '../../_sys/CloudStorageHelper.php';
if (file_exists($gcsHelperPath)) {
	try {
		require_once($gcsHelperPath);
	} catch (Exception $e) {
		error_log('CloudStorageHelper読み込みエラー: ' . $e->getMessage());
	}
}
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));			// テンプレートHTMLプレフィックス

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
		$template = new TemplateAdmin();
		
		// データ取得
		getData($_GET, array("M"));
		
		// 実処理
		$mainWin = true;
		switch ($_GET["M"]) {
			case "detail":			// 詳細画面
				$mainWin = false;
				DispDetail($template);
				break;
				
			case "regist":			// 登録処理
				$mainWin = false;
				RegistData($template);
				break;
				
			case "end":				// 完了画面
				$mainWin = false;
				DispComplete($template);
				break;
				
			default:				// 一覧画面
				DispList($template);
		}
		
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), $mainWin);
	}
}

/**
 * 一覧画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispList($template, $message = "") {
	
	// データ取得
	getData($_GET , array("P","ODR"		// 2020/04/28 [UPD]
							, "S_NOTICE_NAME","S_LINK_TYPE", "S_TITLE", "S_SUB_TITLE", "S_CONTENTS"));
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	// ソート初期処理
	if (mb_strlen($_GET["ODR"]) == 0) $_GET["ODR"] = "start_dt desc";
	
	//ページングクエリ作成
	$_que = HtmlPagingQueryString( $_GET, array("P"));
	
	//検索判定
	if( ($_GET["S_NOTICE_NAME"]!="") || ($_GET["S_LINK_TYPE"]!="") || ($_GET["S_TITLE"]!="") || ($_GET["S_SUB_TITLE"]!="") || ($_GET["S_CONTENTS"]!="")){
		$_search = "show";
	}else{
		$_search = "";
	}
	
	// 2020/04/28 [UPD Stsrt]
	$lngSql = "";
	if(mb_strlen($_GET["S_TITLE"]) > 0 || mb_strlen($_GET["S_SUB_TITLE"]) > 0 || mb_strlen($_GET["S_CONTENTS"]) > 0) {
		$lngSql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field( "lng.notice_no" )
				->from("dat_notice_lang lng")
				->where()
					->and(SQL_CUT, "lng.title like "    , ["%",$_GET["S_TITLE"],"%"], FD_STR)
					->and(SQL_CUT, "lng.sub_title like ", ["%",$_GET["S_SUB_TITLE"],"%"], FD_STR)
					->and(SQL_CUT, "lng.contents like " , ["%",$_GET["S_CONTENTS"],"%"], FD_STR)
		->createSql("\n");
	}
	// 2020/04/28 [UPD End]

	// DB
	$sqls = new SqlString();
	$csql = $sqls
			->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field( "count(*)" )
			->from("dat_notice dn")
			->where()
				->and("dn.del_flg != ", "1", FD_NUM)
				->and( true, "dn.notice_name like ", ["%",$_GET["S_NOTICE_NAME"],"%"], FD_STR )
				->and( true, "dn.link_type = "     ,  $_GET["S_LINK_TYPE"], FD_NUM );
	if (mb_strlen($lngSql) > 0) $csql = $sqls->subQuery("dn.notice_no", $lngSql);	// 2020/04/28 [UPD]
	$csql = $sqls->createSql("\n");
	
	// カウント取得
	$allrows = $template->DB->getOne( $csql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / ADMIN_LIST_ROWMAX);		// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;
	
	$rsql = $sqls
			->resetField()
			->field("dn.notice_no, dn.notice_name, dn.link_type, dn.link_url, dn.disp_order, dn.start_dt, dn.end_dt, dn.del_flg, dn.upd_dt")
			->page( $_GET["P"], ADMIN_LIST_ROWMAX)
			->orderby( $_GET["ODR"] )
		->createSql("\n");
	
	// データ取得
	$rs = $template->DB->query($rsql);
	
	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	
	$template->assign("S_OPEN"              , $_search, true);
	$template->assign("S_NOTICE_NAME"       , $_GET["S_NOTICE_NAME"], true);
	$template->assign("SEL_LINK_TYPE"       , makeOptionArray($GLOBALS["noticeLinkTypeList"], $_GET["S_LINK_TYPE"], true));
	$template->assign("S_TITLE"             , $_GET["S_TITLE"], true);
	$template->assign("S_SUB_TITLE"         , $_GET["S_SUB_TITLE"], true);
	$template->assign("S_CONTENTS"          , $_GET["S_CONTENTS"], true);

	
	// ページング
	$template->assign("PAGING"  , HtmlPagingTag( $template->Self ."?". $_que, $_GET["P"], $allpage) );
	$template->assign("ALLROW"  , (string)$allrows);			// 総件数
	$template->assign("P"       , (string)$_GET["P"]);			// 現在ページ番号
	$template->assign("ALLP"    , (string)$allpage);			// 総ページ数
	//
	$template->assign("ODR"     , $_GET["ODR"]);				// ソート順
	
	// リスト処理
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("NOTICE_NO"        , $row["notice_no"], true);
		$template->assign("NOTICE_NAME"      , $row["notice_name"], true);
		$template->assign("DISP_LINK_TYPE"   , $template->getArrayValue($GLOBALS["noticeLinkTypeList"], $row["link_type"]), true);
		$template->assign("LINK_URL"         , $row["link_url"], true);
		$template->assign("DISP_ORDER"       , $row["disp_order"], true);
		$template->assign("RELEASE_START_DT" , format_date($row["start_dt"]), true);	// 2020/04/28 [UPD]
		$template->assign("RELEASE_END_DT"   , format_date($row["end_dt"]), true);		// 2020/04/28 [UPD]
		$template->loop_next();
	}
	$template->loop_end("LIST");
	unset($rs);
	
	// 表示
	$template->flush();
}

/**
 * 詳細画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$message		再表示時のエラーメッセージ
 * @return	なし
 */
function DispDetail($template, $message = "") {
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	
	// データ取得
	getData($_GET , array("NO"));
	getData($_POST, array("NOTICE_NO", "NOTICE_NAME", "LINK_TYPE", "LINK_URL", "DISP_ORDER", "RELEASE_START_DT", "RELEASE_END_DT"));	// 2020/04/28 [UPD]	
	//優先度
	$dispOrderList = array();
	for($i=1;$i<10;$i++){
		$dispOrderList[$i] = $i;
	}
	
	if( mb_strlen($_GET["NO"]) > 0){
		if( mb_strlen($message) == 0 ){
			$_load = true;
		}else{
			$_load = false;
		}
	}else{
		$_load = false;
	}
	
	if( $_load ){
		
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			->field("dn.notice_no, dn.notice_name, dn.link_type, dn.link_url, dn.disp_order, dn.start_dt, dn.end_dt")
			->from( "dat_notice dn" )
			->where()
				->and("dn.notice_no = ", $_GET["NO"], FD_NUM)
				->and("dn.del_flg != " , "1", FD_NUM)	// 2020/04/28 [ADD]
			->createSql();
		$row = $template->DB->getRow( $sql, PDO::FETCH_ASSOC);
		// 2020/04/28 [ADD Start]
		if (empty($row["notice_no"])) {		// データ不存在は通常あり得ないのでシステムエラー
			$template->dispProcError("指定されたお知らせが見つかりません。", false);
			return;
		}
		// 2020/04/28 [ADD End]

		// 多言語項目
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
			//--- 2023/11/01 Upd S by S.Okamoto 一覧タイトル追加
			//->field("dnl.notice_no, dnl.lang, dnl.top_image, dnl.title, dnl.sub_title, dnl.contents")
			->field("dnl.notice_no, dnl.lang, dnl.top_image, dnl.title, dnl.sub_title, dnl.list_title, dnl.contents")
			//--- 2023/11/01 Upd E
			->from( "dat_notice_lang dnl" )
			->where()
				->and( "dnl.notice_no = ", $_GET["NO"], FD_NUM )
			->createSql();
		$lrow = $template->DB->query($sql);
		
		$row["top_image"] = array();
		$row["title"] = array();
		$row["sub_title"] = array();
		$row["list_title"] = array();		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
		$row["contents"] = array();
		while ($v = $lrow->fetch(PDO::FETCH_ASSOC)) {
			$row["top_image"][$v["lang"]] = $v["top_image"];
			$row["title"][$v["lang"]]     = $v["title"];
			$row["sub_title"][$v["lang"]] = $v["sub_title"];
			$row["list_title"][$v["lang"]] = $v["list_title"];		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
			$row["contents"][$v["lang"]]  = $v["contents"];
		}
		unset($lrow);
		
	}else{
		$row["notice_no"]        = $_POST["NOTICE_NO"];
		$row["notice_name"]      = $_POST["NOTICE_NAME"];
		$row["link_type"]        = $_POST["LINK_TYPE"];
		$row["link_url"]         = $_POST["LINK_URL"];
		$row["disp_order"]       = $_POST["DISP_ORDER"];
		$row["start_dt"] = $_POST["RELEASE_START_DT"];
		$row["end_dt"]   = $_POST["RELEASE_END_DT"];
		
		foreach ($setLangList as $v) {
			$row["top_image"][$v["lang"]] = (isset($_POST["TOP_IMAGE_".$v["lang"]]) ? $_POST["TOP_IMAGE_".$v["lang"]] : "");
			$row["title"][$v["lang"]]     = (isset($_POST["TITLE"][$v["lang"]]) ? $_POST["TITLE"][$v["lang"]] : "");
			$row["sub_title"][$v["lang"]] = (isset($_POST["SUB_TITLE"][$v["lang"]]) ? $_POST["SUB_TITLE"][$v["lang"]] : "");
			//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
			$row["list_title"][$v["lang"]] = (isset($_POST["LIST_TITLE"][$v["lang"]]) ? $_POST["LIST_TITLE"][$v["lang"]] : "");
			$row["contents"][$v["lang"]]  = (isset($_POST["CONTENTS"][$v["lang"]]) ? $_POST["CONTENTS"][$v["lang"]] : "");
		}
		
	}
	
	// 画面表示開始
	$template->open(PRE_HTML . "_detail.html");
	// 2020/04/28 [ADD Start] 共通置換される前に置換
	$defLangName = $GLOBALS["langList"][FOLDER_LANG]["names"][array_search(FOLDER_LANG, array_column($GLOBALS["langList"][FOLDER_LANG]["names"], 'lang'))]["name"];
	$template->assign("A2001", "お知らせ名称を入力してください。", true);
	$template->assign("A2005", "リンクURLを入力してください。", true);
	$template->assign("A2006", "リンクURLが正しくありません。", true);
	$template->assign("A2007", "公開開始日を入力してください。", true);
	$template->assign("A2008", "公開開始日が正しくありません。", true);
	$template->assign("A2010", "公開終了日が正しくありません。", true);
	$template->assign("A2013", "画像ファイル形式が正しくありません。", true);
	$template->assign("A2014", "画像ファイルサイズが大きすぎます。", true);
	$template->assign("A2015", "公開終了日は公開開始日より後の日付を入力してください。", true);
	$template->assign("A2016", "お知らせ画像（" . $defLangName . "）を選択してください。", true);
	$template->assign("A2020", "お知らせ内容（" . $defLangName . "）を入力してください。", true);
	$template->assign("A0001", "この内容で登録してもよろしいですか？", true);
	$template->assign("A0002", "このお知らせを削除してもよろしいですか？", true);
	// 2020/04/28 [ADD End] 共通置換される前に置換
	$template->assignCommon();
	$template->assign("ERRMSG", $message);
	// 表示制御
	$template->if_enable("ERRMSG", mb_strlen($message) > 0);
	$template->if_enable("DEL"   , mb_strlen( $row["notice_no"]) > 0);	// 2020/04/28 [UPD]
	
	// タブ項目
	$template->loop_start("LIST_TAB");
	foreach ($setLangList as $lang) {
		$template->assign("LANG"     , $lang["lang"], true);
		$template->assign("LANG_NAME", $lang["name"], true);
		$template->assign("LANG_ACT" , ($lang["lang"] == FOLDER_LANG) ? "active" : "", true);
		$template->loop_next();
	}
	$template->loop_end("LIST_TAB");
	
	// 多言語項目
	$template->loop_start("LIST_LANG");
	foreach ($setLangList as $lang) {
		$template->assign("LANG"       , $lang["lang"], true);
		$template->assign("LANG_ACT"   , ($lang["lang"] == FOLDER_LANG) ? "active" : "", true);
		$topImage = (isset($row["top_image"][$lang["lang"]]) ? $row["top_image"][$lang["lang"]] : "");
		$template->assign("TOP_IMAGE"  , $topImage, true);
		// 画像URL生成（GCS URLはそのまま、ローカルはパス付加）
		if (strpos($topImage, 'https://') === 0) {
			$topImageUrl = $topImage;  // GCS URL
		} else {
			$topImageUrl = DIR_IMG_NOTICE_DIR . $topImage;  // ローカルパス
		}
		$template->assign("TOP_IMAGE_URL", $topImageUrl, true);
		$template->assign("TITLE"      , isset($row["title"][$lang["lang"]]) ? $row["title"][$lang["lang"]] : "", true);
		$template->assign("SUB_TITLE"  , isset($row["sub_title"][$lang["lang"]]) ? $row["sub_title"][$lang["lang"]] : "", true);
		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
		$template->assign("LIST_TITLE" , isset($row["list_title"][$lang["lang"]]) ? $row["list_title"][$lang["lang"]] : "", true);
		$template->assign("CONTENTS"   , isset($row["contents"][$lang["lang"]]) ? $row["contents"][$lang["lang"]] : "", true);
		$template->if_enable("DEF_LANG", $lang["lang"] == FOLDER_LANG);
		$template->if_enable("EDIT_IMG", $topImage!="");
		$template->if_enable("UPD"     , mb_strlen($topImage) > 0);		// 2020/04/28 [ADD]
		$template->loop_next();
	}
	$template->loop_end("LIST_LANG");
	
	
	$template->assign("ADMIN_PATH"       , ADMIN_PATH, true);		// 管理画面パス
	$template->assign("NOTICE_NO"        , $row["notice_no"], true);
	$template->assign("NOTICE_NAME"      , $row["notice_name"], true);
	$template->assign("SEL_LINK_TYPE"    , makeOptionArray( $GLOBALS["noticeLinkTypeList"],  $row["link_type"],  false));
	$template->assign("LINK_URL"         , $row["link_url"], true);
	$template->assign("SEL_DISP_ORDER"   , makeOptionArray( $dispOrderList,  $row["disp_order"],  false));
	$template->assign("RELEASE_START_DT" , $row["start_dt"], true);
	$template->assign("RELEASE_END_DT"   , $row["end_dt"], true);
	
	$aryExt = explode("/", str_replace(" ", "", UPFILE_IMG_EXT));
	$template->assign("IMG_EXT"           , "." . implode(",.", $aryExt));
	$template->assign("UPFILE_IMG_EXT"    , UPFILE_IMG_EXT, true);
	$template->assign("UPFILE_IMG_MAX"    , UPFILE_IMG_MAX, true);
	$template->assign("UPFILE_IMG_MAXBYTE", (UPFILE_IMG_MAX * 1024 *1024), true);
	$template->assign("DIR_IMG_NOTICE_DIR", DIR_IMG_NOTICE_DIR, true);
	$imgType = array_column($GLOBALS["ImgExtension"], "mine");
	$template->assign("UPFILE_IMG_TYPE" , "'" . implode("','", $imgType) . "'");
	
	$template->assign("DEF_LANG_S"           , FOLDER_LANG, true);
	$template->assign("NOTICE_TITLE_MAX"     , NOTICE_TITLE_MAX, true);
	$template->assign("NOTICE_SUB_TITLE_MAX" , NOTICE_SUB_TITLE_MAX, true);
	$template->assign("NOTICE_LIST_TITLE_MAX", NOTICE_LIST_TITLE_MAX, true);		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
	// 表示
	$template->flush();
	
}

/**
 * 登録処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function RegistData($template) {
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	
	// データ取得
	getData($_GET , array("ACT", "NO"));
	getData($_POST, array("NOTICE_NO", "NOTICE_NAME", "LINK_TYPE", "LINK_URL", "DISP_ORDER", "RELEASE_START_DT", "RELEASE_END_DT"));	// 2020/04/28 [UPD]
	
	// 入力チェック
	$message = checkInput($template);
	if (mb_strlen($message) > 0) {
		DispDetail($template, $message);
		return;
	}
	
	// トランザクション開始
	$template->DB->autoCommit(false);
	$mode = "";
	// GCSヘルパー初期化（クラスが存在する場合のみ）
	$gcs = null;
	if (class_exists('CloudStorageHelper')) {
		try {
			$gcs = new CloudStorageHelper();
		} catch (Exception $e) {
			error_log('CloudStorageHelper初期化エラー: ' . $e->getMessage());
		}
	}

	if ($_GET["ACT"] == "del") {
		// 削除
		$mode = "del";
		// 2020/04/28 [ADD Start]
		// 画像登録時は削除
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("top_image")
			->from("dat_notice_lang")
			->where()
				->and( "notice_no =", $_GET["NO"], FD_NUM)
				->and( "top_image IS NOT NULL")
			->createSQL("\n");
		$rs = $template->DB->query($sql);
		while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
			if (mb_strlen($row["top_image"]) > 0) {
				// GCS URLの場合はGCSから削除
				if (strpos($row["top_image"], 'https://storage.googleapis.com/') === 0) {
					if ($gcs) $gcs->delete($row["top_image"]);
				} else {
					// ローカルファイルの場合
					$delimage = DIR_IMG_NOTICE . $row["top_image"];
					if (file_exists($delimage)) {
						chmod($delimage, 0755);
						unlink($delimage);
					}
				}
			}
		}
		unset($rs);
		// 2020/04/28 [ADD End]

		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "dat_notice" )
				->set()
					->value( "del_flg"          , 1, FD_NUM)
					->value( "del_no"           , $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt"           , "current_timestamp", FD_FUNCTION)
				->where()
					->and( "notice_no ="        , $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
		
	}else{
		// 2020/04/28 [ADD Start] 画像と言語の編集
		$topImage = array();
		$title = array();
		$subTitle = array();
		$listTitle = array();		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
		$contents = array();
		$oldFile = array();
		$trgLang = "";
		$upfile = "";
		foreach ($setLangList as $lang) {
			$trgLang = $lang["name"];

			// 画像以外
			$title[$lang["lang"]] = $_POST["TITLE"][$lang["lang"]];			// タイトル
			$subTitle[$lang["lang"]] = $_POST["SUB_TITLE"][$lang["lang"]];	// サブタイトル
			//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
			$listTitle[$lang["lang"]] = $_POST["LIST_TITLE"][$lang["lang"]];	// 一覧タイトル
			$contents[$lang["lang"]] = $_POST["CONTENTS"][$lang["lang"]];	// お知らせ内容
			// 画像処理
			$topImage[$lang["lang"]] = "";		// ファイル名初期化
			if (isset($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['tmp_name']) && !empty($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['tmp_name'])) {
				try {
					if (!isset($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['error']) || !is_int($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['error'])) {
						throw new RuntimeException("画像のアップロードに失敗しました。");
					}
					
					switch ($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['error']) {
						case UPLOAD_ERR_OK: // OK
							break;
						case UPLOAD_ERR_NO_FILE:   // ファイル未選択
							if (mb_strlen($_POST["NOTICE_NO"]) == 0 && $lang["lang"] == FOLDER_LANG) {
								throw new RuntimeException("お知らせ画像（" . $trgLang . "）を選択してください。");
							}
							break;
						case UPLOAD_ERR_INI_SIZE:  // php.ini定義の最大サイズ超過
						case UPLOAD_ERR_FORM_SIZE: // フォーム定義の最大サイズ超過
							throw new RuntimeException("画像ファイルのサイズが大きすぎます。");
						default:
							throw new RuntimeException("画像のアップロードに失敗しました。");
					}
					
					// ファイルサイズチェック
					if ($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['size'] > (UPFILE_IMG_MAX * 1024 *1024)) {
						throw new RuntimeException("画像ファイルのサイズが大きすぎます。");
					}
					
					// MIMEタイプチェック(拡張子)
					$chkMime = array_column($GLOBALS["ImgExtension"], 'mine', 'ext');
					if (!$ext = array_search(mime_content_type($_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['tmp_name']), $chkMime, true)) {
						throw new RuntimeException("画像ファイルの形式が不正です。");
					}

					// 保存（GCS優先）
					$upfile = sha1(mt_rand() . time());
					$filename = $upfile . "." . $ext;
					$tmpPath = $_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['tmp_name'];

					if ($gcs && $gcs->isEnabled()) {
						// GCSにアップロード
						$gcsUrl = $gcs->upload($tmpPath, 'notice', $filename);
						if ($gcsUrl) {
							$topImage[$lang["lang"]] = $gcsUrl;  // GCS URLを保存
							// 旧画像削除用にGCS URLを保存
							if (mb_strlen($_POST['TOP_IMAGE_'.$lang["lang"]]) > 0) {
								$oldFile[] = $_POST['TOP_IMAGE_'.$lang["lang"]];
							}
						} else {
							throw new RuntimeException("画像のアップロードに失敗しました。（GCSエラー）");
						}
					} else {
						// ローカルに保存（フォールバック）
						// ディレクトリが存在しない場合は作成
						if (!is_dir(DIR_IMG_NOTICE)) {
							@mkdir(DIR_IMG_NOTICE, 0777, true);
						}
						if (move_uploaded_file($tmpPath, sprintf(DIR_IMG_NOTICE . '%s', $filename))) {
							$topImage[$lang["lang"]] = $filename;
							if (mb_strlen($_POST['TOP_IMAGE_'.$lang["lang"]]) > 0) {
								if (file_exists(DIR_IMG_NOTICE . $_POST['TOP_IMAGE_'.$lang["lang"]])) {
									$oldFile[] = DIR_IMG_NOTICE . $_POST['TOP_IMAGE_'.$lang["lang"]];
								}
							}
						} else {
							$upfile = "";
							throw new RuntimeException("画像のアップロードに失敗しました。（ローカル保存エラー: " . DIR_IMG_NOTICE . "）");
						}
					}
				} catch (RuntimeException $e) {
					DispDetail($template, $e->getMessage());
					return;
				}
			}
		}

		// 画像以外のエラーが発生しないと信じて旧画像削除
		foreach($oldFile as $file) {
			// GCS URLの場合はGCSから削除
			if (strpos($file, 'https://storage.googleapis.com/') === 0) {
				if ($gcs) $gcs->delete($file);
			} else if (file_exists($file)) {
				// ローカルファイルの場合
				chmod($file, 0755);
				unlink($file);
			}
		}

		// ページ作成時他言語のお知らせ内容が未設定の場合はデフォルト言語を設定
		if ($_POST["LINK_TYPE"] == 2) {
			foreach ($setLangList as $lang) {
				if ($lang["lang"] == FOLDER_LANG) continue;
				if (mb_strlen($contents[$lang["lang"]]) <= 0) $contents[$lang["lang"]] = $contents[FOLDER_LANG];
			}
		}
		// 2020/04/28 [ADD End] 画像と言語の編集

		if (mb_strlen($_POST["NOTICE_NO"]) > 0) {
			// 更新
			$mode = "update";
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "dat_notice" )
					->set()
						->value("notice_name", $_POST["NOTICE_NAME"], FD_STR)
						->value("link_type"  , $_POST["LINK_TYPE"], FD_NUM)
						->value("link_url"   , $_POST["LINK_URL"], FD_STR)
						->value("disp_order" , $_POST["DISP_ORDER"], FD_NUM)
						->value("start_dt"   , $_POST["RELEASE_START_DT"], FD_DATEEX)
						->value("end_dt"     , ($_POST["RELEASE_END_DT"]!="")? $_POST["RELEASE_END_DT"]:DEFAULT_END_DATE , FD_DATEEX)
						->value("upd_no"     , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt"     , "current_timestamp", FD_FUNCTION)
					->where()
						->and( "notice_no = ", $_POST["NOTICE_NO"], FD_NUM)
				->createSQL();
			$template->DB->exec($sql);
			
			foreach ($setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->update( "dat_notice_lang" )
						->set()
							->value(true, "top_image", $topImage[$lang["lang"]], FD_STR)
							->value("title"          , $title[$lang["lang"]], FD_STR)
							->value("sub_title"      , $subTitle[$lang["lang"]], FD_STR)
							->value("list_title"     , $listTitle[$lang["lang"]], FD_STR)		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
							->value("contents"       , $contents[$lang["lang"]], FD_STR)
							->value("upd_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value("upd_dt"         , "current_timestamp", FD_FUNCTION)
						->where()
							->and( "notice_no ="     , $_POST["NOTICE_NO"], FD_NUM)
							->and( "lang ="          , $lang["lang"], FD_STR)
					->createSQL();
				$template->DB->exec($sql);
			}
		}else{
			// 新規
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert()
					->into( "dat_notice" )
						->value("notice_name", $_POST["NOTICE_NAME"], FD_STR)
						->value("link_type"  , $_POST["LINK_TYPE"], FD_NUM)
						->value(true, "link_url", $_POST["LINK_URL"], FD_STR)
						->value("disp_order" , $_POST["DISP_ORDER"], FD_NUM)
						->value("start_dt"   , $_POST["RELEASE_START_DT"], FD_DATEEX)
						->value("end_dt"     , ($_POST["RELEASE_END_DT"]!="")? $_POST["RELEASE_END_DT"]:DEFAULT_END_DATE , FD_DATEEX)
						->value("add_no"     , $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("add_dt"     , "current_timestamp", FD_FUNCTION)
						->value("upd_no",     $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt",     "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->exec($sql);
			// お知らせNo取得
			$sql = "select last_insert_id()";
			$noticeNo = $template->DB->getOne($sql);
			// 新規 lang
			foreach ($setLangList as $lang) {
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->insert()
						->into("dat_notice_lang" )
							->value("notice_no"      , $noticeNo, FD_STR)
							->value("lang"           , $lang["lang"], FD_STR)
							->value(true, "top_image", $topImage[$lang["lang"]], FD_STR)
							->value("title"          , $title[$lang["lang"]], FD_STR)
							->value("sub_title"      , $subTitle[$lang["lang"]], FD_STR)
							->value("list_title"     , $listTitle[$lang["lang"]], FD_STR)		//--- 2023/11/01 Add by S.Okamoto 一覧タイトル追加
							->value("contents"       , $contents[$lang["lang"]], FD_STR)
							->value("add_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value("add_dt"         , "current_timestamp", FD_FUNCTION)
							->value("upd_no"         , $template->Session->AdminInfo["admin_no"], FD_NUM)
							->value("upd_dt"         , "current_timestamp", FD_FUNCTION)
					->createSQL();
				$template->DB->exec($sql);
			}
			
		}
	}
	
	// コミット(トランザクション終了)
	$template->DB->autoCommit(true);
	// 完了画面表示
	$selfUrl = basename($_SERVER['PHP_SELF']);
	header("Location: " . URL_ADMIN . $selfUrl . "?M=end&ACT=" . $mode);
	exit;
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {
	// データ取得
	getData($_GET , array("ACT"));
	
	switch ($_GET["ACT"]) {
		case "update":
			// 更新
			$title = "お知らせ更新完了";
			$msg = "お知らせ情報を更新しました。";
			break;
		case "del":
			// 削除
			$title = "お知らせ削除完了";
			$msg = "お知らせを削除しました。";
			break;
		default:
			// 新規登録
			$title = "お知らせ登録完了";
			$msg = "新しいお知らせを登録しました。";
	}
	// 完了画面表示
	$template->dispProcEnd( $title, "", $msg);
}


/**
 * 入力チェック
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	string					エラーメッセージ
 */
function checkInput($template) {
	global $langList;
	$setLangList = $langList[FOLDER_LANG]["names"];
	
	$errMessage = array();
	
	if ($_GET["ACT"] != "del") {
		//イメージチェック用
		$_tempimg_img = array();
		$_filename_img = array();
		$_chk_img = array();
		foreach ($setLangList as $lang) {
			$_tempimg_img[ $lang["lang"]]  = $_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['tmp_name'];
			$_filename_img[ $lang["lang"]] = './' . $_FILES['TOP_IMAGE_'.$lang["lang"].'_NEW']['name'];		// 2020/04/28 [UPD]
			$_chk_img[ $lang["lang"]] = ($_tempimg_img[ $lang["lang"]]!="")? $_filename_img[ $lang["lang"]]: $_POST["TOP_IMAGE_".$lang["lang"]];
		}
	
		$errMessage = (new SmartAutoCheck($template))
			//お知らせ名称
			->item($_POST["NOTICE_NAME"])
				->required("お知らせ名称を入力してください。")
			//リンクタイプ
			->item($_POST["LINK_TYPE"])
				->required("リンクタイプを選択してください。")
			//リンクURL
			->item($_POST["LINK_URL"])
				->case( $_POST["LINK_TYPE"] == 1 )
					->required("リンクURLを入力してください。")
					->chk_url("正しいURL形式で入力してください。")
				->case( $_POST["LINK_TYPE"] != 1 )	// 2020/04/28 [ADD]
					->any()
					->chk_url("正しいURL形式で入力してください。")
			//表示優先（数値チェック）
			->item($_POST["DISP_ORDER"])
				->any()
				->number('表示優先は数値で入力してください。')
			//公開開始日
			->item($_POST["RELEASE_START_DT"])
				->required("公開開始日を入力してください。")
				->date("正しい日付形式で入力してください。")
			//公開終了日
			->item($_POST["RELEASE_END_DT"])
				->any()
				->date("正しい日付形式で入力してください。")
			//お知らせ画像（デフォルト言語）
			->item( $_chk_img[ FOLDER_LANG])
				->required("お知らせ画像をアップロードしてください。")
			//お知らせ内容
			->item( $_POST["CONTENTS"][ FOLDER_LANG])
				->case( $_POST["LINK_TYPE"] == 2 )
					->required("お知らせ内容を入力してください。")
		->report();
		// 2020/04/28 [ADD Start]
		if (count($errMessage) <= 0) {
			// 開始終了整合チェック
			if (mb_strlen($_POST["RELEASE_END_DT"]) > 0) {
				if (strtotime($_POST["RELEASE_START_DT"]) > strtotime($_POST["RELEASE_END_DT"])) $errMessage[] = "公開終了日は公開開始日より後の日付を入力してください。";
			}
		}
		// 2020/04/28 [ADD End]
	}
	
	$ret = (!empty($errMessage)) ? implode("<br />", $errMessage) : "";
	return $ret;
}

?>
