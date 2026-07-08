# BOOTH OAuth セットアップ手順

## 前提条件

- pixiv ID（BOOTH アカウント必須）
- BOOTH 販売者アカウント登録済み

---

## 📝 ステップ1: BOOTH Developer Console 登録

### アクセス方法

1. https://booth.pm/developer にアクセス
2. pixiv ID でログイン

### Application 作成

1. **Dashboard** → **Applications** → **New App**
2. 以下を入力:

| 項目 | 値 |
|------|-----|
| Application Name | `comicCockpit` |
| Website | `https://mangatool.vercel.app` |
| Description | `AI漫画自動生成・販売プラットフォーム` |

3. **Redirect URIs** に以下を追加:
   - ローカル: `http://localhost:3000/auth/booth/callback`
   - ステージング: `https://staging.mangatool.vercel.app/auth/booth/callback`
   - 本番: `https://mangatool.vercel.app/auth/booth/callback`
   - Railway: `https://mangatool-production.up.railway.app/auth/booth/callback`

4. **Scopes** を選択:
   - ✅ `shop:read` — ショップ情報取得
   - ✅ `item:read` — 商品取得
   - ✅ `item:write` — 商品作成・編集
   - ✅ `item:delete` — 商品削除
   - ✅ `sales:read` — 売上データ取得
   - ✅ `webhook:manage` — Webhook 設定

5. **Create** をクリック

---

## 🔐 ステップ2: API キー取得

Application 作成後、以下の情報を確認:

```
Client ID: xxxxxxxxxxxxxxxxxxxxxxxx
Client Secret: xxxxxxxxxxxxxxxxxxxxxxxx (秘密鍵 - 絶対に共有しないこと)
```

**このキーを Railway 環境変数に設定:**

```bash
# Railway CLI
railway variable add BOOTH_CLIENT_ID xxxxxxxxxxxxxxxxxxxxxxxx
railway variable add BOOTH_CLIENT_SECRET xxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 🔔 ステップ3: Webhook 設定

販売イベントをリアルタイムで受け取る:

### Webhook URL 設定

1. BOOTH Developer Console > Application > **Webhooks**
2. **Add Webhook**
3. URL: `https://mangatool-production.up.railway.app/webhook/booth`
4. Events を選択:
   - ✅ `order.completed` — 注文完了
   - ✅ `order.cancelled` — 注文キャンセル
   - ✅ `payment.succeeded` — 支払い成功

5. **Save**

### Webhook Secret

生成された Secret をメモ:

```
Webhook Secret: xxxxxxxxxxxxxxxxxxxxxxxx
```

このを環境変数に追加:

```bash
railway variable add BOOTH_WEBHOOK_SECRET xxxxxxxxxxxxxxxxxxxxxxxx
```

---

## 🧪 ステップ4: テスト

### OAuth フロー テスト

ローカルで以下をテスト:

```bash
# ローカルで起動
cd frontend && npm run dev
cd backend && python main.py
```

テスト URL: http://localhost:3000/auth/booth

### Webhook テスト

BOOTH Console の Webhook テスト機能を使用:

1. Webhooks > **Send Test**
2. Event Type: `order.completed`
3. テストデータを送信
4. Railway ログで受信確認:
   ```bash
   railway logs --follow
   ```

---

## ✅ チェックリスト

- [ ] BOOTH Developer Console アカウント作成
- [ ] Application 登録完了
- [ ] Client ID / Secret 取得
- [ ] Redirect URIs すべて登録
- [ ] Scopes 選択完了
- [ ] Webhook URL 登録
- [ ] Webhook Secret 生成
- [ ] Railway 環境変数設定
- [ ] ローカルテスト成功
- [ ] Webhook テスト成功
- [ ] 本番環境で動作確認

---

## 💡 本番デプロイ前の確認事項

### OAuth フロー

```
[お客様] → [BOOTH ログイン画面]
         → [comicCockpit アプリ認可]
         → [コールバック]
         → [Access Token 保存]
         → [出品 API 呼び出し可能]
```

### エラー対応

| エラー | 原因 | 対応 |
|--------|------|------|
| `invalid_client` | Client ID/Secret 不正 | BOOTH Console で確認 |
| `invalid_redirect_uri` | Redirect URI 未登録 | BOOTH Console に追加 |
| `permission_denied` | ユーザーが認可拒否 | リトライまたはサポート |
| `webhook_signature_invalid` | Secret が合致しない | Secret 確認 |

---

## 🔗 参考資料

- BOOTH API ドキュメント: https://booth.pm/api
- pixiv Developer: https://developer.pixiv.net/
- OAuth 2.0 リファレンス: https://tools.ietf.org/html/rfc6749

---

**すべて完了したら、本番リリース準備完了です！** 🚀
