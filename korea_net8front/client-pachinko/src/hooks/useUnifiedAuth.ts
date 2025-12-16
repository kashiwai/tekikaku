// hooks/useUnifiedAuth.ts
'use client';

import { useState, useEffect, useCallback } from 'react';
import { User } from '@/types/user.types';
import { unifiedAuthService, UnifiedUser, AuthSyncResult } from '@/lib/auth.service';
import { useNET8Game } from './useNET8Game';

export function useUnifiedAuth() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [unifiedUser, setUnifiedUser] = useState<UnifiedUser | null>(null);
  const [isNet8Ready, setIsNet8Ready] = useState(false);
  
  const { session } = useNET8Game();

  // 統合セッション初期化
  const initializeSession = useCallback(async () => {
    try {
      const existingSession = await unifiedAuthService.getUnifiedSession();
      if (existingSession) {
        setUnifiedUser(existingSession);
        setIsNet8Ready(existingSession.isNet8Synced);
      }
    } catch (err) {
      console.error('Failed to initialize unified session:', err);
    }
  }, []);

  // コンポーネントマウント時にセッション確認
  useEffect(() => {
    initializeSession();
  }, [initializeSession]);

  // 韓国側ログイン後の自動NET8同期
  const syncKoreaUser = useCallback(async (koreaUser: User): Promise<AuthSyncResult> => {
    setLoading(true);
    setError(null);

    try {
      const syncResult = await unifiedAuthService.syncWithNet8(koreaUser);
      
      if (syncResult.success && syncResult.unifiedUser) {
        setUnifiedUser(syncResult.unifiedUser);
        setIsNet8Ready(true);
        
        // 同期成功をローカルストレージにも保存
        localStorage.setItem('korea_net8_sync', 'completed');
      } else {
        setError(syncResult.error || 'Sync failed');
      }

      return syncResult;
    } catch (err) {
      const errorMessage = err instanceof Error ? err.message : 'Unknown sync error';
      setError(errorMessage);
      return {
        success: false,
        error: errorMessage
      };
    } finally {
      setLoading(false);
    }
  }, []);

  // NET8ゲーム開始前の認証検証
  const validateNet8Access = useCallback(async (): Promise<boolean> => {
    try {
      const validation = await unifiedAuthService.validateNet8Session();
      return validation.valid;
    } catch (err) {
      console.error('NET8 access validation failed:', err);
      return false;
    }
  }, []);

  // NET8ユーザーIDを取得
  const getNet8UserId = useCallback((): string | null => {
    return unifiedUser?.net8UserId || null;
  }, [unifiedUser]);

  // 統合ログアウト
  const unifiedLogout = useCallback(async () => {
    try {
      await unifiedAuthService.unifiedLogout();
      setUnifiedUser(null);
      setIsNet8Ready(false);
      localStorage.removeItem('korea_net8_sync');
    } catch (err) {
      console.error('Unified logout failed:', err);
    }
  }, []);

  // 韓国側認証状態の確認
  const checkKoreaAuth = useCallback(async (): Promise<boolean> => {
    try {
      // モック韓国側APIのセッション確認
      const response = await fetch('/api/v1/account/sign-in/check', {
        method: 'GET',
        credentials: 'include'
      });
      
      if (response.ok) {
        const data = await response.json();
        return data.success && data.authenticated;
      }
      
      return false;
    } catch (err) {
      console.error('Korea auth check failed:', err);
      return false;
    }
  }, []);

  // NET8ゲーム開始時の自動認証フロー
  const startNet8GameWithAuth = useCallback(async (modelId: string) => {
    const net8UserId = getNet8UserId();
    
    if (!net8UserId) {
      throw new Error('NET8 user not synced. Please login first.');
    }

    const isValid = await validateNet8Access();
    if (!isValid) {
      throw new Error('NET8 session expired. Please re-login.');
    }

    // NET8Game hookのstartGameを使用
    return { net8UserId, modelId };
  }, [getNet8UserId, validateNet8Access]);

  return {
    // 状態
    loading,
    error,
    unifiedUser,
    isNet8Ready,
    
    // 認証関連
    syncKoreaUser,
    validateNet8Access,
    getNet8UserId,
    unifiedLogout,
    checkKoreaAuth,
    
    // ゲーム関連
    startNet8GameWithAuth,
    
    // ユーティリティ
    initializeSession
  };
}