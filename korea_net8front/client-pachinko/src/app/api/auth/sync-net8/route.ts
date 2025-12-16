// app/api/auth/sync-net8/route.ts
import { NextRequest, NextResponse } from 'next/server';
import { NET8Service } from '@/lib/net8.service';

const net8Service = new NET8Service();

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { koreaUserId, email, displayName } = body;

    if (!koreaUserId) {
      return NextResponse.json(
        { error: 'Korea user ID is required' },
        { status: 400 }
      );
    }

    // NET8ユーザーID生成
    const net8UserId = `kr_${koreaUserId}_${Date.now()}`;

    // 初回ポイント付与でユーザー登録
    const initialPoints = 1000;
    const pointsResult = await net8Service.addPoints(
      net8UserId, 
      initialPoints, 
      'Initial registration bonus from Korea platform'
    );

    // プレイ履歴取得（ユーザー存在確認）
    const playHistory = await net8Service.getPlayHistory(net8UserId, 5, 0);

    const userProfile = {
      net8UserId,
      balance: initialPoints,
      playHistory: playHistory || [],
      createdAt: new Date().toISOString(),
      lastLogin: new Date().toISOString(),
      registrationResult: pointsResult
    };

    return NextResponse.json({
      success: true,
      userProfile
    });

  } catch (error) {
    console.error('NET8 sync API error:', error);
    
    // エラーレスポンスでも部分的な情報を返す
    const body = await request.json().catch(() => ({}));
    const fallbackUserId = body.koreaUserId ? `kr_${body.koreaUserId}_${Date.now()}` : null;
    
    return NextResponse.json({
      success: false,
      error: error instanceof Error ? error.message : 'Sync failed',
      fallbackUserId
    }, { status: 500 });
  }
}