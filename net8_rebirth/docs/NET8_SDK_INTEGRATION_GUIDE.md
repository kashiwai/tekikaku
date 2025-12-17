# Net8 パチンコゲームサービス SDK統合ガイド

## 概要

このドキュメントは、Net8パチンコゲームサービスを外部サービスに統合するための完全ガイドです。
初めてのエンジニアでも、ステップバイステップで実装できるよう設計されています。

---

## 目次

1. [アーキテクチャ概要](#1-アーキテクチャ概要)
2. [前提条件](#2-前提条件)
3. [Step 1: APIキーの取得](#step-1-apiキーの取得)
4. [Step 2: ゲーム開始API](#step-2-ゲーム開始api)
5. [Step 3: ゲーム画面の埋め込み](#step-3-ゲーム画面の埋め込み)
6. [Step 4: ゲーム終了・精算API](#step-4-ゲーム終了精算api)
7. [ポイントシステムの仕組み](#5-ポイントシステムの仕組み)
8. [決済連携ポイント](#6-決済連携ポイント)
9. [トラブルシューティング](#7-トラブルシューティング)
10. [サンプルコード](#8-サンプルコード)

---

## 1. アーキテクチャ概要

```
┌─────────────────────────────────────────────────────────────────┐
│                        あなたのサービス                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │   フロント    │    │  バックエンド  │    │   決済処理   │      │
│  │   (HTML)     │───▶│   (API)      │◀──▶│  (Stripe等)  │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│         │                   │                                   │
│         │ iframe            │ API呼び出し                       │
│         ▼                   ▼                                   │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      Net8 ゲームサービス                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐      │
│  │  play_embed  │    │   PHP API    │    │   カメラPC   │      │
│  │ (ゲーム画面)  │◀──▶│  (認証/残高) │◀──▶│  (映像配信)  │      │
│  └──────────────┘    └──────────────┘    └──────────────┘      │
│         ▲                                       │               │
│         │              WebRTC                   │               │
│         └───────────────────────────────────────┘               │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
```

### 通信フロー

```
1. ユーザーがゲーム開始ボタンをクリック
2. あなたのバックエンド → Net8 game_start API を呼び出し
3. Net8がセッションIDとWebRTC情報を返却
4. フロントエンドにiframeでplay_embedを埋め込み
5. play_embedがWebRTCでカメラPCに接続、映像配信開始
6. ゲーム終了時、あなたのバックエンド → Net8 game_end API を呼び出し
7. ポイント精算完了
```

---

## 2. 前提条件

### 必要なもの

| 項目 | 説明 |
|------|------|
| APIキー | Net8から発行されるAPIキー（例: `pk_live_xxxx`） |
| HTTPSサーバー | iframeはHTTPS環境が必須 |
| サーバーサイド言語 | PHP, Node.js, Python等（API呼び出し用） |

### Net8 APIエンドポイント

| 環境 | ベースURL |
|------|----------|
| 本番 | `https://mgg-webservice-production.up.railway.app` |
| テスト | `https://mgg-webservice-staging.up.railway.app` |

---

## Step 1: APIキーの取得

### 1.1 APIキーの種類

| キータイプ | 用途 | 例 |
|-----------|------|-----|
| `pk_live_*` | 本番環境用 | `pk_live_abc123xyz` |
| `pk_test_*` | テスト環境用 | `pk_test_demo12345` |
| `pk_demo_*` | デモ用（制限あり） | `pk_demo_12345` |

### 1.2 APIキーの使い方

すべてのAPI呼び出しで、Authorizationヘッダーにキーを設定します：

```
Authorization: Bearer pk_live_your_api_key_here
```

---

## Step 2: ゲーム開始API

### 2.1 エンドポイント

```
POST /api/v1/game_start.php
```

### 2.2 リクエスト

```json
{
  "userId": "your_user_123",
  "modelId": "HOKUTO4GO",
  "machineNo": "1",
  "pointsToConsume": 100,
  "initialPoints": 5000
}
```

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| userId | string | ○ | あなたのサービスのユーザーID |
| modelId | string | ○ | ゲーム機種コード |
| machineNo | string | △ | 台番号（指定しない場合は自動割当） |
| pointsToConsume | int | △ | 1ゲームで消費するポイント（デフォルト: 100） |
| initialPoints | int | △ | 初期チャージポイント（決済後のポイント） |

### 2.3 レスポンス

```json
{
  "success": true,
  "sessionId": "sess_abc123_1702900000",
  "userId": "your_user_123",
  "modelId": "HOKUTO4GO",
  "machineNo": 1,
  "memberNo": 12345,
  "newBalance": 4900,
  "signaling": {
    "host": "mgg-signaling-production.up.railway.app",
    "port": 443,
    "secure": true
  },
  "camera": {
    "peerId": "camera_10000021_1765859502",
    "machineNo": 1
  }
}
```

### 2.4 実装例（Node.js）

```javascript
async function startGame(userId, modelId, points) {
  const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_start.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer pk_live_your_api_key',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      modelId: modelId,
      initialPoints: points,
      pointsToConsume: 100
    })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error(data.message || 'ゲーム開始に失敗しました');
  }

  return data;
}
```

### 2.5 実装例（PHP）

```php
function startGame($userId, $modelId, $points) {
    $apiKey = 'pk_live_your_api_key';
    $url = 'https://mgg-webservice-production.up.railway.app/api/v1/game_start.php';

    $data = [
        'userId' => $userId,
        'modelId' => $modelId,
        'initialPoints' => $points,
        'pointsToConsume' => 100
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
```

---

## Step 3: ゲーム画面の埋め込み

### 3.1 iframe URL構造

```
https://mgg-webservice-production.up.railway.app/play_embed/?session_id={sessionId}&member_no={memberNo}
```

### 3.2 基本的な埋め込み

```html
<iframe
  id="net8-game-frame"
  src="https://mgg-webservice-production.up.railway.app/play_embed/?session_id=sess_abc123&member_no=12345"
  width="800"
  height="600"
  frameborder="0"
  allow="autoplay; fullscreen"
  allowfullscreen
></iframe>
```

### 3.3 レスポンシブ対応

```html
<style>
  .game-container {
    position: relative;
    width: 100%;
    max-width: 800px;
    aspect-ratio: 4/3;
  }
  .game-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
  }
</style>

<div class="game-container">
  <iframe
    id="net8-game-frame"
    src="..."
    allow="autoplay; fullscreen"
    allowfullscreen
  ></iframe>
</div>
```

### 3.4 iframe通信（postMessage）

play_embedはpostMessageでイベントを送信します：

```javascript
// ゲーム画面からのメッセージを受信
window.addEventListener('message', function(event) {
  // オリジン確認（セキュリティ）
  if (event.origin !== 'https://mgg-webservice-production.up.railway.app') {
    return;
  }

  const data = event.data;

  switch (data.type) {
    case 'GAME_READY':
      console.log('ゲーム準備完了');
      break;

    case 'CREDIT_CHANGED':
      console.log('クレジット変更:', data.credits);
      updateCreditDisplay(data.credits);
      break;

    case 'GAME_END':
      console.log('ゲーム終了:', data.result);
      handleGameEnd(data);
      break;

    case 'ERROR':
      console.error('エラー:', data.message);
      break;
  }
});
```

### 3.5 親ページからiframeへコマンド送信

```javascript
// ゲーム終了コマンドを送信
function sendEndGameCommand() {
  const iframe = document.getElementById('net8-game-frame');
  iframe.contentWindow.postMessage({
    type: 'END_GAME',
    reason: 'user_request'
  }, 'https://mgg-webservice-production.up.railway.app');
}

// クレジット追加コマンド（チャージ後）
function sendAddCredits(amount) {
  const iframe = document.getElementById('net8-game-frame');
  iframe.contentWindow.postMessage({
    type: 'ADD_CREDITS',
    amount: amount
  }, 'https://mgg-webservice-production.up.railway.app');
}
```

---

## Step 4: ゲーム終了・精算API

### 4.1 エンドポイント

```
POST /api/v1/game_end.php
```

### 4.2 リクエスト

```json
{
  "sessionId": "sess_abc123_1702900000",
  "result": "completed",
  "pointsWon": 2500,
  "resultData": {
    "credit": 250,
    "play_count": 1500,
    "bb_count": 3,
    "rb_count": 5
  }
}
```

| パラメータ | 型 | 必須 | 説明 |
|-----------|-----|------|------|
| sessionId | string | ○ | game_startで取得したセッションID |
| result | string | ○ | 結果（completed/cancelled/error） |
| pointsWon | int | ○ | 獲得ポイント（精算ポイント） |
| resultData | object | △ | 詳細データ（統計用） |

### 4.3 レスポンス

```json
{
  "success": true,
  "sessionId": "sess_abc123_1702900000",
  "result": "completed",
  "pointsConsumed": 100,
  "pointsWon": 2500,
  "netProfit": 2400,
  "newBalance": 7400,
  "playDuration": 1800,
  "transaction": {
    "id": "txn_xyz789_1702901800",
    "amount": 2500,
    "balanceBefore": 4900,
    "balanceAfter": 7400
  }
}
```

### 4.4 実装例（Node.js）

```javascript
async function endGame(sessionId, pointsWon) {
  const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/game_end.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer pk_live_your_api_key',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      sessionId: sessionId,
      result: 'completed',
      pointsWon: pointsWon
    })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error(data.message || '精算に失敗しました');
  }

  return data;
}
```

---

## 5. ポイントシステムの仕組み

### 5.1 重要な概念

Net8には2つのポイント管理テーブルがあります：

```
┌─────────────────────────────────────────────────────────────┐
│                    ポイントシステム構造                       │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌─────────────────┐          ┌─────────────────┐          │
│  │  user_balances  │◀────────▶│ mst_member.point │          │
│  │  (SDK管理用)    │   同期    │  (カメラ参照用)  │          │
│  └─────────────────┘          └─────────────────┘          │
│         ▲                              ▲                    │
│         │                              │                    │
│    API経由で更新                  ゲーム機が参照              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 ポイントフロー

```
1. 決済完了
   ↓
2. game_start API呼び出し（initialPoints: 決済額）
   ↓
3. Net8が両テーブルにポイントを追加
   - user_balances.balance += initialPoints
   - mst_member.point += initialPoints
   ↓
4. ゲームプレイ（ポイント消費・獲得）
   ↓
5. game_end API呼び出し（pointsWon: 精算額）
   ↓
6. Net8が両テーブルを更新
   - user_balances.balance += pointsWon
   - mst_member.point += pointsWon
```

### 5.3 残高取得API

```
GET /api/v1/balance.php?userId={userId}
```

レスポンス：
```json
{
  "success": true,
  "userId": "your_user_123",
  "balance": 7400,
  "totalDeposited": 10000,
  "totalConsumed": 5000,
  "totalWon": 2400
}
```

---

## 6. 決済連携ポイント

### 6.1 決済フロー全体像

```
┌─────────────────────────────────────────────────────────────┐
│                       決済フロー                             │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  1. ユーザーが金額選択                                       │
│     ↓                                                       │
│  2. 決済処理（Stripe/PayPal等）                              │
│     ↓                                                       │
│  3. Webhook受信 → ポイント付与                               │
│     ↓                                                       │
│  4. game_start API（initialPoints: 購入ポイント）            │
│     ↓                                                       │
│  5. ゲームプレイ                                             │
│     ↓                                                       │
│  6. game_end API → 精算                                     │
│     ↓                                                       │
│  7. 出金リクエスト（オプション）                              │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

### 6.2 Stripe連携例

```javascript
// 1. Stripeチェックアウトセッション作成
app.post('/api/create-checkout', async (req, res) => {
  const { amount, userId } = req.body;

  const session = await stripe.checkout.sessions.create({
    payment_method_types: ['card'],
    line_items: [{
      price_data: {
        currency: 'jpy',
        product_data: {
          name: 'ゲームポイント',
        },
        unit_amount: amount,
      },
      quantity: 1,
    }],
    mode: 'payment',
    success_url: `${YOUR_DOMAIN}/game?session_id={CHECKOUT_SESSION_ID}`,
    cancel_url: `${YOUR_DOMAIN}/cancel`,
    metadata: {
      userId: userId,
      points: amount  // 1円 = 1ポイント
    }
  });

  res.json({ sessionId: session.id });
});

// 2. Webhook: 決済完了時にNet8にポイント追加
app.post('/webhook/stripe', async (req, res) => {
  const event = req.body;

  if (event.type === 'checkout.session.completed') {
    const session = event.data.object;
    const { userId, points } = session.metadata;

    // Net8にポイントをデポジット
    await depositToNet8(userId, parseInt(points));
  }

  res.json({ received: true });
});

// 3. Net8へのデポジット
async function depositToNet8(userId, points) {
  // game_start APIでinitialPointsとして送信
  // または専用のdeposit APIを使用
  const response = await fetch('https://mgg-webservice-production.up.railway.app/api/v1/deposit.php', {
    method: 'POST',
    headers: {
      'Authorization': 'Bearer pk_live_your_api_key',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      amount: points,
      source: 'stripe',
      transactionId: session.payment_intent
    })
  });

  return response.json();
}
```

### 6.3 決済プロバイダー対応表

| プロバイダー | 対応状況 | 連携方法 |
|-------------|---------|---------|
| Stripe | ○ 推奨 | Webhook + API |
| PayPal | ○ | IPN + API |
| クレジットカード直接 | △ | PCI DSS準拠必須 |
| コンビニ決済 | ○ | GMO等経由 |
| キャリア決済 | ○ | 各キャリアAPI |
| 暗号通貨 | △ | 要相談 |

### 6.4 出金（払い戻し）フロー

```javascript
// 出金リクエストAPI
app.post('/api/withdraw', async (req, res) => {
  const { userId, amount, bankAccount } = req.body;

  // 1. Net8の残高確認
  const balance = await getNet8Balance(userId);

  if (balance < amount) {
    return res.status(400).json({ error: '残高不足' });
  }

  // 2. Net8からポイントを減算
  await withdrawFromNet8(userId, amount);

  // 3. 銀行振込処理（または決済プロバイダーの出金API）
  await processBankTransfer(bankAccount, amount);

  res.json({ success: true });
});
```

### 6.5 連携拡張ポイント

```
┌─────────────────────────────────────────────────────────────┐
│                    拡張ポイント一覧                          │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  【入金系】                                                  │
│  ├─ /api/v1/deposit.php      - ポイント入金                 │
│  ├─ /api/v1/game_start.php   - initialPointsで入金          │
│  └─ Webhook連携              - 決済完了通知                  │
│                                                             │
│  【出金系】                                                  │
│  ├─ /api/v1/withdraw.php     - ポイント出金                 │
│  ├─ /api/v1/game_end.php     - pointsWonで精算              │
│  └─ /api/v1/transfer.php     - 他ユーザーへ送金             │
│                                                             │
│  【残高管理】                                                │
│  ├─ /api/v1/balance.php      - 残高照会                     │
│  ├─ /api/v1/history.php      - 取引履歴                     │
│  └─ /api/v1/statement.php    - 明細書出力                   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 7. トラブルシューティング

### 7.1 よくあるエラー

| エラー | 原因 | 解決策 |
|--------|------|--------|
| `UNAUTHORIZED` | APIキーが無効 | APIキーを確認 |
| `INSUFFICIENT_BALANCE` | 残高不足 | initialPointsで入金 |
| `SESSION_NOT_FOUND` | セッション期限切れ | 新規game_start |
| `MACHINE_NOT_AVAILABLE` | 台が使用中 | 別の台を指定 |
| `残高がない` | mst_member.point未同期 | サポートに連絡 |

### 7.2 iframe表示されない

```
□ HTTPSで配信しているか確認
□ X-Frame-Options ヘッダー確認
□ CSP (Content-Security-Policy) 確認
□ ブラウザのコンソールでエラー確認
```

### 7.3 WebRTC接続失敗

```
□ STUN/TURNサーバーへの接続確認
□ ファイアウォール設定確認
□ WebRTC対応ブラウザか確認
□ シグナリングサーバーの状態確認
```

### 7.4 デバッグモード

テスト時は以下のクエリパラメータを追加：

```
/play_embed/?session_id=xxx&member_no=xxx&debug=1
```

コンソールに詳細ログが出力されます。

---

## 8. サンプルコード

### 8.1 完全な実装例（Next.js）

```typescript
// pages/api/game/start.ts
import { NextApiRequest, NextApiResponse } from 'next';

const NET8_API_BASE = process.env.NET8_API_BASE_URL;
const NET8_API_KEY = process.env.NET8_API_KEY;

export default async function handler(req: NextApiRequest, res: NextApiResponse) {
  if (req.method !== 'POST') {
    return res.status(405).json({ error: 'Method not allowed' });
  }

  const { userId, modelId, points } = req.body;

  try {
    const response = await fetch(`${NET8_API_BASE}/api/v1/game_start.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${NET8_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        userId,
        modelId,
        initialPoints: points,
        pointsToConsume: 100,
      }),
    });

    const data = await response.json();

    if (!data.success) {
      throw new Error(data.message);
    }

    // iframeのURLを生成
    const iframeUrl = `${NET8_API_BASE}/play_embed/?session_id=${data.sessionId}&member_no=${data.memberNo}`;

    res.json({
      ...data,
      iframeUrl,
    });

  } catch (error) {
    res.status(500).json({ error: error.message });
  }
}
```

### 8.2 フロントエンド（React）

```tsx
// components/GamePlayer.tsx
import { useState, useEffect, useRef } from 'react';

interface GamePlayerProps {
  userId: string;
  modelId: string;
  initialPoints: number;
  onGameEnd: (result: any) => void;
}

export function GamePlayer({ userId, modelId, initialPoints, onGameEnd }: GamePlayerProps) {
  const [iframeUrl, setIframeUrl] = useState<string | null>(null);
  const [sessionId, setSessionId] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const iframeRef = useRef<HTMLIFrameElement>(null);

  // ゲーム開始
  useEffect(() => {
    async function startGame() {
      try {
        const response = await fetch('/api/game/start', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ userId, modelId, points: initialPoints }),
        });

        const data = await response.json();

        if (!data.success) {
          throw new Error(data.error);
        }

        setSessionId(data.sessionId);
        setIframeUrl(data.iframeUrl);

      } catch (err) {
        setError(err.message);
      }
    }

    startGame();
  }, [userId, modelId, initialPoints]);

  // iframeからのメッセージ受信
  useEffect(() => {
    function handleMessage(event: MessageEvent) {
      if (event.origin !== process.env.NEXT_PUBLIC_NET8_ORIGIN) return;

      if (event.data.type === 'GAME_END') {
        handleGameEnd(event.data);
      }
    }

    window.addEventListener('message', handleMessage);
    return () => window.removeEventListener('message', handleMessage);
  }, [sessionId]);

  // ゲーム終了処理
  async function handleGameEnd(data: any) {
    try {
      const response = await fetch('/api/game/end', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          sessionId,
          result: 'completed',
          pointsWon: data.credits * 10,  // クレジット→ポイント変換
        }),
      });

      const result = await response.json();
      onGameEnd(result);

    } catch (err) {
      setError(err.message);
    }
  }

  if (error) {
    return <div className="error">エラー: {error}</div>;
  }

  if (!iframeUrl) {
    return <div className="loading">ゲームを読み込み中...</div>;
  }

  return (
    <div className="game-container">
      <iframe
        ref={iframeRef}
        src={iframeUrl}
        width="800"
        height="600"
        allow="autoplay; fullscreen"
        allowFullScreen
      />
      <button onClick={() => handleGameEnd({ credits: 0 })}>
        ゲーム終了
      </button>
    </div>
  );
}
```

---

## 付録: API一覧

| エンドポイント | メソッド | 説明 |
|---------------|---------|------|
| `/api/v1/game_start.php` | POST | ゲーム開始 |
| `/api/v1/game_end.php` | POST | ゲーム終了・精算 |
| `/api/v1/balance.php` | GET | 残高照会 |
| `/api/v1/deposit.php` | POST | ポイント入金 |
| `/api/v1/withdraw.php` | POST | ポイント出金 |
| `/api/v1/history.php` | GET | 取引履歴 |
| `/api/v1/machines.php` | GET | 利用可能台一覧 |
| `/api/v1/models.php` | GET | 機種一覧 |
| `/play_embed/` | GET | ゲーム画面（iframe用） |

---

## お問い合わせ

技術サポート: support@net8.example.com
APIキー発行: api@net8.example.com

---

*最終更新: 2025-12-18*
*バージョン: 1.0.0*
