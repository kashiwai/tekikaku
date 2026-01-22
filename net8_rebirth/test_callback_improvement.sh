#!/bin/bash
# NET8 Callback Improvement Test Script
# 韓国チーム対応: 正確なポイントデータ構造テスト

API_BASE="https://mgg-webservice-production.up.railway.app/api/v1"
API_KEY="pk_korea_harumi_20260117_ea2af7c3b9c7a1c9e8f5d4b3a2c1b0a9"

echo "🧪 NET8 Callback Improvement Test"
echo "=================================="
echo ""

# テストシナリオ
echo "📋 Test Scenario:"
echo "  1. Game Start with initialPoints: 10000"
echo "  2. Game End with totalBets: 1500, totalWins: 2000"
echo "  3. Expected Callback Data:"
echo "     - points.initial = 10000"
echo "     - points.consumed = 1500"
echo "     - points.won = 2000"
echo "     - points.net = (final - initial)"
echo ""

# Step 1: Game Start
echo "🚀 Step 1: Starting game with initialPoints=10000..."
START_RESPONSE=$(curl -s -X POST "${API_BASE}/game_start.php" \
  -H "Authorization: Bearer ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_korea_callback_001",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "initialPoints": 10000,
    "callbackUrl": "https://api.harumi.com/net8/callback",
    "callbackSecret": "whsec_test_callback_improvement"
  }')

SESSION_ID=$(echo "$START_RESPONSE" | grep -o '"sessionId":"[^"]*"' | cut -d'"' -f4)
MEMBER_NO=$(echo "$START_RESPONSE" | grep -o '"memberNo":[0-9]*' | cut -d':' -f2)

if [ -z "$SESSION_ID" ]; then
  echo "❌ Game start failed"
  echo "$START_RESPONSE"
  exit 1
fi

echo "✅ Game started successfully"
echo "   SessionID: $SESSION_ID"
echo "   MemberNo: $MEMBER_NO"
echo ""

# Step 2: Wait a bit
echo "⏳ Waiting 2 seconds..."
sleep 2

# Step 3: Game End with totalBets and totalWins
echo "🏁 Step 2: Ending game with totalBets=1500, totalWins=2000..."
END_RESPONSE=$(curl -s -X POST "${API_BASE}/game_end.php" \
  -H "Authorization: Bearer ${API_KEY}" \
  -H "Content-Type: application/json" \
  -d "{
    \"sessionId\": \"${SESSION_ID}\",
    \"memberNo\": ${MEMBER_NO},
    \"result\": \"win\",
    \"pointsWon\": 500,
    \"totalBets\": 1500,
    \"totalWins\": 2000
  }")

echo "✅ Game ended"
echo ""

# Step 4: Check response
echo "📊 Response Analysis:"
echo "$END_RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$END_RESPONSE"
echo ""

# Step 5: Verify points structure
echo "🔍 Verifying points structure..."
INITIAL=$(echo "$END_RESPONSE" | grep -o '"initial":[0-9]*' | cut -d':' -f2)
CONSUMED=$(echo "$END_RESPONSE" | grep -o '"consumed":[0-9]*' | cut -d':' -f2)
WON=$(echo "$END_RESPONSE" | grep -o '"won":[0-9]*' | cut -d':' -f2)
FINAL=$(echo "$END_RESPONSE" | grep -o '"final":[0-9]*' | cut -d':' -f2)
NET=$(echo "$END_RESPONSE" | grep -o '"net":-?[0-9]*' | cut -d':' -f2)

echo ""
echo "📈 Extracted Points Data:"
echo "   initial: ${INITIAL:-N/A}"
echo "   consumed: ${CONSUMED:-N/A}"
echo "   won: ${WON:-N/A}"
echo "   final: ${FINAL:-N/A}"
echo "   net: ${NET:-N/A}"
echo ""

# Step 6: Validation
echo "✅ Validation:"
if [ "$INITIAL" = "10000" ]; then
  echo "   ✅ points.initial = 10000 (PASS)"
else
  echo "   ❌ points.initial = ${INITIAL:-N/A} (FAIL - Expected: 10000)"
fi

if [ "$CONSUMED" = "1500" ]; then
  echo "   ✅ points.consumed = 1500 (PASS)"
else
  echo "   ❌ points.consumed = ${CONSUMED:-N/A} (FAIL - Expected: 1500)"
fi

if [ "$WON" = "2000" ]; then
  echo "   ✅ points.won = 2000 (PASS)"
else
  echo "   ❌ points.won = ${WON:-N/A} (FAIL - Expected: 2000)"
fi

echo ""
echo "🎉 Test completed!"
echo ""
echo "📝 Note: Check Railway logs for callback delivery confirmation:"
echo "   https://railway.app/project/.../logs"
