# NET8 在线弹珠机 API 手册（中文版）

**版本:** 1.0.0
**最后更新:** 2026年1月30日
**目标:** 外部合作伙伴（中国、韩国及其他海外市场）

---

## 📋 目录

1. [概述](#概述)
2. [认证系统](#认证系统)
3. [API端点列表](#api端点列表)
4. [游戏流程](#游戏流程)
5. [实时回调](#实时回调)
6. [Webhook安全](#webhook安全)
7. [错误代码](#错误代码)
8. [代码示例](#代码示例)
9. [测试方法](#测试方法)
10. [生产环境部署](#生产环境部署)

---

## 概述

NET8 在线弹珠机 API 是一个集成系统，允许用户在线游玩真实的弹珠机设备。外部合作伙伴可以使用本 API 将 NET8 的游戏集成到自己的平台中。

### 主要功能

- 🔐 **JWT认证** - 基于安全令牌的认证
- 🎮 **游戏管理** - 机台列表、游戏开始/结束、实时事件
- 💰 **点数管理** - 余额添加、调整、转换
- 🔔 **Webhook系统** - 实时事件通知（HMAC-SHA256签名）
- 🌐 **多语言支持** - 日语、中文、韩语、英语
- 💱 **多货币支持** - JPY、CNY、USD、TWD

### 基础URL

```
生产环境: https://ifreamnet8-development.up.railway.app/api/v1
```

### 请求格式

- **HTTP方法:** POST（部分GET）
- **内容类型:** `application/json`
- **认证头:** `Authorization: Bearer {JWT_TOKEN}`
- **语言参数:** `lang=zh` (zh/ja/ko/en)
- **货币参数:** `currency=CNY` (CNY/JPY/USD/TWD)

---

## 认证系统

### 获取 JWT 令牌

在调用所有API之前，首先需要获取 JWT 认证令牌。

**端点:** `POST /auth.php`

**请求示例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "apiKey": "your_api_key_here"
  }'
```

**成功响应:**

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "production"
}
```

**错误响应:**

```json
{
  "error": "INVALID_API_KEY",
  "message": "无效或过期的API密钥"
}
```

### 令牌使用方法

将获取的 JWT 令牌包含在所有 API 请求的 Authorization 头中。

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{...}'
```

**令牌有效期:**
- 发行后1小时（3600秒）
- 过期后请重新调用 `/auth.php`

---

## API端点列表

### 🔐 认证 API

| 端点 | 方法 | 说明 |
|------|------|------|
| `/auth.php` | POST | 获取JWT认证令牌 |

### 🎮 游戏管理 API

| 端点 | 方法 | 说明 |
|------|------|------|
| `/list_machines.php` | GET | 获取可用机台列表 |
| `/models.php` | GET | 获取机型详细信息 |
| `/recommended_models.php` | GET | 获取推荐机型 |
| `/check_machines.php` | GET | 检查机台状态 |
| `/game_start.php` | POST | 开始游戏 |
| `/game_bet.php` | POST | 记录下注事件（实时） |
| `/game_win.php` | POST | 记录获胜事件（实时） |
| `/game_end.php` | POST | 结束游戏・结算 |

### 💰 点数管理 API

| 端点 | 方法 | 说明 |
|------|------|------|
| `/add_points.php` | POST | 添加点数（充值） |
| `/set_balance.php` | POST | 设置余额（管理员功能） |
| `/adjust_balance.php` | POST | 调整余额（增减） |
| `/convert_credit.php` | POST | 信用转换 |

### 📊 查询 API

| 端点 | 方法 | 说明 |
|------|------|------|
| `/play_history.php` | GET | 获取游戏历史 |
| `/list_users.php` | GET | 用户列表（管理员） |

---

## 游戏流程

### 完整游戏流程示例

```
1️⃣ 认证
   POST /auth.php
   → 获取 JWT Token

2️⃣ 获取机台列表
   GET /list_machines.php?lang=zh
   → 获取可用机台列表

3️⃣ 开始游戏
   POST /game_start.php
   → 获取 sessionId、playUrl
   → 在iframe中加载 playUrl

4️⃣ 游戏中（自动）
   ┌─ 玩家下注
   │  → POST /game_bet.php（自动发送）
   │  → Webhook: game.bet 事件
   │
   └─ 玩家获胜
      → POST /game_win.php（自动发送）
      → Webhook: game.win 事件

5️⃣ 游戏结束
   玩家结算
   → window.parent.postMessage（game:settlement）
   → 合作伙伴接收并调用 POST /game_end.php
   → Webhook: game.ended 事件

6️⃣ 查看历史
   GET /play_history.php
```

---

## 实时回调

### 自动发送的事件

NET8 在游戏过程中会自动将以下事件发送到 API：

#### 1. 下注事件（自动）

**发送时机:** 每次玩家下注

**实现位置:** `view_auth_pachi.js:167`

```javascript
// 自动调用
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

**API响应后的行为:**
- 通过 Webhook 将 `game.bet` 事件发送到合作伙伴服务器

#### 2. 获胜事件（自动）

**发送时机:** 每次玩家获胜

**实现位置:** `view_auth_pachi.js:216`

```javascript
// 自动调用
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

**API响应后的行为:**
- 通过 Webhook 将 `game.win` 事件发送到合作伙伴服务器

#### 3. 游戏结束通知（postMessage）

**发送时机:** 游戏结算时

**实现位置:** `view_auth_pachi.js:1240`

```javascript
// 向父窗口发送 postMessage
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

**合作伙伴端实现:**

```javascript
window.addEventListener('message', async (event) => {
    if (event.data.type === 'game:settlement') {
        // 调用 game_end.php
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

### 重要: koreaMode 标志

要发送实时回调（game_bet、game_win），必须设置 `koreaMode = true`。

**自动启用条件:**
- ✅ 在 `game_start.php` 中设置 `callbackUrl`
- ✅ 在 `game_start.php` 中设置 `callbackSecret`
- ✅ `initialPoints > 0`

满足这些条件后，游戏开始时会自动启用 `koreaMode`，实时回调将正常工作。

---

## 主要API详解

### 1. 开始游戏 API

**端点:** `POST /game_start.php`

**请求参数:**

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| modelId | string | ✅ | 机型ID（例: "SLOT-107"） |
| userId | string | ✅ | 合作伙伴的用户ID |
| machineNo | integer | ❌ | 指定机台号（空则自动分配） |
| initialPoints | integer | ✅ | 初始点数 |
| balanceMode | string | ❌ | 余额模式: "add"（累加）/ "set"（设置）。默认: "add" |
| consumeImmediately | boolean | ❌ | 立即消费: true（游戏开始时）/ false（结束时）。默认: true |
| lang | string | ❌ | 语言: zh/ja/ko/en。默认: ja |
| currency | string | ❌ | 货币: CNY/JPY/USD/TWD。默认: JPY |
| callbackUrl | string | ❌ | Webhook回调URL（必须HTTPS） |
| callbackSecret | string | ❌ | Webhook签名验证密钥 |

**balanceMode 说明:**

- **"add"（累加模式）:** 在现有余额上加上 `initialPoints`
  - 例: 现有500点 → initialPoints: 1000 → 总计1500点

- **"set"（设置模式）:** 将余额设置为 `initialPoints`
  - 例: 现有500点 → initialPoints: 1000 → 总计1000点

**请求示例:**

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
    "lang": "zh",
    "currency": "CNY",
    "callbackUrl": "https://your-server.com/webhook/net8",
    "callbackSecret": "your_secret_key_here"
  }'
```

**成功响应:**

```json
{
  "success": true,
  "sessionId": "sess_1738234567_23_abc123",
  "machineNo": 23,
  "modelId": "SLOT-107",
  "modelName": "北斗无双",
  "playUrl": "https://ifreamnet8-development.up.railway.app/ch/play_v2/?NO=23",
  "balance": {
    "current": 1000,
    "currency": "CNY"
  },
  "expiresAt": "2026-01-30T15:30:00Z"
}
```

---

### 2. 结束游戏 API

**端点:** `POST /game_end.php`

**请求参数:**

| 参数 | 类型 | 必需 | 说明 |
|------|------|------|------|
| sessionId | string | ✅ | 游戏会话ID |
| result | string | ❌ | 结果: "win"/"lose"/"draw"/"completed"。默认: "completed" |
| pointsWon | integer | ❌ | 获胜点数 |
| totalBets | integer | ✅ | 总下注额 |
| totalWins | integer | ✅ | 总获胜额 |
| resultData | object | ❌ | 附加结果数据 |

**请求示例:**

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

**成功响应:**

```json
{
  "success": true,
  "sessionId": "sess_1738234567_23_abc123",
  "finalBalance": 1150,
  "currency": "CNY",
  "summary": {
    "totalBets": 500,
    "totalWins": 650,
    "netProfit": 150,
    "gameTime": 1200
  }
}
```

---

### 3. 添加点数 API

**端点:** `POST /add_points.php`

**请求示例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/add_points.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "partner_user_12345",
    "points": 500,
    "currency": "CNY",
    "transactionId": "txn_20260130_001",
    "reason": "deposit"
  }'
```

**成功响应:**

```json
{
  "success": true,
  "balance": {
    "current": 1500,
    "currency": "CNY"
  },
  "transactionId": "txn_20260130_001"
}
```

---

## Webhook安全

### HMAC-SHA256 签名验证

所有 Webhook 请求都包含 HMAC-SHA256 签名。必须验证此签名。

### HTTP头

```
Content-Type: application/json
X-NET8-Signature: sha256={signature}
X-NET8-Timestamp: {unix_timestamp}
X-NET8-Event: {event_type}
```

### Node.js 签名验证示例

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, callbackSecret) {
    const signature = req.headers['x-net8-signature'];
    const timestamp = req.headers['x-net8-timestamp'];
    const rawBody = JSON.stringify(req.body);

    // 计算预期签名
    const expectedSignature = 'sha256=' + crypto
        .createHmac('sha256', callbackSecret)
        .update(rawBody)
        .digest('hex');

    // 比较签名（防止时序攻击）
    if (!crypto.timingSafeEqual(
        Buffer.from(signature),
        Buffer.from(expectedSignature)
    )) {
        throw new Error('签名无效');
    }

    // 验证时间戳（防止重放攻击）
    const now = Math.floor(Date.now() / 1000);
    if (Math.abs(now - parseInt(timestamp)) > 300) { // 5分钟
        throw new Error('时间戳过旧或过新');
    }

    return true;
}

// Express.js Webhook处理器
app.post('/webhook/net8', (req, res) => {
    try {
        verifyWebhookSignature(req, process.env.NET8_CALLBACK_SECRET);

        const { event, data } = req.body;

        switch (event) {
            case 'game.bet':
                console.log('下注:', data.betAmount, 'CNY');
                // 记录到数据库
                break;

            case 'game.win':
                console.log('获胜:', data.winAmount, 'CNY');
                // 记录到数据库
                break;

            case 'game.ended':
                console.log('游戏结束:', data);
                // 最终结算处理
                break;
        }

        res.json({ success: true });
    } catch (error) {
        console.error('Webhook错误:', error);
        res.status(400).json({ error: error.message });
    }
});
```

### PHP 签名验证示例

```php
<?php
function verifyWebhookSignature($rawBody, $signature, $timestamp, $callbackSecret) {
    // 计算预期签名
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $callbackSecret);

    // 比较签名
    if (!hash_equals($signature, $expectedSignature)) {
        throw new Exception('签名无效');
    }

    // 验证时间戳（防止重放攻击）
    $now = time();
    if (abs($now - intval($timestamp)) > 300) { // 5分钟
        throw new Exception('时间戳过旧或过新');
    }

    return true;
}

// Webhook接收
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
            error_log("下注: " . $eventData['betAmount'] . " CNY");
            break;

        case 'game.win':
            error_log("获胜: " . $eventData['winAmount'] . " CNY");
            break;

        case 'game.ended':
            error_log("游戏结束");
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

## 错误代码

| 代码 | HTTP状态 | 说明 |
|------|---------|------|
| INVALID_API_KEY | 401 | 无效或过期的API密钥 |
| UNAUTHORIZED | 401 | 缺少认证头 |
| MISSING_PARAMETER | 400 | 缺少必需参数 |
| INVALID_CALLBACK_URL | 400 | 回调URL必须使用HTTPS |
| MACHINE_NOT_AVAILABLE | 404 | 机台不可用 |
| INSUFFICIENT_BALANCE | 400 | 余额不足 |
| SESSION_NOT_FOUND | 404 | 会话未找到 |
| SESSION_EXPIRED | 400 | 会话已过期 |
| INTERNAL_ERROR | 500 | 内部服务器错误 |

### 错误响应格式

```json
{
  "error": "ERROR_CODE",
  "message": "人类可读的错误消息",
  "details": {
    "field": "附加详细信息"
  }
}
```

---

## 代码示例

### JavaScript（前端集成）

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

// 使用示例
const client = new NET8ApiClient('your_api_key');

// 开始游戏
const gameData = await client.startGame({
    modelId: 'SLOT-107',
    userId: 'user_12345',
    initialPoints: 1000,
    lang: 'zh',
    currency: 'CNY',
    callbackUrl: 'https://your-server.com/webhook/net8',
    callbackSecret: 'your_secret'
});

console.log('会话 ID:', gameData.sessionId);

// 在iframe中加载游戏
document.getElementById('game-iframe').src = gameData.playUrl;
```

---

## 测试方法

### 自动测试脚本

```bash
#!/bin/bash

BASE_URL="https://ifreamnet8-development.up.railway.app/api/v1"
API_KEY="your_api_key_here"

# 1. 认证
echo "1. 认证测试"
JWT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth.php" \
  -H "Content-Type: application/json" \
  -d "{\"apiKey\": \"$API_KEY\"}")

JWT_TOKEN=$(echo "$JWT_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$JWT_TOKEN" ]; then
    echo "❌ 认证失败"
    exit 1
fi

echo "✅ 认证成功: ${JWT_TOKEN:0:20}..."

# 2. 获取机台列表
echo ""
echo "2. 获取机台列表"
MACHINES=$(curl -s -X GET "$BASE_URL/list_machines.php?lang=zh" \
  -H "Authorization: Bearer $JWT_TOKEN")

echo "✅ 机台列表获取成功"

# 3. 开始游戏
echo ""
echo "3. 开始游戏"
GAME_START=$(curl -s -X POST "$BASE_URL/game_start.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "SLOT-107",
    "userId": "test_user_001",
    "initialPoints": 1000,
    "lang": "zh",
    "currency": "CNY"
  }')

SESSION_ID=$(echo "$GAME_START" | grep -o '"sessionId":"[^"]*' | cut -d'"' -f4)

if [ -z "$SESSION_ID" ]; then
    echo "❌ 游戏开始失败"
    exit 1
fi

echo "✅ 游戏开始成功: $SESSION_ID"

echo ""
echo "=========================================="
echo "✅ 所有测试成功完成！"
echo "=========================================="
```

### 浏览器控制台测试

1. 游戏开始后，打开浏览器开发者工具
2. 在Console标签中确认以下日志:

```
✅ [Korea] Korea mode ENABLED!
🎰 [BET-CALLBACK] Called with: { betAmount: 10, ... }
📡 [BET-CALLBACK] Response status: 200
✅ [BET-CALLBACK] Success

🎉 [WIN-CALLBACK] Called with: { winAmount: 50, ... }
📡 [WIN-CALLBACK] Response status: 200
✅ [WIN-CALLBACK] Success
```

3. 在Network标签中确认以下请求:
   - `auth.php` → 200 OK
   - `game_start.php` → 200 OK
   - `game_bet.php` → 200 OK（每次下注）
   - `game_win.php` → 200 OK（每次获胜）

---

## 生产环境部署

### 部署前检查清单

- [ ] **API密钥** 安全存储在环境变量中
- [ ] **Webhook密钥** 安全存储在环境变量中
- [ ] **HTTPS** 用于回调URL
- [ ] **签名验证** 在Webhook处理器中实现
- [ ] **错误处理和重试逻辑** 已实现
- [ ] **日志记录** 已配置用于审计
- [ ] **速率限制** 已实现
- [ ] **负载测试** 已完成
- [ ] **备份系统** 已就位
- [ ] **监控和告警** 已配置

### 安全最佳实践

1. **API密钥安全**
   - 不要在客户端代码中公开API密钥
   - 存储在环境变量中
   - 定期轮换

2. **JWT令牌管理**
   - 令牌在1小时后过期
   - 存储在服务器端
   - 不要在URL中传递令牌

3. **Webhook安全**
   - 始终验证HMAC-SHA256签名
   - 检查时间戳以防止重放攻击
   - 对回调URL使用HTTPS

4. **网络安全**
   - 对所有API请求使用HTTPS
   - 实施速率限制
   - 记录所有API调用
   - 监控异常活动

---

## 技术支持

如需技术帮助或支持:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技术文档:** https://docs.net8gaming.com

---

## 更新日志

### v1.0.0 (2026-01-30)
- ✅ 初始版本发布
- ✅ 全部18个API端点
- ✅ JWT认证系统
- ✅ 实时回调
- ✅ HMAC-SHA256安全
- ✅ 多语言支持（zh/ja/ko/en）
- ✅ 多货币支持（CNY/JPY/USD/TWD）

---

**© 2026 NET8 Gaming. 保留所有权利。**
