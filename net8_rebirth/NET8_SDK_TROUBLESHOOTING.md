# NET8 SDK トラブルシューティングガイド

**バージョン**: v1.1.0
**最終更新**: 2025-11-23

このドキュメントでは、NET8 SDK使用時によくある問題とその解決方法を提供します。

---

## 📋 目次

1. [認証エラー](#認証エラー)
2. [ポイント関連エラー](#ポイント関連エラー)
3. [セッション関連エラー](#セッション関連エラー)
4. [ネットワークエラー](#ネットワークエラー)
5. [デバッグ方法](#デバッグ方法)
6. [よくある質問](#よくある質問)

---

## 認証エラー

### エラー: `INVALID_API_KEY`

```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

#### 原因

1. API Keyが間違っている
2. API Keyが期限切れ
3. 環境（テスト/本番）が間違っている
4. Authorizationヘッダーの形式が間違っている

#### 解決方法

**1. API Keyの確認**

```bash
# 環境変数を確認
echo $NET8_API_KEY
```

**2. Authorizationヘッダーの形式確認**

```javascript
// ✅ 正しい
headers: {
  'Authorization': 'Bearer pk_demo_12345'
}

// ❌ 間違い
headers: {
  'Authorization': 'pk_demo_12345'  // "Bearer "が無い
}
```

**3. 環境の確認**

```javascript
// テスト環境
const API_KEY = 'pk_test_...' または 'pk_demo_...';

// 本番環境
const API_KEY = 'pk_live_...';
```

**4. cURLでの確認**

```bash
curl -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"userId": "test", "modelId": "HOKUTO4GO"}'
```

---

## ポイント関連エラー

### エラー: `INSUFFICIENT_BALANCE`

```json
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Not enough points to start game",
  "required": 100,
  "current": 50
}
```

#### 原因

ユーザーのポイント残高が不足しています。

#### 解決方法

**1. 残高確認**

```javascript
// ユーザーの現在の残高を確認
const balance = await getUserBalance(userId);
console.log('現在の残高:', balance);
```

**2. ポイント追加**

```javascript
// ポイントを追加
await net8.addPoints(userId, 1000, 'initial_deposit');
```

**3. エラーハンドリング**

```javascript
try {
  await net8.startGame(userId, modelId);
} catch (error) {
  if (error.response?.data?.error === 'INSUFFICIENT_BALANCE') {
    // ユーザーにポイント購入を促す
    showPointsPurchaseDialog(error.response.data.required);
  }
}
```

---

### エラー: ポイントが正しく更新されない

#### 症状

- game_end後もポイントが変わらない
- 獲得ポイントが反映されない

#### 原因と解決方法

**1. game_endのレスポンス確認**

```javascript
const gameEnd = await net8.endGame(sessionId, 'win', 500);

// レスポンスを確認
console.log('トランザクションID:', gameEnd.transaction.id);
console.log('変更前残高:', gameEnd.transaction.balanceBefore);
console.log('変更後残高:', gameEnd.transaction.balanceAfter);
console.log('新しい残高:', gameEnd.newBalance);
```

**2. キャッシュの問題**

```javascript
// ポイント取得時にキャッシュを無効化
const balance = await getUserBalance(userId, { cache: false });
```

**3. トランザクション履歴の確認**

```javascript
// プレイ履歴で確認
const history = await net8.getPlayHistory(userId, 1);
console.log('最新のセッション:', history.history[0]);
```

---

## セッション関連エラー

### エラー: `SESSION_NOT_FOUND`

```json
{
  "error": "SESSION_NOT_FOUND",
  "message": "Game session not found or already ended"
}
```

#### 原因

1. sessionIdが間違っている
2. セッションが既に終了している
3. セッションが存在しない

#### 解決方法

**1. sessionIdの確認**

```javascript
// game_startのレスポンスを保存
const gameStart = await net8.startGame(userId, modelId);
const sessionId = gameStart.sessionId;

// sessionIdをローカルストレージに保存（推奨）
localStorage.setItem('currentSession', sessionId);

// game_end時に使用
const storedSessionId = localStorage.getItem('currentSession');
await net8.endGame(storedSessionId, 'win', 500);
```

**2. セッション状態管理（React例）**

```typescript
const [currentSession, setCurrentSession] = useState<string | null>(null);

// ゲーム開始
const handleStart = async () => {
  const result = await net8.startGame(userId, modelId);
  setCurrentSession(result.sessionId);
};

// ゲーム終了
const handleEnd = async () => {
  if (!currentSession) {
    console.error('アクティブなセッションがありません');
    return;
  }
  await net8.endGame(currentSession, 'win', 500);
  setCurrentSession(null);
};
```

---

### エラー: `SESSION_ALREADY_ENDED`

```json
{
  "error": "SESSION_ALREADY_ENDED",
  "message": "This session has already been completed"
}
```

#### 原因

同じセッションに対して複数回game_endを呼び出しています。

#### 解決方法

**1. 二重送信防止**

```javascript
let isEnding = false;

async function endGame(sessionId, result, points) {
  if (isEnding) {
    console.warn('既にゲーム終了処理中です');
    return;
  }

  isEnding = true;
  try {
    const gameEnd = await net8.endGame(sessionId, result, points);
    return gameEnd;
  } finally {
    isEnding = false;
  }
}
```

**2. セッション状態管理**

```typescript
interface Session {
  id: string;
  status: 'active' | 'ended';
}

class SessionManager {
  private session: Session | null = null;

  async start(userId: string, modelId: string) {
    const result = await net8.startGame(userId, modelId);
    this.session = {
      id: result.sessionId,
      status: 'active'
    };
    return result;
  }

  async end(result: string, points: number) {
    if (!this.session || this.session.status === 'ended') {
      throw new Error('No active session');
    }

    const gameEnd = await net8.endGame(this.session.id, result, points);
    this.session.status = 'ended';
    return gameEnd;
  }
}
```

---

## ネットワークエラー

### エラー: `ETIMEDOUT` / `ECONNREFUSED`

#### 症状

```
Error: connect ETIMEDOUT
Error: connect ECONNREFUSED
```

#### 原因と解決方法

**1. タイムアウト設定**

```javascript
// タイムアウトを長めに設定
const client = axios.create({
  baseURL: API_BASE,
  timeout: 30000  // 30秒
});
```

**2. リトライロジック**

```javascript
async function startGameWithRetry(userId, modelId, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await net8.startGame(userId, modelId);
    } catch (error) {
      if (i === maxRetries - 1) throw error;

      console.log(`リトライ ${i + 1}/${maxRetries}...`);
      await new Promise(resolve => setTimeout(resolve, 1000 * (i + 1)));
    }
  }
}
```

**3. ネットワーク接続確認**

```bash
# APIサーバーへの接続確認
curl -I https://mgg-webservice-production.up.railway.app

# DNS解決確認
nslookup mgg-webservice-production.up.railway.app
```

---

### エラー: `429 Too Many Requests`

```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests. Please try again later.",
  "retryAfter": 60
}
```

#### 原因

APIのレート制限を超えています。

#### 解決方法

**1. レート制限の確認**

```javascript
// レスポンスヘッダーを確認
const response = await axios.post(...);
console.log('Limit:', response.headers['x-ratelimit-limit']);
console.log('Remaining:', response.headers['x-ratelimit-remaining']);
console.log('Reset:', response.headers['x-ratelimit-reset']);
```

**2. リトライ処理（Exponential Backoff）**

```javascript
async function requestWithBackoff(fn, maxRetries = 5) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (error.response?.status === 429) {
        const retryAfter = error.response.headers['retry-after'] || Math.pow(2, i);
        console.log(`レート制限エラー。${retryAfter}秒後にリトライします...`);
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      } else {
        throw error;
      }
    }
  }
}
```

---

## デバッグ方法

### リクエスト/レスポンスのログ

```javascript
// Axiosインターセプター
axios.interceptors.request.use(request => {
  console.log('[Request]', request.method, request.url);
  console.log('[Headers]', request.headers);
  console.log('[Body]', request.data);
  return request;
});

axios.interceptors.response.use(
  response => {
    console.log('[Response]', response.status, response.statusText);
    console.log('[Data]', response.data);
    return response;
  },
  error => {
    console.error('[Error]', error.response?.status, error.response?.data);
    return Promise.reject(error);
  }
);
```

### cURLでのテスト

```bash
# game_start
curl -v -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"userId": "debug_user_001", "modelId": "HOKUTO4GO"}'

# game_end
curl -v -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_end.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"sessionId": "gs_xxx...", "result": "win", "pointsWon": 500}'
```

### 環境変数の確認

```bash
# Node.js
console.log('NET8_API_KEY:', process.env.NET8_API_KEY);
console.log('NET8_API_BASE:', process.env.NET8_API_BASE);

# .envファイルの確認
cat .env

# 環境変数が読み込まれているか確認
node -e "require('dotenv').config(); console.log(process.env.NET8_API_KEY)"
```

---

## よくある質問

### Q1: テスト環境と本番環境の違いは？

**A**:

| 項目 | テスト環境 | 本番環境 |
|------|----------|---------|
| API Key | `pk_test_*` または `pk_demo_*` | `pk_live_*` |
| 機器 | モック機器（machineNo: 9999） | 実機 |
| ポイント | テスト用（リセット可能） | 実ポイント |
| WebRTC | モック接続 | 実機カメラ接続 |

### Q2: セッションの有効期限は？

**A**: セッションに有効期限はありませんが、game_startしたら必ずgame_endを呼び出してください。未終了のセッションが残ると、ポイントが正しく処理されません。

### Q3: 同じユーザーで複数のセッションを同時に持てますか？

**A**: はい、可能です。ただし、各セッションは独立して管理されるため、sessionIdを正しく管理してください。

### Q4: ポイントの単位は？

**A**: 1ポイント = 1円相当です。

### Q5: エラーが解決しない場合は？

**A**:

1. このトラブルシューティングガイドをすべて確認
2. [実装マニュアル](NET8_SDK_IMPLEMENTATION_GUIDE.md)を再確認
3. [APIリファレンス](NET8_SDK_API_REFERENCE.md)でAPIの仕様を確認
4. それでも解決しない場合は、以下の情報を添えてNET8サポートに連絡：
   - エラーメッセージ
   - リクエスト内容（API Keyは伏せる）
   - 発生日時
   - 環境（テスト/本番）

---

## サポート連絡先

### 技術サポート

- **Email**: support@net8.com
- **営業時間**: 平日 10:00-18:00 (JST)
- **対応時間**: 24時間以内に返信

### 緊急連絡

本番環境で重大な問題が発生した場合：

- **緊急Email**: emergency@net8.com
- **対応**: 1時間以内に返信

### ドキュメント

- **公式ドキュメント**: https://docs.net8.com
- **APIステータス**: https://status.net8.com
- **変更履歴**: https://changelog.net8.com

---

## デバッグチェックリスト

問題が発生したら、以下を順番に確認してください：

- [ ] API Keyが正しいか確認
- [ ] Authorizationヘッダーの形式が正しいか確認
- [ ] 環境変数が読み込まれているか確認
- [ ] ネットワーク接続を確認
- [ ] cURLで直接APIを呼び出して動作確認
- [ ] リクエスト/レスポンスのログを確認
- [ ] エラーメッセージの詳細を確認
- [ ] このドキュメントで該当するエラーを検索
- [ ] それでも解決しない場合はサポートに連絡

---

**© 2025 NET8. All rights reserved.**
