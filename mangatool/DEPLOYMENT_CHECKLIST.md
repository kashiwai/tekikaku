# BOOTH 販売代行システム デプロイチェックリスト

## ✅ ローカル動作確認

- [ ] `pip install -r requirements.txt` で reportlab インストール確認
- [ ] `python -m alembic upgrade head` で DB マイグレーション実行
- [ ] バックエンド起動確認: `python main.py`
- [ ] フロントエンド起動確認: `npm run dev` (port 3002)
- [ ] 漫画詳細ページで「BOOTH で販売」ボタン表示確認
- [ ] PDF ダウンロード機能テスト
- [ ] モーダルで価格入力 → API 呼び出し確認

---

## 📋 Railway環境変数設定

Railway Dashboard で以下を設定:

```
# BOOTH API Configuration
BOOTH_CLIENT_ID=<BOOTH DevConsoleから取得>
BOOTH_CLIENT_SECRET=<BOOTH DevConsoleから取得>
BOOTH_REDIRECT_URI=https://mangatool-production.up.railway.app/auth/booth/callback
```

**設定手順:**
1. Railway Project > Variables
2. 上記の 3 つの環境変数を追加
3. Deploy > Redeploy を実行

---

## Vercel環境変数設定

Vercel Project Settings で以下を確認:

```
NEXT_PUBLIC_SUPABASE_URL=https://...
NEXT_PUBLIC_SUPABASE_ANON_KEY=...
```

---

## Ⓜ️ BOOTH API 登録（手動作業）

### BOOTH Developer Console 登録

1. https://booth.pm/developer へアクセス
2. BOOTH アカウントでログイン（pixiv ID が必要）
3. 「Application」 → 「New App」
4. アプリ名: `comicCockpit`
5. リダイレクト URI:
   - ローカル: `http://localhost:3000/auth/booth/callback`
   - 本番: `https://mangatool.vercel.app/auth/booth/callback`
6. スコープ選択:
   - `item:read`（商品取得）
   - `item:write`（商品作成）
   - `sales:read`（売上取得）
   - `webhook:manage`（webhook 管理）
7. 生成された `CLIENT_ID` と `CLIENT_SECRET` をメモ
8. Railway に環境変数として設定

### Webhook 設定（BOOTH Console）

1. Application Settings > Webhooks
2. URL: `https://mangatool-production.up.railway.app/webhook/booth`
3. Events: `order.completed`, `payment.succeeded`
4. Webhook Secret をメモ（環境変数に追加）

---

## 🧪 本番前テスト

### E2E テストフロー

1. **本番ステージング環境**
   - Vercel Preview URL で UI テスト
   - Railway staging branch で API テスト

2. **完全な販売フロー**
   - 漫画を完成させる
   - 「BOOTH で販売」ボタンをクリック
   - 販売価格を ¥2,500 に設定
   - 「BOOTH で出品」をクリック
   - BOOTHListing レコード作成確認（DB確認）
   - 配分額計算確認:
     - 売上：¥2,500
     - BOOTH手数料（10%）：¥250
     - お客様獲得（60%）：¥1,500
     - mangatool獲得（40%）：¥1,000

3. **実際の売上テスト**（BOOTH Test Mode で可能）
   - テスト注文作成
   - webhook 受信確認
   - CommissionPayment レコード自動作成確認

---

## 🚀 デプロイ手順

### 1. バックエンド（Railway）

```bash
# ローカルでマイグレーション確認
cd backend
python -m alembic upgrade head

# Railway に push（GitHub連携）
git add .
git commit -m "feat: add BOOTH sales integration"
git push origin main

# Railway が自動デプロイ
# ただし、環境変数を先に設定すること
```

### 2. フロントエンド（Vercel）

```bash
# ローカルでビルド確認
cd frontend
npm run build

# Vercel に push（GitHub連携）
git push origin main

# または手動デプロイ
vercel --prod
```

### 3. 本番確認

- Vercel: https://mangatool.vercel.app
- Railway: https://mangatool-production.up.railway.app

---

## 🔍 本番環境でのモニタリング

### ログ確認

```bash
# Railway ログ
railway logs

# Vercel ログ
vercel logs
```

### メトリクス監視

- BOOTH 出品リクエスト成功率
- 売上記録作成スピード
- Commission Payment 自動計算正確性

---

## ⚠️ トラブルシューティング

| 症状 | 原因 | 対応 |
|------|------|------|
| 「BOOTH で販売」ボタンが表示されない | ステータスが `completed` でない | 漫画が正常に完成しているか確認 |
| モーダルが開かない | フロント build エラー | `npm run build` で確認 |
| API エラー 400 | ページがない | ページが正常に生成されているか確認 |
| BOOTH API 401 エラー | API キー無効 | BOOTH_CLIENT_ID/SECRET 確認 |
| DB テーブル不足 | マイグレーション未実行 | Railway で `alembic upgrade head` を実行 |

---

## ✨ 本番リリース完了後

1. BOOTH 販売ページを公開
2. ドキュメント更新（ユーザー向けガイド）
3. Analytics 設定（BOOTH 売上トラッキング）
4. サポートチケット準備（販売関連の問い合わせ対応）

---

**デプロイ準備完了！** 🎉
