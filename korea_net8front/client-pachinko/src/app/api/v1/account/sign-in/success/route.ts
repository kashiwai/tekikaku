// app/api/v1/account/sign-in/success/route.ts - ログイン成功API（モック実装）
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { cookies } from 'next/headers';

export async function POST(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get('user.sid')?.value;

    if (!sessionId) {
      return NextResponse.json({
        success: false,
        error: 'SESSION_NOT_FOUND',
        message: 'セッションが見つかりません'
      }, { status: 401 });
    }

    // セッション検証
    const user = mockKoreaDB.validateSession(sessionId);
    
    if (!user) {
      return NextResponse.json({
        success: false,
        error: 'INVALID_SESSION',
        message: 'セッションが無効です'
      }, { status: 401 });
    }

    // 完全なユーザー情報を返す
    const userData = mockKoreaDB.toNextUser(user);
    
    console.log('Mock Korea Login Success API:', { userId: user.id });

    return NextResponse.json({
      success: true,
      data: userData,
      message: 'ログイン情報取得成功'
    });

  } catch (error) {
    console.error('Mock Korea Login Success Error:', error);
    return NextResponse.json({
      success: false,
      error: 'INTERNAL_SERVER_ERROR',
      message: 'サーバーエラーが発生しました'
    }, { status: 500 });
  }
}