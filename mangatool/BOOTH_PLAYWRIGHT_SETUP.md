# BOOTH Playwright 自動出品セットアップ

Playwright を使用した BOOTH 自動出品機能の設定ガイドです。

---

## 📋 前提条件

- BOOTH 販売者アカウント（pixiv ID でログイン可）
- Python 3.11+
- Playwright がインストールされている

---

## ⚙️ セットアップ手順

### ステップ 1: 環境変数を設定

`backend/.env` ファイルを編集：

```env
# BOOTH ログイン情報（Playwright自動化用）
BOOTH_EMAIL=your-booth-email@example.com
BOOTH_PASSWORD=your-booth-password
BOOTH_HEADLESS=True  # True=ヘッドレスモード, False=ブラウザウィンドウを表示
```

**⚠️ セキュリティ警告：**
- パスワードを平文で保存するのはリスクです
- ローカル開発時のみ使用してください
- 本番環境では以下の対策を検討：
  1. AWS Secrets Manager で管理
  2. GitHub Actions の暗号化シークレット
  3. HashiCorp Vault

---

### ステップ 2: Playwright ブラウザをインストール

```bash
cd backend
pip install -r requirements.txt
playwright install chromium
```

---

### ステップ 3: ローカルテスト

バックエンド起動：

```bash
cd backend
python main.py
```

フロントエンド起動（別ターミナル）：

```bash
cd frontend
npm run dev -- -p 3002
```

ブラウザで以下にアクセス：
```
http://localhost:3002
```

**テストフロー：**
1. 漫画を完成させる
2. 詳細ページで「BOOTH で販売」をクリック
3. 価格設定（推奨：¥2,500）
4. 「BOOTH で出品」をクリック
5. バックグラウンドで自動出品が実行

---

## 🔍 トラブルシューティング

### 「BOOTH login failed」

**原因:**
- BOOTH_EMAIL / BOOTH_PASSWORD が間違っている
- BOOTH アカウントにセキュリティ設定がある

**対応:**
1. BOOTH に直接ログインしてメール・パスワードを確認
2. 2段階認証が有効な場合は無効に（テスト用）

### Playwright がブラウザを開かない

**原因:**
- Chromium がインストールされていない

**対応:**
```bash
playwright install chromium
```

### timeout エラー

**原因:**
- BOOTH のページロード時間が長い
- ネットワーク遅延

**対応:**
`booth_browser_service.py` の timeout を増やす：
```python
await self.page.wait_for_url("**/dashboard", timeout=30000)  # 30秒に延長
```

---

## 📊 動作確認

Railway ログでステータスを確認：

```bash
railway logs --follow
```

出力例：
```
Successfully logged in as your-booth-email@example.com
Successfully uploaded file from https://...
Successfully created product: item-id-12345
```

---

## 🚀 本番デプロイ

### Railway での設定

Railway Project Variables に追加：

```
BOOTH_EMAIL=<本番BOOTH メール>
BOOTH_PASSWORD=<本番BOOTH パスワード>
BOOTH_HEADLESS=True
```

⚠️ **重要：** 本番環境では以下を実施してください：

1. **パスワード管理：** AWS Secrets Manager または HashiCorp Vault を使用
2. **ログ監視：** Sentry でエラー追跡
3. **レート制限：** 短時間での多数出品は避ける
4. **テスト環境：** 本番前に十分テスト

---

## 📈 ステータス管理

DB テーブル `booth_listings` のステータス遷移：

```
DRAFT 
  ↓（出品開始時）
PENDING 
  ↓（自動出品成功）
PUBLISHED 
  ↓（キャンセル時）
ARCHIVED
```

---

## 🔐 セキュリティベストプラクティス

### ✅ すべきこと

- [ ] テスト用アカウントで先にテスト
- [ ] ローカル開発環境でのみ .env を使用
- [ ] 本番環境では環境変数を暗号化
- [ ] BOOTH との連絡：規約違反がないか確認
- [ ] エラーログを監視

### ❌ してはいけないこと

- パスワードをコードに埋め込む
- Git に `.env` をコミット
- パスワードをログに出力
- 無限ループで多数の商品を出品

---

## 📞 サポート

問題が発生した場合：

1. Railway ログを確認
2. `.env` ファイルが正しく設定されているか確認
3. BOOTH のメール・パスワードが正しいか確認

---

**セットアップ完了！🎉**
