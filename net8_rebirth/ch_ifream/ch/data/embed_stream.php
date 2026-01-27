<?php
/**
 * embed_stream.php
 *
 * 外部サイト埋め込み用LL-HLSプレイヤー
 * iframe で埋め込み可能
 *
 * @package NET8
 * @author Claude Code
 * @version 1.0
 */

// CORS設定（埋め込み許可）
header('X-Frame-Options: ALLOWALL');
header('Access-Control-Allow-Origin: *');

require_once('../_etc/require_files.php');

// パラメータ取得
$machineNo = isset($_GET['no']) ? (int)$_GET['no'] : 0;
$autoplay = isset($_GET['autoplay']) ? $_GET['autoplay'] : '1';
$muted = isset($_GET['muted']) ? $_GET['muted'] : '1';
$controls = isset($_GET['controls']) ? $_GET['controls'] : '1';

// MediaMTXサーバーURL
$mediaServerUrl = getenv('MEDIAMTX_URL') ?: 'https://mediamtx-server.railway.app';

// 台情報確認
$machineExists = false;
$modelName = '';

if ($machineNo > 0) {
    try {
        $db = new SmartDB(DB_DSN);
        $sql = "SELECT dm.machine_no, mm.model_name
                FROM dat_machine dm
                LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
                WHERE dm.machine_no = " . (int)$machineNo . " AND dm.del_flg != 1";
        $row = $db->getRow($sql);
        if ($row) {
            $machineExists = true;
            $modelName = $row['model_name'];
        }
    } catch (Exception $e) {
        // エラー時も続行
    }
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($modelName ?: 'ライブ配信'); ?> - NET8</title>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    html, body {
        width: 100%;
        height: 100%;
        background: #000;
        overflow: hidden;
    }

    .player-container {
        position: relative;
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    video {
        width: 100%;
        height: 100%;
        object-fit: contain;
        background: #000;
    }

    .overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        padding: 10px;
        background: linear-gradient(to bottom, rgba(0,0,0,0.7), transparent);
        display: flex;
        justify-content: space-between;
        align-items: center;
        opacity: 0;
        transition: opacity 0.3s;
    }

    .player-container:hover .overlay {
        opacity: 1;
    }

    .live-badge {
        padding: 4px 10px;
        background: #ef4444;
        color: white;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
        font-family: sans-serif;
    }

    .logo {
        color: #ffd700;
        font-size: 14px;
        font-weight: bold;
        font-family: sans-serif;
        text-decoration: none;
    }

    .error-message {
        color: #fff;
        text-align: center;
        font-family: sans-serif;
        padding: 20px;
    }

    .error-message h2 {
        color: #ffd700;
        margin-bottom: 10px;
    }

    .loading {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #fff;
        font-family: sans-serif;
    }

    .spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(255,255,255,0.3);
        border-top-color: #ffd700;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* コントロール非表示時 */
    video::-webkit-media-controls {
        <?php if ($controls !== '1'): ?>display: none !important;<?php endif; ?>
    }
    </style>
</head>
<body>
    <div class="player-container">
        <?php if ($machineNo > 0 && $machineExists): ?>
        <video id="video"
               playsinline
               <?php echo $autoplay === '1' ? 'autoplay' : ''; ?>
               <?php echo $muted === '1' ? 'muted' : ''; ?>
               <?php echo $controls === '1' ? 'controls' : ''; ?>>
        </video>

        <div class="overlay">
            <span class="live-badge">🔴 LIVE</span>
            <a href="https://mgg-webservice-production.up.railway.app/data/spectate.php?NO=<?php echo $machineNo; ?>"
               target="_blank" class="logo">NET8</a>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <div>読み込み中...</div>
        </div>

        <script>
        const mediaServerUrl = '<?php echo $mediaServerUrl; ?>';
        const machineNo = <?php echo $machineNo; ?>;
        const hlsUrl = `${mediaServerUrl}/machine/${machineNo}/index.m3u8`;
        const video = document.getElementById('video');
        const loading = document.getElementById('loading');

        function initPlayer() {
            if (Hls.isSupported()) {
                const hls = new Hls({
                    lowLatencyMode: true,
                    liveSyncDuration: 1,
                    liveMaxLatencyDuration: 3,
                    liveDurationInfinity: true
                });

                hls.loadSource(hlsUrl);
                hls.attachMedia(video);

                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    loading.style.display = 'none';
                    video.play().catch(e => console.warn('Autoplay blocked'));
                });

                hls.on(Hls.Events.ERROR, function(event, data) {
                    if (data.fatal) {
                        console.error('HLS Error:', data);
                        showError('配信に接続できません');
                    }
                });

            } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                // Safari native HLS
                video.src = hlsUrl;
                video.addEventListener('loadedmetadata', function() {
                    loading.style.display = 'none';
                    video.play().catch(e => console.warn('Autoplay blocked'));
                });
            } else {
                showError('お使いのブラウザはHLS再生に対応していません');
            }
        }

        function showError(message) {
            loading.innerHTML = `<div class="error-message"><h2>⚠️</h2><p>${message}</p></div>`;
        }

        // 初期化
        initPlayer();

        // 5秒後にまだローディングなら再試行
        setTimeout(function() {
            if (loading.style.display !== 'none') {
                console.log('Retrying connection...');
                initPlayer();
            }
        }, 5000);
        </script>

        <?php else: ?>
        <div class="error-message">
            <h2>⚠️ エラー</h2>
            <p><?php echo $machineNo > 0 ? '指定された台が見つかりません' : '台番号を指定してください'; ?></p>
            <p style="margin-top: 10px; font-size: 12px; color: #888;">
                使用例: embed_stream.php?no=1
            </p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
