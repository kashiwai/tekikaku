# 实时游戏数据集成指南（中文版）

**版本:** 1.0.0
**最后更新:** 2026年1月30日
**目标:** 外部合作伙伴（中国、韩国及其他海外市场）

---

## 概述

在NET8游戏过程中，**每次游戏动作都会通过API实时自动获取数据**！

本指南详细说明已实现的实时回调系统。

---

## ✅ 已实现功能

### 自动发送的事件

| 事件 | API端点 | 触发时机 | 发送方式 |
|------|---------|---------|---------|
| 🎰 **下注** | `/api/v1/game_bet.php` | 每次玩家下注 | 自动发送 |
| 🎉 **获胜** | `/api/v1/game_win.php` | 每次玩家获胜 | 自动发送 |
| 🏁 **游戏结束** | `window.parent.postMessage` | 游戏结算时 | postMessage |

---

## 🔍 实现代码确认

### 1. 下注回调（自动发送）

**实现文件:** `/ch/play_v2/js/view_auth_pachi.js`（第167行）

**工作原理:** 每次玩家下注时，自动调用 `/api/v1/game_bet.php`。

```javascript
function sendBetCallback(betAmount, creditBefore, creditAfter) {
    console.log('🎰 [BET-CALLBACK] Called with:', {
        betAmount: betAmount,
        creditBefore: creditBefore,
        creditAfter: creditAfter
    });

    // 仅在sessionId和koreaMode有效时发送
    if (typeof sessionId === 'undefined' || !sessionId || !koreaMode) {
        console.error('❌ [BET-CALLBACK] SKIPPED!');
        return;
    }

    // 累计总下注额
    game.totalBets = (game.totalBets || 0) + betAmount;

    // 实时API发送
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

**发送数据:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "betAmount": 10,
  "creditBefore": 1000,
  "creditAfter": 990
}
```

**API处理后:**
- 服务器端处理 `/api/v1/game_bet.php`
- 通过Webhook将 `game.bet` 事件发送到合作伙伴服务器

---

### 2. 获胜回调（自动发送）

**实现文件:** `/ch/play_v2/js/view_auth_pachi.js`（第216行）

**工作原理:** 每次玩家获胜时，自动调用 `/api/v1/game_win.php`。

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

    // 累计总获胜额
    game.totalWins = (game.totalWins || 0) + winAmount;

    // 实时API发送
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

**发送数据:**
```json
{
  "sessionId": "sess_1738234567_23_abc123",
  "winAmount": 50,
  "creditBefore": 990,
  "creditAfter": 1040
}
```

**API处理后:**
- 服务器端处理 `/api/v1/game_win.php`
- 通过Webhook将 `game.win` 事件发送到合作伙伴服务器

---

### 3. 游戏结束通知（postMessage）

**实现文件:** `/ch/play_v2/js/view_auth_pachi.js`（第1240行）

**工作原理:** 游戏结算时，通过 `postMessage` 通知父窗口。

```javascript
// 游戏结算时向父窗口发送postMessage
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

**发送数据:**
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

## 🔑 重要: koreaMode 标志

实时回调（`game_bet.php`、`game_win.php`）仅在 **`koreaMode = true`** 时发送！

### koreaMode 启用条件

**实现文件:** `/ch/play_v2/js/view_auth_pachi.js`（第1095行）

```javascript
if (game.playpoint > 0 && _sconnect && _sconnect.open) {
    console.log('💰 [Korea] Syncing playpoint to camera:', game.playpoint);
    _sconnect.send(_sendStr('Spt', game.playpoint));
    koreaMode = true;  // 启用韩国模式
    console.log('✅ [Korea] Korea mode ENABLED!', {
        koreaMode: koreaMode,
        sessionId: sessionId
    });
}
```

**启用条件:**
1. ✅ `game.playpoint > 0` - 游戏点数大于0
2. ✅ `_sconnect` 存在 - 连接存在
3. ✅ `_sconnect.open` 为 true - 连接已打开

**自动满足这些条件的方法:**

在调用 `game_start.php` 时，设置以下参数：

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
        initialPoints: 1000,  // 必须大于0
        callbackUrl: 'https://your-server.com/webhook/net8',  // 必需！
        callbackSecret: 'your_webhook_secret'  // 必需！
    })
});
```

**重要:** 设置 `callbackUrl` 和 `callbackSecret` 后，`koreaMode` 会自动启用，实时回调将正常工作。

**注意:** 虽然名为"韩国模式"，但实际上是**外部合作伙伴集成模式**。中国和其他海外合作伙伴也可以使用相同的机制。

---

## 📊 数据流程图

```
┌─────────────────────────────────────────────────────────────┐
│  1. 开始游戏 / Game Start                                     │
│     POST /api/v1/game_start.php                             │
│     {                                                       │
│       modelId, userId, initialPoints,                       │
│       callbackUrl, callbackSecret  ← 必需                   │
│     }                                                       │
│     ↓                                                       │
│     获取sessionId & 自动启用koreaMode                         │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  2. 游戏中（实时自动发送）                                     │
│                                                             │
│  玩家下注                                                    │
│  ↓ (自动 / Automatic)                                       │
│  JavaScript: sendBetCallback() 调用                         │
│  ↓                                                          │
│  POST /api/v1/game_bet.php                                  │
│  { sessionId, betAmount, creditBefore, creditAfter }        │
│  ↓                                                          │
│  服务器处理 → Webhook发送                                    │
│  ↓                                                          │
│  合作伙伴服务器接收                                          │
│  {                                                          │
│    event: 'game.bet',                                       │
│    data: { sessionId, betAmount, balance }                  │
│  }                                                          │
│                                                             │
│  ─────────────────────────────────────                      │
│                                                             │
│  玩家获胜                                                    │
│  ↓ (自动 / Automatic)                                       │
│  JavaScript: sendWinCallback() 调用                         │
│  ↓                                                          │
│  POST /api/v1/game_win.php                                  │
│  { sessionId, winAmount, creditBefore, creditAfter }        │
│  ↓                                                          │
│  服务器处理 → Webhook发送                                    │
│  ↓                                                          │
│  合作伙伴服务器接收                                          │
│  {                                                          │
│    event: 'game.win',                                       │
│    data: { sessionId, winAmount, balance }                  │
│  }                                                          │
└────────────────┬────────────────────────────────────────────┘
                 ↓
┌─────────────────────────────────────────────────────────────┐
│  3. 游戏结束 / Game End                                       │
│                                                             │
│  玩家结算                                                    │
│  ↓                                                          │
│  JavaScript: window.parent.postMessage                      │
│  {                                                          │
│    type: 'game:settlement',                                 │
│    payload: { totalBets, totalWins, finalBalance, ... }     │
│  }                                                          │
│  ↓                                                          │
│  【合作伙伴端】接收postMessage                                │
│  window.addEventListener('message', ...)                    │
│  ↓                                                          │
│  【合作伙伴端】调用game_end.php                                │
│  POST /api/v1/game_end.php                                  │
│  {                                                          │
│    sessionId, totalBets, totalWins, result                  │
│  }                                                          │
│  ↓                                                          │
│  服务器处理 → 最终Webhook发送                                │
│  ↓                                                          │
│  合作伙伴服务器接收（最终结算）                               │
│  {                                                          │
│    event: 'game.ended',                                     │
│    data: { finalBalance, totalBets, totalWins, netProfit }  │
│  }                                                          │
└─────────────────────────────────────────────────────────────┘
```

---

## 💡 合作伙伴实现指南

### 步骤 1: 通过game_start.php获取sessionId

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
        initialPoints: 1000,  // 大于0的值
        balanceMode: 'set',
        lang: 'zh',
        currency: 'CNY',
        callbackUrl: 'https://your-server.com/webhook/net8',  // 必需！
        callbackSecret: 'your_webhook_secret_key'  // 必需！
    })
});

const { sessionId, playUrl } = await gameStartResponse.json();
console.log('✅ Session ID:', sessionId);

// 在iframe中加载游戏
document.getElementById('game-iframe').src = playUrl;
```

**重要:** 必须设置 `callbackUrl` 和 `callbackSecret`。这将自动启用 `koreaMode`，使实时回调正常工作。

---

### 步骤 2: 通过Webhook接收实时数据

**Node.js Express 服务器示例:**

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

// Webhook签名验证
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
        throw new Error('签名无效');
    }

    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) {
        throw new Error('时间戳过旧');
    }

    return true;
}

// Webhook端点
app.post('/webhook/net8', async (req, res) => {
    try {
        verifyWebhookSignature(req);

        const { event, data } = req.body;

        switch (event) {
            case 'game.bet':
                console.log('🎰 下注:', data.betAmount, 'CNY');
                // 记录到数据库
                await db.recordBet({
                    sessionId: data.sessionId,
                    betAmount: data.betAmount,
                    balance: data.balance
                });
                break;

            case 'game.win':
                console.log('🎉 获胜:', data.winAmount, 'CNY');
                // 记录到数据库
                await db.recordWin({
                    sessionId: data.sessionId,
                    winAmount: data.winAmount,
                    balance: data.balance
                });
                break;

            case 'game.ended':
                console.log('🏁 游戏结束:', {
                    totalBets: data.totalBets,
                    totalWins: data.totalWins,
                    netProfit: data.netProfit
                });
                // 最终结算
                await db.finalizeSession(data);
                break;
        }

        res.json({ success: true });
    } catch (error) {
        console.error('Webhook错误:', error);
        res.status(400).json({ error: error.message });
    }
});

const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Webhook服务器启动: 端口${PORT}`);
});
```

---

### 步骤 3: 通过postMessage检测游戏结束

```javascript
// 在父窗口监听postMessage
window.addEventListener('message', async (event) => {
    // 安全: 验证来源
    if (event.origin !== 'https://ifreamnet8-development.up.railway.app') {
        console.warn('来自不正确来源的消息:', event.origin);
        return;
    }

    const { type, payload } = event.data;

    if (type === 'game:settlement') {
        console.log('🏁 收到游戏结束通知:', payload);

        try {
            // 调用game_end.php
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
            console.log('✅ 游戏结束API调用成功:', result);

            // 更新UI
            updateBalanceDisplay(result.finalBalance);

            // 隐藏iframe
            document.getElementById('game-iframe').style.display = 'none';

        } catch (error) {
            console.error('❌ 游戏结束处理错误:', error);
        }
    }
});
```

---

## 🧪 测试方法

### 浏览器控制台确认

1. 游戏开始后，打开浏览器开发者工具（F12）
2. 在Console标签中确认以下日志:

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

### Network标签确认

1. 打开Network标签
2. 游戏中确认以下API请求:
   - `game_bet.php` → 200 OK（每次下注）
   - `game_win.php` → 200 OK（每次获胜）
   - `game_end.php` → 200 OK（游戏结束时）

### Webhook日志确认

在服务器端的日志中确认:

```
📨 Received webhook: game.bet
📋 Data: { sessionId: "sess_...", betAmount: 10, balance: 990 }

📨 Received webhook: game.win
📋 Data: { sessionId: "sess_...", winAmount: 50, balance: 1040 }

📨 Received webhook: game.ended
📋 Data: { sessionId: "sess_...", totalBets: 100, totalWins: 150, netProfit: 50 }
```

---

## ⚠️ 故障排除

### 问题 1: 回调未发送

**症状:** `game_bet.php` 或 `game_win.php` 未被调用

**原因和解决方法:**

| 原因 | 确认方法 | 解决方法 |
|------|---------|---------|
| `koreaMode = false` | 控制台检查 `koreaMode` | 设置 `callbackUrl` 和 `callbackSecret` |
| `sessionId` 未定义 | 控制台检查 `sessionId` | 正确调用 `game_start.php` |
| `initialPoints = 0` | 检查请求参数 | 将 `initialPoints` 设置为1或更大 |

**调试命令（浏览器控制台）:**

```javascript
// 检查当前koreaMode状态
console.log('koreaMode:', typeof koreaMode !== 'undefined' ? koreaMode : 'UNDEFINED');
console.log('sessionId:', typeof sessionId !== 'undefined' ? sessionId : 'UNDEFINED');
```

---

### 问题 2: Webhook未接收

**症状:** 服务器端未接收到Webhook

**检查事项:**

1. **确认callbackUrl是HTTPS**
   - 生产环境必需
   - 本地测试仅允许 `http://localhost`

2. **确认签名验证正确**
   - 使用HMAC-SHA256算法
   - `callbackSecret` 是否匹配

3. **确认防火墙设置**
   - 允许来自NET8 IP地址的连接

---

## ✅ 实现检查清单

合作伙伴实现前的确认事项:

### 必需项目

- [ ] 在 `game_start.php` 中设置 `callbackUrl`（HTTPS）
- [ ] 在 `game_start.php` 中设置 `callbackSecret`
- [ ] 确保 `initialPoints > 0`
- [ ] 实现Webhook签名验证（HMAC-SHA256）
- [ ] 实现 `window.addEventListener('message')`
- [ ] 实现接收postMessage时调用 `game_end.php` 的处理

### 操作确认

- [ ] 在浏览器控制台确认 `koreaMode = true`
- [ ] 在Network标签确认 `game_bet.php` 请求（每次下注）
- [ ] 在Network标签确认 `game_win.php` 请求（每次获胜）
- [ ] 在服务器日志确认Webhook接收（`game.bet`, `game.win`, `game.ended`）
- [ ] 在测试环境测试完整游戏流程

---

## 📞 技术支持

关于实时回调的问题:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技术文档:** https://docs.net8gaming.com

---

**© 2026 NET8 Gaming. 保留所有权利。**
