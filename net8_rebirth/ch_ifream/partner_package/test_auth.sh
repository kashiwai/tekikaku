#!/bin/bash
# NET8 API 认证测试脚本
# 使用方法: ./test_auth.sh

API_KEY="pk_live_42c61da908dd515d9f0a6a99406c4dcb"
BASE_URL="https://ifreamnet8-development.up.railway.app/api/v1"

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "NET8 API 认证测试"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

echo "步骤 1: 获取 JWT 令牌..."
echo ""

RESPONSE=$(curl -s -X POST "${BASE_URL}/auth.php" \
  -H "Content-Type: application/json" \
  -d "{\"apiKey\": \"${API_KEY}\"}")

echo "响应:"
echo "$RESPONSE" | jq '.'

# トークンを抽出
TOKEN=$(echo "$RESPONSE" | jq -r '.token')

if [ "$TOKEN" != "null" ] && [ -n "$TOKEN" ]; then
    echo ""
    echo "✅ 认证成功!"
    echo ""
    echo "JWT 令牌: $TOKEN"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "步骤 2: 使用令牌获取机器列表..."
    echo ""

    MACHINES=$(curl -s -X GET "${BASE_URL}/list_machines.php" \
      -H "Authorization: Bearer ${TOKEN}" \
      -H "Content-Type: application/json")

    echo "响应:"
    echo "$MACHINES" | jq '.'
    echo ""
    echo "✅ 测试完成! API 集成可以开始。"
else
    echo ""
    echo "❌ 认证失败! 请检查 API 密钥。"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
