# NET8 SDK v1.1.0 完全実装マニュアル

**最終更新**: 2025-11-23
**対象バージョン**: SDK v1.1.0
**対象者**: パートナー企業の開発者

---

## 📋 目次

1. [概要](#概要)
2. [認証とセキュリティ](#認証とセキュリティ)
3. [クイックスタート](#クイックスタート)
4. [API仕様](#api仕様)
5. [実装パターン](#実装パターン)
6. [エラーハンドリング](#エラーハンドリング)
7. [テスト方法](#テスト方法)
8. [本番環境デプロイ](#本番環境デプロイ)
9. [FAQ](#faq)

---

## 概要

### NET8 SDKとは

NET8 SDKは、パートナー企業がNET8のパチンコ・スロット遊技システムを自社サービスに統合するためのAPIです。

### 主な機能

- ✅ **ユーザー自動連携**: パートナー側のユーザーIDを自動的にNET8側のユーザーと紐づけ
- ✅ **ポイント管理**: ゲームプレイの課金・払い出しを完全管理
- ✅ **トランザクション記録**: すべての取引履歴を保存
- ✅ **セッション管理**: ゲーム開始から終了までの状態管理
- ✅ **リアルタイム通信**: WebRTCによる映像配信・操作

### システム構成

```
パートナー側           NET8側
┌─────────────┐       ┌─────────────┐
│ Your App    │       │ NET8 API    │
│             │←─────→│             │
│ - API Key   │ HTTPS │ - 認証      │
│ - User ID   │       │ - ポイント  │
│             │       │ - セッション│
└─────────────┘       └─────────────┘
```

---

## 認証とセキュリティ

### API Key取得

NET8から発行される2種類のAPI Keyがあります：

| 環境 | API Key形式 | 用途 |
|------|------------|------|
| **テスト環境** | `pk_test_*` または `pk_demo_*` | 開発・検証用 |
| **本番環境** | `pk_live_*` | 本番サービス用 |

### 認証方式

すべてのAPIリクエストに **Bearer Token認証** を使用します：

```http
Authorization: Bearer pk_demo_12345
```

### セキュリティベストプラクティス

#### ✅ 推奨

```javascript
// 環境変数でAPI Keyを管理
const API_KEY = process.env.NET8_API_KEY;

// HTTPS通信必須
const API_BASE = 'https://mgg-webservice-production.up.railway.app';
```

#### ❌ 禁止

```javascript
// ハードコーディング禁止
const API_KEY = 'pk_live_abcdef123456'; // ❌

// フロントエンドに露出禁止
<script>
  const apiKey = 'pk_live_abcdef123456'; // ❌
</script>
```

### API Key管理

```bash
# .env ファイル
NET8_API_KEY=pk_demo_12345
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
```

```javascript
// 環境変数読み込み
require('dotenv').config();

const NET8_CONFIG = {
  apiKey: process.env.NET8_API_KEY,
  apiBase: process.env.NET8_API_BASE
};
```

---

## クイックスタート

### 1. 必要な情報

- ✅ NET8 API Key（テスト用: `pk_demo_12345`）
- ✅ パートナー側のユーザーID（例: `user_12345`）
- ✅ 遊技したい機種コード（例: `HOKUTO4GO`）

### 2. 最小実装例

```javascript
const axios = require('axios');

const NET8_API_KEY = 'pk_demo_12345';
const API_BASE = 'https://mgg-webservice-production.up.railway.app';

// ゲーム開始
async function startGame(userId, modelId) {
  const response = await axios.post(
    `${API_BASE}/api/v1/game_start.php`,
    {
      userId: userId,      // パートナー側のユーザーID
      modelId: modelId     // 機種コード
    },
    {
      headers: {
        'Authorization': `Bearer ${NET8_API_KEY}`,
        'Content-Type': 'application/json'
      }
    }
  );

  return response.data;
}

// ゲーム終了
async function endGame(sessionId, result, pointsWon) {
  const response = await axios.post(
    `${API_BASE}/api/v1/game_end.php`,
    {
      sessionId: sessionId,
      result: result,      // 'win' | 'lose' | 'draw'
      pointsWon: pointsWon
    },
    {
      headers: {
        'Authorization': `Bearer ${NET8_API_KEY}`,
        'Content-Type': 'application/json'
      }
    }
  );

  return response.data;
}

// 実行例
(async () => {
  try {
    // 1. ゲーム開始
    const gameStart = await startGame('user_12345', 'HOKUTO4GO');
    console.log('セッション作成:', gameStart.sessionId);
    console.log('消費ポイント:', gameStart.pointsConsumed);

    // 2. ゲームプレイ（ここでWebRTC接続などを行う）
    // ...

    // 3. ゲーム終了
    const gameEnd = await endGame(gameStart.sessionId, 'win', 500);
    console.log('獲得ポイント:', gameEnd.pointsWon);
    console.log('新しい残高:', gameEnd.newBalance);

  } catch (error) {
    console.error('エラー:', error.response?.data || error.message);
  }
})();
```

### 3. 実行結果

```json
// game_start レスポンス
{
  "success": true,
  "sessionId": "gs_6922713d44833_1763864893",
  "pointsConsumed": 100,
  "points": {
    "consumed": 100,
    "balance": "9900",
    "balanceBefore": 10000
  },
  "playUrl": "/data/play_v2/index.php?NO=9999"
}

// game_end レスポンス
{
  "success": true,
  "sessionId": "gs_6922713d44833_1763864893",
  "result": "win",
  "pointsWon": 500,
  "netProfit": 400,
  "newBalance": 10400
}
```

---

## API仕様

### エンドポイント一覧

| API | メソッド | エンドポイント | 用途 |
|-----|---------|---------------|------|
| ゲーム開始 | POST | `/api/v1/game_start.php` | セッション作成・ポイント消費 |
| ゲーム終了 | POST | `/api/v1/game_end.php` | セッション終了・ポイント払い出し |
| ポイント追加 | POST | `/api/v1/add_points.php` | ユーザーポイント追加 |
| プレイ履歴 | GET | `/api/v1/play_history.php` | ゲーム履歴取得 |
| 推奨機種 | GET | `/api/v1/recommended_models.php` | おすすめ機種一覧 |

---

### 1. game_start API

**エンドポイント**: `POST /api/v1/game_start.php`

#### リクエスト

```json
{
  "userId": "user_12345",       // 必須: パートナー側のユーザーID
  "modelId": "HOKUTO4GO"        // 必須: 機種コード
}
```

#### レスポンス（成功）

```json
{
  "success": true,
  "environment": "test",          // "test" | "production"
  "sessionId": "gs_xxx",          // セッションID（game_endで使用）
  "machineNo": 9999,              // 割り当てられた台番号
  "model": {
    "id": "HOKUTO4GO",
    "name": "北斗の拳",
    "category": "pachinko"
  },
  "signaling": {                  // WebRTC接続情報
    "signalingId": "sig_xxx",
    "host": "signaling.net8.com",
    "port": 443,
    "secure": true
  },
  "camera": {                     // カメラ情報
    "cameraNo": 123,
    "streamUrl": "wss://..."
  },
  "playUrl": "/data/play_v2/index.php?NO=9999",
  "points": {
    "consumed": 100,              // 消費ポイント
    "balance": "9900",            // 残高
    "balanceBefore": 10000        // 消費前残高
  },
  "pointsConsumed": 100
}
```

#### エラーレスポンス

```json
// 認証エラー
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}

// 残高不足
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Not enough points to start game",
  "required": 100,
  "current": 50
}

// 機種が見つからない
{
  "error": "MODEL_NOT_FOUND",
  "message": "Specified model does not exist"
}
```

---

### 2. game_end API

**エンドポイント**: `POST /api/v1/game_end.php`

#### リクエスト

```json
{
  "sessionId": "gs_xxx",          // 必須: game_startで取得したセッションID
  "result": "win",                // 必須: "win" | "lose" | "draw"
  "pointsWon": 500                // 必須: 獲得ポイント（負けの場合は0）
}
```

#### レスポンス（成功）

```json
{
  "success": true,
  "sessionId": "gs_xxx",
  "result": "win",
  "pointsConsumed": "100",        // 消費ポイント
  "pointsWon": 500,               // 獲得ポイント
  "netProfit": 400,               // 純利益（獲得 - 消費）
  "playDuration": 32430,          // プレイ時間（秒）
  "endedAt": "2025-11-23 11:28:44",
  "newBalance": 10400,            // 新しい残高
  "transaction": {
    "id": "txn_xxx",              // トランザクションID
    "amount": 500,
    "balanceBefore": "9900",
    "balanceAfter": 10400
  }
}
```

#### エラーレスポンス

```json
// セッションが見つからない
{
  "error": "SESSION_NOT_FOUND",
  "message": "Game session not found or already ended"
}

// セッションが既に終了
{
  "error": "SESSION_ALREADY_ENDED",
  "message": "This session has already been completed"
}
```

---

### 3. add_points API

**エンドポイント**: `POST /api/v1/add_points.php`

#### リクエスト

```json
{
  "userId": "user_12345",         // 必須: パートナー側のユーザーID
  "amount": 1000,                 // 必須: 追加ポイント数
  "reason": "purchase"            // オプション: 追加理由
}
```

#### レスポンス（成功）

```json
{
  "success": true,
  "userId": "user_12345",
  "amountAdded": 1000,
  "balanceBefore": 10400,
  "balanceAfter": 11400,
  "transaction": {
    "id": "txn_xxx",
    "timestamp": "2025-11-23T11:30:00Z"
  }
}
```

---

## 実装パターン

### パターン1: シンプルな統合

```javascript
class NET8GameClient {
  constructor(apiKey) {
    this.apiKey = apiKey;
    this.baseUrl = 'https://mgg-webservice-production.up.railway.app';
  }

  async startGame(userId, modelId) {
    const response = await fetch(`${this.baseUrl}/api/v1/game_start.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ userId, modelId })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${await response.text()}`);
    }

    return await response.json();
  }

  async endGame(sessionId, result, pointsWon) {
    const response = await fetch(`${this.baseUrl}/api/v1/game_end.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${this.apiKey}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ sessionId, result, pointsWon })
    });

    if (!response.ok) {
      throw new Error(`HTTP ${response.status}: ${await response.text()}`);
    }

    return await response.json();
  }
}

// 使用例
const client = new NET8GameClient(process.env.NET8_API_KEY);

const gameSession = await client.startGame('user_123', 'HOKUTO4GO');
// ... ゲームプレイ ...
const gameResult = await client.endGame(gameSession.sessionId, 'win', 500);
```

---

### パターン2: React統合

```typescript
// hooks/useNET8Game.ts
import { useState } from 'react';
import axios from 'axios';

interface GameSession {
  sessionId: string;
  pointsConsumed: number;
  balance: string;
}

export function useNET8Game() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [session, setSession] = useState<GameSession | null>(null);

  const startGame = async (userId: string, modelId: string) => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(
        `${process.env.REACT_APP_NET8_API}/api/v1/game_start.php`,
        { userId, modelId },
        {
          headers: {
            'Authorization': `Bearer ${process.env.REACT_APP_NET8_API_KEY}`
          }
        }
      );

      setSession(response.data);
      return response.data;
    } catch (err: any) {
      setError(err.response?.data?.message || 'ゲーム開始に失敗しました');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  const endGame = async (result: 'win' | 'lose' | 'draw', pointsWon: number) => {
    if (!session) throw new Error('セッションがありません');

    setLoading(true);
    setError(null);

    try {
      const response = await axios.post(
        `${process.env.REACT_APP_NET8_API}/api/v1/game_end.php`,
        {
          sessionId: session.sessionId,
          result,
          pointsWon
        },
        {
          headers: {
            'Authorization': `Bearer ${process.env.REACT_APP_NET8_API_KEY}`
          }
        }
      );

      setSession(null);
      return response.data;
    } catch (err: any) {
      setError(err.response?.data?.message || 'ゲーム終了に失敗しました');
      throw err;
    } finally {
      setLoading(false);
    }
  };

  return {
    loading,
    error,
    session,
    startGame,
    endGame
  };
}

// components/GameScreen.tsx
import { useNET8Game } from '../hooks/useNET8Game';

export function GameScreen({ userId }: { userId: string }) {
  const { loading, error, session, startGame, endGame } = useNET8Game();

  const handleStartGame = async () => {
    await startGame(userId, 'HOKUTO4GO');
  };

  const handleEndGame = async (result: 'win' | 'lose', points: number) => {
    await endGame(result, points);
  };

  return (
    <div>
      {error && <div className="error">{error}</div>}

      {!session ? (
        <button onClick={handleStartGame} disabled={loading}>
          ゲーム開始
        </button>
      ) : (
        <div>
          <p>セッションID: {session.sessionId}</p>
          <button onClick={() => handleEndGame('win', 500)}>
            勝利で終了
          </button>
        </div>
      )}
    </div>
  );
}
```

---

### パターン3: Node.js バックエンド統合

```typescript
// services/net8.service.ts
import axios, { AxiosInstance } from 'axios';

export class NET8Service {
  private client: AxiosInstance;

  constructor(apiKey: string, baseUrl: string) {
    this.client = axios.create({
      baseURL: baseUrl,
      headers: {
        'Authorization': `Bearer ${apiKey}`,
        'Content-Type': 'application/json'
      },
      timeout: 30000
    });

    // リクエストログ
    this.client.interceptors.request.use(config => {
      console.log(`[NET8] ${config.method?.toUpperCase()} ${config.url}`);
      return config;
    });

    // エラーハンドリング
    this.client.interceptors.response.use(
      response => response,
      error => {
        console.error('[NET8] Error:', error.response?.data || error.message);
        throw error;
      }
    );
  }

  async startGame(userId: string, modelId: string) {
    const { data } = await this.client.post('/api/v1/game_start.php', {
      userId,
      modelId
    });
    return data;
  }

  async endGame(sessionId: string, result: 'win' | 'lose' | 'draw', pointsWon: number) {
    const { data } = await this.client.post('/api/v1/game_end.php', {
      sessionId,
      result,
      pointsWon
    });
    return data;
  }

  async addPoints(userId: string, amount: number, reason?: string) {
    const { data } = await this.client.post('/api/v1/add_points.php', {
      userId,
      amount,
      reason
    });
    return data;
  }

  async getPlayHistory(userId: string, limit: number = 10) {
    const { data } = await this.client.get('/api/v1/play_history.php', {
      params: { userId, limit }
    });
    return data;
  }
}

// app.ts
import express from 'express';
import { NET8Service } from './services/net8.service';

const app = express();
const net8 = new NET8Service(
  process.env.NET8_API_KEY!,
  process.env.NET8_API_BASE!
);

app.post('/game/start', async (req, res) => {
  try {
    const { userId, modelId } = req.body;
    const result = await net8.startGame(userId, modelId);
    res.json(result);
  } catch (error: any) {
    res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message
    });
  }
});

app.post('/game/end', async (req, res) => {
  try {
    const { sessionId, result, pointsWon } = req.body;
    const gameResult = await net8.endGame(sessionId, result, pointsWon);
    res.json(gameResult);
  } catch (error: any) {
    res.status(error.response?.status || 500).json({
      error: error.response?.data || error.message
    });
  }
});

app.listen(3000, () => {
  console.log('Server running on port 3000');
});
```

---

## エラーハンドリング

### 標準エラー形式

```json
{
  "error": "ERROR_CODE",
  "message": "Human readable error message",
  "details": {}  // オプション
}
```

### エラーコード一覧

| エラーコード | HTTPステータス | 説明 | 対処法 |
|------------|--------------|------|--------|
| `INVALID_API_KEY` | 401 | API Keyが無効 | API Keyを確認 |
| `INSUFFICIENT_BALANCE` | 400 | ポイント不足 | ポイントを追加 |
| `MODEL_NOT_FOUND` | 404 | 機種が見つからない | modelIdを確認 |
| `SESSION_NOT_FOUND` | 404 | セッションが見つからない | sessionIdを確認 |
| `SESSION_ALREADY_ENDED` | 400 | セッション既に終了 | 新しいセッションを開始 |
| `TRANSACTION_FAILED` | 500 | トランザクション失敗 | リトライまたはサポート連絡 |

### エラーハンドリング実装例

```typescript
async function handleGameStart(userId: string, modelId: string) {
  try {
    const result = await net8.startGame(userId, modelId);
    return result;
  } catch (error: any) {
    const errorData = error.response?.data;

    switch (errorData?.error) {
      case 'INVALID_API_KEY':
        // API Key設定を確認
        console.error('API Key設定エラー。環境変数を確認してください。');
        break;

      case 'INSUFFICIENT_BALANCE':
        // ポイント追加を促す
        console.error(`ポイント不足: 必要=${errorData.required}, 現在=${errorData.current}`);
        // ユーザーにポイント購入画面を表示
        break;

      case 'MODEL_NOT_FOUND':
        // 機種リストを再取得
        console.error('指定された機種が見つかりません。機種リストを更新してください。');
        break;

      default:
        // 一般的なエラー
        console.error('ゲーム開始エラー:', errorData?.message || error.message);
    }

    throw error;
  }
}
```

---

## テスト方法

### テスト環境

- **API Base URL**: `https://mgg-webservice-production.up.railway.app`
- **テストAPI Key**: `pk_demo_12345`
- **テストユーザーID**: 任意の文字列（例: `test_user_001`）

### cURLでのテスト

```bash
# 1. ゲーム開始
curl -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"userId": "test_user_001", "modelId": "HOKUTO4GO"}'

# レスポンスからsessionIdを取得
# → "sessionId": "gs_xxx..."

# 2. ゲーム終了
curl -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_end.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"sessionId": "gs_xxx...", "result": "win", "pointsWon": 500}'
```

### 統合テスト例（Jest）

```typescript
// __tests__/net8.integration.test.ts
import { NET8Service } from '../services/net8.service';

describe('NET8 SDK Integration Tests', () => {
  let net8: NET8Service;
  const testUserId = `test_user_${Date.now()}`;

  beforeAll(() => {
    net8 = new NET8Service(
      process.env.NET8_API_KEY!,
      process.env.NET8_API_BASE!
    );
  });

  it('should complete full game flow', async () => {
    // 1. ゲーム開始
    const gameStart = await net8.startGame(testUserId, 'HOKUTO4GO');

    expect(gameStart.success).toBe(true);
    expect(gameStart.sessionId).toBeDefined();
    expect(gameStart.pointsConsumed).toBeGreaterThan(0);

    // 2. ゲーム終了
    const gameEnd = await net8.endGame(gameStart.sessionId, 'win', 500);

    expect(gameEnd.success).toBe(true);
    expect(gameEnd.pointsWon).toBe(500);
    expect(gameEnd.newBalance).toBeGreaterThan(0);
  }, 30000);

  it('should handle insufficient balance error', async () => {
    // ポイントを使い果たしたユーザーでテスト
    await expect(
      net8.startGame('broke_user', 'HOKUTO4GO')
    ).rejects.toThrow();
  });
});
```

---

## 本番環境デプロイ

### チェックリスト

- [ ] 本番API Key取得済み（`pk_live_*`）
- [ ] 環境変数設定済み
- [ ] HTTPS通信確認
- [ ] エラーハンドリング実装
- [ ] ログ記録実装
- [ ] リトライロジック実装
- [ ] タイムアウト設定

### 環境変数設定

```bash
# 本番環境 .env
NET8_API_KEY=pk_live_your_production_key
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
NODE_ENV=production
```

### 本番環境での注意事項

1. **API Keyの保護**
   - 環境変数で管理
   - コードにハードコーディングしない
   - Gitにコミットしない（.gitignoreに追加）

2. **エラーログの記録**
   ```typescript
   try {
     await net8.startGame(userId, modelId);
   } catch (error) {
     // ログサービスに記録（Sentry, CloudWatch等）
     logger.error('NET8 game start failed', {
       userId,
       modelId,
       error: error.message,
       timestamp: new Date()
     });
   }
   ```

3. **リトライロジック**
   ```typescript
   async function startGameWithRetry(userId: string, modelId: string, maxRetries = 3) {
     for (let i = 0; i < maxRetries; i++) {
       try {
         return await net8.startGame(userId, modelId);
       } catch (error) {
         if (i === maxRetries - 1) throw error;
         await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
       }
     }
   }
   ```

---

## FAQ

### Q1: ユーザーの紐づけはどうなっていますか？

**A**: パートナー側のユーザーIDを送信すると、NET8側で自動的にユーザーアカウントが作成・紐づけられます。

```
パートナー側: user_12345
     ↓
NET8側: sdk_partner_user_12345@net8.local (自動生成)
     ↓
mst_member.member_no = 16 (自動割り当て)
```

### Q2: ポイントの単位は何ですか？

**A**: 1ポイント = 1円相当です。ゲーム開始時に消費されるポイントは機種によって異なります。

### Q3: セッションの有効期限はありますか？

**A**: セッションは無期限ですが、適切にgame_endを呼び出してセッションを終了してください。

### Q4: 同じユーザーが複数のセッションを持てますか？

**A**: はい、可能です。ただし、各セッションは独立して管理されます。

### Q5: テスト環境と本番環境の違いは？

**A**:
- テスト環境: モック機器が割り当てられ、実際の台は使用されません
- 本番環境: 実機が割り当てられ、リアルタイムで遊技可能

### Q6: エラーが発生した場合のサポートは？

**A**:
1. まず[トラブルシューティングガイド](NET8_SDK_TROUBLESHOOTING.md)を確認
2. 解決しない場合はNET8サポート（support@net8.com）に連絡

---

## 次のステップ

1. **API詳細リファレンス**: [NET8_SDK_API_REFERENCE.md](NET8_SDK_API_REFERENCE.md)
2. **実装サンプル集**: [NET8_SDK_EXAMPLES.md](NET8_SDK_EXAMPLES.md)
3. **トラブルシューティング**: [NET8_SDK_TROUBLESHOOTING.md](NET8_SDK_TROUBLESHOOTING.md)

---

## サポート

- **技術サポート**: support@net8.com
- **ドキュメント**: https://docs.net8.com
- **API状態**: https://status.net8.com

---

**© 2025 NET8. All rights reserved.**
