// hooks/useWebRTC.ts
'use client';

import { useEffect, useRef, useState } from 'react';
import Peer, { MediaConnection } from 'peerjs';

export function useWebRTC(signalingInfo: any) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const peerConnection = useRef<RTCPeerConnection | null>(null);
  const peerRef = useRef<Peer | null>(null);
  const [connectionStatus, setConnectionStatus] = useState<'connecting' | 'connected' | 'disconnected' | 'error'>('disconnected');

  useEffect(() => {
    if (!signalingInfo) return;

    // signalingInfo形式を確認してPeerJSまたはネイティブWebRTCを選択
    if (signalingInfo.signalingUrl?.includes('mgg-signaling') || signalingInfo.signalingUrl?.includes('dockerfilesignaling') || signalingInfo.peerId) {
      initializePeerJS();
    } else if (signalingInfo.signaling) {
      // NET8 APIから直接返されたsignaling情報を使用
      initializePeerJSWithNet8Config();
    } else {
      initializeWebRTC();
    }

    return () => {
      if (peerRef.current) {
        peerRef.current.destroy();
      }
      if (peerConnection.current) {
        peerConnection.current.close();
      }
    };
  }, [signalingInfo]);

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
      const peerConfig = {
        host: process.env.NEXT_PUBLIC_PEERJS_HOST || 'mgg-signaling-production-c1bd.up.railway.app',
        port: parseInt(process.env.NEXT_PUBLIC_PEERJS_PORT || '443'),
        path: process.env.NEXT_PUBLIC_PEERJS_PATH || '/myapp',
        secure: process.env.NEXT_PUBLIC_PEERJS_SECURE === 'true',
        config: {
          iceServers: signalingInfo.stunServers || [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun1.l.google.com:19302' }
          ]
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

      peer.on('open', (id) => {
        console.log('PeerJS connected with ID:', id);
        
        // 実機カメラに接続
        if (signalingInfo.remotePeerId) {
          console.log('Calling remote peer:', signalingInfo.remotePeerId);
          
          // カメラに接続要求を送信
          const call = peer.call(signalingInfo.remotePeerId, undefined as any);
          
          if (call) {
            handleIncomingCall(call);
          }
        }
      });

      // 実機カメラからの着信を処理
      peer.on('call', (call: MediaConnection) => {
        console.log('Incoming call from:', call.peer);
        handleIncomingCall(call);
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
    // 自動的に応答（プレイヤー側はカメラなし）
    call.answer();
    
    // リモートストリーム（実機カメラ映像）を受信
    call.on('stream', (remoteStream: MediaStream) => {
      console.log('Received remote stream from camera');
      if (videoRef.current) {
        videoRef.current.srcObject = remoteStream;
        setConnectionStatus('connected');
      }
    });

    call.on('close', () => {
      console.log('Call closed');
      setConnectionStatus('disconnected');
    });

    call.on('error', (err) => {
      console.error('Call error:', err);
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
        path: '/myapp',
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
      // Canvas要素でモック映像生成
      const canvas = document.createElement('canvas');
      canvas.width = 1280;
      canvas.height = 720;
      const ctx = canvas.getContext('2d');
      
      if (!ctx) return;
      
      const stream = canvas.captureStream(30); // 30fps
      
      if (videoRef.current) {
        videoRef.current.srcObject = stream;
      }
      
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
          ctx.fillStyle = '#silver';
          ctx.fill();
          ctx.strokeStyle = '#white';
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