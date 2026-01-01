# NET8 Live Streaming Server (Ant Media Server)

**目的**: 乗っかりベット機能向けの低遅延ライブ配信サーバー

---

## 📋 概要

- **配信サーバー**: Ant Media Server Community Edition
- **遅延**: 0.5秒以下 (WebRTC)
- **スケール**: 同時配信 20-30、視聴者 500-800
- **コスト**: 無料 (Community Edition)

---

## 🚀 ローカル起動

### 前提条件
- Docker Desktop インストール済み
- ポート空き状況確認: 5080, 5443, 1935, 5000-5100

### 起動手順

```bash
cd streaming-server

# Docker Composeで起動
docker-compose up -d

# ログ確認
docker-compose logs -f

# 起動確認 (2-3分待機)
curl http://localhost:5080/LiveApp
```

### 管理画面アクセス

- **URL**: http://localhost:5080
- **初回ログイン**: 自動セットアップ画面が表示されます

---

## ☁️ Railway デプロイ

### 1. Railway プロジェクト作成

```bash
# Railway CLI インストール
npm install -g @railway/cli

# ログイン
railway login

# 新規プロジェクト作成
railway init
```

### 2. デプロイ

```bash
# streaming-serverディレクトリから
railway up
```

### 3. 公開URL取得

Railway ダッシュボードで自動生成されたURLを確認:
- 例: `https://mgg-streaming-server.up.railway.app`

### 4. 環境変数確認

Railway ダッシュボードで以下が設定されているか確認:
- `SERVER_MODE=community`
- `TZ=Asia/Tokyo`

---

## 🔌 API使用方法

### 1. ストリーム作成

```bash
curl -X POST https://your-railway-url.up.railway.app/LiveApp/rest/v2/broadcasts/create \
  -H "Content-Type: application/json" \
  -d '{
    "name": "stream_test_001",
    "type": "liveStream",
    "publicStream": true
  }'
```

**レスポンス例:**
```json
{
  "success": true,
  "streamId": "stream_test_001_12345",
  "name": "stream_test_001"
}
```

### 2. ストリーム一覧取得

```bash
curl https://your-railway-url.up.railway.app/LiveApp/rest/v2/broadcasts/list/0/10
```

### 3. WebRTC 配信 (JavaScript)

```javascript
import { WebRTCAdaptor } from '@antmedia/webrtc_adaptor';

const webRTCAdaptor = new WebRTCAdaptor({
  websocket_url: 'wss://your-railway-url.up.railway.app/LiveApp/websocket',
  mediaConstraints: {
    video: true,
    audio: true
  },
  callback: (info, obj) => {
    if (info === 'initialized') {
      webRTCAdaptor.publish('stream_test_001_12345');
    }
  }
});
```

### 4. WebRTC 視聴 (JavaScript)

```javascript
const webRTCAdaptor = new WebRTCAdaptor({
  websocket_url: 'wss://your-railway-url.up.railway.app/LiveApp/websocket',
  remoteVideoElement: document.getElementById('remoteVideo'),
  callback: (info, obj) => {
    if (info === 'initialized') {
      webRTCAdaptor.play('stream_test_001_12345');
    }
  }
});
```

---

## 🔍 トラブルシューティング

### ローカル起動時

**問題**: コンテナが起動しない
```bash
# ログ確認
docker-compose logs

# ポート競合確認
lsof -i :5080
```

**問題**: ヘルスチェック失敗
```bash
# 起動まで2-3分かかります。以下で状態確認:
docker-compose ps
```

### Railway デプロイ時

**問題**: デプロイ失敗
- Railway ダッシュボードでビルドログ確認
- `railway.toml` のパス設定確認

**問題**: WebRTC 接続できない
- Railway の公開URLが HTTPS であることを確認
- ブラウザコンソールで ICE candidate エラー確認

---

## 📊 パフォーマンス目標

### MVP (Railway Pro プラン想定)

| 項目 | 目標値 |
|------|--------|
| 遅延 | 0.5秒以下 |
| 同時配信数 | 20-30 |
| 総視聴者数 | 500-800 |
| メモリ使用量 | 2GB以下 |
| CPU使用率 | 70%以下 |

---

## 🛠️ 次のステップ

1. ✅ Ant Media Server デプロイ完了
2. ⏳ NET8 API統合 (stream/start, stream/list)
3. ⏳ データベーステーブル作成 (live_streams, piggyback_bets)
4. ⏳ フロントエンド統合 (配信一覧、視聴UI)

---

## 📚 参考リンク

- [Ant Media Server Documentation](https://github.com/ant-media/Ant-Media-Server/wiki)
- [WebRTC Adaptor Reference](https://github.com/ant-media/StreamApp/tree/master/src/main/js)
- [REST API Reference](https://github.com/ant-media/Ant-Media-Server/wiki/Rest-API-Guide)
