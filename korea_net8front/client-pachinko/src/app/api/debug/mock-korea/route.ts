// app/api/debug/mock-korea/route.ts - デバッグ用API（モック韓国APIの状態確認）
import { NextRequest, NextResponse } from 'next/server';
import { mockKoreaDB } from '@/lib/mock/korea-db';

export async function GET(request: NextRequest) {
  try {
    const users = mockKoreaDB.getAllUsers();
    const activeSessions = mockKoreaDB.getActiveSessions();

    // パスワードを隠して返す
    const safeUsers = users.map(user => ({
      ...user,
      password: '***hidden***'
    }));

    return NextResponse.json({
      success: true,
      data: {
        totalUsers: users.length,
        users: safeUsers,
        activeSessions: activeSessions.length,
        sessions: activeSessions.map(session => ({
          sessionId: session.sessionId,
          userId: session.userId,
          email: session.email,
          createdAt: session.createdAt,
          expiresAt: session.expiresAt
        }))
      },
      testCredentials: [
        {
          loginId: 'testuser1',
          email: 'test@korea.com',
          password: 'password123',
          name: '테스트 사용자'
        },
        {
          loginId: 'demouser',
          email: 'demo@korea.com', 
          password: 'demo123',
          name: '데모 사용자'
        },
        {
          loginId: 'admin',
          email: 'admin@korea.com',
          password: 'admin123',
          name: '관리자'
        }
      ],
      message: 'Mock Korea API Debug Info'
    });

  } catch (error) {
    console.error('Mock Korea Debug Error:', error);
    return NextResponse.json({
      success: false,
      error: 'DEBUG_ERROR',
      message: error instanceof Error ? error.message : 'Unknown error'
    }, { status: 500 });
  }
}