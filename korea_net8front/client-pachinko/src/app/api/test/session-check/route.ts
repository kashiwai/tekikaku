// app/api/test/session-check/route.ts - セッション確認API
import { NextRequest, NextResponse } from 'next/server';
import { cookies } from 'next/headers';
import { mockKoreaDB } from '@/lib/mock/korea-db';

export async function GET(request: NextRequest) {
  try {
    const cookieStore = await cookies();
    const sessionId = cookieStore.get('user.sid')?.value;

    console.log('Session check started:', { sessionId });

    if (!sessionId) {
      return NextResponse.json({
        success: false,
        authenticated: false,
        message: 'セッションが見つかりません'
      });
    }

    // セッション検証
    const session = mockKoreaDB.getSessionById(sessionId);
    if (!session || !session.user) {
      return NextResponse.json({
        success: false,
        authenticated: false,
        message: 'セッションが無効です'
      });
    }

    return NextResponse.json({
      success: true,
      authenticated: true,
      user: {
        id: session.user.id,
        loginId: session.user.loginId,
        email: session.user.email,
        name: session.user.name,
        sessionId: session.sessionId
      },
      sessionInfo: {
        createdAt: session.createdAt,
        expiresAt: session.expiresAt,
        isValid: session.createdAt <= Date.now() && session.expiresAt > Date.now()
      },
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error('Session check error:', error);
    return NextResponse.json({
      success: false,
      error: 'SESSION_CHECK_FAILED',
      message: error instanceof Error ? error.message : 'Unknown error'
    }, { status: 500 });
  }
}