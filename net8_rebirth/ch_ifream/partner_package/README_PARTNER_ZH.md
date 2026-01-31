# NET8 合作伙伴集成包

**发行日期:** 2026年1月31日
**版本:** 1.0.0

---

## 📦 包含内容

此集成包包含您开始使用 NET8 在线弹珠机 API 所需的所有文件。

---

## 🔑 您的 API 认证信息

```yaml
环境: Production（生产环境）
API 密钥: pk_live_42c61da908dd515d9f0a6a99406c4dcb
Base URL: https://ifreamnet8-development.up.railway.app/api/v1
速率限制: 100,000 次请求/天
有效期: 永久
```

**⚠️ 重要:** 请将此 API 密钥保存在环境变量中，不要直接写入源代码。

---

## 📁 文件清单

### 📄 文档文件

| 文件名 | 大小 | 说明 | 优先级 |
|-------|------|------|--------|
| `SIMPLE_INTEGRATION_GUIDE_ZH.md` | 15KB | **⭐ 从这里开始！** 最简集成指南（仅 iframe + 用户连接） | ⭐⭐⭐⭐ 推荐！ |
| `PARTNER_ONBOARDING_PACKAGE_ZH.md` | 40KB | 完整的集成指南（详细版） | ⭐⭐⭐ 参考 |
| `API_MANUAL_ZH.md` | 71KB | 全部18个 API 端点详细文档 | ⭐⭐ 参考 |
| `REALTIME_CALLBACK_GUIDE_ZH.md` | 53KB | 实时数据回调实现指南 | ⭐⭐ 参考 |

### 💻 示例代码文件

| 文件名 | 语言 | 说明 |
|-------|------|------|
| `test_auth.sh` | Bash | 快速认证测试脚本（5秒） |
| `net8_integration_sample.js` | Node.js | 完整集成示例（含 Webhook） |
| `net8_integration_sample.php` | PHP | 完整集成示例（含 Webhook） |

---

## 🎯 集成方式说明

### 方式 1: 简单集成（推荐！2-3小时完成）

**适用于:** 只需要 iframe 嵌入游戏 + API 用户连接

**阅读文档:**
1. ✅ `SIMPLE_INTEGRATION_GUIDE_ZH.md`（本包中）
2. ✅ 运行 `test_auth.sh` 测试

**您只需要做3件事:**
- 用 iframe 显示 NET8 游戏（游戏本身由 NET8 提供）
- 用 API 管理用户信息和积分
- 接收 Webhook 获取游戏结果

### 方式 2: 完整集成（需要更多自定义功能）

**适用于:** 需要使用全部 API 功能

**阅读文档:**
1. ✅ `PARTNER_ONBOARDING_PACKAGE_ZH.md`
2. ✅ `API_MANUAL_ZH.md`
3. ✅ `REALTIME_CALLBACK_GUIDE_ZH.md`

---

## 🚀 快速开始（推荐！最快2小时完成集成）

### ⭐ 推荐: 简单集成路线

如果您只需要 **iframe 嵌入游戏** + **用户连接 API**:

**步骤 1: 测试认证（5秒）**

```bash
chmod +x test_auth.sh
./test_auth.sh
```

**步骤 2: 阅读简单集成指南（20分钟）**

打开 `SIMPLE_INTEGRATION_GUIDE_ZH.md` 了解:
- 如何用 iframe 显示游戏（只需4行代码）
- 如何用 API 管理用户积分
- 如何接收游戏结果（Webhook）

**步骤 3: 开始实现（1-2小时）**

参考 `SIMPLE_INTEGRATION_GUIDE_ZH.md` 中的示例代码。

---

### 📖 可选: 完整集成路线

如果您需要使用全部 API 功能:

**步骤 1: 测试认证（5秒）**

```bash
chmod +x test_auth.sh
./test_auth.sh
```

**步骤 2: 运行集成示例**

**Node.js:**
```bash
npm install node-fetch  # Node.js < 18
node net8_integration_sample.js
```

**PHP:**
```bash
php net8_integration_sample.php
```

**步骤 3: 阅读完整文档**

打开 `PARTNER_ONBOARDING_PACKAGE_ZH.md` 开始完整的集成。

---

## 📚 推荐阅读顺序

### 🚀 快速路线（仅 iframe + 用户连接，2-3小时完成）

**第1步 - 理解集成（30分钟）**

1. ✅ `README_PARTNER_ZH.md`（本文件）- 10分钟
2. ✅ `SIMPLE_INTEGRATION_GUIDE_ZH.md` - 20分钟 ⭐ **从这里开始！**
3. ✅ 运行 `test_auth.sh` - 5分钟

**第2步 - 实现集成（1-2小时）**

4. ✅ 参考 `SIMPLE_INTEGRATION_GUIDE_ZH.md` 实现后端 API
5. ✅ 实现 iframe 显示
6. ✅ 实现 Webhook 接收端点

**第3步 - 测试（30分钟）**

7. ✅ 测试游戏开始
8. ✅ 测试 Webhook 接收
9. ✅ 测试用户余额更新

---

### 📖 完整路线（使用全部 API 功能，1-2天完成）

**第1天 - 理解 API（1-2小时）**

1. ✅ `README_PARTNER_ZH.md`（本文件）- 10分钟
2. ✅ `PARTNER_ONBOARDING_PACKAGE_ZH.md` - 30分钟
3. ✅ 运行测试脚本 `test_auth.sh` - 5分钟
4. ✅ 运行集成示例（Node.js 或 PHP）- 15分钟

**第2天 - 实现集成（4-6小时）**

5. ✅ `API_MANUAL_ZH.md` - 参考具体端点
6. ✅ 实现游戏开始功能
7. ✅ 实现 iframe 显示
8. ✅ 测试基本游戏流程

**第3天 - 实时回调（2-4小时）**

9. ✅ `REALTIME_CALLBACK_GUIDE_ZH.md` - 详细阅读
10. ✅ 实现 Webhook 接收端点
11. ✅ 实现 HMAC 签名验证
12. ✅ 测试实时数据接收

---

## 🎮 核心功能概述

### 1. 认证流程

```
API 密钥 → JWT 令牌（1小时有效）→ 使用令牌访问所有端点
```

### 2. 游戏流程

```
获取机器列表 → 选择机器 → 开始游戏 → 显示 iframe → 接收回调 → 游戏结束
```

### 3. 实时回调（自动）

玩家游戏时，以下事件自动发送到您的服务器:

| 事件 | 发送时机 | 数据内容 |
|------|---------|---------|
| `game.bet` | 每次投注 | 投注额、余额 |
| `game.win` | 每次获胜 | 获胜额、余额 |
| `game.end` | 游戏结束 | 累计投注、累计获胜、最终余额 |

---

## 🔔 实时回调设置

### 在 game_start.php 中添加参数

```javascript
{
  "userId": "user_001",
  "machineNo": "001",
  "initialCredit": 50000,
  "callbackUrl": "https://your-server.com/api/webhook/net8",  // 您的 Webhook URL
  "callbackSecret": "your_secret_key_123"                      // 您的密钥
}
```

### Webhook 接收端点实现

参考:
- Node.js: `net8_integration_sample.js` 中的 `setupWebhookHandler()`
- PHP: `net8_integration_sample.php` 中的 `showWebhookExample()`

---

## 🌐 多语言・多货币支持

### 支持的语言

| 代码 | 语言 |
|-----|-----|
| `zh` | 中文（简体） |
| `ja` | 日语 |
| `ko` | 韩语 |
| `en` | 英语 |

### 支持的货币

| 代码 | 货币 | 最小投注 |
|-----|-----|---------|
| `CNY` | 人民币 | ¥30 |
| `JPY` | 日元 | ¥300 |
| `USD` | 美元 | $3 |
| `TWD` | 新台币 | NT$90 |

---

## 🛠️ 技术要求

### 服务器端

- **Node.js:** 14+ 推荐（18+ 内置 fetch）
- **PHP:** 7.4+ 推荐
- **HTTPS:** 必须（Webhook 接收）
- **防火墙:** 允许来自 NET8 的 Webhook 请求

### 客户端

- **iframe:** 必须支持
- **现代浏览器:** Chrome, Firefox, Safari, Edge

---

## 🔒 安全最佳实践

### ✅ 应该做的事

- 将 API 密钥保存在环境变量中
- 仅在服务器端使用 API 密钥
- 实现 HMAC-SHA256 签名验证（Webhook）
- 使用 HTTPS 通信
- 定期轮换 API 密钥（3-6个月）
- 记录所有 API 请求和错误

### ❌ 不应该做的事

- 在客户端 JavaScript 中使用 API 密钥
- 将 API 密钥提交到 Git
- 在明文邮件中发送 API 密钥
- 跳过 Webhook 签名验证
- 与其他合作伙伴共享 API 密钥

---

## 📊 API 端点列表

### 认证

- `POST /auth.php` - 获取 JWT 令牌

### 游戏管理

- `POST /game_start.php` - 开始游戏
- `POST /game_bet.php` - 记录投注（自动回调）
- `POST /game_win.php` - 记录获胜（自动回调）
- `POST /game_end.php` - 结束游戏

### 积分管理

- `POST /add_points.php` - 添加积分
- `POST /set_balance.php` - 设置余额
- `POST /adjust_balance.php` - 调整余额

### 查询

- `GET /list_machines.php` - 获取机器列表
- `GET /models.php` - 获取机型列表
- `GET /play_history.php` - 获取游戏历史
- `GET /check_machines.php` - 检查机器状态
- `GET /recommended_models.php` - 获取推荐机型

完整说明请参考 `API_MANUAL_ZH.md`

---

## 🐛 故障排除

### 常见错误

| 错误 | 原因 | 解决方法 |
|------|------|---------|
| `Invalid API Key` | API 密钥错误或已禁用 | 检查密钥是否正确 |
| `Invalid or expired token` | JWT 令牌已过期（1小时） | 重新获取令牌 |
| `Rate limit exceeded` | 超过速率限制（100,000/天） | 联系支持或使用缓存 |
| `Machine not available` | 机器正在使用中 | 选择其他机器 |

详细故障排除请参考 `PARTNER_ONBOARDING_PACKAGE_ZH.md`

---

## 📞 技术支持

### 支持渠道

- **📧 Email:** support@net8gaming.com
- **🌐 Website:** https://net8gaming.com
- **📱 技术文档:** https://docs.net8gaming.com
- **⏰ 营业时间:** 工作日 9:00〜18:00 (JST)

### 紧急联系方式

- **安全事件:** security@net8gaming.com（24小时）
- **API 故障:** api-support@net8gaming.com（24小时）

---

## ✅ 集成检查清单

### 开发环境（1-2天）

- [ ] API 密钥认证测试成功
- [ ] 获取机器列表成功
- [ ] 游戏开始成功
- [ ] iframe 显示游戏成功
- [ ] Webhook 接收测试成功

### 预生产环境（3-5天）

- [ ] 实现完整的游戏流程
- [ ] 实现 Webhook 处理
- [ ] 实现 HMAC 签名验证
- [ ] 实现错误处理
- [ ] 实现日志记录
- [ ] 负载测试

### 生产环境（1天）

- [ ] 安全审计通过
- [ ] 性能测试通过
- [ ] 监控系统就位
- [ ] 备份计划就位
- [ ] 逐步推出（10% → 50% → 100%）

---

## 🎯 估计时间表

| 阶段 | 时间 | 任务 |
|-----|------|------|
| **第1天** | 1-2小时 | 阅读文档、运行测试 |
| **第2-3天** | 4-8小时 | 基本集成实现 |
| **第4-5天** | 4-6小时 | Webhook 实现和测试 |
| **第6-10天** | 2-4天 | 预生产环境测试 |
| **第11天** | 1天 | 生产环境部署 |

**总计:** 约 10-12 天完成完整集成

---

## 💡 提示

### 快速测试

```bash
# 1分钟快速测试
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

### 环境变量设置

**Node.js (.env):**
```
NET8_API_KEY=pk_live_42c61da908dd515d9f0a6a99406c4dcb
NET8_BASE_URL=https://ifreamnet8-development.up.railway.app/api/v1
NET8_CALLBACK_SECRET=your_secret_key_123
```

**PHP (.env):**
```
NET8_API_KEY=pk_live_42c61da908dd515d9f0a6a99406c4dcb
NET8_BASE_URL=https://ifreamnet8-development.up.railway.app/api/v1
NET8_CALLBACK_SECRET=your_secret_key_123
```

---

## 🎉 开始吧!

所有准备工作已完成。从 `PARTNER_ONBOARDING_PACKAGE_ZH.md` 开始，您可以在30分钟内开始测试集成。

**立即测试:**

```bash
./test_auth.sh
```

祝您集成顺利！如有任何问题，请随时联系我们的支持团队。

---

**© 2026 NET8 Gaming. 保留所有权利。**
