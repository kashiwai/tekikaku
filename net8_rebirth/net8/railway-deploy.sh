#!/bin/bash

# Railway自動デプロイスクリプト
# 使用方法: ./railway-deploy.sh <RAILWAY_API_TOKEN>

set -e

RAILWAY_TOKEN="${1:-$RAILWAY_TOKEN}"

if [ -z "$RAILWAY_TOKEN" ]; then
    echo "❌ Railway APIトークンが必要です"
    echo ""
    echo "使用方法:"
    echo "  ./railway-deploy.sh <RAILWAY_API_TOKEN>"
    echo ""
    echo "または環境変数で指定:"
    echo "  export RAILWAY_TOKEN='your-token'"
    echo "  ./railway-deploy.sh"
    echo ""
    echo "APIトークンの取得方法:"
    echo "  1. https://railway.app/ にログイン"
    echo "  2. Account Settings → Tokens"
    echo "  3. Create New Token"
    echo ""
    echo "詳細: RAILWAY_API_TOKEN_GUIDE.md を参照"
    exit 1
fi

echo "🚀 Railway デプロイ開始"
echo "======================="
echo ""

# GraphQL APIエンドポイント
API_URL="https://backboard.railway.app/graphql/v2"

# 認証確認
echo "🔐 APIトークン認証確認..."
USER_INFO=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"query":"query { me { id name email } }"}' \
  "$API_URL")

if echo "$USER_INFO" | grep -q "errors"; then
    echo "❌ 認証失敗"
    echo "$USER_INFO"
    echo ""
    echo "正しいAPIトークンを取得してください:"
    echo "  https://railway.app/account/tokens"
    exit 1
fi

USER_NAME=$(echo "$USER_INFO" | grep -o '"name":"[^"]*"' | cut -d'"' -f4)
USER_EMAIL=$(echo "$USER_INFO" | grep -o '"email":"[^"]*"' | cut -d'"' -f4)

echo "✅ 認証成功"
echo "   User: $USER_NAME ($USER_EMAIL)"
echo ""

# プロジェクト作成
echo "📦 プロジェクト作成..."
PROJECT_CREATE=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "mutation { projectCreate(input: { name: \"NET8 WebRTC System\" }) { id name } }"
  }' \
  "$API_URL")

PROJECT_ID=$(echo "$PROJECT_CREATE" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

if [ -z "$PROJECT_ID" ]; then
    echo "❌ プロジェクト作成失敗"
    echo "$PROJECT_CREATE"
    exit 1
fi

echo "✅ プロジェクト作成完了: $PROJECT_ID"
echo ""

# MySQL データベース作成
echo "💾 MySQL データベース作成..."
MYSQL_CREATE=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { databaseCreate(input: { projectId: \\\"$PROJECT_ID\\\", type: MYSQL }) { id } }\"
  }" \
  "$API_URL")

echo "✅ MySQL データベース作成完了"
echo ""

# GitHubリポジトリ接続
echo "🔗 GitHubリポジトリ接続..."
REPO_URL="https://github.com/mgg00123mg-prog/mgg001"

# Signaling サーバーデプロイ
echo "📡 PeerJS Signaling サーバーデプロイ..."
SIGNALING_CREATE=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { serviceCreate(input: { projectId: \\\"$PROJECT_ID\\\", name: \\\"net8-signaling\\\" }) { id } }\"
  }" \
  "$API_URL")

SIGNALING_ID=$(echo "$SIGNALING_CREATE" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

echo "✅ Signaling サービス作成完了: $SIGNALING_ID"
echo ""

# Web サーバーデプロイ
echo "🌐 Apache/PHP Webサーバーデプロイ..."
WEB_CREATE=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { serviceCreate(input: { projectId: \\\"$PROJECT_ID\\\", name: \\\"net8-web\\\" }) { id } }\"
  }" \
  "$API_URL")

WEB_ID=$(echo "$WEB_CREATE" | grep -o '"id":"[^"]*"' | head -1 | cut -d'"' -f4)

echo "✅ Web サービス作成完了: $WEB_ID"
echo ""

# 完了メッセージ
echo "🎉 デプロイ準備完了！"
echo "======================="
echo ""
echo "📋 次のステップ（Railway Dashboardで実施）:"
echo ""
echo "1. Railway Dashboard にアクセス:"
echo "   https://railway.app/project/$PROJECT_ID"
echo ""
echo "2. 各サービスの設定:"
echo ""
echo "   📡 net8-signaling:"
echo "      - Settings → Deploy → Dockerfile Path: Dockerfile.signaling"
echo "      - Settings → Variables:"
echo "        PORT=9000"
echo "        PEERJS_KEY=peerjs"
echo "      - Settings → Networking → Generate Domain"
echo ""
echo "   🌐 net8-web:"
echo "      - Settings → Deploy → Dockerfile Path: Dockerfile.web"
echo "      - Settings → Variables:"
echo "        DATABASE_HOST=\${{MySQL.MYSQLHOST}}"
echo "        DATABASE_PORT=\${{MySQL.MYSQLPORT}}"
echo "        DATABASE_USER=\${{MySQL.MYSQLUSER}}"
echo "        DATABASE_PASSWORD=\${{MySQL.MYSQLPASSWORD}}"
echo "        DATABASE_NAME=\${{MySQL.MYSQLDATABASE}}"
echo "        SIGNALING_HOST=\${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}"
echo "        SIGNALING_PORT=443"
echo "      - Settings → Networking → Generate Domain"
echo ""
echo "3. デプロイ確認:"
echo "   - 各サービスのDeployタブでビルド状況を確認"
echo ""
echo "詳細: RAILWAY_QUICK_START.md を参照"
