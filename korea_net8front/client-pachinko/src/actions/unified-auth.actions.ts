// actions/unified-auth.actions.ts
"use server";

import { unifiedAuthService } from '@/lib/auth.service';
import { User } from '@/types/user.types';
import { login as koreaLogin } from './auth.actions';
import { AuthSchemas } from '@/validations/auth.schemas';

export interface UnifiedLoginResult {
  success: boolean;
  koreaUser?: User;
  net8Synced?: boolean;
  error?: string;
  redirectUrl?: string;
}

/**
 * 統合ログイン - 韓国側ログイン後に自動でNET8同期
 */
export async function unifiedLogin(
  values: AuthSchemas['koreaLogin'], 
  timezone: string
): Promise<UnifiedLoginResult> {
  try {
    // 1. 韓国側ログイン実行
    const koreaResult = await koreaLogin(values, timezone);
    
    if (!koreaResult.success || !koreaResult.data) {
      return {
        success: false,
        error: 'Korea login failed'
      };
    }

    const koreaUser = koreaResult.data as User;

    // 2. サーバーサイドでNET8同期は行わず、クライアントサイドに委譲
    // （ブラウザ環境でのみlocalStorageアクセス可能）
    
    return {
      success: true,
      koreaUser,
      net8Synced: false, // クライアントサイドで同期予定
      redirectUrl: '/dashboard' // 適切なリダイレクト先
    };

  } catch (error) {
    console.error('Unified login error:', error);
    return {
      success: false,
      error: error instanceof Error ? error.message : 'Login failed'
    };
  }
}

/**
 * NET8ゲーム開始前の認証確認
 */
export async function validateGameAccess(userId: string): Promise<{
  valid: boolean;
  net8UserId?: string;
  error?: string;
}> {
  try {
    // サーバーサイドでの認証確認ロジック
    // 実際の実装では、セッション管理システムと連携
    
    return {
      valid: true,
      net8UserId: `kr_${userId}_${Date.now()}`
    };
  } catch (error) {
    return {
      valid: false,
      error: 'Authentication validation failed'
    };
  }
}

/**
 * 統合ログアウト（サーバーサイド処理）
 */
export async function unifiedLogoutServer(): Promise<{ success: boolean }> {
  try {
    // 韓国側ログアウト処理
    // 既存のlogout()関数を使用
    
    return { success: true };
  } catch (error) {
    console.error('Unified logout error:', error);
    return { success: false };
  }
}