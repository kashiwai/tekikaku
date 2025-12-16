<?php
/*
 * signaling.php
 *
 * (C)SmartRams Co.,Ltd. 2025 All Rights Reserved．
 *
 * Signalingサーバー管理
 *
 * PeerJS Signalingサーバーの設定と状態を管理
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
			case 'save':
				SaveSettings($template);
				DispList($template);
				break;
			case 'test':
				TestConnection($template);
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
 * Signaling設定一覧表示
 */
function DispList($template) {
	// 現在の設定値を取得
	$settings = [
		'signaling_host' => defined('SIGNALING_HOST') ? SIGNALING_HOST : 'localhost',
		'signaling_port' => defined('SIGNALING_PORT') ? SIGNALING_PORT : '59000',
		'signaling_key' => defined('SIGNALING_KEY') ? SIGNALING_KEY : 'peerjs',
		'signaling_path' => defined('SIGNALING_PATH') ? SIGNALING_PATH : '/',
		'stun_server' => defined('STUN_SERVER') ? STUN_SERVER : 'stun:stun.l.google.com:19302',
		'turn_server' => defined('TURN_SERVER') ? TURN_SERVER : '',
		'turn_username' => defined('TURN_USERNAME') ? TURN_USERNAME : '',
		'turn_credential' => defined('TURN_CREDENTIAL') ? TURN_CREDENTIAL : ''
	];

	// Signalingサーバー接続テスト
	$connection_status = testSignalingConnection(
		$settings['signaling_host'],
		$settings['signaling_port']
	);

	// テンプレート表示（SmartTemplate API使用）
	$template->open(PRE_HTML . ".html");
	$template->assignCommon();

	// 設定値を個別にアサイン
	$template->assign('signaling_host', $settings['signaling_host']);
	$template->assign('signaling_port', $settings['signaling_port']);
	$template->assign('signaling_key', $settings['signaling_key']);
	$template->assign('signaling_path', $settings['signaling_path']);
	$template->assign('stun_server', $settings['stun_server']);
	$template->assign('turn_server', $settings['turn_server']);
	$template->assign('turn_username', $settings['turn_username']);
	$template->assign('turn_credential', $settings['turn_credential']);
	// connection_statusは配列なので個別にアサイン
	$template->assign('connection_status', $connection_status['status'] ?? 'unknown');
	$template->assign('connection_message', $connection_status['message'] ?? '');
	$template->assign('connection_timestamp', $connection_status['timestamp'] ?? '');

	$template->flush();
}

/**
 * 設定保存
 */
function SaveSettings($template) {
	// POSTデータ取得
	$signaling_host = isset($_POST['signaling_host']) ? trim($_POST['signaling_host']) : '';
	$signaling_port = isset($_POST['signaling_port']) ? trim($_POST['signaling_port']) : '';
	$signaling_key = isset($_POST['signaling_key']) ? trim($_POST['signaling_key']) : '';
	$signaling_path = isset($_POST['signaling_path']) ? trim($_POST['signaling_path']) : '';
	$stun_server = isset($_POST['stun_server']) ? trim($_POST['stun_server']) : '';
	$turn_server = isset($_POST['turn_server']) ? trim($_POST['turn_server']) : '';
	$turn_username = isset($_POST['turn_username']) ? trim($_POST['turn_username']) : '';
	$turn_credential = isset($_POST['turn_credential']) ? trim($_POST['turn_credential']) : '';

	// バリデーション
	if (empty($signaling_host)) {
		throw new Exception('Signalingホストは必須です');
	}
	if (empty($signaling_port)) {
		throw new Exception('Signalingポートは必須です');
	}

	// 設定ファイルに保存（setting.phpを更新）
	$setting_file = '../../_etc/setting.php';
	$content = file_get_contents($setting_file);

	// 各設定値を置換
	$replacements = [
		'/define\(\'SIGNALING_HOST\',.*?\);/s' => "define('SIGNALING_HOST', getenv('SIGNALING_HOST') ?: '$signaling_host');",
		'/define\(\'SIGNALING_PORT\',.*?\);/s' => "define('SIGNALING_PORT', getenv('SIGNALING_PORT') ?: '$signaling_port');",
		'/define\(\'SIGNALING_KEY\',.*?\);/s' => "define('SIGNALING_KEY', getenv('SIGNALING_KEY') ?: '$signaling_key');",
		'/define\(\'SIGNALING_PATH\',.*?\);/s' => "define('SIGNALING_PATH', getenv('SIGNALING_PATH') ?: '$signaling_path');",
		'/define\(\'STUN_SERVER\',.*?\);/s' => "define('STUN_SERVER', getenv('STUN_SERVER') ?: '$stun_server');",
		'/define\(\'TURN_SERVER\',.*?\);/s' => "define('TURN_SERVER', getenv('TURN_SERVER') ?: '$turn_server');",
		'/define\(\'TURN_USERNAME\',.*?\);/s' => "define('TURN_USERNAME', getenv('TURN_USERNAME') ?: '$turn_username');",
		'/define\(\'TURN_CREDENTIAL\',.*?\);/s' => "define('TURN_CREDENTIAL', getenv('TURN_CREDENTIAL') ?: '$turn_credential');"
	];

	foreach ($replacements as $pattern => $replacement) {
		$content = preg_replace($pattern, $replacement, $content);
	}

	// ファイルに書き込み
	file_put_contents($setting_file, $content);
}

/**
 * 接続テスト
 */
function TestConnection($template) {
	$signaling_host = defined('SIGNALING_HOST') ? SIGNALING_HOST : 'localhost';
	$signaling_port = defined('SIGNALING_PORT') ? SIGNALING_PORT : '59000';

	$result = testSignalingConnection($signaling_host, $signaling_port);

	if ($result['status'] == 'success') {
		$_SESSION['test_message'] = '接続テスト成功：Signalingサーバーに接続できました';
	} else {
		$_SESSION['test_message'] = '接続テスト失敗：' . $result['message'];
	}
}

/**
 * Signalingサーバー接続テスト
 */
function testSignalingConnection($host, $port) {
	$timeout = 5;

	// ソケット接続テスト
	$socket = @fsockopen($host, $port, $errno, $errstr, $timeout);

	if ($socket) {
		fclose($socket);
		return [
			'status' => 'success',
			'message' => 'オンライン',
			'timestamp' => date('Y-m-d H:i:s')
		];
	} else {
		return [
			'status' => 'error',
			'message' => "オフライン ($errstr)",
			'timestamp' => date('Y-m-d H:i:s')
		];
	}
}
