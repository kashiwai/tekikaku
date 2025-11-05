#!/bin/bash

# Railway API経由での自動デプロイスクリプト
# 正しいRailway APIトークンを使用

set -e

RAILWAY_TOKEN="rw_Fe26.2**4ca71b23b0913e9d304acaa3cd8b4b71af1fe238a48435cf2461808183edad6d*x2Yd5Gw4NKLIXlUJwsVkdg*Hmq8W5CIVCY7C6nhYP_eLXbRcRpHjlHWYmj0ORDA4f3Su1rx7B92AuaM5KDGW3z_fkfbuwbRgMdlMiCeOpi9dg*1764033871376*e735762fcfc3d2b55a7bc73245abb62cd9356cd2afd65f987645ee55da1b07fd*GLsKXAJd6dMtx8rMt-JU_MFciP0Xx3txESq28jUt6j4"
API_URL="https://backboard.railway.com/graphql/v2"

echo "🚀 Railway自動デプロイ開始"
echo "============================"
echo ""

# ユーザー情報取得
echo "🔐 認証確認..."
USER_DATA=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"query":"query { me { id name email } }"}' \
  "$API_URL")

USER_ID=$(echo "$USER_DATA" | jq -r '.data.me.id')
USER_NAME=$(echo "$USER_DATA" | jq -r '.data.me.name')

if [ -z "$USER_ID" ] || [ "$USER_ID" = "null" ]; then
    echo "❌ 認証失敗"
    echo "$USER_DATA"
    exit 1
fi

echo "✅ 認証成功: $USER_NAME"
echo ""

# デフォルトワークスペースIDを使用（個人アカウント）
WORKSPACE_ID="$USER_ID"

# プロジェクト作成
echo "📦 プロジェクト作成..."
PROJECT_DATA=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { projectCreate(input: { name: \\\"NET8 WebRTC System\\\", workspaceId: \\\"$WORKSPACE_ID\\\" }) { id name } }\"
  }" \
  "$API_URL")

PROJECT_ID=$(echo "$PROJECT_DATA" | jq -r '.data.projectCreate.id')

if [ -z "$PROJECT_ID" ] || [ "$PROJECT_ID" = "null" ]; then
    echo "❌ プロジェクト作成失敗"
    echo "$PROJECT_DATA"
    exit 1
fi

echo "✅ プロジェクト作成完了"
echo "   Project ID: $PROJECT_ID"
echo ""

# 環境取得（production環境のIDを取得）
echo "🌍 環境情報取得..."
ENV_DATA=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"query { project(id: \\\"$PROJECT_ID\\\") { environments { edges { node { id name } } } } }\"
  }" \
  "$API_URL")

ENV_ID=$(echo "$ENV_DATA" | jq -r '.data.project.environments.edges[0].node.id')

echo "   Environment ID: $ENV_ID"
echo ""

# MySQLプラグイン追加
echo "💾 MySQL データベース追加..."
MYSQL_DATA=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { pluginCreate(input: { projectId: \\\"$PROJECT_ID\\\", name: \\\"MySQL\\\", type: MYSQL }) { id } }\"
  }" \
  "$API_URL")

MYSQL_ID=$(echo "$MYSQL_DATA" | jq -r '.data.pluginCreate.id')

if [ -z "$MYSQL_ID" ] || [ "$MYSQL_ID" = "null" ]; then
    echo "❌ MySQL作成失敗"
    echo "$MYSQL_DATA"
else
    echo "✅ MySQL追加完了"
    echo "   MySQL ID: $MYSQL_ID"
fi
echo ""

# GitHubリポジトリ接続
echo "🔗 GitHubリポジトリ接続..."
REPO_DATA=$(curl -s -X POST \
  -H "Authorization: Bearer $RAILWAY_TOKEN" \
  -H "Content-Type: application/json" \
  -d "{
    \"query\": \"mutation { serviceCreateFromGitHub(input: { projectId: \\\"$PROJECT_ID\\\", repo: \\\"mgg00123mg-prog/mgg001\\\", branch: \\\"main\\\" }) { id name } }\"
  }" \
  "$API_URL")

SERVICE_ID=$(echo "$REPO_DATA" | jq -r '.data.serviceCreateFromGitHub.id')

if [ -z "$SERVICE_ID" ] || [ "$SERVICE_ID" = "null" ]; then
    echo "⚠️ GitHubリポジトリ自動接続失敗（権限が必要な可能性）"
    echo "$REPO_DATA"
    echo ""
    echo "手動で接続してください:"
    echo "  railway open"
    echo ""
else
    echo "✅ GitHubリポジトリ接続完了"
    echo "   Service ID: $SERVICE_ID"
fi
echo ""

# プロジェクトURL表示
echo "🎉 デプロイ準備完了！"
echo "======================"
echo ""
echo "📊 プロジェクト情報:"
echo "   Project ID: $PROJECT_ID"
echo "   Project URL: https://railway.app/project/$PROJECT_ID"
echo ""
echo "🌐 次のステップ:"
echo ""
echo "1. Railway Dashboardを開く:"
echo "   railway open"
echo ""
echo "2. GitHubリポジトリを接続:"
echo "   - 「+ New」→「GitHub Repo」"
echo "   - 「mgg00123mg-prog/mgg001」を選択"
echo ""
echo "3. サービスを設定:"
echo "   - Signaling: Dockerfile.signaling"
echo "   - Web: Dockerfile.web"
echo ""
echo "詳細: RAILWAY_MANUAL_DEPLOY_SIMPLE.md を参照"
