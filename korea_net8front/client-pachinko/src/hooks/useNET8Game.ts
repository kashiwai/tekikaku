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
        const errorText = await response.text();
        console.error('Game start failed:', response.status, errorText);
        throw new Error(`Failed to start game: ${response.status} ${response.statusText}`);
      }

      const result = await response.json();
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