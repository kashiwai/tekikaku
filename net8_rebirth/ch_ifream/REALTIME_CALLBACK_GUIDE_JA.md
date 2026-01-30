# リアルタイムゲームデータ連携ガイド（日本語版）

**バージョン:** 1.0.0
**最終更新:** 2026年1月30日
**対象:** 外部パートナー（中国・韓国・その他海外）

---

## 概要

NET8のゲームプレイ中、**毎回1ゲームごとにリアルタイムでデータがAPI経由で自動的に取得可能**です！

本ガイドでは、実装済みのリアルタイムコールバックシステムについて詳しく説明します。

---

## ✅ 実装済み機能

### 自動送信されるイベント

| イベント | API Endpoint | タイミング | 送信方法 |
|---------|-------------|-----------|---------|
| 🎰 **ベット** | `/api/v1/game_bet.php` | プレイヤーがベットするたび | 自動送信 |
| 🎉 **勝利** | `/api/v1/game_win.php` | プレイヤーが勝利するたび | 自動送信 |
| 🏁 **ゲーム終了** | `window.parent.postMessage` | ゲーム精算時 | postMessage |

---

## 🔍 実装コード確認

### 1. ベットコールバック（自動送信）

**実装ファイル:** `/ch/play_v2/js/view_auth_pachi.js`（167行目）

**動作:** プレイヤーがベットするたびに、自動的に `/api/v1/game_bet.php` が呼び出されます。

```javascript
function sendBetCallback(betAmount, creditBefore, creditAfter) {
    console.log('🎰 [BET-CALLBACK] Called with:', {
        betAmount: betAmount,
        creditBefore: creditBefore,
        creditAfter: creditAfter
    });

    // sessionIdとkoreaModeが有効な時のみ送信
    if (typeof sessionId === 'undefined' || !sessionId || !koreaMode) {
        console.error('❌ [BET-CALLBACK] SKIPPED!');
        return;
    }

    // 総ベット額を累計
    game.totalBets = (game.totalBets || 0) + betAmount;

    // リアルタイムAPI送信
    fetch('/api/v1/game_bet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sessionId: sessionId,
            betAmount: betAmount,
            creditBefore: creditBefore,
            creditAfter: creditAfter
        })
    }).then(function(res) {
        console.log('📡 [BET-CALLBACK] Response status:', res.status);
        return res.json();
    }).then(function(data) {
        console.log('✅ [BET-CALLBACK] Success:', data);
    }).catch(function(err) {
        console.error('❌ [BET-CALLBACK] Failed:', err);
    });
}
```

**送信データ:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "betAmount": 10,
  "creditBefore": 1000,
  "creditAfter": 990
}
```

**API処理後:**
- サーバー側で `/api/v1/game_bet.php` が処理
- Webhookで `game.bet` イベントがパートナーサーバーに送信される

---

### 2. 勝利コールバック（自動送信）

**実装ファイル:** `/ch/play_v2/js/view_auth_pachi.js`（216行目）

**動作:** プレイヤーが勝利するたびに、自動的に `/api/v1/game_win.php` が呼び出されます。

```javascript
function sendWinCallback(winAmount, creditBefore, creditAfter) {
    console.log('🎰 [WIN-CALLBACK] Called with:', {
        winAmount: winAmount,
        creditBefore: creditBefore,
        creditAfter: creditAfter
    });

    if (typeof sessionId === 'undefined' || !sessionId || !koreaMode) {
        console.error('❌ [WIN-CALLBACK] SKIPPED!');
        return;
    }

    // 総勝利額を累計
    game.totalWins = (game.totalWins || 0) + winAmount;

    // リアルタイムAPI送信
    fetch('/api/v1/game_win.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            sessionId: sessionId,
            winAmount: winAmount,
            creditBefore: creditBefore,
            creditAfter: creditAfter
        })
    }).then(function(res) {
        console.log('📡 [WIN-CALLBACK] Response status:', res.status);
        return res.json();
    }).then(function(data) {
        console.log('✅ [WIN-CALLBACK] Success:', data);
    }).catch(function(err) {
        console.error('❌ [WIN-CALLBACK] Failed:', err);
    });
}
```

**送信データ:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "winAmount": 50,
  "creditBefore": 990,
  "creditAfter": 1040
}
```

**API処理後:**
- サーバー側で `/api/v1/game_win.php` が処理
- Webhookで `game.win` イベントがパートナーサーバーに送信される

---

### 3. ゲーム終了通知（postMessage）

**実装ファイル:** `/ch/play_v2/js/view_auth_pachi.js`（1240行目）

**動作:** ゲーム精算時に、親ウィンドウに `postMessage` で通知されます。

```javascript
// ゲーム精算時にpostMessageで親ウィンドウに送信
window.parent.postMessage({
    type: 'game:settlement',
    payload: {
        playPoint: finalPlayPoint,
        credit: finalCredit,
        drawPoint: finalDrawPoint,
        totalDrawPoint: finalTotalDrawPoint,
        result: 'completed',
        totalBets: game.totalBets || 0,
        totalWins: game.totalWins || 0
    }
}, '*');
```

**送信データ:**
```json
{
  "type": "game:settlement",
  "payload": {
    "playPoint": 1050,
    "credit": 0,
    "drawPoint": 1050,
    "totalDrawPoint": 1050,
    "result": "completed",
    "totalBets": 100,
    "totalWins": 150
  }
}
```

---

## 🔑 重要: koreaMode フラグ

リアルタイムコールバック（`game_bet.php`、`game_win.php`）は、**`koreaMode = true`** の時のみ送信されます！

### koreaModeが有効化される条件

**実装ファイル:** `/ch/play_v2/js/view_auth_pachi.js`（1095行目）

```javascript
if (game.playpoint > 0 && _sconnect && _sconnect.open) {
    console.log('💰 [Korea] Syncing playpoint to camera:', game.playpoint);
    _sconnect.send(_sendStr('Spt', game.playpoint));
    koreaMode = true;  // 韓国モードを有効化
    console.log('✅ [Korea] Korea mode ENABLED!', {
        koreaMode: koreaMode,
        sessionId: sessionId
    });
}
```

**有効化条件:**
1. ✅ `game.playpoint > 0` - プレイポイントが0より大きい
2. ✅ `_sconnect` が存在する - 接続が存在
3. ✅ `_sconnect.open` が true - 接続が開いている

**これらの条件を自動的に満たす方法:**

`game_start.php` を呼び出す際に、以下のパラメータを設定してください：

```javascript
await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        modelId: 'SLOT-107',
        userId: 'partner_user_001',
        initialPoints: 1000,  // 必ず0より大きい値を設定
        callbackUrl: 'https://your-server.com/webhook/net8',  // 必須
        callbackSecret: 'your_webhook_secret'  // 必須
    })
});
```

**重要:** `callbackUrl` と `callbackSecret` を設定することで、`koreaMode` が自動的に有効化され、リアルタイムコールバックが動作します。

**注意:** 名前は「韓国モード」ですが、実際には**外部パートナー統合モード**として機能します。中国や他の海外パートナーも同じ仕組みを利用できます。

---

## 📊 データフロー図

```
┌─────────────────────────────────────────────────────────────┐
│  1. ゲーム開始 / Game Start                                   │
│     POST /api/v1/game_start.php                             │
│     {                                                       │
│       modelId, userId, initialPoints,                       │
│       callbackUrl, callbackSecret  ← 必須                   │
│     }                                                       │
│     ↓                                                       │
│     sessionId取得 & koreaMode自動有効化                      │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  2. ゲームプレイ中（リアルタイム自動送信）                      │
│                                                             │
│  プレイヤーがベット                                           │
│  ↓ (自動 / Automatic)                                       │
│  JavaScript: sendBetCallback() 呼び出し                      │
│  ↓                                                          │
│  POST /api/v1/game_bet.php                                  │
│  { sessionId, betAmount, creditBefore, creditAfter }        │
│  ↓                                                          │
│  サーバー処理 → Webhook送信                                  │
│  ↓                                                          │
│  パートナーサーバー受信                                       │
│  {                                                          │
│    event: 'game.bet',                                       │
│    data: { sessionId, betAmount, balance }                  │
│  }                                                          │
│                                                             │
│  ─────────────────────────────────────                      │
│                                                             │
│  プレイヤーが勝利                                             │
│  ↓ (自動 / Automatic)                                       │
│  JavaScript: sendWinCallback() 呼び出し                      │
│  ↓                                                          │
│  POST /api/v1/game_win.php                                  │
│  { sessionId, winAmount, creditBefore, creditAfter }        │
│  ↓                                                          │
│  サーバー処理 → Webhook送信                                  │
│  ↓                                                          │
│  パートナーサーバー受信                                       │
│  {                                                          │
│    event: 'game.win',                                       │
│    data: { sessionId, winAmount, balance }                  │
│  }                                                          │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  3. ゲーム終了 / Game End                                     │
│                                                             │
│  プレイヤーが精算                                             │
│  ↓                                                          │
│  JavaScript: window.parent.postMessage                      │
│  {                                                          │
│    type: 'game:settlement',                                 │
│    payload: { totalBets, totalWins, finalBalance, ... }     │
│  }                                                          │
│  ↓                                                          │
│  【パートナー側】postMessageを受け取る                         │
│  window.addEventListener('message', ...)                    │
│  ↓                                                          │
│  【パートナー側】game_end.phpを呼び出す                         │
│  POST /api/v1/game_end.php                                  │
│  {                                                          │
│    sessionId, totalBets, totalWins, result                  │
│  }                                                          │
│  ↓                                                          │
│  サーバー処理 → 最終Webhook送信                              │
│  ↓                                                          │
│  パートナーサーバー受信（最終精算）                            │
│  {                                                          │
│    event: 'game.ended',                                     │
│    data: { finalBalance, totalBets, totalWins, netProfit }  │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 💡 パートナー実装ガイド

### ステップ 1: game_start.phpでsessionIdを取得

```javascript
const gameStartResponse = await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        modelId: 'SLOT-107',
        userId: 'partner_user_001',
        initialPoints: 1000,  // 0より大きい値
        balanceMode: 'set',
        lang: 'ja',
        currency: 'JPY',
        callbackUrl: 'https://your-server.com/webhook/net8',  // 必須！
        callbackSecret: 'your_webhook_secret_key'  // 必須！
    })
});

const { sessionId, playUrl } = await gameStartResponse.json();
console.log('✅ Session ID:', sessionId);

// iframeにゲームをロード
document.getElementById('game-iframe').src = playUrl;
```

**重要:** `callbackUrl` と `callbackSecret` を必ず設定してください。これにより`koreaMode`が自動的に有効化され、リアルタイムコールバックが動作します。

---

### ステップ 2: Webhookでリアルタイムデータを受信

**Node.js Express サーバー例:**

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.use(express.json({
    verify: (req, res, buf) => {
        req.rawBody = buf.toString('utf8');
    }
}));

const CALLBACK_SECRET = process.env.NET8_CALLBACK_SECRET;

// Webhook署名検証
function verifyWebhookSignature(req) {
    const signature = req.headers['x-net8-signature'];
    const timestamp = req.headers['x-net8-timestamp'];

    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', CALLBACK_SECRET)
        .update(req.rawBody)
        .digest('hex');

    if (!crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    )) {
        throw new Error('無効な署名');
    }

    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        throw new Error('タイムスタンプが古すぎます');
    }

    return true;
}

// Webhookエンドポイント
app.post('/webhook/net8', async (req, res) => {
    try {
        verifyWebhookSignature(req);

        const { event, data } = req.body;

        switch (event) {
            case 'game.bet':
                console.log('🎰 ベット:', data.betAmount, 'JPY');
                // データベースに記録
                await db.recordBet({
                    sessionId: data.sessionId,
                    betAmount: data.betAmount,
                    balance: data.balance
                });
                break;

            case 'game.win':
                console.log('🎉 勝利:', data.winAmount, 'JPY');
                // データベースに記録
                await db.recordWin({
                    sessionId: data.sessionId,
                    winAmount: data.winAmount,
                    balance: data.balance
                });
                break;

            case 'game.ended':
                console.log('🏁 ゲーム終了:', {
                    totalBets: data.totalBets,
                    totalWins: data.totalWins,
                    netProfit: data.netProfit
                });
                // 最終精算
                await db.finalizeSession(data);
                break;
        }

        res.json({ success: true });
    } catch (error) {
        console.error('Webhookエラー:', error);
        res.status(400).json({ error: error.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Webhookサーバー起動: ポート${PORT}`);
});
```

---

### ステップ 3: postMessageでゲーム終了を検知

```javascript
// 親ウィンドウでpostMessageをリッスン
window.addEventListener('message', async (event) => {
    // セキュリティ: オリジンを検証
    if (event.origin !== 'https://ifreamnet8-development.up.railway.app') {
        console.warn('不正なオリジンからのメッセージ:', event.origin);
        return;
    }

    const { type, payload } = event.data;

    if (type === 'game:settlement') {
        console.log('🏁 ゲーム終了通知を受信:', payload);

        try {
            // game_end.phpを呼び出す
            const gameEndResponse = await fetch('/api/v1/game_end.php', {
                method: 'POST',
                headers: {
                    'Authorization': `Bearer ${jwtToken}`,
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    sessionId: currentSessionId,
                    result: payload.result || 'completed',
                    totalBets: payload.totalBets || 0,
                    totalWins: payload.totalWins || 0,
                    resultData: {
                        finalPlayPoint: payload.playPoint,
                        finalCredit: payload.credit,
                        finalDrawPoint: payload.drawPoint
                    }
                })
            });

            const result = await gameEndResponse.json();
            console.log('✅ ゲーム終了API呼び出し成功:', result);

            // UIを更新
            updateBalanceDisplay(result.finalBalance);

            // iframeを非表示
            document.getElementById('game-iframe').style.display = 'none';

        } catch (error) {
            console.error('❌ ゲーム終了処理エラー:', error);
        }
    }
});
```

---

## 🧪 テスト方法

### ブラウザコンソールで確認

1. ゲーム開始後、ブラウザの開発者ツールを開く（F12）
2. Consoleタブで以下のログを確認:

```
✅ [Korea] Korea mode ENABLED!
   koreaMode: true
   sessionId: "sess_1738234567_23_abc123"

🎰 [BET-CALLBACK] Called with: { betAmount: 10, creditBefore: 1000, creditAfter: 990 }
📡 [BET-CALLBACK] Response status: 200
✅ [BET-CALLBACK] Success: { success: true, ... }

🎉 [WIN-CALLBACK] Called with: { winAmount: 50, creditBefore: 990, creditAfter: 1040 }
📡 [WIN-CALLBACK] Response status: 200
✅ [WIN-CALLBACK] Success: { success: true, ... }

[DEBUG] Sending game:settlement postMessage: { totalBets: 100, totalWins: 150, ... }
```

### Networkタブで確認

1. Networkタブを開く
2. ゲームプレイ中に以下のAPIリクエストを確認:
   - `game_bet.php` → 200 OK（ベットごと）
   - `game_win.php` → 200 OK（勝利ごと）
   - `game_end.php` → 200 OK（ゲーム終了時）

### Webhookログで確認

サーバー側のログで以下を確認:

```
📨 Received webhook: game.bet
📋 Data: { sessionId: "sess_...", betAmount: 10, balance: 990 }

📨 Received webhook: game.win
📋 Data: { sessionId: "sess_...", winAmount: 50, balance: 1040 }

📨 Received webhook: game.ended
📋 Data: { sessionId: "sess_...", totalBets: 100, totalWins: 150, netProfit: 50 }
```

---

## ⚠️ トラブルシューティング

### 問題 1: コールバックが送信されない

**症状:** `game_bet.php` や `game_win.php` が呼ばれない

**原因と解決方法:**

| 原因 | 確認方法 | 解決方法 |
|------|---------|---------|
| `koreaMode = false` | コンソールで `koreaMode` を確認 | `callbackUrl` と `callbackSecret` を設定 |
| `sessionId` が未定義 | コンソールで `sessionId` を確認 | `game_start.php` を正しく呼び出す |
| `initialPoints = 0` | リクエストパラメータを確認 | `initialPoints` を1以上に設定 |

**デバッグコマンド（ブラウザコンソール）:**

```javascript
// 現在のkoreaMode状態を確認
console.log('koreaMode:', typeof koreaMode !== 'undefined' ? koreaMode : 'UNDEFINED');
console.log('sessionId:', typeof sessionId !== 'undefined' ? sessionId : 'UNDEFINED');
```

---

### 問題 2: Webhookが受信されない

**症状:** サーバー側でWebhookが受信されない

**確認事項:**

1. **callbackUrlがHTTPSか確認**
   - 本番環境では必須
   - ローカルテストは `http://localhost` のみ許可

2. **署名検証が正しいか確認**
   - HMAC-SHA256アルゴリズムを使用
   - `callbackSecret` が一致しているか

3. **ファイアウォール設定を確認**
   - NET8のIPアドレスからの接続を許可

---

## ✅ 実装チェックリスト

パートナー実装前の確認事項:

### 必須項目

- [ ] `game_start.php` で `callbackUrl` を設定（HTTPS）
- [ ] `game_start.php` で `callbackSecret` を設定
- [ ] `initialPoints > 0` を確保
- [ ] Webhook署名検証を実装（HMAC-SHA256）
- [ ] `window.addEventListener('message')` を実装
- [ ] postMessage受信時に `game_end.php` を呼び出す処理を実装

### 動作確認

- [ ] ブラウザコンソールで `koreaMode = true` を確認
- [ ] Networkタブで `game_bet.php` のリクエストを確認（ベットごと）
- [ ] Networkタブで `game_win.php` のリクエストを確認（勝利ごと）
- [ ] サーバーログでWebhook受信を確認（`game.bet`, `game.win`, `game.ended`）
- [ ] テスト環境で完全なゲームフローをテスト

---

## 📞 サポート

リアルタイムコールバックに関する質問:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技術ドキュメント:** https://docs.net8gaming.com

---

**© 2026 NET8 Gaming. All rights reserved.**
