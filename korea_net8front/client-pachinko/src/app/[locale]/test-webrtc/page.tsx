'use client';

import { useState, useEffect } from 'react';
import { Net8GamePlayer } from '@/components/net8/Net8GamePlayer';
import { Button } from '@/components/ui/button';

export default function TestWebRTCPage() {
  const [isGameStarted, setIsGameStarted] = useState(false);
  const [testUserId, setTestUserId] = useState('test_user_loading');

  // Hydrationエラー回避: クライアント側でのみDate.now()を使用
  useEffect(() => {
    setTestUserId(`test_user_${Date.now()}`);
  }, []);
  const [modelId] = useState('HOKUTO4GO');
  const [logs, setLogs] = useState<string[]>([]);
  const [connectionTest, setConnectionTest] = useState<any>(null);

  const addLog = (message: string) => {
    const timestamp = new Date().toLocaleTimeString();
    setLogs(prev => [...prev, `[${timestamp}] ${message}`]);
    console.log(`[WebRTC Test] ${message}`);
  };

  useEffect(() => {
    addLog('テストページ初期化');
    testConnection();
  }, []);

  const testConnection = async () => {
    addLog('シグナリングサーバー接続テスト開始...');
    try {
      const response = await fetch('https://mgg-signaling-production-c1bd.up.railway.app');
      if (response.ok) {
        addLog('✅ シグナリングサーバー応答確認');
      } else {
        addLog(`❌ シグナリングサーバー応答エラー: ${response.status}`);
      }
    } catch (error) {
      addLog(`❌ シグナリングサーバー接続エラー: ${error}`);
    }
  };

  return (
    <div className="min-h-screen p-8 bg-background">
      <div className="max-w-6xl mx-auto">
        <h1 className="text-3xl font-bold mb-8">WebRTC接続テスト</h1>
        
        <div className="mb-6 p-4 bg-card rounded-lg border">
          <h2 className="text-xl font-semibold mb-3">接続情報</h2>
          <div className="space-y-2 text-sm">
            <p>シグナリングサーバー: {process.env.NEXT_PUBLIC_PEERJS_HOST || 'mgg-signaling-production-c1bd.up.railway.app'}</p>
            <p>プロトコル: WSS (Secure WebSocket)</p>
            <p>パス: {process.env.NEXT_PUBLIC_PEERJS_PATH || '/'}</p>
            <p>ポート: {process.env.NEXT_PUBLIC_PEERJS_PORT || '443'}</p>
            <p>モックモード: {process.env.NET8_MOCK_MODE === 'true' ? '有効' : '無効'}</p>
          </div>
        </div>

        {!isGameStarted ? (
          <div className="flex flex-col items-center gap-4">
            <p className="text-muted-foreground">
              WebRTC経由でパチンコ実機の映像を受信します
            </p>
            <Button
              onClick={() => setIsGameStarted(true)}
              size="default"
              className="px-8"
            >
              テスト開始
            </Button>
          </div>
        ) : (
          <div className="space-y-4">
            <Net8GamePlayer
              userId={testUserId}
              modelId={modelId}
              modelName="北斗の拳4号機"
            />
            
            <div className="mt-4">
              <Button
                onClick={() => setIsGameStarted(false)}
                variant="default"
              >
                リセット
              </Button>
            </div>
          </div>
        )}

        {/* ログ表示エリア */}
        <div className="mt-8 p-4 bg-card rounded-lg border">
          <h3 className="font-semibold mb-2">接続ログ</h3>
          <div className="bg-gray-900 text-green-400 p-3 rounded text-xs font-mono h-48 overflow-auto">
            {logs.map((log, i) => (
              <div key={i} className="mb-1">{log}</div>
            ))}
          </div>
        </div>

        <div className="mt-4 p-4 bg-muted rounded-lg">
          <h3 className="font-semibold mb-2">デバッグ情報</h3>
          <div className="text-xs font-mono space-y-1">
            <p>User ID: {testUserId}</p>
            <p>Model ID: {modelId}</p>
            <p>カメラID: {process.env.NET8_CAMERA_PEER_ID || 'camera_10000021_1765859502'}</p>
            <p>環境: {process.env.NODE_ENV}</p>
            <p>API URL: {process.env.NEXT_PUBLIC_API_URL}</p>
            <p>モックモード: {process.env.NET8_MOCK_MODE === 'true' ? '有効' : '無効'}</p>
          </div>
        </div>
      </div>
    </div>
  );
}