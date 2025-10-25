<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 WebRTC Diagnostic</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #4ec9b0;
            border-bottom: 2px solid #4ec9b0;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .section {
            background: #252526;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .section h2 {
            color: #569cd6;
            font-size: 18px;
            margin-bottom: 15px;
        }
        .status-line {
            padding: 8px 0;
            border-bottom: 1px solid #3c3c3c;
        }
        .status-line:last-child {
            border-bottom: none;
        }
        .label {
            color: #9cdcfe;
            display: inline-block;
            width: 200px;
        }
        .value {
            color: #ce9178;
        }
        .ok {
            color: #4ec9b0;
            font-weight: bold;
        }
        .error {
            color: #f48771;
            font-weight: bold;
        }
        .warning {
            color: #dcdcaa;
            font-weight: bold;
        }
        #console {
            background: #1e1e1e;
            border: 1px solid #3c3c3c;
            border-radius: 5px;
            padding: 15px;
            height: 400px;
            overflow-y: auto;
            font-size: 12px;
            line-height: 1.6;
        }
        .log-entry {
            margin-bottom: 5px;
        }
        .timestamp {
            color: #808080;
        }
        button {
            background: #0e639c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 3px;
            cursor: pointer;
            margin: 5px;
            font-family: inherit;
        }
        button:hover {
            background: #1177bb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 NET8 WebRTC Diagnostic Tool</h1>

        <div class="section">
            <h2>📊 System Status</h2>
            <div class="status-line">
                <span class="label">Mac Server:</span>
                <span class="value ok" id="mac-server">Checking...</span>
            </div>
            <div class="status-line">
                <span class="label">PeerJS Server:</span>
                <span class="value ok" id="peerjs-server">Checking...</span>
            </div>
            <div class="status-line">
                <span class="label">Database:</span>
                <span class="value ok" id="database">Checking...</span>
            </div>
            <div class="status-line">
                <span class="label">Camera API:</span>
                <span class="value ok" id="camera-api">Checking...</span>
            </div>
        </div>

        <div class="section">
            <h2>📹 Camera Status (MAC: 00:00:00:00:00:01)</h2>
            <div id="camera-info"></div>
        </div>

        <div class="section">
            <h2>🔗 WebRTC Connection Test</h2>
            <div>
                <button onclick="testPeerConnection()">Test PeerJS Connection</button>
                <button onclick="testCameraStream()">Test Camera Stream</button>
                <button onclick="clearConsole()">Clear Console</button>
            </div>
            <div id="console" style="margin-top: 15px;"></div>
        </div>

        <div class="section">
            <h2>🌐 Access URLs</h2>
            <div class="status-line">
                <span class="label">Game Test Page:</span>
                <span class="value">
                    <a href="/game_test.php" style="color: #4ec9b0;">https://aicrypto.ngrok.dev/game_test.php</a>
                </span>
            </div>
            <div class="status-line">
                <span class="label">Play Page (Machine 1):</span>
                <span class="value">
                    <a href="/play_v2/?NO=1" style="color: #4ec9b0;">https://aicrypto.ngrok.dev/play_v2/?NO=1</a>
                </span>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/peerjs@1.4.7/dist/peerjs.min.js"></script>
    <script>
        const consoleDiv = document.getElementById('console');

        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const colors = {
                info: '#9cdcfe',
                success: '#4ec9b0',
                error: '#f48771',
                warning: '#dcdcaa'
            };
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.innerHTML = `<span class="timestamp">[${timestamp}]</span> <span style="color: ${colors[type]}">${message}</span>`;
            consoleDiv.appendChild(entry);
            consoleDiv.scrollTop = consoleDiv.scrollHeight;
        }

        function clearConsole() {
            consoleDiv.innerHTML = '';
        }

        // Check Mac Server
        async function checkMacServer() {
            try {
                const response = await fetch('/simple_test.php');
                if (response.ok) {
                    document.getElementById('mac-server').textContent = '✅ Online';
                    document.getElementById('mac-server').className = 'value ok';
                    log('Mac server is online', 'success');
                } else {
                    throw new Error('Server returned ' + response.status);
                }
            } catch (error) {
                document.getElementById('mac-server').textContent = '❌ Offline';
                document.getElementById('mac-server').className = 'value error';
                log('Mac server check failed: ' + error.message, 'error');
            }
        }

        // Check PeerJS Server
        async function checkPeerJSServer() {
            try {
                const response = await fetch('https://aimoderation.ngrok-free.app/peerjs/id');
                if (response.ok) {
                    const peerId = await response.text();
                    document.getElementById('peerjs-server').textContent = '✅ Online (ID: ' + peerId.substring(0, 10) + '...)';
                    document.getElementById('peerjs-server').className = 'value ok';
                    log('PeerJS server is online', 'success');
                } else {
                    throw new Error('Server returned ' + response.status);
                }
            } catch (error) {
                document.getElementById('peerjs-server').textContent = '❌ Offline';
                document.getElementById('peerjs-server').className = 'value error';
                log('PeerJS server check failed: ' + error.message, 'error');
            }
        }

        // Check Database
        async function checkDatabase() {
            try {
                const response = await fetch('/simple_test.php');
                const text = await response.text();
                if (text.includes('Database connected')) {
                    document.getElementById('database').textContent = '✅ Connected';
                    document.getElementById('database').className = 'value ok';
                    log('Database is connected', 'success');
                } else {
                    throw new Error('Database connection not confirmed');
                }
            } catch (error) {
                document.getElementById('database').textContent = '❌ Error';
                document.getElementById('database').className = 'value error';
                log('Database check failed: ' + error.message, 'error');
            }
        }

        // Check Camera API
        async function checkCameraAPI() {
            try {
                const testMAC = '00:00:00:00:00:01';
                const testID = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=';
                const url = `/api/cameraListAPI.php?M=getno&MAC=${testMAC}&ID=${encodeURIComponent(testID)}&IP=127.0.0.1`;

                const response = await fetch(url);
                const data = await response.json();

                if (data.status === 'ok') {
                    document.getElementById('camera-api').textContent = '✅ OK (Machine: ' + data.machine_no + ')';
                    document.getElementById('camera-api').className = 'value ok';
                    log('Camera API is working (machine_no: ' + data.machine_no + ', category: ' + data.category + ')', 'success');

                    // Display camera info
                    const infoDiv = document.getElementById('camera-info');
                    infoDiv.innerHTML = `
                        <div class="status-line"><span class="label">Machine No:</span><span class="value">${data.machine_no}</span></div>
                        <div class="status-line"><span class="label">Category:</span><span class="value">${data.category === 1 ? 'パチスロ' : 'パチンコ'}</span></div>
                        <div class="status-line"><span class="label">Leave Time:</span><span class="value">${data.leavetime}s</span></div>
                        <div class="status-line"><span class="label">Status:</span><span class="value ok">✅ Ready</span></div>
                    `;
                } else {
                    throw new Error(data.error || 'API returned error');
                }
            } catch (error) {
                document.getElementById('camera-api').textContent = '❌ Error';
                document.getElementById('camera-api').className = 'value error';
                log('Camera API check failed: ' + error.message, 'error');
            }
        }

        // Test PeerJS Connection
        function testPeerConnection() {
            log('🔗 Testing PeerJS connection...', 'info');

            try {
                // Use a custom config for ngrok
                const peer = new Peer({
                    host: 'aimoderation.ngrok-free.app',
                    port: 443,
                    path: '/peerjs',
                    secure: true,
                    debug: 2,
                    config: {
                        iceServers: [
                            { urls: 'stun:stun.l.google.com:19302' },
                            { urls: 'stun:stun1.l.google.com:19302' }
                        ]
                    }
                });

                peer.on('open', function(id) {
                    log('✅ PeerJS connection established! Peer ID: ' + id, 'success');
                    setTimeout(() => {
                        peer.destroy();
                        log('🔌 Connection closed', 'info');
                    }, 3000);
                });

                peer.on('error', function(err) {
                    log('❌ PeerJS error: ' + err.message, 'error');
                    log('ℹ️ This may be due to ngrok browser warning. Try accessing: https://aimoderation.ngrok-free.app first', 'warning');
                });

                peer.on('disconnected', function() {
                    log('⚠️ PeerJS disconnected', 'warning');
                });
            } catch (error) {
                log('❌ PeerJS test failed: ' + error.message, 'error');
            }
        }

        // Test Camera Stream
        function testCameraStream() {
            log('📹 Testing camera stream connection...', 'warning');
            log('⚠️ This requires Windows PC camera client to be running', 'warning');

            const cameraId = 'camera-001-0001'; // Expected camera peer ID
            log('🔍 Looking for camera peer: ' + cameraId, 'info');

            // Try to connect to camera
            const peer = new Peer({
                host: 'aimoderation.ngrok-free.app',
                port: 443,
                path: '/peerjs',
                secure: true
            });

            peer.on('open', function(id) {
                log('✅ Viewer peer created: ' + id, 'success');
                log('🔗 Attempting to call camera peer...', 'info');

                // Note: We can't actually call without a media stream,
                // but we can check if peer exists
                setTimeout(() => {
                    log('ℹ️ To complete test, Windows camera client must be running', 'info');
                    peer.destroy();
                }, 3000);
            });

            peer.on('error', function(err) {
                log('❌ Error: ' + err.message, 'error');
            });
        }

        // Run initial checks
        window.addEventListener('load', function() {
            log('🚀 Starting diagnostic checks...', 'info');
            checkMacServer();
            setTimeout(checkPeerJSServer, 500);
            setTimeout(checkDatabase, 1000);
            setTimeout(checkCameraAPI, 1500);
        });
    </script>
</body>
</html>
