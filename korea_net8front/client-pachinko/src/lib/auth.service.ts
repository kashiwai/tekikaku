// lib/auth.service.ts
import { User } from '@/types/user.types';
import { mockKoreaDB } from './mock/korea-db';

export interface UnifiedUser {
  // 韓国側ユーザー情報
  koreaUser: User;
  // NET8側ユーザー情報
  net8UserId: string;
  net8UserProfile?: {
    balance: number;
    playHistory: any[];
    createdAt: Date;
    lastLogin: Date;
  };
  // 統合情報
  isNet8Synced: boolean;
  syncedAt?: Date;
}

export interface AuthSyncResult {
  success: boolean;
  unifiedUser?: UnifiedUser;
  error?: string;
  needsRegistration?: boolean;
}

export class UnifiedAuthService {
  constructor() {
    // mockKoreaDBを使用するため、NET8Serviceは不要
  }

  /**
   * 韓国側ログイン後にNET8側のユーザーと自動連携
   */
  async syncWithNet8(koreaUser: User): Promise<AuthSyncResult> {
    try {
      // 1. NET8側でユーザーID生成（韓国側ユーザーIDベース）
      const net8UserId = this.generateNet8UserId(koreaUser.id);
      
      // 2. NET8側でユーザー存在確認
      const existingNet8User = await this.checkNet8User(net8UserId);
      
      if (!existingNet8User) {
        // 3. NET8側に新規ユーザー作成
        const registrationResult = await this.registerNet8User(net8UserId, koreaUser);
        if (!registrationResult.success) {
          return {
            success: false,
            error: 'Failed to register NET8 user',
            needsRegistration: true
          };
        }
      }

      // 4. 統合ユーザー情報作成
      const unifiedUser: UnifiedUser = {
        koreaUser,
        net8UserId,
        net8UserProfile: existingNet8User || await this.getNet8UserProfile(net8UserId),
        isNet8Synced: true,
        syncedAt: new Date()
      };

      // 5. ローカルストレージに統合情報保存
      await this.saveUnifiedSession(unifiedUser);

      return {
        success: true,
        unifiedUser
      };
    } catch (error) {
      console.error('Auth sync error:', error);
      return {
        success: false,
        error: error instanceof Error ? error.message : 'Unknown sync error'
      };
    }
  }

  /**
   * 韓国側ユーザーIDからNET8用ユーザーID生成
   * 一貫性のあるID生成（タイムスタンプなし）
   */
  private generateNet8UserId(koreaUserId: string | number): string {
    const userId = String(koreaUserId);
    // mockKoreaDBの一貫したID生成を使用
    return mockKoreaDB.generateNet8UserId(userId);
  }

  /**
   * NET8側でユーザー存在確認
   */
  private async checkNet8User(net8UserId: string): Promise<any | null> {
    try {
      // モックNET8ユーザー登録として処理
      console.log(`Checking NET8 user: ${net8UserId}`);
      return null; // 常に新規登録として処理
    } catch (error) {
      return null;
    }
  }

  /**
   * NET8側に新規ユーザー登録
   */
  private async registerNet8User(net8UserId: string, koreaUser: User): Promise<{ success: boolean; error?: string }> {
    try {
      // モックNET8ユーザー登録として処理
      console.log(`Registering NET8 user: ${net8UserId} for Korea user: ${koreaUser.loginId}`);
      
      // 実際の環境では/api/game/addPointsなどのAPIエンドポイントを使用
      const response = await fetch('/api/game/addPoints', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          userId: net8UserId,
          amount: 1000,
          reason: 'Initial registration bonus'
        })
      });

      if (response.ok) {
        return { success: true };
      } else {
        return { success: false, error: 'API call failed' };
      }
    } catch (error) {
      console.error('NET8 user registration failed:', error);
      return { 
        success: true, // モック環境では成功として処理
        error: undefined
      };
    }
  }

  /**
   * NET8ユーザープロフィール取得
   */
  private async getNet8UserProfile(net8UserId: string): Promise<any> {
    try {
      console.log(`Getting NET8 profile for: ${net8UserId}`);
      // モックプロフィール返却
      return {
        balance: 1000, // 初期ボーナス
        playHistory: [],
        createdAt: new Date(),
        lastLogin: new Date()
      };
    } catch (error) {
      return {
        balance: 1000,
        playHistory: [],
        createdAt: new Date(),
        lastLogin: new Date()
      };
    }
  }

  /**
   * 統合セッション情報をローカルに保存
   */
  private async saveUnifiedSession(unifiedUser: UnifiedUser): Promise<void> {
    if (typeof window !== 'undefined') {
      localStorage.setItem('unified_auth', JSON.stringify({
        ...unifiedUser,
        syncedAt: unifiedUser.syncedAt?.toISOString()
      }));
    }
  }

  /**
   * 統合セッション情報を取得
   */
  async getUnifiedSession(): Promise<UnifiedUser | null> {
    if (typeof window === 'undefined') return null;
    
    try {
      const stored = localStorage.getItem('unified_auth');
      if (!stored) return null;
      
      const parsed = JSON.parse(stored);
      return {
        ...parsed,
        syncedAt: parsed.syncedAt ? new Date(parsed.syncedAt) : undefined
      };
    } catch (error) {
      console.error('Failed to get unified session:', error);
      return null;
    }
  }

  /**
   * 統合ログアウト
   */
  async unifiedLogout(): Promise<void> {
    // ローカルセッションクリア
    if (typeof window !== 'undefined') {
      localStorage.removeItem('unified_auth');
    }
    
    // 必要に応じて韓国側とNET8側のログアウト処理
    // 韓国側は既存のlogout()関数を使用
  }

  /**
   * NET8ゲームセッション開始時の認証チェック
   */
  async validateNet8Session(): Promise<{ valid: boolean; net8UserId?: string }> {
    const unifiedUser = await this.getUnifiedSession();
    
    if (!unifiedUser || !unifiedUser.isNet8Synced) {
      return { valid: false };
    }

    return {
      valid: true,
      net8UserId: unifiedUser.net8UserId
    };
  }
}

// シングルトンインスタンス
export const unifiedAuthService = new UnifiedAuthService();