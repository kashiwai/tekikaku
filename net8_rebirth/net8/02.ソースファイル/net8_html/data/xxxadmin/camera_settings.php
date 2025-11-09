<?php
/*
 * camera_settings.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * カメラ・台割り当て設定管理
 *
 * カメラとマシン（台）の紐付け設定を行う
 *
 * @package
 * @author   System
 * @version  1.0
 * @since    2025/11/06 初版作成
 */

// インクルード
require_once('../../_etc/require_files_admin.php');
define("PRE_HTML", basename(get_self(), ".php"));

// メイン処理
main();

/**
 * メイン処理
 */
function main() {
	try {
		$template = new TemplateAdmin();

		// パラメータ取得
		$mode = isset($_GET['mode']) ? $_GET['mode'] : 'list';
		$machine_no = isset($_GET['machine_no']) ? intval($_GET['machine_no']) : 0;

		switch ($mode) {
			case 'list':
				DispList($template);
				break;
			case 'assign':
				AssignCamera($template);
				DispList($template);
				break;
			case 'unassign':
				UnassignCamera($template, $machine_no);
				DispList($template);
				break;
			default:
				DispList($template);
		}

	} catch (Exception $e) {
		echo '<h1>エラーが発生しました</h1>';
		echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
		exit;
	}
}

/**
 * カメラ割り当て一覧表示
 */
function DispList($template) {
	// 全台一覧取得（カメラ割り当て情報含む）
	$sql = "
		SELECT
			dm.machine_no,
			dm.machine_cd,
			dm.camera_no,
			dm.machine_status,
			dm.release_date,
			mm.model_name,
			mm.model_cd,
			mm.category,
			mc.camera_name,
			mc.camera_mac,
			mcl.state as camera_state,
			mcl.ip_address as camera_ip
		FROM dat_machine dm
		INNER JOIN mst_model mm ON dm.model_no = mm.model_no AND mm.del_flg = 0
		LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no AND mc.del_flg = 0
		LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
		WHERE dm.del_flg = 0
		ORDER BY dm.machine_no ASC
	";

	$result = $template->DB->query($sql);
	$machine_list = [];
	$assigned_count = 0;
	$unassigned_count = 0;

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$machine_list[] = $row;

		if ($row['camera_no']) {
			$assigned_count++;
		} else {
			$unassigned_count++;
		}
	}

	// カメラ一覧取得（割り当て用）
	$camera_sql = "
		SELECT
			camera_no,
			camera_name,
			camera_mac
		FROM mst_camera
		WHERE del_flg = 0
		ORDER BY camera_no ASC
	";

	$camera_result = $template->DB->query($camera_sql);
	$camera_list = [];

	while ($row = $camera_result->fetch(PDO::FETCH_ASSOC)) {
		$camera_list[] = $row;
	}

	// テンプレートに設定
	$template->assign('machine_list', $machine_list);
	$template->assign('camera_list', $camera_list);
	$template->assign('machine_count', count($machine_list));
	$template->assign('assigned_count', $assigned_count);
	$template->assign('unassigned_count', $unassigned_count);

	// 表示
	$template->display(PRE_HTML . ".html");
}

/**
 * カメラ割り当て
 */
function AssignCamera($template) {
	$machine_no = isset($_POST['machine_no']) ? intval($_POST['machine_no']) : 0;
	$camera_no = isset($_POST['camera_no']) ? intval($_POST['camera_no']) : 0;

	if ($machine_no <= 0) {
		throw new Exception('台番号が指定されていません');
	}

	// camera_no が 0 の場合は割り当て解除
	if ($camera_no == 0) {
		$camera_no = null;
	}

	// 更新
	$sql = "
		UPDATE dat_machine SET
			camera_no = :camera_no,
			upd_no = 1,
			upd_dt = NOW()
		WHERE machine_no = :machine_no
	";

	$stmt = $template->DB->prepare($sql);
	$stmt->execute([
		'machine_no' => $machine_no,
		'camera_no' => $camera_no
	]);
}

/**
 * カメラ割り当て解除
 */
function UnassignCamera($template, $machine_no) {
	if ($machine_no <= 0) {
		throw new Exception('台番号が指定されていません');
	}

	$sql = "
		UPDATE dat_machine SET
			camera_no = NULL,
			upd_no = 1,
			upd_dt = NOW()
		WHERE machine_no = :machine_no
	";

	$stmt = $template->DB->prepare($sql);
	$stmt->execute(['machine_no' => $machine_no]);
}
