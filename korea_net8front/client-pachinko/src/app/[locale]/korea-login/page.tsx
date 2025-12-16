"use client";

import { useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import styles from "./korea-login.module.css";

export default function KoreaLoginPage() {
  const [loginId, setLoginId] = useState("testuser1");
  const [password, setPassword] = useState("password123");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const router = useRouter();

  // クッキーチェック
  useEffect(() => {
    if (document.cookie.includes("user.sid")) {
      setIsLoggedIn(true);
    }
  }, []);

  const handleLogin = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError("");
    
    try {
      const response = await fetch('/api/test/korea-only', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ loginId, password })
      });
      
      const data = await response.json();
      
      if (data.success) {
        setIsLoggedIn(true);
        // パチンコページへリダイレクト
        setTimeout(() => {
          router.push('/pachinko');
        }, 1000);
      } else {
        setError(data.message || 'ログイン失敗');
      }
    } catch (err) {
      setError('ネットワークエラー');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    document.cookie = "user.sid=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
    setIsLoggedIn(false);
    window.location.reload();
  };

  return (
    <div className="korea-login-container">
      <div className="login-card">
        <h1>🇰🇷 NET8 Korea ログイン</h1>
        
        {isLoggedIn ? (
          <div className="logged-in">
            <p>✅ ログイン済み</p>
            <div className="menu-links">
              <a href="/pachinko" className="menu-link">🎰 パチンコゲーム一覧</a>
              <a href="/pachinko/play/HOKUTO4GO" className="menu-link">🎮 北斗の拳4をプレイ</a>
            </div>
            <button onClick={handleLogout} className="logout-btn">
              ログアウト
            </button>
          </div>
        ) : (
          <form onSubmit={handleLogin}>
            <div className="form-group">
              <label>ログインID</label>
              <input
                type="text"
                value={loginId}
                onChange={(e) => setLoginId(e.target.value)}
                placeholder="testuser1"
                disabled={loading}
              />
            </div>
            
            <div className="form-group">
              <label>パスワード</label>
              <input
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="password123"
                disabled={loading}
              />
            </div>
            
            {error && <p className="error">{error}</p>}
            
            <button type="submit" disabled={loading} className="login-btn">
              {loading ? "ログイン中..." : "ログイン"}
            </button>
            
            <div className="test-info">
              <h3>テストアカウント:</h3>
              <p>ID: testuser1 / PW: password123</p>
              <p>ID: testuser2 / PW: password456</p>
            </div>
          </form>
        )}
      </div>
      
      <style jsx>{`
        .korea-login-container {
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
          padding: 20px;
        }
        
        .login-card {
          background: rgba(255, 255, 255, 0.95);
          padding: 40px;
          border-radius: 20px;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
          width: 100%;
          max-width: 450px;
        }
        
        h1 {
          text-align: center;
          color: #333;
          margin-bottom: 30px;
          font-size: 28px;
        }
        
        .form-group {
          margin-bottom: 20px;
        }
        
        label {
          display: block;
          margin-bottom: 8px;
          color: #555;
          font-weight: 500;
        }
        
        input {
          width: 100%;
          padding: 12px;
          border: 2px solid #e0e0e0;
          border-radius: 8px;
          font-size: 16px;
          transition: border-color 0.3s;
        }
        
        input:focus {
          outline: none;
          border-color: #667eea;
        }
        
        .login-btn, .logout-btn {
          width: 100%;
          padding: 14px;
          background: #667eea;
          color: white;
          border: none;
          border-radius: 8px;
          font-size: 16px;
          font-weight: bold;
          cursor: pointer;
          transition: background 0.3s;
        }
        
        .login-btn:hover, .logout-btn:hover {
          background: #5569d3;
        }
        
        .login-btn:disabled {
          background: #ccc;
          cursor: not-allowed;
        }
        
        .error {
          color: #f44336;
          text-align: center;
          margin: 10px 0;
        }
        
        .test-info {
          margin-top: 20px;
          padding: 15px;
          background: #f5f5f5;
          border-radius: 8px;
        }
        
        .test-info h3 {
          margin-bottom: 10px;
          color: #666;
          font-size: 14px;
        }
        
        .test-info p {
          margin: 5px 0;
          color: #777;
          font-size: 13px;
        }
        
        .logged-in {
          text-align: center;
        }
        
        .logged-in p {
          color: #4CAF50;
          font-size: 20px;
          margin-bottom: 20px;
        }
        
        .menu-links {
          margin: 20px 0;
        }
        
        .menu-link {
          display: block;
          padding: 15px;
          margin: 10px 0;
          background: #f0f0f0;
          border-radius: 8px;
          color: #333;
          text-decoration: none;
          transition: background 0.3s;
        }
        
        .menu-link:hover {
          background: #e0e0e0;
        }
      `}</style>
    </div>
  );
}