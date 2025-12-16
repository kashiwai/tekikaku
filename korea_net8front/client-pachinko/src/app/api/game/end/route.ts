// app/api/game/end/route.ts
import { NextRequest, NextResponse } from 'next/server';
import { NET8Service } from '@/lib/net8.service';

const net8Service = new NET8Service();

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { sessionId, result, pointsWon } = body;

    if (!sessionId || !result || pointsWon === undefined) {
      return NextResponse.json(
        { error: 'Missing required parameters' },
        { status: 400 }
      );
    }

    const gameResult = await net8Service.endGame({
      sessionId,
      result,
      pointsWon,
    });

    return NextResponse.json(gameResult);
  } catch (error) {
    console.error('Game end API error:', error);
    return NextResponse.json(
      { error: 'Failed to end game' },
      { status: 500 }
    );
  }
}