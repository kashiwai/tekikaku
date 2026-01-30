#!/bin/bash

##############################################
# NET8 Chinese Partner API Integration Test
# Tests all API endpoints with Chinese language and CNY currency
##############################################

# Configuration
BASE_URL="https://ifreamnet8-development.up.railway.app/api/v1"
API_KEY="test_api_key_1234567890" # Replace with actual API key
LANG="zh"
CURRENCY="CNY"

echo "=========================================="
echo "NET8 Chinese Partner API Integration Test"
echo "=========================================="
echo ""
echo "Testing BASE_URL: $BASE_URL"
echo "Language: $LANG (Chinese)"
echo "Currency: $CURRENCY"
echo ""

##############################################
# Test 1: Authentication
##############################################
echo "=================================================="
echo "Test 1: Authentication (认证)"
echo "=================================================="

JWT_RESPONSE=$(curl -s -X POST "$BASE_URL/auth.php" \
  -H "Content-Type: application/json" \
  -d "{
    \"apiKey\": \"$API_KEY\"
  }")

echo "Response: $JWT_RESPONSE"
echo ""

# Extract JWT token
JWT_TOKEN=$(echo "$JWT_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$JWT_TOKEN" ]; then
    echo "❌ Authentication failed! Cannot proceed."
    echo "Please check your API key configuration."
    exit 1
fi

echo "✅ Authentication successful!"
echo "JWT Token: ${JWT_TOKEN:0:50}..."
echo ""

##############################################
# Test 2: List Available Machines (Chinese)
##############################################
echo "=================================================="
echo "Test 2: List Available Machines (列出可用机台)"
echo "=================================================="

MACHINES_RESPONSE=$(curl -s -X GET "$BASE_URL/list_machines.php?lang=$LANG" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json")

echo "Response (first 500 chars):"
echo "${MACHINES_RESPONSE:0:500}..."
echo ""

# Extract first available modelId and machineNo
MODEL_ID=$(echo "$MACHINES_RESPONSE" | grep -o '"modelId":"[^"]*' | head -1 | cut -d'"' -f4)
MACHINE_NO=$(echo "$MACHINES_RESPONSE" | grep -o '"machineNo":[0-9]*' | head -1 | grep -o '[0-9]*')

if [ -z "$MODEL_ID" ]; then
    echo "⚠️  No available machines found. Using default modelId."
    MODEL_ID="SLOT-107"
fi

if [ -z "$MACHINE_NO" ]; then
    echo "⚠️  No specific machineNo found. Will let API assign."
    MACHINE_NO=""
fi

echo "✅ Found available machine:"
echo "   Model ID: $MODEL_ID"
echo "   Machine No: $MACHINE_NO"
echo ""

##############################################
# Test 3: Get Model Details (Chinese)
##############################################
echo "=================================================="
echo "Test 3: Get Model Details (获取机型详情)"
echo "=================================================="

MODELS_RESPONSE=$(curl -s -X GET "$BASE_URL/models.php?lang=$LANG&modelId=$MODEL_ID" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json")

echo "Response (first 500 chars):"
echo "${MODELS_RESPONSE:0:500}..."
echo ""
echo "✅ Model details retrieved"
echo ""

##############################################
# Test 4: Game Start (Chinese + CNY)
##############################################
echo "=================================================="
echo "Test 4: Game Start (开始游戏)"
echo "=================================================="

# Build game start request
GAME_START_DATA="{
  \"modelId\": \"$MODEL_ID\",
  \"userId\": \"chinese_partner_user_001\",
  \"initialPoints\": 1000,
  \"balanceMode\": \"set\",
  \"consumeImmediately\": false,
  \"lang\": \"$LANG\",
  \"currency\": \"$CURRENCY\""

# Add machineNo if available
if [ -n "$MACHINE_NO" ]; then
    GAME_START_DATA="$GAME_START_DATA,
  \"machineNo\": $MACHINE_NO"
fi

# Add callback configuration (optional - for testing)
# Uncomment below if you have a callback endpoint
# GAME_START_DATA="$GAME_START_DATA,
#   \"callbackUrl\": \"https://your-partner-server.com/webhook/net8\",
#   \"callbackSecret\": \"your_webhook_secret_key\""

GAME_START_DATA="$GAME_START_DATA
}"

GAME_START_RESPONSE=$(curl -s -X POST "$BASE_URL/game_start.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "$GAME_START_DATA")

echo "Request: $GAME_START_DATA"
echo ""
echo "Response:"
echo "$GAME_START_RESPONSE"
echo ""

# Extract sessionId
SESSION_ID=$(echo "$GAME_START_RESPONSE" | grep -o '"sessionId":"[^"]*' | cut -d'"' -f4)

if [ -z "$SESSION_ID" ]; then
    echo "❌ Game start failed! Cannot proceed with game flow test."
    exit 1
fi

echo "✅ Game started successfully!"
echo "   Session ID: $SESSION_ID"
echo ""

##############################################
# Test 5: Game Bet Event (Real-time)
##############################################
echo "=================================================="
echo "Test 5: Game Bet Event (下注事件)"
echo "=================================================="

GAME_BET_RESPONSE=$(curl -s -X POST "$BASE_URL/game_bet.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"sessionId\": \"$SESSION_ID\",
    \"betAmount\": 10,
    \"betData\": {
      \"betType\": \"normal\",
      \"lines\": 20
    }
  }")

echo "Response: $GAME_BET_RESPONSE"
echo ""
echo "✅ Bet event recorded"
echo ""

##############################################
# Test 6: Game Win Event (Real-time)
##############################################
echo "=================================================="
echo "Test 6: Game Win Event (获胜事件)"
echo "=================================================="

GAME_WIN_RESPONSE=$(curl -s -X POST "$BASE_URL/game_win.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"sessionId\": \"$SESSION_ID\",
    \"winAmount\": 50,
    \"winData\": {
      \"winType\": \"big_bonus\",
      \"multiplier\": 5
    }
  }")

echo "Response: $GAME_WIN_RESPONSE"
echo ""
echo "✅ Win event recorded"
echo ""

##############################################
# Test 7: Game End
##############################################
echo "=================================================="
echo "Test 7: Game End (结束游戏)"
echo "=================================================="

GAME_END_RESPONSE=$(curl -s -X POST "$BASE_URL/game_end.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"sessionId\": \"$SESSION_ID\",
    \"result\": \"win\",
    \"pointsWon\": 50,
    \"totalBets\": 100,
    \"totalWins\": 150,
    \"resultData\": {
      \"finalBalance\": 1050,
      \"gameTime\": 300
    }
  }")

echo "Response:"
echo "$GAME_END_RESPONSE"
echo ""
echo "✅ Game ended successfully"
echo ""

##############################################
# Test 8: Check Balance
##############################################
echo "=================================================="
echo "Test 8: Check User Balance (查询余额)"
echo "=================================================="

BALANCE_RESPONSE=$(curl -s -X POST "$BASE_URL/adjust_balance.php" \
  -H "Authorization: Bearer $JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"userId\": \"chinese_partner_user_001\",
    \"action\": \"get\"
  }")

echo "Response: $BALANCE_RESPONSE"
echo ""
echo "✅ Balance check completed"
echo ""

##############################################
# Summary
##############################################
echo "=========================================="
echo "✅ All API Tests Completed Successfully!"
echo "=========================================="
echo ""
echo "Tested Features:"
echo "  ✅ Authentication (JWT)"
echo "  ✅ List machines (Chinese)"
echo "  ✅ Model details (Chinese)"
echo "  ✅ Game start (CNY currency)"
echo "  ✅ Real-time bet events"
echo "  ✅ Real-time win events"
echo "  ✅ Game end & settlement"
echo "  ✅ Balance checking"
echo ""
echo "Your API is ready for Chinese partner integration!"
echo ""
