<?php
/*
 * model_detail.php
 *
 * 機体詳細画面表示
 *
 * 機種を選択した後の詳細画面を表示する
 *
 * @package
 * @author   Claude Code
 * @version  1.0
 * @since    2025/12/17 初版作成
 */

// インクルード
require_once('../_etc/require_files.php');			// requireファイル
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

		// 実処理
		DispDetail($template);

	} catch (Exception $e) {
		$template->dispProcError($e->getMessage());
	}
}

/**
 * 詳細画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispDetail($template) {

	// データ取得
	getData($_GET, array("MODEL_NO", "NO"));

	// MODEL_NOまたはNO（machine_no）が必要
	$modelNo = $_GET["MODEL_NO"];
	$machineNo = $_GET["NO"];

	if (mb_strlen($modelNo) == 0 && mb_strlen($machineNo) == 0) {
		// パラメータがない場合は検索ページにリダイレクト
		header("Location: search.php");
		exit;
	}

	// ログインチェック
	$_login_flg = false;
	$testerFlg = false;
	if ($template->checkSessionUser(true, false)) {
		$sql = (new SqlString())
			->setAutoConvert([$template->DB, "conv_sql"])
			->select()
				->field("men.tester_flg")
				->from("mst_member men")
				->where()
					->and(false, "men.member_no = ", $template->Session->UserInfo["member_no"], FD_NUM)
					->and(false, "men.mail = ", $template->Session->UserInfo["mail"], FD_STR)
					->and(false, "men.pass = ", $template->Session->UserInfo["pass"], FD_STR)
					->and(false, "men.state = ", "1", FD_NUM)
				->createSQL();
		$row = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$testerFlg = ($row["tester_flg"] == "1");
		$_login_flg = true;
	}

	// 営業時間チェック
	$open = false;
	$today = date('H:i:s');
	if (strtotime(GLOBAL_OPEN_TIME . ':00') <= strtotime($today)) {
		$open = true;
	} else {
		if (strtotime(GLOBAL_CLOSE_TIME . ':00') > strtotime($today)) {
			$open = true;
		}
	}

	// machine_noからmodel_noを取得する場合
	if (mb_strlen($machineNo) > 0 && mb_strlen($modelNo) == 0) {
		$sql = (new SqlString())
			->setAutoConvert([$template->DB, "conv_sql"])
			->select()
				->field("model_no")
				->from("dat_machine")
				->where()
					->and(false, "machine_no = ", $machineNo, FD_NUM)
				->createSQL();
		$machineRow = $template->DB->getRow($sql, PDO::FETCH_ASSOC);
		$modelNo = $machineRow["model_no"];
	}

	// 機種情報の取得
	$sql = (new SqlString())
		->setAutoConvert([$template->DB, "conv_sql"])
		->select()
			->field("mm.model_no, mm.model_name, mm.model_roman, mm.model_cd")
			->field("mm.category, mm.image_list, mm.image_detail, mm.image_reel")
			->field("mm.prizeball_data, mm.layout_data")
			->field("mk.maker_name, mk.maker_roman")
			->field("un.unit_name, un.unit_roman")
			->from("mst_model mm")
			->join("left", "mst_maker mk", "mm.maker_no = mk.maker_no")
			->join("left", "mst_unit un", "mm.unit_no = un.unit_no")
			->where()
				->and(false, "mm.model_no = ", $modelNo, FD_NUM)
				->and(false, "mm.del_flg != ", "1", FD_NUM)
			->createSQL();

	$modelData = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

	if (!$modelData) {
		// 機種が見つからない場合
		header("Location: search.php");
		exit;
	}

	// この機種の利用可能な台一覧を取得
	$sqls = new SqlString($template->DB);
	$template->SearchMachineBase($sqls, $testerFlg);
	$sqls->and(false, "mm.model_no = ", $modelNo, FD_NUM);

	// 台データ取得
	$template->SearchMachineField($sqls);
	$row_sql = $sqls->orderby("dm.machine_no asc")->createSql("\n");
	$rs = $template->DB->query($row_sql);

	// 利用可能台数をカウント
	$totalMachines = 0;
	$availableMachines = 0;
	$machineList = [];

	while ($row = $rs->fetch(PDO::FETCH_ASSOC)) {
		$totalMachines++;

		$assignFlg = $row["assign_flg"];
		$machineStatus = $row["machine_status"];
		$isLinkMainte = ($assignFlg == 9);

		// 自身への割当は未割り当て扱い
		if ($_login_flg && $assignFlg == 1 && $row["member_no"] == $template->Session->UserInfo["member_no"]) {
			$assignFlg = 0;
		}

		// 3状態判定（空き台・使用中・準備中）
		$isInUse = ($assignFlg == 1);
		$isAvailable = ($assignFlg == 0 && $machineStatus == 1 && $open && !$isLinkMainte);
		$isPreparing = !$isInUse && !$isAvailable;

		if ($isAvailable) {
			$availableMachines++;
		}

		// 状態テキストとクラス
		if ($isAvailable) {
			$statusClass = "available";
			$statusText = "🟢 空き";
		} elseif ($isInUse) {
			$statusClass = "in-use";
			$statusText = "🔴 使用中";
		} else {
			$statusClass = "preparing";
			$statusText = "🟡 準備中";
		}

		$machineList[] = [
			'machine_no' => $row["machine_no"],
			'is_available' => $isAvailable,
			'status_class' => $statusClass,
			'status_text' => $statusText,
			'assign_flg' => $assignFlg
		];
	}

	// 最初の利用可能台を取得（プレイボタン用）
	$firstAvailableMachine = null;
	foreach ($machineList as $machine) {
		if ($machine['is_available']) {
			$firstAvailableMachine = $machine['machine_no'];
			break;
		}
	}

	// prizeball_dataとlayout_dataをパース
	$prizeballData = json_decode($modelData["prizeball_data"], true) ?? [];
	$layoutData = json_decode($modelData["layout_data"], true) ?? [];

	// カテゴリ判定（1:パチンコ, 2:スロット）
	$categoryName = ($modelData["category"] == "1") ? "パチンコ" : "パチスロ";

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 機種情報
	$template->assign("MODEL_NO", $modelData["model_no"], true);
	$template->assign("MODEL_NAME", (FOLDER_LANG == DEFAULT_LANG) ? $modelData["model_name"] : $modelData["model_roman"], true);
	$template->assign("MODEL_CD", $modelData["model_cd"], true);
	$template->assign("MAKER_NAME", (FOLDER_LANG == DEFAULT_LANG) ? $modelData["maker_name"] : $modelData["maker_roman"], true);
	$template->assign("UNIT_NAME", (FOLDER_LANG == DEFAULT_LANG) ? $modelData["unit_name"] : $modelData["unit_roman"], true);
	$template->assign("CATEGORY_NAME", $categoryName, true);

	// 画像 - GCS URL対応
	$imageList = $modelData["image_list"] ?: "noimage.png";
	$imageDetail = $modelData["image_detail"] ?: $imageList;

	// GCS URLでなければローカルパスを追加
	if ($imageList && !preg_match('/^https?:\/\//', $imageList)) {
		$imageList = '/data/img/model/' . $imageList;
	}
	if ($imageDetail && !preg_match('/^https?:\/\//', $imageDetail)) {
		$imageDetail = '/data/img/model/' . $imageDetail;
	}

	$template->assign("IMAGE_LIST", $imageList, true);
	$template->assign("IMAGE_DETAIL", $imageDetail, true);
	$template->assign("DIR_IMG_MODEL_DIR", "", true);  // GCS対応のため空

	// ゲーム情報（prizeball_dataから）
	$template->assign("MAX_PAYOUT", $prizeballData["MAX"] ?? "---", true);
	$template->assign("MAX_RATE", $prizeballData["MAX_RATE"] ?? "---", true);

	// 台情報
	$template->assign("TOTAL_MACHINES", $totalMachines, true);
	$template->assign("AVAILABLE_MACHINES", $availableMachines, true);
	$template->assign("FIRST_AVAILABLE_MACHINE", $firstAvailableMachine ?? "", true);

	// プレイ可能判定
	$canPlay = ($availableMachines > 0 && $open);
	$template->if_enable("CAN_PLAY", $canPlay);
	$template->if_enable("CANNOT_PLAY", !$canPlay);
	$template->if_enable("NEED_LOGIN", !$_login_flg);
	$template->if_enable("LOGGED_IN", $_login_flg);

	// 台リスト
	$template->loop_start("MACHINE_LIST");
	foreach ($machineList as $machine) {
		$template->assign("M_MACHINE_NO", $machine['machine_no'], true);
		$template->assign("M_STATUS_CLASS", $machine['status_class'], true);
		$template->assign("M_STATUS_TEXT", $machine['status_text'], true);
		$template->loop_next();
	}
	$template->loop_end("MACHINE_LIST");

	// 表示
	$template->flush();
}

?>
