// app/api/test/mock-api-only/route.ts - 簡単なモックAPIテスト
import { NextRequest, NextResponse } from 'next/server';

export async function GET(request: NextRequest) {
  return NextResponse.json({
    success: true,
    message: '韓国側モックAPIが正常に動作しています',
    timestamp: new Date().toISOString(),
    endpoints: {
      login: 'POST /api/v1/account/sign-in',
      loginSuccess: 'POST /api/v1/account/sign-in/success',
      authCheck: 'GET /api/v1/account/sign-in/check',
      logout: 'POST /api/v1/account/sign-out',
      net8Sync: 'POST /api/auth/sync-net8',
      debug: 'GET /api/debug/mock-korea'
    },
    testUsers: [
      { loginId: 'testuser1', password: 'password123', name: '테스트 사용자' },
      { loginId: 'demouser', password: 'demo123', name: '데모 사용자' },
      { loginId: 'admin', password: 'admin123', name: '관리자' }
    ]
  });
}