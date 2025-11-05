# NET8 WebSocket Hub - Railway中継サーバー

## 📋 概要

本社Mac ↔ このサーバー ↔ 各Windows PCの通信を中継するWebSocketサーバー

## 🚀 Railwayへのデプロイ

### 方法1: Railway CLI（推奨）

```bash
cd railway-websocket-hub

# Railwayにログイン
railway login

# 新しいプロジェクト作成
railway init

# デプロイ
railway up

# URLを確認
railway domain
# 例: net8-websocket-hub.up.railway.app
```

### 方法2: GitHub連携

1. このディレクトリをGitHubにpush
2. Railwayダッシュボードで「New Project」
3. 「Deploy from GitHub repo」を選択
4. リポジトリを選択してデプロイ

## 🔧 環境変数設定（Railway Dashboard）

```
PORT=3001
AUTH_TOKEN=your-secure-token-here
NODE_ENV=production
```

## ✅ デプロイ確認

```bash
# ヘルスチェック
curl https://net8-websocket-hub.up.railway.app/

# 接続中のエージェント確認
curl https://net8-websocket-hub.up.railway.app/api/agents
```

## 📊 エンドポイント

- `GET /` - ヘルスチェック・統計情報
- `GET /api/agents` - 接続中のエージェント一覧
- WebSocket: `wss://net8-websocket-hub.up.railway.app`

## 🔌 WebSocketイベント

### クライアント → サーバー
- `register` - クライアント登録
- `send_to_machine` - Windows PCに指示送信
- `result` - 実行結果送信
- `heartbeat` - 接続維持

### サーバー → クライアント
- `registered` - 登録完了
- `instruction` - 実行指示
- `execution_result` - 実行結果
- `agent_connected` - エージェント接続通知
- `agent_disconnected` - エージェント切断通知
