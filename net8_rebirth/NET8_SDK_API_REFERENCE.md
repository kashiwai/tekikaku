# NET8 SDK API詳細リファレンス

**バージョン**: v1.1.0
**最終更新**: 2025-11-23
**Base URL**: `https://mgg-webservice-production.up.railway.app`

---

## 📋 目次

1. [認証](#認証)
2. [game_start API](#game_start-api)
3. [game_end API](#game_end-api)
4. [add_points API](#add_points-api)
5. [play_history API](#play_history-api)
6. [recommended_models API](#recommended_models-api)
7. [レート制限](#レート制限)
8. [Webhook](#webhook)

---

## 認証

### Bearer Token認証

すべてのAPIリクエストには、HTTPヘッダーに`Authorization`を含める必要があります。

```http
Authorization: Bearer {API_KEY}
```

### API Key形式

| 環境 | プレフィックス | 例 |
|------|--------------|-----|
| テスト | `pk_test_` または `pk_demo_` | `pk_demo_12345` |
| 本番 | `pk_live_` | `pk_live_abcdef123456` |

### リクエスト例

```bash
curl -X POST "https://mgg-webservice-production.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer pk_demo_12345" \
  -H "Content-Type: application/json" \
  -d '{"userId": "user_001", "modelId": "HOKUTO4GO"}'
```

---

## game_start API

ゲームセッションを開始し、ポイントを消費します。

### エンドポイント

```
POST /api/v1/game_start.php
```

### リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 | 例 |
|----------|-----|-----|------|-----|
| `userId` | string | ✅ | パートナー側のユーザーID | `"user_12345"` |
| `modelId` | string | ✅ | 遊技する機種コード | `"HOKUTO4GO"` |

### リクエスト例

```json
{
  "userId": "user_12345",
  "modelId": "HOKUTO4GO"
}
```

### レスポンス（成功時）

**HTTPステータス**: `200 OK`

```json
{
  "success": true,
  "environment": "test",
  "sessionId": "gs_6922713d44833_1763864893",
  "machineNo": 9999,
  "signalingId": "mock_sig_13769348",
  "model": {
    "id": "HOKUTO4GO",
    "name": "北斗の拳",
    "category": "pachinko"
  },
  "signaling": {
    "signalingId": "mock_sig_13769348",
    "host": "mock-signaling.net8.test",
    "port": 443,
    "secure": true,
    "path": "/socket.io",
    "iceServers": [
      {
        "urls": "stun:stun.l.google.com:19302"
      }
    ],
    "mock": true
  },
  "camera": {
    "cameraNo": 9999,
    "streamUrl": "mock://camera.net8.test/stream/HOKUTO4GO",
    "mock": true
  },
  "playUrl": "/data/play_v2/index.php?NO=9999",
  "mock": true,
  "points": {
    "consumed": 100,
    "balance": "9900",
    "balanceBefore": 10000
  },
  "pointsConsumed": 100
}
```

#### レスポンスフィールド詳細

| フィールド | 型 | 説明 |
|----------|-----|------|
| `success` | boolean | リクエスト成功フラグ |
| `environment` | string | 環境（`"test"` または `"production"`） |
| `sessionId` | string | **重要**: game_endで使用するセッションID |
| `machineNo` | number | 割り当てられた台番号 |
| `signalingId` | string | WebRTC接続用のシグナリングID |
| `model.id` | string | 機種コード |
| `model.name` | string | 機種名 |
| `model.category` | string | カテゴリ（`"pachinko"` または `"slot"`） |
| `signaling` | object | WebRTC接続情報 |
| `camera` | object | カメラストリーム情報 |
| `playUrl` | string | ゲームプレイ画面URL |
| `points.consumed` | number | 消費されたポイント |
| `points.balance` | string | 残高 |
| `points.balanceBefore` | number | 消費前の残高 |
| `pointsConsumed` | number | 消費ポイント（重複） |

### エラーレスポンス

#### 1. 認証エラー

**HTTPステータス**: `401 Unauthorized`

```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

#### 2. 残高不足

**HTTPステータス**: `400 Bad Request`

```json
{
  "error": "INSUFFICIENT_BALANCE",
  "message": "Not enough points to start game",
  "required": 100,
  "current": 50
}
```

#### 3. 機種が見つからない

**HTTPステータス**: `404 Not Found`

```json
{
  "error": "MODEL_NOT_FOUND",
  "message": "Specified model does not exist",
  "modelId": "INVALID_MODEL"
}
```

#### 4. バリデーションエラー

**HTTPステータス**: `400 Bad Request`

```json
{
  "error": "VALIDATION_ERROR",
  "message": "Missing required parameter: userId"
}
```

---

## game_end API

ゲームセッションを終了し、獲得ポイントを払い出します。

### エンドポイント

```
POST /api/v1/game_end.php
```

### リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 | 例 |
|----------|-----|-----|------|-----|
| `sessionId` | string | ✅ | game_startで取得したセッションID | `"gs_xxx..."` |
| `result` | string | ✅ | ゲーム結果（`"win"`, `"lose"`, `"draw"`） | `"win"` |
| `pointsWon` | number | ✅ | 獲得ポイント数（負けの場合は0） | `500` |

### リクエスト例

```json
{
  "sessionId": "gs_6922713d44833_1763864893",
  "result": "win",
  "pointsWon": 500
}
```

### レスポンス（成功時）

**HTTPステータス**: `200 OK`

```json
{
  "success": true,
  "sessionId": "gs_6922713d44833_1763864893",
  "result": "win",
  "pointsConsumed": "100",
  "pointsWon": 500,
  "netProfit": 400,
  "playDuration": 32430,
  "endedAt": "2025-11-23 11:28:44",
  "newBalance": 10400,
  "transaction": {
    "id": "txn_6922715d14ed0_1763864925",
    "amount": 500,
    "balanceBefore": "9900",
    "balanceAfter": 10400
  }
}
```

#### レスポンスフィールド詳細

| フィールド | 型 | 説明 |
|----------|-----|------|
| `success` | boolean | リクエスト成功フラグ |
| `sessionId` | string | 終了したセッションID |
| `result` | string | ゲーム結果 |
| `pointsConsumed` | string | 消費ポイント |
| `pointsWon` | number | 獲得ポイント |
| `netProfit` | number | 純利益（獲得 - 消費） |
| `playDuration` | number | プレイ時間（秒） |
| `endedAt` | string | 終了日時（ISO 8601形式） |
| `newBalance` | number | 新しい残高 |
| `transaction.id` | string | トランザクションID |
| `transaction.amount` | number | トランザクション金額 |
| `transaction.balanceBefore` | string | 変更前残高 |
| `transaction.balanceAfter` | number | 変更後残高 |

### エラーレスポンス

#### 1. セッションが見つからない

**HTTPステータス**: `404 Not Found`

```json
{
  "error": "SESSION_NOT_FOUND",
  "message": "Game session not found or already ended",
  "sessionId": "gs_invalid_123"
}
```

#### 2. セッション既に終了

**HTTPステータス**: `400 Bad Request`

```json
{
  "error": "SESSION_ALREADY_ENDED",
  "message": "This session has already been completed",
  "sessionId": "gs_xxx...",
  "endedAt": "2025-11-23 10:00:00"
}
```

#### 3. トランザクション失敗

**HTTPステータス**: `500 Internal Server Error`

```json
{
  "error": "TRANSACTION_FAILED",
  "message": "Failed to complete game end transaction",
  "details": "Database error: ..."
}
```

---

## add_points API

ユーザーにポイントを追加します（チャージ、ボーナス等）。

### エンドポイント

```
POST /api/v1/add_points.php
```

### リクエストパラメータ

| パラメータ | 型 | 必須 | 説明 | 例 |
|----------|-----|-----|------|-----|
| `userId` | string | ✅ | パートナー側のユーザーID | `"user_12345"` |
| `amount` | number | ✅ | 追加するポイント数 | `1000` |
| `reason` | string | ❌ | 追加理由（ログ用） | `"purchase"` |

### リクエスト例

```json
{
  "userId": "user_12345",
  "amount": 1000,
  "reason": "purchase"
}
```

### レスポンス（成功時）

**HTTPステータス**: `200 OK`

```json
{
  "success": true,
  "userId": "user_12345",
  "amountAdded": 1000,
  "balanceBefore": 10400,
  "balanceAfter": 11400,
  "transaction": {
    "id": "txn_69227abc12345_1763870000",
    "timestamp": "2025-11-23T11:30:00Z"
  }
}
```

### エラーレスポンス

#### 1. 無効な金額

**HTTPステータス**: `400 Bad Request`

```json
{
  "error": "INVALID_AMOUNT",
  "message": "Amount must be greater than 0",
  "amount": -100
}
```

---

## play_history API

ユーザーのプレイ履歴を取得します。

### エンドポイント

```
GET /api/v1/play_history.php
```

### クエリパラメータ

| パラメータ | 型 | 必須 | 説明 | デフォルト |
|----------|-----|-----|------|----------|
| `userId` | string | ✅ | パートナー側のユーザーID | - |
| `limit` | number | ❌ | 取得件数 | `10` |
| `offset` | number | ❌ | オフセット | `0` |

### リクエスト例

```bash
GET /api/v1/play_history.php?userId=user_12345&limit=5&offset=0
```

### レスポンス（成功時）

**HTTPステータス**: `200 OK`

```json
{
  "success": true,
  "userId": "user_12345",
  "total": 25,
  "limit": 5,
  "offset": 0,
  "history": [
    {
      "sessionId": "gs_xxx1",
      "modelId": "HOKUTO4GO",
      "modelName": "北斗の拳",
      "result": "win",
      "pointsConsumed": 100,
      "pointsWon": 500,
      "netProfit": 400,
      "playDuration": 1234,
      "startedAt": "2025-11-23T10:00:00Z",
      "endedAt": "2025-11-23T10:20:34Z"
    },
    {
      "sessionId": "gs_xxx2",
      "modelId": "ZENIGATA01",
      "modelName": "主役は銭形",
      "result": "lose",
      "pointsConsumed": 100,
      "pointsWon": 0,
      "netProfit": -100,
      "playDuration": 567,
      "startedAt": "2025-11-23T09:00:00Z",
      "endedAt": "2025-11-23T09:09:27Z"
    }
  ]
}
```

---

## recommended_models API

おすすめの機種一覧を取得します。

### エンドポイント

```
GET /api/v1/recommended_models.php
```

### クエリパラメータ

| パラメータ | 型 | 必須 | 説明 | デフォルト |
|----------|-----|-----|------|----------|
| `category` | string | ❌ | カテゴリフィルター（`"pachinko"` または `"slot"`） | 全て |
| `limit` | number | ❌ | 取得件数 | `10` |

### リクエスト例

```bash
GET /api/v1/recommended_models.php?category=pachinko&limit=5
```

### レスポンス（成功時）

**HTTPステータス**: `200 OK`

```json
{
  "success": true,
  "count": 5,
  "models": [
    {
      "modelId": "HOKUTO4GO",
      "modelName": "北斗の拳",
      "category": "pachinko",
      "makerName": "サミー",
      "imageUrl": "https://cdn.net8.com/models/hokuto4go.jpg",
      "description": "大人気の北斗の拳シリーズ最新作",
      "minBet": 100,
      "maxPayout": 10000,
      "popularity": 95
    }
  ]
}
```

---

## レート制限

### 制限値

| エンドポイント | レート制限 | 制限単位 |
|--------------|----------|---------|
| `game_start` | 100リクエスト/分 | API Key単位 |
| `game_end` | 100リクエスト/分 | API Key単位 |
| `add_points` | 50リクエスト/分 | API Key単位 |
| その他 | 200リクエスト/分 | API Key単位 |

### レート制限エラー

**HTTPステータス**: `429 Too Many Requests`

```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Too many requests. Please try again later.",
  "retryAfter": 60,
  "limit": 100,
  "remaining": 0,
  "reset": 1700000000
}
```

### レスポンスヘッダー

```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1700000000
```

---

## Webhook

### Webhook設定

ゲームイベントの通知を受け取るためのWebhookを設定できます。

### サポートイベント

| イベント | 説明 | タイミング |
|---------|------|----------|
| `game.started` | ゲーム開始 | game_start API呼び出し時 |
| `game.ended` | ゲーム終了 | game_end API呼び出し時 |
| `points.added` | ポイント追加 | add_points API呼び出し時 |

### Webhookペイロード例

```json
{
  "event": "game.ended",
  "timestamp": "2025-11-23T11:28:44Z",
  "data": {
    "sessionId": "gs_xxx...",
    "userId": "user_12345",
    "result": "win",
    "pointsWon": 500,
    "netProfit": 400
  }
}
```

### Webhook署名検証

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const expectedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');

  return crypto.timingSafeEqual(
    Buffer.from(signature),
    Buffer.from(expectedSignature)
  );
}
```

---

## バージョニング

### APIバージョン

現在のバージョン: **v1.1.0**

### 変更履歴

#### v1.1.0 (2025-11-23)
- `game_sessions`テーブルに`partner_user_id`カラム追加
- ユーザー自動紐づけ機能追加
- トランザクション記録強化

#### v1.0.0 (2025-11-06)
- 初回リリース
- 基本API実装（game_start, game_end, add_points）

---

## サポート

- **技術サポート**: support@net8.com
- **APIステータス**: https://status.net8.com
- **ドキュメント**: https://docs.net8.com

---

**© 2025 NET8. All rights reserved.**
