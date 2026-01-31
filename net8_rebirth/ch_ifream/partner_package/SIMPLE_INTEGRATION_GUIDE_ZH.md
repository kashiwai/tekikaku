# NET8 最简集成指南（中文版）

**适用对象:** 只需要 iframe 嵌入游戏 + API 用户连接的合作伙伴
**集成时间:** 2-3 小时
**发行日期:** 2026年1月31日

---

## 🎯 集成目标

您的系统只需要做3件事:

1. ✅ **用 iframe 显示 NET8 游戏**（游戏本身由 NET8 提供）
2. ✅ **用 API 管理用户信息和积分**（用户连接）
3. ✅ **接收 Webhook 获取游戏结果**（实时数据）

**不需要自己实现游戏！** 所有游戏逻辑、画面、音效都由 NET8 提供。

---

## 🔑 您的 API 认证信息

```yaml
API 密钥: pk_live_42c61da908dd515d9f0a6a99406c4dcb
Base URL: https://ifreamnet8-development.up.railway.app/api/v1
```

---

## 📋 完整集成流程（仅需 4 个步骤）

### 步骤 1: 获取 JWT 令牌（1次，缓存1小时）

```javascript
// 服务器端代码
const API_KEY = 'pk_live_42c61da908dd515d9f0a6a99406c4dcb';

async function getAuthToken() {
  const response = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ apiKey: API_KEY })
  });

  const data = await response.json();
  return data.token; // 缓存这个令牌1小时
}
```

---

### 步骤 2: 开始游戏（获取 iframe URL）

```javascript
async function startGame(userId, userName, initialCredit) {
  const token = await getAuthToken(); // 步骤1的令牌

  const response = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/game_start.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,              // 您的用户ID
      userName: userName,          // 用户显示名称
      machineNo: '001',           // 机器编号（从 list_machines.php 获取）
      initialCredit: initialCredit, // 初始积分（例: 50000 = ¥500）
      lang: 'zh',                 // 中文界面
      currency: 'CNY',            // 人民币
      callbackUrl: 'https://your-server.com/api/webhook/net8',  // 您的 Webhook URL
      callbackSecret: 'your_secret_123' // 您的密钥（用于验证签名）
    })
  });

  const data = await response.json();

  if (data.success) {
    return {
      sessionId: data.sessionId,  // 游戏会话ID
      gameUrl: data.gameUrl       // 👈 这个 URL 放到 iframe 中显示！
    };
  }
}
```

---

### 步骤 3: 在 iframe 中显示游戏

```html
<!-- 前端代码 -->
<iframe
  id="game-frame"
  src=""
  width="100%"
  height="800px"
  frameborder="0"
  allow="autoplay; fullscreen">
</iframe>

<script>
// 用户点击"开始游戏"按钮时
async function onStartGameClick() {
  // 1. 调用后端 API 开始游戏
  const result = await fetch('/your-backend/start-game', {
    method: 'POST',
    body: JSON.stringify({
      userId: currentUser.id,
      userName: currentUser.name,
      initialCredit: currentUser.balance
    })
  });

  const { sessionId, gameUrl } = await result.json();

  // 2. 在 iframe 中显示游戏
  document.getElementById('game-frame').src = gameUrl;

  // 3. 保存 sessionId（用于后续查询）
  currentSessionId = sessionId;
}
</script>
```

**完成！** 用户现在可以玩游戏了。

---

### 步骤 4: 接收 Webhook（游戏结果）

游戏过程中，NET8 会自动发送以下事件到您的服务器:

#### 📡 Webhook 接收端点（Node.js/Express 示例）

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.post('/api/webhook/net8', express.json(), (req, res) => {

  // 1. 验证签名（防止伪造请求）
  const signature = req.headers['x-net8-signature'];
  const secret = 'your_secret_123'; // 与 game_start.php 中的 callbackSecret 一致

  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(req.body))
    .digest('hex');

  if (signature !== computedSignature) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  // 2. 处理事件
  const { event, data } = req.body;

  switch(event) {
    case 'game.bet':
      // 用户投注时（每次投注都会发送）
      console.log(`用户 ${data.userId} 投注 ${data.betAmount} CNY`);
      console.log(`剩余积分: ${data.creditAfter} CNY`);
      // 可选: 保存到数据库用于统计
      break;

    case 'game.win':
      // 用户获胜时（每次获胜都会发送）
      console.log(`用户 ${data.userId} 获胜 ${data.winAmount} CNY`);
      console.log(`剩余积分: ${data.creditAfter} CNY`);
      // 可选: 保存到数据库用于统计
      break;

    case 'game.end':
      // 游戏结束时（重要！需要更新用户余额）
      console.log(`游戏结束 - Session: ${data.sessionId}`);
      console.log(`最终积分: ${data.finalCredit} CNY`);
      console.log(`累计投注: ${data.totalBets} CNY`);
      console.log(`累计获胜: ${data.totalWins} CNY`);
      console.log(`净损益: ${data.totalWins - data.totalBets} CNY`);

      // 👉 更新用户余额（重要！）
      updateUserBalance(data.userId, data.finalCredit);
      break;
  }

  // 3. 返回成功响应
  res.json({ success: true });
});

app.listen(3000);
```

---

## 🔔 Webhook 事件详细说明

### 1. game.bet（投注事件）

**发送时机:** 用户每次投注时

**数据内容:**
```json
{
  "event": "game.bet",
  "timestamp": "2026-01-31T12:34:56Z",
  "data": {
    "sessionId": "sess_abc123",
    "userId": "user_001",
    "machineNo": "001",
    "betAmount": 300,        // 投注额 ¥3
    "creditBefore": 50000,   // 投注前积分 ¥500
    "creditAfter": 49700     // 投注后积分 ¥497
  }
}
```

**用途:** 可选，用于实时统计分析

---

### 2. game.win（获胜事件）

**发送时机:** 用户每次获胜时

**数据内容:**
```json
{
  "event": "game.win",
  "timestamp": "2026-01-31T12:35:10Z",
  "data": {
    "sessionId": "sess_abc123",
    "userId": "user_001",
    "machineNo": "001",
    "winAmount": 1500,       // 获胜额 ¥15
    "creditBefore": 49700,   // 获胜前积分 ¥497
    "creditAfter": 51200     // 获胜后积分 ¥512
  }
}
```

**用途:** 可选，用于实时统计分析

---

### 3. game.end（游戏结束事件）⭐ 重要！

**发送时机:** 用户结束游戏时

**数据内容:**
```json
{
  "event": "game.end",
  "timestamp": "2026-01-31T12:40:00Z",
  "data": {
    "sessionId": "sess_abc123",
    "userId": "user_001",
    "machineNo": "001",
    "finalCredit": 48500,    // 最终积分 ¥485
    "totalBets": 15000,      // 累计投注 ¥150
    "totalWins": 13500,      // 累计获胜 ¥135
    "duration": 300          // 游戏时长（秒）
  }
}
```

**用途:** **必须处理！** 更新用户的最终余额

```javascript
// 更新用户余额
function updateUserBalance(userId, finalCredit) {
  // 更新数据库
  db.query('UPDATE users SET balance = ? WHERE id = ?', [finalCredit, userId]);

  console.log(`✅ 用户 ${userId} 余额已更新为 ${finalCredit} CNY`);
}
```

---

## 🌐 多语言・多货币支持

### 支持的语言

在 `game_start.php` 中设置 `lang` 参数:

```javascript
lang: 'zh'  // 中文（简体）
lang: 'ja'  // 日语
lang: 'ko'  // 韩语
lang: 'en'  // 英语
```

### 支持的货币

在 `game_start.php` 中设置 `currency` 参数:

```javascript
currency: 'CNY'  // 人民币（¥30 最小投注）
currency: 'JPY'  // 日元（¥300 最小投注）
currency: 'USD'  // 美元（$3 最小投注）
currency: 'TWD'  // 新台币（NT$90 最小投注）
```

**积分单位:**
- 1 CNY = 100 积分
- 例: `initialCredit: 50000` = ¥500

---

## 🛠️ 其他有用的 API（可选）

### 获取可用机器列表

```javascript
async function getMachines(token) {
  const response = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php', {
    headers: { 'Authorization': `Bearer ${token}` }
  });

  const data = await response.json();
  return data.machines; // [{ machineNo: '001', modelName: '北斗神拳', status: 'available' }, ...]
}
```

### 获取游戏机型列表

```javascript
async function getModels(token) {
  const response = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/models.php', {
    headers: { 'Authorization': `Bearer ${token}` }
  });

  const data = await response.json();
  return data.models; // [{ modelNo: 'M001', modelName: '北斗神拳', ... }, ...]
}
```

### 手动添加用户积分（可选）

```javascript
async function addPoints(token, userId, points, reason) {
  const response = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/add_points.php', {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      points: points,
      reason: reason
    })
  });

  const data = await response.json();
  return data.newBalance;
}
```

---

## 📊 完整的集成示例（端到端）

### 后端 API（Node.js/Express）

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

const API_KEY = 'pk_live_42c61da908dd515d9f0a6a99406c4dcb';
const BASE_URL = 'https://ifreamnet8-development.up.railway.app/api/v1';
const CALLBACK_SECRET = 'your_secret_123';

// JWT 令牌缓存（1小时）
let cachedToken = null;
let tokenExpiry = 0;

async function getAuthToken() {
  if (cachedToken && Date.now() < tokenExpiry) {
    return cachedToken;
  }

  const response = await fetch(`${BASE_URL}/auth.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ apiKey: API_KEY })
  });

  const data = await response.json();
  cachedToken = data.token;
  tokenExpiry = Date.now() + (data.expiresIn * 1000) - 60000; // 提前1分钟刷新

  return cachedToken;
}

// 前端调用这个 API 开始游戏
app.post('/api/start-game', express.json(), async (req, res) => {
  try {
    const { userId, userName, initialCredit } = req.body;
    const token = await getAuthToken();

    const response = await fetch(`${BASE_URL}/game_start.php`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        userId,
        userName,
        machineNo: '001',
        initialCredit,
        lang: 'zh',
        currency: 'CNY',
        callbackUrl: 'https://your-server.com/api/webhook/net8',
        callbackSecret: CALLBACK_SECRET
      })
    });

    const data = await response.json();
    res.json(data);

  } catch (error) {
    res.status(500).json({ error: error.message });
  }
});

// Webhook 接收端点
app.post('/api/webhook/net8', express.json(), (req, res) => {
  // 验证签名
  const signature = req.headers['x-net8-signature'];
  const computedSignature = crypto
    .createHmac('sha256', CALLBACK_SECRET)
    .update(JSON.stringify(req.body))
    .digest('hex');

  if (signature !== computedSignature) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  const { event, data } = req.body;

  switch(event) {
    case 'game.end':
      // 更新用户余额
      updateUserBalance(data.userId, data.finalCredit);
      break;
  }

  res.json({ success: true });
});

function updateUserBalance(userId, finalCredit) {
  // 更新数据库
  console.log(`更新用户 ${userId} 余额为 ${finalCredit}`);
}

app.listen(3000);
```

---

### 前端代码（HTML + JavaScript）

```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>NET8 游戏</title>
</head>
<body>
  <h1>NET8 在线弹珠机</h1>

  <button onclick="startGame()">开始游戏</button>

  <iframe
    id="game-frame"
    width="100%"
    height="800px"
    frameborder="0"
    style="display: none;">
  </iframe>

  <script>
  async function startGame() {
    // 1. 调用后端 API
    const response = await fetch('/api/start-game', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        userId: 'user_001',
        userName: '测试用户',
        initialCredit: 50000  // ¥500
      })
    });

    const data = await response.json();

    if (data.success) {
      // 2. 在 iframe 中显示游戏
      const iframe = document.getElementById('game-frame');
      iframe.src = data.gameUrl;
      iframe.style.display = 'block';

      console.log('游戏开始！Session ID:', data.sessionId);
    }
  }
  </script>
</body>
</html>
```

---

## ✅ 集成检查清单

### 开发环境测试

- [ ] API 密钥认证测试成功（运行 `test_auth.sh`）
- [ ] 游戏开始成功（获取 gameUrl）
- [ ] iframe 显示游戏成功
- [ ] Webhook 接收测试成功（使用 ngrok 等工具）
- [ ] game.end 事件成功更新用户余额

### 生产环境部署

- [ ] API 密钥保存在环境变量中
- [ ] Webhook URL 使用 HTTPS
- [ ] HMAC 签名验证已实现
- [ ] 错误日志已记录
- [ ] 用户余额更新逻辑已测试

---

## 🐛 常见问题

### Q1: iframe 无法显示游戏

**原因:**
- gameUrl 格式错误
- iframe 被浏览器安全策略阻止

**解决方法:**
```javascript
// 确保 iframe 允许必要的权限
<iframe
  allow="autoplay; fullscreen; payment"
  sandbox="allow-scripts allow-same-origin allow-forms">
</iframe>
```

---

### Q2: Webhook 没有收到回调

**原因:**
- callbackUrl 无法访问（防火墙阻止）
- callbackUrl 不是 HTTPS
- 签名验证失败

**解决方法:**
```bash
# 开发环境使用 ngrok 测试
ngrok http 3000
# 使用 ngrok 提供的 HTTPS URL 作为 callbackUrl
```

---

### Q3: 用户余额没有更新

**原因:**
- 没有处理 game.end 事件
- 数据库更新失败

**解决方法:**
```javascript
// 确保处理 game.end 事件
case 'game.end':
  console.log('收到 game.end 事件:', data);
  updateUserBalance(data.userId, data.finalCredit);
  break;
```

---

## 📞 技术支持

如有任何问题，请联系:

- **Email:** support@net8gaming.com
- **技术支持:** api-support@net8gaming.com

---

## 🎉 总结

您只需要实现3件事:

1. ✅ 调用 `game_start.php` 获取 gameUrl
2. ✅ 在 iframe 中显示 gameUrl
3. ✅ 接收 Webhook 并更新用户余额

**就这么简单！** 游戏本身完全由 NET8 提供。

**立即开始测试:**

```bash
cd partner_package
./test_auth.sh
```

---

**© 2026 NET8 Gaming. 保留所有权利。**
