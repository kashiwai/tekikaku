# NET8 Gaming SDK API仕様書 v1.1.0

## 📋 目次

1. [認証](#認証)
2. [エンドポイント一覧](#エンドポイント一覧)
3. [データモデル](#データモデル)
4. [エラーコード](#エラーコード)
5. [レート制限](#レート制限)

---

## 認証

すべてのAPIリクエストには、Authorizationヘッダーにベアラートークン（APIキー）が必要です。

```http
Authorization: Bearer YOUR_API_KEY
```

### APIキーの種類

| 環境 | プレフィックス | 用途 |
|-----|--------------|------|
| テスト | `pk_demo_*` | 開発・テスト |
| ステージング | `pk_staging_*` | 検証環境 |
| 本番 | `pk_live_*` | 実稼働 |

---

## エンドポイント一覧

### 基本URL

```
https://mgg-webservice-production.up.railway.app
```

### エンドポイント

| メソッド | エンドポイント | 説明 |
|---------|--------------|------|
| POST | `/api/v1/auth.php` | JWT認証トークン取得 |
| GET | `/api/v1/models.php` | 利用可能な機種一覧取得 |
| GET | `/api/v1/recommended_models.php` | 推奨機種取得 |
| POST | `/api/v1/game_start.php` | ゲーム開始 |
| POST | `/api/v1/game_end.php` | ゲーム終了 |
| POST | `/api/v1/add_points.php` | ポイント追加 |

---

## 1. 認証API

### POST /api/v1/auth.php

JWT認証トークンを取得します。

#### リクエスト

```http
POST /api/v1/auth.php
Content-Type: application/json

{
  "apiKey": "pk_demo_12345"
}
```

#### パラメータ

| フィールド | 型 | 必須 | 説明 |
|----------|---|-----|------|
| apiKey | string | ✅ | APIキー |

#### レスポンス (200 OK)

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresAt": "2025-11-22T10:30:00Z",
  "partnerId": 1,
  "partnerName": "Demo Partner",
  "environment": "test"
}
```

#### エラーレスポンス (401 Unauthorized)

```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

---

## 2. 機種一覧API

### GET /api/v1/models.php

利用可能なゲーム機種の一覧を取得します。

#### リクエスト

```http
GET /api/v1/models.php
Authorization: Bearer YOUR_JWT_TOKEN
```

#### レスポンス (200 OK)

```json
{
  "success": true,
  "count": 5,
  "models": [
    {
      "id": "HOKUTO4GO",
      "name": "北斗の拳 初号機",
      "category": "slot",
      "maker": "サミー",
      "releaseDate": "2018-10-01",
      "thumbnail": "/data/img/model/hokuto4go.jpg",
      "minPoints": 100,
      "description": "大人気パチスロ機種",
      "features": ["AT", "ボーナス"],
      "isActive": true
    },
    {
      "id": "ZENIGATA01",
      "name": "主役は銭形",
      "category": "pachinko",
      "maker": "平和",
      "releaseDate": "2019-03-15",
      "thumbnail": "/data/img/model/zenigata.jpg",
      "minPoints": 100,
      "description": "痛快パチンコ",
      "features": ["確変", "時短"],
      "isActive": true
    }
  ]
}
```

#### フィールド説明

| フィールド | 型 | 説明 |
|----------|---|------|
| id | string | 機種ID（一意） |
| name | string | 機種名 |
| category | string | カテゴリ（`slot` / `pachinko`） |
| maker | string | メーカー名 |
| releaseDate | string | リリース日（ISO 8601） |
| thumbnail | string | サムネイル画像URL |
| minPoints | integer | 最低プレイポイント |
| description | string | 説明文 |
| features | array | 特徴タグ |
| isActive | boolean | 有効フラグ |

---

## 3. 推奨機種API

### GET /api/v1/recommended_models.php

ユーザーの残高に基づいた推奨機種を取得します。

#### リクエスト

```http
GET /api/v1/recommended_models.php?balance=5000&limit=3
Authorization: Bearer YOUR_JWT_TOKEN
```

#### パラメータ

| パラメータ | 型 | 必須 | デフォルト | 説明 |
|----------|---|-----|----------|------|
| balance | integer | ✅ | - | ユーザーの現在残高 |
| limit | integer | ❌ | 3 | 取得する機種数（1-10） |

#### レスポンス (200 OK)

```json
{
  "success": true,
  "balance": 5000,
  "count": 3,
  "models": [
    {
      "id": "MILLIONGOD01",
      "name": "ミリオンゴッド",
      "category": "slot",
      "maker": "ユニバーサル",
      "thumbnail": "/data/img/model/milliongod.jpg",
      "minPoints": 100,
      "canPlay": true,
      "availability": {
        "total": 5,
        "available": 3,
        "isAvailable": true
      },
      "recommended": true,
      "reason": "高人気機種"
    }
  ]
}
```

#### フィールド説明

| フィールド | 型 | 説明 |
|----------|---|------|
| canPlay | boolean | 残高で遊べるかどうか |
| availability.total | integer | 総台数 |
| availability.available | integer | 空き台数 |
| availability.isAvailable | boolean | 台が空いているか |
| recommended | boolean | 推奨機種か |
| reason | string | 推奨理由 |

---

## 4. ゲーム開始API

### POST /api/v1/game_start.php

ゲームセッションを開始し、ポイントを消費します。

#### リクエスト

```http
POST /api/v1/game_start.php
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "modelId": "HOKUTO4GO",
  "userId": "user_12345"
}
```

#### パラメータ

| フィールド | 型 | 必須 | 説明 |
|----------|---|-----|------|
| modelId | string | ✅ | ゲーム機種ID |
| userId | string | ❌ | パートナー側のユーザーID |

**注意**: `userId` を指定すると、ポイント管理機能が有効になります。

#### レスポンス (200 OK)

```json
{
  "success": true,
  "environment": "test",
  "sessionId": "gs_691fc1b55b8c7_1763688885",
  "machineNo": 9999,
  "signalingId": "mock_sig_cf2fdc99",
  "model": {
    "id": "HOKUTO4GO",
    "name": "北斗の拳",
    "category": "slot"
  },
  "signaling": {
    "signalingId": "mock_sig_cf2fdc99",
    "host": "mock-signaling.net8.test",
    "port": 443,
    "secure": true,
    "path": "/socket.io",
    "iceServers": [
      {
        "urls": "stun:stun.l.google.com:19302"
      }
    ]
  },
  "camera": {
    "cameraNo": 9999,
    "streamUrl": "mock://camera.net8.test/stream/HOKUTO4GO"
  },
  "playUrl": "/data/play_v2/index.php?NO=9999",
  "points": {
    "consumed": 100,
    "balance": 9900,
    "balanceBefore": 10000
  },
  "pointsConsumed": 100
}
```

#### エラーレスポンス

##### 404 - 機種が見つからない

```json
{
  "error": "MODEL_NOT_FOUND",
  "message": "Model not found"
}
```

##### 402 - ポイント不足

```json
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Insufficient points",
  "balance": 50,
  "required": 100
}
```

##### 503 - 台が空いていない

```json
{
  "error": "NO_AVAILABLE_MACHINE",
  "message": "No available machine for this model",
  "environment": "production"
}
```

---

## 5. ゲーム終了API

### POST /api/v1/game_end.php

ゲームセッションを終了し、ポイントを払い出します。

#### リクエスト

```http
POST /api/v1/game_end.php
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "sessionId": "gs_691fc1b55b8c7_1763688885",
  "result": "win",
  "pointsWon": 500
}
```

#### パラメータ

| フィールド | 型 | 必須 | 説明 |
|----------|---|-----|------|
| sessionId | string | ✅ | ゲームセッションID |
| result | string | ✅ | 結果（`win` / `lose` / `draw`） |
| pointsWon | integer | ❌ | 獲得ポイント（デフォルト: 0） |

#### レスポンス (200 OK)

```json
{
  "success": true,
  "sessionId": "gs_691fc1b55b8c7_1763688885",
  "result": "win",
  "pointsConsumed": 100,
  "pointsWon": 500,
  "netProfit": 400,
  "balance": {
    "before": 9900,
    "after": 10300
  },
  "transaction": {
    "id": "txn_abc123_1763688900",
    "type": "payout",
    "amount": 500,
    "timestamp": "2025-11-21T10:15:00Z"
  },
  "message": "Game ended successfully"
}
```

#### エラーレスポンス

##### 404 - セッションが見つからない

```json
{
  "error": "SESSION_NOT_FOUND",
  "message": "Game session not found"
}
```

##### 400 - 無効なリクエスト

```json
{
  "error": "INVALID_REQUEST",
  "message": "Missing required parameter: sessionId"
}
```

---

## 6. ポイント追加API

### POST /api/v1/add_points.php

プレイ中にボーナスポイントを追加します。

#### リクエスト

```http
POST /api/v1/add_points.php
Authorization: Bearer YOUR_API_KEY
Content-Type: application/json

{
  "sessionId": "gs_691fc1b55b8c7_1763688885",
  "amount": 500,
  "description": "ログインボーナス"
}
```

#### パラメータ

| フィールド | 型 | 必須 | 説明 |
|----------|---|-----|------|
| sessionId | string | ✅ | ゲームセッションID |
| amount | integer | ✅ | 追加ポイント（正の整数） |
| description | string | ❌ | ポイント追加の説明 |

#### レスポンス (200 OK)

```json
{
  "success": true,
  "sessionId": "gs_691fc1b55b8c7_1763688885",
  "transaction": {
    "id": "txn_bonus_123_1763688920",
    "amount": 500,
    "balanceBefore": 10300,
    "balanceAfter": 10800,
    "description": "ログインボーナス",
    "timestamp": "2025-11-21T10:16:00Z"
  },
  "message": "Points added successfully"
}
```

#### エラーレスポンス

##### 404 - セッションが見つからない

```json
{
  "error": "SESSION_NOT_FOUND",
  "message": "Game session not found or not active"
}
```

##### 400 - 無効な金額

```json
{
  "error": "INVALID_AMOUNT",
  "message": "Amount must be a positive integer"
}
```

---

## データモデル

### Model（機種）

```typescript
interface Model {
  id: string;              // 機種ID
  name: string;            // 機種名
  category: 'slot' | 'pachinko';  // カテゴリ
  maker: string;           // メーカー名
  releaseDate: string;     // リリース日（ISO 8601）
  thumbnail: string;       // サムネイルURL
  minPoints: number;       // 最低プレイポイント
  description?: string;    // 説明文
  features?: string[];     // 特徴タグ
  isActive: boolean;       // 有効フラグ
}
```

### GameSession（ゲームセッション）

```typescript
interface GameSession {
  sessionId: string;       // セッションID
  userId?: string;         // ユーザーID（オプション）
  machineNo: number;       // マシン番号
  modelId: string;         // 機種ID
  pointsConsumed: number;  // 消費ポイント
  pointsWon: number;       // 獲得ポイント
  result: 'playing' | 'win' | 'lose' | 'draw' | 'cancelled' | 'error';
  startedAt: string;       // 開始時刻（ISO 8601）
  endedAt?: string;        // 終了時刻（ISO 8601）
  playDuration?: number;   // プレイ時間（秒）
}
```

### Transaction（取引）

```typescript
interface Transaction {
  id: string;              // 取引ID
  userId: string;          // ユーザーID
  type: 'consume' | 'payout' | 'bonus' | 'deposit';
  amount: number;          // 金額
  balanceBefore: number;   // 変更前残高
  balanceAfter: number;    // 変更後残高
  sessionId?: string;      // 関連セッションID
  description?: string;    // 説明
  timestamp: string;       // タイムスタンプ（ISO 8601）
}
```

### User（ユーザー）

```typescript
interface User {
  id: number;              // 内部ユーザーID
  partnerUserId: string;   // パートナー側ユーザーID
  apiKeyId: number;        // APIキーID
  email?: string;          // メールアドレス
  username?: string;       // ユーザー名
  balance: number;         // 現在残高
  totalDeposited: number;  // 総入金額
  totalConsumed: number;   // 総消費額
  totalWon: number;        // 総獲得額
  isActive: boolean;       // 有効フラグ
  createdAt: string;       // 作成日時（ISO 8601）
}
```

---

## エラーコード

### 認証エラー (401)

| コード | 説明 |
|-------|------|
| `UNAUTHORIZED` | Authorization ヘッダーがない |
| `INVALID_API_KEY` | 無効なAPIキー |
| `EXPIRED_TOKEN` | トークンの有効期限切れ |
| `INVALID_TOKEN` | 無効なトークン |

### クライアントエラー (400)

| コード | 説明 |
|-------|------|
| `MISSING_MODEL_ID` | modelIdパラメータがない |
| `INVALID_REQUEST` | 無効なリクエスト |
| `INVALID_AMOUNT` | 無効な金額 |
| `INVALID_SESSION` | 無効なセッション |

### リソースエラー (404)

| コード | 説明 |
|-------|------|
| `MODEL_NOT_FOUND` | 機種が見つからない |
| `SESSION_NOT_FOUND` | セッションが見つからない |
| `USER_NOT_FOUND` | ユーザーが見つからない |

### ビジネスロジックエラー (402, 403, 409)

| コード | HTTPステータス | 説明 |
|-------|---------------|------|
| `INSUFFICIENT_BALANCE` | 402 | ポイント不足 |
| `FORBIDDEN` | 403 | アクセス禁止 |
| `SESSION_ALREADY_ENDED` | 409 | セッション既に終了 |

### サーバーエラー (500, 503)

| コード | HTTPステータス | 説明 |
|-------|---------------|------|
| `INTERNAL_ERROR` | 500 | サーバー内部エラー |
| `NO_AVAILABLE_MACHINE` | 503 | 空き台なし |
| `GAME_START_FAILED` | 500 | ゲーム開始失敗 |
| `GAME_END_FAILED` | 500 | ゲーム終了失敗 |

---

## レート制限

### 制限値

| エンドポイント | 制限 | ウィンドウ |
|--------------|-----|----------|
| `/api/v1/auth.php` | 100回 | 15分 |
| `/api/v1/models.php` | 1000回 | 1時間 |
| `/api/v1/game_start.php` | 100回 | 1分 |
| `/api/v1/game_end.php` | 100回 | 1分 |
| `/api/v1/add_points.php` | 300回 | 1分 |

### レート制限超過時のレスポンス (429)

```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests",
  "retryAfter": 60
}
```

### ヘッダー

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1700000000
```

---

## ベストプラクティス

### 1. エラーハンドリング

すべてのAPI呼び出しでエラーハンドリングを実装してください：

```javascript
try {
  const response = await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${apiKey}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ modelId, userId })
  });

  if (!response.ok) {
    const error = await response.json();
    throw new Error(error.message);
  }

  const data = await response.json();
  return data;
} catch (error) {
  console.error('ゲーム開始エラー:', error);
  throw error;
}
```

### 2. リトライロジック

一時的なエラーの場合、Exponential backoffでリトライしてください：

```javascript
async function retryWithBackoff(fn, maxRetries = 3) {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error) {
      if (i === maxRetries - 1) throw error;
      await new Promise(resolve => setTimeout(resolve, Math.pow(2, i) * 1000));
    }
  }
}
```

### 3. タイムアウト設定

長時間実行されるリクエストにはタイムアウトを設定してください：

```javascript
const controller = new AbortController();
const timeoutId = setTimeout(() => controller.abort(), 30000);

try {
  const response = await fetch(url, {
    signal: controller.signal
  });
} finally {
  clearTimeout(timeoutId);
}
```

---

## バージョニング

APIバージョンはURLに含まれます: `/api/v1/`

メジャーバージョンアップ時は、後方互換性のない変更が含まれる可能性があります。

**現在のバージョン**: `v1.1.0`

---

## 変更履歴

### v1.1.0 (2025-11-21)

- ✅ `/api/v1/add_points.php` 追加
- ✅ `/api/v1/recommended_models.php` 追加
- ✅ `game_start.php` に `userId` パラメータ追加
- ✅ ポイント管理機能強化
- ✅ トランザクション管理改善

### v1.0.1 (2025-11-06)

- ✅ 初回リリース
- ✅ 基本的なゲーム開始・終了機能

---

**NET8 Gaming SDK API仕様書 v1.1.0**
© 2025 NET8 Development Team

最終更新: 2025-11-21
