// hooks/useNET8Game.ts
'use client';

import { useState, useCallback } from 'react';
import { GameStartResponse, GameEndResponse } from '@/types/net8';

export function useNET8Game() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [session, setSession] = useState<GameStartResponse | null>(null);

  const startGame = useCallback(async (userId: string, modelId: string) => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch('/api/game/start', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ userId, modelId }),
      });

      if (!response.ok) {
        // JSONエラーレスポンスを取得
        let errorData;
        try {
          errorData = await response.json();
        } catch {
          const errorText = await response.text();
          console.error('Game start failed:', response.status, errorText);
          throw new Error(`Failed to start game: ${response.status} ${response.statusText}`);
        }

        console.error('Game start failed:', response.status, errorData);

        // ポイント不足エラーの場合は詳細メッセージを表示
        if (errorData.error === 'INSUFFICIENT_POINTS') {
          throw new Error(
            `${errorData.message}\n現在の残高: ${errorData.current}pt\n必要ポイント: ${errorData.required}pt`
          );
        }

        throw new Error(errorData.message || `Failed to start game: ${response.status}`);
      }

      const result = await response.json();

      // エラーレスポンスの場合（status 200だがerrorフィールドあり）
      if (result.error) {
        throw new Error(result.message || result.error);
      }

      setSession(result);
      return result;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Unknown error';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, []);

  const endGame = useCallback(async (
    result: 'win' | 'lose' | 'draw',
    pointsWon: number
  ) => {
    if (!session) {
      throw new Error('No active session');
    }

    setLoading(true);
    setError(null);

    try {
      const response = await fetch('/api/game/end', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          sessionId: session.sessionId,
          result,
          pointsWon,
          memberNo: session.memberNo, // NET8 APIに必要
        }),
      });

      if (!response.ok) {
        throw new Error('Failed to end game');
      }

      const gameResult: GameEndResponse = await response.json();
      setSession(null);
      return gameResult;
    } catch (err) {
      const message = err instanceof Error ? err.message : 'Unknown error';
      setError(message);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [session]);

  return {
    loading,
    error,
    session,
    startGame,
    endGame,
  };
}