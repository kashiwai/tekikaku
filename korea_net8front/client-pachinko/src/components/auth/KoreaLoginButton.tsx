"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogTrigger 
} from "@/components/ui/dialog";
import { useRouter } from "next/navigation";
import { toastSuccess, toastDanger } from "@/components/ui/sonner";

export default function KoreaLoginButton() {
  const [isOpen, setIsOpen] = useState(false);
  const [isLoading, setIsLoading] = useState(false);
  const [loginId, setLoginId] = useState("");
  const [password, setPassword] = useState("");
  const router = useRouter();

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!loginId.trim() || !password.trim()) {
      toastDanger("ログインIDとパスワードを入力してください");
      return;
    }

    setIsLoading(true);
    
    try {
      // AbortControllerを使用してタイムアウトを設定
      const controller = new AbortController();
      const timeoutId = setTimeout(() => controller.abort(), 5000); // 5秒のタイムアウト
      
      const response = await fetch('/api/test/korea-only', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          loginId: loginId.trim(),
          password: password.trim()
        }),
        signal: controller.signal
      });
      
      clearTimeout(timeoutId);

      const data = await response.json();

      if (data.success) {
        toastSuccess(`ログイン成功: ${data.userId}`);
        setIsOpen(false);
        setLoginId("");
        setPassword("");
        
        // ページをリロードして認証状態を更新
        window.location.reload();
      } else {
        toastDanger(data.message || "ログインに失敗しました");
      }
    } catch (error) {
      console.error('Login error:', error);
      toastDanger("ログイン中にエラーが発生しました");
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={setIsOpen}>
      <DialogTrigger asChild>
        <Button variant="default" className="mr-2">
          🇰🇷 Korean Login
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-[425px]">
        <DialogHeader>
          <DialogTitle>韓国アカウントログイン</DialogTitle>
        </DialogHeader>
        
        <form onSubmit={handleLogin} className="space-y-4">
          <div className="space-y-2">
            <Label htmlFor="loginId">ログインID</Label>
            <Input
              id="loginId"
              type="text"
              value={loginId}
              onChange={(e) => setLoginId(e.target.value)}
              placeholder="testuser1"
              disabled={isLoading}
            />
          </div>
          
          <div className="space-y-2">
            <Label htmlFor="password">パスワード</Label>
            <Input
              id="password"
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              placeholder="password123"
              disabled={isLoading}
            />
          </div>
          
          <div className="bg-muted p-3 rounded text-sm">
            <p className="font-semibold mb-1">テストアカウント:</p>
            <p>ID: testuser1 / PW: password123</p>
            <p>ID: testuser2 / PW: password456</p>
          </div>
          
          <div className="flex justify-end gap-2">
            <Button
              type="button"
              variant="default"
              onClick={() => setIsOpen(false)}
              disabled={isLoading}
            >
              キャンセル
            </Button>
            <Button type="submit" disabled={isLoading}>
              {isLoading ? "ログイン中..." : "ログイン"}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  );
}