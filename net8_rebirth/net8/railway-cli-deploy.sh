#!/bin/bash

# Railway CLI自動デプロイスクリプト
# 前提条件: railway login が完了していること

set -e

echo "🚀 Railway CLI デプロイ開始"
echo "=========================="
echo ""

# 認証確認
echo "🔐 認証確認..."
if ! railway whoami &>/dev/null; then
    echo "❌ Railway CLIにログインしていません"
    echo ""
    echo "以下のコマンドを実行してログインしてください:"
    echo ""
    echo "  railway login"
    echo ""
    echo "ブラウザが開き、GitHubアカウントで認証されます。"
    echo "認証完了後、このスクリプトを再実行してください。"
    exit 1
fi

USER_INFO=$(railway whoami)
echo "✅ 認証成功: $USER_INFO"
echo ""

# プロジェクト作成
echo "📦 新しいプロジェクトを作成..."
railway init <<EOF
NET8 WebRTC System
EOF

echo "✅ プロジェクト作成完了"
echo ""

# MySQLデータベース追加
echo "💾 MySQL データベースを追加..."
railway add --database mysql

echo "✅ MySQL データベース追加完了"
echo ""

# Signaling サーバーデプロイ
echo "📡 PeerJS Signaling サーバーをデプロイ..."

# Signalingサービス用の一時ディレクトリ作成
mkdir -p /tmp/railway-signaling
cd /tmp/railway-signaling

# 必要なファイルをコピー
cp -r /Users/kotarokashiwai/net8_rebirth/net8/01.サーバ構築手順/net8peerjs-server/* .
cp /Users/kotarokashiwai/net8_rebirth/net8/Dockerfile.signaling ./Dockerfile

# railway.toml作成
cat > railway.toml <<'TOML_EOF'
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"

[deploy]
numReplicas = 1
restartPolicyType = "ON_FAILURE"
TOML_EOF

# Signalingサービス作成とデプロイ
railway service create net8-signaling
railway up --service net8-signaling

echo "✅ Signaling サーバーデプロイ完了"
echo ""

# 元のディレクトリに戻る
cd /Users/kotarokashiwai/net8_rebirth/net8

# Webサーバーデプロイ
echo "🌐 Apache/PHP Webサーバーをデプロイ..."

# Webサービス用の一時ディレクトリ作成
mkdir -p /tmp/railway-web
cd /tmp/railway-web

# 必要なファイルをコピー
cp -r /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/* .
cp -r /Users/kotarokashiwai/net8_rebirth/net8/docker/web/* .
cp /Users/kotarokashiwai/net8_rebirth/net8/Dockerfile.web ./Dockerfile

# railway.toml作成
cat > railway.toml <<'TOML_EOF'
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"

[deploy]
numReplicas = 1
restartPolicyType = "ON_FAILURE"
TOML_EOF

# Webサービス作成とデプロイ
railway service create net8-web
railway up --service net8-web

echo "✅ Web サーバーデプロイ完了"
echo ""

# 環境変数設定
echo "⚙️  環境変数を設定..."

# Signaling サーバーの環境変数
railway variables --service net8-signaling set PORT=9000
railway variables --service net8-signaling set PEERJS_KEY=peerjs

# Web サーバーの環境変数
railway variables --service net8-web set DATABASE_HOST='${{MySQL.MYSQLHOST}}'
railway variables --service net8-web set DATABASE_PORT='${{MySQL.MYSQLPORT}}'
railway variables --service net8-web set DATABASE_USER='${{MySQL.MYSQLUSER}}'
railway variables --service net8-web set DATABASE_PASSWORD='${{MySQL.MYSQLPASSWORD}}'
railway variables --service net8-web set DATABASE_NAME='${{MySQL.MYSQLDATABASE}}'
railway variables --service net8-web set SIGNALING_HOST='${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}'
railway variables --service net8-web set SIGNALING_PORT=443

echo "✅ 環境変数設定完了"
echo ""

# ドメイン生成
echo "🌐 ドメインを生成..."

railway domain --service net8-signaling
railway domain --service net8-web

echo "✅ ドメイン生成完了"
echo ""

# プロジェクト情報表示
echo "🎉 デプロイ完了！"
echo "===================="
echo ""
echo "📊 プロジェクト情報:"
railway status

echo ""
echo "🌐 次のステップ:"
echo "1. Railway Dashboard でドメインを確認"
echo "   railway open"
echo ""
echo "2. Windows側のURLを更新"
echo "   https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73"
echo ""
echo "3. Mac側でテスト"
echo "   https://[net8-web のドメイン]/play_v2/test_simple.html"
