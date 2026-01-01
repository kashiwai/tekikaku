"use client";

import { useState, useEffect, useRef } from "react";
import { useParams } from "next/navigation";
import { useNET8Game } from "@/hooks/useNET8Game";

interface Net8GamePlayerIframeProps {
  userId: string;        // 韓国側UserID
  net8UserId: string;    // NET8側UserID（ゲームAPIで使用）
  modelId: string;
  modelName: string;
}

// ローディングスピナーコンポーネント
function LoadingSpinner({ message, subMessage }: { message: string; subMessage?: string }) {
  return (
    <div className="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-b from-gray-900 via-black to-gray-900 z-50">
      {/* パチンコ玉風のアニメーション */}
      <div className="relative w-32 h-32 mb-8">
        {/* 外側の回転リング */}
        <div className="absolute inset-0 border-4 border-transparent border-t-yellow-500 border-r-orange-500 rounded-full animate-spin" />
        {/* 中間の回転リング（逆回転） */}
        <div className="absolute inset-2 border-4 border-transparent border-b-yellow-400 border-l-orange-400 rounded-full animate-spin" style={{ animationDirection: 'reverse', animationDuration: '1.5s' }} />
        {/* 中央のパチンコ玉 */}
        <div className="absolute inset-4 flex items-center justify-center">
          <div className="w-16 h-16 bg-gradient-to-br from-gray-300 via-white to-gray-400 rounded-full shadow-lg animate-pulse">
            <div className="absolute top-2 left-4 w-4 h-2 bg-white/60 rounded-full transform rotate-45" />
          </div>
        </div>
      </div>

      {/* メインメッセージ */}
      <div className="text-center">
        <p className="text-2xl font-bold text-yellow-400 mb-2 animate-pulse">{message}</p>
        {subMessage && (
          <p className="text-sm text-gray-400">{subMessage}</p>
        )}
      </div>

      {/* プログレスドット */}
      <div className="flex gap-2 mt-6">
        <div className="w-3 h-3 bg-yellow-500 rounded-full animate-bounce" style={{ animationDelay: '0ms' }} />
        <div className="w-3 h-3 bg-orange-500 rounded-full animate-bounce" style={{ animationDelay: '150ms' }} />
        <div className="w-3 h-3 bg-yellow-500 rounded-full animate-bounce" style={{ animationDelay: '300ms' }} />
      </div>
    </div>
  );
}

export function Net8GamePlayerIframe({ userId, net8UserId, modelId, modelName }: Net8GamePlayerIframeProps) {
  const params = useParams();
  const locale = (params?.locale as string) || 'ja';
  const { loading, error, session, startGame, endGame } = useNET8Game();
  const [gameStatus, setGameStatus] = useState<"idle" | "starting" | "playing" | "connected" | "ended">("idle");
  const [loadingMessage, setLoadingMessage] = useState<string>("");
  const [loadingSubMessage, setLoadingSubMessage] = useState<string>("");
  const [points, setPoints] = useState({ consumed: 0, won: 0 });
  const [playUrl, setPlayUrl] = useState<string | null>(null);
  const [playerStatus, setPlayerStatus] = useState<string>("待機中");
  const iframeRef = useRef<HTMLIFrameElement>(null);

  // play_embedからのpostMessageを受信
  useEffect(() => {
    const handleMessage = async (event: MessageEvent) => {
      // NET8ドメインからのメッセージのみ処理
      if (!event.origin.includes('mgg-webservice-production') && !event.origin.includes('net8games')) return;

      console.log('[NET8 Event]:', event.data);

      const { type, data } = event.data || {};

      switch (type) {
        case 'NET8_PLAYER_READY':
          setPlayerStatus('準備完了');
          setLoadingMessage('プレイヤー準備完了');
          break;
        case 'NET8_INITIALIZED':
          setPlayerStatus('初期化完了');
          break;
        case 'NET8_CONNECTED':
          setPlayerStatus('カメラ接続完了');
          setGameStatus('connected');
          break;
        case 'NET8_STATUS':
          setPlayerStatus(data?.message || 'ステータス更新');
          break;
        case 'NET8_GAME_END':
          setPlayerStatus('ゲーム終了');
          // 精算はgame:settlement経由で処理するため、ここでは状態更新のみ
          break;
        case 'NET8_ERROR':
          console.error('[NET8 Error]:', data?.message);
          setPlayerStatus('エラー発生');
          break;
        case 'game:settlement':
          console.log('[NET8 Settlement]:', event.data.payload);
          // 精算データを受け取って処理（インラインで処理してクロージャ問題を回避）
          const settlementData = event.data.payload;
          try {
            console.log('[Settlement] Processing:', settlementData);
            const returnPoints = settlementData.credit || 0;
            const result = returnPoints > 0 ? "win" : "lose";

            // endGameを直接呼び出し（最新のsessionを使用）
            await endGame(result, returnPoints);

            setGameStatus("ended");
            setPoints((prev) => ({ ...prev, won: returnPoints }));
            setPlayUrl(null);
          } catch (err) {
            console.error("Settlement failed:", err);
            // エラーでも終了画面を表示
            setGameStatus("ended");
            setPoints((prev) => ({ ...prev, won: settlementData.credit || 0 }));
            setPlayUrl(null);
          }
          break;
      }
    };

    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  }, [endGame]);

  const handleStartGame = async () => {
    try {
      // ★ ポイント残高チェック（チャージ確認）
      const sessionCheck = await fetch('/api/test/session-check');
      if (!sessionCheck.ok) {
        alert('セッション確認に失敗しました。再度ログインしてください。');
        return;
      }

      const sessionData = await sessionCheck.json();
      const userBalance = sessionData.user?.wallets?.money || 0;
      const minimumPoints = 100; // 最低必要ポイント（1ゲーム分）

      console.log('[Point Check] Balance:', userBalance, 'Required:', minimumPoints);

      if (userBalance < minimumPoints) {
        // ポイント不足時のアラート表示
        alert(
          `ポイントが不足しています。\nチャージしてからご利用ください。\n\n` +
          `現在の残高: ${userBalance.toLocaleString()}pt\n` +
          `必要ポイント: ${minimumPoints.toLocaleString()}pt`
        );
        return; // ゲーム開始を中止
      }

      // ローディング開始
      setGameStatus("starting");
      setLoadingMessage("ゲーム準備中...");
      setLoadingSubMessage("サーバーに接続しています");

      // NET8 APIにはnet8UserIdを使用
      console.log('[Game] Starting game with NET8 UserId:', net8UserId);

      // API呼び出し
      setLoadingMessage("台に接続中...");
      setLoadingSubMessage("セッションを確立しています");

      const result = await startGame(net8UserId, modelId);

      if (result) {
        console.log('[Game] Session started:', result);

        // カメラPeerIDを取得
        const cameraId = result.camera?.peerId || result.webRTC?.remotePeerId || '';
        console.log('[Camera PeerID]:', cameraId);

        // ユーザーのポイント残高を取得
        const userPoints = result.gameState?.credits || result.newBalance || 10000; // デフォルト10000ポイント
        const userCredit = result.gameState?.betAmount || 0;
        console.log('[User Points]:', userPoints, 'Credit:', userCredit);

        // play_embed URLを構築（ポイント情報含む + 多言語対応）
        const url = `https://mgg-webservice-production.up.railway.app/data/play_embed/?NO=${result.machineNo}&sessionId=${result.sessionId}&userId=${net8UserId}&cameraId=${encodeURIComponent(cameraId)}&points=${userPoints}&credit=${userCredit}&lang=${locale}`;
        console.log('[Play URL]:', url);

        setPlayUrl(url);
        setLoadingMessage("映像を取得中...");
        setLoadingSubMessage("実機カメラと接続しています");
        setGameStatus("playing");
        setPoints({ consumed: result.pointsConsumed || 100, won: 0 });
      }
    } catch (err) {
      console.error("Failed to start game:", err);
      setGameStatus("idle");
      setLoadingMessage("");

      // エラーメッセージをユーザーに表示
      const errorMessage = err instanceof Error ? err.message : 'ゲーム開始に失敗しました';
      alert(errorMessage);
    }
  };

  // 接続状態の表示
  const getConnectionStatusText = () => {
    switch (gameStatus) {
      case 'starting':
        return loadingMessage || '接続中...';
      case 'playing':
        return playerStatus === 'カメラ接続完了' ? '接続完了' : '接続中...';
      case 'connected':
        return '接続完了';
      default:
        return '待機中';
    }
  };

  const isConnected = gameStatus === 'connected' || playerStatus === 'カメラ接続完了';

  return (
    <div className="flex flex-col gap-4 w-full max-w-7xl mx-auto">
      {/* Header */}
      <div className="flex items-center justify-between p-4 bg-card rounded-lg border">
        <div>
          <h1 className="text-xl font-bold">{modelName}</h1>
          <p className="text-sm text-muted-foreground">機種ID: {modelId}</p>
        </div>
        <div className="text-right">
          {session && (
            <div className="text-sm">
              <p>消費: <span className="font-mono text-yellow-500">{points.consumed}</span> pt</p>
              <p>残高: <span className="font-mono text-green-500">{session.points?.balance || "---"}</span> pt</p>
            </div>
          )}
        </div>
      </div>

      {/* Game Area */}
      <div className="relative bg-black rounded-lg overflow-hidden border" style={{ height: '600px' }}>
        {/* ローディング状態 */}
        {gameStatus === "starting" && (
          <LoadingSpinner message={loadingMessage} subMessage={loadingSubMessage} />
        )}

        {/* 初期状態（ゲーム開始前） */}
        {gameStatus === "idle" && (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-gray-900 to-black">
            <div className="text-center">
              <h2 className="text-2xl font-bold text-white mb-2">{modelName}</h2>
              <p className="text-gray-400 mb-6">NET8 パチンコ・スロット</p>
            </div>
            <button
              onClick={handleStartGame}
              disabled={loading}
              className="px-8 py-4 bg-gradient-to-r from-yellow-500 to-orange-500 text-black font-bold rounded-lg text-lg hover:from-yellow-400 hover:to-orange-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105 shadow-lg shadow-orange-500/30"
            >
              {loading ? "接続中..." : "ゲーム開始"}
            </button>
          </div>
        )}

        {/* プレイ中 - play_embed iframe */}
        {(gameStatus === "playing" || gameStatus === "connected") && session && playUrl && (
          <>
            {/* play_embed iframe - フルスクリーンで表示（オーバーレイ最小化） */}
            <iframe
              ref={iframeRef}
              src={playUrl}
              className="absolute inset-0 w-full h-full border-0"
              allow="camera; microphone; autoplay; fullscreen; encrypted-media"
              allowFullScreen
            />

            {/* 最小化された接続ステータス（左上） */}
            <div className="absolute top-2 left-2 px-3 py-1 bg-black/80 rounded-full text-xs z-10 flex items-center gap-2">
              <span className={`w-2 h-2 rounded-full ${isConnected ? 'bg-green-500' : 'bg-yellow-500 animate-pulse'}`} />
              <span className="text-white">{isConnected ? '接続完了' : '接続中'}</span>
            </div>

            {/* セッション情報（右上） */}
            <div className="absolute top-2 right-2 px-3 py-1 bg-black/80 rounded-full text-xs z-10">
              <span className="text-gray-400">Session: {session.sessionId.substring(0, 8)}...</span>
            </div>

            {/* 終了ボタンのみ（右上、小さく） */}
            <button
              onClick={() => {
                // iframeに精算コマンドを送信
                if (iframeRef.current?.contentWindow) {
                  iframeRef.current.contentWindow.postMessage({
                    type: 'NET8_EXIT'
                  }, '*');
                  console.log('[Korea] Sent NET8_EXIT to iframe');
                }
              }}
              className="absolute top-2 right-36 px-4 py-1 bg-red-600/80 text-white text-xs font-bold rounded-full hover:bg-red-700 transition-all z-10"
            >
              終了
            </button>
          </>
        )}

        {gameStatus === "ended" && (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-gray-900 to-black">
            <div className="text-center">
              <h2 className="text-3xl font-bold text-white mb-2">ゲーム終了</h2>
              <div className="text-lg text-gray-300 space-y-1">
                <p>消費ポイント: <span className="text-yellow-500">{points.consumed}</span></p>
                <p>獲得ポイント: <span className="text-green-500">{points.won}</span></p>
                <p className="text-xl mt-2">
                  収支: <span className={points.won - points.consumed >= 0 ? "text-green-400" : "text-red-400"}>
                    {points.won - points.consumed >= 0 ? "+" : ""}{points.won - points.consumed}
                  </span> pt
                </p>
              </div>
            </div>
            <button
              onClick={() => setGameStatus("idle")}
              className="px-6 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition-all"
            >
              もう一度プレイ
            </button>
          </div>
        )}
      </div>

      {/* Error display */}
      {error && (
        <div className="p-4 bg-red-500/10 border border-red-500/50 rounded-lg">
          <p className="text-red-500">{error}</p>
        </div>
      )}

      {/* Debug Info */}
      {session && (
        <div className="p-4 bg-card rounded-lg border text-sm">
          <h3 className="font-semibold mb-2">接続情報</h3>
          <div className="grid grid-cols-2 gap-2 text-muted-foreground">
            <p>韓国UserID: {userId}</p>
            <p>NET8 UserID: {net8UserId}</p>
            <p>セッションID: {session.sessionId}</p>
            <p>台番号: {session.machineNo}</p>
            <p>環境: {session.environment}</p>
            <p>接続状態: {playerStatus}</p>
            {playUrl && (
              <p className="col-span-2 text-xs break-all">URL: {playUrl}</p>
            )}
          </div>
        </div>
      )}
    </div>
  );
}
