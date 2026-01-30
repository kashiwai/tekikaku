# NET8 在线弹珠机 API 文档 / NET8 Online Pachislot API Documentation

**版本 / Version:** 1.0.0
**最后更新 / Last Updated:** 2026-01-30
**语言支持 / Languages:** 中文 (zh), 日语 (ja), 韩语 (ko), 英语 (en)
**货币支持 / Currencies:** CNY, JPY, USD, TWD

---

## 📋 目录 / Table of Contents

1. [概述 / Overview](#概述--overview)
2. [认证 / Authentication](#认证--authentication)
3. [API 端点列表 / API Endpoints](#api-端点列表--api-endpoints)
4. [游戏流程 / Game Flow](#游戏流程--game-flow)
5. [回调 Webhook / Callbacks](#回调-webhook--callbacks)
6. [错误代码 / Error Codes](#错误代码--error-codes)
7. [安全最佳实践 / Security Best Practices](#安全最佳实践--security-best-practices)

---

## 概述 / Overview

NET8 在线弹珠机 API 允许外部合作伙伴将真实的弹珠机游戏集成到他们的平台中。

NET8 Online Pachislot API allows external partners to integrate real pachislot games into their platforms.

### 基础 URL / Base URL

```
生产环境 / Production:
https://ifreamnet8-development.up.railway.app/api/v1

所有 API 端点 / All API endpoints:
https://ifreamnet8-development.up.railway.app/api/v1/{endpoint}
```

### 请求格式 / Request Format

- **方法 / Method:** POST (除非另有说明 / unless specified)
- **内容类型 / Content-Type:** `application/json`
- **认证 / Authentication:** `Authorization: Bearer {JWT_TOKEN}`
- **语言参数 / Language Parameter:** `lang=zh` (zh/ja/ko/en)
- **货币参数 / Currency Parameter:** `currency=CNY` (CNY/JPY/USD/TWD)

---

## 认证 / Authentication

### 1. 获取 JWT Token / Get JWT Token

**端点 / Endpoint:** `POST /auth.php`

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "apiKey": "your_api_key_here"
  }'
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "production"
}
```

**错误响应 / Error Response:**

```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

### 如何获取 API Key / How to Get API Key

请联系 NET8 团队获取您的 API Key。
Please contact NET8 team to obtain your API Key.

---

## API 端点列表 / API Endpoints

### 🎮 游戏管理 / Game Management

#### 1. 列出可用机台 / List Available Machines

**端点 / Endpoint:** `GET /list_machines.php?lang={language}`

**参数 / Parameters:**
- `lang` (可选 / optional): zh/ja/ko/en (默认 / default: ja)

**请求示例 / Request Example:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php?lang=zh" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "machines": [
    {
      "machineNo": 23,
      "modelId": "SLOT-107",
      "modelName": "北斗无双",
      "maker": "Sammy",
      "status": "available",
      "maxWin": 50000,
      "currency": "CNY"
    }
  ]
}
```

---

#### 2. 获取机型详情 / Get Model Details

**端点 / Endpoint:** `GET /models.php?lang={language}&modelId={modelId}`

**参数 / Parameters:**
- `lang` (可选 / optional): zh/ja/ko/en
- `modelId` (必需 / required): 机型ID / Model ID

**请求示例 / Request Example:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/models.php?lang=zh&modelId=SLOT-107" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "model": {
    "modelId": "SLOT-107",
    "modelName": "北斗无双",
    "maker": "Sammy",
    "maxChainWin": 9999,
    "jackpotProbability": "1/319.7",
    "maxPayout": 50000,
    "features": ["ART功能可用", "大奖金模式"],
    "description": "人气系列北斗无双的最新作品"
  }
}
```

---

#### 3. 开始游戏 / Start Game

**端点 / Endpoint:** `POST /game_start.php`

**重要参数说明 / Important Parameters:**

- **balanceMode**: 余额模式 / Balance Mode
  - `add`: 累加模式（将 initialPoints 添加到现有余额）/ Add to existing balance
  - `set`: 设置模式（将余额设置为 initialPoints）/ Set balance to initialPoints

- **consumeImmediately**: 立即消费 / Consume Immediately
  - `true`: 游戏开始时立即扣除押注金额 / Deduct bet amount at game start
  - `false`: 游戏结束时结算 / Settle at game end

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "SLOT-107",
    "userId": "chinese_partner_user_001",
    "machineNo": 23,
    "initialPoints": 1000,
    "balanceMode": "set",
    "consumeImmediately": false,
    "lang": "zh",
    "currency": "CNY",
    "callbackUrl": "https://your-server.com/webhook/net8",
    "callbackSecret": "your_webhook_secret_key"
  }'
```

**参数详解 / Parameter Details:**

| 参数 / Parameter | 类型 / Type | 必需 / Required | 说明 / Description |
|-----------------|------------|----------------|-------------------|
| modelId | string | ✅ | 机型ID / Model ID |
| userId | string | ✅ | 合作伙伴用户ID / Partner user ID |
| machineNo | integer | ❌ | 指定机台号（空则自动分配）/ Specific machine number |
| initialPoints | integer | ✅ | 初始点数 / Initial points |
| balanceMode | string | ❌ | 余额模式：add/set（默认：add）/ Balance mode |
| consumeImmediately | boolean | ❌ | 立即消费（默认：true）/ Consume immediately |
| lang | string | ❌ | 语言：zh/ja/ko/en（默认：ja）/ Language |
| currency | string | ❌ | 货币：CNY/JPY/USD/TWD（默认：JPY）/ Currency |
| callbackUrl | string | ❌ | 回调URL（HTTPS必需）/ Callback URL (HTTPS required) |
| callbackSecret | string | ❌ | Webhook密钥 / Webhook secret |

**成功响应 / Success Response:**

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

#### 4. 押注事件 / Bet Event

**端点 / Endpoint:** `POST /game_bet.php`

**用途 / Purpose:** 实时记录玩家的每次押注 / Record each bet in real-time

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_bet.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "sess_1738234567_23_abc123",
    "betAmount": 10,
    "betData": {
      "betType": "normal",
      "lines": 20
    }
  }'
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "eventId": "bet_evt_001",
  "balance": {
    "current": 990,
    "currency": "CNY"
  },
  "totalBets": 10
}
```

---

#### 5. 获胜事件 / Win Event

**端点 / Endpoint:** `POST /game_win.php`

**用途 / Purpose:** 实时记录玩家的每次获胜 / Record each win in real-time

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_win.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "sess_1738234567_23_abc123",
    "winAmount": 50,
    "winData": {
      "winType": "big_bonus",
      "multiplier": 5
    }
  }'
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "eventId": "win_evt_001",
  "balance": {
    "current": 1040,
    "currency": "CNY"
  },
  "totalWins": 50
}
```

---

#### 6. 结束游戏 / End Game

**端点 / Endpoint:** `POST /game_end.php`

**用途 / Purpose:** 结算游戏并触发最终回调 / Settle game and trigger final callback

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_end.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "sess_1738234567_23_abc123",
    "result": "win",
    "pointsWon": 50,
    "totalBets": 100,
    "totalWins": 150,
    "resultData": {
      "finalBalance": 1050,
      "gameTime": 300,
      "totalSpins": 50
    }
  }'
```

**参数详解 / Parameter Details:**

| 参数 / Parameter | 类型 / Type | 必需 / Required | 说明 / Description |
|-----------------|------------|----------------|-------------------|
| sessionId | string | ✅ | 会话ID / Session ID |
| result | string | ❌ | 结果：win/lose/draw（默认：completed）/ Result |
| pointsWon | integer | ❌ | 获胜点数 / Points won |
| totalBets | integer | ✅ | 总押注额 / Total bets |
| totalWins | integer | ✅ | 总获胜额 / Total wins |
| resultData | object | ❌ | 额外结果数据 / Additional result data |

**成功响应 / Success Response:**

```json
{
  "success": true,
  "sessionId": "sess_1738234567_23_abc123",
  "finalBalance": 1050,
  "currency": "CNY",
  "summary": {
    "totalBets": 100,
    "totalWins": 150,
    "netProfit": 50,
    "gameTime": 300
  }
}
```

---

### 💰 点数管理 / Points Management

#### 7. 添加点数 / Add Points

**端点 / Endpoint:** `POST /add_points.php`

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/add_points.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "chinese_partner_user_001",
    "points": 500,
    "currency": "CNY",
    "transactionId": "txn_001",
    "reason": "deposit"
  }'
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "balance": {
    "current": 1500,
    "currency": "CNY"
  },
  "transactionId": "txn_001"
}
```

---

#### 8. 设置余额 / Set Balance

**端点 / Endpoint:** `POST /set_balance.php`

**用途 / Purpose:** 直接设置用户余额（管理员功能）/ Set user balance directly (admin function)

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/set_balance.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "chinese_partner_user_001",
    "balance": 2000,
    "currency": "CNY",
    "reason": "admin_adjustment"
  }'
```

---

#### 9. 调整余额 / Adjust Balance

**端点 / Endpoint:** `POST /adjust_balance.php`

**用途 / Purpose:** 增加或减少用户余额 / Increase or decrease user balance

**请求示例 / Request Example:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/adjust_balance.php" \
  -H "Authorization: Bearer {JWT_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "chinese_partner_user_001",
    "action": "add",
    "amount": 100,
    "currency": "CNY"
  }'
```

---

### 📊 查询 API / Query APIs

#### 10. 游戏历史 / Play History

**端点 / Endpoint:** `GET /play_history.php?userId={userId}&lang={language}`

**请求示例 / Request Example:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/play_history.php?userId=chinese_partner_user_001&lang=zh" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

**成功响应 / Success Response:**

```json
{
  "success": true,
  "history": [
    {
      "sessionId": "sess_1738234567_23_abc123",
      "modelName": "北斗无双",
      "startTime": "2026-01-30T14:00:00Z",
      "endTime": "2026-01-30T14:15:00Z",
      "totalBets": 100,
      "totalWins": 150,
      "netProfit": 50,
      "currency": "CNY"
    }
  ]
}
```

---

#### 11. 推荐机型 / Recommended Models

**端点 / Endpoint:** `GET /recommended_models.php?lang={language}`

**请求示例 / Request Example:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/recommended_models.php?lang=zh" \
  -H "Authorization: Bearer {JWT_TOKEN}"
```

---

## 游戏流程 / Game Flow

### 完整游戏流程示意图 / Complete Game Flow

```
1. 认证 / Authentication
   POST /auth.php
   → 获取 JWT Token / Get JWT Token

2. 查询可用机台 / Query Available Machines
   GET /list_machines.php?lang=zh
   → 获取机台列表 / Get machine list

3. 开始游戏 / Start Game
   POST /game_start.php
   → 获取 sessionId 和 playUrl / Get sessionId and playUrl
   → 用户在 iframe 中游戏 / User plays in iframe

4. 游戏中事件 / In-Game Events (实时 / Real-time)
   POST /game_bet.php (每次押注 / Each bet)
   POST /game_win.php (每次获胜 / Each win)
   ← 触发回调 / Trigger callbacks: game.bet, game.win

5. 结束游戏 / End Game
   POST /game_end.php
   → 最终结算 / Final settlement
   ← 触发回调 / Trigger callback: game.ended

6. 查询历史 / Query History
   GET /play_history.php
```

### 代码示例 / Code Example

```javascript
// 1. 认证 / Authentication
const authResponse = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/auth.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ apiKey: 'your_api_key' })
});
const { token } = await authResponse.json();

// 2. 开始游戏 / Start Game
const gameStartResponse = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/game_start.php', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    modelId: 'SLOT-107',
    userId: 'chinese_partner_user_001',
    initialPoints: 1000,
    balanceMode: 'set',
    consumeImmediately: false,
    lang: 'zh',
    currency: 'CNY',
    callbackUrl: 'https://your-server.com/webhook/net8',
    callbackSecret: 'your_webhook_secret'
  })
});
const { sessionId, playUrl } = await gameStartResponse.json();

// 3. 在 iframe 中加载游戏 / Load game in iframe
document.getElementById('game-iframe').src = playUrl;

// 4. 结束游戏 / End Game (当用户完成游戏时 / When user finishes)
const gameEndResponse = await fetch('https://ifreamnet8-development.up.railway.app/api/v1/game_end.php', {
  method: 'POST',
  headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    sessionId: sessionId,
    result: 'win',
    totalBets: 100,
    totalWins: 150
  })
});
```

---

## 回调 Webhook / Callbacks

### Webhook 事件类型 / Webhook Event Types

NET8 会在游戏过程中向您的 `callbackUrl` 发送实时事件通知。
NET8 will send real-time event notifications to your `callbackUrl` during gameplay.

#### 事件类型 / Event Types:

1. **game.bet** - 玩家押注 / Player bets
2. **game.win** - 玩家获胜 / Player wins
3. **game.ended** - 游戏结束 / Game ended

### Webhook 安全验证 / Webhook Security Verification

每个 webhook 请求都包含 HMAC-SHA256 签名，您必须验证此签名以确保请求来自 NET8。
Each webhook request includes an HMAC-SHA256 signature that you must verify to ensure it's from NET8.

#### HTTP 头部 / HTTP Headers:

```
Content-Type: application/json
X-NET8-Signature: sha256={signature}
X-NET8-Timestamp: {unix_timestamp}
X-NET8-Event: {event_type}
```

#### 验证签名示例 (Node.js) / Signature Verification Example:

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, callbackSecret) {
  const signature = req.headers['x-net8-signature'];
  const timestamp = req.headers['x-net8-timestamp'];
  const rawBody = JSON.stringify(req.body);

  // 计算期望的签名 / Calculate expected signature
  const expectedSignature = 'sha256=' + crypto
    .createHmac('sha256', callbackSecret)
    .update(rawBody)
    .digest('hex');

  // 比较签名 / Compare signatures
  if (signature !== expectedSignature) {
    throw new Error('Invalid signature');
  }

  // 验证时间戳（防止重放攻击）/ Verify timestamp (prevent replay attacks)
  const now = Math.floor(Date.now() / 1000);
  if (Math.abs(now - parseInt(timestamp)) > 300) { // 5分钟 / 5 minutes
    throw new Error('Timestamp too old');
  }

  return true;
}

// Express.js 示例 / Express.js Example
app.post('/webhook/net8', (req, res) => {
  try {
    verifyWebhookSignature(req, process.env.NET8_CALLBACK_SECRET);

    const { event, data } = req.body;

    switch (event) {
      case 'game.bet':
        console.log('用户押注 / User bet:', data.betAmount);
        // 更新您的数据库 / Update your database
        break;

      case 'game.win':
        console.log('用户获胜 / User won:', data.winAmount);
        // 更新您的数据库 / Update your database
        break;

      case 'game.ended':
        console.log('游戏结束 / Game ended:', data.finalBalance);
        // 最终结算 / Final settlement
        break;
    }

    res.json({ success: true });
  } catch (error) {
    console.error('Webhook验证失败 / Webhook verification failed:', error);
    res.status(400).json({ error: error.message });
  }
});
```

#### 验证签名示例 (PHP) / Signature Verification Example:

```php
<?php
function verifyWebhookSignature($rawBody, $signature, $timestamp, $callbackSecret) {
    // 计算期望的签名 / Calculate expected signature
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $rawBody, $callbackSecret);

    // 比较签名 / Compare signatures
    if (!hash_equals($signature, $expectedSignature)) {
        throw new Exception('Invalid signature');
    }

    // 验证时间戳（防止重放攻击）/ Verify timestamp (prevent replay attacks)
    $now = time();
    if (abs($now - intval($timestamp)) > 300) { // 5分钟 / 5 minutes
        throw new Exception('Timestamp too old');
    }

    return true;
}

// 接收 webhook / Receive webhook
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
            error_log("用户押注 / User bet: " . $eventData['betAmount']);
            break;

        case 'game.win':
            error_log("用户获胜 / User won: " . $eventData['winAmount']);
            break;

        case 'game.ended':
            error_log("游戏结束 / Game ended: " . $eventData['finalBalance']);
            break;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
```

### Webhook 数据格式 / Webhook Data Format

#### game.bet 事件 / game.bet Event

```json
{
  "event": "game.bet",
  "timestamp": 1738234567,
  "data": {
    "sessionId": "sess_1738234567_23_abc123",
    "userId": "chinese_partner_user_001",
    "betAmount": 10,
    "balance": 990,
    "currency": "CNY"
  }
}
```

#### game.win 事件 / game.win Event

```json
{
  "event": "game.win",
  "timestamp": 1738234567,
  "data": {
    "sessionId": "sess_1738234567_23_abc123",
    "userId": "chinese_partner_user_001",
    "winAmount": 50,
    "balance": 1040,
    "currency": "CNY"
  }
}
```

#### game.ended 事件 / game.ended Event

```json
{
  "event": "game.ended",
  "timestamp": 1738234567,
  "data": {
    "sessionId": "sess_1738234567_23_abc123",
    "userId": "chinese_partner_user_001",
    "totalBets": 100,
    "totalWins": 150,
    "netProfit": 50,
    "finalBalance": 1050,
    "currency": "CNY",
    "gameTime": 300
  }
}
```

---

## 错误代码 / Error Codes

| 代码 / Code | HTTP状态 / HTTP Status | 说明 / Description |
|-------------|----------------------|-------------------|
| INVALID_API_KEY | 401 | 无效或过期的API密钥 / Invalid or expired API key |
| UNAUTHORIZED | 401 | 缺少授权头 / Missing authorization header |
| MISSING_PARAMETER | 400 | 缺少必需参数 / Missing required parameter |
| INVALID_CALLBACK_URL | 400 | 回调URL必须使用HTTPS / Callback URL must use HTTPS |
| MACHINE_NOT_AVAILABLE | 404 | 机台不可用 / Machine not available |
| INSUFFICIENT_BALANCE | 400 | 余额不足 / Insufficient balance |
| SESSION_NOT_FOUND | 404 | 会话不存在 / Session not found |
| SESSION_EXPIRED | 400 | 会话已过期 / Session expired |
| INTERNAL_ERROR | 500 | 内部服务器错误 / Internal server error |

### 错误响应格式 / Error Response Format

```json
{
  "error": "ERROR_CODE",
  "message": "Human-readable error message",
  "details": {
    "field": "additional context"
  }
}
```

---

## 安全最佳实践 / Security Best Practices

### 1. API Key 安全 / API Key Security

- ✅ **不要在客户端代码中暴露 API Key** / Never expose API key in client-side code
- ✅ **使用环境变量存储 API Key** / Store API key in environment variables
- ✅ **定期轮换 API Key** / Rotate API key regularly
- ❌ **不要将 API Key 提交到版本控制** / Never commit API key to version control

### 2. JWT Token 管理 / JWT Token Management

- ✅ **Token 有效期为 1 小时** / Token valid for 1 hour
- ✅ **Token 过期后重新认证** / Re-authenticate when token expires
- ✅ **在服务器端存储 Token** / Store token on server-side
- ❌ **不要在 URL 中传递 Token** / Never pass token in URL

### 3. Webhook 安全 / Webhook Security

- ✅ **始终验证 HMAC-SHA256 签名** / Always verify HMAC-SHA256 signature
- ✅ **检查时间戳防止重放攻击** / Check timestamp to prevent replay attacks
- ✅ **使用 HTTPS 作为回调 URL** / Use HTTPS for callback URL
- ✅ **妥善保管 callbackSecret** / Keep callbackSecret secure

### 4. 网络安全 / Network Security

- ✅ **所有 API 请求使用 HTTPS** / Use HTTPS for all API requests
- ✅ **实施速率限制** / Implement rate limiting
- ✅ **记录所有 API 调用日志** / Log all API calls
- ✅ **监控异常活动** / Monitor for unusual activity

### 5. 数据验证 / Data Validation

- ✅ **验证所有输入参数** / Validate all input parameters
- ✅ **使用参数化查询防止 SQL 注入** / Use parameterized queries to prevent SQL injection
- ✅ **清理用户输入** / Sanitize user inputs
- ✅ **实施输入长度限制** / Enforce input length limits

---

## 联系支持 / Contact Support

如有技术问题或需要帮助，请联系：
For technical questions or assistance, please contact:

**NET8 技术支持团队 / NET8 Technical Support Team**

- 📧 Email: support@net8gaming.com
- 🌐 Website: https://net8gaming.com
- 📱 技术文档 / Technical Docs: https://docs.net8gaming.com

---

## 更新日志 / Changelog

### v1.0.0 (2026-01-30)
- ✅ 初始版本发布 / Initial release
- ✅ 多语言支持 (zh/ja/ko/en) / Multi-language support
- ✅ 多货币支持 (CNY/JPY/USD/TWD) / Multi-currency support
- ✅ JWT 认证系统 / JWT authentication system
- ✅ 实时游戏事件回调 / Real-time game event callbacks
- ✅ HMAC-SHA256 安全签名 / HMAC-SHA256 security signatures

---

**© 2026 NET8 Gaming. All rights reserved.**
