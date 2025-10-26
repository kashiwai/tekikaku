<?php
/*
 * test_view.php
 *
 * ログイン不要のテスト用プレイヤー
 */

// インクルード
require_once('../../_etc/require_files_payment.php');
require_once('../../_sys/WebRTCAPI.php');
require_once('../../_etc/webRTC_setting.php');

// パラメータ取得
$machine_no = isset($_GET['NO']) ? intval($_GET['NO']) : 4;

// データベースから機械情報を取得
$template = new TemplateUser(false);

$sql = "SELECT
    dm.machine_no,
    dm.camera_no,
    dm.signaling_id,
    mm.model_name,
    mm.category
FROM dat_machine dm
JOIN mst_model mm ON dm.model_no = mm.model_no
WHERE dm.machine_no = {$machine_no}";

$result = $template->DB->query($sql);
$row = $result->fetchRow(MDB2_FETCHMODE_ASSOC);

if (!$row) {
    die("Machine not found: NO={$machine_no}");
}

// カメラ名
$camera = sprintf("camera_%d_%d", $row["camera_no"], time());

// シグナリングサーバー設定
$sig = explode(":", $GLOBALS["RTC_Signaling_Servers"][$row["signaling_id"]]);
$sighost = $sig[0];
$sigport = $sig[1];

// WebRTC設定
$webRTC = new WebRTCAPI();
$id   = $webRTC->getOneTimeAuthID();
$pass = $webRTC->getOneTimeAuthPASS();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Player - Machine <?php echo $machine_no; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #000;
            color: #fff;
        }
        .container {
            max-width: 1280px;
            margin: 0 auto;
        }
        h1 {
            color: #4CAF50;
        }
        #remoteVideo {
            width: 100%;
            max-width: 1280px;
            height: auto;
            background: #333;
            border: 2px solid #4CAF50;
        }
        .status {
            margin: 20px 0;
            padding: 15px;
            border-radius: 5px;
            font-size: 14px;
        }
        .info { background: #2196F3; }
        .success { background: #4CAF50; }
        .error { background: #f44336; }
        .warning { background: #ff9800; }
        #log {
            margin-top: 20px;
            padding: 10px;
            background: #222;
            border: 1px solid #444;
            height: 200px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry {
            margin: 5px 0;
            padding: 3px 0;
            border-bottom: 1px solid #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎥 Test Player - Machine <?php echo $machine_no; ?></h1>

        <div id="status" class="status info">Initializing...</div>

        <video id="remoteVideo" autoplay playsinline></video>

        <div style="margin: 20px 0;">
            <button onclick="startConnection()" style="padding: 10px 20px; font-size: 16px;">▶️ Start Connection</button>
            <button onclick="stopConnection()" style="padding: 10px 20px; font-size: 16px;">⏹️ Stop</button>
        </div>

        <h3>📊 Connection Info</h3>
        <div style="background: #222; padding: 10px; border-radius: 5px;">
            <p><strong>Camera:</strong> <?php echo $camera; ?></p>
            <p><strong>Signaling Server:</strong> <?php echo $sighost; ?>:<?php echo $sigport; ?></p>
            <p><strong>Category:</strong> <?php echo $row['category'] == 1 ? 'Pachinko' : 'Slot'; ?></p>
        </div>

        <h3>📝 Connection Log</h3>
        <div id="log"></div>
    </div>

    <script src="js/peer_ie.js"></script>
    <script>
        // 設定
        const cameraid = '<?php echo $camera; ?>';
        const peerjskey = '<?php echo $GLOBALS["RTC_PEER_APIKEY"]; ?>';
        const authID = '<?php echo $id; ?>';
        const sigHost = '<?php echo $sighost; ?>';
        const sigPort = <?php echo $sigport; ?>;
        const iceServers = <?php echo $webRTC->getIceServers($camera, false); ?>;

        let peer = null;
        let conn = null;
        const statusDiv = document.getElementById('status');
        const logDiv = document.getElementById('log');
        const remoteVideo = document.getElementById('remoteVideo');

        function log(message, type = 'info') {
            console.log(message);
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.textContent = new Date().toLocaleTimeString() + ' - ' + message;
            logDiv.appendChild(entry);
            logDiv.scrollTop = logDiv.scrollHeight;
        }

        function updateStatus(message, className) {
            statusDiv.textContent = message;
            statusDiv.className = 'status ' + className;
        }

        function startConnection() {
            log('🚀 Starting PeerJS connection...');
            updateStatus('Connecting to signaling server...', 'info');

            peer = new Peer(
                'viewer_' + Math.random().toString(36).substr(2, 9),
                {
                    host: sigHost,
                    port: sigPort,
                    path: '/',  // サーバー側が自動的に/peerjsを追加
                    secure: true,
                    key: peerjskey,
                    token: authID,
                    config: {
                        iceServers: iceServers,
                        iceTransportPolicy: 'all'
                    },
                    debug: 3
                }
            );

            peer.on('open', function(id) {
                log('✅ PeerJS connection opened. ID: ' + id);
                updateStatus('Connected! Calling camera...', 'success');

                // カメラに接続
                log('📞 Calling camera: ' + cameraid);
                const call = peer.call(cameraid, null);

                if (!call) {
                    log('❌ Failed to call camera');
                    updateStatus('Failed to call camera', 'error');
                    return;
                }

                call.on('stream', function(remoteStream) {
                    log('✅ Received remote stream!');
                    updateStatus('✅ Stream received! Playing...', 'success');
                    remoteVideo.srcObject = remoteStream;
                });

                call.on('error', function(err) {
                    log('❌ Call error: ' + err);
                    updateStatus('Call error: ' + err, 'error');
                });

                call.on('close', function() {
                    log('⚠️ Call closed');
                    updateStatus('Call closed', 'warning');
                });
            });

            peer.on('error', function(err) {
                log('❌ PeerJS error: ' + err);
                updateStatus('Error: ' + err, 'error');
            });

            peer.on('close', function() {
                log('⚠️ PeerJS connection closed');
                updateStatus('Connection closed', 'warning');
            });
        }

        function stopConnection() {
            if (peer) {
                peer.destroy();
                peer = null;
                log('⏹️ Connection stopped');
                updateStatus('Stopped', 'info');
            }
            if (remoteVideo.srcObject) {
                remoteVideo.srcObject.getTracks().forEach(track => track.stop());
                remoteVideo.srcObject = null;
            }
        }

        // 自動開始
        log('📱 Test player loaded');
        updateStatus('Ready. Click "Start Connection" to begin.', 'info');
    </script>
</body>
</html>
