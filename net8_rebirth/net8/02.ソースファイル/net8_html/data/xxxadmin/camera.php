<?php
/*
 * camera.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * カメラマスター管理
 *
 * カメラの一覧表示・登録・編集・削除を行う
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
		$camera_no = isset($_GET['camera_no']) ? intval($_GET['camera_no']) : 0;

		switch ($mode) {
			case 'list':
				DispList($template);
				break;
			case 'detail':
				DispDetail($template, $camera_no);
				break;
			case 'delete':
				DeleteCamera($template, $camera_no);
				DispList($template);
				break;
			case 'save':
				SaveCamera($template);
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
 * カメラ一覧表示
 */
function DispList($template) {
	// カメラ一覧取得
	$sql = "
		SELECT
			mc.camera_no,
			mc.camera_mac,
			mc.camera_name,
			mc.add_dt,
			mc.upd_dt,
			mcl.state,
			mcl.system_name,
			mcl.ip_address,
			COUNT(DISTINCT dm.machine_no) as machine_count
		FROM mst_camera mc
		LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
		LEFT JOIN dat_machine dm ON mc.camera_no = dm.camera_no AND dm.del_flg = 0
		WHERE mc.del_flg = 0
		GROUP BY mc.camera_no
		ORDER BY mc.camera_no ASC
	";

	$result = $template->DB->query($sql);
	$camera_list = [];

	while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
		$camera_list[] = $row;
	}

	// テンプレート表示（SmartTemplate API使用）
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// カメラ数
	$template->assign('camera_count', count($camera_list));

	// カメラリストをループで設定
	if (count($camera_list) > 0) {
		$template->loop_start('CAMERA');
		foreach ($camera_list as $camera) {
			$template->assign('camera_no', $camera['camera_no']);
			$template->assign('camera_mac', $camera['camera_mac']);
			$template->assign('camera_name', $camera['camera_name']);
			$template->assign('ip_address', $camera['ip_address'] ?? '-');
			$template->assign('machine_count', $camera['machine_count']);
			$template->assign('add_dt', date('Y/m/d H:i', strtotime($camera['add_dt'])));

			// 状態バッジの条件分岐
			$template->if_enable('state_online', $camera['state'] == 1);
			$template->if_enable('state_offline', $camera['state'] == 0);
			$template->if_enable('state_unknown', !isset($camera['state']) || ($camera['state'] != 0 && $camera['state'] != 1));

			$template->loop_next();
		}
		$template->loop_end('CAMERA');
		$template->if_enable('HAS_CAMERAS', true);
	} else {
		$template->if_enable('HAS_CAMERAS', false);
	}

	$template->flush();
}

/**
 * カメラ詳細表示
 */
function DispDetail($template, $camera_no) {
	$camera_data = null;

	if ($camera_no > 0) {
		// 既存データ取得
		$sql = "
			SELECT
				mc.*,
				mcl.state,
				mcl.system_name,
				mcl.ip_address,
				mcl.identifing_number,
				mcl.product_name,
				mcl.cpu_name,
				mcl.core,
				mcl.uuid,
				mcl.license_id
			FROM mst_camera mc
			LEFT JOIN mst_cameralist mcl ON mc.camera_mac = mcl.mac_address
			WHERE mc.camera_no = :camera_no AND mc.del_flg = 0
		";

		$stmt = $template->DB->prepare($sql);
		$stmt->execute(['camera_no' => $camera_no]);
		$camera_data = $stmt->fetch(PDO::FETCH_ASSOC);
	}

	// テンプレート開く
	$template->open(PRE_HTML . "_detail.html");
	$template->assignCommon();

	// カメラデータを個別に割り当て
	if ($camera_data) {
		$template->assign('camera_no', isset($camera_data['camera_no']) ? $camera_data['camera_no'] : '');
		$template->assign('camera_name', isset($camera_data['camera_name']) ? $camera_data['camera_name'] : '');
		$template->assign('camera_mac', isset($camera_data['camera_mac']) ? $camera_data['camera_mac'] : '');
		$template->assign('camera_status', isset($camera_data['camera_status']) ? $camera_data['camera_status'] : '1');
		$template->assign('state', isset($camera_data['state']) ? $camera_data['state'] : '0');
		$template->assign('system_name', isset($camera_data['system_name']) ? $camera_data['system_name'] : '-');
		$template->assign('ip_address', isset($camera_data['ip_address']) ? $camera_data['ip_address'] : '-');
		$template->assign('identifing_number', isset($camera_data['identifing_number']) ? $camera_data['identifing_number'] : '-');
		$template->assign('product_name', isset($camera_data['product_name']) ? $camera_data['product_name'] : '-');
		$template->assign('cpu_name', isset($camera_data['cpu_name']) ? $camera_data['cpu_name'] : '-');
		$template->assign('core', isset($camera_data['core']) ? $camera_data['core'] : '-');
		$template->assign('uuid', isset($camera_data['uuid']) ? $camera_data['uuid'] : '-');
		$template->assign('license_id', isset($camera_data['license_id']) ? $camera_data['license_id'] : '-');
	} else {
		// 新規作成時の初期値
		$template->assign('camera_no', '');
		$template->assign('camera_name', '');
		$template->assign('camera_mac', '');
		$template->assign('camera_status', '1');
		$template->assign('state', '0');
		$template->assign('system_name', '-');
		$template->assign('ip_address', '-');
		$template->assign('identifing_number', '-');
		$template->assign('product_name', '-');
		$template->assign('cpu_name', '-');
		$template->assign('core', '-');
		$template->assign('uuid', '-');
		$template->assign('license_id', '-');
	}

	$template->assign('mode', $camera_no > 0 ? 'edit' : 'new');

	// 割り当て済み台一覧取得
	if ($camera_no > 0) {
		$machine_sql = "
			SELECT
				dm.machine_no,
				dm.machine_cd,
				mm.model_name,
				dm.release_date,
				dm.machine_status
			FROM dat_machine dm
			INNER JOIN mst_model mm ON dm.model_no = mm.model_no
			WHERE dm.camera_no = :camera_no AND dm.del_flg = 0
			ORDER BY dm.machine_no ASC
		";

		$stmt = $template->DB->prepare($machine_sql);
		$stmt->execute(['camera_no' => $camera_no]);
		$machine_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// 台一覧をループで展開
		if (count($machine_list) > 0) {
			$template->loop_start('MACHINES');
			foreach ($machine_list as $machine) {
				$template->assign('machine_no', isset($machine['machine_no']) ? $machine['machine_no'] : '');
				$template->assign('machine_cd', isset($machine['machine_cd']) ? $machine['machine_cd'] : '');
				$template->assign('model_name', isset($machine['model_name']) ? $machine['model_name'] : '');
				$template->assign('release_date', isset($machine['release_date']) ? $machine['release_date'] : '');
				$template->assign('machine_status', isset($machine['machine_status']) ? $machine['machine_status'] : '0');
				$template->loop_next();
			}
			$template->loop_end('MACHINES');
		}
	}

	// 表示
	$template->flush();
}

/**
 * カメラ保存
 */
function SaveCamera($template) {
	$camera_no = isset($_POST['camera_no']) ? intval($_POST['camera_no']) : 0;
	$camera_mac = isset($_POST['camera_mac']) ? trim($_POST['camera_mac']) : '';
	$camera_name = isset($_POST['camera_name']) ? trim($_POST['camera_name']) : '';

	// バリデーション
	if (empty($camera_mac)) {
		throw new Exception('MACアドレスは必須です');
	}
	if (empty($camera_name)) {
		throw new Exception('カメラ名は必須です');
	}

	// MACアドレスフォーマットチェック
	if (!preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $camera_mac)) {
		throw new Exception('MACアドレスの形式が正しくありません');
	}

	// 大文字に統一
	$camera_mac = strtoupper(str_replace('-', ':', $camera_mac));

	if ($camera_no > 0) {
		// 更新
		$sql = "
			UPDATE mst_camera SET
				camera_mac = :camera_mac,
				camera_name = :camera_name,
				upd_no = 1,
				upd_dt = NOW()
			WHERE camera_no = :camera_no
		";

		$stmt = $template->DB->prepare($sql);
		$stmt->execute([
			'camera_no' => $camera_no,
			'camera_mac' => $camera_mac,
			'camera_name' => $camera_name
		]);
	} else {
		// 新規登録
		$sql = "
			INSERT INTO mst_camera (
				camera_mac,
				camera_name,
				del_flg,
				add_no,
				add_dt
			) VALUES (
				:camera_mac,
				:camera_name,
				0,
				1,
				NOW()
			)
		";

		$stmt = $template->DB->prepare($sql);
		$stmt->execute([
			'camera_mac' => $camera_mac,
			'camera_name' => $camera_name
		]);
	}
}

/**
 * カメラ削除
 */
function DeleteCamera($template, $camera_no) {
	if ($camera_no <= 0) {
		throw new Exception('削除対象が指定されていません');
	}

	// 論理削除
	$sql = "
		UPDATE mst_camera SET
			del_flg = 1,
			del_no = 1,
			del_dt = NOW()
		WHERE camera_no = :camera_no
	";

	$stmt = $template->DB->prepare($sql);
	$stmt->execute(['camera_no' => $camera_no]);
}
