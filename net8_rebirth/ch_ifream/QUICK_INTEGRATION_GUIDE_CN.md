# NET8 快速集成指南 / Quick Integration Guide

**适用于 / For:** 中国合作伙伴 / Chinese Partners
**语言 / Language:** 中文 / Chinese (zh)
**货币 / Currency:** CNY (人民币)

---

## 📦 5分钟快速集成 / 5-Minute Quick Start

### 前提条件 / Prerequisites

1. **获取 API Key** / Get API Key
   - 联系 NET8 团队获取您的 API Key
   - Contact NET8 team to get your API key

2. **准备回调服务器** / Prepare Callback Server
   - 您需要一个 HTTPS 服务器接收游戏事件回调
   - You need an HTTPS server to receive game event callbacks

---

## 🚀 集成步骤 / Integration Steps

### Step 1: HTML 页面集成 / HTML Page Integration

创建一个简单的游戏页面 / Create a simple game page:

```html
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 在线弹珠机</title>
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #1a1a1a;
            color: #fff;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .game-controls {
            background: #2a2a2a;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .btn {
            background: #ff6b00;
            color: white;
            border: none;
            padding: 10px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 5px;
        }
        .btn:hover {
            background: #ff8c00;
        }
        .btn:disabled {
            background: #666;
            cursor: not-allowed;
        }
        #game-iframe {
            width: 100%;
            height: 800px;
            border: 3px solid #ff6b00;
            border-radius: 10px;
            background: #000;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            background: #333;
        }
        .balance {
            font-size: 24px;
            font-weight: bold;
            color: #ffd700;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🎰 NET8 在线弹珠机</h1>

        <div class="game-controls">
            <div class="status">
                <strong>当前余额 / Current Balance:</strong>
                <span class="balance" id="balance">0</span> CNY
            </div>

            <button class="btn" id="btnStartGame" onclick="startGame()">
                🎮 开始游戏 / Start Game
            </button>

            <button class="btn" id="btnEndGame" onclick="endGame()" disabled>
                🛑 结束游戏 / End Game
            </button>

            <button class="btn" onclick="addPoints()">
                💰 充值 500 CNY / Add 500 Points
            </button>

            <button class="btn" onclick="checkBalance()">
                💵 查询余额 / Check Balance
            </button>

            <div id="status-message"></div>
        </div>

        <iframe id="game-iframe" style="display:none;"></iframe>
    </div>

    <script src="net8-api-client.js"></script>
    <script>
        // 初始化 / Initialize
        const net8Client = new NET8ApiClient({
            apiKey: 'YOUR_API_KEY_HERE', // 替换为您的 API Key
            baseUrl: 'https://ifreamnet8-development.up.railway.app/api/v1',
            lang: 'zh',
            currency: 'CNY'
        });

        let currentSessionId = null;

        // 显示状态消息 / Show status message
        function showStatus(message, type = 'info') {
            const statusEl = document.getElementById('status-message');
            statusEl.innerHTML = `<div style="margin-top:10px; padding:10px; background:${type === 'error' ? '#ff4444' : '#4CAF50'}; border-radius:5px;">${message}</div>`;
        }

        // 更新余额显示 / Update balance display
        function updateBalance(balance) {
            document.getElementById('balance').textContent = balance;
        }

        // 查询余额 / Check balance
        async function checkBalance() {
            try {
                const userId = 'chinese_user_' + Date.now(); // 使用您的用户ID系统
                const balance = await net8Client.getBalance(userId);
                updateBalance(balance.current);
                showStatus(`余额查询成功 / Balance: ${balance.current} ${balance.currency}`, 'success');
            } catch (error) {
                showStatus(`查询失败 / Error: ${error.message}`, 'error');
            }
        }

        // 充值 / Add points
        async function addPoints() {
            try {
                const userId = 'chinese_user_' + Date.now();
                const result = await net8Client.addPoints(userId, 500);
                updateBalance(result.balance.current);
                showStatus(`充值成功！/ Added 500 CNY. New balance: ${result.balance.current}`, 'success');
            } catch (error) {
                showStatus(`充值失败 / Error: ${error.message}`, 'error');
            }
        }

        // 开始游戏 / Start game
        async function startGame() {
            try {
                document.getElementById('btnStartGame').disabled = true;
                showStatus('正在启动游戏... / Starting game...', 'info');

                const userId = 'chinese_user_' + Date.now(); // 使用您的用户ID系统

                const result = await net8Client.startGame({
                    modelId: 'SLOT-107', // 北斗无双
                    userId: userId,
                    initialPoints: 1000,
                    balanceMode: 'set',
                    consumeImmediately: false
                });

                currentSessionId = result.sessionId;
                updateBalance(result.balance.current);

                // 显示游戏 iframe
                const gameIframe = document.getElementById('game-iframe');
                gameIframe.src = result.playUrl;
                gameIframe.style.display = 'block';

                document.getElementById('btnEndGame').disabled = false;
                showStatus(`游戏已启动！/ Game started! Session: ${result.sessionId}`, 'success');

            } catch (error) {
                document.getElementById('btnStartGame').disabled = false;
                showStatus(`启动失败 / Error: ${error.message}`, 'error');
            }
        }

        // 结束游戏 / End game
        async function endGame() {
            if (!currentSessionId) {
                showStatus('没有活动的游戏会话 / No active game session', 'error');
                return;
            }

            try {
                document.getElementById('btnEndGame').disabled = true;
                showStatus('正在结束游戏... / Ending game...', 'info');

                const result = await net8Client.endGame({
                    sessionId: currentSessionId,
                    result: 'completed',
                    totalBets: 0, // 这些值应该从游戏中获取
                    totalWins: 0  // These values should come from the game
                });

                updateBalance(result.finalBalance);

                // 隐藏 iframe
                const gameIframe = document.getElementById('game-iframe');
                gameIframe.style.display = 'none';
                gameIframe.src = '';

                currentSessionId = null;
                document.getElementById('btnStartGame').disabled = false;

                showStatus(`游戏已结束！最终余额 / Game ended! Final balance: ${result.finalBalance} CNY`, 'success');

            } catch (error) {
                document.getElementById('btnEndGame').disabled = false;
                showStatus(`结束失败 / Error: ${error.message}`, 'error');
            }
        }

        // 页面加载时初始化 / Initialize on page load
        window.addEventListener('load', async () => {
            try {
                await net8Client.authenticate();
                showStatus('✅ 认证成功 / Authentication successful', 'success');
                checkBalance(); // 自动查询余额
            } catch (error) {
                showStatus(`❌ 认证失败 / Authentication failed: ${error.message}`, 'error');
            }
        });
    </script>
</body>
</html>
```

---

### Step 2: JavaScript API 客户端 / JavaScript API Client

创建 `net8-api-client.js` 文件:

```javascript
/**
 * NET8 API Client
 * 中国合作伙伴专用 / For Chinese Partners
 */
class NET8ApiClient {
    constructor(config) {
        this.apiKey = config.apiKey;
        this.baseUrl = config.baseUrl || 'https://ifreamnet8-development.up.railway.app/api/v1';
        this.lang = config.lang || 'zh';
        this.currency = config.currency || 'CNY';
        this.token = null;
        this.callbackUrl = config.callbackUrl || null;
        this.callbackSecret = config.callbackSecret || null;
    }

    /**
     * 认证并获取 JWT Token / Authenticate and get JWT token
     */
    async authenticate() {
        const response = await fetch(`${this.baseUrl}/auth.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ apiKey: this.apiKey })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Authentication failed');
        }

        this.token = data.token;
        return data;
    }

    /**
     * 获取可用机台列表 / Get available machines
     */
    async getMachines() {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/list_machines.php?lang=${this.lang}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            }
        });

        return await response.json();
    }

    /**
     * 开始游戏 / Start game
     */
    async startGame(options) {
        if (!this.token) await this.authenticate();

        const payload = {
            modelId: options.modelId,
            userId: options.userId,
            machineNo: options.machineNo || null,
            initialPoints: options.initialPoints || 1000,
            balanceMode: options.balanceMode || 'set',
            consumeImmediately: options.consumeImmediately !== undefined ? options.consumeImmediately : false,
            lang: this.lang,
            currency: this.currency
        };

        // 添加回调配置（如果有）/ Add callback config if available
        if (this.callbackUrl) {
            payload.callbackUrl = this.callbackUrl;
            payload.callbackSecret = this.callbackSecret;
        }

        const response = await fetch(`${this.baseUrl}/game_start.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Game start failed');
        }

        return data;
    }

    /**
     * 记录押注 / Record bet
     */
    async recordBet(sessionId, betAmount, betData = {}) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/game_bet.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sessionId: sessionId,
                betAmount: betAmount,
                betData: betData
            })
        });

        return await response.json();
    }

    /**
     * 记录获胜 / Record win
     */
    async recordWin(sessionId, winAmount, winData = {}) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/game_win.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sessionId: sessionId,
                winAmount: winAmount,
                winData: winData
            })
        });

        return await response.json();
    }

    /**
     * 结束游戏 / End game
     */
    async endGame(options) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/game_end.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sessionId: options.sessionId,
                result: options.result || 'completed',
                pointsWon: options.pointsWon || 0,
                totalBets: options.totalBets || 0,
                totalWins: options.totalWins || 0,
                resultData: options.resultData || {}
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Game end failed');
        }

        return data;
    }

    /**
     * 充值 / Add points
     */
    async addPoints(userId, points, transactionId = null) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/add_points.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId: userId,
                points: points,
                currency: this.currency,
                transactionId: transactionId || `txn_${Date.now()}`,
                reason: 'deposit'
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Add points failed');
        }

        return data;
    }

    /**
     * 查询余额 / Get balance
     */
    async getBalance(userId) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/adjust_balance.php`, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                userId: userId,
                action: 'get'
            })
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Get balance failed');
        }

        return data.balance;
    }

    /**
     * 获取游戏历史 / Get play history
     */
    async getPlayHistory(userId) {
        if (!this.token) await this.authenticate();

        const response = await fetch(`${this.baseUrl}/play_history.php?userId=${userId}&lang=${this.lang}`, {
            method: 'GET',
            headers: {
                'Authorization': `Bearer ${this.token}`,
                'Content-Type': 'application/json'
            }
        });

        return await response.json();
    }
}

// 导出供 HTML 使用 / Export for HTML usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NET8ApiClient;
}
```

---

### Step 3: Webhook 服务器 (Node.js) / Webhook Server

创建 `webhook-server.js` 文件:

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

// 重要：使用 raw body parser 以便验证签名
app.use(express.json({
    verify: (req, res, buf) => {
        req.rawBody = buf.toString('utf8');
    }
}));

// 您的 Webhook 密钥 / Your webhook secret
const CALLBACK_SECRET = process.env.NET8_CALLBACK_SECRET || 'your_webhook_secret_key';

/**
 * 验证 Webhook 签名 / Verify webhook signature
 */
function verifyWebhookSignature(req) {
    const signature = req.headers['x-net8-signature'];
    const timestamp = req.headers['x-net8-timestamp'];

    if (!signature || !timestamp) {
        throw new Error('Missing signature or timestamp');
    }

    // 计算期望的签名 / Calculate expected signature
    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', CALLBACK_SECRET)
        .update(req.rawBody)
        .digest('hex');

    // 比较签名（防止时序攻击）/ Compare signatures (timing-safe)
    if (!crypto.timingSafeEqual(Buffer.from(signature), Buffer.from(expectedSignature))) {
        throw new Error('Invalid signature');
    }

    // 验证时间戳（防止重放攻击）/ Verify timestamp (prevent replay)
    const now = Math.floor(Date.now() / 1000);
    const requestTime = parseInt(timestamp);

    if (Math.abs(now - requestTime) > 300) { // 5分钟窗口 / 5-minute window
        throw new Error('Timestamp too old or too far in future');
    }

    return true;
}

/**
 * NET8 Webhook 端点 / NET8 Webhook endpoint
 */
app.post('/webhook/net8', async (req, res) => {
    try {
        // 1. 验证签名 / Verify signature
        verifyWebhookSignature(req);

        // 2. 提取事件数据 / Extract event data
        const { event, timestamp, data } = req.body;

        console.log(`📨 Received webhook: ${event} at ${timestamp}`);
        console.log('📋 Data:', JSON.stringify(data, null, 2));

        // 3. 处理不同类型的事件 / Handle different event types
        switch (event) {
            case 'game.bet':
                // 玩家押注 / Player bet
                await handleBetEvent(data);
                break;

            case 'game.win':
                // 玩家获胜 / Player win
                await handleWinEvent(data);
                break;

            case 'game.ended':
                // 游戏结束 / Game ended
                await handleGameEndedEvent(data);
                break;

            default:
                console.log(`⚠️  Unknown event type: ${event}`);
        }

        // 4. 返回成功响应 / Return success response
        res.json({ success: true, received: true });

    } catch (error) {
        console.error('❌ Webhook error:', error.message);
        res.status(400).json({
            success: false,
            error: error.message
        });
    }
});

/**
 * 处理押注事件 / Handle bet event
 */
async function handleBetEvent(data) {
    console.log('🎰 用户押注 / User placed bet');
    console.log(`   Session: ${data.sessionId}`);
    console.log(`   User: ${data.userId}`);
    console.log(`   Bet Amount: ${data.betAmount} ${data.currency}`);
    console.log(`   New Balance: ${data.balance} ${data.currency}`);

    // TODO: 更新您的数据库 / Update your database
    // await db.recordBet({
    //     sessionId: data.sessionId,
    //     userId: data.userId,
    //     betAmount: data.betAmount,
    //     balance: data.balance,
    //     currency: data.currency
    // });
}

/**
 * 处理获胜事件 / Handle win event
 */
async function handleWinEvent(data) {
    console.log('🎉 用户获胜 / User won');
    console.log(`   Session: ${data.sessionId}`);
    console.log(`   User: ${data.userId}`);
    console.log(`   Win Amount: ${data.winAmount} ${data.currency}`);
    console.log(`   New Balance: ${data.balance} ${data.currency}`);

    // TODO: 更新您的数据库 / Update your database
    // await db.recordWin({
    //     sessionId: data.sessionId,
    //     userId: data.userId,
    //     winAmount: data.winAmount,
    //     balance: data.balance,
    //     currency: data.currency
    // });
}

/**
 * 处理游戏结束事件 / Handle game ended event
 */
async function handleGameEndedEvent(data) {
    console.log('🏁 游戏结束 / Game ended');
    console.log(`   Session: ${data.sessionId}`);
    console.log(`   User: ${data.userId}`);
    console.log(`   Total Bets: ${data.totalBets} ${data.currency}`);
    console.log(`   Total Wins: ${data.totalWins} ${data.currency}`);
    console.log(`   Net Profit: ${data.netProfit} ${data.currency}`);
    console.log(`   Final Balance: ${data.finalBalance} ${data.currency}`);
    console.log(`   Game Time: ${data.gameTime} seconds`);

    // TODO: 最终结算 / Final settlement
    // await db.finalizeGameSession({
    //     sessionId: data.sessionId,
    //     userId: data.userId,
    //     totalBets: data.totalBets,
    //     totalWins: data.totalWins,
    //     netProfit: data.netProfit,
    //     finalBalance: data.finalBalance,
    //     gameTime: data.gameTime,
    //     currency: data.currency
    // });
}

// 启动服务器 / Start server
const PORT = process.env.PORT || 3000;
app.listen(PORT, () => {
    console.log(`🚀 Webhook server running on port ${PORT}`);
    console.log(`📡 Endpoint: http://localhost:${PORT}/webhook/net8`);
    console.log(`🔐 Secret: ${CALLBACK_SECRET.substring(0, 10)}...`);
});
```

启动 Webhook 服务器 / Start webhook server:

```bash
# 安装依赖 / Install dependencies
npm install express

# 设置环境变量 / Set environment variable
export NET8_CALLBACK_SECRET="your_webhook_secret_key"

# 启动服务器 / Start server
node webhook-server.js
```

---

## 🧪 测试 / Testing

### 1. 使用测试脚本 / Using Test Script

```bash
chmod +x test_chinese_partner_api.sh
./test_chinese_partner_api.sh
```

### 2. 手动测试 / Manual Testing

```bash
# 1. 认证 / Authentication
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "YOUR_API_KEY"}'

# 2. 列出机台 / List machines
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php?lang=zh" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# 3. 开始游戏 / Start game
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "SLOT-107",
    "userId": "test_user_001",
    "initialPoints": 1000,
    "lang": "zh",
    "currency": "CNY"
  }'
```

---

## 📊 生产环境清单 / Production Checklist

部署到生产环境前，请确保：

- [ ] **API Key 安全存储**在环境变量中
- [ ] **Webhook 密钥**安全存储且定期轮换
- [ ] **HTTPS** 用于所有回调 URL
- [ ] **签名验证**已在 webhook 处理器中实现
- [ ] **错误处理**和重试逻辑已实现
- [ ] **日志记录**已配置用于审计
- [ ] **速率限制**已实施
- [ ] **负载测试**已完成
- [ ] **备份系统**已就位
- [ ] **监控和告警**已配置

---

## 🆘 常见问题 / FAQ

### Q1: 如何获取 API Key？
**A:** 联系 NET8 技术支持团队：support@net8gaming.com

### Q2: Token 过期了怎么办？
**A:** JWT Token 有效期为 1 小时。过期后重新调用 `/auth.php` 获取新 token。

### Q3: Webhook 必须使用 HTTPS 吗？
**A:** 是的，生产环境必须使用 HTTPS。仅本地测试允许 HTTP (localhost)。

### Q4: 如何测试 Webhook？
**A:** 使用 [ngrok](https://ngrok.com/) 或 [localtunnel](https://localtunnel.github.io/www/) 将本地服务器暴露为 HTTPS URL。

### Q5: 支持哪些机型？
**A:** 调用 `/list_machines.php?lang=zh` 查看所有可用机型。

---

## 📞 技术支持 / Technical Support

如需帮助，请联系：

- 📧 Email: support@net8gaming.com
- 📱 微信 / WeChat: NET8Support
- 🌐 文档 / Docs: https://docs.net8gaming.com

---

**© 2026 NET8 Gaming. All rights reserved.**
