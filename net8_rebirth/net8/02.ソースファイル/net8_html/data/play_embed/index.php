<?php
/**
 * play_embed/index.php - iframe埋め込み専用プレイヤー
 *
 * SDK API経由のsessionId認証のみで動作（通常ログイン不要）
 * 外部サイトからのiFrame埋め込み用
 *
 * Version: 1.0.1
 * Created: 2025-12-17
 */

// CORS設定（外部サイトからの埋め込みを許可）- 最初に設定
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 既存のrequire_filesを使用
require_once('../../_etc/require_files.php');
require_once('../../_sys/WebRTCAPI.php');
require_once('../../_etc/webRTC_setting.php');

// パラメータ取得
$machineNo = $_GET['NO'] ?? null;
$sessionId = $_GET['sessionId'] ?? null;
$userId = $_GET['userId'] ?? null;
$cameraIdParam = $_GET['cameraId'] ?? null; // URLから直接カメラID（PeerID）を受け取る

// 必須パラメータチェック
if (!$machineNo || !$sessionId) {
    http_response_code(400);
    outputError('必須パラメータが不足しています: NO, sessionId');
    exit;
}

error_log("🎮 play_embed: Validating session - machineNo={$machineNo}, sessionId={$sessionId}");

try {
    // DB接続（既存の方法を使用）
    $pdo = get_db_connection();

    // sessionIdを検証（game_sessionsテーブル）
    $stmt = $pdo->prepare("
        SELECT
            gs.id,
            gs.session_id,
            gs.machine_no,
            gs.member_no,
            gs.partner_user_id,
            gs.status,
            gs.started_at,
            dm.signaling_id,
            dm.camera_no,
            dm.model_no,
            mm.model_name,
            mm.model_cd,
            mm.category,
            mm.prizeball_data,
            mm.layout_data,
            mm.image_reel,
            mc.camera_name,
            cp.credit as convcredit,
            cp.point as convplaypoint
        FROM game_sessions gs
        JOIN dat_machine dm ON gs.machine_no = dm.machine_no
        LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        LEFT JOIN mst_convertPoint cp ON dm.convert_no = cp.convert_no
        WHERE gs.session_id = :session_id
        AND gs.status IN ('active', 'playing', 'pending')
        AND gs.machine_no = :machine_no
    ");

    $stmt->execute([
        'session_id' => $sessionId,
        'machine_no' => $machineNo
    ]);

    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        error_log("❌ play_embed: Invalid or expired session - sessionId={$sessionId}, machineNo={$machineNo}");
        http_response_code(401);
        outputError('無効または期限切れのセッションです');
        exit;
    }

    error_log("✅ play_embed: Session validated - member_no={$session['member_no']}, camera_name={$session['camera_name']}");

    // セッションステータスを 'playing' に更新
    $updateStmt = $pdo->prepare("
        UPDATE game_sessions
        SET status = 'playing',
            updated_at = NOW()
        WHERE session_id = :session_id
    ");
    $updateStmt->execute(['session_id' => $sessionId]);

    // WebRTC認証情報を生成
    $webRTC = new WebRTCAPI();
    $oneTimeAuthID = $webRTC->getOneTimeAuthID();

    // メンバー番号をハッシュ化
    $memberNo = sha1(sprintf("%06d", $session['member_no']));

    // シグナリングサーバー情報
    $signalingId = $session['signaling_id'] ?? 1;
    $sigServers = $GLOBALS["RTC_Signaling_Servers"] ?? [];
    $sigInfo = isset($sigServers[$signalingId]) ? explode(":", $sigServers[$signalingId]) : ['mgg-signaling-production-c1bd.up.railway.app', '443'];
    $sigHost = $sigInfo[0];
    $sigPort = $sigInfo[1] ?? '443';

    // カメラ情報（URLパラメータを優先、なければDBから取得）
    $cameraId = $cameraIdParam ?: ($session['camera_name'] ?? '');
    error_log("📹 play_embed: cameraId={$cameraId} (from param={$cameraIdParam}, from DB={$session['camera_name']})");

    // ICE Servers設定
    $iceServers = $webRTC->getIceServers($cameraId);

    // レイアウトデータ
    $prizeballData = json_decode($session['prizeball_data'] ?? '{}', true) ?: [];
    $layoutData = json_decode($session['layout_data'] ?? '{}', true) ?: [];
    if (!isset($layoutData["hide"])) $layoutData["hide"] = [];

    // 押し順対応
    if (!in_array("pushorder", $layoutData["hide"])) {
        $layoutData["hide"][] = "nonepushorder";
    }

    // lnk_machine を更新（台の使用状況）
    $linkStmt = $pdo->prepare("
        UPDATE lnk_machine
        SET assign_flg = 1,
            exit_flg = 0,
            member_no = :member_no,
            onetime_id = :onetime_id,
            start_dt = NOW()
        WHERE machine_no = :machine_no
    ");
    $linkStmt->execute([
        'member_no' => $session['member_no'],
        'onetime_id' => $oneTimeAuthID,
        'machine_no' => $machineNo
    ]);

    // エラーメッセージ定義
    $errorMessages = [
        "U5050" => "システムエラーが発生しました。",
        "U5051" => "接続がタイムアウトしました。",
        "U5052" => "カメラとの接続に失敗しました。",
        "U5053" => "セッションが終了しました。",
        "U5054" => "残高が不足しています。",
        "U5058" => "サーバーエラーが発生しました。",
        "U5059" => "接続が切断されました。",
        "U5060" => "再接続中...",
        "U5061" => "認証に失敗しました。",
        "U5062" => "台が使用中です。",
        "U5063" => "営業時間外です。",
        "U5064" => "台が使用できません。",
        "U5066" => "エラーが発生しました。",
        "U5067" => "操作がタイムアウトしました。",
        "U5069" => "通信エラーが発生しました。"
    ];

    // HTMLを出力
    outputPlayerHTML([
        'machineNo' => $machineNo,
        'sessionId' => $sessionId,
        'cameraId' => $cameraId,
        'memberNo' => $memberNo,
        'authId' => $oneTimeAuthID,
        'sigHost' => $sigHost,
        'sigPort' => $sigPort,
        'peerJsKey' => $GLOBALS["RTC_PEER_APIKEY"] ?? 'peerjs',
        'iceServers' => $iceServers,
        'modelName' => $session['model_name'] ?? '',
        'modelCd' => $session['model_cd'] ?? '',
        'category' => $session['category'] ?? 1,
        'prizeballData' => $prizeballData,
        'layoutData' => $layoutData,
        'convCredit' => $session['convcredit'] ?? 1,
        'convPlaypoint' => $session['convplaypoint'] ?? 1,
        'errorMessages' => $errorMessages,
        'imageReel' => $session['image_reel'] ?? '',
        'username' => 'Player'
    ]);

} catch (PDOException $e) {
    error_log("❌ play_embed DB error: " . $e->getMessage());
    http_response_code(500);
    outputError('データベースエラー: ' . $e->getMessage());
} catch (Exception $e) {
    error_log("❌ play_embed error: " . $e->getMessage());
    http_response_code(500);
    outputError('サーバーエラー: ' . $e->getMessage());
}

/**
 * エラーページ出力
 */
function outputError($message) {
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - NET8 Player</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background: #1a1a2e;
            color: #fff;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .error-container {
            text-align: center;
            padding: 40px;
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }
        .error-message {
            font-size: 18px;
            color: #ff6b6b;
            margin-bottom: 20px;
        }
        .error-detail {
            font-size: 14px;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">⚠️</div>
        <div class="error-message"><?= htmlspecialchars($message) ?></div>
        <div class="error-detail">セッションが無効か、接続に問題があります。</div>
    </div>
    <script>
        // 親ウィンドウにエラー通知
        if (window.parent !== window) {
            window.parent.postMessage({
                type: 'NET8_ERROR',
                error: 'initialization_failed',
                message: '<?= addslashes($message) ?>'
            }, '*');
        }
    </script>
</body>
</html>
    <?php
}

/**
 * プレイヤーHTML出力
 */
function outputPlayerHTML($data) {
    $timestamp = time();
    $layoutJson = json_encode($data['layoutData']);
    $errorMessagesJson = json_encode($data['errorMessages']);
    $iceServersJson = $data['iceServers'];

    // カテゴリによってパチンコ/スロットを判定
    $isSlot = ($data['category'] == 2);
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
    <meta name="format-detection" content="telephone=no">
    <title>NET8 Player - <?= htmlspecialchars($data['modelName']) ?></title>

    <!-- Webfont -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css?family=Chakra+Petch" rel="stylesheet">

    <!-- Play CSS -->
    <link href="/css/play.css?ts=<?= $timestamp ?>" rel="stylesheet">
    <link href="/data/play_embed/css/embed.css?ts=<?= $timestamp ?>" rel="stylesheet">
</head>
<script>
    // 設定値をJavaScript変数に設定
    var languageMode   = 'ja';
    var machineno      = '<?= $data['machineNo'] ?>';
    var cameraid       = '<?= $data['cameraId'] ?>';
    var peerjskey      = '<?= $data['peerJsKey'] ?>';
    var memberno       = '<?= $data['memberNo'] ?>';
    var authID         = '<?= $data['authId'] ?>';
    var sigHost        = '<?= $data['sigHost'] ?>';
    var sigPort        = '<?= $data['sigPort'] ?>';
    var iceServers     = <?= $iceServersJson ?>;
    var autopush       = 'false';
    var purchase       = [];
    var errorMessages  = <?= $errorMessagesJson ?>;
    var layoutOption   = <?= $layoutJson ?>;
    var convCredit     = <?= (int)$data['convCredit'] ?>;
    var convPlaypoint  = <?= (int)$data['convPlaypoint'] ?>;
    var browserVersion = 'embed/1.0';
    var username       = '<?= htmlspecialchars($data['username']) ?>';
    var closeTime      = '06:00';
    var pachi_rate     = '0';
    var sessionId      = '<?= $data['sessionId'] ?>';

    // 埋め込みモードフラグ
    var isEmbedMode    = true;

    console.log('🎮 NET8 Embed Player Config:', {
        machineNo: machineno,
        cameraId: cameraid,
        sigHost: sigHost,
        sigPort: sigPort
    });
</script>
<script>
    // ダブルタップによる拡大を禁止
    var t = 0;
    document.documentElement.addEventListener('touchend', function (e) {
        var now = new Date().getTime();
        if ((now - t) < 350) {
            e.preventDefault();
        }
        t = now;
    }, false);
</script>
<body class="play-page embed-mode" oncontextmenu="return false;">
    <!-- ローディング表示 -->
    <div id="loading">
        <div class="loader">Loading...</div>
        <div id="loading_connect">接続中です。しばらくお待ち下さい。<span id="phase"></span></div>
        <div id="loading_pay" style="display:none;">精算中です。しばらくお待ち下さい。</div>
        <div id="loadinglost" style="display:none;">
            <a id="loading_cancel" class="btn btn-primary btn-loading game-after-button">Exit</a>
            <a id="btn_reload2" class="btn btn-primary btn-loading">Reload</a>
        </div>
        <div id="connectlost" style="display:none;">
            <div><span id="conn_error_message"></span></div>
            <a class="btn btn-primary btn-loading game-after-button">Exit</a>
            <a id="btn_reload" class="btn btn-primary btn-loading">Reload</a>
        </div>
    </div>

    <!-- ナビゲーションバー -->
    <nav class="navbar fixed-top navbar-expand-lg navbar-dark bg-dark" style="display:none;">
        <div class="container">
            <div class="game_status">
                <div class="machine-no">
                    <div>台番</div>
                    <span id="machine_no"><?= $data['machineNo'] ?></span>
                </div>
                <div class="game-situation-group">
                    <div class="game-situation situation-gc">
                        <div>ゲーム数</div>
                        <span id="count">0</span>
                    </div>
                    <div class="game-situation situation-bb">
                        <div>大当り</div>
                        <span id="bb_count">0</span>
                    </div>
                    <div class="game-situation situation-rb" id="rb_back">
                        <div>初当り</div>
                        <span id="rb_count">0</span>
                    </div>
                </div>
            </div>
            <div class="game_status txt-btn-group">
                <div class="num-title">PT</div>
                <span id="point">0</span>
                <span id="point_add" class="add-point"></span>
            </div>
        </div>
    </nav>

    <!-- メインコンテンツ -->
    <main class="main-content">
        <!-- ビデオコンテナ -->
        <div id="videocontainer" class="video-container">
            <video id="video" autoplay playsinline muted></video>
            <div id="video_overlay" class="video-overlay"></div>
        </div>

        <!-- コントロールパネル -->
        <div id="control_panel" class="control-panel" style="display:none;">
            <div class="control-buttons">
                <button id="btn_start" class="btn btn-game btn-start">START</button>
                <button id="btn_stop" class="btn btn-game btn-stop" disabled>STOP</button>
            </div>
            <div class="bet-controls">
                <button id="btn_bet_down" class="btn btn-bet">-</button>
                <span id="bet_amount">1</span>
                <button id="btn_bet_up" class="btn btn-bet">+</button>
            </div>
        </div>

        <!-- ステータスバー -->
        <div id="status_bar" class="status-bar" style="display:none;">
            <div class="status-item">
                <span class="status-label">CREDIT</span>
                <span id="credit" class="status-value">0</span>
            </div>
            <div class="status-item">
                <span class="status-label">WIN</span>
                <span id="win" class="status-value">0</span>
            </div>
        </div>

        <!-- 終了ボタン -->
        <div id="exit_area" class="exit-area" style="display:none;">
            <button id="btn_exit" class="btn btn-exit">終了</button>
        </div>
    </main>

    <!-- JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>

    <!-- play_v2のJSを使用 - view_functionsを先に読み込む（_touch等の変数定義が必要） -->
    <script src="/data/play_v2/js/view_functions.js?ts=<?= $timestamp ?>"></script>
    <?php if ($isSlot): ?>
    <!-- スロット用 -->
    <script src="/data/play_v2/js/view_auth.js?ts=<?= $timestamp ?>"></script>
    <?php else: ?>
    <!-- パチンコ用 -->
    <script src="/data/play_v2/js/view_auth_pachi.js?ts=<?= $timestamp ?>"></script>
    <?php endif; ?>

    <!-- 埋め込み専用JS -->
    <script src="/data/play_embed/js/embed_player.js?ts=<?= $timestamp ?>"></script>

    <script>
        // 初期化 - 親ウィンドウに準備完了を通知
        $(document).ready(function() {
            console.log('🎮 NET8 Embed Player initialized');
            console.log('📹 Camera ID:', cameraid);
            console.log('🔌 Signaling:', sigHost + ':' + sigPort);

            // 親ウィンドウにready通知
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'NET8_PLAYER_READY',
                    machineNo: machineno,
                    sessionId: sessionId,
                    cameraId: cameraid
                }, '*');
            }
        });
    </script>
</body>
</html>
    <?php
}
?>
