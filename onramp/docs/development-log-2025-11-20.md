# TransFi Onramp 開発ログ - 2025-11-20

## プロジェクト概要

日本円（JPY）から仮想通貨への変換を可能にするOnrampアプリケーション。TransFi APIを統合し、ユーザーが簡単に法定通貨から暗号資産を購入できるWebサービス。

## 技術スタック

- **Backend**: Node.js + Express
- **Database**: SQLite
- **API Integration**: TransFi Sandbox API
- **Frontend**: Vanilla JavaScript + HTML/CSS

## 開発経緯

### フェーズ1: API直接統合の実装（開発中・未完成）

#### 実装内容
- プロジェクト構造のセットアップ完了
- SQLite データベース統合完了
- TransFi API サービスレイヤー実装
- REST APIエンドポイント作成
- フロントエンドUI（フォーム入力画面）作成

#### 発生したエラーと対応

**Error 1: "paymentType is required"**
- 対応: `orderController.js`に`paymentType: 'bank_transfer'`を追加
- 結果: 解決

**Error 2: "Invalid purposeCode"**
- 問題: `purposeCode: 'personal_expense'`が無効
- 対応: `purposeCode: 'personal'`に変更
- 有効な値: 'insurance_claims', 'maintenance_expenses', 'personal', 'remittance'
- 結果: 解決

**Error 3: "invoiceId is required"**
- 問題: TransFi API `/v2/orders/deposit`が`invoiceId`を要求
- 対応: 一時的に`invoiceId: INV_${Date.now()}`を追加
- 結果: 次のエラーへ

**Error 4: "Invoice not found"**
- 問題: TransFi側でInvoiceが存在しないとエラー
- 根本原因: Invoice作成APIが別途必要だが、ドキュメント不明
- ユーザー報告: 「注文を作成しても、反応がないです」
- 状態: **未解決**

#### 現在のコード状態

`src/controllers/orderController.js`（API直接統合版）:
```javascript
const orderData = {
  firstName: req.body.firstName || 'Demo',
  lastName: req.body.lastName || 'User',
  email: req.body.email || 'demo@example.com',
  country: req.body.country || 'JP',
  type: 'individual',
  amount: parseFloat(sourceAmount),
  currency: sourceCurrency,
  paymentType: paymentMethod || 'bank_transfer',
  purposeCode: req.body.purposeCode || 'personal',
  partnerId: `ORDER_${Date.now()}`,
  redirectUrl: `${req.protocol}://${req.get('host')}/order-complete`,
  withdrawDetails: {
    cryptoTicker: targetCurrency,
    walletAddress: walletAddress
  }
};
```

### フェーズ2: Widget統合の実装（実装完了・Sandbox制限あり）

#### 背景
- API直接統合が「Invoice not found」エラーで行き詰まり
- ユーザー要望: 「でもモードは意味がないので、実際にちゃんと動くものを作ってください」
- TransFi公式WidgetはiFrame埋め込みで利用可能

#### 実装内容

**作成したファイル:**

1. **`public/widget.html`**
   - TransFi公式Widget（iFrame）を埋め込み
   - 取引履歴表示機能
   - Widget URLパラメータ設定
   ```html
   <iframe
     src="https://buy.transfi.com/?apiKey=MSXR6W_NA_NA&defaultCrypto=USDT&defaultFiat=JPY&theme=light"
     allow="accelerometer; autoplay; camera; gyroscope; payment"
     sandbox="allow-same-origin allow-scripts allow-popups allow-forms"
   ></iframe>
   ```

2. **`public/test-widget.html`**
   - デバッグ用テストページ
   - エラー検出機能
   - iFrame読み込み状態の監視

3. **`IMPLEMENTATION.md`**
   - Widget統合とAPI統合の両方を詳細ドキュメント化
   - パラメータ仕様
   - Webhook設定手順
   - トラブルシューティングガイド

**ルート更新:**

`src/index.js`:
```javascript
// Root URL → Widget画面
app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/widget.html'));
});

// 旧API統合画面（保持）
app.get('/api-demo', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/index.html'));
});

// Widget直接アクセス
app.get('/widget', (req, res) => {
  res.sendFile(path.join(__dirname, '../public/widget.html'));
});
```

#### 発生した問題

**Widget表示エラー:**
```
Couldn't Find You
Oops! We couldn't find you in our list of active customers
For Any assistance please contact us at customercare@transfi.com
```

**原因分析:**
1. TransFi Sandboxアカウント（MID: `MSXR6W_NA_NA`）がWidget統合用のアクティブ顧客リストに存在しない
2. Widget統合機能がSandbox環境で制限されている可能性
3. 別のMIDまたは設定が必要

## 環境設定

### 環境変数 (`.env`)

```bash
# TransFi API Configuration
TRANSFI_API_USERNAME=medicalsandbox
TRANSFI_API_PASSWORD=pUidKxYgUQzrya
TRANSFI_MID=MSXR6W_NA_NA
TRANSFI_BASE_URL=https://sandbox-api.transfi.com
TRANSFI_WEBHOOK_SECRET=qd1NxUxy053Mv4

# Server Configuration
PORT=3000
NODE_ENV=development

# Database
DB_PATH=./data/onramp.db
```

### TransFi Dashboard 設定項目

**必要な設定:**
- Webhook URL: `http://localhost:3000/api/webhook`
- Redirect URL: `http://localhost:3000/order-complete`

## プロジェクト構造

```
onramp/
├── src/
│   ├── index.js                  # メインサーバー
│   ├── config.js                 # 環境設定
│   ├── controllers/
│   │   └── orderController.js    # 注文処理ロジック
│   ├── services/
│   │   └── transFiService.js     # TransFi API統合
│   ├── models/
│   │   └── database.js           # SQLiteデータベース
│   └── routes/
│       └── api.js                # APIルート定義
├── public/
│   ├── index.html                # API統合画面（旧）
│   ├── widget.html               # Widget統合画面（新）
│   ├── test-widget.html          # Widget テストページ
│   └── css/
│       └── style.css             # スタイル
├── data/
│   └── onramp.db                 # SQLiteデータベース
├── docs/
│   └── development-log-2025-11-20.md  # このファイル
├── IMPLEMENTATION.md             # 実装ドキュメント
├── package.json
└── .env
```

## 現在の状態

### ✅ 完成した機能
- SQLiteデータベース統合
- 取引履歴保存・取得API
- Webhook受信エンドポイント
- フロントエンドUI（両方式）
- Widget統合のコード実装

### ❌ 未完成・問題がある機能
1. **API直接統合**: Invoice API実装が未完成
2. **Widget統合**: Sandbox環境でアカウントが認識されない

### ⚠️ ブロッカー

**API直接統合:**
- Invoice作成APIのエンドポイント・仕様が不明
- TransFi公式ドキュメントに詳細情報なし

**Widget統合:**
- MID `MSXR6W_NA_NA` がWidget用のアクティブ顧客リストに存在しない
- Sandbox環境での制限の可能性

## 次のアクションアイテム

### 優先度: 高

1. **TransFi Dashboardの徹底確認**
   - Settings → API Keys セクション
   - Widget統合の有効化設定
   - 利用可能なAPI一覧の確認
   - Invoice API のドキュメント探索

2. **TransFiサポートへの問い合わせ**
   - Widget統合の有効化依頼
   - Invoice API仕様の確認
   - Sandbox環境の制限事項確認

### 優先度: 中

3. **代替アプローチの検討**
   - 他のOnrampサービス（Ramp, MoonPay等）の検討
   - TransFi以外のソリューション調査

4. **本番環境移行の準備**
   - 本番用APIキーの取得プロセス確認
   - HTTPS環境のセットアップ計画

## 技術的な学び

### TransFi API の特性
- Basic認証（Username/Password）を使用
- Webhookで取引ステータスを通知
- Widget統合とAPI統合の2つの方式がある
- Sandbox環境には機能制限がある可能性

### 発生したエラーパターン
1. 必須フィールド漏れ → APIレスポンスから特定
2. Enum値の不一致 → ドキュメント調査が必要
3. 外部リソース依存（Invoice） → 事前作成APIが必要

### トラブルシューティング手法
- ブラウザコンソールでのエラー確認
- ネットワークタブでAPI応答の確認
- iFrame読み込み状態の監視
- TransFi Dashboardでの設定確認

## コミット履歴

```
a5b670e docs: Add development log for 2025-11-10
0d04af5 docs: Add quickstart guide for next development session
b555ed6 docs: Add comprehensive project development log for 2025-11-15 session
7114744 docs: Add comprehensive SDK development log
5fe8274 fix: Add noticeTypeLang definition and fix corner.php error messages
```

## リソース

### TransFi 関連
- Sandbox Dashboard: https://sandbox-api-dashboard.transfi.com/
- API Base URL: https://sandbox-api.transfi.com
- Widget URL: https://buy.transfi.com/
- サポート: customercare@transfi.com

### ドキュメント
- TransFi Docs: https://docs.transfi.com
- 本プロジェクト: `/Users/kotarokashiwai/onramp/IMPLEMENTATION.md`

## 結論

現在、TransFi Onramp統合は**2つの方式ともブロックされている状態**です：

1. **API直接統合**: Invoice API仕様不明で未完成
2. **Widget統合**: Sandboxアカウントが認識されず動作不可

**推奨される次のステップ:**
- TransFi Dashboardで設定を徹底確認
- 必要に応じてTransFiサポートに連絡
- Invoice API仕様の取得
- または、別のOnrampサービスへの切り替えを検討

---

**記録日時:** 2025-11-20
**開発者:** Claude Code
**プロジェクトステータス:** 開発中（ブロッカーあり）
