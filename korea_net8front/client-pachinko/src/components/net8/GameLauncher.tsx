// components/net8/GameLauncher.tsx
'use client';

import { useState } from 'react';
import { useTranslations } from 'next-intl';
import { Button } from '@/components/ui/button';
import CardWrapper from '@/components/wrapper/cardWrapper';
// Removed unified auth - using direct Korean user authentication
import { useNET8Game } from '@/hooks/useNET8Game';
import { useUserStore } from '@/store/user.store';
import { toastSuccess, toastDanger } from '@/components/ui/sonner';

interface GameLauncherProps {
  modelId: string;
  modelName: string;
  category: 'pachinko' | 'slot';
  imageUrl?: string;
  requiredPoints?: number;
}

export default function GameLauncher({ 
  modelId, 
  modelName, 
  category, 
  imageUrl, 
  requiredPoints = 100 
}: GameLauncherProps) {
  const t = useTranslations('GAME_LAUNCHER');
  const [isLaunching, setIsLaunching] = useState(false);
  
  // Using direct Korean user authentication instead of unified auth
  
  const { startGame, loading: gameLoading, error: gameError } = useNET8Game();
  const user = useUserStore((state) => state.user);

  const handleGameLaunch = async () => {
    setIsLaunching(true);

    try {
      // 簡単な解決方法：韓国ユーザーがログイン済みならNET8ユーザーIDを生成して直接ゲーム開始
      if (!user) {
        toastDanger('ログインしてください。');
        setIsLaunching(false);
        return;
      }

      // NET8ユーザーID生成（韓国ユーザーIDベース）
      const net8UserId = `kr_${user.id}_net8`;
      console.log(`Using NET8 UserId: ${net8UserId} for Korea User: ${user.loginId}`);

      // ゲーム開始
      const gameResult = await startGame(net8UserId, modelId);
      
      if (gameResult) {
        toastSuccess(`${modelName}のゲームを開始しました！`);
        
        // ゲーム画面への遷移やモーダル表示など
        // 必要に応じてルーティング処理を追加
      }

    } catch (error) {
      console.error('Game launch error:', error);
      toastDanger(error instanceof Error ? error.message : 'ゲーム開始に失敗しました');
    } finally {
      setIsLaunching(false);
    }
  };

  const isLoading = isLaunching || gameLoading;

  return (
    <CardWrapper 
      title={modelName}
      description={`${category === 'pachinko' ? 'パチンコ' : 'スロット'} • ${requiredPoints}ポイント消費`}
      className="w-full max-w-md mx-auto space-y-4"
    >
      {imageUrl && (
        <div className="w-full h-48 rounded-lg overflow-hidden mb-4">
          <img 
            src={imageUrl} 
            alt={modelName}
            className="w-full h-full object-cover"
          />
        </div>
      )}
        {/* 韓国ユーザー認証ステータス */}
        <div className="space-y-2">
          <div className="flex items-center justify-between text-sm">
            <span>ログイン状態:</span>
            <span className={user ? 'text-green-600' : 'text-red-600'}>
              {user ? '✅ ログイン済み' : '❌ 未ログイン'}
            </span>
          </div>
          
          {user && (
            <div className="flex items-center justify-between text-sm">
              <span>ユーザー:</span>
              <span className="font-medium">{user.loginId}</span>
            </div>
          )}
          
          {user && (
            <div className="flex items-center justify-between text-sm">
              <span>ウォレット残高:</span>
              <span className="font-medium">
                ¥{user.wallets?.money || 0}
              </span>
            </div>
          )}
        </div>

        {/* エラー表示 */}
        {gameError && (
          <div className="text-sm text-red-600 bg-red-50 p-2 rounded">
            エラー: {gameError}
          </div>
        )}

        {/* ゲーム開始ボタン */}
        <Button
          onClick={handleGameLaunch}
          disabled={!user || isLoading}
          className="w-full"
          size="default"
        >
          {isLoading ? (
            <div className="flex items-center space-x-2">
              <div className="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
              <span>ゲーム準備中...</span>
            </div>
          ) : (
            `${modelName}を開始`
          )}
        </Button>

        {/* ログインしていない場合の案内 */}
        {!user && (
          <div className="text-sm text-gray-600 text-center">
            ゲームを開始するにはログインが必要です
          </div>
        )}
    </CardWrapper>
  );
}