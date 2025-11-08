#!/bin/bash

# ==========================================
# NET8 起動スクリプト
# Cloud SQL Auth Proxy + Apache
# ==========================================

echo "🚀 NET8起動中..."

# Cloud SQL Proxyをバックグラウンドで起動
echo "🔒 トンネルプログラム起動中..."
/usr/local/bin/cloud_sql_proxy \
  -instances=avamodb:us-central1:net8-mysql57=tcp:127.0.0.1:3306 \
  -credential_file=/secrets/gcp-key.json &

# Proxyが起動するまで待機
echo "⏳ トンネル接続確立中..."
sleep 5

# 接続確認
if nc -z 127.0.0.1 3306 2>/dev/null; then
  echo "✅ トンネル接続成功: 127.0.0.1:3306"
else
  echo "⚠️ トンネル接続確認できませんが、Apache起動を続行します"
fi

# Apache起動（フォアグラウンド）
echo "🌐 Apache起動..."
exec apache2-foreground
