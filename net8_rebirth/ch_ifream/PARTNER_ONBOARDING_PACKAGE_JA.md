# NET8 パートナー向けオンボーディングパッケージ（日本語版）

**発行日:** 2026年1月31日
**対象:** 外部パートナー様（API統合事業者）
**バージョン:** 1.0.0

---

## 🎉 ようこそ NET8 Gaming へ

この度は NET8 オンラインパチスロ API をご利用いただき、誠にありがとうございます。本パッケージには、API 統合に必要なすべての情報が含まれています。

---

## 🔑 お客様専用 API 認証情報

### 本番環境（Production）

```yaml
環境: Production (本番)
API キー: pk_live_42c61da908dd515d9f0a6a99406c4dcb
Base URL: https://ifreamnet8-development.up.railway.app/api/v1
レート制限: 100,000 リクエスト/日
有効期限: 無期限（定期的なローテーション推奨）
```

**⚠️ 重要な注意事項:**
- この API キーは機密情報です。絶対に公開しないでください
- 環境変数に保存し、ソースコードには直接記述しないでください
- サーバーサイドでのみ使用してください（クライアントサイド JavaScript での使用不可）
- 定期的なローテーション（3〜6ヶ月ごと）を推奨します

---

## 📚 完全ドキュメントセット

### 1. API マニュアル（必読）

**日本語版:**
- ファイル: `API_MANUAL_JA.md`
- 内容: 全18個の API エンドポイント詳細説明
- サイズ: 約73KB、460行

**主要エンドポイント:**
- `POST /auth.php` - JWT トークン認証
- `POST /game_start.php` - ゲーム開始
- `POST /game_bet.php` - ベット記録（自動コールバック）
- `POST /game_win.php` - 勝利記録（自動コールバック）
- `POST /game_end.php` - ゲーム終了
- `POST /add_points.php` - ポイント追加
- `GET /list_machines.php` - マシン一覧取得
- `GET /models.php` - 機種一覧取得

### 2. リアルタイムコールバックガイド（重要）

**日本語版:**
- ファイル: `REALTIME_CALLBACK_GUIDE_JA.md`
- 内容: リアルタイムゲームデータ連携の実装方法
- サイズ: 約56KB、350行

**リアルタイムコールバックとは:**
- プレイヤーがゲームをプレイ中、1ゲームごとに自動的にベット・勝利データが送信されます
- Webhook 形式で貴社のサーバーに通知されます
- HMAC-SHA256 署名による安全な検証が可能です

### 3. API キー管理マニュアル（管理者向け）

**日本語版:**
- ファイル: `API_KEY_MANAGEMENT_JA.md`
- 内容: API キーの発行・管理・停止・監視方法
- サイズ: 約49KB

---

## 🚀 クイックスタート（5分で動作確認）

### ステップ1: JWT トークン取得

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{
    "apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"
  }'
```

**期待されるレスポンス:**

```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "live"
}
```

**✅ 成功確認:** `success: true` が返ればOK！このトークンを次のリクエストで使用します。

---

### ステップ2: マシン一覧取得（トークン使用）

**リクエスト例:**

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json"
```

**期待されるレスポンス:**

```json
{
  "success": true,
  "machines": [
    {
      "machineNo": "001",
      "modelNo": "M001",
      "modelName": "北斗の拳",
      "status": "available",
      "currentUser": null
    }
  ]
}
```

---

### ステップ3: ゲーム開始（統合テスト）

**リクエスト例:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/game_start.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "userName": "テストユーザー",
    "machineNo": "001",
    "initialCredit": 50000,
    "lang": "ja",
    "currency": "JPY",
    "callbackUrl": "https://your-server.com/api/webhook/net8",
    "callbackSecret": "your_secret_key_123"
  }'
```

**期待されるレスポンス:**

```json
{
  "success": true,
  "sessionId": "sess_abc123def456",
  "gameUrl": "https://ifreamnet8-development.up.railway.app/ch/play_v2/?sessionId=sess_abc123def456",
  "message": "Game started successfully"
}
```

**✅ 成功確認:** プレイヤーに `gameUrl` を表示し、iframe で埋め込みます。

---

## 🎮 実装例: JavaScript（推奨）

### 完全な統合フロー

```javascript
// 1. API キーを環境変数から取得（サーバーサイド）
const API_KEY = process.env.NET8_API_KEY; // pk_live_42c61da908dd515d9f0a6a99406c4dcb
const BASE_URL = 'https://ifreamnet8-development.up.railway.app/api/v1';

// 2. JWT トークン取得関数
async function getAuthToken() {
  const response = await fetch(`${BASE_URL}/auth.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ apiKey: API_KEY })
  });

  const data = await response.json();

  if (!data.success) {
    throw new Error('Authentication failed: ' + data.message);
  }

  return data.token; // JWT トークンを返す
}

// 3. ゲーム開始関数
async function startGame(userId, userName, machineNo) {
  const token = await getAuthToken();

  const response = await fetch(`${BASE_URL}/game_start.php`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      userId: userId,
      userName: userName,
      machineNo: machineNo,
      initialCredit: 50000,
      lang: 'ja',
      currency: 'JPY',
      callbackUrl: 'https://your-server.com/api/webhook/net8',
      callbackSecret: 'your_secret_key_123'
    })
  });

  const data = await response.json();

  if (data.success) {
    console.log('✅ ゲーム開始成功!');
    console.log('Session ID:', data.sessionId);
    console.log('Game URL:', data.gameUrl);

    // iframe にゲーム URL を設定
    document.getElementById('game-frame').src = data.gameUrl;

    return data;
  } else {
    throw new Error('Game start failed: ' + data.message);
  }
}

// 4. Webhook 受信処理（Node.js/Express 例）
const express = require('express');
const crypto = require('crypto');
const app = express();

app.post('/api/webhook/net8', express.json(), (req, res) => {
  // HMAC 署名検証
  const signature = req.headers['x-net8-signature'];
  const secret = 'your_secret_key_123';
  const payload = JSON.stringify(req.body);

  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(payload)
    .digest('hex');

  if (signature !== computedSignature) {
    return res.status(401).json({ error: 'Invalid signature' });
  }

  // イベント処理
  const { event, data } = req.body;

  switch(event) {
    case 'game.bet':
      console.log('🎰 ベット:', data.betAmount, 'JPY');
      console.log('残高:', data.creditAfter, 'JPY');
      // データベースに記録
      break;

    case 'game.win':
      console.log('🎉 勝利!:', data.winAmount, 'JPY');
      console.log('残高:', data.creditAfter, 'JPY');
      // データベースに記録
      break;

    case 'game.end':
      console.log('🏁 ゲーム終了');
      console.log('最終残高:', data.finalCredit, 'JPY');
      console.log('累計ベット:', data.totalBets, 'JPY');
      console.log('累計勝利:', data.totalWins, 'JPY');
      console.log('純損益:', data.totalWins - data.totalBets, 'JPY');
      // ユーザーの残高を更新
      break;
  }

  res.json({ success: true });
});

app.listen(3000, () => {
  console.log('Webhook server running on port 3000');
});
```

---

## 🔔 リアルタイムコールバック仕様

### プレイ中に自動送信されるイベント

| イベント | 送信タイミング | データ内容 |
|---------|-------------|-----------|
| **game.bet** | プレイヤーがベットするたび | ベット額、ベット前残高、ベット後残高 |
| **game.win** | プレイヤーが勝利するたび | 勝利額、勝利前残高、勝利後残高 |
| **game.end** | ゲーム終了時 | 最終残高、累計ベット、累計勝利、セッション ID |

### Webhook リクエスト形式

**ヘッダー:**
```
Content-Type: application/json
X-NET8-Signature: <HMAC-SHA256 署名>
```

**ペイロード例（game.bet）:**
```json
{
  "event": "game.bet",
  "timestamp": "2026-01-31T12:34:56Z",
  "data": {
    "sessionId": "sess_abc123def456",
    "userId": "test_user_001",
    "machineNo": "001",
    "betAmount": 300,
    "creditBefore": 50000,
    "creditAfter": 49700
  }
}
```

**署名検証:**
```javascript
const crypto = require('crypto');

function verifyWebhookSignature(payload, signature, secret) {
  const computedSignature = crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');

  return signature === computedSignature;
}
```

---

## 🌐 多言語・多通貨対応

### サポート言語

| コード | 言語 |
|-------|-----|
| `ja` | 日本語 |
| `zh` | 中国語（簡体字） |
| `ko` | 韓国語 |
| `en` | 英語 |

### サポート通貨

| コード | 通貨 | 最小ベット単位 |
|-------|-----|-------------|
| `JPY` | 日本円 | ¥300 |
| `CNY` | 中国元 | ¥30 |
| `USD` | 米ドル | $3 |
| `TWD` | 台湾ドル | NT$90 |

**使用例:**
```json
{
  "lang": "zh",
  "currency": "CNY",
  "initialCredit": 5000
}
```

---

## 🛠️ トラブルシューティング

### よくあるエラーと解決方法

#### ❌ エラー: "Invalid API Key"

**原因:**
- API キーが間違っている
- API キーが無効化されている

**解決方法:**
```bash
# API キーが正しいか確認
echo "pk_live_42c61da908dd515d9f0a6a99406c4dcb"

# 認証テスト
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

#### ❌ エラー: "Invalid or expired token"

**原因:**
- JWT トークンが期限切れ（1時間）
- トークンの形式が間違っている

**解決方法:**
```javascript
// トークンを再取得
const newToken = await getAuthToken();
```

#### ❌ エラー: "Rate limit exceeded"

**原因:**
- 1日のリクエスト制限（100,000回）を超過

**解決方法:**
- NET8 サポートに連絡し、制限の引き上げを依頼
- キャッシュを活用してリクエスト数を削減

#### ❌ エラー: "Machine not available"

**原因:**
- 指定したマシンが使用中または存在しない

**解決方法:**
```bash
# 利用可能なマシン一覧を取得
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 📞 テクニカルサポート

### サポート窓口

- **📧 Email:** support@net8gaming.com
- **🌐 Website:** https://net8gaming.com
- **📱 技術サポート:** https://docs.net8gaming.com
- **⏰ 営業時間:** 平日 9:00〜18:00 (JST)

### 緊急連絡先

- **セキュリティインシデント:** security@net8gaming.com
- **API 障害報告:** api-support@net8gaming.com

---

## 📋 チェックリスト: 本番環境デプロイ前

### セキュリティ

- [ ] API キーを環境変数に保存
- [ ] API キーをソースコードから削除
- [ ] HTTPS 通信のみを使用
- [ ] Webhook 署名検証を実装
- [ ] レート制限対策を実装

### 機能

- [ ] JWT トークン取得が成功
- [ ] ゲーム開始が成功
- [ ] iframe でゲームが正常に表示
- [ ] Webhook 受信が成功
- [ ] game.bet イベント処理が正常
- [ ] game.win イベント処理が正常
- [ ] game.end イベント処理が正常

### 監視

- [ ] API リクエストログを記録
- [ ] エラー監視を設定
- [ ] レート制限の使用率を監視
- [ ] Webhook 受信の失敗を監視

---

## 🎯 次のステップ

### 1. 統合テスト（1〜2日）

- [ ] JWT 認証テスト
- [ ] マシン一覧取得テスト
- [ ] ゲーム開始テスト
- [ ] Webhook 受信テスト
- [ ] エラーハンドリングテスト

### 2. ステージング環境デプロイ（3〜5日）

- [ ] 本番環境と同じ構成でテスト
- [ ] 負荷テスト実施
- [ ] セキュリティテスト実施

### 3. 本番環境デプロイ（1日）

- [ ] デプロイチェックリスト確認
- [ ] 段階的ロールアウト（10% → 50% → 100%）
- [ ] 監視ダッシュボード確認

---

## 📖 追加ドキュメント

### 中国語版ドキュメント

- `API_MANUAL_ZH.md` - API マニュアル（中文版）
- `REALTIME_CALLBACK_GUIDE_ZH.md` - リアルタイムコールバックガイド（中文版）
- `API_KEY_MANAGEMENT_ZH.md` - API キー管理マニュアル（中文版）
- `PARTNER_ONBOARDING_PACKAGE_ZH.md` - パートナー向けオンボーディングパッケージ（中文版）

### 技術仕様

- API レスポンスタイム: 平均 100〜300ms
- Webhook タイムアウト: 5秒
- JWT トークン有効期限: 1時間
- レート制限: 100,000 リクエスト/日
- 最大同時接続数: 1,000セッション

---

## ✨ まとめ

お客様の API キー `pk_live_42c61da908dd515d9f0a6a99406c4dcb` を使用して、すぐに統合を開始できます。

**今すぐ試す:**

```bash
# 認証テスト（5秒で完了）
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

ご不明な点がございましたら、いつでもサポートチームまでお問い合わせください。

---

**© 2026 NET8 Gaming. All rights reserved.**
