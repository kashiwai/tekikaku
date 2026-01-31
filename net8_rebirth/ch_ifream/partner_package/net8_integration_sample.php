<?php
/**
 * NET8 API 集成示例（PHP）
 *
 * 使用方法:
 * php net8_integration_sample.php
 */

// 配置
define('API_KEY', 'pk_live_42c61da908dd515d9f0a6a99406c4dcb');
define('BASE_URL', 'https://ifreamnet8-development.up.railway.app/api/v1');
define('CALLBACK_SECRET', 'your_secret_key_123'); // 请更改为您自己的密钥

/**
 * 发送 HTTP 请求
 */
function sendRequest($endpoint, $method = 'GET', $data = null, $token = null) {
    $url = BASE_URL . $endpoint;

    $headers = [
        'Content-Type: application/json'
    ];

    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}

/**
 * 1. 获取 JWT 令牌
 */
function getAuthToken() {
    echo "🔑 获取 JWT 令牌...\n";

    $response = sendRequest('/auth.php', 'POST', [
        'apiKey' => API_KEY
    ]);

    if ($response['code'] !== 200 || !$response['data']['success']) {
        throw new Exception('认证失败: ' . ($response['data']['message'] ?? 'Unknown error'));
    }

    $data = $response['data'];

    echo "✅ 认证成功!\n";
    echo "   令牌: " . substr($data['token'], 0, 50) . "...\n";
    echo "   有效期: {$data['expiresIn']} 秒（1小时）\n";
    echo "   环境: {$data['environment']}\n";
    echo "\n";

    return $data['token'];
}

/**
 * 2. 获取机器列表
 */
function getMachines($token) {
    echo "🎰 获取可用机器列表...\n";

    $response = sendRequest('/list_machines.php', 'GET', null, $token);

    $machines = $response['data']['machines'] ?? [];

    echo "✅ 找到 " . count($machines) . " 台机器\n";
    if (count($machines) > 0) {
        echo "   第一台机器: {$machines[0]['machineNo']} - {$machines[0]['modelName']}\n";
    }
    echo "\n";

    return $machines;
}

/**
 * 3. 开始游戏
 */
function startGame($token, $machineNo) {
    echo "🚀 开始游戏...\n";

    $response = sendRequest('/game_start.php', 'POST', [
        'userId' => 'test_user_001',
        'userName' => '测试用户',
        'machineNo' => $machineNo,
        'initialCredit' => 50000,
        'lang' => 'zh',
        'currency' => 'CNY',
        'callbackUrl' => 'https://your-server.com/api/webhook/net8',
        'callbackSecret' => CALLBACK_SECRET
    ], $token);

    $data = $response['data'];

    if ($data['success']) {
        echo "✅ 游戏开始成功!\n";
        echo "   Session ID: {$data['sessionId']}\n";
        echo "   游戏 URL: {$data['gameUrl']}\n";
        echo "\n";
        echo "💡 在 iframe 中显示此 URL 即可开始游戏\n";
    } else {
        echo "❌ 游戏开始失败: " . ($data['message'] ?? 'Unknown error') . "\n";
    }
    echo "\n";

    return $data;
}

/**
 * 4. Webhook 签名验证函数
 */
function verifyWebhookSignature($payload, $signature, $secret) {
    $computedSignature = hash_hmac('sha256', json_encode($payload), $secret);
    return hash_equals($signature, $computedSignature);
}

/**
 * 5. Webhook 处理示例
 */
function showWebhookExample() {
    echo "📡 Webhook 处理示例代码:\n";
    echo "\n";
    echo "```php\n";
    echo "<?php\n";
    echo "// webhook_handler.php\n";
    echo "\n";
    echo "// 1. 获取请求数据\n";
    echo "\$payload = json_decode(file_get_contents('php://input'), true);\n";
    echo "\$signature = \$_SERVER['HTTP_X_NET8_SIGNATURE'] ?? '';\n";
    echo "\$secret = 'your_secret_key_123';\n";
    echo "\n";
    echo "// 2. 验证签名\n";
    echo "\$computedSignature = hash_hmac('sha256', json_encode(\$payload), \$secret);\n";
    echo "\n";
    echo "if (!hash_equals(\$signature, \$computedSignature)) {\n";
    echo "    http_response_code(401);\n";
    echo "    echo json_encode(['error' => 'Invalid signature']);\n";
    echo "    exit;\n";
    echo "}\n";
    echo "\n";
    echo "// 3. 处理事件\n";
    echo "\$event = \$payload['event'] ?? '';\n";
    echo "\$data = \$payload['data'] ?? [];\n";
    echo "\n";
    echo "switch (\$event) {\n";
    echo "    case 'game.bet':\n";
    echo "        error_log('🎰 投注: ' . \$data['betAmount'] . ' CNY');\n";
    echo "        // 保存到数据库\n";
    echo "        break;\n";
    echo "\n";
    echo "    case 'game.win':\n";
    echo "        error_log('🎉 获胜: ' . \$data['winAmount'] . ' CNY');\n";
    echo "        // 保存到数据库\n";
    echo "        break;\n";
    echo "\n";
    echo "    case 'game.end':\n";
    echo "        error_log('🏁 游戏结束');\n";
    echo "        error_log('   累计投注: ' . \$data['totalBets'] . ' CNY');\n";
    echo "        error_log('   累计获胜: ' . \$data['totalWins'] . ' CNY');\n";
    echo "        // 更新用户余额\n";
    echo "        break;\n";
    echo "}\n";
    echo "\n";
    echo "// 4. 返回成功响应\n";
    echo "echo json_encode(['success' => true]);\n";
    echo "?>\n";
    echo "```\n";
    echo "\n";
}

/**
 * 主函数 - 完整测试流程
 */
function main() {
    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "NET8 API 集成测试（PHP）\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "\n";

    try {
        // 步骤 1: 获取令牌
        $token = getAuthToken();

        // 步骤 2: 获取机器列表
        $machines = getMachines($token);

        // 步骤 3: 开始游戏（如果有机器）
        if (count($machines) > 0) {
            startGame($token, $machines[0]['machineNo']);
        }

        // 步骤 4: 显示 Webhook 处理示例
        showWebhookExample();

        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "✅ 测试完成!\n";
        echo "\n";
        echo "下一步:\n";
        echo "1. 实现 Webhook 接收端点（webhook_handler.php）\n";
        echo "2. 设置您自己的 callbackUrl 和 callbackSecret\n";
        echo "3. 在 iframe 中显示游戏 URL\n";
        echo "4. 开始接收实时游戏数据!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "\n";

    } catch (Exception $e) {
        echo "❌ 错误: " . $e->getMessage() . "\n";
    }
}

// 运行测试
main();
?>
