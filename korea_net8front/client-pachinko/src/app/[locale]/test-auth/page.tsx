// app/[locale]/test-auth/page.tsx
'use client';

import { useState } from 'react';
import CardWrapper from '@/components/wrapper/cardWrapper';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import UnifiedLoginForm from '@/components/forms/auth/unifiedLoginForm';
import GameLauncher from '@/components/net8/GameLauncher';
import { useUnifiedAuth } from '@/hooks/useUnifiedAuth';
import { useUserStore } from '@/store/user.store';

export default function TestAuthPage() {
  const [showLogin, setShowLogin] = useState(true);
  const [activeTab, setActiveTab] = useState<'login' | 'register' | 'verify-email' | 'reset-password'>('login');
  
  const { 
    unifiedUser, 
    isNet8Ready, 
    loading, 
    error, 
    unifiedLogout,
    getNet8UserId 
  } = useUnifiedAuth();
  
  const user = useUserStore((state) => state.user);

  const handleLoginSuccess = () => {
    setShowLogin(false);
  };

  const handleLogout = async () => {
    await unifiedLogout();
    setShowLogin(true);
  };

  return (
    <div className="container mx-auto py-8 space-y-8">
      <div className="text-center">
        <h1 className="text-3xl font-bold mb-2">統合認証テスト</h1>
        <p className="text-gray-600">韓国API + NET8 SDK連携テスト</p>
      </div>

      {/* 認証状態表示 */}
      <CardWrapper title="認証状態" className="space-y-4">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <Label>韓国側ログイン:</Label>
              <p className={user ? 'text-green-600' : 'text-red-600'}>
                {user ? `✅ ${(user as { email?: string }).email || user.loginId}` : '❌ 未ログイン'}
              </p>
            </div>
            
            <div>
              <Label>NET8連携:</Label>
              <p className={isNet8Ready ? 'text-green-600' : 'text-orange-600'}>
                {isNet8Ready ? '✅ 連携済み' : '⚠️ 未連携'}
              </p>
            </div>
            
            <div>
              <Label>NET8ユーザーID:</Label>
              <p className="font-mono text-xs">
                {getNet8UserId() || 'なし'}
              </p>
            </div>
            
            <div>
              <Label>統合ユーザー:</Label>
              <p className={unifiedUser ? 'text-green-600' : 'text-gray-400'}>
                {unifiedUser ? '✅ 作成済み' : '未作成'}
              </p>
            </div>
          </div>

          {error && (
            <div className="text-red-600 bg-red-50 p-2 rounded text-sm">
              エラー: {error}
            </div>
          )}

          {unifiedUser && (
            <div className="mt-4 p-4 bg-gray-50 rounded">
              <Label>統合ユーザー詳細:</Label>
              <pre className="text-xs mt-2 overflow-auto">
                {JSON.stringify({
                  net8UserId: unifiedUser.net8UserId,
                  isNet8Synced: unifiedUser.isNet8Synced,
                  syncedAt: unifiedUser.syncedAt,
                  net8Balance: unifiedUser.net8UserProfile?.balance || 0
                }, null, 2)}
              </pre>
            </div>
          )}
      </CardWrapper>

      {/* ログインフォーム */}
      {showLogin && (
        <CardWrapper title="統合ログイン">
          <UnifiedLoginForm 
            setActiveTab={setActiveTab}
            onLoginSuccess={handleLoginSuccess}
          />
        </CardWrapper>
      )}

      {/* ゲームランチャー */}
      {!showLogin && (
        <div className="space-y-6">
          <div className="flex justify-between items-center">
            <h2 className="text-2xl font-bold">NET8ゲームテスト</h2>
            <Button onClick={handleLogout} variant="default">
              ログアウト
            </Button>
          </div>
          
          <div className="grid md:grid-cols-2 gap-6">
            <GameLauncher
              modelId="HOKUTO4GO"
              modelName="CR北斗の拳4 覇王"
              category="pachinko"
              requiredPoints={100}
              imageUrl="/images/games/hokuto4go.jpg"
            />
            
            <GameLauncher
              modelId="ZYUGOKUSAN"
              modelName="パチスロ 十字架3"
              category="slot" 
              requiredPoints={50}
              imageUrl="/images/games/zyugokusan.jpg"
            />
          </div>
        </div>
      )}

      {/* モックAPIテスト */}
      <CardWrapper title="モック韓国APIテスト" className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <Button 
              variant="default" 
              onClick={async () => {
                try {
                  const response = await fetch('/api/debug/mock-korea');
                  const data = await response.json();
                  console.log('Mock API Debug:', data);
                  alert('デバッグ情報をコンソールに出力しました');
                } catch (error) {
                  console.error('Debug API Error:', error);
                  alert('デバッグAPIエラー');
                }
              }}
            >
              モックAPI状態確認
            </Button>
            
            <Button 
              variant="default"
              onClick={async () => {
                try {
                  const response = await fetch('/api/v1/account/sign-in/check');
                  const data = await response.json();
                  console.log('Auth Check:', data);
                  alert(`認証状態: ${data.authenticated ? 'ログイン中' : '未ログイン'}`);
                } catch (error) {
                  console.error('Auth Check Error:', error);
                  alert('認証確認エラー');
                }
              }}
            >
              認証状態確認
            </Button>
            
            <Button 
              variant="default"
              onClick={() => {
                const testUsers = [
                  'testuser1 / password123',
                  'demouser / demo123', 
                  'admin / admin123'
                ];
                alert('テストユーザー:\n' + testUsers.join('\n'));
              }}
            >
              テストユーザー表示
            </Button>
          </div>
          
          <div className="bg-gray-50 p-4 rounded text-sm">
            <h4 className="font-medium mb-2">テスト手順:</h4>
            <ol className="list-decimal list-inside space-y-1">
              <li>上記のテストユーザーでログインを試行</li>
              <li>統合認証でNET8連携が自動実行されることを確認</li>
              <li>ゲームランチャーでNET8ゲーム開始をテスト</li>
              <li>ログアウト後、セッションが正常にクリアされることを確認</li>
            </ol>
          </div>
      </CardWrapper>

      {/* デバッグ情報 */}
      <CardWrapper title="デバッグ情報">
          <div className="space-y-2 text-xs">
            <div><strong>Loading:</strong> {loading ? 'true' : 'false'}</div>
            <div><strong>Korea User:</strong> <pre className="mt-1 p-2 bg-gray-100 rounded overflow-auto">{JSON.stringify(user, null, 2)}</pre></div>
            <div><strong>Unified User:</strong> <pre className="mt-1 p-2 bg-gray-100 rounded overflow-auto">{JSON.stringify(unifiedUser, null, 2)}</pre></div>
          </div>
      </CardWrapper>
    </div>
  );
}