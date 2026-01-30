# リアルタイムゲームデータ連携ガイド（中国パートナー向け）
# Real-time Game Data Integration Guide (For Chinese Partners)

**日付 / Date:** 2026-01-30
**対象 / Target:** 中国パートナー / Chinese Partners

---

## ✅ 実装済み機能 / Implemented Features

NET8のゲームプレイ中、**毎回1ゲームごとにリアルタイムでデータがAPI経由で取得可能**です！

During NET8 gameplay, **real-time data is available via API for every single game action**!

### 自動送信されるイベント / Automatically Sent Events

| イベント / Event | API Endpoint | タイミング / Timing |
|-----------------|-------------|-------------------|
| 🎰 **ベット** / Bet | `/api/v1/game_bet.php` | プレイヤーがベットするたび / Each bet |
| 🎉 **勝利** / Win | `/api/v1/game_win.php` | プレイヤーが勝利するたび / Each win |
| 🏁 **ゲーム終了** / Game End | `window.parent.postMessage` | ゲーム精算時 / Game settlement |

---

## 🔍 実装コード確認 / Implementation Code Review

### 1. ベットコールバック / Bet Callback

**ファイル / File:** `/ch/play_v2/js/view_auth_pachi.js` (167行目)

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

**送信データ / Sent Data:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "betAmount": 10,
  "creditBefore": 1000,
  "creditAfter": 990
}
```

---

### 2. 勝利コールバック / Win Callback

**ファイル / File:** `/ch/play_v2/js/view_auth_pachi.js` (216行目)

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

**送信データ / Sent Data:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "winAmount": 50,
  "creditBefore": 990,
  "creditAfter": 1040
}
```

---

### 3. ゲーム終了通知 / Game End Notification

**ファイル / File:** `/ch/play_v2/js/view_auth_pachi.js` (1240行目)

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

**送信データ / Sent Data:**
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

## 🔑 重要: koreaMode フラグ / Important: koreaMode Flag

リアルタイムコールバックは **`koreaMode = true`** の時のみ送信されます！

Real-time callbacks are sent **only when `koreaMode = true`**!

### koreaModeが有効化される条件 / When koreaMode is Activated

**ファイル / File:** `/ch/play_v2/js/view_auth_pachi.js` (1095行目)

```javascript
if (game.playpoint > 0 && _sconnect && _sconnect.open) {
    console.log('💰 [Korea] Syncing playpoint to camera:', game.playpoint);
    _sconnect.send(_sendStr('Spt', game.playpoint));
    koreaMode = true;  // 韓国モードを有効化
    console.log('✅ [Korea] Korea mode ENABLED!');
}
```

**条件 / Conditions:**
1. ✅ `game.playpoint > 0` - プレイポイントが0より大きい / Play points greater than 0
2. ✅ `_sconnect` が存在する / Connection exists
3. ✅ `_sconnect.open` がtrue / Connection is open

**注意 / Note:** 名前は「韓国モード」ですが、実際には**外部パートナー統合モード**として機能します。中国パートナーも同じ仕組みを利用できます。

---

## 📊 データフロー図 / Data Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  1. ゲーム開始 / Game Start                                   │
│     POST /api/v1/game_start.php                             │
│     ↓                                                       │
│     sessionId取得 & koreaMode有効化                          │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  2. ゲームプレイ中（リアルタイム）/ During Gameplay (Real-time)│
│                                                             │
│  プレイヤーがベット / Player bets                             │
│  ↓ (自動送信 / Auto-send)                                    │
│  POST /api/v1/game_bet.php                                  │
│  ↓                                                          │
│  Webhook → 中国サーバー / Your Server                         │
│  {                                                          │
│    event: 'game.bet',                                       │
│    data: { sessionId, betAmount, balance }                  │
│  }                                                          │
│                                                             │
│  ─────────────────────────────────────                      │
│                                                             │
│  プレイヤーが勝利 / Player wins                               │
│  ↓ (自動送信 / Auto-send)                                    │
│  POST /api/v1/game_win.php                                  │
│  ↓                                                          │
│  Webhook → 中国サーバー / Your Server                         │
│  {                                                          │
│    event: 'game.win',                                       │
│    data: { sessionId, winAmount, balance }                  │
│  }                                                          │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  3. ゲーム終了 / Game End                                     │
│                                                             │
│  プレイヤーが精算 / Player settles                            │
│  ↓                                                          │
│  window.parent.postMessage (game:settlement)                │
│  ↓                                                          │
│  【中国側】postMessageを受け取る / Your side receives message  │
│  ↓                                                          │
│  【中国側】/api/v1/game_end.phpを呼び出す                       │
│  POST /api/v1/game_end.php                                  │
│  {                                                          │
│    sessionId, totalBets, totalWins, result                  │
│  }                                                          │
│  ↓                                                          │
│  Webhook → 中国サーバー / Your Server (最終結算)               │
│  {                                                          │
│    event: 'game.ended',                                     │
│    data: { finalBalance, totalBets, totalWins, netProfit }  │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 💡 中国パートナー実装ガイド / Implementation Guide for Chinese Partners

### ステップ 1: game_start.phpでsessionIdを取得 / Get sessionId

```javascript
const gameStartResponse = await fetch('/api/v1/game_start.php', {
    method: 'POST',
    headers: {
        'Authorization': `Bearer ${jwtToken}`,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        modelId: 'SLOT-107',
        userId: 'chinese_user_001',
        initialPoints: 1000,
        balanceMode: 'set',
        lang: 'zh',
        currency: 'CNY',
        callbackUrl: 'https://your-server.com/webhook/net8',
        callbackSecret: 'your_webhook_secret'
    })
});

const { sessionId, playUrl } = await gameStartResponse.json();
console.log('✅ Session ID:', sessionId);

// iframeにゲームをロード
document.getElementById('game-iframe').src = playUrl;
```

**重要 / Important:** `callbackUrl` と `callbackSecret` を必ず設定してください！これにより`koreaMode`が自動的に有効化されます。

---

### ステップ 2: Webhookでリアルタイムデータを受信 / Receive Real-time Data via Webhook

```javascript
// Node.js Express サーバー例
app.post('/webhook/net8', async (req, res) => {
    try {
        // 1. HMAC-SHA256署名を検証
        verifyWebhookSignature(req, callbackSecret);

        // 2. イベントタイプごとに処理
        const { event, data } = req.body;

        switch (event) {
            case 'game.bet':
                console.log('🎰 ユーザーがベット:', data.betAmount, 'CNY');
                // データベースに記録
                await db.recordBet({
                    sessionId: data.sessionId,
                    betAmount: data.betAmount,
                    balance: data.balance
                });
                break;

            case 'game.win':
                console.log('🎉 ユーザーが勝利:', data.winAmount, 'CNY');
                // データベースに記録
                await db.recordWin({
                    sessionId: data.sessionId,
                    winAmount: data.winAmount,
                    balance: data.balance
                });
                break;

            case 'game.ended':
                console.log('🏁 ゲーム終了:',  {
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
        console.error('Webhook error:', error);
        res.status(400).json({ error: error.message });
    }
});
```

---

### ステップ 3: postMessageでゲーム終了を検知 / Detect Game End via postMessage

```javascript
// 親ウィンドウでpostMessageをリッスン
window.addEventListener('message', async (event) => {
    // セキュリティ: オリジンを検証
    if (event.origin !== 'https://ifreamnet8-development.up.railway.app') {
        return;
    }

    const { type, payload } = event.data;

    if (type === 'game:settlement') {
        console.log('🏁 ゲーム終了通知を受信:', payload);

        // /api/v1/game_end.phpを呼び出す
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
    }
});
```

---

## 🧪 テスト方法 / Testing Method

### ブラウザコンソールで確認 / Check in Browser Console

1. ゲーム開始後、ブラウザの開発者ツールを開く
2. Consoleタブで以下のログを確認:

```
✅ [Korea] Korea mode ENABLED!
🎰 [BET-CALLBACK] Called with: { betAmount: 10, ... }
📡 [BET-CALLBACK] Response status: 200
✅ [BET-CALLBACK] Success: { success: true, ... }

🎉 [WIN-CALLBACK] Called with: { winAmount: 50, ... }
📡 [WIN-CALLBACK] Response status: 200
✅ [WIN-CALLBACK] Success: { success: true, ... }

[DEBUG] Sending game:settlement postMessage: { totalBets: 100, totalWins: 150, ... }
```

### Networkタブで確認 / Check in Network Tab

1. Networkタブを開く
2. ゲームプレイ中に以下のAPIリクエストを確認:
   - `game_bet.php` (ベットごと)
   - `game_win.php` (勝利ごと)

---

## ⚠️ 注意事項 / Important Notes

### 1. koreaModeの有効化が必須 / koreaMode Must Be Enabled

- ❌ `koreaMode = false` → コールバック送信なし / No callbacks sent
- ✅ `koreaMode = true` → コールバック送信あり / Callbacks sent

**解決方法 / Solution:**
- `game_start.php`で`callbackUrl`と`callbackSecret`を必ず設定
- `initialPoints > 0`を確保

### 2. game_end.phpは手動呼び出しが必要 / game_end.php Requires Manual Call

- `game_bet.php`, `game_win.php` → **自動送信** / Automatic
- `game_end.php` → **手動呼び出し必要** (postMessageリスナーから) / Manual call required

**理由 / Reason:** ゲーム終了は親ウィンドウが制御するため / Game end is controlled by parent window

### 3. HTTPS必須 (本番環境) / HTTPS Required (Production)

- 本番環境では`callbackUrl`は**必ずHTTPS**を使用
- ローカルテストのみ`http://localhost`が許可

---

## 📞 サポート / Support

リアルタイムコールバックに関する質問:
Questions about real-time callbacks:

- 📧 Email: support@net8gaming.com
- 📱 WeChat: NET8Support
- 📚 Documentation: [API_DOCUMENTATION_CN.md](./API_DOCUMENTATION_CN.md)

---

## ✅ チェックリスト / Checklist

中国パートナー実装前の確認事項:

- [ ] `callbackUrl` (HTTPS) を設定した
- [ ] `callbackSecret` を安全に保管した
- [ ] Webhook署名検証を実装した (HMAC-SHA256)
- [ ] `window.addEventListener('message')` を実装した
- [ ] postMessage受信時に`game_end.php`を呼び出す処理を実装した
- [ ] ブラウザコンソールで`koreaMode = true`を確認した
- [ ] Networkタブで`game_bet.php`, `game_win.php`のリクエストを確認した
- [ ] テスト環境で完全なゲームフローをテストした

---

**© 2026 NET8 Gaming. All rights reserved.**
