// app/api/test/auto-login/route.ts - 自動ログインフローテスト
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';
import { unifiedAuthService } from '@/lib/auth.service';
import { cookies } from 'next/headers';

export async function POST(request: NextRequest) {
  try {
    const body = await request.json();
    const { loginId, password } = body;

    console.log('Auto-login test started:', { loginId });

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

    // 4. NET8自動同期テスト
    let net8SyncResult = null;
    try {
      const koreaUserData = mockKoreaDB.toNextUser(user);
      net8SyncResult = await unifiedAuthService.syncWithNet8(koreaUserData);
      console.log('NET8 sync result:', net8SyncResult);
    } catch (error) {
      console.error('NET8 sync error:', error);
      net8SyncResult = {
        success: false,
        error: error instanceof Error ? error.message : 'NET8 sync failed',
        needsManualSync: true
      };
    }

    return NextResponse.json({
      success: true,
      message: '自動ログイン完了',
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
      net8: {
        syncAttempted: true,
        syncResult: net8SyncResult
      },
      timestamp: new Date().toISOString()
    });

  } catch (error) {
    console.error('Auto-login test error:', error);
    return NextResponse.json({
      success: false,
      error: 'AUTO_LOGIN_FAILED',
      message: error instanceof Error ? error.message : 'Unknown error'
    }, { status: 500 });
  }
}