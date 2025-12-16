// lib/mock/korea-db.ts
import { User } from '@/types/user.types';

// 仮想ユーザーデータベース（メモリ内）
export interface MockUser {
  id: string;
  email: string;
  loginId: string;
  password: string;
  name: string;
  phone?: string;
  balance: number;
  createdAt: string;
  lastLogin?: string;
  role: 'user' | 'admin';
  status: 'active' | 'inactive' | 'suspended';
}

// 仮想セッション管理
export interface MockSession {
  sessionId: string;
  userId: string;
  email: string;
  loginId: string;
  createdAt: Date;
  expiresAt: Date;
}

class MockKoreaDatabase {
  private users: Map<string, MockUser> = new Map();
  private sessions: Map<string, MockSession> = new Map();
  private emailIndex: Map<string, string> = new Map(); // email -> userId
  private loginIdIndex: Map<string, string> = new Map(); // loginId -> userId

  constructor() {
    this.initializeDefaultUsers();
  }

  // デフォルトテストユーザーを作成
  private initializeDefaultUsers() {
    const defaultUsers: MockUser[] = [
      {
        id: 'user_001',
        email: 'test@korea.com',
        loginId: 'testuser1',
        password: 'password123', // 実際の実装では暗号化が必要
        name: '테스트 사용자',
        phone: '+82-10-1234-5678',
        balance: 50000,
        createdAt: '2024-01-01T00:00:00Z',
        role: 'user',
        status: 'active'
      },
      {
        id: 'user_002', 
        email: 'demo@korea.com',
        loginId: 'demouser',
        password: 'demo123',
        name: '데모 사용자',
        phone: '+82-10-9876-5432',
        balance: 75000,
        createdAt: '2024-01-15T00:00:00Z',
        role: 'user',
        status: 'active'
      },
      {
        id: 'user_003',
        email: 'admin@korea.com', 
        loginId: 'admin',
        password: 'admin123',
        name: '관리자',
        balance: 1000000,
        createdAt: '2024-01-01T00:00:00Z',
        role: 'admin',
        status: 'active'
      }
    ];

    defaultUsers.forEach(user => {
      this.users.set(user.id, user);
      this.emailIndex.set(user.email, user.id);
      this.loginIdIndex.set(user.loginId, user.id);
    });
  }

  // ユーザー認証
  authenticate(loginId: string, password: string): MockUser | null {
    const userId = this.emailIndex.get(loginId) || this.loginIdIndex.get(loginId);
    if (!userId) return null;

    const user = this.users.get(userId);
    if (!user || user.password !== password || user.status !== 'active') {
      return null;
    }

    // ログイン時刻更新
    user.lastLogin = new Date().toISOString();
    return user;
  }

  // セッション作成
  createSession(user: MockUser): string {
    const sessionId = `mock_session_${Date.now()}_${Math.random().toString(36).slice(2)}`;
    const expiresAt = new Date();
    expiresAt.setHours(expiresAt.getHours() + 24); // 24時間有効

    const session: MockSession = {
      sessionId,
      userId: user.id,
      email: user.email,
      loginId: user.loginId,
      createdAt: new Date(),
      expiresAt
    };

    this.sessions.set(sessionId, session);
    return sessionId;
  }

  // セッション検証
  validateSession(sessionId: string): MockUser | null {
    const session = this.sessions.get(sessionId);
    if (!session || session.expiresAt < new Date()) {
      if (session) this.sessions.delete(sessionId);
      return null;
    }

    const user = this.users.get(session.userId);
    return user || null;
  }

  // セッション削除
  destroySession(sessionId: string): boolean {
    return this.sessions.delete(sessionId);
  }

  // セッション取得（IDによる）
  getSessionById(sessionId: string): { sessionId: string; user: MockUser | null; createdAt: number; expiresAt: number } | null {
    const session = this.sessions.get(sessionId);
    if (!session) return null;

    const user = this.users.get(session.userId);
    
    return {
      sessionId,
      user: user || null,
      createdAt: session.createdAt.getTime(),
      expiresAt: session.expiresAt.getTime()
    };
  }

  // ユーザーをNext.js User型に変換
  toNextUser(mockUser: MockUser): User {
    return {
      id: parseInt(mockUser.id.replace('user_', '')) || 1,
      loginId: mockUser.loginId,
      siteId: 1,
      agencyId: 1,
      referral: 0,
      userCode: mockUser.loginId,
      info: {
        ip: '127.0.0.1',
        os: {},
        exp: 100,
        needExp: 200,
        spin: 3,
        level: 1,
        phone: mockUser.phone || '',
        client: {
          name: 'korea-client',
          type: 'web',
          version: '1.0.0'
        },
        device: {
          id: 'korea-device',
          type: 'desktop',
          brand: 'chrome',
          model: 'browser'
        },
        nickname: mockUser.name,
        isApprove: 'approved',
        sessionId: 'korea_session',
        transaction: {
          bank: 'KB국민은행',
          bankNumber: '123456789',
          realname: mockUser.name,
          withdrawalType: 'bank'
        },
        nextLevelData: {
          bonus: 1000,
          needExp: 200,
          name: 'Level 2'
        },
        curLevelData: {
          bonus: 500,
          needExp: 100,
          name: 'Level 1'
        }
      },
      rollingCommission: {
        games: {
          slot: 0.5,
          casino: 0.3
        },
        isUse: true
      },
      bonus: {
        locked: 0,
        unlocked: 1000
      },
      attendance: {
        dates: [],
        totalReward: 0,
        streakReward: 0,
        count: 0,
        total: 0,
        streakDays: 0
      },
      isUse: true,
      roulette: {
        count: 3,
        total: 10
      },
      wallets: {
        money: mockUser.balance,
        vault: 0
      },
      rolling: {
        games: {
          slot: 0,
          casino: 0,
          holdem: 0,
          sports: 0,
          virtual: 0,
          pachinko: 0
        },
        isUse: false
      },
      losingCommission: {
        games: {
          slot: 0,
          casino: 0
        },
        isUse: false
      },
      eventBans: [],
      gameBans: []
    } as User;
  }

  // デバッグ用：全ユーザー取得
  getAllUsers(): MockUser[] {
    return Array.from(this.users.values());
  }

  // デバッグ用：アクティブセッション取得
  getActiveSessions(): MockSession[] {
    const now = new Date();
    return Array.from(this.sessions.values())
      .filter(session => session.expiresAt > now);
  }
}

// シングルトンインスタンス
export const mockKoreaDB = new MockKoreaDatabase();