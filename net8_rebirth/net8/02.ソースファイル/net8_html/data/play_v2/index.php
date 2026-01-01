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
 * @author   村上 俊行
 * @version  1.0
 * @since    2019/02/10 初版作成 村上 俊行
 * @since    2020/06/29 修正     村上 俊行 Notice対応
 * @since    2020/07/15 修正     村上 俊行 台湾API時のNotice対応
 * @since    2020/09/18 修正     村上 俊行 シグナリングサーバPort変更対応
 * @since    2020/12/17 修正     村上 俊行 筐体障害対応用メッセージを追加
 * @since    2023/02/24 修正     村上 俊行 lnk_machineのexit_flgの初期化を追加
 */

// インクルード
require_once('../../_etc/require_files_payment.php');	// 決済用requireファイル

require_once('../../_sys/WebRTCAPI.php');				// requireファイル
require_once('../../_etc/webRTC_setting.php');			// webRTCセッティングファイル
require_once('../../api/v1/helpers/currency_helper.php');	// 通貨対応ヘルパー

// X-Frame-Options と CORS セキュリティ設定（SDK対応）
// frame_security.phpがCORSヘッダーを設定し、CSP/X-Frame-Optionsは設定しない
try {
    @require_once(__DIR__ . '/frame_security.php');
} catch (Exception $e) {
    // frame_security.phpの読み込みエラーは無視（ゲーム動作を優先）
    error_log('❌ Frame security initialization failed: ' . $e->getMessage());

    // エラー時もCSP/X-Frame-Optionsは設定しない（すべてのiFrame埋め込みを許可）
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
}

// 項目定義
define("PRE_1p_HTML",  "play/index_pachi");				// テンプレートHTMLプレフィックス（パチンコ縦画面）
define("PRE_1l_HTML",  "play/index_pachi_ls_v2");		// テンプレートHTMLプレフィックス（パチンコ横画面）
define("PRE_2p_HTML",  "play/index_slot");				// テンプレートHTMLプレフィックス（スロット縦画面）
//2020-01-07 version2
define("PRE_2l_HTML",  "play/index_slot_ls_v2");		// テンプレートHTMLプレフィックス（スロット横画面）
define("ERR_HTML",     "play/no_assign");				// テンプレートHTMLプレフィックス(エラー時）

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
 * VIEW画面表示
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @return	なし
 */
function DispTop($template) {

	//webRTCのauth設定
	$webRTC = new WebRTCAPI();

	// データ取得
	getData($_GET, array("NO","TESTMODE"));

	//ログインチェック（SDK連携対応）
	if ( !isset($template->Session->UserInfo) ) {
		// SDK経由かチェック：game_sessionsからmember_noを取得
		$machineNo = $_GET["NO"] ?? null;
		$sdkMemberNo = null;

		if ($machineNo) {
			try {
				error_log("🔍 SDK session check: machine_no={$machineNo}");

				// 既存のDB接続を使用（$template->DBが使用可能なため）
				$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
					->select()
						->field("member_no, partner_user_id, session_id, currency")
						->from("game_sessions")
						->where()
							->and( "machine_no =", $machineNo, FD_NUM)
							->and( "status IN ('playing', 'pending')", "", FD_SKIP)
						->orderBy("started_at DESC")
						->limit(1)
					->createSQL("\n");

				$sdkSession = $template->DB->getRow($sql);
				error_log("🔍 SDK session query result: " . json_encode($sdkSession));

				if ($sdkSession && isset($sdkSession['member_no']) && $sdkSession['member_no']) {
					// SDK経由のセッション：mst_memberから情報を取得
					$sdkMemberNo = $sdkSession['member_no'];
					error_log("🔍 Found SDK session: member_no={$sdkMemberNo}");

					$memberSql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
						->select()
							->field("member_no, nickname, mail, point, currency")
							->from("mst_member")
							->where()
								->and( "member_no =", $sdkMemberNo, FD_NUM)
						->createSQL("\n");

					$memberInfo = $template->DB->getRow($memberSql);
					error_log("🔍 Member info: " . json_encode($memberInfo));

					if ($memberInfo && isset($memberInfo['member_no'])) {
						// 通貨情報を決定（優先順位: game_sessions.currency > mst_member.currency > 'JPY'）
						$currency = $sdkSession['currency'] ?? $memberInfo['currency'] ?? 'JPY';

						// SmartSessionのsetUserInfo()メソッドを使用してセッションを作成
						// 注: UserInfoは連想配列として保存される
						$_SESSION['UserInfo'] = $memberInfo;
						$_SESSION['UserInfo']['sdk_session'] = true;
						$_SESSION['UserInfo']['partner_user_id'] = $sdkSession['partner_user_id'];
						$_SESSION['UserInfo']['currency'] = $currency;

						// $template->Session->UserInfoを再読み込み
						$template->Session->setSession();

						error_log("✅ SDK user session created: member_no={$sdkMemberNo}, partner_user_id={$sdkSession['partner_user_id']}, currency={$currency}");
					} else {
						error_log("❌ Member info not found for member_no={$sdkMemberNo}");
					}
				} else {
					error_log("🔍 No SDK session found for machine_no={$machineNo}");
				}
			} catch (Exception $e) {
				error_log("❌ SDK session creation error: " . $e->getMessage());
				error_log("❌ Stack trace: " . $e->getTraceAsString());
			}
		}

		// セッションがまだ存在しない場合はエラー
		if ( !isset($template->Session->UserInfo) ) {
			error_log("❌ No session found, redirecting to error");
			//ログインしていないのでどこかへ飛ばす？
			DispError( $template, "U5001" );
			return;
		}
	}

	// 2021-07-16 ここで前回アクセスからの時間をチェック
	if ( array_key_exists("lastplaytime", $_SESSION) ) {
		$spanTime = Time() - $_SESSION["lastplaytime"];
		if ( $spanTime <= 3 ){
			// DispError( $template, "U5050" );
			$template->open("play/reload_error.html");
			$template->assignCommon();
			$template->assign("MACHINE_NO" ,  $_GET["NO"] );
			$template->assign("ERROR_MESSAGE" ,  $template->message("U5070") );
			$template->flush();
			return;
		}
	}

	//テスターフラグ
	$chksql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("member_no, tester_flg")
			->from("mst_member")
			->where()
				->and( "member_no =", $template->Session->UserInfo["member_no"], FD_NUM)
		->createSQL("\n");
	$testerRow = $template->DB->getRow($chksql);

	//営業時間チェック（"TESTMODE=on"ならば回避できる）
	$nowTime = date("H:i");

	if( $testerRow["tester_flg"] == "0"){
		//営業時間チェック（"TESTMODE=on"ならば回避できる）
		$nowTime = date("H:i");

		if ( GLOBAL_CLOSE_TIME <= $nowTime && GLOBAL_OPEN_TIME > $nowTime){
			DispError( $template, "U5004" );
			return;
		}
	}

	/* testmodeは消す
	if ( $_GET["TESTMODE"] != "on" ){
		$jsDir = "js";
	} else {
		$jsDir = "js_src";
	}
	*/
	$jsDir = "/data/play_v2/js";

	//ブラウザチェック
	$browserStatus = $webRTC->checkBrowser(true);
	if ( $browserStatus["status"] == false ){
		DispError( $template, "U5006" );
		return;
	}

	//別のところでまだ未清算で残っている場合は他の台に移動させない
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("machine_no")
			->from("lnk_machine")
			->where()
				->and( "member_no =",    $template->Session->UserInfo["member_no"], FD_NUM)
				->and( "assign_flg = ",  "1", FD_NUM)
		->createSQL("\n");
	
	$row = $template->DB->getRow($sql);
	
	//2020-06-24 notice対応
	if ( array_key_exists("machine_no", $row) ){
		if( $row["machine_no"] != "" &&  $row["machine_no"] != $_GET["NO"] ){
		
			//台情報の取得
			$sql = (new SqlString())
				->setAutoConvert( [$template->DB,"conv_sql"] )
				->select()
					->field("dm.machine_no")
					->field("mm.model_name,mm.model_roman")
					->from("dat_machine dm")
					->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
					->where()
						->and( "machine_no =", $row["machine_no"], FD_NUM)
				->createSQL("\n");

			$machineRow = $template->DB->getRow($sql);
		
			DispError( $template, "U5003", $machineRow );
			return;
		}
	}
	
	//台チェック
	$chksql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("machine_no, machine_status")
			->from("dat_machine")
			->where()
				->and( "machine_no =", $_GET["NO"], FD_NUM)
		->createSQL("\n");
	$datmachineRow = $template->DB->getRow($chksql);
	//
	if( $testerRow["tester_flg"] == "0"){
		if( $datmachineRow["machine_status"] != "1"){
			DispError( $template, "U5005", $datmachineRow );
			return;
		}
	}

	//ログインユーザーの取得
	$memberNo = sha1(sprintf("%06d", $template->Session->UserInfo["member_no"]));

	//既に使用していないかのDBを確認
	// トランザクション開始
	$template->DB->autoCommit(false);

	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("machine_no,assign_flg,member_no")
			->from("lnk_machine")
			->where()
				->and( "machine_no =", $_GET["NO"], FD_NUM)
			->forUpdate()
		->createSQL("\n");

	$row = $template->DB->getRow($sql);
	$start_dt = date("Y-m-d H:i:s");
	
	//現在稼働していない
	if ( $row["assign_flg"] == "9" || mb_strlen($row["machine_no"]) == 0 ){
		//rollBackしてトランザクション終了
		$template->DB->rollBack();
		DispError( $template, "U5005" );
		return;
	
	}
	//既にアサインされている
	if ( $row["assign_flg"] == "1" ){
		//自分がアサインしているか？
		if ( $row["member_no"] !=  $template->Session->UserInfo["member_no"] ) {
			//rollBackしてトランザクション終了
			$template->DB->rollBack();
			DispError( $template, "U5002" );
			return;
		}
		//再接続なので開始日時はセットさせない
		$start_dt = "";
	}

	//ワンタイムパスの発行
	$oneTimeAuthID = $webRTC->getOneTimeAuthID();

	//machine確保
	$sql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
		->update( "lnk_machine" )
			->set()
				->value( "assign_flg",        "1", FD_NUM)
				->value( "exit_flg",          "0",  FD_NUM)
				->value( "member_no",         $template->Session->UserInfo["member_no"], FD_NUM)
				->value( "onetime_id",        $oneTimeAuthID, FD_STR)
				->value( true, "start_dt",    $start_dt, FD_DATE)
			->where()
				->and( "machine_no =", $_GET["NO"], FD_NUM)
		->createSQL("\n");
	$result = $template->DB->query($sql);
	$cnt = $result->rowCount();

	$template->DB->autoCommit(true);


	//台情報の取得
	$sql = (new SqlString())
		->setAutoConvert( [$template->DB,"conv_sql"] )
		->select()
			->field("dm.machine_no,dm.signaling_id,dm.camera_no,mm.category,mm.prizeball_data,mm.layout_data,mc.camera_name,mm.image_reel")
			->field("mm.model_name,mm.model_roman,mm.model_cd")
			->field("cp.credit as convcredit,cp.point as convplaypoint")
			->from("dat_machine dm")
			->join("left", "mst_model mm", "dm.model_no = mm.model_no" )
			->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no" )
			->join("left", "mst_convertPoint cp", "dm.convert_no = cp.convert_no" )
			->where()
				->and( "dm.machine_no =", $_GET["NO"], FD_NUM)
		->createSQL("\n");


	$machineRow = $template->DB->getRow($sql);

	$prizeball_data = json_decode( $machineRow["prizeball_data"], true );
	$layout_data    = json_decode( $machineRow["layout_data"], true );
	if ( !isset($layout_data["hide"]) ) $layout_data["hide"] = array();

	// 押し順対応[pushorder]が非表示に無い場合、押し順非対応[nonepushorder]を非表示にする
	if (!in_array("pushorder", $layout_data["hide"])) {
		$layout_data["hide"][] = "nonepushorder";
	}

	//接続先の情報
	$camera  = $machineRow["camera_name"];
	//2020-09-18 シグナリングサーバのPort設定追加
	$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$machineRow["signaling_id"]]);
	$sighost = $sig[0];
	$sigport = $sig[1];
	//$sighost = $GLOBALS["RTC_Signaling_Servers"][$machineRow["signaling_id"]];

	//シグナリングサーバへ登録
	if ( !$webRTC->addKeySignaling( $oneTimeAuthID, $machineRow["signaling_id"] ) ){
		DispError( $template, $webRTC->errorMessage() );
		return;
	}
	
	//ポイント購入一覧
	$pointJson = getPointListJson( $template->DB );
	//使用するエラーメッセージを設定
	$errorMessageJson = json_encode( array( 
				 "U5050" => $template->message("U5050")
				,"U5051" => $template->message("U5051")
				,"U5052" => $template->message("U5052")
				,"U5053" => $template->message("U5053")
				,"U5054" => $template->message("U5054")
				,"U5058" => $template->message("U5058")
				,"U5059" => $template->message("U5059")
				,"U5060" => $template->message("U5060")
				,"U5061" => $template->message("U5061")
				,"U5062" => $template->message("U5062")
				,"U5063" => $template->message("U5063")
				,"U5064" => $template->message("U5064")
				,"U5066" => $template->message("U5066")
				,"U5067" => $template->message("U5067")
				//2020-12-17 メッセージを追加
				,"U5069" => $template->message("U5069")
	) );

	// 画面表示開始
	if ( $machineRow["category"] == "1" ){
		if ( $layout_data["video_portrait"] == "1" ){
			$template->open(PRE_1p_HTML . ".html");
		} else {
			$template->open(PRE_1l_HTML . ".html");
		}
	} else {
		if ( $layout_data["video_portrait"] == "1" ){
			$template->open(PRE_2p_HTML . ".html");
		} else {
			$template->open(PRE_2l_HTML . ".html");
		}
	}
	
	//決済用データ抽出用
	$SPOINT = new SettlementPoint( $template->DB );

	// ============================================================
	// 統計データ取得（BB数、RB数、差枚数）
	// ============================================================
	$stats = [
		'bb_today' => 0,
		'bb_1day' => 0,
		'bb_2day' => 0,
		'rb_today' => 0,
		'rb_1day' => 0,
		'rb_2day' => 0,
		'bb_total' => 0,
		'rb_total' => 0,
		'max_diff_today' => 0,
		'max_diff_ever' => 0,
		'history' => []
	];

	try {
		// 基準日時の取得
		$refToday = GetRefTimeTodayExt();  // 今日の基準日（04:00基準）
		$ref1DayAgo = date('Y-m-d', strtotime($refToday . ' -1 day'));
		$ref2DayAgo = date('Y-m-d', strtotime($refToday . ' -2 day'));

		// 日別集計クエリ（この台のみ）
		$sqlDailyStats = "
			SELECT
				DATE(start_dt) as play_date,
				SUM(bb_count) as total_bb,
				SUM(rb_count) as total_rb,
				MAX(out_point - in_point) as max_diff
			FROM his_play
			WHERE machine_no = ?
			  AND start_dt >= ?
			GROUP BY DATE(start_dt)
			ORDER BY play_date DESC
		";

		$stmt = $template->DB->prepare($sqlDailyStats);
		$stmt->execute([$_GET["NO"], $ref2DayAgo . ' 00:00:00']);

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$playDate = $row['play_date'];

			if ($playDate == $refToday) {
				$stats['bb_today'] = (int)$row['total_bb'];
				$stats['rb_today'] = (int)$row['total_rb'];
				$stats['max_diff_today'] = (int)$row['max_diff'];
			} elseif ($playDate == $ref1DayAgo) {
				$stats['bb_1day'] = (int)$row['total_bb'];
				$stats['rb_1day'] = (int)$row['total_rb'];
			} elseif ($playDate == $ref2DayAgo) {
				$stats['bb_2day'] = (int)$row['total_bb'];
				$stats['rb_2day'] = (int)$row['total_rb'];
			}
		}

		// 累計と過去最高差枚数を取得
		$sqlTotalStats = "
			SELECT
				SUM(bb_count) as total_bb,
				SUM(rb_count) as total_rb,
				MAX(out_point - in_point) as max_diff_ever
			FROM his_play
			WHERE machine_no = ?
		";

		$stmt = $template->DB->prepare($sqlTotalStats);
		$stmt->execute([$_GET["NO"]]);
		$row = $stmt->fetch(PDO::FETCH_ASSOC);

		if ($row) {
			$stats['bb_total'] = (int)$row['total_bb'];
			$stats['rb_total'] = (int)$row['total_rb'];
			$stats['max_diff_ever'] = (int)$row['max_diff_ever'];
		}

		// 直近10回のプレイ履歴（グラフ用）
		$sqlHistory = "
			SELECT
				bb_count,
				rb_count,
				(out_point - in_point) as diff_count,
				start_dt
			FROM his_play
			WHERE machine_no = ?
			  AND (bb_count > 0 OR rb_count > 0)
			ORDER BY start_dt DESC
			LIMIT 10
		";

		$stmt = $template->DB->prepare($sqlHistory);
		$stmt->execute([$_GET["NO"]]);

		while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
			$stats['history'][] = [
				'bb' => (int)$row['bb_count'],
				'rb' => (int)$row['rb_count'],
				'diff' => (int)$row['diff_count'],
				'date' => $row['start_dt']
			];
		}

		// 履歴を時系列順に並び替え（古い順）
		$stats['history'] = array_reverse($stats['history']);
	} catch (Exception $e) {
		// エラー時はデフォルト値を使用
		error_log('統計データ取得エラー: ' . $e->getMessage());
	}

	// 通貨情報を取得（優先順位: UserInfo['currency'] > mst_member.currency > 'JPY'）
	$userCurrency = $template->Session->UserInfo['currency'] ?? null;

	// UserInfoに通貨がない場合、mst_memberから取得
	if (!$userCurrency) {
		$currencySql = (new SqlString())->setAutoConvert( [$template->DB,"conv_sql"] )
			->select()
				->field("currency")
				->from("mst_member")
				->where()
					->and( "member_no =", $template->Session->UserInfo["member_no"], FD_NUM)
			->createSQL("\n");
		$currencyRow = $template->DB->getRow($currencySql);
		$userCurrency = $currencyRow['currency'] ?? 'JPY';
	}

	// 通貨を正規化（デフォルトはJPY）
	$userCurrency = normalizeCurrency($userCurrency);

	// ポイント残高を通貨フォーマットで取得
	$userPoint = $template->Session->UserInfo['point'] ?? 0;
	$currencyInfo = getCurrencyInfo($userCurrency);
	$formattedBalance = formatCurrency($userPoint, $userCurrency);

	error_log("💰 User currency: {$userCurrency}, point: {$userPoint}, formatted: {$formattedBalance}");

	$template->assignCommon();
	
	$template->assign("CAMERA_ID"       , $camera);
	$template->assign("MACHINE_NO"      , $machineRow["machine_no"]);
	$template->assign("MODEL_NAME"      , $machineRow["model_name"]);
	$template->assign("MODEL_ROMAN"     , $machineRow["model_roman"]);
	$template->assign("MODEL_CD"        , $machineRow["model_cd"]);
	$template->assign("PEERJSKEY"       , $GLOBALS["RTC_PEER_APIKEY"]);
	$template->assign("MEMBERNO"        , $memberNo );
	$template->assign("AUTHID"          , $oneTimeAuthID);
	//2020-06-24 pass廃止
	//$template->assign("AUTHPASS"        , $pass);
	$template->assign("SIGHOST"         , $sighost);
	//2020-09-18 port追加
	$template->assign("SIGPORT"         , $sigport);
	$template->assign("ICESERVERS"      , $webRTC->getIceServers($camera) );
	//2020-06-24 Notice対応
	$template->assign("AUTO_PUSH"       , ( AUTO_PUSH ) ? true : false );
	//$template->assign("AUTO_PUSH"       , $GLOBALS["AUTO_PUSH"]);
	
	$template->assign("PURCHASE"        , $pointJson);
	$template->assign("NOTICETIME"      , NOTICE_CLOSE_TIME);
	$template->assign("CONVCREDIT"      , $machineRow["convcredit"]);
	$template->assign("CONVPLAYPOINT"   , $machineRow["convplaypoint"]);

	$template->assign("MAX"             , $prizeball_data["MAX"]);
	$template->assign("MAX_RATE"        , $prizeball_data["MAX_RATE"]);
	$template->assign("NAVEL"           , $prizeball_data["NAVEL"]);
	$template->assign("TULIP"           , $prizeball_data["TULIP"]);
	$template->assign("ATTACKER1"       , $prizeball_data["ATTACKER1"]);
	$template->assign("ATTACKER2"       , $prizeball_data["ATTACKER2"]);
	$template->assign("ERRORMESSAGES"   , $errorMessageJson);
	$template->assign("LAYOUTOPTION"    , json_encode($layout_data));

	$template->assign("IMAGE_REEL"      , $machineRow["image_reel"]);
	$template->assign("TIMESTAMP"       , "ts=".time() );
	$template->assign("JSDIR"           , $jsDir );
	$template->assign("LANG"            , FOLDER_LANG );
	$template->assign("USERNAME"        , $template->Session->UserInfo["nickname"] );
	// 通貨対応
	$template->assign("CURRENCY"        , $userCurrency );
	$template->assign("CURRENCY_SYMBOL" , $currencyInfo['symbol'] );
	$template->assign("CURRENCY_NAME"   , $currencyInfo['name'] );
	$template->assign("FORMATTED_BALANCE", $formattedBalance );
	// 2021-06-04 追加
	$template->assign("CLOSETIME"       , GLOBAL_CLOSE_TIME );
	$rate = $template->DB->getSystemSetting("PACHI_RATE");
	$template->assign("PACHI_RATE"      , $rate );
	if ($rate == 0){
		$template->assign("RATE_DISPLAY", "d-none");
	}
	
	if ( isset($layout_data["bonus_push"]) ){
		if( count($layout_data["bonus_push"]) > 1){
			$template->assign("BONUS_IMAGE_HTML" ,  buildBunusHtml($layout_data));
			$template->assign("BONUS_IMAGE2_HTML" , buildBunusHtml($layout_data, 2));
			$template->if_enable("PUSH_BONUS", true);
		}else{
			$template->if_enable("PUSH_BONUS", false);
		}
	}else{
		$template->if_enable("PUSH_BONUS", false);
	}
	
	
	$template->assign("PAYMENT_URL"    , PAYMENT_URL, true);
	$template->assign("PAYMENT_HIDDEN" , $SPOINT->hiddenTag(), false);			//hiddenタグを直接挿入


	$template->assign("BROWSERVERSION"  , "{$browserStatus["name"]}/{$browserStatus["version"]}" );

	// ============================================================
	// 統計データをテンプレートに渡す
	// ============================================================
	$template->assign("STATS_BB_TODAY", $stats['bb_today'], true);
	$template->assign("STATS_BB_1DAY", $stats['bb_1day'], true);
	$template->assign("STATS_BB_2DAY", $stats['bb_2day'], true);
	$template->assign("STATS_RB_TODAY", $stats['rb_today'], true);
	$template->assign("STATS_RB_1DAY", $stats['rb_1day'], true);
	$template->assign("STATS_RB_2DAY", $stats['rb_2day'], true);
	$template->assign("STATS_BB_TOTAL", $stats['bb_total'], true);
	$template->assign("STATS_RB_TOTAL", $stats['rb_total'], true);
	$template->assign("STATS_MAX_DIFF_TODAY", number_format($stats['max_diff_today']), true);
	$template->assign("STATS_MAX_DIFF_EVER", number_format($stats['max_diff_ever']), true);
	$template->assign("STATS_HISTORY_JSON", json_encode($stats['history']), true);

	// 最後にアクセスした時間を記録
	$_SESSION["lastplaytime"] = time();

	$template->flush();
}

/**
 * エラー処理
 * @access	private
 * @param	object	$template		テンプレートクラスオブジェクト
 * @param	string	$errno			エラー番号
 * @return	なし
 */
function DispError( $template, $errno, $row=array() ) {
	// 画面表示開始
	$template->open(ERR_HTML . ".html");
	$template->assignCommon();
	
	//2020-06-24 Notice対応
	$row["machine_no"]  = (array_key_exists("machine_no", $row)) ? $row["machine_no"] : "";
	$row["model_name"]  = (array_key_exists("model_name", $row)) ? $row["model_name"] : "";
	$row["model_roman"] = (array_key_exists("model_roman", $row)) ? $row["model_roman"] : "";

	$message = $template->message( $errno );
	if ( count($row) > 0 ){
		if ( FOLDER_LANG == DEFAULT_LANG ){
			$message = sprintf( $message, $row["machine_no"], $row["model_name"], $row["machine_no"], $row["model_name"] );
		} else {
			$message = sprintf( $message, $row["machine_no"], $row["model_roman"], $row["machine_no"], $row["model_roman"] );
		}
	}
	
	$template->assign("ERRMSG"    , $message );

	$template->flush();
}

/**
 * point購入リスト情報のjson化
 * @access	private
 * @param	object	$DB		DBクラスオブジェクト
 * @return	なし
 */
function getPointListJson($DB) {
	$sql = (new SqlString())->setAutoConvert( [$DB,"conv_sql"] )
		->select()
			->field("purchase_type,amount,point")
			->from("mst_purchasePoint")
			->where()
				->and( "del_flg =", "0", FD_NUM)
			->orderby("purchase_type,amount")
		->createSQL("\n");

	$pointall = $DB->getAll($sql, PDO::FETCH_ASSOC);

	$jsonArray = array();
	$draw = array();
	foreach( $pointall as $row ){
		$ary = $row;
		//2020-07-15 台湾API時のNotice対応
		if( array_key_exists( $row["purchase_type"], $GLOBALS["viewPurchaseType"] ) ){
			$ary["purchaseType"] = $GLOBALS["viewPurchaseType"][$row["purchase_type"]];
			$ary["amountType"]   = $GLOBALS["viewAmountType"][$row["purchase_type"]];
			$ary["pointUnit"]    = $GLOBALS["viewUnitList"]["1"];
		}
		if ( $row["purchase_type"] == "11" ){
			$draw[] = $ary;
		} else {
			$jsonArray[] = $ary;
		}
	}
	$jsonArray = array_merge($jsonArray, $draw);
	
	return( json_encode( $jsonArray ) );
}

/**
 * 目押し用html作成
 * @access	private
 * @param	$layout_json
 * @return	なし
 */
function buildBunusHtml($layout, $no="") {
	
	$template = "<li class=\"dropdown-item px-2\"><a class=\"selectbonus%%no%%\" value=\"bb%%idx%%\"><img id=\"bb%%idx%%_img%%no%%\" src=\"/img/base/%%path%%\"></a></li>";
	
	$html = "";
	$idx = 0;
	foreach( $layout["bonus_push"] as $rec ){
		if ( trim($rec["path"]) != '' ){
			$rechtml = $template;
			$rechtml = str_replace("%%no%%",   $no, $rechtml);
			$rechtml = str_replace("%%idx%%",  $idx, $rechtml);
			$rechtml = str_replace("%%path%%", $rec["path"], $rechtml);
			$html .= $rechtml;
		}
		$idx++;
	}
	return( $html );
}

?>
