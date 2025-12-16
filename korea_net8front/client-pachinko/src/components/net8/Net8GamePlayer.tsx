"use client";

import { useState, useRef, useEffect } from "react";
import { useNET8Game } from "@/hooks/useNET8Game";
import { useWebRTC } from "@/hooks/useWebRTC";
import { GameStartResponse } from "@/types/net8";

interface Net8GamePlayerProps {
  userId: string;
  modelId: string;
  modelName: string;
}

export function Net8GamePlayer({ userId, modelId, modelName }: Net8GamePlayerProps) {
  const { loading, error, session, startGame, endGame } = useNET8Game();
  const [gameStatus, setGameStatus] = useState<"idle" | "playing" | "ended">("idle");
  const [points, setPoints] = useState({ consumed: 0, won: 0 });
  const iframeRef = useRef<HTMLIFrameElement>(null);
  
  // WebRTCでビデオストリーミング
  const { videoRef, connectionStatus } = useWebRTC(session?.signaling);

  const handleStartGame = async () => {
    try {
      const result = await startGame(userId, modelId);
      if (result) {
        setGameStatus("playing");
        setPoints({ consumed: result.pointsConsumed, won: 0 });
      }
    } catch (err) {
      console.error("Failed to start game:", err);
    }
  };

  const handleEndGame = async (result: "win" | "lose", pointsWon: number) => {
    try {
      const gameResult = await endGame(result, pointsWon);
      if (gameResult) {
        setGameStatus("ended");
        setPoints((prev) => ({ ...prev, won: pointsWon }));
      }
    } catch (err) {
      console.error("Failed to end game:", err);
    }
  };

  return (
    <div className="flex flex-col gap-4 w-full max-w-4xl mx-auto">
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
      <div className="relative aspect-video bg-black rounded-lg overflow-hidden border">
        {gameStatus === "idle" && (
          <div className="absolute inset-0 flex flex-col items-center justify-center gap-4 bg-gradient-to-b from-gray-900 to-black">
            <div className="text-center">
              <h2 className="text-2xl font-bold text-white mb-2">{modelName}</h2>
              <p className="text-gray-400 mb-6">NET8 パチンコ・スロット</p>
            </div>
            <button
              onClick={handleStartGame}
              disabled={loading}
              className="px-8 py-4 bg-gradient-to-r from-yellow-500 to-orange-500 text-black font-bold rounded-lg text-lg hover:from-yellow-400 hover:to-orange-400 disabled:opacity-50 disabled:cursor-not-allowed transition-all transform hover:scale-105"
            >
              {loading ? "接続中..." : "ゲーム開始"}
            </button>
          </div>
        )}

        {gameStatus === "playing" && session && (
          <>
            {/* WebRTC Video Stream */}
            <video
              ref={videoRef}
              autoPlay
              playsInline
              muted
              className="w-full h-full object-cover"
              style={{ display: 'block' }}
            />
            
            {/* Overlay with video status */}
            <div className="absolute top-4 left-4 bg-black/70 text-white px-3 py-1 rounded text-sm">
              {connectionStatus === 'connected' && '🔴 映像配信中'}
              {connectionStatus === 'connecting' && '🟡 接続中...'}
              {connectionStatus === 'disconnected' && '⚪ 未接続'}
              {connectionStatus === 'error' && '🔴 接続エラー'}
            </div>
            
            {/* Signaling ID情報 */}
            {session?.signaling?.signalingId && (
              <div className="absolute top-4 right-4 bg-black/70 text-white px-3 py-1 rounded text-xs">
                シグナリングID: {session.signaling.signalingId}
              </div>
            )}

            {/* Control overlay */}
            <div className="absolute bottom-0 left-0 right-0 p-4 bg-gradient-to-t from-black/80 to-transparent">
              <div className="flex items-center justify-between">
                <div className="text-white text-sm">
                  <p>セッション: {session.sessionId?.slice(0, 20)}...</p>
                </div>
                <div className="flex gap-2">
                  <button
                    onClick={() => handleEndGame("lose", 0)}
                    disabled={loading}
                    className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50"
                  >
                    終了 (負け)
                  </button>
                  <button
                    onClick={() => handleEndGame("win", 500)}
                    disabled={loading}
                    className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
                  >
                    終了 (勝ち +500pt)
                  </button>
                </div>
              </div>
            </div>
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

      {/* Session info */}
      {session && gameStatus === "playing" && (
        <div className="p-4 bg-card rounded-lg border text-sm">
          <h3 className="font-semibold mb-2">セッション情報</h3>
          <div className="grid grid-cols-2 gap-2 text-muted-foreground">
            <p>環境: <span className="text-foreground">{session.environment}</span></p>
            <p>台番号: <span className="text-foreground">{session.machineNo}</span></p>
            <p>カテゴリ: <span className="text-foreground">{session.model?.category}</span></p>
            <p>モック: <span className="text-foreground">{session.mock ? "Yes" : "No"}</span></p>
          </div>
        </div>
      )}
    </div>
  );
}
