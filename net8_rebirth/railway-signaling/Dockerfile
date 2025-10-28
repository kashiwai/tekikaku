FROM node:14-alpine

# Cache buster - force rebuild: 2025-10-28-v6
ARG CACHEBUST=20251028v6

# sqlite3のビルドに必要なパッケージをインストール
RUN apk add --no-cache python3 make g++ sqlite-dev

WORKDIR /app

# package.jsonとpackage-lock.jsonをコピー
COPY net8/01.サーバ構築手順/net8peerjs-server/package*.json ./

# 依存関係のインストール
RUN npm ci --production

# アプリケーションファイルを全てコピー（確実にapp.jsonも含める）
COPY net8/01.サーバ構築手順/net8peerjs-server/ ./

# 確実に最新のlibディレクトリ（server.js含む）を上書きコピー
COPY net8/01.サーバ構築手順/net8peerjs-server/lib/ ./lib/

# デバッグ: server.jsの221行目付近を確認
RUN echo "=== Checking server.js line 221 ===" && \
    sed -n '215,240p' ./lib/server.js

# 不要なファイルを削除（オプション）
RUN rm -f package-lock.json

# ログディレクトリ作成
RUN mkdir -p /var/log/peerjs && chmod 777 /var/log/peerjs

# Railwayの環境変数PORTを使用（Railwayのデフォルトは8080）
ENV PORT=${PORT:-8080}
ENV PEERJS_KEY=peerjs

EXPOSE ${PORT}

# PeerJSサーバー起動
CMD node bin/peerjs -p ${PORT} -k ${PEERJS_KEY}
