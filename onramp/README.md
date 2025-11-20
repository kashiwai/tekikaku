# TransFi Onramp Application

日本円から仮想通貨への変換を行うオンランプアプリケーション

## 機能

- 対応通貨の表示
- リアルタイム為替レート取得
- 日本円→仮想通貨の注文作成
- 取引履歴の保存・表示

## 技術スタック

- **Backend**: Node.js + Express
- **Database**: SQLite
- **API**: TransFi Ramp API
- **Frontend**: HTML/CSS/JavaScript

## セットアップ

1. 依存関係のインストール:
```bash
npm install
```

2. 環境変数の設定:
```bash
cp .env.example .env
```

`.env`ファイルを編集して、TransFi APIキーとMerchant IDを設定してください。

3. アプリケーションの起動:
```bash
npm run dev  # 開発モード
npm start    # 本番モード
```

4. ブラウザでアクセス:
```
http://localhost:3000
```

## TransFi API設定

### ✅ 現在の設定状態

以下のTransFi Sandbox認証情報が設定済みです：
- **MID (Merchant ID)**: MSXR6W_NA_NA
- **API Username**: medicalsandbox
- **API Password**: 設定済み
- **Webhook Secret**: 設定済み

### TransFi Dashboardで設定が必要なURL

[TransFi Dashboard](https://sandbox-api-dashboard.transfi.com/settings) の「API Integration Settings」で以下を設定してください：

```
Webhook URL: http://localhost:3000/api/webhook
Redirect URL: http://localhost:3000/order-complete
```

**注意**: 本番環境では、`localhost`を実際のドメイン名に置き換えてください。

## API エンドポイント

### 公開API

- `GET /api/currencies` - 対応通貨一覧 (JPY, USD, EUR, GBP)
- `GET /api/tokens` - 対応トークン一覧 (USDT, USDC, BTC, ETH)
- `GET /api/payment-methods` - TransFi支払い方法一覧（銀行振込など）
- `POST /api/quote` - 為替レート見積もり
- `POST /api/order` - オンランプ注文作成
- `GET /api/orders` - 取引履歴取得
- `GET /api/orders/:id` - 特定の注文詳細取得

### Webhook

- `POST /api/webhook` - TransFiからの通知受信（注文ステータス更新）

## 使い方

1. ブラウザで http://localhost:3000 にアクセス
2. 金額（JPY）を入力
3. 受取暗号資産を選択 (USDT, USDC, BTC, ETH)
4. ウォレットアドレスを入力
5. 「見積もりを取得」ボタンをクリック
6. 為替レートを確認
7. 「注文を作成」ボタンをクリック
8. TransFiの支払い画面に遷移（本番環境の場合）

## 対応通貨・トークン

### 法定通貨（Fiat）
- 🇯🇵 JPY (日本円)
- 🇺🇸 USD (米ドル)
- 🇪🇺 EUR (ユーロ)
- 🇬🇧 GBP (英ポンド)

### 暗号資産（Crypto）
- USDT (Tether)
- USDC (USD Coin)
- BTC (Bitcoin)
- ETH (Ethereum)

### 支払い方法
- 銀行振込（Bank Transfer）
- 最小額: 300 JPY
- 最大額: 5,000,000 JPY

## ライセンス

ISC
