// hooks/useWebRTC.ts
'use client';

import { useEffect, useRef, useState } from 'react';
import Peer, { MediaConnection } from 'peerjs';

export function useWebRTC(signalingInfo: any) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const peerConnection = useRef<RTCPeerConnection | null>(null);
  const peerRef = useRef<Peer | null>(null);
  const initializingRef = useRef<boolean>(false); // 初期化中フラグ
  const lastPeerIdRef = useRef<string | null>(null); // 前回のpeerIdを記録
  const connectionTimeoutRef = useRef<NodeJS.Timeout | null>(null); // 接続タイムアウト
  const isConnectedRef = useRef<boolean>(false); // 接続完了フラグ（クロージャ対策）
  const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected' | 'error'>('disconnected');

  useEffect(() => {
    if (!signalingInfo) return;

    // 既存の接続があれば先にクリーンアップ
    if (peerRef.current) {
      console.log('🧹 Cleaning up existing peer connection');
      peerRef.current.destroy();
      peerRef.current = null;
    }
    if (peerConnection.current) {
      peerConnection.current.close();
      peerConnection.current = null;
    }

    // 少し遅延を入れてから接続（前の接続のクリーンアップを待つ）
    const timer = setTimeout(() => {
      // signalingInfo形式を確認してPeerJSまたはネイティブWebRTCを選択
      if (signalingInfo.signalingUrl?.includes('mgg-signaling') || signalingInfo.signalingUrl?.includes('dockerfilesignaling') || signalingInfo.peerId) {
        initializePeerJS();
      } else if (signalingInfo.signaling) {
        // NET8 APIから直接返されたsignaling情報を使用
        initializePeerJSWithNet8Config();
      } else {
        initializeWebRTC();
      }
    }, 100);

    return () => {
      clearTimeout(timer);
      // 接続タイムアウトをクリア
      if (connectionTimeoutRef.current) {
        clearTimeout(connectionTimeoutRef.current);
        connectionTimeoutRef.current = null;
      }
      if (peerRef.current) {
        console.log('🧹 Destroying peer on cleanup');
        peerRef.current.destroy();
        peerRef.current = null;
      }
      if (peerConnection.current) {
        peerConnection.current.close();
        peerConnection.current = null;
      }
    };
  }, [signalingInfo?.peerId]); // peerIdのみを依存に（オブジェクト全体だと無限ループの可能性）

  const initializeWebRTC = async () => {
    try {
      // Create RTCPeerConnection with ICE servers from signaling info
      const configuration = {
        iceServers: signalingInfo.iceServers || [{ urls: 'stun:stun.l.google.com:19302' }]
      };

      peerConnection.current = new RTCPeerConnection(configuration);

      // Handle incoming tracks
      peerConnection.current.ontrack = (event) => {
        if (videoRef.current) {
          videoRef.current.srcObject = event.streams[0];
        }
      };

      // Handle ICE connection state changes
      peerConnection.current.oniceconnectionstatechange = () => {
        console.log('ICE connection state:', peerConnection.current?.iceConnectionState);
      };

      // You would typically create an offer and exchange SDP with signaling server
      await createOffer();

    } catch (error) {
      console.error('WebRTC initialization failed:', error);
    }
  };

  const createOffer = async () => {
    if (!peerConnection.current) return;

    try {
      const offer = await peerConnection.current.createOffer();
      await peerConnection.current.setLocalDescription(offer);

      // Send offer to signaling server
      // This would be specific to your signaling implementation
      await sendSignalingMessage({
        type: 'offer',
        sdp: offer.sdp
      });

    } catch (error) {
      console.error('Create offer failed:', error);
    }
  };

  const initializePeerJS = async () => {
    try {
      setConnectionStatus('connecting');
      console.log('Initializing PeerJS connection...');
      console.log('Signaling info:', signalingInfo);

      // 環境変数からPeerJS設定を読み込み
      // ICEサーバー設定（STUN + TURN）
      const iceServers = [
        // Google STUN servers
        { urls: 'stun:stun.l.google.com:19302' },
        { urls: 'stun:stun1.l.google.com:19302' },
        { urls: 'stun:stun2.l.google.com:19302' },
        { urls: 'stun:stun3.l.google.com:19302' },
        { urls: 'stun:stun4.l.google.com:19302' },
        // OpenRelay TURN servers (free, for development)
        {
          urls: 'turn:openrelay.metered.ca:80',
          username: 'openrelayproject',
          credential: 'openrelayproject'
        },
        {
          urls: 'turn:openrelay.metered.ca:443',
          username: 'openrelayproject',
          credential: 'openrelayproject'
        },
        {
          urls: 'turn:openrelay.metered.ca:443?transport=tcp',
          username: 'openrelayproject',
          credential: 'openrelayproject'
        },
        // 環境変数からカスタムTURNサーバー（設定されている場合）
        ...(process.env.NEXT_PUBLIC_TURN_URL ? [{
          urls: process.env.NEXT_PUBLIC_TURN_URL,
          username: process.env.NEXT_PUBLIC_TURN_USERNAME || '',
          credential: process.env.NEXT_PUBLIC_TURN_CREDENTIAL || ''
        }] : [])
      ];

      console.log('🧊 ICE servers configured:', iceServers.length);

      const peerConfig = {
        host: process.env.NEXT_PUBLIC_PEERJS_HOST || 'mgg-signaling-production-c1bd.up.railway.app',
        port: parseInt(process.env.NEXT_PUBLIC_PEERJS_PORT || '443'),
        path: process.env.NEXT_PUBLIC_PEERJS_PATH || '/',
        secure: process.env.NEXT_PUBLIC_PEERJS_SECURE !== 'false',
        config: {
          // APIから返されたiceServersを優先、なければデフォルト
          iceServers: signalingInfo.iceServers || signalingInfo.stunServers || iceServers,
          iceCandidatePoolSize: 10
        }
      };

      console.log('PeerJS config:', peerConfig);

      // PeerJSクライアント初期化
      const peer = new Peer(signalingInfo.peerId || undefined, peerConfig);

      peerRef.current = peer;
      
      // モックモードの場合は即座にモック映像を開始
      if (signalingInfo.mock) {
        console.log('Mock mode detected, creating mock video stream');
        createMockVideoStream();
        setConnectionStatus('connected');
        return; // モックモードなら実際の接続は不要
      }

      // 接続タイムアウト: 10秒以内に映像が来なければモックにフォールバック
      isConnectedRef.current = false;
      connectionTimeoutRef.current = setTimeout(() => {
        if (!isConnectedRef.current) {
          console.log('⏰ Connection timeout (10s) - falling back to mock video');
          console.log('💡 TIP: カメラ側にもTURNサーバー設定が必要な場合があります');
          createMockVideoStream();
          setConnectionStatus('connected');
          isConnectedRef.current = true;
        }
      }, 10000);

      peer.on('open', (id) => {
        console.log('✅ PeerJS connected with ID:', id);
        console.log('📡 Signaling server connected successfully');
        // シグナリング接続完了、カメラ映像待機中（まだconnectedではない）
        console.log('⏳ Waiting for camera stream... (10s timeout)');

        // 実機カメラに接続
        if (signalingInfo.remotePeerId) {
          console.log('📞 Calling remote camera peer:', signalingInfo.remotePeerId);

          // カメラからのストリームを受信するために、まず接続を確立
          // カメラ側がcallしてくるのを待つ方式の場合もある
          console.log('⏳ Waiting for camera stream...');

          // 方式1: こちらからcallする（ダミーストリームなし）
          try {
            const call = peer.call(signalingInfo.remotePeerId, new MediaStream());
            if (call) {
              console.log('📞 Call initiated to camera');
              handleIncomingCall(call);
            } else {
              console.log('⚠️ Call returned null - waiting for incoming call from camera');
            }
          } catch (callError) {
            console.log('⚠️ Could not initiate call, waiting for camera to call us:', callError);
          }
        }
      });

      // 実機カメラからの着信を処理
      peer.on('call', (call: MediaConnection) => {
        console.log('📥 Incoming call from camera:', call.peer);
        handleIncomingCall(call);
      });

      // データ接続のリスニング
      peer.on('connection', (conn) => {
        console.log('📡 Data connection from:', conn.peer);
        conn.on('data', (data) => {
          console.log('📩 Data received:', data);
        });
      });

      peer.on('error', (err) => {
        console.error('PeerJS error:', err);
        setConnectionStatus('error');
        
        // エラー時はフォールバック映像を表示
        // 開発環境またはモックモードの場合
        if (process.env.NODE_ENV === 'development' || signalingInfo.mock) {
          console.log('Falling back to mock video stream');
          createMockVideoStream();
        }
      });

      peer.on('disconnected', () => {
        console.log('PeerJS disconnected');
        setConnectionStatus('disconnected');
      });

    } catch (error) {
      console.error('PeerJS initialization failed:', error);
      setConnectionStatus('error');
      
      // 初期化失敗時はモック映像を表示
      if (process.env.NODE_ENV === 'development') {
        createMockVideoStream();
      }
    }
  };

  const handleIncomingCall = (call: MediaConnection) => {
    console.log('🎥 Handling call from:', call.peer);
    console.log('📋 Call metadata:', call.metadata);
    console.log('📋 Call type:', call.type);
    console.log('📋 Call open:', call.open);

    // 自動的に応答（プレイヤー側はカメラなし）
    call.answer();
    console.log('✅ Call answered, waiting for stream...');

    // ICE接続状態の変化を監視
    if (call.peerConnection) {
      console.log('🔗 PeerConnection exists, monitoring ICE state...');

      // ICE候補の収集を監視
      call.peerConnection.onicecandidate = (event) => {
        if (event.candidate) {
          console.log('🧊 ICE candidate:', event.candidate.type, event.candidate.address);
        } else {
          console.log('🧊 ICE candidate gathering complete');
        }
      };

      call.peerConnection.onicegatheringstatechange = () => {
        console.log('🧊 ICE gathering state:', call.peerConnection?.iceGatheringState);
      };

      call.peerConnection.oniceconnectionstatechange = () => {
        const state = call.peerConnection?.iceConnectionState;
        console.log('🧊 ICE connection state:', state);

        // ICE接続が失敗した場合
        if (state === 'failed') {
          console.error('❌ ICE connection failed - NAT traversal issue');
          setConnectionStatus('error');
        } else if (state === 'connected' || state === 'completed') {
          console.log('✅ ICE connection established!');
        }
      };

      call.peerConnection.onconnectionstatechange = () => {
        console.log('🔌 Connection state:', call.peerConnection?.connectionState);
      };

      call.peerConnection.ontrack = (event) => {
        console.log('🎵 ontrack event:', event.streams);
        if (event.streams[0] && videoRef.current) {
          console.log('🎬 Setting video from ontrack event');
          videoRef.current.srcObject = event.streams[0];
          videoRef.current.play().catch(e => console.log('Video play error:', e));
          setConnectionStatus('connected');
        }
      };
    }

    // リモートストリーム（実機カメラ映像）を受信
    call.on('stream', (remoteStream: MediaStream) => {
      console.log('🎬 Received remote stream from camera!');
      console.log('Stream tracks:', remoteStream.getTracks());
      console.log('Stream active:', remoteStream.active);

      // タイムアウトをクリア（接続成功）
      if (connectionTimeoutRef.current) {
        clearTimeout(connectionTimeoutRef.current);
        connectionTimeoutRef.current = null;
        console.log('⏰ Connection timeout cleared - real stream received');
      }
      isConnectedRef.current = true;

      if (videoRef.current) {
        videoRef.current.srcObject = remoteStream;
        videoRef.current.play().catch(e => console.log('Video play error:', e));
        setConnectionStatus('connected');
        console.log('✅ Video stream connected to player');
      }
    });

    call.on('close', () => {
      console.log('📴 Call closed');
      setConnectionStatus('disconnected');
    });

    call.on('error', (err) => {
      console.error('❌ Call error:', err);
      setConnectionStatus('error');
    });
  };

  const sendSignalingMessage = async (message: any) => {
    // PeerJSを使用している場合はこの関数は不要
    console.log('Signaling message (not used with PeerJS):', message);
  };

  const initializePeerJSWithNet8Config = async () => {
    try {
      setConnectionStatus('connecting');
      console.log('Initializing PeerJS with NET8 config...');
      console.log('NET8 Signaling info:', signalingInfo.signaling);

      const peerConfig = {
        host: signalingInfo.signaling.host,
        port: signalingInfo.signaling.port,
        path: '/',
        secure: signalingInfo.signaling.secure,
        config: {
          iceServers: signalingInfo.stunServers || [
            { urls: 'stun:stun.l.google.com:19302' }
          ]
        }
      };

      console.log('NET8 PeerJS config:', peerConfig);

      // PeerJSクライアント初期化（NET8のsignalingIdを使用）
      const peer = new Peer(signalingInfo.signaling.signalingId || undefined, peerConfig);
      peerRef.current = peer;

      peer.on('open', (id) => {
        console.log('PeerJS connected with NET8 ID:', id);
        
        // カメラストリームURLがある場合は使用
        if (signalingInfo.camera?.streamUrl) {
          console.log('Camera stream URL:', signalingInfo.camera.streamUrl);
          // 必要に応じてstreamUrlを使用した接続処理
        }
        
        // カメラPeerIDがある場合は接続
        if (signalingInfo.camera?.peerId) {
          console.log('Calling camera peer:', signalingInfo.camera.peerId);
          const call = peer.call(signalingInfo.camera.peerId, undefined as any);
          if (call) {
            handleIncomingCall(call);
          }
        }
      });

      peer.on('call', (call: MediaConnection) => {
        console.log('Incoming call from NET8 camera:', call.peer);
        handleIncomingCall(call);
      });

      peer.on('error', (err) => {
        console.error('NET8 PeerJS error:', err);
        setConnectionStatus('error');
      });

      peer.on('disconnected', () => {
        console.log('NET8 PeerJS disconnected');
        setConnectionStatus('disconnected');
      });

    } catch (error) {
      console.error('NET8 PeerJS initialization failed:', error);
      setConnectionStatus('error');
    }
  };

  const createMockVideoStream = async () => {
    try {
      console.log('🎬 Creating mock video stream...');
      console.log('📺 videoRef.current:', videoRef.current);

      // Canvas要素でモック映像生成
      const canvas = document.createElement('canvas');
      canvas.width = 1280;
      canvas.height = 720;
      const ctx = canvas.getContext('2d');

      if (!ctx) {
        console.error('❌ Could not get canvas context');
        return;
      }

      if (!videoRef.current) {
        console.error('❌ videoRef.current is null');
        return;
      }

      const stream = canvas.captureStream(30); // 30fps
      console.log('✅ Canvas stream created:', stream);

      videoRef.current.srcObject = stream;
      videoRef.current.play().catch(e => console.log('Mock video play error:', e));
      console.log('✅ Mock stream attached to video element');

      // アニメーション描画
      const animateDemo = () => {
        // パチンコ台のモック描画
        const gradient = ctx.createLinearGradient(0, 0, canvas.width, canvas.height);
        gradient.addColorStop(0, '#1a1a2e');
        gradient.addColorStop(0.5, '#16213e');
        gradient.addColorStop(1, '#0f0f23');
        ctx.fillStyle = gradient;
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // パチンコ玉の描画
        const time = Date.now() / 1000;
        for (let i = 0; i < 10; i++) {
          const x = 200 + Math.sin(time + i) * 100;
          const y = 100 + (time * 50 + i * 50) % canvas.height;

          ctx.beginPath();
          ctx.arc(x, y, 8, 0, 2 * Math.PI);
          ctx.fillStyle = '#C0C0C0'; // silver color
          ctx.fill();
          ctx.strokeStyle = '#FFFFFF'; // white color
          ctx.lineWidth = 2;
          ctx.stroke();
        }
        
        // 機種名表示
        ctx.fillStyle = '#ffff00';
        ctx.font = 'bold 48px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('NET8 デモモード', canvas.width / 2, 100);
        
        // 情報表示
        ctx.fillStyle = '#00ff00';
        ctx.font = '24px Arial';
        ctx.fillText('映像配信中...', canvas.width / 2, canvas.height - 50);
        
        requestAnimationFrame(animateDemo);
      };
      
      animateDemo();
    } catch (error) {
      console.error('Mock video stream creation failed:', error);
    }
  };

  return { videoRef, connectionStatus };
}