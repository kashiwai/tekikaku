// components/GamePlayer.tsx
"use client";

import { useState, useRef, useEffect } from "react";
import { GameStartResponse } from "@/types/net8";

interface GamePlayerProps {
  session: GameStartResponse;
  onGameEnd: (result: "win" | "lose", points: number) => void;
}

export default function PachinkoPlayer({ session, onGameEnd }: GamePlayerProps) {
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const videoRef = useRef<HTMLVideoElement>(null);
  const [isPlaying, setIsPlaying] = useState(false);
  const [playbackError, setPlaybackError] = useState<string | null>(null);

  // Check if this is a mock session
  const isMock = session.camera?.mock || session.mock || false;

  useEffect(() => {
    if (isMock && videoRef.current) {
      // For mock streams, we can simulate video playback
      simulateMockPlayback();
    }
  }, [isMock]);

  const simulateMockPlayback = () => {
    // Simulate game playback with mock events
    setTimeout(() => {
      setIsPlaying(true);
      console.log("Mock game playback started");
    }, 1000);
  };

  const handleRealStreamPlayback = async () => {
    try {
      if (session.camera?.streamUrl) {
        // For real RTSP/WebRTC streams, you'd use a WebRTC library
        await initializeWebRTCStream();
      } else if (session.playUrl) {
        // Direct play URL (iframe)
        setIsPlaying(true);
      }
    } catch (error) {
      console.error("Stream playback failed:", error);
      setPlaybackError("ストリームの再生に失敗しました");
    }
  };

  const initializeWebRTCStream = async () => {
    // This is where you'd integrate with WebRTC for real camera streams
    // Using the signaling server information from session.signaling
    console.log("Initializing WebRTC stream:", session.signaling);

    // Placeholder for WebRTC implementation
    // You'd use libraries like simple-peer or implement WebRTC directly
  };

  const handleWin = () => {
    // Simulate win logic - in real game, this would come from game events
    const pointsWon = calculateWinPoints();
    onGameEnd("win", pointsWon);
  };

  const handleLose = () => {
    onGameEnd("lose", 0);
  };

  const calculateWinPoints = () => {
    // Simple points calculation - replace with actual game logic
    const basePoints = session.pointsConsumed * 2;
    return Math.floor(basePoints * (0.5 + Math.random()));
  };

  // Render appropriate player based on session type
  const renderPlayer = () => {
    if (isMock) {
      return renderMockPlayer();
    }

    if (session.playUrl) {
      return renderIframePlayer();
    }

    if (session.camera?.streamUrl && !session.camera.mock) {
      return renderStreamPlayer();
    }

    return <div>不明なゲームタイプ</div>;
  };

  const renderMockPlayer = () => {
    return (
      <div className="relative bg-gray-900 rounded-lg overflow-hidden">
        {/* Mock game visualization */}
        <div className="aspect-video bg-gradient-to-br from-blue-900 to-purple-900 flex items-center justify-center">
          <div className="text-center text-white">
            <div className="text-4xl mb-4">🎰</div>
            <h3 className="text-xl font-bold mb-2">{session.model.name}</h3>
            <p className="text-gray-300">モックゲーム再生中</p>
            <p className="text-sm text-gray-400 mt-2">
              カメラ: {session.camera.cameraNo} | 機械: {session.machineNo}
            </p>
          </div>
        </div>

        {/* Mock game controls */}
        <div className="absolute bottom-4 left-4 right-4 flex gap-2">
          <button
            onClick={handleWin}
            className="flex-1 bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition-colors"
          >
            🎉 勝利シミュレーション
          </button>
          <button
            onClick={handleLose}
            className="flex-1 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition-colors"
          >
            😢 敗北シミュレーション
          </button>
        </div>
      </div>
    );
  };

  const renderIframePlayer = () => {
    return (
      <div className="relative">
        <iframe
          ref={iframeRef}
          src={session.playUrl}
          className="w-full aspect-video rounded-lg"
          allow="autoplay; fullscreen"
          sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
        />
        <div className="absolute bottom-4 left-4 right-4 flex gap-2">
          <button
            onClick={handleWin}
            className="flex-1 bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition-colors"
          >
            勝利報告
          </button>
          <button
            onClick={handleLose}
            className="flex-1 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition-colors"
          >
            敗北報告
          </button>
        </div>
      </div>
    );
  };

  const renderStreamPlayer = () => {
    return (
      <div className="relative">
        <video
          ref={videoRef}
          className="w-full aspect-video rounded-lg bg-black"
          controls
          autoPlay
          muted
        >
          <source src={session.camera.streamUrl} type="application/x-mpegURL" />
          お使いのブラウザはビデオタグをサポートしていません。
        </video>
        <div className="absolute bottom-4 left-4 right-4 flex gap-2">
          <button
            onClick={handleWin}
            className="flex-1 bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 transition-colors"
          >
            勝利報告
          </button>
          <button
            onClick={handleLose}
            className="flex-1 bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition-colors"
          >
            敗北報告
          </button>
        </div>
      </div>
    );
  };

  return (
    <div className="game-player">
      <div className="mb-4 p-4 bg-blue-50 rounded-lg">
        <h3 className="font-bold text-lg mb-2">ゲーム情報</h3>
        <div className="grid grid-cols-2 gap-2 text-sm">
          <div>
            <span className="font-medium">モデル:</span> {session.model.name}
          </div>
          <div>
            <span className="font-medium">セッションID:</span>{" "}
            {session.sessionId}
          </div>
          <div>
            <span className="font-medium">環境:</span> {session.environment}
          </div>
          <div>
            <span className="font-medium">タイプ:</span>{" "}
            {isMock ? "モック" : "本番"}
          </div>
        </div>
      </div>

      {playbackError && (
        <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
          <p className="text-red-800">{playbackError}</p>
        </div>
      )}

      {renderPlayer()}

      {/* Game statistics */}
      <div className="mt-4 grid grid-cols-3 gap-4 text-center">
        <div className="p-3 bg-gray-100 rounded-lg">
          <div className="text-2xl font-bold text-blue-600">
            {session.pointsConsumed}
          </div>
          <div className="text-sm text-gray-600">消費ポイント</div>
        </div>
        <div className="p-3 bg-gray-100 rounded-lg">
          <div className="text-2xl font-bold text-gray-600">
            {session.points.balance}
          </div>
          <div className="text-sm text-gray-600">現在の残高</div>
        </div>
        <div className="p-3 bg-gray-100 rounded-lg">
          <div className="text-2xl font-bold text-green-600">-</div>
          <div className="text-sm text-gray-600">獲得ポイント</div>
        </div>
      </div>
    </div>
  );
}
