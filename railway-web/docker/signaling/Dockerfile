FROM node:14-alpine

# sqlite3のビルドに必要なパッケージをインストール
RUN apk add --no-cache python3 make g++ sqlite-dev

WORKDIR /app

# ログディレクトリ作成
RUN mkdir -p /var/log/peerjs && chmod 777 /var/log/peerjs

# ポート公開
EXPOSE 59000

# 起動スクリプト
# 注: アプリケーションファイルはdocker-compose.ymlでマウントされます
# 既存のnode_modulesを削除してから新規インストール（プラットフォーム互換性のため）
CMD sh -c "rm -rf node_modules && npm install --production && node bin/peerjs -p 59000 -k peerjs"
