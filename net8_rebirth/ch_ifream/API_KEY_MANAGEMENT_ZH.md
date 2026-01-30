# NET8 API密钥发行・管理手册（中文版）

**版本:** 1.0.0
**最后更新:** 2026年1月30日
**目标:** NET8管理员・外部合作伙伴

---

## 📋 目录

1. [概述](#概述)
2. [API密钥管理系统](#api密钥管理系统)
3. [API密钥发行方法](#api密钥发行方法)
4. [API密钥类型](#api密钥类型)
5. [管理界面使用方法](#管理界面使用方法)
6. [向合作伙伴提供的方法](#向合作伙伴提供的方法)
7. [安全指南](#安全指南)
8. [故障排除](#故障排除)

---

## 概述

NET8采用**API密钥认证系统**，使外部合作伙伴能够使用API。本手册说明API密钥的发行和管理方法。

### 系统构成

- **管理界面:** `/xxxadmin/api_keys_manage.php`
- **数据库表:** `api_keys`, `api_usage_logs`
- **认证端点:** `/api/v1/auth.php`

---

## API密钥管理系统

### 数据库结构

#### api_keys 表

```sql
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NULL,
  `key_value` VARCHAR(100) NOT NULL UNIQUE,    -- API密钥值
  `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
  `name` VARCHAR(100) NULL,                     -- 密钥名称（用于识别）
  `environment` VARCHAR(20) NOT NULL DEFAULT 'test',  -- test or live
  `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000,  -- 速率限制
  `is_active` TINYINT(4) NOT NULL DEFAULT 1,    -- 启用/禁用
  `last_used_at` DATETIME NULL,                 -- 最后使用时间
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,                   -- 有效期（可选）
  PRIMARY KEY (`id`),
  KEY `idx_key_value` (`key_value`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

#### api_usage_logs 表（使用统计）

```sql
CREATE TABLE IF NOT EXISTS `api_usage_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_key_id` INT(10) UNSIGNED NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL,             -- 调用的端点
  `method` VARCHAR(10) NOT NULL,                -- HTTP方法
  `status_code` INT(10) UNSIGNED NULL,          -- 响应状态
  `response_time_ms` INT(10) UNSIGNED NULL,     -- 响应时间
  `ip_address` VARCHAR(45) NULL,                -- IP地址
  `user_agent` VARCHAR(512) NULL,               -- User-Agent
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_key_id` (`api_key_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## API密钥发行方法

### 方法1: 从管理界面发行（推荐）

**访问URL:**
```
https://ifreamnet8-development.up.railway.app/xxxadmin/api_keys_manage.php
```

**步骤:**

1. **登录管理界面**
   - 使用管理员账户登录

2. **前往"生成新API密钥"部分**

3. **输入必要信息:**
   - **密钥名称:** 合作伙伴识别名（例: "中国合作伙伴A公司"）
   - **环境:** `test`（测试用）或 `live`（生产用）

4. **点击"生成"按钮**

5. **复制生成的API密钥**
   - 例: `pk_live_a1b2c3d4e5f6...`
   - ⚠️ **重要:** 密钥值仅在此界面显示一次！务必复制并保存

---

### 方法2: 直接通过SQL发行

**测试环境密钥:**

```sql
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_test_abc123def456',  -- 随机字符串
  'public',
  '中国合作伙伴A公司（测试）',
  'test',
  10000,
  1
);
```

**生产环境密钥:**

```sql
INSERT INTO `api_keys` (
  `key_value`,
  `key_type`,
  `name`,
  `environment`,
  `rate_limit`,
  `is_active`
) VALUES (
  'pk_live_xyz789ghi012',  -- 随机字符串
  'public',
  '中国合作伙伴A公司（生产）',
  'live',
  100000,
  1
);
```

**安全生成密钥值:**

```bash
# Linux命令行生成
echo "pk_live_$(openssl rand -hex 16)"

# 输出示例: pk_live_f3a8b2c9d4e5f6g7h8i9j0k1l2m3n4o5
```

---

## API密钥类型

### 按环境分类

| 环境 | 前缀 | 用途 | 速率限制（默认） |
|------|------|------|----------------|
| **test** | `pk_test_` | 测试・开发用 | 10,000次/天 |
| **live** | `pk_live_` | 生产环境用 | 100,000次/天 |

### 按类型分类

| 类型 | 说明 | 权限 |
|------|------|------|
| **public** | 公钥 | 可在前端使用，只读 |
| **secret** | 密钥 | 仅服务器端，有写入权限 |

**当前实现:** 全部为 `public` 类型（通过JWT认证保护）

---

## 管理界面使用方法

### 界面构成

#### 1. API密钥生成部分

```
┌─────────────────────────────────────────┐
│ 🔑 生成新API密钥                         │
│                                         │
│ 密钥名称: [__________________________] │
│ 环境:     [test ▼]                     │
│                                         │
│ [生成] 按钮                             │
└─────────────────────────────────────────┘
```

#### 2. API密钥列表部分

```
┌─────────────────────────────────────────────────────────────┐
│ ID | 密钥名 | 密钥值 | 环境 | 速率限制 | 状态 | 最后使用 │
├────┼────────┼────────┼──────┼──────────┼──────┼─────────┤
│ 1  │ A公司  │ pk_... │ live │ 100000   │ 启用 │ 1小时前  │
│ 2  │ B公司  │ pk_... │ test │ 10000    │ 禁用 │ 3天前    │
└─────────────────────────────────────────────────────────────┘
```

#### 3. 使用统计部分

```
┌─────────────────────────────────────────┐
│ 📊 使用统计（最近7天）                   │
│                                         │
│ 日期       | 请求数    | 平均响应时间   │
│ 2026-01-30 | 15,234   | 120ms         │
│ 2026-01-29 | 14,892   | 115ms         │
└─────────────────────────────────────────┘
```

### 主要功能

#### 启用/禁用API密钥

1. 在API密钥列表中点击相应密钥的"切换"按钮
2. `is_active` 在 1（启用）↔ 0（禁用）之间切换
3. 禁用的密钥立即无法使用

**用途:**
- 临时停用（维护时）
- 安全事件发生时的紧急停用
- 合作伙伴合同终止时的永久禁用

#### 确认使用情况

- **最后使用时间:** 最后一次使用的时间
- **请求数:** 每日使用次数
- **平均响应时间:** API性能指标

---

## 向合作伙伴提供的方法

### 步骤1: 发行API密钥

1. 在管理界面为合作伙伴生成API密钥
2. 根据环境选择 `test` 或 `live`
3. 安全地复制密钥值

### 步骤2: 创建认证信息包

**提供的信息:**

```yaml
合作伙伴名称: 中国合作伙伴A公司
环境: live（生产环境）

认证信息:
  API Key: pk_live_f3a8b2c9d4e5f6g7h8i9j0k1l2m3n4o5
  Base URL: https://ifreamnet8-development.up.railway.app/api/v1

限制事项:
  速率限制: 100,000次请求/天
  有效期: 无（永久）

文档:
  - API_MANUAL_JA.md（日语版）
  - API_MANUAL_ZH.md（中文版）
  - REALTIME_CALLBACK_GUIDE_JA.md
  - REALTIME_CALLBACK_GUIDE_ZH.md
```

### 步骤3: 通过安全方式发送

**推荐方法:**

1. **加密邮件** - S/MIME 或 PGP加密
2. **安全文件共享** - Box, Dropbox Business, Google Drive（密码保护）
3. **专用门户** - 合作伙伴专用管理界面

**❌ 应避免的方法:**

- 明文邮件
- Slack/ChatWork等聊天工具
- SMS/电话

### 步骤4: 确认开始使用

1. 请求合作伙伴进行测试
2. 在管理界面确认使用统计
3. 无问题后迁移到生产环境

---

## 安全指南

### API密钥管理

#### ✅ 应该做的事

- **存储在环境变量中:** 存储在服务器环境变量
- **定期轮换:** 每3-6个月更新一次
- **最小权限原则:** 仅授予最小必要权限
- **监控使用情况:** 检测异常访问模式
- **备份:** 安全记录合作伙伴信息

#### ❌ 不应该做的事

- **硬编码:** 直接写入源代码
- **包含在版本控制中:** 提交到Git/SVN
- **在客户端使用:** 在浏览器JavaScript中使用
- **多个合作伙伴共享:** 1个合作伙伴 = 1个密钥
- **保留过期密钥:** 删除不需要的密钥

### 事件响应

#### 疑似泄露时

1. **立即禁用:** 在管理界面禁用相应密钥
2. **调查影响范围:** 在`api_usage_logs`中确认不正当使用
3. **发行新密钥:** 生成新密钥并提供给合作伙伴
4. **确定原因:** 调查泄露途径
5. **防止再发:** 加强安全措施

---

## 故障排除

### 问题1: "Invalid API Key"错误

**症状:**
```json
{
  "error": "INVALID_API_KEY",
  "message": "Invalid or expired API key"
}
```

**原因和解决方法:**

| 原因 | 确认方法 | 解决方法 |
|------|---------|---------|
| 密钥已禁用 | `is_active = 0` | 在管理界面启用 |
| 密钥不存在 | `SELECT * FROM api_keys WHERE key_value = '...'` | 使用正确的密钥 |
| 密钥已过期 | `expires_at < NOW()` | 发行新密钥 |
| 拼写错误 | 重新确认密钥值 | 准确复制粘贴 |

---

### 问题2: 速率限制错误

**症状:**
```json
{
  "error": "RATE_LIMIT_EXCEEDED",
  "message": "Rate limit exceeded"
}
```

**解决方法:**

```sql
-- 增加速率限制
UPDATE api_keys
SET rate_limit = 200000
WHERE key_value = 'pk_live_...';
```

---

### 问题3: 管理界面错误

**症状:** "api_keys表可能不存在"

**解决方法:**

```bash
# 执行SQL文件创建表
mysql -u username -p database_name < /path/to/setup_api_keys_table.sql
```

或从管理界面:

```sql
-- 复制并粘贴 setup_api_keys_table.sql 的内容执行
```

---

## 常见问题（FAQ）

### Q1: 可以发行多少个API密钥？

**A:** 没有限制，但从管理角度建议：
- 每个合作伙伴1个生产密钥
- 测试用1个开发密钥
- 总计保持在50-100个左右

---

### Q2: 可以设置API密钥的有效期吗？

**A:** 是的。在 `expires_at` 列设置日期时间：

```sql
UPDATE api_keys
SET expires_at = '2026-12-31 23:59:59'
WHERE id = 1;
```

---

### Q3: 可以重置API密钥吗？

**A:** 不可以。出于安全原因，一旦发行的密钥值无法更改。请发行新密钥并禁用旧密钥。

---

### Q4: 可以同时使用test和live环境的密钥吗？

**A:** 是的。可以为同一合作伙伴发行两个环境的密钥。

---

### Q5: 可以确认API密钥的使用统计吗？

**A:** 是的。在管理界面的"使用统计"部分可以确认：
- 每日请求数
- 平均响应时间
- 最后使用时间

---

## 附录: 自动化脚本

### API密钥生成脚本（Bash）

```bash
#!/bin/bash
# generate_api_key.sh

# 使用方法: ./generate_api_key.sh "合作伙伴名称" "test|live"

PARTNER_NAME=$1
ENVIRONMENT=${2:-test}
PREFIX="pk_${ENVIRONMENT}_"
KEY_VALUE="${PREFIX}$(openssl rand -hex 16)"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NET8 API密钥生成"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "合作伙伴名称: $PARTNER_NAME"
echo "环境: $ENVIRONMENT"
echo "API密钥: $KEY_VALUE"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "请执行以下SQL:"
echo ""
cat <<EOF
INSERT INTO api_keys (key_value, name, environment, rate_limit, is_active)
VALUES (
  '$KEY_VALUE',
  '$PARTNER_NAME',
  '$ENVIRONMENT',
  $([ "$ENVIRONMENT" = "live" ] && echo "100000" || echo "10000"),
  1
);
EOF
```

---

## 技术支持

关于API密钥发行・管理的问题:

- 📧 **Email:** support@net8gaming.com
- 🌐 **Website:** https://net8gaming.com
- 📱 **技术支持:** https://docs.net8gaming.com

---

**© 2026 NET8 Gaming. 保留所有权利。**
