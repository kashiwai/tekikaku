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
$initialPoints = intval($_GET['points'] ?? 0); // 初期ポイント（korea_net8frontから渡される）
$initialCredit = intval($_GET['credit'] ?? 0); // 初期クレジット

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

    // ★重要: mst_member.point からポイント残高を取得（URLパラメータより優先）
    $memberPoints = 0;
    if ($session['member_no']) {
        $pointStmt = $pdo->prepare("SELECT point FROM mst_member WHERE member_no = :member_no");
        $pointStmt->execute(['member_no' => $session['member_no']]);
        $memberData = $pointStmt->fetch(PDO::FETCH_ASSOC);
        if ($memberData) {
            $memberPoints = (int)$memberData['point'];
            error_log("💰 play_embed: Retrieved member points from mst_member - member_no={$session['member_no']}, points={$memberPoints}");
        }
    }
    // URLパラメータのpointsより、mst_member.pointを優先
    if ($memberPoints > 0) {
        $initialPoints = $memberPoints;
    }

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

    // シグナリングID取得
    $signalingId = $session['signaling_id'] ?? 1;

    // メンバー番号をハッシュ化
    $memberNo = sha1(sprintf("%06d", $session['member_no']));

    // シグナリングサーバー情報
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

    // ★重要: play_v2と同じ順序で処理する（lnk_machine更新 → addKeySignaling）
    // トランザクション開始
    $pdo->beginTransaction();

    try {
        // lnk_machine を更新（台の使用状況）- FOR UPDATE で排他ロック
        $linkStmt = $pdo->prepare("
            SELECT machine_no, assign_flg, member_no
            FROM lnk_machine
            WHERE machine_no = :machine_no
            FOR UPDATE
        ");
        $linkStmt->execute(['machine_no' => $machineNo]);
        $linkRow = $linkStmt->fetch(PDO::FETCH_ASSOC);

        // 既にアサインされているか確認（他のユーザーが使用中の場合）
        if ($linkRow && $linkRow['assign_flg'] == '1' && $linkRow['member_no'] != $session['member_no']) {
            $pdo->rollBack();
            error_log("❌ play_embed: Machine already assigned to another user");
            http_response_code(409);
            outputError('この台は他のユーザーが使用中です');
            exit;
        }

        // lnk_machine を更新
        $updateStmt = $pdo->prepare("
            UPDATE lnk_machine
            SET assign_flg = 1,
                exit_flg = 0,
                member_no = :member_no,
                onetime_id = :onetime_id,
                start_dt = NOW()
            WHERE machine_no = :machine_no
        ");
        $updateStmt->execute([
            'member_no' => $session['member_no'],
            'onetime_id' => $oneTimeAuthID,
            'machine_no' => $machineNo
        ]);

        // コミット
        $pdo->commit();
        error_log("✅ play_embed: lnk_machine updated - machineNo={$machineNo}, member_no={$session['member_no']}, onetime_id={$oneTimeAuthID}");

    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("❌ play_embed: Failed to update lnk_machine - " . $e->getMessage());
        http_response_code(500);
        outputError('台の確保に失敗しました');
        exit;
    }

    // シグナリングサーバーへ認証ID登録（lnk_machine更新後に実行 - play_v2と同じ順序）
    if (!$webRTC->addKeySignaling($oneTimeAuthID, $signalingId)) {
        error_log("❌ play_embed: Failed to register auth key with signaling server");
        http_response_code(500);
        outputError('シグナリングサーバーへの登録に失敗しました: ' . $webRTC->errorMessage());
        exit;
    }
    error_log("✅ play_embed: Auth key registered with signaling server - authId={$oneTimeAuthID}, signalingId={$signalingId}");

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
        'username' => 'Player',
        'initialPoints' => $initialPoints,
        'initialCredit' => $initialCredit
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

    // 初期ポイント・クレジット（korea_net8frontから渡される）
    var initialPoints  = <?= (int)$data['initialPoints'] ?>;
    var initialCredit  = <?= (int)$data['initialCredit'] ?>;

    // 埋め込みモードフラグ
    var isEmbedMode    = true;

    // ★重要: view_auth.jsより先にgameオブジェクトを定義
    // 韓国側からのポイントを初期残高として設定
    var game = {
        'credit'      : initialCredit,
        'playpoint'   : initialPoints,  // 韓国側のポイント残高
        'drawpoint'   : 0,
        'total_count' : 0,
        'bb_count'    : 0,
        'rb_count'    : 0,
        'count'       : 0,
        'min_credit'  : 2,
        'ccc_status'  : '',
        'in_credit'   : 0
    };

    console.log('🎮 NET8 Embed Player Config:', {
        machineNo: machineno,
        cameraId: cameraid,
        sigHost: sigHost,
        sigPort: sigPort,
        initialPoints: initialPoints,
        initialCredit: initialCredit
    });
    console.log('💰 Game object initialized with Korean points:', game.playpoint);
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
        <!-- ビデオコンテナ（play_v2互換） -->
        <div id="videocontainer" class="video-container playing-screen landscape">
            <div class="marquee-container"></div>
            <video id="video" muted="muted" autoplay="autoplay" playsinline style="display:none;"></video>
            <audio id="audio"></audio>
            <div id="video_overlay" class="video-overlay"></div>
            <div id="consolelog" style="display:none;"></div>
        </div>

        <!-- play_v2互換コントロールパネル（2列レイアウト） -->
        <div id="control_panel" class="playing-controls compact-2row">
            <!-- 1列目: 残高・クレジット・変換・精算 -->
            <div class="control-row-1">
                <div class="info-box">
                    <span class="info-label">残高</span>
                    <span id="playpoint" class="info-value"><?= (int)$data['initialPoints'] ?></span>
                </div>
                <div class="info-box credit-box">
                    <span class="info-label">CR</span>
                    <span id="credit" class="info-value" nextnumber="0"><?= (int)$data['initialCredit'] ?></span>
                </div>
                <button class="btn-compact btn-convert" id="convcr-button" onclick="showConvModal()">変換</button>
                <button class="btn-compact btn-pay" id="pay-button" onclick="showPayModal()">精算</button>
            </div>

            <!-- 2列目: ゲームコントロール -->
            <?php if ($isSlot): ?>
            <div class="control-row-2">
                <button class="btn-game maxbet sendBtn" id="sendBtnsb" oncontextmenu="return false">BET</button>
                <button class="btn-game start sendBtn" id="sendBtnss" oncontextmenu="return false">START</button>
                <button class="btn-game stop sendBtn" id="sendBtns1" oncontextmenu="return false">1</button>
                <button class="btn-game stop sendBtn" id="sendBtns2" oncontextmenu="return false">2</button>
                <button class="btn-game stop sendBtn" id="sendBtns3" oncontextmenu="return false">3</button>
                <button class="btn-game auto" id="autoplay_credit" startlabel="AUTO" stoplabel="STOP" waitlabel="WAIT" oncontextmenu="return false">AUTO</button>
            </div>
            <?php else: ?>
            <div class="control-row-2">
                <button class="btn-game sendBtn" id="sendBtnph" oncontextmenu="return false">ハンドル</button>
                <button class="btn-game start sendBtn" id="sendBtnpstart" oncontextmenu="return false">START</button>
            </div>
            <?php endif; ?>
            <div id="animeField" style="display:none;"><span id="animeNumber"></span></div>
        </div>

        <!-- クレジット変換モーダル -->
        <div id="convcr-modal" class="embed-modal" style="display:none;">
            <div class="embed-modal-content">
                <div class="embed-modal-header">
                    <h3>クレジット変換</h3>
                    <button class="embed-modal-close" onclick="hideConvModal()">&times;</button>
                </div>
                <div class="embed-modal-body">
                    <p>変換金額を選択してください</p>
                    <p class="current-points">現在のポイント: <span id="modal-playpoint">0</span> pt</p>
                    <div class="conv-buttons">
                        <button class="conv-amount-btn" data-amount="500" onclick="convertCredit(500)">
                            500 クレジット (2,500pt)
                        </button>
                        <button class="conv-amount-btn" data-amount="1000" onclick="convertCredit(1000)">
                            1,000 クレジット (5,000pt)
                        </button>
                        <button class="conv-amount-btn" data-amount="3000" onclick="convertCredit(3000)">
                            3,000 クレジット (15,000pt)
                        </button>
                        <button class="conv-amount-btn" data-amount="5000" onclick="convertCredit(5000)">
                            5,000 クレジット (25,000pt)
                        </button>
                    </div>
                    <button class="conv-all-btn" onclick="convertAllCredit()">
                        全額変換
                    </button>
                </div>
            </div>
        </div>

        <!-- 精算モーダル -->
        <div id="pay-modal" class="embed-modal" style="display:none;">
            <div class="embed-modal-content">
                <div class="embed-modal-header">
                    <h3>精算確認</h3>
                    <button class="embed-modal-close" onclick="hidePayModal()">&times;</button>
                </div>
                <div class="embed-modal-body">
                    <p>現在のクレジットを精算してゲームを終了しますか？</p>
                    <p class="current-credit">現在のクレジット: <span id="modal-credit">0</span></p>
                    <div class="pay-buttons">
                        <button class="pay-confirm-btn" onclick="confirmPay()">精算する</button>
                        <button class="pay-cancel-btn" onclick="hidePayModal()">キャンセル</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- エラーモーダル -->
        <div id="error-modal" class="embed-modal" style="display:none;">
            <div class="embed-modal-content">
                <div class="embed-modal-header">
                    <h3 id="error-modal_title">エラー</h3>
                    <button class="embed-modal-close" onclick="hideErrorModal()">&times;</button>
                </div>
                <div class="embed-modal-body">
                    <p id="error-modal_message"></p>
                    <button class="pay-cancel-btn" onclick="hideErrorModal()">OK</button>
                </div>
            </div>
        </div>

        <!-- ステータスバー（play_v2互換） -->
        <div id="status_bar" class="status-bar" style="display:none;">
            <div class="status-item">
                <span class="status-label">POINT</span>
                <span id="playpoint" class="status-value">0</span>
            </div>
            <div class="status-item">
                <span class="status-label">TOTAL</span>
                <span id="total_count" class="status-value">0</span>
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

    <!-- Bootstrap モーダル ポリフィル（play_embed用） -->
    <script>
        // Bootstrap の .modal() メソッドが存在しない場合のポリフィル
        (function($) {
            if (!$.fn.modal) {
                $.fn.modal = function(options) {
                    var $this = $(this);

                    if (options === 'hide') {
                        $this.fadeOut(200);
                        return this;
                    }

                    if (options === 'show' || typeof options === 'object' || typeof options === 'undefined') {
                        $this.fadeIn(200);
                        return this;
                    }

                    return this;
                };
            }
        })(jQuery);

        // popstate モーダル対応（ブラウザバック防止）
        history.pushState = function() {};
        history.replaceState = function() {};
    </script>

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
        // モーダル表示関数
        function showConvModal() {
            $('#modal-playpoint').text(game.playpoint || 0);
            $('#convcr-modal').fadeIn(200);
        }
        function hideConvModal() {
            $('#convcr-modal').fadeOut(200);
        }
        function showPayModal() {
            $('#modal-credit').text(game.credit || 0);
            $('#pay-modal').fadeIn(200);
        }
        function hidePayModal() {
            $('#pay-modal').fadeOut(200);
        }
        function hideErrorModal() {
            $('#error-modal').fadeOut(200);
        }

        // クレジット変換（金額指定）
        function convertCredit(amount) {
            console.log('💰 Converting credit:', amount);
            hideConvModal();
            if (typeof dataConnection !== 'undefined' && dataConnection && dataConnection.open) {
                // cca コマンド: Convert Credit Amount
                dataConnection.send(_sendStr('cca', amount));
            } else {
                console.error('DataConnection not available');
                showError('接続エラー', '台との接続が確立されていません');
            }
        }

        // 全額変換
        function convertAllCredit() {
            console.log('💰 Converting all credit');
            hideConvModal();
            if (typeof dataConnection !== 'undefined' && dataConnection && dataConnection.open) {
                // ccc コマンド: Convert Credit (全額)
                dataConnection.send(_sendStr('ccc', ''));
            } else {
                console.error('DataConnection not available');
                showError('接続エラー', '台との接続が確立されていません');
            }
        }

        // 精算実行
        function confirmPay() {
            console.log('💳 Confirming payment');
            hidePayModal();
            if (typeof dataConnection !== 'undefined' && dataConnection && dataConnection.open) {
                dataConnection.send(_sendStr('pay', ''));
            } else {
                console.error('DataConnection not available');
                showError('接続エラー', '台との接続が確立されていません');
            }
        }

        // エラー表示
        function showError(title, message) {
            $('#error-modal_title').text(title);
            $('#error-modal_message').text(message);
            $('#error-modal').fadeIn(200);
        }

        // view_auth.jsのerrorAlert関数をオーバーライド
        function errorAlert(message, title) {
            showError(title || 'エラー', message);
        }

        // 初期化 - 親ウィンドウに準備完了を通知
        $(document).ready(function() {
            console.log('🎮 NET8 Embed Player initialized');
            console.log('📹 Camera ID:', cameraid);
            console.log('🔌 Signaling:', sigHost + ':' + sigPort);
            console.log('💰 Korean Points (Balance):', initialPoints, 'Credit:', initialCredit);

            // 韓国側からのポイントを残高として表示
            // game.playpoint はすでに設定済み（headセクションで初期化）
            $('#point').text(game.playpoint);
            $('#playpoint').text(game.playpoint);
            $('#modal-playpoint').text(game.playpoint);

            // クレジットの表示
            $('#credit').text(game.credit);
            $('#modal-credit').text(game.credit);

            console.log('💰 Balance initialized - Playpoint:', game.playpoint, 'Credit:', game.credit);

            // 親ウィンドウにready通知
            if (window.parent !== window) {
                window.parent.postMessage({
                    type: 'NET8_PLAYER_READY',
                    machineNo: machineno,
                    sessionId: sessionId,
                    cameraId: cameraid,
                    points: game.playpoint,
                    credit: game.credit
                }, '*');
            }
        });

        // ポイント表示を定期的に更新（game.playpointの変更を反映）
        setInterval(function() {
            if (typeof game !== 'undefined') {
                $('#point').text(game.playpoint);
                $('#playpoint').text(game.playpoint);
                $('#credit').text(game.credit);
            }
        }, 500);
    </script>
</body>
</html>
    <?php
}
?>
