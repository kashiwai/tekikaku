/**
 * Stream Forwarder - WebRTC to MediaMTX (WHIP)
 *
 * カメラ映像をMediaMTXサーバーに転送し、LL-HLS配信を可能にする
 *
 * @version 1.0
 * @author Claude Code
 */

class StreamForwarder {
    constructor(options = {}) {
        // MediaMTXサーバーURL
        this.mediaServerUrl = options.mediaServerUrl || 'https://mediamtx-server.railway.app';

        // 接続状態
        this.peerConnection = null;
        this.isForwarding = false;
        this.machineId = null;

        // コールバック
        this.onStateChange = options.onStateChange || (() => {});
        this.onError = options.onError || console.error;

        // 設定
        this.iceServers = [
            { urls: 'stun:stun.l.google.com:19302' }
        ];
    }

    /**
     * MediaMTXへの転送を開始
     * @param {MediaStream} stream - 転送する映像ストリーム
     * @param {string|number} machineId - 台番号
     */
    async startForwarding(stream, machineId) {
        if (this.isForwarding) {
            console.warn('[StreamForwarder] Already forwarding');
            return;
        }

        this.machineId = machineId;
        console.log(`[StreamForwarder] Starting forward to machine/${machineId}`);

        try {
            // PeerConnectionを作成
            this.peerConnection = new RTCPeerConnection({
                iceServers: this.iceServers
            });

            // ストリームのトラックを追加
            stream.getTracks().forEach(track => {
                this.peerConnection.addTrack(track, stream);
            });

            // ICE candidateの収集完了を待つ
            await this._waitForIceGathering();

            // WHIPでMediaMTXに接続
            const response = await this._sendWhipRequest();

            if (response.ok) {
                this.isForwarding = true;
                this.onStateChange('forwarding');
                console.log('[StreamForwarder] Forwarding started successfully');
            } else {
                throw new Error(`WHIP request failed: ${response.status}`);
            }

        } catch (error) {
            this.onError(error);
            this.stopForwarding();
        }
    }

    /**
     * 転送を停止
     */
    stopForwarding() {
        if (this.peerConnection) {
            this.peerConnection.close();
            this.peerConnection = null;
        }
        this.isForwarding = false;
        this.onStateChange('stopped');
        console.log('[StreamForwarder] Forwarding stopped');
    }

    /**
     * ICE candidateの収集を待つ
     */
    async _waitForIceGathering() {
        return new Promise((resolve) => {
            if (this.peerConnection.iceGatheringState === 'complete') {
                resolve();
                return;
            }

            const checkState = () => {
                if (this.peerConnection.iceGatheringState === 'complete') {
                    this.peerConnection.removeEventListener('icegatheringstatechange', checkState);
                    resolve();
                }
            };

            this.peerConnection.addEventListener('icegatheringstatechange', checkState);

            // タイムアウト（5秒）
            setTimeout(() => {
                this.peerConnection.removeEventListener('icegatheringstatechange', checkState);
                resolve();
            }, 5000);
        });
    }

    /**
     * WHIPリクエストを送信
     */
    async _sendWhipRequest() {
        // Offerを作成
        const offer = await this.peerConnection.createOffer();
        await this.peerConnection.setLocalDescription(offer);

        // WHIPエンドポイントに送信
        const whipUrl = `${this.mediaServerUrl}/machine/${this.machineId}/whip`;

        const response = await fetch(whipUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/sdp'
            },
            body: this.peerConnection.localDescription.sdp
        });

        if (response.ok) {
            // Answerを設定
            const answerSdp = await response.text();
            await this.peerConnection.setRemoteDescription({
                type: 'answer',
                sdp: answerSdp
            });
        }

        return response;
    }

    /**
     * 現在の転送状態を取得
     */
    getStatus() {
        return {
            isForwarding: this.isForwarding,
            machineId: this.machineId,
            connectionState: this.peerConnection?.connectionState || 'disconnected'
        };
    }
}

/**
 * 観戦モードプレイヤー - LL-HLS/WebRTC受信
 */
class SpectatorPlayer {
    constructor(options = {}) {
        this.mediaServerUrl = options.mediaServerUrl || 'https://mediamtx-server.railway.app';
        this.videoElement = options.videoElement || document.createElement('video');
        this.machineId = null;
        this.hls = null;
        this.mode = 'llhls'; // 'llhls' or 'webrtc'
    }

    /**
     * LL-HLSで視聴開始
     * @param {string|number} machineId - 台番号
     */
    async playLLHLS(machineId) {
        this.machineId = machineId;
        this.mode = 'llhls';

        const hlsUrl = `${this.mediaServerUrl}/machine/${machineId}/index.m3u8`;

        // hls.jsがあれば使用
        if (typeof Hls !== 'undefined' && Hls.isSupported()) {
            if (this.hls) {
                this.hls.destroy();
            }

            this.hls = new Hls({
                lowLatencyMode: true,
                liveSyncDuration: 1,
                liveMaxLatencyDuration: 3,
                liveDurationInfinity: true,
                highBufferWatchdogPeriod: 1
            });

            this.hls.loadSource(hlsUrl);
            this.hls.attachMedia(this.videoElement);

            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                this.videoElement.play().catch(e => console.warn('Autoplay blocked:', e));
            });

            this.hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('[SpectatorPlayer] HLS Error:', data);
            });

        } else if (this.videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            // Safari native HLS
            this.videoElement.src = hlsUrl;
            this.videoElement.play().catch(e => console.warn('Autoplay blocked:', e));
        } else {
            console.error('[SpectatorPlayer] HLS not supported');
        }
    }

    /**
     * WebRTC (WHEP)で視聴開始
     * @param {string|number} machineId - 台番号
     */
    async playWebRTC(machineId) {
        this.machineId = machineId;
        this.mode = 'webrtc';

        const pc = new RTCPeerConnection({
            iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
        });

        pc.addTransceiver('video', { direction: 'recvonly' });
        pc.addTransceiver('audio', { direction: 'recvonly' });

        pc.ontrack = (event) => {
            this.videoElement.srcObject = event.streams[0];
            this.videoElement.play().catch(e => console.warn('Autoplay blocked:', e));
        };

        const offer = await pc.createOffer();
        await pc.setLocalDescription(offer);

        // WHEP接続
        const whepUrl = `${this.mediaServerUrl}/machine/${machineId}/whep`;
        const response = await fetch(whepUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/sdp' },
            body: pc.localDescription.sdp
        });

        if (response.ok) {
            const answerSdp = await response.text();
            await pc.setRemoteDescription({ type: 'answer', sdp: answerSdp });
        }
    }

    /**
     * 視聴停止
     */
    stop() {
        if (this.hls) {
            this.hls.destroy();
            this.hls = null;
        }
        this.videoElement.srcObject = null;
        this.videoElement.src = '';
    }

    /**
     * 視聴URLを取得（外部埋め込み用）
     */
    getEmbedUrl(machineId) {
        return `${this.mediaServerUrl}/machine/${machineId}/index.m3u8`;
    }
}

// グローバルに公開
window.StreamForwarder = StreamForwarder;
window.SpectatorPlayer = SpectatorPlayer;

console.log('[StreamForwarder] Loaded - LL-HLS streaming support enabled');
