<?php
/*
 * streaming.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * 配信管理
 *
 * ライブ配信状態の監視と管理を行う
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

		switch ($mode) {
			case 'list':
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
 * 配信状態一覧表示
 */
function DispList($template) {
	// 配信可能台一覧取得（カメラが割り当てられている台）
	$sql = "
		SELECT
			dm.machine_no,
			dm.machine_cd,
			dm.machine_status,
			dm.release_date,
			mm.model_name,
			mm.model_cd,
			mm.category,
			mc.camera_no,
			mc.camera_name,
			mc.camera_mac,
			mcl.state as camera_state,
			mcl.ip_address as camera_ip,
			mcl.system_name as camera_system,
			COUNT(DISTINCT lm.member_no) as viewer_count
		FROM dat_machine dm
		INNER JOIN mst_model mm ON dm.model_no = mm.model_no AND mm.del_flg = 0
		INNER JOIN mst_camera mc ON dm.camera_no = mc.camera_no AND mc.del_flg = 0
		LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
		LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no AND lm.assign_flg = 1
		WHERE dm.del_flg = 0
			AND dm.camera_no IS NOT NULL
		GROUP BY dm.machine_no
		ORDER BY dm.machine_no ASC
	";

	$result = $template->DB->query($sql);
	$streaming_list = [];
	$total_streaming = 0;
	$online_cameras = 0;
	$offline_cameras = 0;
	$total_viewers = 0;

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$streaming_list[] = $row;
		$total_streaming++;

		if ($row['camera_state'] == 1) {
			$online_cameras++;
		} else {
			$offline_cameras++;
		}

		$total_viewers += intval($row['viewer_count']);
	}

	// カテゴリ別統計
	$category_stats_sql = "
		SELECT
			mm.category,
			CASE
				WHEN mm.category = 1 THEN 'パチンコ'
				WHEN mm.category = 2 THEN 'スロット'
				ELSE 'その他'
			END as category_name,
			COUNT(DISTINCT dm.machine_no) as machine_count,
			SUM(CASE WHEN mcl.state = 1 THEN 1 ELSE 0 END) as online_count
		FROM dat_machine dm
		INNER JOIN mst_model mm ON dm.model_no = mm.model_no AND mm.del_flg = 0
		INNER JOIN mst_camera mc ON dm.camera_no = mc.camera_no AND mc.del_flg = 0
		LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
		WHERE dm.del_flg = 0
			AND dm.camera_no IS NOT NULL
		GROUP BY mm.category
	";

	$category_result = $template->DB->query($category_stats_sql);
	$category_stats = [];

	while ($row = $category_result->fetch(PDO::FETCH_ASSOC)) {
		$category_stats[] = $row;
	}

	// テンプレートに設定
	$template->assign('streaming_list', $streaming_list);
	$template->assign('category_stats', $category_stats);
	$template->assign('total_streaming', $total_streaming);
	$template->assign('online_cameras', $online_cameras);
	$template->assign('offline_cameras', $offline_cameras);
	$template->assign('total_viewers', $total_viewers);

	// 表示
	$template->display(PRE_HTML . ".html");
}
