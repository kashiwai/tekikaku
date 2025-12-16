// app/api/v1/account/sign-in/check/route.ts - ログイン状態確認API（モック実装）
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { cookies } from 'next/headers';

export async function GET(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get('user.sid')?.value;

    if (!sessionId) {
      return NextResponse.json({
        success: false,
        authenticated: false,
        message: 'セッションが見つかりません'
      });
    }

    // セッション検証
    const user = mockKoreaDB.validateSession(sessionId);
    
    if (!user) {
      return NextResponse.json({
        success: false,
        authenticated: false,
        message: 'セッションが無効または期限切れです'
      });
    }

    console.log('Mock Korea Auth Check:', { userId: user.id, authenticated: true });

    return NextResponse.json({
      success: true,
      authenticated: true,
      user: {
        id: user.id,
        loginId: user.loginId,
        email: user.email,
        name: user.name,
        lastLogin: user.lastLogin
      },
      message: 'ログイン状態確認成功'
    });

  } catch (error) {
    console.error('Mock Korea Auth Check Error:', error);
    return NextResponse.json({
      success: false,
      authenticated: false,
      error: 'INTERNAL_SERVER_ERROR',
      message: 'サーバーエラーが発生しました'
    }, { status: 500 });
  }
}

export async function POST(request: NextRequest) {
  // POSTメソッドもGETと同じ処理
  return GET(request);
}