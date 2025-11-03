<?php
/*
 * play_test.php
 *
 * テスト用プレイページ（認証なし）
 *
 * 開発・デバッグ専用：ログイン認証をスキップしてゲーム画面を表示
 */

// インクルード
require_once('../_etc/require_files_payment.php');
require_once('../_sys/WebRTCAPI.php');
require_once('../_etc/webRTC_setting.php');

// 項目定義
define("PRE_1p_HTML",  "play/index_pachi");
define("PRE_1l_HTML",  "play/index_pachi_ls_v2");
define("PRE_2p_HTML",  "play/index_slot");
define("PRE_2l_HTML",  "play/index_slot_ls_v2");
define("ERR_HTML",     "play/no_assign");

// メイン処理
main();

function main() {
	try {
		$template = new TemplateUser(false);

		// テスト用会員情報を強制的にセット
		setupTestSession($template);

		// play_v2のロジックをそのまま使用
		include('play_v2/index.php');

	} catch (Exception $e) {
		echo "<h1>エラー</h1>";
		echo "<pre>" . $e->getMessage() . "</pre>";
		echo "<pre>" . $e->getTraceAsString() . "</pre>";
	}
}

/**
 * テスト用セッション情報をセットアップ
 */
function setupTestSession($template) {
	// test@example.com のユーザー情報を取得
	$sql = "SELECT member_no, nickname, mail, last_name, first_name, state, point, login_dt, draw_point
	        FROM mst_member
	        WHERE mail = 'test@example.com' AND state = '1'";

	$userRow = $template->DB->getRow($sql, MDB2_FETCHMODE_ASSOC);

	if (empty($userRow["member_no"])) {
		throw new Exception("テストユーザー (test@example.com) が見つかりません。データベースを確認してください。");
	}

	// セッションインスタンス生成
	$template->Session = new SmartSession(URL_SSL_SITE . "", SESSION_SEC, SESSION_SID, DOMAIN, true);
	$template->Session->start();
	$template->Session->UserInfo = $userRow;

	// NOパラメータがない場合はデフォルトで1を設定
	if (!isset($_GET["NO"]) || $_GET["NO"] == "") {
		$_GET["NO"] = "1";
	}

	echo "<!-- TEST MODE: User authenticated as " . $userRow["mail"] . " (member_no=" . $userRow["member_no"] . ") -->\n";
	echo "<!-- Machine NO: " . $_GET["NO"] . " -->\n";
}
