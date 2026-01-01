// app/api/game/end/route.ts
import { NextRequest, NextResponse } from 'next/server';

const NET8_API_BASE = process.env.NET8_API_BASE_URL || 'https://mgg-webservice-production.up.railway.app';
const NET8_API_KEY = process.env.NET8_API_KEY || 'pk_demo_12345';
const MOCK_MODE = process.env.NET8_MOCK_MODE === 'true';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { sessionId, result, pointsWon, resultData } = body;

    if (!sessionId || !result || pointsWon === undefined) {
      return NextResponse.json(
        { error: 'Missing required parameters' },
        { status: 400 }
      );
    }

    console.log(`[NET8] Ending game session: ${sessionId}, result: ${result}, pointsWon: ${pointsWon}`);

    // モックモードの場合
    if (MOCK_MODE) {
      const endTime = new Date().toISOString();
      const gameResult = {
        sessionId,
        result,
        pointsWon,
        endTime,
        newBalance: 50000 + (result === 'win' ? pointsWon : -100),
        summary: {
          totalRounds: Math.floor(Math.random() * 50) + 10,
          totalBet: Math.floor(Math.random() * 5000) + 1000,
          totalWin: pointsWon,
          netProfit: pointsWon - 100
        },
        success: true,
        mock: true
      };
      return NextResponse.json(gameResult);
    }

    // 実際のNET8 APIを呼び出す
    try {
      console.log(`[NET8] Calling game_end API for session: ${sessionId}`);
      const net8Response = await fetch(`${NET8_API_BASE}/api/v1/game_end.php`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${NET8_API_KEY}`,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          sessionId: sessionId,
          result: result,
          pointsWon: pointsWon,
          resultData: resultData || {}
        }),
      });

      if (!net8Response.ok) {
        const errorData = await net8Response.json().catch(() => null);
        console.error('[NET8] Game end API error:', errorData);
        throw new Error('NET8 API call failed');
      }

      const net8Data = await net8Response.json();
      console.log('[NET8] Game end response:', net8Data);

      return NextResponse.json({
        ...net8Data,
        success: true,
        mock: false
      });

    } catch (apiError) {
      console.error('[NET8] API connection failed:', apiError);

      // フォールバック
      const endTime = new Date().toISOString();
      return NextResponse.json({
        sessionId,
        result,
        pointsWon,
        endTime,
        newBalance: pointsWon,
        success: true,
        fallback: true,
        error: 'NET8 API temporarily unavailable'
      });
    }

  } catch (error) {
    console.error('Game end API error:', error);
    return NextResponse.json(
      { error: 'Failed to end game' },
      { status: 500 }
    );
  }
}