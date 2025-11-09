#!/bin/bash
# NET8 Gaming Platform - Startup Script with Cloud SQL Auth Proxy
# このスクリプトはCloud SQL Proxyを起動してからApacheを起動します

set -e

echo "🚀 NET8 Gaming Platform Starting..."

# Cloud SQL接続情報を環境変数から取得
INSTANCE_CONNECTION_NAME="${CLOUD_SQL_INSTANCE_CONNECTION_NAME:-avamodb:asia-northeast1:avamodb}"

echo "📡 Starting Cloud SQL Auth Proxy..."
echo "   Instance: ${INSTANCE_CONNECTION_NAME}"

# Cloud SQL Auth Proxyをバックグラウンドで起動
/usr/local/bin/cloud_sql_proxy \
  -instances="${INSTANCE_CONNECTION_NAME}=tcp:3306" \
  -credential_file=/secrets/gcp-key.json \
  -ip_address_types=PRIVATE,PUBLIC \
  &

# Cloud SQL Proxyが起動するまで待機
echo "⏳ Waiting for Cloud SQL Proxy to be ready..."
for i in {1..30}; do
  if nc -z localhost 3306 2>/dev/null; then
    echo "✅ Cloud SQL Proxy is ready!"
    break
  fi
  if [ $i -eq 30 ]; then
    echo "❌ Cloud SQL Proxy failed to start within 30 seconds"
    exit 1
  fi
  sleep 1
done

# 環境変数を上書き（localhostのProxyに接続）
export DB_HOST=127.0.0.1
export DB_PORT=3306

echo "🌐 Starting Apache HTTP Server..."

# Apacheをフォアグラウンドで起動
exec apache2-foreground
