// app/api/game/start/route.ts
import { NextRequest, NextResponse } from 'next/server';
import { getSession } from '@/lib/getSession';

const NET8_API_BASE = process.env.NET8_API_BASE_URL || 'https://mgg-webservice-production.up.railway.app';
const NET8_API_KEY = process.env.NET8_API_KEY || 'pk_demo_12345';
const MOCK_MODE = process.env.NET8_MOCK_MODE === 'true';
const CAMERA_PEER_ID = process.env.NET8_CAMERA_PEER_ID || 'camera_10000021_1765859502';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { userId, modelId } = body;

    if (!userId || !modelId) {
      return NextResponse.json(
        { error: 'Missing required parameters' },
        { status: 400 }
      );
    }

    // 韓国セッション認証チェック（テストモードでは認証をスキップ）
    const session = await getSession();
    const isTestMode = MOCK_MODE || modelId === 'HOKUTO4GO'; // テスト用モデルIDの場合もテストモードとする
    
    if (!isTestMode && !session.user) {
      return NextResponse.json(
        { error: 'Please login first' },
        { status: 401 }
      );
    }

    console.log(`Starting NET8 game for user: ${userId}, model: ${modelId}`);

    // モックモードの場合は実機に接続せずモックデータを返す
    if (MOCK_MODE) {
      const mockGameSession = {
        sessionId: `mock_session_${Date.now()}_${Math.random().toString(36).substring(2, 11)}`,
        userId: userId,
        modelId: modelId,
        startTime: new Date().toISOString(),
        machineNo: 1,
        webRTC: {
          signalingUrl: 'wss://mgg-signaling-production-c1bd.up.railway.app',
          peerId: `player_${userId}_${Date.now()}`,
          remotePeerId: CAMERA_PEER_ID, // 実際のカメラIDを使用
          stunServers: [
            { urls: 'stun:stun.l.google.com:19302' }
          ]
        },
        gameState: {
          credits: session.user?.wallets?.money || 50000,
          betAmount: 100,
          maxBet: 1000,
          gameRounds: 0
        },
        mock: true,
        success: true
      };
      return NextResponse.json(mockGameSession);
    }
    
    // 実際のNET8 APIを呼び出す
    try {
      // 本番環境では台番号01（稼働中のカメラがある台）を直接指定
      const machineNo = process.env.NET8_MACHINE_NO || '1';

      // 韓国側のポイント残高を取得
      const koreaPoints = session.user?.wallets?.money || 0;
      console.log(`[NET8] Korea user points: ${koreaPoints}`);

      // ★ ポイント不足チェック（API側の二重チェック）
      const minimumPoints = 100; // 1ゲーム最低ポイント
      if (koreaPoints < minimumPoints) {
        console.error(`[NET8] Insufficient points: ${koreaPoints} < ${minimumPoints}`);
        return NextResponse.json({
          error: 'INSUFFICIENT_POINTS',
          message: 'ポイントが不足しています。チャージしてください。',
          required: minimumPoints,
          current: koreaPoints
        }, { status: 400 });
      }

      console.log(`[NET8] Calling game_start API for user: ${userId}, model: ${modelId}, machineNo: ${machineNo}, koreaPoints: ${koreaPoints}`);
      const net8Response = await fetch(`${NET8_API_BASE}/api/v1/game_start.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${NET8_API_KEY}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          userId: userId,
          modelId: modelId,
          machineNo: machineNo, // 直接台番号を指定（稼働中の台を使用）
          pointsToConsume: 100, // 1ゲーム100ポイント消費
          initialPoints: koreaPoints, // 韓国側のポイントをNet8にデポジット
          balanceMode: 'set', // ★ Case 1対応: 既存残高を無視して設定
          consumeImmediately: false // ★ Case 6対応: iframe表示時点ではポイント消費しない
        }),
      });

      if (!net8Response.ok) {
        const errorData = await net8Response.json().catch(() => null);
        console.error('NET8 API error:', errorData);
        throw new Error('NET8 API call failed');
      }

      const net8Data = await net8Response.json();
      
      // NET8からのレスポンスを使用（signaling と camera が既に含まれている）
      console.log('[NET8] API Response:', JSON.stringify(net8Data, null, 2));
      console.log('[NET8] Using camera peer ID:', net8Data.camera?.peerId || CAMERA_PEER_ID);

      const gameSession = {
        ...net8Data,
        // 本番モードを明示的に設定（mockフラグを上書き）
        mock: false,
        // NET8レスポンスのsignalingとcameraを活用してWebRTC設定を構築
        // カメラPeerIDは環境変数を優先（APIからのcamera.peerIdがない場合）
        webRTC: net8Data.signaling ? {
          signalingUrl: `wss://${net8Data.signaling.host}:${net8Data.signaling.port}`,
          peerId: `player_${userId}_${Date.now()}`,
          remotePeerId: net8Data.camera?.peerId || CAMERA_PEER_ID,
          sessionId: net8Data.sessionId,
          stunServers: [
            { urls: 'stun:stun.l.google.com:19302' },
            { urls: 'stun:stun.l.google.com:5349' }
          ],
          secure: net8Data.signaling.secure,
          mock: false
        } : {
          // フォールバック設定（本番カメラIDを使用）
          signalingUrl: 'wss://mgg-signaling-production-c1bd.up.railway.app',
          peerId: `player_${userId}_${Date.now()}`,
          remotePeerId: CAMERA_PEER_ID,
          stunServers: [
            { urls: 'stun:stun.l.google.com:19302' }
          ],
          secure: true,
          mock: false
        },
        gameState: {
          credits: net8Data.newBalance || (session.user?.wallets?.money || 50000) - 100,
          betAmount: 100,
          maxBet: 1000,
          gameRounds: 0
        },
        success: true
      };

      return NextResponse.json(gameSession);
      
    } catch (apiError) {
      console.error('NET8 API connection failed:', apiError);
      
      // APIエラーの場合はフォールバックモードで動作
      const fallbackSession = {
        sessionId: `fallback_session_${Date.now()}`,
        userId: userId,
        modelId: modelId,
        startTime: new Date().toISOString(),
        error: 'NET8 API temporarily unavailable, running in fallback mode',
        webRTC: {
          signalingUrl: 'wss://mgg-signaling-production-c1bd.up.railway.app',
          peerId: `fallback_peer_${userId}_${Date.now()}`,
          remotePeerId: 'demo_camera',
          stunServers: [
            { urls: 'stun:stun.l.google.com:19302' }
          ]
        },
        gameState: {
          credits: session.user?.wallets?.money || 50000,
          betAmount: 100,
          maxBet: 1000,
          gameRounds: 0
        },
        fallback: true,
        success: true
      };
      
      return NextResponse.json(fallbackSession);
    }
    
  } catch (error) {
    console.error('Game start API error:', error);
    return NextResponse.json(
      { error: 'Failed to start game' },
      { status: 500 }
    );
  }
}