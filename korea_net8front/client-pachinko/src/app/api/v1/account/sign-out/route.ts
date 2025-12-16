// app/api/v1/account/sign-out/route.ts - ログアウトAPI（モック実装）
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { cookies } from 'next/headers';

export async function POST(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get('user.sid')?.value;

    if (sessionId) {
      // セッション削除
      const destroyed = mockKoreaDB.destroySession(sessionId);
      console.log('Mock Korea Logout:', { sessionId, destroyed });
    }

    // クッキー削除
    const isProd = process.env.NODE_ENV === 'production';
    
    cookieStore.set({
      name: 'user.sid',
      value: '',
      domain: isProd ? '.goodfriendsgaming.com' : undefined,
      path: '/',
      httpOnly: true,
      secure: isProd,
      maxAge: 0, // 即座に削除
      sameSite: 'lax'
    });

    return NextResponse.json({
      success: true,
      message: 'ログアウトしました'
    });

  } catch (error) {
    console.error('Mock Korea Logout Error:', error);
    return NextResponse.json({
      success: false,
      error: 'INTERNAL_SERVER_ERROR',
      message: 'ログアウト処理でエラーが発生しました'
    }, { status: 500 });
  }
}