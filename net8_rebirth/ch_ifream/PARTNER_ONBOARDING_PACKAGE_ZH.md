# NET8 合作伙伴入门包（中文版）

**发行日期:** 2026年1月31日
**对象:** 外部合作伙伴（API集成业务）
**版本:** 1.0.0

---

## 🎉 欢迎使用 NET8 Gaming

感谢您选择 NET8 在线弹珠机 API。本入门包包含 API 集成所需的所有信息。

---

## 🔑 您的专用 API 认证信息

### 生产环境（Production）

```yaml
环境: Production（生产）
API 密钥: pk_live_42c61da908dd515d9f0a6a99406c4dcb
Base URL: https://ifreamnet8-development.up.railway.app/api/v1
速率限制: 100,000 次请求/天
有效期: 永久（建议定期轮换）
```

**⚠️ 重要注意事项:**
- 此 API 密钥是机密信息，请勿公开
- 保存在环境变量中，不要直接写入源代码
- 仅在服务器端使用（不可在客户端 JavaScript 中使用）
- 建议定期轮换（每3-6个月）

---

## 📚 完整文档集

### 1. API 手册（必读）

**中文版:**
- 文件: `API_MANUAL_ZH.md`
- 内容: 全部18个 API 端点详细说明
- 大小: 约71KB，450行

**主要端点:**
- `POST /auth.php` - JWT 令牌认证
- `POST /game_start.php` - 游戏开始
- `POST /game_bet.php` - 投注记录（自动回调）
- `POST /game_win.php` - 获胜记录（自动回调）
- `POST /game_end.php` - 游戏结束
- `POST /add_points.php` - 添加积分
- `GET /list_machines.php` - 获取机器列表
- `GET /models.php` - 获取机型列表

### 2. 实时回调指南（重要）

**中文版:**
- 文件: `REALTIME_CALLBACK_GUIDE_ZH.md`
- 内容: 实时游戏数据连接的实现方法
- 大小: 约53KB，340行

**什么是实时回调:**
- 玩家游戏时，每局游戏的投注和获胜数据自动发送
- 以 Webhook 形式通知您的服务器
- 可通过 HMAC-SHA256 签名进行安全验证

### 3. API 密钥管理手册（管理员用）

**中文版:**
- 文件: `API_KEY_MANAGEMENT_ZH.md`
- 内容: API 密钥的发行、管理、停用、监控方法
- 大小: 约47KB

---

## 🚀 快速入门（5分钟完成验证）

### 步骤1: 获取 JWT 令牌

**请求示例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"
  }'
```

**预期响应:**

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "live"
}
```

**✅ 成功确认:** 返回 `success: true` 即可！在下一个请求中使用此令牌。

---

### 步骤2: 获取机器列表（使用令牌）

**请求示例:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

**预期响应:**

```json
{
  "success": true,
  "machines": [
    {
      "machineNo": "001",
      "modelNo": "M001",
      "modelName": "北斗神拳",
      "status": "available",
      "currentUser": null
    }
  ]
}
```

---

### 步骤3: 开始游戏（集成测试）

**请求示例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "userName": "测试用户",
    "machineNo": "001",
    "initialCredit": 50000,
    "lang": "zh",
    "currency": "CNY",
    "callbackUrl": "https://your-server.com/api/webhook/net8",
    "callbackSecret": "your_secret_key_123"
  }'
```

**预期响应:**

```json
{
  "success": true,
  "sessionId": "sess_abc123def456",
  "gameUrl": "https://ifreamnet8-development.up.railway.app/ch/play_v2/?sessionId=sess_abc123def456",
  "message": "Game started successfully"
}
```

**✅ 成功确认:** 向玩家显示 `gameUrl`，并在 iframe 中嵌入。

---

## 🎮 实现示例: JavaScript（推荐）

### 完整集成流程

```javascript
// 1. 从环境变量获取 API 密钥（服务器端）
const API_KEY = process.env.NET8_API_KEY; // pk_live_42c61da908dd515d9f0a6a99406c4dcb
const BASE_URL = 'https://ifreamnet8-development.up.railway.app/api/v1';

// 2. JWT 令牌获取函数
async function getAuthToken() {
  const response = await fetch(`${BASE_URL}/auth.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ apiKey: API_KEY })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error('Authentication failed: ' + data.message);
  }

  return data.token; // 返回 JWT 令牌
}

// 3. 游戏开始函数
async function startGame(userId, userName, machineNo) {
  const token = await getAuthToken();

  const response = await fetch(`${BASE_URL}/game_start.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      userName: userName,
      machineNo: machineNo,
      initialCredit: 50000,
      lang: 'zh',
      currency: 'CNY',
      callbackUrl: 'https://your-server.com/api/webhook/net8',
      callbackSecret: 'your_secret_key_123'
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log('✅ 游戏开始成功!');
    console.log('Session ID:', data.sessionId);
    console.log('Game URL:', data.gameUrl);

    // 在 iframe 中设置游戏 URL
    document.getElementById('game-frame').src = data.gameUrl;

    return data;
  } else {
    throw new Error('Game start failed: ' + data.message);
  }
}

// 4. Webhook 接收处理（Node.js/Express 示例）
const express = require('express');
const crypto = require('crypto');
const app = express();

app.post('/api/webhook/net8', express.json(), (req, res) => {
  // HMAC 签名验证
  const signature = req.headers['x-net8-signature'];
  const secret = 'your_secret_key_123';
  const payload = JSON.stringify(req.body);

  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');

  if (signature !== computedSignature) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  // 事件处理
  const { event, data } = req.body;

  switch(event) {
    case 'game.bet':
      console.log('🎰 投注:', data.betAmount, 'CNY');
      console.log('余额:', data.creditAfter, 'CNY');
      // 记录到数据库
      break;

    case 'game.win':
      console.log('🎉 获胜!:', data.winAmount, 'CNY');
      console.log('余额:', data.creditAfter, 'CNY');
      // 记录到数据库
      break;

    case 'game.end':
      console.log('🏁 游戏结束');
      console.log('最终余额:', data.finalCredit, 'CNY');
      console.log('累计投注:', data.totalBets, 'CNY');
      console.log('累计获胜:', data.totalWins, 'CNY');
      console.log('净损益:', data.totalWins - data.totalBets, 'CNY');
      // 更新用户余额
      break;
  }

  res.json({ success: true });
});

app.listen(3000, () => {
  console.log('Webhook server running on port 3000');
});
```

---

## 🔔 实时回调规格

### 游戏中自动发送的事件

| 事件 | 发送时机 | 数据内容 |
|------|---------|---------|
| **game.bet** | 玩家每次投注时 | 投注额、投注前余额、投注后余额 |
| **game.win** | 玩家每次获胜时 | 获胜额、获胜前余额、获胜后余额 |
| **game.end** | 游戏结束时 | 最终余额、累计投注、累计获胜、会话 ID |

### Webhook 请求格式

**头部:**
```
Content-Type: application/json
X-NET8-Signature: <HMAC-SHA256 签名>
```

**有效载荷示例（game.bet）:**
```json
{
  "event": "game.bet",
  "timestamp": "2026-01-31T12:34:56Z",
  "data": {
    "sessionId": "sess_abc123def456",
    "userId": "test_user_001",
    "machineNo": "001",
    "betAmount": 300,
    "creditBefore": 50000,
    "creditAfter": 49700
  }
}
```

**签名验证:**
```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');

  return signature === computedSignature;
}
```

---

## 🌐 多语言・多货币支持

### 支持的语言

| 代码 | 语言 |
|-----|-----|
| `ja` | 日语 |
| `zh` | 中文（简体） |
| `ko` | 韩语 |
| `en` | 英语 |

### 支持的货币

| 代码 | 货币 | 最小投注单位 |
|-----|-----|------------|
| `JPY` | 日元 | ¥300 |
| `CNY` | 人民币 | ¥30 |
| `USD` | 美元 | $3 |
| `TWD` | 新台币 | NT$90 |

**使用示例:**
```json
{
  "lang": "zh",
  "currency": "CNY",
  "initialCredit": 5000
}
```

---

## 🛠️ 故障排除

### 常见错误和解决方法

#### ❌ 错误: "Invalid API Key"

**原因:**
- API 密钥错误
- API 密钥已被禁用

**解决方法:**
```bash
# 确认 API 密钥是否正确
echo "pk_live_42c61da908dd515d9f0a6a99406c4dcb"

# 认证测试
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

#### ❌ 错误: "Invalid or expired token"

**原因:**
- JWT 令牌已过期（1小时）
- 令牌格式错误

**解决方法:**
```javascript
// 重新获取令牌
const newToken = await getAuthToken();
```

#### ❌ 错误: "Rate limit exceeded"

**原因:**
- 超过每天请求限制（100,000次）

**解决方法:**
- 联系 NET8 支持，请求提高限制
- 使用缓存减少请求次数

#### ❌ 错误: "Machine not available"

**原因:**
- 指定的机器正在使用中或不存在

**解决方法:**
```bash
# 获取可用机器列表
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 📞 技术支持

### 支持窗口

- **📧 Email:** support@net8gaming.com
- **🌐 Website:** https://net8gaming.com
- **📱 技术支持:** https://docs.net8gaming.com
- **⏰ 营业时间:** 工作日 9:00〜18:00 (JST)

### 紧急联系方式

- **安全事件:** security@net8gaming.com
- **API 故障报告:** api-support@net8gaming.com

---

## 📋 检查清单: 生产环境部署前

### 安全

- [ ] 将 API 密钥保存在环境变量中
- [ ] 从源代码中删除 API 密钥
- [ ] 仅使用 HTTPS 通信
- [ ] 实现 Webhook 签名验证
- [ ] 实现速率限制对策

### 功能

- [ ] JWT 令牌获取成功
- [ ] 游戏开始成功
- [ ] 游戏在 iframe 中正常显示
- [ ] Webhook 接收成功
- [ ] game.bet 事件处理正常
- [ ] game.win 事件处理正常
- [ ] game.end 事件处理正常

### 监控

- [ ] 记录 API 请求日志
- [ ] 设置错误监控
- [ ] 监控速率限制使用率
- [ ] 监控 Webhook 接收失败

---

## 🎯 下一步

### 1. 集成测试（1〜2天）

- [ ] JWT 认证测试
- [ ] 机器列表获取测试
- [ ] 游戏开始测试
- [ ] Webhook 接收测试
- [ ] 错误处理测试

### 2. 预生产环境部署（3〜5天）

- [ ] 在与生产环境相同的配置下测试
- [ ] 执行负载测试
- [ ] 执行安全测试

### 3. 生产环境部署（1天）

- [ ] 确认部署检查清单
- [ ] 分阶段推出（10% → 50% → 100%）
- [ ] 确认监控仪表板

---

## 📖 附加文档

### 日语版文档

- `API_MANUAL_JA.md` - API 手册（日语版）
- `REALTIME_CALLBACK_GUIDE_JA.md` - 实时回调指南（日语版）
- `API_KEY_MANAGEMENT_JA.md` - API 密钥管理手册（日语版）
- `PARTNER_ONBOARDING_PACKAGE_JA.md` - 合作伙伴入门包（日语版）

### 技术规格

- API 响应时间: 平均 100〜300ms
- Webhook 超时: 5秒
- JWT 令牌有效期: 1小时
- 速率限制: 100,000 次请求/天
- 最大并发连接数: 1,000个会话

---

## ✨ 总结

您可以立即使用 API 密钥 `pk_live_42c61da908dd515d9f0a6a99406c4dcb` 开始集成。

**立即尝试:**

```bash
# 认证测试（5秒完成）
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

如有任何疑问，请随时联系支持团队。

---

**© 2026 NET8 Gaming. 保留所有权利。**
