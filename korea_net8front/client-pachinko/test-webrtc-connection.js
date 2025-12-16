#!/usr/bin/env node

/**
 * NET8 WebRTC接続テストスクリプト
 * 実機のカメラストリーミングが受信できるか確認します
 */

const Peer = require('peerjs').Peer;

// 設定
const SIGNALING_HOST = 'dockerfilesignaling-production.up.railway.app';
const SIGNALING_PORT = 443;
const SIGNALING_PATH = '/myapp';

console.log('=== NET8 WebRTC 接続テスト ===');
console.log(`シグナリングサーバー: ${SIGNALING_HOST}`);
console.log(`ポート: ${SIGNALING_PORT}`);
console.log(`パス: ${SIGNALING_PATH}`);
console.log('');

// テスト用PeerIDを生成
const testPeerId = `test_client_${Date.now()}`;
console.log(`クライアントPeer ID: ${testPeerId}`);

// PeerJSクライアント作成
const peer = new Peer(testPeerId, {
  host: SIGNALING_HOST,
  port: SIGNALING_PORT,
  path: SIGNALING_PATH,
  secure: true,
  config: {
    iceServers: [
      { urls: 'stun:stun.l.google.com:19302' },
      { urls: 'stun:stun1.l.google.com:19302' }
    ]
  },
  debug: 3 // デバッグレベルを最大に
});

// 接続イベント
peer.on('open', (id) => {
  console.log('✅ シグナリングサーバーに接続成功');
  console.log(`接続ID: ${id}`);
  console.log('');
  
  // 既知のマシンカメラIDに接続試行
  const machineIds = [
    'machine_1_camera',
    'machine_2_camera',
    'net8_camera_001',
    'demo_camera'
  ];
  
  console.log('実機カメラの検出を試みています...');
  machineIds.forEach((machineId) => {
    console.log(`- ${machineId} への接続を試行中...`);
    
    // カメラに接続要求
    const call = peer.call(machineId, undefined);
    
    if (call) {
      call.on('stream', (remoteStream) => {
        console.log(`✅ ${machineId} からストリーム受信！`);
        console.log('  ビデオトラック数:', remoteStream.getVideoTracks().length);
        console.log('  オーディオトラック数:', remoteStream.getAudioTracks().length);
      });
      
      call.on('error', (err) => {
        console.log(`❌ ${machineId} 接続エラー:`, err.message);
      });
      
      call.on('close', () => {
        console.log(`  ${machineId} 接続終了`);
      });
    }
  });
});

// エラーハンドリング
peer.on('error', (err) => {
  console.error('❌ PeerJSエラー:', err.type, err.message);
  
  if (err.type === 'network') {
    console.log('ネットワーク接続を確認してください');
  } else if (err.type === 'peer-unavailable') {
    console.log('指定されたPeerが見つかりません');
  } else if (err.type === 'server-error') {
    console.log('シグナリングサーバーエラー');
  }
});

peer.on('disconnected', () => {
  console.log('⚠️ シグナリングサーバーから切断されました');
  console.log('再接続を試みています...');
  peer.reconnect();
});

peer.on('close', () => {
  console.log('接続がクローズされました');
});

// 着信処理（実機側からの接続がある場合）
peer.on('call', (call) => {
  console.log(`📞 着信: ${call.peer} から接続要求`);
  
  // 自動応答
  call.answer();
  
  call.on('stream', (remoteStream) => {
    console.log('✅ リモートストリーム受信成功！');
    console.log('  送信元:', call.peer);
    console.log('  ビデオトラック:', remoteStream.getVideoTracks().length);
    console.log('  オーディオトラック:', remoteStream.getAudioTracks().length);
  });
});

// プロセス終了時のクリーンアップ
process.on('SIGINT', () => {
  console.log('\n終了処理中...');
  peer.destroy();
  process.exit(0);
});

// タイムアウト設定（30秒）
setTimeout(() => {
  console.log('\nタイムアウト: 30秒経過');
  console.log('実機カメラが検出できませんでした');
  console.log('実機側の設定を確認してください');
  peer.destroy();
  process.exit(0);
}, 30000);

console.log('\n接続待機中... (Ctrl+C で終了)\n');