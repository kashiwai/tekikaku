"use client";

import { useState } from "react";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";

export default function KoreaTestPage() {
  const [loginId, setLoginId] = useState("testuser1");
  const [password, setPassword] = useState("password123");
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState<any>(null);

  const handleLogin = async () => {
    setLoading(true);
    setResult(null);
    
    try {
      const response = await fetch('/api/test/korea-only', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ loginId, password })
      });
      
      const data = await response.json();
      setResult(data);
      
      if (data.success) {
        // ページをリロードして認証状態を更新
        setTimeout(() => {
          window.location.href = "/korea-test";
        }, 1500);
      }
    } catch (error) {
      setResult({ error: error instanceof Error ? error.message : 'Unknown error' });
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    document.cookie = "user.sid=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    window.location.reload();
  };

  return (
    <div className="min-h-screen bg-gray-900 text-white p-8">
      <div className="max-w-2xl mx-auto">
        <h1 className="text-4xl font-bold mb-8">韓国NET8統合テスト</h1>
        
        <Card className="bg-gray-800 border-gray-700">
          <CardHeader>
            <CardTitle>韓国ログインテスト</CardTitle>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <label className="block text-sm mb-2">ログインID</label>
              <Input
                value={loginId}
                onChange={(e) => setLoginId(e.target.value)}
                placeholder="testuser1"
                className="bg-gray-700 border-gray-600"
              />
            </div>
            
            <div>
              <label className="block text-sm mb-2">パスワード</label>
              <Input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="password123"
                className="bg-gray-700 border-gray-600"
              />
            </div>
            
            <div className="flex gap-2">
              <Button 
                onClick={handleLogin}
                disabled={loading}
                className="bg-blue-600 hover:bg-blue-700"
              >
                {loading ? "ログイン中..." : "ログイン"}
              </Button>
              
              <Button
                onClick={handleLogout}
                variant="default"
                className="border-gray-600"
              >
                ログアウト
              </Button>
            </div>
            
            {result && (
              <div className="mt-4 p-4 bg-gray-900 rounded">
                <pre className="text-xs overflow-auto">
                  {JSON.stringify(result, null, 2)}
                </pre>
              </div>
            )}
          </CardContent>
        </Card>
        
        <Card className="bg-gray-800 border-gray-700 mt-6">
          <CardHeader>
            <CardTitle>ゲームアクセス</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            <a href="/pachinko" className="block p-3 bg-gray-700 rounded hover:bg-gray-600">
              パチンコゲーム一覧 →
            </a>
            <a href="/pachinko/play/HOKUTO4GO" className="block p-3 bg-gray-700 rounded hover:bg-gray-600">
              北斗の拳4をプレイ →
            </a>
          </CardContent>
        </Card>
        
        <div className="mt-6 p-4 bg-gray-800 rounded">
          <h3 className="font-bold mb-2">テストアカウント:</h3>
          <p>ID: testuser1 / PW: password123</p>
          <p>ID: testuser2 / PW: password456</p>
        </div>
      </div>
    </div>
  );
}