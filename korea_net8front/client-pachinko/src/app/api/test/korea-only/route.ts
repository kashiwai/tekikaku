// app/api/test/korea-only/route.ts - 韓国側のみの自動ログインテスト
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { cookies } from 'next/headers';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { loginId, password } = body;

    console.log('Korea-only login test started:', { loginId });

    // 1. 韓国側認証
    const user = mockKoreaDB.authenticate(loginId, password);
    if (!user) {
      return NextResponse.json({
        success: false,
        error: 'AUTHENTICATION_FAILED',
        message: '認証に失敗しました'
      }, { status: 401 });
    }

    // 2. セッション作成
    const sessionId = mockKoreaDB.createSession(user);
    
    // 3. クッキー設定
    const cookieStore = await cookies();
    cookieStore.set({
      name: 'user.sid',
      value: sessionId,
      path: '/',
      httpOnly: true,
      maxAge: 60 * 60 * 24, // 24時間
      sameSite: 'lax'
    });

    console.log('Korea-only login successful:', {
      userId: user.id,
      sessionId,
      timestamp: new Date().toISOString()
    });

    return NextResponse.json({
      success: true,
      message: '韓国側ログイン完了（NET8連携なし）',
      korea: {
        authenticated: true,
        user: {
          id: user.id,
          loginId: user.loginId,
          email: user.email,
          name: user.name,
          sessionId
        }
      },
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error('Korea-only login test error:', error);
    return NextResponse.json({
      success: false,
      error: 'KOREA_LOGIN_FAILED',
      message: error instanceof Error ? error.message : 'Unknown error'
    }, { status: 500 });
  }
}