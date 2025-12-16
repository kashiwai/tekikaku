'use server';

import { NET8Service } from '@/lib/net8.service';
import { revalidatePath } from 'next/cache';

const net8Service = new NET8Service();

export async function startGameAction(userId: string, modelId: string) {
  try {
    const result = await net8Service.startGame({ userId, modelId });
    
    // 必要に応じてキャッシュを再検証
    revalidatePath('/game');
    
    return { success: true, data: result };
  } catch (error) {
    console.error('Game start error:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Unknown error' 
    };
  }
}

export async function endGameAction(
  sessionId: string,
  result: 'win' | 'lose' | 'draw',
  pointsWon: number
) {
  try {
    const gameResult = await net8Service.endGame({
      sessionId,
      result,
      pointsWon,
    });

    revalidatePath('/game');
    revalidatePath('/profile');

    return { success: true, data: gameResult };
  } catch (error) {
    console.error('Game end error:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Unknown error' 
    };
  }
}

export async function addPointsAction(
  userId: string,
  amount: number,
  reason?: string
) {
  try {
    const result = await net8Service.addPoints(userId, amount, reason);
    
    revalidatePath('/profile');
    
    return { success: true, data: result };
  } catch (error) {
    console.error('Add points error:', error);
    return { 
      success: false, 
      error: error instanceof Error ? error.message : 'Unknown error' 
    };
  }
}