#!/bin/bash

# Railway APIトークンテストスクリプト
# 使用方法: ./test-railway-token.sh <RAILWAY_API_TOKEN>

RAILWAY_TOKEN="${1:-$RAILWAY_TOKEN}"

if [ -z "$RAILWAY_TOKEN" ]; then
    echo "❌ Railway APIトークンを指定してください"
    echo ""
    echo "使用方法:"
    echo "  ./test-railway-token.sh <RAILWAY_API_TOKEN>"
    echo ""
    echo "APIトークン取得:"
    echo "  https://railway.com/account/tokens"
    echo ""
    exit 1
fi

echo "🔐 Railway APIトークン認証テスト"
echo "=================================="
echo ""

# GraphQL APIエンドポイント
API_URL="https://backboard.railway.com/graphql/v2"

# 認証確認
echo "📡 APIリクエスト送信中..."
RESPONSE=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"query":"query { me { id name email } }"}' \
  "$API_URL")

# エラーチェック
if echo "$RESPONSE" | grep -q '"errors"'; then
    echo "❌ 認証失敗"
    echo ""
    echo "レスポンス:"
    echo "$RESPONSE" | jq '.' 2>/dev/null || echo "$RESPONSE"
    echo ""
    echo "トークンを確認してください:"
    echo "  https://railway.com/account/tokens"
    echo ""
    echo "正しいトークンの形式:"
    echo "  rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*..."
    echo "  （200文字以上の長い文字列）"
    exit 1
fi

# 成功
echo "✅ 認証成功！"
echo ""

# ユーザー情報表示
if command -v jq &> /dev/null; then
    echo "👤 ユーザー情報:"
    echo "$RESPONSE" | jq '.data.me'
else
    echo "ユーザー情報:"
    echo "$RESPONSE"
fi

echo ""
echo "🎉 このトークンは有効です！"
echo ""
echo "次のステップ:"
echo "  ./railway-deploy.sh $RAILWAY_TOKEN"
