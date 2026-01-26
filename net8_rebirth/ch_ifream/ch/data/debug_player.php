<?php
/**
 * プレイヤー接続診断ツール
 *
 * プレイヤー画面が動かない原因を特定するためのデバッグページ
 */

// インクルード
require_once('../../_etc/require_files.php');
require_once('../../_etc/webRTC_setting.php');

// マシン番号をGETパラメータから取得
$machine_no = isset($_GET['NO']) ? (int)$_GET['NO'] : 1;

// テンプレートクラスのインスタンス生成
$template = new TemplateUser(false);

// マシン情報を取得
$sql = (new SqlString($template->DB))
    ->select()
        ->field("dm.machine_no, dm.signaling_id, dm.camera_no, dm.model_no")
        ->field("mm.model_name, mm.category")
        ->field("mc.camera_mac, mc.camera_name")
        ->field("lm.assign_flg, lm.member_no, lm.start_dt")
        ->from("dat_machine dm")
        ->join("left", "mst_model mm", "dm.model_no = mm.model_no")
        ->join("left", "mst_camera mc", "dm.camera_no = mc.camera_no")
        ->join("left", "lnk_machine lm", "dm.machine_no = lm.machine_no")
        ->where()
            ->and("dm.machine_no =", $machine_no, FD_NUM)
    ->createSQL();

$machine = $template->DB->getRow($sql);

// Signaling Server情報を取得
$signaling_servers = $GLOBALS["RTC_Signaling_Servers"];
$signaling_id = $machine['signaling_id'] ?? 'default';
$signaling_server = $signaling_servers[$signaling_id] ?? $signaling_servers['default'];

// カメラID生成（実際のシステムと同じロジック）
$camera_id = sprintf("camera-%03d-%03d",
    $machine['machine_no'] ?? 0,
    $machine['camera_no'] ?? 0
);

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>プレイヤー接続診断 - マシン<?= $machine_no ?></title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #1a1a1a;
            color: #00ff00;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            border-bottom: 2px solid #00ff00;
            padding-bottom: 10px;
        }
        .section {
            background: #2a2a2a;
            border: 1px solid #00ff00;
            padding: 15px;
            margin: 20px 0;
            border-radius: 5px;
        }
        .status {
            display: inline-block;
            padding: 5px 10px;
            margin: 5px;
            border-radius: 3px;
            font-weight: bold;
        }
        .status.ok { background: #00aa00; color: #fff; }
        .status.ng { background: #aa0000; color: #fff; }
        .status.waiting { background: #aaaa00; color: #000; }
        pre {
            background: #000;
            padding: 10px;
            overflow-x: auto;
            border-left: 3px solid #00ff00;
        }
        button {
            background: #00ff00;
            color: #000;
            border: none;
            padding: 10px 20px;
            margin: 5px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 3px;
        }
        button:hover {
            background: #00cc00;
        }
        #log {
            height: 300px;
            overflow-y: auto;
            background: #000;
            padding: 10px;
            font-size: 12px;
        }
        .log-entry {
            margin: 3px 0;
        }
        .log-entry.error { color: #ff0000; }
        .log-entry.success { color: #00ff00; }
        .log-entry.info { color: #ffff00; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 プレイヤー接続診断ツール - マシン <?= $machine_no ?></h1>

        <!-- 1. データベース情報 -->
        <div class="section">
            <h2>📊 データベース情報</h2>
            <?php if ($machine): ?>
                <p><strong>マシン番号:</strong> <?= $machine['machine_no'] ?></p>
                <p><strong>機種名:</strong> <?= $machine['model_name'] ?? '未設定' ?></p>
                <p><strong>カテゴリ:</strong> <?= $machine['category'] == 1 ? 'パチンコ' : 'スロット' ?></p>
                <p><strong>カメラ番号:</strong> <?= $machine['camera_no'] ?? '未設定' ?></p>
                <p><strong>カメラMAC:</strong> <?= $machine['camera_mac'] ?? '未登録' ?></p>
                <p><strong>カメラ名:</strong> <?= $machine['camera_name'] ?? '未設定' ?></p>
                <p><strong>シグナリングID:</strong> <?= $machine['signaling_id'] ?? 'default' ?></p>
                <p><strong>割り当て状態:</strong>
                    <span class="status <?= $machine['assign_flg'] == 9 ? 'ok' : 'ng' ?>">
                        <?= $machine['assign_flg'] == 9 ? 'カメラ待機中' : '状態不明' ?>
                    </span>
                </p>
            <?php else: ?>
                <p class="status ng">⚠️ マシン情報がデータベースに存在しません</p>
            <?php endif; ?>
        </div>

        <!-- 2. WebRTC設定情報 -->
        <div class="section">
            <h2>🌐 WebRTC設定情報</h2>
            <p><strong>シグナリングサーバー:</strong> <?= $signaling_server ?></p>
            <p><strong>カメラID:</strong> <code><?= $camera_id ?></code></p>
            <p><strong>PeerJS API Key:</strong> <?= $GLOBALS["RTC_PEER_APIKEY"] ?></p>
            <p><strong>ICE Servers:</strong></p>
            <pre><?= json_encode($GLOBALS["ICE_SERVERS"], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?></pre>
        </div>

        <!-- 3. 接続テスト -->
        <div class="section">
            <h2>🔌 接続テスト</h2>
            <button onclick="testSignalingServer()">Signalingサーバー接続テスト</button>
            <button onclick="testPeerConnection()">PeerJS接続テスト</button>
            <button onclick="testCameraConnection()">カメラ接続テスト</button>
            <button onclick="clearLog()">ログクリア</button>

            <h3>診断ログ:</h3>
            <div id="log"></div>
        </div>

        <!-- 4. 次のステップ -->
        <div class="section">
            <h2>📋 診断結果に基づく次のステップ</h2>
            <div id="recommendations"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script>
        // 設定値
        const CONFIG = {
            signalingServer: '<?= explode(':', $signaling_server)[0] ?>',
            signalingPort: <?= explode(':', $signaling_server)[1] ?? 443 ?>,
            cameraId: '<?= $camera_id ?>',
            apiKey: '<?= $GLOBALS["RTC_PEER_APIKEY"] ?>',
            iceServers: <?= json_encode($GLOBALS["ICE_SERVERS"]) ?>
        };

        let peer = null;

        // ログ出力関数
        function log(message, type = 'info') {
            const logDiv = document.getElementById('log');
            const entry = document.createElement('div');
            entry.className = 'log-entry ' + type;
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
            console.log(message);
        }

        function clearLog() {
            document.getElementById('log').innerHTML = '';
        }

        // 1. Signalingサーバー接続テスト
        function testSignalingServer() {
            log('🔍 Signalingサーバー接続テスト開始...', 'info');

            const wsUrl = `wss://${CONFIG.signalingServer}:${CONFIG.signalingPort}/peerjs?key=${CONFIG.apiKey}&id=test_${Date.now()}&token=test123`;
            log(`接続先: ${wsUrl}`, 'info');

            const ws = new WebSocket(wsUrl);

            ws.onopen = function() {
                log('✅ Signalingサーバー接続成功！', 'success');
                ws.close();
            };

            ws.onerror = function(err) {
                log(`❌ Signalingサーバー接続失敗: ${err}`, 'error');
            };

            ws.onclose = function() {
                log('接続を切断しました', 'info');
            };
        }

        // 2. PeerJS接続テスト
        function testPeerConnection() {
            log('🔍 PeerJS接続テスト開始...', 'info');

            if (peer) {
                peer.destroy();
            }

            const peerId = 'player_test_' + Date.now();

            peer = new Peer(peerId, {
                host: CONFIG.signalingServer,
                port: CONFIG.signalingPort,
                secure: true,
                key: CONFIG.apiKey,
                config: {
                    iceServers: CONFIG.iceServers
                },
                debug: 3
            });

            peer.on('open', function(id) {
                log(`✅ PeerJS接続成功！ Peer ID: ${id}`, 'success');
                log(`WebSocket状態: ${peer.socket ? 'Connected' : 'Not connected'}`, 'info');

                if (peer.socket) {
                    log(`ReadyState: ${peer.socket.readyState}`, 'info');
                }
            });

            peer.on('error', function(err) {
                log(`❌ PeerJSエラー: ${err.type} - ${err.message}`, 'error');
            });

            peer.on('disconnected', function() {
                log('⚠️ PeerJS切断されました', 'error');
            });

            peer.on('close', function() {
                log('PeerJS接続を閉じました', 'info');
            });
        }

        // 3. カメラ接続テスト
        function testCameraConnection() {
            log('🔍 カメラ接続テスト開始...', 'info');
            log(`カメラID: ${CONFIG.cameraId}`, 'info');

            if (!peer || !peer.open) {
                log('❌ 先にPeerJS接続テストを実行してください', 'error');
                return;
            }

            log(`カメラ "${CONFIG.cameraId}" に接続を試みています...`, 'info');

            const conn = peer.connect(CONFIG.cameraId, {
                metadata: 'test:debug'
            });

            conn.on('open', function() {
                log('✅ カメラとのデータ接続が確立しました！', 'success');
                conn.close();
            });

            conn.on('error', function(err) {
                log(`❌ カメラ接続エラー: ${err}`, 'error');
            });

            conn.on('close', function() {
                log('カメラとの接続を切断しました', 'info');
            });

            // タイムアウト設定
            setTimeout(function() {
                if (conn.open === false) {
                    log('⚠️ カメラ接続タイムアウト（10秒）', 'error');
                    log('カメラ側が起動していない可能性があります', 'error');
                }
            }, 10000);
        }

        // 初期ログ
        log('診断ツールを読み込みました', 'success');
        log('上のボタンをクリックして接続テストを実行してください', 'info');
    </script>
</body>
</html>
