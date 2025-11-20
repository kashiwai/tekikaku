# TransFi Onramp 実装ドキュメント

## 実装方法

このアプリには2つの実装方法があります：

### 1. Widget統合（推奨・動作確認済み）

TransFiのホストされたウィジェットを使用します。

**URL:** http://localhost:3000/ または http://localhost:3000/widget

**特徴:**
- TransFi側が全ての決済処理・UI・バリデーションを管理
- iFrameで埋め込むだけで利用可能
- 確実に動作する
- KYC/KYBプロセスもTransFi側で処理

**実装内容:**
- `public/widget.html` - TransFi Widgetを埋め込んだページ
- Webhook受信エンドポイント: `POST /api/webhook`
- 取引履歴表示: データベースから履歴を取得

### 2. API直接統合（開発中）

TransFi APIを直接呼び出す方法です。

**URL:** http://localhost:3000/api-demo

**現在の状況:**
- TransFi APIの `invoiceId` 要件により、注文作成に問題が発生
- Invoice作成APIのドキュメントが不明確
- Sandbox環境での制限事項が不明

**必要な対応:**
1. TransFi Dashboardで利用可能なAPIエンドポイントを確認
2. Invoice作成APIの実装
3. 完全なパラメータ仕様の確認

## Widget URL パラメータ

```
https://buy.transfi.com/?apiKey=MSXR6W_NA_NA&defaultCrypto=USDT&defaultFiat=JPY&theme=light
```

**利用可能なパラメータ:**
- `apiKey`: MerchantID（MSXR6W_NA_NA）
- `defaultCrypto`: デフォルト暗号資産（USDT, USDC, BTC, ETH）
- `defaultFiat`: デフォルト法定通貨（JPY, USD, EUR）
- `theme`: テーマ（light, dark）
- `walletAddress`: 事前入力するウォレットアドレス（オプション）

## Webhook設定

TransFi Dashboardで以下を設定してください：

```
Webhook URL: http://localhost:3000/api/webhook
Webhook Secret: qd1NxUxy053Mv4
```

### Webhookイベント

TransFiから以下のイベントが送信されます：

- `onramp.order.initiated` - 注文開始
- `onramp.order.fund_settled` - 入金完了
- `onramp.order.asset_settled` - 暗号資産送金完了
- `onramp.order.failed` - 失敗

### Webhook署名検証

```javascript
const crypto = require('crypto');

const signature = req.headers['x-transfi-signature'];
const payload = JSON.stringify(req.body);

const expectedSignature = crypto
  .createHmac('sha256', process.env.TRANSFI_WEBHOOK_SECRET)
  .update(payload)
  .digest('hex');

if (signature !== expectedSignature) {
  return res.status(401).json({ error: 'Invalid signature' });
}
```

## デプロイ時の注意事項

### 1. 環境変数

```bash
TRANSFI_API_USERNAME=medicalsandbox
TRANSFI_API_PASSWORD=pUidKxYgUQzrya
TRANSFI_MID=MSXR6W_NA_NA
TRANSFI_BASE_URL=https://sandbox-api.transfi.com
TRANSFI_WEBHOOK_SECRET=qd1NxUxy053Mv4
```

### 2. Webhook URL更新

本番環境では、Webhook URLを実際のドメインに更新：

```
https://yourapp.com/api/webhook
```

### 3. Widget URL更新

本番環境用のAPIキーを使用：

```html
<iframe src="https://buy.transfi.com/?apiKey=YOUR_PRODUCTION_KEY&...">
```

### 4. HTTPS必須

本番環境ではHTTPS接続が必須です。

## トラブルシューティング

### Widgetが表示されない

1. ブラウザのコンソールでエラーを確認
2. APIキー（MID）が正しいか確認
3. iFrameの`sandbox`属性を確認

### Webhookが受信されない

1. TransFi Dashboardで正しいURLが設定されているか確認
2. サーバーが外部からアクセス可能か確認（ngrok等を使用）
3. Webhook署名検証が正しいか確認

### 取引履歴が表示されない

1. データベース接続を確認
2. `/api/orders` エンドポイントが動作しているか確認
3. ブラウザのコンソールでエラーを確認

## 次のステップ

1. **本番環境への移行:**
   - 本番用APIキーの取得
   - HTTPS環境のセットアップ
   - Webhook URLの更新

2. **機能拡張:**
   - ユーザー認証の追加
   - KYC情報の保存
   - メール通知の実装
   - 取引詳細ページの追加

3. **API直接統合の完成:**
   - Invoice APIの実装
   - 完全なエラーハンドリング
   - リトライロジックの実装
