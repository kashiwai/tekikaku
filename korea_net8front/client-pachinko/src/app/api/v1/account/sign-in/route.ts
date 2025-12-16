// app/api/v1/account/sign-in/route.ts - 韓国側ログインAPI（モック実装）
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { cookies } from 'next/headers';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { loginId, pw, role, timezone } = body;

    console.log('Mock Korea Login Request:', { loginId, role, timezone });

    if (!loginId || !pw) {
      return NextResponse.json({
        success: false,
        error: 'LOGIN_ID_AND_PASSWORD_REQUIRED',
        message: 'ログインIDとパスワードが必要です'
      }, { status: 400 });
    }

    // ユーザー認証
    const user = mockKoreaDB.authenticate(loginId, pw);
    
    if (!user) {
      return NextResponse.json({
        success: false,
        error: 'INVALID_CREDENTIALS',
        message: 'ログインIDまたはパスワードが正しくません'
      }, { status: 401 });
    }

    if (user.status !== 'active') {
      return NextResponse.json({
        success: false,
        error: 'ACCOUNT_INACTIVE',
        message: 'アカウントが無効です'
      }, { status: 403 });
    }

    // セッション作成
    const sessionId = mockKoreaDB.createSession(user);
    
    // クッキー設定
    const cookieStore = await cookies();
    const isProd = process.env.NODE_ENV === 'production';
    
    cookieStore.set({
      name: 'user.sid',
      value: sessionId,
      domain: isProd ? '.goodfriendsgaming.com' : undefined,
      path: '/',
      httpOnly: true,
      secure: isProd,
      maxAge: 60 * 60 * 24, // 24時間
      sameSite: 'lax'
    });

    console.log('Mock Korea Login Success:', { userId: user.id, sessionId });

    return NextResponse.json({
      success: true,
      message: 'ログインに成功しました',
      sessionId, // デバッグ用
      user: {
        id: user.id,
        loginId: user.loginId,
        email: user.email,
        name: user.name
      }
    });

  } catch (error) {
    console.error('Mock Korea Login Error:', error);
    return NextResponse.json({
      success: false,
      error: 'INTERNAL_SERVER_ERROR',
      message: 'サーバーエラーが発生しました'
    }, { status: 500 });
  }
}