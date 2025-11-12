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
		// ステータス判定ロジック
		$camera_state = intval($row['camera_state']);
		$machine_status = intval($row['machine_status']);
		$viewer_count = intval($row['viewer_count']);

		// ステータス判定
		if ($machine_status == 0) {
			$status = 'maintenance';
			$status_label = 'メンテナンス中';
			$status_color = 'warning';
		} elseif ($camera_state != 1) {
			$status = 'offline';
			$status_label = 'オフライン';
			$status_color = 'danger';
			$offline_cameras++;
		} elseif ($viewer_count > 0) {
			$status = 'playing';
			$status_label = '接続中';
			$status_color = 'success';
			$online_cameras++;
		} else {
			$status = 'ready';
			$status_label = '待機中';
			$status_color = 'info';
			$online_cameras++;
		}

		$row['status'] = $status;
		$row['status_label'] = $status_label;
		$row['status_color'] = $status_color;

		$streaming_list[] = $row;
		$total_streaming++;
		$total_viewers += $viewer_count;
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

	// テンプレート開く
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 統計値を設定
	$template->assign('total_streaming', $total_streaming);
	$template->assign('online_cameras', $online_cameras);
	$template->assign('offline_cameras', $offline_cameras);
	$template->assign('total_viewers', $total_viewers);

	// 配信リストをループ処理で展開
	if (count($streaming_list) > 0) {
		$template->loop_start('STREAMING');
		foreach ($streaming_list as $machine) {
			$template->assign('machine_no', isset($machine['machine_no']) ? $machine['machine_no'] : '');
			$template->assign('machine_cd', isset($machine['machine_cd']) ? $machine['machine_cd'] : '');
			$template->assign('machine_status', isset($machine['machine_status']) ? $machine['machine_status'] : '');
			$template->assign('release_date', isset($machine['release_date']) ? $machine['release_date'] : '');
			$template->assign('model_name', isset($machine['model_name']) ? $machine['model_name'] : '');
			$template->assign('model_cd', isset($machine['model_cd']) ? $machine['model_cd'] : '');
			$template->assign('category', isset($machine['category']) ? $machine['category'] : '');
			$template->assign('camera_no', isset($machine['camera_no']) ? $machine['camera_no'] : '');
			$template->assign('camera_name', isset($machine['camera_name']) ? $machine['camera_name'] : '');
			$template->assign('camera_mac', isset($machine['camera_mac']) ? $machine['camera_mac'] : '');
			$template->assign('camera_state', isset($machine['camera_state']) ? $machine['camera_state'] : '0');
			$template->assign('camera_ip', isset($machine['camera_ip']) && $machine['camera_ip'] != '' ? $machine['camera_ip'] : '-');
			$template->assign('camera_system', isset($machine['camera_system']) ? $machine['camera_system'] : '');
			$template->assign('viewer_count', isset($machine['viewer_count']) ? $machine['viewer_count'] : '0');

			// ステータス情報
			$template->assign('status', isset($machine['status']) ? $machine['status'] : 'offline');
			$template->assign('status_label', isset($machine['status_label']) ? $machine['status_label'] : 'オフライン');
			$template->assign('status_color', isset($machine['status_color']) ? $machine['status_color'] : 'danger');

			// カメラ状態表示用
			$template->if_enable('camera_online', isset($machine['camera_state']) && $machine['camera_state'] == 1);
			$template->if_enable('camera_offline', !isset($machine['camera_state']) || $machine['camera_state'] != 1);

			// ステータス別の条件分岐
			$template->if_enable('status_playing', isset($machine['status']) && $machine['status'] == 'playing');
			$template->if_enable('status_ready', isset($machine['status']) && $machine['status'] == 'ready');
			$template->if_enable('status_maintenance', isset($machine['status']) && $machine['status'] == 'maintenance');
			$template->if_enable('status_offline', isset($machine['status']) && $machine['status'] == 'offline');

			$template->loop_next();
		}
		$template->loop_end('STREAMING');
	}

	// カテゴリ統計をループ処理で展開
	if (count($category_stats) > 0) {
		$template->loop_start('CATEGORY');
		foreach ($category_stats as $category) {
			$template->assign('category', isset($category['category']) ? $category['category'] : '');
			$template->assign('category_name', isset($category['category_name']) ? $category['category_name'] : '');
			$template->assign('machine_count', isset($category['machine_count']) ? $category['machine_count'] : '0');
			$template->assign('online_count', isset($category['online_count']) ? $category['online_count'] : '0');

			// 稼働率を計算
			$machine_count = intval($category['machine_count']);
			$online_count = intval($category['online_count']);
			$rate = ($machine_count > 0) ? round(($online_count / $machine_count) * 100, 1) : 0;
			$template->assign('rate', $rate);

			$template->loop_next();
		}
		$template->loop_end('CATEGORY');
	}

	// 表示
	$template->flush();
}
