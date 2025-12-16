// components/GameInterface.tsx
'use client';

import { useState } from 'react';
import { useNET8Game } from '@/hooks/useNET8Game';
import PachinkoPlayer from './pachinkoPlayer';

interface GameInterfaceProps {
  userId: string;
  modelId: string;
}

export default function GameInterface({ userId, modelId }: GameInterfaceProps) {
  const { loading, error, session, startGame, endGame } = useNET8Game();
  const [gameResult, setGameResult] = useState<any>(null);

  const handleStartGame = async () => {
    try {
      await startGame(userId, modelId);
      setGameResult(null);
    } catch (error) {
      console.error('Failed to start game:', error);
    }
  };

  const handleGameEnd = async (result: 'win' | 'lose', pointsWon: number) => {
    try {
      const resultData = await endGame(result, pointsWon);
      setGameResult(resultData);
    } catch (error) {
      console.error('Failed to end game:', error);
    }
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center p-8">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        <span className="ml-3">ゲームを読み込み中...</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-lg p-4">
        <p className="text-red-800">{error}</p>
        <button
          onClick={handleStartGame}
          className="mt-2 px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700"
        >
          再試行
        </button>
      </div>
    );
  }

  if (!session) {
    return (
      <div className="text-center p-8">
        <h2 className="text-2xl font-bold mb-4">{modelId}で遊ぶ</h2>
        <div className="max-w-md mx-auto mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
          <p className="text-yellow-800 text-sm">
            ⚠️ テスト環境: ゲーム開始時はモックデータが返されます
          </p>
        </div>
        <button
          onClick={handleStartGame}
          className="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
        >
          ゲーム開始
        </button>
      </div>
    );
  }

  if (gameResult) {
    return (
      <div className="bg-green-50 border border-green-200 rounded-lg p-6">
        <h2 className="text-2xl font-bold mb-4 text-green-800">ゲーム結果</h2>
        <div className="space-y-2">
          <p>
            <strong>結果:</strong>{' '}
            {gameResult.result === 'win' ? '🎉 勝利' : '😢 敗北'}
          </p>
          <p>
            <strong>獲得ポイント:</strong> {gameResult.pointsWon}
          </p>
          <p>
            <strong>純利益:</strong> {gameResult.netProfit}
          </p>
          <p>
            <strong>新しい残高:</strong> {gameResult.newBalance}
          </p>
          <p>
            <strong>プレイ時間:</strong> {gameResult.playDuration}秒
          </p>
        </div>
        <button
          onClick={handleStartGame}
          className="mt-4 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
        >
          もう一度遊ぶ
        </button>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h2 className="text-xl font-bold">ゲームプレイ中</h2>
        <div className="text-sm text-gray-600">
          セッション: {session.sessionId}
        </div>
      </div>
      
      <PachinkoPlayer
        session={session} 
        onGameEnd={handleGameEnd} 
      />
    </div>
  );
}