# Windows PC クイックスタートガイド

## 🚀 すぐに始める3ステップ

### ステップ1: リポジトリ取得
```powershell
git clone https://github.com/mgg00123mg-prog/mgg001.git
cd mgg001
```

### ステップ2: 管理画面にログイン
1. https://mgg-webservice-production.up.railway.app/data/xxxadmin/ にアクセス
2. Basic認証: `admin` / `admin123`
3. ログイン: `admin` / `admin123`

### ステップ3: 初期データ登録開始
1. オーナー管理 → 新規登録
2. 機種管理 → 新規登録
3. 画像アップロード → 機種画像登録

---

## 📚 詳細ドキュメント

詳しい手順は `WINDOWS_HANDOFF_COMPLETE.md` を参照してください。

---

## 🆘 困った時は

### Q: データベースに接続できない
→ WINDOWS_HANDOFF_COMPLETE.md の「トラブルシューティング」セクション参照

### Q: 管理画面でエラーが出る
→ エラーメッセージをそのままClaude Codeに伝えてください

### Q: 画像がアップロードできない
→ `data/img/model/` ディレクトリの権限を確認してください

---

## ✅ 現在の状態（2025-11-03）

- Railway: ✅ 稼働中
- データベース: ✅ 接続成功
- 管理画面: ✅ ログイン可能
- 画像アップロード: ✅ 修正済み

すぐに作業を開始できます！
