<?php
/*
 * recommended_category.php
 *
 * おすすめカテゴリー管理画面
 * トップページのおすすめ機体セクションを管理
 *
 * @package NET8
 * @author  System
 * @version 1.0
 * @since   2025/12/28 初版作成
 */

// インクルード
require_once('../../_etc/require_files_admin.php');

// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

/**
 * メイン処理
 */
function main() {
	try {
		$template = new TemplateAdmin();
		getData($_GET, array("M"));

		$mainWin = true;
		switch ($_GET["M"]) {
			case "detail":
				$mainWin = false;
				DispDetail($template);
				break;
			case "regist":
				$mainWin = false;
				RegistData($template);
				break;
			case "end":
				$mainWin = false;
				DispComplete($template);
				break;
			default:
				DispList($template);
		}
	} catch (Exception $e) {
		$template->dispProcError($e->getMessage(), $mainWin);
	}
}

/**
 * 一覧画面表示
 */
function DispList($template, $message = "") {
	getData($_GET, array("P", "ODR"));
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;

	// データ取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("category_no, category_name, category_roman, category_icon, link_url, disp_order")
			->from("mst_recommended_category")
			->where()
				->and(false, "del_flg = ", "0", FD_NUM)
			->orderby( 'disp_order asc' )
		->createSql("\n");
	$rs = $template->DB->query($sql);

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();
	$template->assign("MESSAGE", $message, true);

	// LIST ループ作成
	$template->loop_start("LIST");
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$template->assign("NO",              $row["category_no"], true);
		$template->assign("CATEGORY_NAME",   $row["category_name"], true);
		$template->assign("CATEGORY_ROMAN",  $row["category_roman"], true);
		$template->assign("CATEGORY_ICON",   $row["category_icon"], true);
		$template->assign("LINK_URL",        $row["link_url"], true);
		$template->assign("DISP_ORDER",      $row["disp_order"], true);
		$template->loop_next();
	}
	$template->loop_end("LIST");

	$template->flush();
}

/**
 * 詳細画面表示
 */
function DispDetail($template, $message = "") {
	getData($_GET, array("NO"));

	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();
	$template->assign("MESSAGE", $message, true);

	if (mb_strlen($_GET["NO"]) > 0) {
		// 編集モード
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("*")
				->from("mst_recommended_category")
				->where()
					->and(false, "category_no = ", $_GET["NO"], FD_NUM)
			->createSql();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

		if ($row) {
			$template->assign("CATEGORY_NO",     $row["category_no"], true);
			$template->assign("CATEGORY_NAME",   $row["category_name"], true);
			$template->assign("CATEGORY_ROMAN",  $row["category_roman"], true);
			$template->assign("CATEGORY_ICON",   $row["category_icon"], true);
			$template->assign("LINK_URL",        $row["link_url"], true);
			$template->assign("DISP_ORDER",      $row["disp_order"], true);
		}
	} else {
		// 新規作成モード
		$template->assign("CATEGORY_NO",     "", true);
		$template->assign("CATEGORY_NAME",   "", true);
		$template->assign("CATEGORY_ROMAN",  "", true);
		$template->assign("CATEGORY_ICON",   "", true);
		$template->assign("LINK_URL",        "./", true);
		$template->assign("DISP_ORDER",      "99", true);
	}

	$template->flush();
}

/**
 * 登録処理
 */
function RegistData($template) {
	getData($_POST, array("CATEGORY_NO", "CATEGORY_NAME", "CATEGORY_ROMAN", "CATEGORY_ICON", "LINK_URL", "DISP_ORDER", "ACT"));
	getData($_GET, array("NO"));

	if ($_GET["ACT"] == "del") {
		// 削除
		$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->update( "mst_recommended_category" )
				->set()
					->value( "del_flg", 1, FD_NUM)
					->value( "del_no", $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "del_dt", "current_timestamp", FD_FUNCTION)
				->where()
					->and( "category_no =", $_GET["NO"], FD_NUM)
			->createSQL();
		$template->DB->exec($sql);
	} else {
		if (mb_strlen($_POST["CATEGORY_NO"]) > 0) {
			// 更新
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->update( "mst_recommended_category" )
					->set()
						->value("category_name",  $_POST["CATEGORY_NAME"], FD_STR)
						->value("category_roman", $_POST["CATEGORY_ROMAN"], FD_STR)
						->value("category_icon",  $_POST["CATEGORY_ICON"], FD_STR)
						->value("link_url",       $_POST["LINK_URL"], FD_STR)
						->value("disp_order",     $_POST["DISP_ORDER"], FD_NUM)
						->value("upd_no",         $template->Session->AdminInfo["admin_no"], FD_NUM)
						->value("upd_dt",         "current_timestamp", FD_FUNCTION)
					->where()
						->and( "category_no = ", $_POST["CATEGORY_NO"], FD_NUM)
				->createSQL();
			$template->DB->exec($sql);
		} else {
			// 新規登録
			$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
				->insert( "mst_recommended_category" )
					->value( "category_name",  $_POST["CATEGORY_NAME"], FD_STR)
					->value( "category_roman", $_POST["CATEGORY_ROMAN"], FD_STR)
					->value( "category_icon",  $_POST["CATEGORY_ICON"], FD_STR)
					->value( "link_url",       $_POST["LINK_URL"], FD_STR)
					->value( "disp_order",     $_POST["DISP_ORDER"], FD_NUM)
					->value( "reg_no",         $template->Session->AdminInfo["admin_no"], FD_NUM)
					->value( "reg_dt",         "current_timestamp", FD_FUNCTION)
				->createSQL();
			$template->DB->exec($sql);
		}
	}

	// 完了画面へ
	$_GET["M"] = "end";
	DispComplete($template);
}

/**
 * 完了画面表示
 */
function DispComplete($template) {
	$template->open(PRE_HTML . "_end.html");
	$template->assignCommon();
	$template->flush();
}

?>