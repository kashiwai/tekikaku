/**
 * NET8 API 集成示例（Node.js）
 *
 * 使用方法:
 * 1. npm install node-fetch
 * 2. node net8_integration_sample.js
 */

const crypto = require('crypto');

// 配置
const CONFIG = {
  apiKey: 'pk_live_42c61da908dd515d9f0a6a99406c4dcb',
  baseUrl: 'https://ifreamnet8-development.up.railway.app/api/v1',
  callbackSecret: 'your_secret_key_123' // 请更改为您自己的密钥
};

/**
 * 1. 获取 JWT 令牌
 */
async function getAuthToken() {
  console.log('🔑 获取 JWT 令牌...');

  const response = await fetch(`${CONFIG.baseUrl}/auth.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ apiKey: CONFIG.apiKey })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error('认证失败: ' + data.message);
  }

  console.log('✅ 认证成功!');
  console.log('   令牌:', data.token.substring(0, 50) + '...');
  console.log('   有效期:', data.expiresIn, '秒（1小时）');
  console.log('   环境:', data.environment);
  console.log('');

  return data.token;
}

/**
 * 2. 获取机器列表
 */
async function getMachines(token) {
  console.log('🎰 获取可用机器列表...');

  const response = await fetch(`${CONFIG.baseUrl}/list_machines.php`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    }
  });

  const data = await response.json();

  if (data.success) {
    console.log(`✅ 找到 ${data.machines?.length || 0} 台机器`);
    if (data.machines && data.machines.length > 0) {
      console.log('   第一台机器:', data.machines[0].machineNo, '-', data.machines[0].modelName);
    }
  }

  console.log('');
  return data.machines || [];
}

/**
 * 3. 开始游戏
 */
async function startGame(token, machineNo) {
  console.log('🚀 开始游戏...');

  const response = await fetch(`${CONFIG.baseUrl}/game_start.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: 'test_user_001',
      userName: '测试用户',
      machineNo: machineNo,
      initialCredit: 50000,
      lang: 'zh',
      currency: 'CNY',
      callbackUrl: 'https://your-server.com/api/webhook/net8',
      callbackSecret: CONFIG.callbackSecret
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log('✅ 游戏开始成功!');
    console.log('   Session ID:', data.sessionId);
    console.log('   游戏 URL:', data.gameUrl);
    console.log('');
    console.log('💡 在 iframe 中显示此 URL 即可开始游戏');
  } else {
    console.log('❌ 游戏开始失败:', data.message);
  }

  console.log('');
  return data;
}

/**
 * 4. Webhook 签名验证函数
 */
function verifyWebhookSignature(payload, signature, secret) {
  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');

  return signature === computedSignature;
}

/**
 * 5. Webhook 处理示例（Express）
 */
function setupWebhookHandler() {
  console.log('📡 Webhook 处理示例代码:');
  console.log('');
  console.log('```javascript');
  console.log('const express = require("express");');
  console.log('const app = express();');
  console.log('');
  console.log('app.post("/api/webhook/net8", express.json(), (req, res) => {');
  console.log('  // 1. 验证签名');
  console.log('  const signature = req.headers["x-net8-signature"];');
  console.log('  const secret = "your_secret_key_123";');
  console.log('  ');
  console.log('  if (!verifyWebhookSignature(req.body, signature, secret)) {');
  console.log('    return res.status(401).json({ error: "Invalid signature" });');
  console.log('  }');
  console.log('  ');
  console.log('  // 2. 处理事件');
  console.log('  const { event, data } = req.body;');
  console.log('  ');
  console.log('  switch(event) {');
  console.log('    case "game.bet":');
  console.log('      console.log("🎰 投注:", data.betAmount, "CNY");');
  console.log('      // 保存到数据库');
  console.log('      break;');
  console.log('  ');
  console.log('    case "game.win":');
  console.log('      console.log("🎉 获胜:", data.winAmount, "CNY");');
  console.log('      // 保存到数据库');
  console.log('      break;');
  console.log('  ');
  console.log('    case "game.end":');
  console.log('      console.log("🏁 游戏结束");');
  console.log('      console.log("   累计投注:", data.totalBets, "CNY");');
  console.log('      console.log("   累计获胜:", data.totalWins, "CNY");');
  console.log('      // 更新用户余额');
  console.log('      break;');
  console.log('  }');
  console.log('  ');
  console.log('  res.json({ success: true });');
  console.log('});');
  console.log('');
  console.log('app.listen(3000);');
  console.log('```');
  console.log('');
}

/**
 * 主函数 - 完整测试流程
 */
async function main() {
  console.log('');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('NET8 API 集成测试');
  console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
  console.log('');

  try {
    // 步骤 1: 获取令牌
    const token = await getAuthToken();

    // 步骤 2: 获取机器列表
    const machines = await getMachines(token);

    // 步骤 3: 开始游戏（如果有机器）
    if (machines.length > 0) {
      await startGame(token, machines[0].machineNo);
    }

    // 步骤 4: 显示 Webhook 处理示例
    setupWebhookHandler();

    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('✅ 测试完成!');
    console.log('');
    console.log('下一步:');
    console.log('1. 实现 Webhook 接收端点');
    console.log('2. 设置您自己的 callbackUrl 和 callbackSecret');
    console.log('3. 在 iframe 中显示游戏 URL');
    console.log('4. 开始接收实时游戏数据!');
    console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
    console.log('');

  } catch (error) {
    console.error('❌ 错误:', error.message);
  }
}

// Node.js 18+ 使用内置 fetch
// Node.js < 18 需要: npm install node-fetch
if (typeof fetch === 'undefined') {
  global.fetch = require('node-fetch');
}

// 运行测试
main();
