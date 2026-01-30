# NET8 オンラインパチスロ API マニュアル（日本語版）

**バージョン:** 1.0.0
**最終更新:** 2026年1月30日
**対象:** 外部パートナー（中国・韓国・その他海外）

---

## 📋 目次

1. [概要](#概要)
2. [認証システム](#認証システム)
3. [APIエンドポイント一覧](#apiエンドポイント一覧)
4. [ゲームフロー](#ゲームフロー)
5. [リアルタイムコールバック](#リアルタイムコールバック)
6. [Webhookセキュリティ](#webhookセキュリティ)
7. [エラーコード](#エラーコード)
8. [コード例](#コード例)
9. [テスト方法](#テスト方法)
10. [本番環境デプロイ](#本番環境デプロイ)

---

## 概要

NET8 オンラインパチスロ APIは、実際のパチスロ台をオンラインで遊技できる統合システムです。外部パートナーは本APIを使用して、自社プラットフォームにNET8のゲームを組み込むことができます。

### 主な機能

- 🔐 **JWT認証** - セキュアなトークンベース認証
- 🎮 **ゲーム管理** - 台一覧、ゲーム開始/終了、リアルタイムイベント
- 💰 **ポイント管理** - 残高追加、調整、変換
- 🔔 **Webhookシステム** - リアルタイムイベント通知（HMAC-SHA256署名）
- 🌐 **多言語対応** - 日本語、中国語、韓国語、英語
- 💱 **多通貨対応** - JPY、CNY、USD、TWD

### ベースURL

```
本番環境: https://ifreamnet8-development.up.railway.app/api/v1
```

### リクエスト形式

- **HTTPメソッド:** POST（一部GET）
- **コンテンツタイプ:** `application/json`
- **認証ヘッダー:** `Authorization: Bearer {JWT_TOKEN}`
- **言語パラメータ:** `lang=ja` (ja/zh/ko/en)
- **通貨パラメータ:** `currency=JPY` (JPY/CNY/USD/TWD)

---

## 認証システム

### JWT トークンの取得

すべてのAPI呼び出しの前に、まずJWT認証トークンを取得する必要があります。

**エンドポイント:** `POST /auth.php`

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "apiKey": "your_api_key_here"
  }'
```

**成功レスポンス:**

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "production"
}
```

**エラーレスポンス:**

```json
{
  "error": "INVALID_API_KEY",
  "message": "無効または期限切れのAPIキーです"
}
```

### トークン使用方法

取得したJWTトークンは、すべてのAPIリクエストのAuthorizationヘッダーに含めます。

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

**トークンの有効期限:**
- 発行から1時間（3600秒）
- 期限切れ後は再度 `/auth.php` を呼び出してください

---

## APIエンドポイント一覧

### 🔐 認証 API

| エンドポイント | メソッド | 説明 |
|--------------|---------|------|
| `/auth.php` | POST | JWT認証トークンの取得 |

### 🎮 ゲーム管理 API

| エンドポイント | メソッド | 説明 |
|--------------|---------|------|
| `/list_machines.php` | GET | 利用可能な台の一覧取得 |
| `/models.php` | GET | 機種詳細情報の取得 |
| `/recommended_models.php` | GET | おすすめ機種の取得 |
| `/check_machines.php` | GET | 台の状態確認 |
| `/game_start.php` | POST | ゲーム開始 |
| `/game_bet.php` | POST | ベットイベント記録（リアルタイム） |
| `/game_win.php` | POST | 勝利イベント記録（リアルタイム） |
| `/game_end.php` | POST | ゲーム終了・精算 |

### 💰 ポイント管理 API

| エンドポイント | メソッド | 説明 |
|--------------|---------|------|
| `/add_points.php` | POST | ポイント追加（課金） |
| `/set_balance.php` | POST | 残高設定（管理者機能） |
| `/adjust_balance.php` | POST | 残高調整（増減） |
| `/convert_credit.php` | POST | クレジット変換 |

### 📊 クエリ API

| エンドポイント | メソッド | 説明 |
|--------------|---------|------|
| `/play_history.php` | GET | プレイ履歴の取得 |
| `/list_users.php` | GET | ユーザー一覧（管理者） |

---

## ゲームフロー

### 完全なゲームフローの例

```
1️⃣ 認証
   POST /auth.php
   → JWT Token取得

2️⃣ 台一覧取得
   GET /list_machines.php?lang=ja
   → 利用可能な台のリストを取得

3️⃣ ゲーム開始
   POST /game_start.php
   → sessionId、playUrl を取得
   → iframeにplayUrlを読み込む

4️⃣ ゲームプレイ中（自動）
   ┌─ プレイヤーがベット
   │  → POST /game_bet.php（自動送信）
   │  → Webhook: game.bet イベント
   │
   └─ プレイヤーが勝利
      → POST /game_win.php（自動送信）
      → Webhook: game.win イベント

5️⃣ ゲーム終了
   プレイヤーが精算
   → window.parent.postMessage（game:settlement）
   → パートナー側が受信してPOST /game_end.php
   → Webhook: game.ended イベント

6️⃣ 履歴確認
   GET /play_history.php
```

---

## リアルタイムコールバック

### 自動送信されるイベント

NET8は、ゲームプレイ中に以下のイベントを自動的にAPIに送信します：

#### 1. ベットイベント（自動）

**送信タイミング:** プレイヤーがベットするたび

**実装場所:** `view_auth_pachi.js:167`

```javascript
// 自動的に呼び出されます
fetch('/api/v1/game_bet.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        sessionId: "sess_1738234567_23_abc123",
        betAmount: 10,
        creditBefore: 1000,
        creditAfter: 990
    })
});
```

**APIレスポンス後の動作:**
- Webhookで `game.bet` イベントがパートナーサーバーに送信されます

#### 2. 勝利イベント（自動）

**送信タイミング:** プレイヤーが勝利するたび

**実装場所:** `view_auth_pachi.js:216`

```javascript
// 自動的に呼び出されます
fetch('/api/v1/game_win.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        sessionId: "sess_1738234567_23_abc123",
        winAmount: 50,
        creditBefore: 990,
        creditAfter: 1040
    })
});
```

**APIレスポンス後の動作:**
- Webhookで `game.win` イベントがパートナーサーバーに送信されます

#### 3. ゲーム終了通知（postMessage）

**送信タイミング:** ゲーム精算時

**実装場所:** `view_auth_pachi.js:1240`

```javascript
// 親ウィンドウにpostMessage送信
window.parent.postMessage({
    type: 'game:settlement',
    payload: {
        totalBets: 100,
        totalWins: 150,
        finalBalance: 1050,
        result: 'completed'
    }
}, '*');
```

**パートナー側の実装:**

```javascript
window.addEventListener('message', async (event) => {
    if (event.data.type === 'game:settlement') {
        // game_end.php を呼び出す
        await fetch('/api/v1/game_end.php', {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${jwtToken}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sessionId: currentSessionId,
                totalBets: event.data.payload.totalBets,
                totalWins: event.data.payload.totalWins,
                result: 'completed'
            })
        });
    }
});
```

### 重要: koreaMode フラグ

リアルタイムコールバック（game_bet、game_win）が送信されるには、`koreaMode = true` である必要があります。

**自動的に有効化される条件:**
- ✅ `game_start.php` で `callbackUrl` を設定
- ✅ `game_start.php` で `callbackSecret` を設定
- ✅ `initialPoints > 0`

この条件を満たすと、ゲーム開始時に自動的に `koreaMode` が有効化され、リアルタイムコールバックが動作します。

---

## 主要APIの詳細

### 1. ゲーム開始 API

**エンドポイント:** `POST /game_start.php`

**リクエストパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| modelId | string | ✅ | 機種ID（例: "SLOT-107"） |
| userId | string | ✅ | パートナー側のユーザーID |
| machineNo | integer | ❌ | 指定する台番号（空なら自動割当） |
| initialPoints | integer | ✅ | 初期ポイント数 |
| balanceMode | string | ❌ | 残高モード: "add"（加算）/ "set"（設定）。デフォルト: "add" |
| consumeImmediately | boolean | ❌ | 即座に消費: true（ゲーム開始時）/ false（終了時）。デフォルト: true |
| lang | string | ❌ | 言語: ja/zh/ko/en。デフォルト: ja |
| currency | string | ❌ | 通貨: JPY/CNY/USD/TWD。デフォルト: JPY |
| callbackUrl | string | ❌ | WebhookコールバックURL（HTTPS必須） |
| callbackSecret | string | ❌ | Webhook署名検証用秘密鍵 |

**balanceModeの説明:**

- **"add"（加算モード）:** 既存の残高に `initialPoints` を加算
  - 例: 既存500pt → initialPoints: 1000 → 合計1500pt

- **"set"（設定モード）:** 残高を `initialPoints` に設定
  - 例: 既存500pt → initialPoints: 1000 → 合計1000pt

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "SLOT-107",
    "userId": "partner_user_12345",
    "initialPoints": 1000,
    "balanceMode": "set",
    "consumeImmediately": false,
    "lang": "ja",
    "currency": "JPY",
    "callbackUrl": "https://your-server.com/webhook/net8",
    "callbackSecret": "your_secret_key_here"
  }'
```

**成功レスポンス:**

```json
{
  "success": true,
  "sessionId": "sess_1738234567_23_abc123",
  "machineNo": 23,
  "modelId": "SLOT-107",
  "modelName": "北斗無双",
  "playUrl": "https://ifreamnet8-development.up.railway.app/ch/play_v2/?NO=23",
  "balance": {
    "current": 1000,
    "currency": "JPY"
  },
  "expiresAt": "2026-01-30T15:30:00Z"
}
```

---

### 2. ゲーム終了 API

**エンドポイント:** `POST /game_end.php`

**リクエストパラメータ:**

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| sessionId | string | ✅ | ゲームセッションID |
| result | string | ❌ | 結果: "win"/"lose"/"draw"/"completed"。デフォルト: "completed" |
| pointsWon | integer | ❌ | 獲得ポイント数 |
| totalBets | integer | ✅ | 総ベット額 |
| totalWins | integer | ✅ | 総勝利額 |
| resultData | object | ❌ | 追加の結果データ |

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_end.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "sess_1738234567_23_abc123",
    "result": "completed",
    "totalBets": 500,
    "totalWins": 650,
    "resultData": {
      "finalBalance": 1150,
      "gameTime": 1200,
      "totalSpins": 100
    }
  }'
```

**成功レスポンス:**

```json
{
  "success": true,
  "sessionId": "sess_1738234567_23_abc123",
  "finalBalance": 1150,
  "currency": "JPY",
  "summary": {
    "totalBets": 500,
    "totalWins": 650,
    "netProfit": 150,
    "gameTime": 1200
  }
}
```

---

### 3. ポイント追加 API

**エンドポイント:** `POST /add_points.php`

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/add_points.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "partner_user_12345",
    "points": 500,
    "currency": "JPY",
    "transactionId": "txn_20260130_001",
    "reason": "deposit"
  }'
```

**成功レスポンス:**

```json
{
  "success": true,
  "balance": {
    "current": 1500,
    "currency": "JPY"
  },
  "transactionId": "txn_20260130_001"
}
```

---

## Webhookセキュリティ

### HMAC-SHA256 署名検証

すべてのWebhookリクエストには、HMAC-SHA256署名が含まれています。必ずこの署名を検証してください。

### HTTPヘッダー

```
Content-Type: application/json
X-NET8-Signature: sha256={signature}
X-NET8-Timestamp: {unix_timestamp}
X-NET8-Event: {event_type}
```

### Node.js での署名検証例

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, callbackSecret) {
    const signature = req.headers['x-net8-signature'];
    const timestamp = req.headers['x-net8-timestamp'];
    const rawBody = JSON.stringify(req.body);

    // 期待される署名を計算
    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', callbackSecret)
        .update(rawBody)
        .digest('hex');

    // 署名を比較（タイミング攻撃対策）
    if (!crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    )) {
        throw new Error('無効な署名です');
    }

    // タイムスタンプ検証（リプレイ攻撃対策）
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) { // 5分
        throw new Error('タイムスタンプが古すぎるか未来すぎます');
    }

    return true;
}

// Express.js Webhookハンドラー
app.post('/webhook/net8', (req, res) => {
    try {
        verifyWebhookSignature(req, process.env.NET8_CALLBACK_SECRET);

        const { event, data } = req.body;

        switch (event) {
            case 'game.bet':
                console.log('ベット:', data.betAmount, 'JPY');
                // データベースに記録
                break;

            case 'game.win':
                console.log('勝利:', data.winAmount, 'JPY');
                // データベースに記録
                break;

            case 'game.ended':
                console.log('ゲーム終了:', data);
                // 最終精算処理
                break;
        }

        res.json({ success: true });
    } catch (error) {
        console.error('Webhookエラー:', error);
        res.status(400).json({ error: error.message });
    }
});
```

### PHP での署名検証例

```php
<?php
function verifyWebhookSignature($rawBody, $signature, $timestamp, $callbackSecret) {
    // 期待される署名を計算
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $callbackSecret);

    // 署名を比較
    if (!hash_equals($signature, $expectedSignature)) {
        throw new Exception('無効な署名です');
    }

    // タイムスタンプ検証（リプレイ攻撃対策）
    $now = time();
    if (abs($now - intval($timestamp)) > 300) { // 5分
        throw new Exception('タイムスタンプが古すぎるか未来すぎます');
    }

    return true;
}

// Webhook受信
$rawBody = file_get_contents('php://input');
$data = json_decode($rawBody, true);
$signature = $_SERVER['HTTP_X_NET8_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_NET8_TIMESTAMP'] ?? '';

try {
    verifyWebhookSignature($rawBody, $signature, $timestamp, getenv('NET8_CALLBACK_SECRET'));

    $event = $data['event'];
    $eventData = $data['data'];

    switch ($event) {
        case 'game.bet':
            error_log("ベット: " . $eventData['betAmount'] . " JPY");
            break;

        case 'game.win':
            error_log("勝利: " . $eventData['winAmount'] . " JPY");
            break;

        case 'game.ended':
            error_log("ゲーム終了");
            break;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

---

## エラーコード

| コード | HTTPステータス | 説明 |
|--------|---------------|------|
| INVALID_API_KEY | 401 | 無効または期限切れのAPIキー |
| UNAUTHORIZED | 401 | 認証ヘッダーがありません |
| MISSING_PARAMETER | 400 | 必須パラメータが不足しています |
| INVALID_CALLBACK_URL | 400 | コールバックURLはHTTPSである必要があります |
| MACHINE_NOT_AVAILABLE | 404 | 台が利用できません |
| INSUFFICIENT_BALANCE | 400 | 残高不足 |
| SESSION_NOT_FOUND | 404 | セッションが見つかりません |
| SESSION_EXPIRED | 400 | セッションの有効期限が切れています |
| INTERNAL_ERROR | 500 | 内部サーバーエラー |

### エラーレスポンス形式

```json
{
  "error": "ERROR_CODE",
  "message": "人間が読めるエラーメッセージ",
  "details": {
    "field": "追加の詳細情報"
  }
}
```

---

## コード例

### JavaScript（フロントエンド統合）

```javascript
class NET8ApiClient {
    constructor(apiKey, baseUrl = 'https://ifreamnet8-development.up.railway.app/api/v1') {
        this.apiKey = apiKey;
        this.baseUrl = baseUrl;
        this.token = null;
    }

    async authenticate() {
        const response = await fetch(`${this.baseUrl}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ apiKey: this.apiKey })
        });

        const data = await response.json();
        if (!data.success) throw new Error(data.message);

        this.token = data.token;
        return data;
    }

    async startGame(options) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/game_start.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(options)
        });

        return await response.json();
    }

    async endGame(sessionId, totalBets, totalWins) {
        const response = await fetch(`${this.baseUrl}/game_end.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ sessionId, totalBets, totalWins })
        });

        return await response.json();
    }
}

// 使用例
const client = new NET8ApiClient('your_api_key');

// ゲーム開始
const gameData = await client.startGame({
    modelId: 'SLOT-107',
    userId: 'user_12345',
    initialPoints: 1000,
    lang: 'ja',
    currency: 'JPY',
    callbackUrl: 'https://your-server.com/webhook/net8',
    callbackSecret: 'your_secret'
});

console.log('Session ID:', gameData.sessionId);

// iframeにゲームを読み込む
document.getElementById('game-iframe').src = gameData.playUrl;
```

---

## テスト方法

### 自動テストスクリプト

```bash
#!/bin/bash

BASE_URL="https://ifreamnet8-development.up.railway.app/api/v1"
API_KEY="your_api_key_here"

# 1. 認証
echo "1. 認証テスト"
JWT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth.php" \
  -H "Content-Type: application/json" \
  -d "{\"apiKey\": \"$API_KEY\"}")

JWT_TOKEN=$(echo "$JWT_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$JWT_TOKEN" ]; then
    echo "❌ 認証失敗"
    exit 1
fi

echo "✅ 認証成功: ${JWT_TOKEN:0:20}..."

# 2. 台一覧取得
echo ""
echo "2. 台一覧取得"
MACHINES=$(curl -s -X GET "$BASE_URL/list_machines.php?lang=ja" \
  -H "Authorization: Bearer $JWT_TOKEN")

echo "✅ 台一覧取得成功"

# 3. ゲーム開始
echo ""
echo "3. ゲーム開始"
GAME_START=$(curl -s -X POST "$BASE_URL/game_start.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "SLOT-107",
    "userId": "test_user_001",
    "initialPoints": 1000,
    "lang": "ja",
    "currency": "JPY"
  }')

SESSION_ID=$(echo "$GAME_START" | grep -o '"sessionId":"[^"]*' | cut -d'"' -f4)

if [ -z "$SESSION_ID" ]; then
    echo "❌ ゲーム開始失敗"
    exit 1
fi

echo "✅ ゲーム開始成功: $SESSION_ID"

echo ""
echo "=========================================="
echo "✅ すべてのテストが成功しました！"
echo "=========================================="
```

### ブラウザコンソールでのテスト

1. ゲーム開始後、ブラウザの開発者ツールを開く
2. Consoleタブで以下のログを確認:

```
✅ [Korea] Korea mode ENABLED!
🎰 [BET-CALLBACK] Called with: { betAmount: 10, ... }
📡 [BET-CALLBACK] Response status: 200
✅ [BET-CALLBACK] Success

🎉 [WIN-CALLBACK] Called with: { winAmount: 50, ... }
📡 [WIN-CALLBACK] Response status: 200
✅ [WIN-CALLBACK] Success
```

3. Networkタブで以下のリクエストを確認:
   - `auth.php` → 200 OK
   - `game_start.php` → 200 OK
   - `game_bet.php` → 200 OK（ベットごと）
   - `game_win.php` → 200 OK（勝利ごと）

---

## 本番環境デプロイ

### デプロイ前チェックリスト

- [ ] **APIキー** を環境変数に安全に保存
- [ ] **Webhook秘密鍵** を環境変数に安全に保存
- [ ] **HTTPS** をコールバックURLに使用
- [ ] **署名検証** をWebhookハンドラーに実装
- [ ] **エラー処理とリトライロジック** を実装
- [ ] **ログ記録** を監査用に設定
- [ ] **レート制限** を実装
- [ ] **負荷テスト** を完了
- [ ] **バックアップシステム** を配置
- [ ] **監視とアラート** を設定

### セキュリティベストプラクティス

1. **APIキーセキュリティ**
   - クライアント側のコードでAPIキーを公開しない
   - 環境変数に保存
   - 定期的にローテーション

2. **JWTトークン管理**
   - トークンは1時間で期限切れ
   - サーバー側に保存
   - URLにトークンを渡さない

3. **Webhookセキュリティ**
   - 常にHMAC-SHA256署名を検証
   - タイムスタンプをチェックしてリプレイ攻撃を防ぐ
   - コールバックURLにHTTPSを使用

4. **ネットワークセキュリティ**
   - すべてのAPIリクエストにHTTPSを使用
   - レート制限を実装
   - すべてのAPI呼び出しをログに記録
   - 異常な活動を監視

---

## サポート

技術的な質問やサポートが必要な場合:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技術ドキュメント:** https://docs.net8gaming.com

---

## 変更履歴

### v1.0.0 (2026-01-30)
- ✅ 初版リリース
- ✅ 全18 APIエンドポイント
- ✅ JWT認証システム
- ✅ リアルタイムコールバック
- ✅ HMAC-SHA256セキュリティ
- ✅ 多言語対応（ja/zh/ko/en）
- ✅ 多通貨対応（JPY/CNY/USD/TWD）

---

**© 2026 NET8 Gaming. All rights reserved.**
