<?php
/*
 * index.php
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
 * TOP画面表示
 *
 * TOP画面の表示を行う
 *
 * @package
 * @author   片岡 充
 * @version  1.0
 * @since    2019/02/07 初版作成 初版作成 片岡 充
 * @since    2019/09/06 表示形態諸々の大幅改修 鶴野
 */

// ★多言語対応: 言語検出（require_files.phpより前に定義必須）
$lang = isset($_GET['lang']) ? $_GET['lang'] : 'zh'; // デフォルトは中国語
$allowedLangs = ['zh', 'ko', 'en', 'ja'];
if (!in_array($lang, $allowedLangs)) {
    $lang = 'zh'; // 無効な言語コードはデフォルトに
}

// require_files.phpで使用される言語設定
define("FOLDER_LANG", $lang);

// インクルード
require_once(__DIR__ . '/../_etc/require_files.php');			// requireファイル

// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));	// テンプレートHTMLプレフィックス

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
 * TOP画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {
	
	// データ取得
	getData($_GET  , array("CN", "P"));
	
	// ページ初期値
	$_GET["P"] = (mb_strlen($_GET["P"]) == 0) ? 1 : $_GET["P"];
	if ($_GET["P"] <= 0) $_GET["P"] = 1;
	
	//時間チェック 営業時間が日をまたぐこと確定としてのチェック
	$open  = false;
	$today = date('H:i:s');
	if( strtotime( GLOBAL_OPEN_TIME.':00') <= strtotime( $today)){
		$open = true;
	}else{
		if( strtotime( GLOBAL_CLOSE_TIME.':00') > strtotime( $today)){
			$open = true;
		}
	}
	
	// ログインしていたらフラグ取得
	$_login_flg  = false;
	$testerFlg = false;
	if( $template->checkSessionUser(true, false)){
		try {
			// セッション情報の安全な取得
			if (isset($template->Session->UserInfo["member_no"]) &&
			    isset($template->Session->UserInfo["mail"])) {

				$sql = (new SqlString())
					->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
						->field("men.tester_flg")
						->from("mst_member men")
						->where()
							->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
							->and(false, "men.mail = ",      $template->Session->UserInfo["mail"], FD_STR)
							->and(false, "men.state = ", "1", FD_NUM)
						->createSQL();

				$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
				if ($row && isset($row["tester_flg"])) {
					$testerFlg = ($row["tester_flg"] == "1");
				}
				$_login_flg  = true;
			}
		} catch (Exception $e) {
			// エラーが出ても処理を続行（ログイン状態は維持）
			$_login_flg  = true;
			$testerFlg = false;
		}
	}
	
	$refToDay = GetRefTimeTodayExt();		// 基準時間＞使用開始時間の当日
	//----------------------------------------------------------
	// お知らせ情報取得
	$notice_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dnl.lang, dnl.top_image, dnl.title, dnl.sub_title")
			->field("dn.notice_no, dn.notice_name, dn.link_type, dn.link_url, dn.disp_order, dn.start_dt, dn.end_dt, dn.del_flg, dn.upd_dt")
			->from( "dat_notice_lang dnl" )
			->from( "inner join dat_notice dn on dn.notice_no = dnl.notice_no and dn.del_flg <> 1"
													. " and dn.start_dt <= " . $template->DB->conv_sql($refToDay, FD_DATE)
													. " and dn.end_dt >= " . $template->DB->conv_sql($refToDay, FD_DATE)
					)
			->where()
				// 画像必須条件を一時的に無効化（管理画面から画像アップロード後に有効化可能）
				// ->and(false, "dnl.top_image is not ", "", FD_STR)
				->and(false, "dnl.lang = ", FOLDER_LANG, FD_STR)
			->orderby( 'dn.disp_order asc' )
		->createSql("\n");
	$notice_row = $template->DB->getAll($notice_sql, PDO::FETCH_ASSOC);

	// おすすめカテゴリー取得（テーブルが存在しない場合はデフォルト値）
	try {
		$recommended_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("mrc.category_no, mrc.category_name, mrc.category_roman, mrc.category_icon, mrc.link_url, mrc.disp_order")
				->from("mst_recommended_category mrc")
				->where()
					->and(false, "mrc.del_flg = ", "0", FD_NUM)
				->orderby( 'mrc.disp_order asc' )
			->createSql("\n");
		$recommended_row = $template->DB->getAll($recommended_sql, PDO::FETCH_ASSOC);
	} catch (PDOException $e) {
		// テーブルが存在しない場合はデフォルト値を使用
		$recommended_row = array(
			array("category_no" => 1, "category_name" => "新台", "category_roman" => "New Machines", "category_icon" => "🆕", "link_url" => "./?CN=new", "disp_order" => 1),
			array("category_no" => 2, "category_name" => "パチスロ", "category_roman" => "Pachislot", "category_icon" => "🎰", "link_url" => "./", "disp_order" => 2),
			array("category_no" => 3, "category_name" => "パチンコ", "category_roman" => "Pachinko", "category_icon" => "🎯", "link_url" => "./", "disp_order" => 3),
			array("category_no" => 4, "category_name" => "人気機種", "category_roman" => "Popular", "category_icon" => "⭐", "link_url" => "./", "disp_order" => 4),
			array("category_no" => 5, "category_name" => "ジャックポット", "category_roman" => "Jackpot", "category_icon" => "🏆", "link_url" => "./", "disp_order" => 5),
			array("category_no" => 6, "category_name" => "クラシック", "category_roman" => "Classic", "category_icon" => "🎮", "link_url" => "./", "disp_order" => 6)
		);
	}

	// コーナー取得
	$corner_sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("mc.corner_no, mc.corner_name, mc.corner_roman")
			->from("mst_corner mc")
			->where()
				->and(false, "mc.del_flg = ", "0", FD_NUM)
		->createSql();
	$corner_row = $template->DB->getAll($corner_sql, PDO::FETCH_ASSOC);

	// CNは固定以外はコーナーのidになるはずなのでチェック(合致しない場合は空にする)
	if (mb_strlen($_GET["CN"]) > 0) {
		if (!array_key_exists($_GET["CN"], $GLOBALS["Frow_Fixed_Array"])) {
			if (array_search($_GET["CN"], array_column($corner_row, 'corner_no')) === false) $_GET["CN"] = "";
		}
	}

	// 台情報取得
	$sqls = (new SqlString($template->DB));
	$template->SearchMachineBase($sqls, $testerFlg);
	$sqls->orderby( 'dm.release_date desc' );		// リリースが新しい順

	if ($_GET["CN"] == "new") {
		// 新台
		$sqls->and("DATEDIFF(" . $template->DB->conv_sql($refToDay, FD_DATE) . ", dm.release_date) < ", NEW_DAYS, FD_NUM);
	} else {
		if (mb_strlen($_GET["CN"]) > 0 && !array_key_exists($_GET["CN"], $GLOBALS["Frow_Fixed_Array"])) {
			$sqls->and("FIND_IN_SET(" . $_GET["CN"] . ",dm.machine_corner)");
		}
	}
	
	// 件数取得
	$count_sql = $sqls->resetField()->field("count(*)")->createSQL();
	$allrows = $template->DB->getOne($count_sql);
	$numrows = (int)$allrows;
	if ($numrows == 0) $numrows = 1;
	$allpage = ceil($numrows / INDEX_VIEW_MACHINES);			// 総ページ数
	if ($_GET["P"] > $allpage) $_GET["P"] = $allpage;

	// 台データ取得
	$template->SearchMachineField($sqls);
	$row_sql = $sqls->page( $_GET["P"], INDEX_VIEW_MACHINES)
					->createSql("\n");
	$rs = $template->DB->query($row_sql);

	// データを配列に格納（SLIDER_LISTとLISTの両方で使用するため）
	$machineData = array();
	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$machineData[] = $row;
	}

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// ★多言語対応: 現在の言語をテンプレートに渡す
	$template->assign("CURRENT_LANG", FOLDER_LANG);
	$template->assign("LANG_PARAM", "?lang=" . FOLDER_LANG);
	$template->assign("LANG_PARAM_AMP", "&lang=" . FOLDER_LANG);

	// ページング
	$queryString = $_SERVER['QUERY_STRING'] ?? "";
	$template->assign("PAGING"        , HtmlPagingTag( (($queryString!="")? "?".$queryString."&":"?"), $_GET["P"], $allpage) );
	
	// コーナー
	$target_corner = "";
	$corner_cnt = 0;
	$template->loop_start("FLOW_CORNER");
	// 固定タブ
	foreach( $GLOBALS["Frow_Fixed_Array"] as $cno => $cname){
		$corner_cnt++;
		if ($_GET["CN"] == $cno) {
			$target_corner = $cname;
			$template->assign("FLOW_ACTIVE" , " flow-active", true);
		} else {
			$template->assign("FLOW_ACTIVE" , "", true);
		}
		$template->assign("CNO"          , $cno, true);
		$template->assign("CORNER_NAME"  , $cname, true);
		$template->if_enable("FLOW_LINK" , true);
		$template->loop_next();
	}
	// コーナーデータ
	foreach( $corner_row as $corner){
		$corner_cnt++;
		if ($_GET["CN"] == $corner["corner_no"]) {
			$target_corner = (FOLDER_LANG==DEFAULT_LANG)? $corner["corner_name"]:$corner["corner_roman"];
			$template->assign("FLOW_ACTIVE" , " flow-active", true);
		} else {
			$template->assign("FLOW_ACTIVE" , "", true);
		}
		$template->assign("CNO"          , $corner["corner_no"], true);
		$template->assign("CORNER_NAME"  , (FOLDER_LANG==DEFAULT_LANG)? $corner["corner_name"]:$corner["corner_roman"], true);
		$template->if_enable("FLOW_LINK" , true);
		$template->loop_next();
		
		// 不足分の補填(最後のループ時にのみ処理)
		if ((count($corner_row) + count($GLOBALS["Frow_Fixed_Array"])) == $corner_cnt) {
			$comp_cnt = $corner_cnt % FLOW_COL_MAX;
			for ($i=$comp_cnt; $i < FLOW_COL_MAX; $i++) {
				$template->assign("FLOW_ACTIVE"  , "", true);
				$template->if_enable("FLOW_LINK" , false);
				$template->loop_next();
			}
		}
	}
	$template->loop_end("FLOW_CORNER");
	$template->assign("TARGET_CORNER" , $target_corner, true);

	// SLIDER_LIST ループ作成（スライダー表示用）
	$template->loop_start("SLIDER_LIST");
	foreach ($machineData as $row) {
		// 画像パスの処理（GCS URLでない場合はローカルパスを追加）
		$imageList = $row["image_list"];
		if ($imageList && !preg_match('/^https?:\/\//', $imageList)) {
			$imageList = '/data/img/model/' . $imageList;
		}

		$template->assign("NO"               , $row["machine_no"], true);
		$template->assign("MACHINE_CD"       , $row["machine_cd"], true);
		$template->assign("MODEL_CD"         , $row["model_cd"], true);
		$template->assign("MODEL_NAME"       , (FOLDER_LANG==DEFAULT_LANG)? $row["model_name"]:$row["model_roman"], true);
		$template->assign("GENERATION"       , (FOLDER_LANG==DEFAULT_LANG)? $row["unit_name"]:$row["unit_roman"], true);
		$template->assign("IMAGE_LIST"       , $imageList, true);
		$template->assign("DIR_IMG_MODEL_DIR", DIR_IMG_MODEL_DIR, true);
		$template->loop_next();
	}
	$template->loop_end("SLIDER_LIST");

	// LIST ループ作成（リスト表示用）
	$template->loop_start("LIST");
	foreach ($machineData as $row) {
		// 画像パスの処理（GCS URLでない場合はローカルパスを追加）
		$imageList = $row["image_list"];
		if ($imageList && !preg_match('/^https?:\/\//', $imageList)) {
			$imageList = '/data/img/model/' . $imageList;
		}

		$template->assign("NO"               , $row["machine_no"], true);
		$template->assign("MACHINE_CD"       , $row["machine_cd"], true);
		$template->assign("MAKER_NO"         , $row["maker_no"], true);
		$template->assign("MAKER_NAME"       , (FOLDER_LANG==DEFAULT_LANG)? $row["maker_name"]:$row["maker_roman"], true);
		$template->assign("MODEL_NO"         , $row["model_no"], true);
		$template->assign("MODEL_CD"         , $row["model_cd"], true);
		$template->assign("MODEL_NAME"       , (FOLDER_LANG==DEFAULT_LANG)? $row["model_name"]:$row["model_roman"], true);
		$template->assign("GENERATION"       , (FOLDER_LANG==DEFAULT_LANG)? $row["unit_name"]:$row["unit_roman"], true);
		$template->assign("IMAGE_LIST"       , $imageList, true);
		$template->assign("DIR_IMG_MODEL_DIR", DIR_IMG_MODEL_DIR, true);

		// ユーザー向け3状態判定（空き台・使用中・準備中）
		$assignFlg = $row["assign_flg"];
		$machineStatus = $row["machine_status"];

		// 使用中: 誰かが割り当てられている
		$isInUse = ($assignFlg == 1);

		// 空き台: 未割当 かつ 通常稼働 かつ 営業時間内
		$isAvailable = ($assignFlg == 0 && $machineStatus == 1 && $open);

		// 準備中: それ以外（メンテ、カメラ待機、営業時間外など）
		$isPreparing = !$isInUse && !$isAvailable;

		$template->if_enable("STATUS_AVAILABLE", $isAvailable);   // 🟢 空き台
		$template->if_enable("STATUS_INUSE", $isInUse);           // 🔴 使用中
		$template->if_enable("STATUS_PREPARING", $isPreparing);   // 🟡 準備中

		$template->loop_next();
	}
	$template->loop_end("LIST");

	// ページ処理
	$template->assign("ALLROW", (string)$allrows, true);		// 総件数
	$template->assign("P", (string)$_GET["P"], true);			// 現在ページ番号
	$template->assign("ALLP", (string)$allpage, true);		// 総ページ数
	
	// お知らせ部分
	$notice_count = 0;
	$template->loop_start("NOTICE_LIST2");
	foreach( $notice_row as $notice){
		// 画像URLの処理（GCS URLでない場合はローカルパスまたはDIR付与）
		$topImageUrl = $notice["top_image"];
		if (!empty($topImageUrl)) {
			// GCS URLかどうかチェック
			if (strpos($topImageUrl, 'https://storage.googleapis.com/') !== 0) {
				// GCS URLでない場合、相対パスかファイル名のみの場合はDIR_IMG_NOTICE_DIRを付与
				if (strpos($topImageUrl, '/') !== 0) {
					$topImageUrl = DIR_IMG_NOTICE_DIR . $topImageUrl;
				}
			}
		}

		// 日付フォーマット（start_dtを表示用に変換）
		$dispDate = '';
		if (!empty($notice["start_dt"])) {
			$dispDate = date('Y.m.d', strtotime($notice["start_dt"]));
		}

		$template->assign("TITLE",      $notice["title"], true);
		$template->assign("SUB_TITLE",  $notice["sub_title"], true);
		$template->assign("TOP_IMAGE",  $topImageUrl, true);
		$template->assign("DISP_DT",    $dispDate, true);
		$template->assign("ACTIVE",    ($notice_count==0)? ' active':'', true);
		$template->assign("LINK_URL",  ($notice["link_type"]==1)? $notice["link_url"]: (($notice["link_type"]==2)? "notice.php?NO=".$notice["notice_no"]:'#' ));
		$template->assign("OTHER_LINK",($notice["link_type"]==1)? ' target="_blank"': "", true);
		$template->if_enable("NOLINK", !($notice["link_type"]==0));
		$notice_count++;
		$template->loop_next();
	}
	$template->loop_end("NOTICE_LIST2");
	
	$template->if_enable("HAVE_NOTICE", !empty($notice_row));

	// おすすめカテゴリー部分
	$template->loop_start("RECOMMENDED_LIST");
	foreach( $recommended_row as $category){
		$template->assign("CATEGORY_NO",    $category["category_no"], true);
		$template->assign("CATEGORY_NAME",  (FOLDER_LANG==DEFAULT_LANG)? $category["category_name"]:$category["category_roman"], true);
		$template->assign("CATEGORY_ICON",  $category["category_icon"], true);
		$template->assign("LINK_URL",       $category["link_url"], true);
		$template->loop_next();
	}
	$template->loop_end("RECOMMENDED_LIST");

	$template->flush();
}

?>