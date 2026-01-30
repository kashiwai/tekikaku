# NET8 API ドキュメントインデックス

**最終更新:** 2026年1月31日
**バージョン:** 1.0.0

---

## 📋 完全ドキュメントセット

このディレクトリには、NET8 オンラインパチスロ API の完全なドキュメントが含まれています。

---

## 🔑 発行済み API キー

### 本番環境（Production）

```
API キー: pk_live_42c61da908dd515d9f0a6a99406c4dcb
環境: live (本番)
レート制限: 100,000 リクエスト/日
発行日: 2026年1月31日
```

---

## 📚 ドキュメント一覧

### 1. パートナー向けオンボーディングパッケージ（推奨スタート）

**新しい外部パートナーはここから開始してください**

| ファイル名 | 言語 | サイズ | 内容 |
|----------|-----|-------|------|
| `PARTNER_ONBOARDING_PACKAGE_JA.md` | 日本語 | ~42KB | 完全な統合ガイド + API キー + クイックスタート |
| `PARTNER_ONBOARDING_PACKAGE_ZH.md` | 中文 | ~40KB | 完整的集成指南 + API 密钥 + 快速入门 |

**含まれる内容:**
- ✅ 実際の API キー（即利用可能）
- ✅ 5分で動作確認できるクイックスタート
- ✅ 完全な JavaScript 実装例
- ✅ Webhook 受信サンプルコード
- ✅ トラブルシューティングガイド
- ✅ 本番環境デプロイ前チェックリスト

---

### 2. API マニュアル（全エンドポイント詳細）

| ファイル名 | 言語 | サイズ | エンドポイント数 |
|----------|-----|-------|--------------|
| `API_MANUAL_JA.md` | 日本語 | 73KB | 18個 |
| `API_MANUAL_ZH.md` | 中文 | 71KB | 18个 |

**カバーする内容:**
- JWT 認証システム（`/auth.php`）
- ゲーム管理エンドポイント
  - `/game_start.php` - ゲーム開始
  - `/game_bet.php` - ベット記録
  - `/game_win.php` - 勝利記録
  - `/game_end.php` - ゲーム終了
- ポイント管理エンドポイント
  - `/add_points.php`, `/set_balance.php`, `/adjust_balance.php`
- クエリエンドポイント
  - `/list_machines.php`, `/models.php`, `/play_history.php`
- 多言語・多通貨対応（ja/zh/ko/en, JPY/CNY/USD/TWD）
- エラーコード完全リファレンス

---

### 3. リアルタイムコールバックガイド（重要）

| ファイル名 | 言語 | サイズ | 内容 |
|----------|-----|-------|------|
| `REALTIME_CALLBACK_GUIDE_JA.md` | 日本語 | 56KB | リアルタイムデータ連携の実装 |
| `REALTIME_CALLBACK_GUIDE_ZH.md` | 中文 | 53KB | 实时数据连接的实现 |

**リアルタイムコールバックとは:**

プレイヤーがゲームをプレイ中、以下のイベントが自動的に貴社のサーバーに送信されます:

| イベント | 送信タイミング | データ内容 |
|---------|-------------|-----------|
| `game.bet` | ベットするたび | ベット額、残高 |
| `game.win` | 勝利するたび | 勝利額、残高 |
| `game.end` | ゲーム終了時 | 累計ベット、累計勝利、最終残高 |

**セキュリティ:**
- HMAC-SHA256 署名による Webhook 検証
- HTTPS 必須
- タイムスタンプによるリプレイ攻撃防止

---

### 4. API キー管理マニュアル（管理者向け）

| ファイル名 | 言語 | サイズ | 対象 |
|----------|-----|-------|------|
| `API_KEY_MANAGEMENT_JA.md` | 日本語 | 49KB | NET8 管理者 |
| `API_KEY_MANAGEMENT_ZH.md` | 中文 | 47KB | NET8 管理员 |

**管理機能:**
- API キーの発行方法（管理画面 / SQL）
- レート制限の調整
- キーの有効化/無効化
- 使用統計の確認
- セキュリティインシデント対応

**管理画面:**
```
URL: https://ifreamnet8-development.up.railway.app/xxxadmin/api_keys_manage.php
```

---

## 🚀 クイックスタート（3ステップ）

### ステップ1: JWT トークン取得（5秒）

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

**期待される結果:**
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "live"
}
```

### ステップ2: マシン一覧取得（5秒）

```bash
curl -X GET "https://ifreamnet8-development.up.railway.app/api/v1/list_machines.php" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### ステップ3: ゲーム開始（10秒）

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
    "currency": "JPY"
  }'
```

**✅ 成功:** `gameUrl` を iframe で表示すればゲームプレイ可能！

---

## 🔔 リアルタイムコールバック設定

### game_start.php に追加パラメータを送信

```json
{
  "userId": "user_001",
  "machineNo": "001",
  "initialCredit": 50000,
  "callbackUrl": "https://your-server.com/api/webhook/net8",
  "callbackSecret": "your_secret_key_123"
}
```

### Webhook 受信例（Node.js/Express）

```javascript
const express = require('express');
const crypto = require('crypto');
const app = express();

app.post('/api/webhook/net8', express.json(), (req, res) => {
  // 1. HMAC 署名検証
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

  // 2. イベント処理
  const { event, data } = req.body;

  switch(event) {
    case 'game.bet':
      console.log('🎰 ベット:', data.betAmount);
      // データベースに記録
      break;

    case 'game.win':
      console.log('🎉 勝利:', data.winAmount);
      // データベースに記録
      break;

    case 'game.end':
      console.log('🏁 ゲーム終了');
      console.log('累計ベット:', data.totalBets);
      console.log('累計勝利:', data.totalWins);
      // ユーザー残高を更新
      break;
  }

  res.json({ success: true });
});

app.listen(3000);
```

---

## 🌐 多言語・多通貨対応

### サポート言語

| コード | 言語 | ドキュメント |
|-------|-----|-----------|
| `ja` | 日本語 | ✅ 完備 |
| `zh` | 中国語 | ✅ 完備 |
| `ko` | 韓国語 | ✅ サポート |
| `en` | 英語 | ✅ サポート |

### サポート通貨

| コード | 通貨 | 最小ベット | ドキュメント |
|-------|-----|----------|-----------|
| `JPY` | 日本円 | ¥300 | ✅ 完備 |
| `CNY` | 中国元 | ¥30 | ✅ 完備 |
| `USD` | 米ドル | $3 | ✅ サポート |
| `TWD` | 台湾ドル | NT$90 | ✅ サポート |

---

## 🛠️ 技術仕様

| 項目 | 値 |
|-----|---|
| **API バージョン** | v1 |
| **認証方式** | JWT Bearer Token |
| **トークン有効期限** | 1時間 |
| **レート制限** | 100,000 リクエスト/日（本番） |
| **レスポンスフォーマット** | JSON |
| **最大同時セッション数** | 1,000 |
| **平均レスポンスタイム** | 100〜300ms |
| **Webhook タイムアウト** | 5秒 |
| **サポートプロトコル** | HTTPS のみ |

---

## 📞 サポート窓口

### 技術サポート

- **📧 Email:** support@net8gaming.com
- **🌐 Website:** https://net8gaming.com
- **📱 技術ドキュメント:** https://docs.net8gaming.com
- **⏰ 営業時間:** 平日 9:00〜18:00 (JST)

### 緊急連絡先

| 種類 | 連絡先 | 対応時間 |
|-----|-------|---------|
| **セキュリティインシデント** | security@net8gaming.com | 24時間 |
| **API 障害報告** | api-support@net8gaming.com | 24時間 |
| **一般問い合わせ** | support@net8gaming.com | 平日 9:00〜18:00 |

---

## 📋 ドキュメント選択ガイド

### 外部パートナー（統合事業者）の場合

**最初に読むべきドキュメント:**
1. ✅ `PARTNER_ONBOARDING_PACKAGE_JA.md` または `PARTNER_ONBOARDING_PACKAGE_ZH.md`
2. ✅ `API_MANUAL_JA.md` または `API_MANUAL_ZH.md`（リファレンス）
3. ✅ `REALTIME_CALLBACK_GUIDE_JA.md` または `REALTIME_CALLBACK_GUIDE_ZH.md`（リアルタイムデータが必要な場合）

**推奨読了時間:** 1〜2時間
**実装開始まで:** 1〜2日

---

### NET8 管理者の場合

**最初に読むべきドキュメント:**
1. ✅ `API_KEY_MANAGEMENT_JA.md` または `API_KEY_MANAGEMENT_ZH.md`
2. ✅ このドキュメント（`API_DOCUMENTATION_INDEX.md`）

**推奨読了時間:** 30分
**API キー発行まで:** 5分

---

## 🎯 統合フロー全体図

```
┌─────────────────────────────────────────────────────────────┐
│ 1. API キー取得（NET8 管理者が発行）                           │
│    pk_live_42c61da908dd515d9f0a6a99406c4dcb                 │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. JWT トークン取得（POST /auth.php）                        │
│    有効期限: 1時間                                           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 3. ゲーム開始（POST /game_start.php）                        │
│    + callbackUrl, callbackSecret を設定                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 4. ゲームプレイ（iframe で gameUrl を表示）                   │
│    ↓ プレイ中、自動コールバック送信 ↓                         │
│    - game.bet（ベットごと）                                  │
│    - game.win（勝利ごと）                                    │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 5. ゲーム終了（自動 or POST /game_end.php）                  │
│    + game.end コールバック送信                               │
│    + 最終残高、累計ベット・勝利を返却                          │
└─────────────────────────────────────────────────────────────┘
```

---

## ✅ チェックリスト: 統合完了確認

### 認証

- [ ] API キーで JWT トークン取得成功
- [ ] JWT トークンで各エンドポイントにアクセス成功
- [ ] トークン有効期限（1時間）を考慮した実装

### ゲーム統合

- [ ] マシン一覧取得成功
- [ ] ゲーム開始成功
- [ ] iframe でゲームが正常に表示
- [ ] ゲーム終了時に残高が正しく返却

### リアルタイムコールバック

- [ ] callbackUrl, callbackSecret を設定
- [ ] game.bet イベント受信成功
- [ ] game.win イベント受信成功
- [ ] game.end イベント受信成功
- [ ] HMAC 署名検証を実装

### セキュリティ

- [ ] API キーを環境変数に保存
- [ ] HTTPS 通信のみを使用
- [ ] Webhook 署名検証を実装
- [ ] エラーログを記録

### 本番環境準備

- [ ] レート制限（100,000/日）を考慮した実装
- [ ] エラーハンドリングを実装
- [ ] 監視・アラート設定
- [ ] ステージング環境でのテスト完了

---

## 📖 バージョン履歴

| バージョン | 日付 | 変更内容 |
|----------|------|---------|
| 1.0.0 | 2026-01-31 | 初回リリース - 全ドキュメント完成 |

---

## 🎉 まとめ

すべてのドキュメントが揃っています。**PARTNER_ONBOARDING_PACKAGE_JA.md**（または中国語版）から開始することで、最短30分で統合テストを開始できます。

**今すぐ試す:**

```bash
curl -X POST "https://ifreamnet8-development.up.railway.app/api/v1/auth.php" \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_live_42c61da908dd515d9f0a6a99406c4dcb"}'
```

ご不明な点がございましたら、いつでもサポートチームまでお問い合わせください。

---

**© 2026 NET8 Gaming. All rights reserved.**
