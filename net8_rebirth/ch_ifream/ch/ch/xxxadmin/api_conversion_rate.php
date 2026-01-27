<?php
/*
 * api_conversion_rate.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved.
 *
 * API専用ポイント変換レート管理画面
 *
 * 韓国パチンコプラットフォーム統合のための変換レート設定
 *
 * @package
 * @author   NET8 Development Team
 * @version  1.0
 * @since    2025/12/31 初版作成
 */

// インクルード
require_once('../../_etc/require_files_admin.php');
// 項目定義
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

/**
 * メイン処理
 * @access	public
 * @param	なし
 * @return	なし
 */
function main() {
	try {
		// 管理系表示コントロールのインスタンス生成
		$template = new TemplateAdmin();

		// データ取得
		getData($_GET, array("M"));

		// 実処理
		$mainWin = true;
		switch ($_GET["M"]) {
			case "apply":			// 適用処理
				$mainWin = false;
				ApplyConversionRate($template);
				break;

			case "complete":		// 完了画面
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
 * @return	なし
 */
function DispList($template) {

	// 現在の変換レート一覧を取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("convert_no, convert_name, point, credit, draw_point")
			->from("mst_convertPoint")
			->where()
				->and( "del_flg =", "0", FD_NUM)
			->orderby("convert_no ASC")
		->createSQL("\n");

	$convertRates = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

	// API用の1:1レートが存在するかチェック
	$apiConvertNo = null;
	$hasApiRate = false;
	foreach ($convertRates as $rate) {
		if ($rate['point'] == 1 && $rate['credit'] == 1) {
			$apiConvertNo = $rate['convert_no'];
			$hasApiRate = true;
			break;
		}
	}

	// 各台の現在の設定を取得
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.machine_no, dm.convert_no, mm.model_name, mm.model_cd")
			->field("cp.point, cp.credit, cp.draw_point")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no")
			->join("left", "mst_convertPoint cp", "dm.convert_no = cp.convert_no")
			->where()
				->and( "dm.del_flg =", "0", FD_NUM)
			->orderby("dm.machine_no ASC")
		->createSQL("\n");

	$machines = $template->DB->getAll($sql, PDO::FETCH_ASSOC);

	// 統計情報
	$totalMachines = count($machines);
	$apiMachines = 0;
	$normalMachines = 0;

	foreach ($machines as $machine) {
		$rate = ($machine['credit'] > 0) ? round($machine['point'] / $machine['credit'], 2) : 0;
		if ($rate == 1) {
			$apiMachines++;
		} else {
			$normalMachines++;
		}
	}

	// 画面表示開始
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 統計情報
	$template->assign("TOTAL_MACHINES", $totalMachines);
	$template->assign("API_MACHINES", $apiMachines);
	$template->assign("NORMAL_MACHINES", $normalMachines);
	$template->assign("HAS_API_RATE", $hasApiRate ? "1" : "0");
	$template->assign("API_CONVERT_NO", $apiConvertNo ?? "");

	// 変換レート一覧
	$template->loop_start("CONVERT_RATE");
	foreach ($convertRates as $rate) {
		$ratio = ($rate['credit'] > 0) ? round($rate['point'] / $rate['credit'], 2) : 0;
		$isApiRate = ($rate['point'] == 1 && $rate['credit'] == 1);

		$template->assign("CONVERT_NO", $rate['convert_no']);
		$template->assign("CONVERT_NAME", $rate['convert_name'] ?? '');
		$template->assign("POINT", number_formatEx($rate['point']));
		$template->assign("CREDIT", number_formatEx($rate['credit']));
		$template->assign("DRAW_POINT", number_formatEx($rate['draw_point']));
		$template->assign("RATIO", $ratio);
		$template->if_enable("IS_API_RATE", $isApiRate);

		$template->loop_next();
	}
	$template->loop_end("CONVERT_RATE");

	// 台一覧
	$template->loop_start("MACHINE");
	foreach ($machines as $machine) {
		$rate = ($machine['credit'] > 0) ? round($machine['point'] / $machine['credit'], 2) : 0;
		$isApiMode = ($rate == 1);

		$template->assign("MACHINE_NO", $machine['machine_no']);
		$template->assign("MODEL_NAME", $machine['model_name'] ?? '');
		$template->assign("MODEL_CD", $machine['model_cd'] ?? '');
		$template->assign("CONVERT_NO", $machine['convert_no']);
		$template->assign("CURRENT_RATIO", $rate);
		$template->if_enable("IS_API_MODE", $isApiMode);

		$template->loop_next();
	}
	$template->loop_end("MACHINE");

	// 表示
	$template->flush();
}

/**
 * 適用処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function ApplyConversionRate($template) {

	// データ取得
	getData($_POST, array("action", "target_convert_no", "apply_all"));

	$action = $_POST["action"];  // "create_api_rate" or "apply_to_machines"
	$targetConvertNo = isset($_POST["target_convert_no"]) ? intval($_POST["target_convert_no"]) : 0;
	$applyAll = isset($_POST["apply_all"]) ? $_POST["apply_all"] : "0";

	$message = "";
	$updatedCount = 0;

	try {
		$template->DB->autoCommit(false);

		if ($action === "create_api_rate") {
			// API用の1:1レートを作成

			// 既に存在するかチェック
			$sql = "SELECT convert_no FROM mst_convertPoint WHERE point = 1 AND credit = 1 AND del_flg = 0";
			$existing = $template->DB->getRow($sql, PDO::FETCH_ASSOC);

			if ($existing) {
				$apiConvertNo = $existing['convert_no'];
				$message = "API用変換レート(1:1)は既に存在します (convert_no: {$apiConvertNo})";
			} else {
				// 新規作成
				$maxConvertNo = $template->DB->getOne("SELECT MAX(convert_no) FROM mst_convertPoint");
				$newConvertNo = $maxConvertNo + 1;

				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->insert("mst_convertPoint")
					->set()
						->value("convert_no", $newConvertNo, FD_NUM)
						->value("convert_name", "API用 (1:1)", FD_STR)
						->value("point", 1, FD_NUM)
						->value("credit", 1, FD_NUM)
						->value("draw_point", 0, FD_NUM)
						->value("del_flg", 0, FD_NUM)
						->value("add_no", 1, FD_NUM)
						->value(true, "add_dt", "NOW()", FD_SKIP)
						->value("upd_no", 1, FD_NUM)
						->value(true, "upd_dt", "NOW()", FD_SKIP)
					->createSQL("\n");

				$template->DB->query($sql);
				$apiConvertNo = $newConvertNo;
				$message = "API用変換レート(1:1)を作成しました (convert_no: {$apiConvertNo})";
			}

			$_SESSION["api_convert_no"] = $apiConvertNo;
			$_SESSION["apply_message"] = $message;

		} elseif ($action === "apply_to_machines") {
			// 台に適用

			if ($targetConvertNo <= 0) {
				throw new Exception("変換レート番号が指定されていません");
			}

			if ($applyAll === "1") {
				// 全台に適用
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->update("dat_machine")
					->set()
						->value("convert_no", $targetConvertNo, FD_NUM)
						->value("upd_no", 1, FD_NUM)
						->value(true, "upd_dt", "NOW()", FD_SKIP)
					->where()
						->and("del_flg =", "0", FD_NUM)
					->createSQL("\n");

				$result = $template->DB->query($sql);
				$updatedCount = $result->rowCount();
				$message = "全{$updatedCount}台の変換レートを適用しました (convert_no: {$targetConvertNo})";

			} else {
				// 個別台に適用
				getData($_POST, array("machine_nos"));
				$machineNos = isset($_POST["machine_nos"]) ? $_POST["machine_nos"] : array();

				if (empty($machineNos)) {
					throw new Exception("適用対象の台が選択されていません");
				}

				foreach ($machineNos as $machineNo) {
					$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
						->update("dat_machine")
						->set()
							->value("convert_no", $targetConvertNo, FD_NUM)
							->value("upd_no", 1, FD_NUM)
							->value(true, "upd_dt", "NOW()", FD_SKIP)
						->where()
							->and("machine_no =", $machineNo, FD_NUM)
						->createSQL("\n");

					$template->DB->query($sql);
					$updatedCount++;
				}

				$message = "選択した{$updatedCount}台の変換レートを適用しました (convert_no: {$targetConvertNo})";
			}

			$_SESSION["updated_count"] = $updatedCount;
			$_SESSION["apply_message"] = $message;
		}

		$template->DB->autoCommit(true);

		// 完了画面へリダイレクト
		header("Location: " . $template->Self . "?M=complete");
		exit;

	} catch (Exception $e) {
		$template->DB->rollBack();
		throw $e;
	}
}

/**
 * 完了画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispComplete($template) {

	$message = isset($_SESSION["apply_message"]) ? $_SESSION["apply_message"] : "処理が完了しました";
	$updatedCount = isset($_SESSION["updated_count"]) ? $_SESSION["updated_count"] : 0;
	$apiConvertNo = isset($_SESSION["api_convert_no"]) ? $_SESSION["api_convert_no"] : "";

	// セッションクリア
	unset($_SESSION["apply_message"]);
	unset($_SESSION["updated_count"]);
	unset($_SESSION["api_convert_no"]);

	// 画面表示開始
	$template->open("admin/complete.html");
	$template->assignCommon();

	$template->assign("MESSAGE", $message);
	$template->assign("UPDATED_COUNT", $updatedCount);
	$template->assign("API_CONVERT_NO", $apiConvertNo);
	$template->assign("BACK_URL", $template->Self);

	// 表示
	$template->flush();
}

?>
