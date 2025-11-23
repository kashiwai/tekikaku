# GCS完全統合セットアップガイド

**作成日**: 2025-11-23
**目的**: 画像表示問題の完全根本解決
**対象**: Railway本番環境

---

## 📋 問題の根本原因

### 発見した事実
1. **Railway環境変数に `GCS_ENABLED` が未設定**
2. そのため、GCS統合が動作せず、ファイル名のみがDBに保存された
3. `DIR_IMG_MODEL_DIR = ''` + ファイル名 = 不正なパス
4. 結果: 画像が表示されない

### 解決策
- Railway環境変数を正しく設定
- 既存画像をGCSにマイグレーション
- 今後の新規アップロードは自動的にGCS完全URLで保存

---

## 🚀 セットアップ手順（必須作業）

### ステップ1: Railway環境変数設定

**Railway Dashboard にアクセス:**
1. https://railway.app/ にログイン
2. `mgg-webservice-production` プロジェクトを選択
3. "Variables" タブをクリック

**以下の環境変数を追加:**

#### 1. GCS_ENABLED
```
変数名: GCS_ENABLED
値: true
```

#### 2. GCS_PROJECT_ID
```
変数名: GCS_PROJECT_ID
値: avamodb
```

#### 3. GCS_BUCKET_NAME
```
変数名: GCS_BUCKET_NAME
値: avamodb-net8-images
```

#### 4. GCS_KEY_JSON
```
変数名: GCS_KEY_JSON
値: 以下のJSON文字列（改行なし、1行で）
```

**⚠️ 重要: GCS_KEY_JSON の値**

`~/gcs-key.json` ファイルの内容を**改行を削除して1行**にしたものを設定してください。

**取得方法:**
```bash
# ローカルマシンで実行
cat ~/gcs-key.json | tr -d '\n'
```

**JSON構造（参考）:**
```json
{"type":"service_account","project_id":"avamodb","private_key_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n","client_email":"net8-storage@avamodb.iam.gserviceaccount.com","client_id":"...","auth_uri":"https://accounts.google.com/o/oauth2/auth","token_uri":"https://oauth2.googleapis.com/token","auth_provider_x509_cert_url":"https://www.googleapis.com/oauth2/v1/certs","client_x509_cert_url":"..."}
```

**注意事項:**
- 改行を**必ず削除**すること
- JSON全体を1行にすること
- ダブルクォートやバックスラッシュはそのまま

---

### ステップ2: Railway再デプロイ

環境変数設定後、Railwayが自動的に再デプロイを開始します。

**確認方法:**
1. "Deployments" タブで進行状況を確認
2. 完了まで約3-5分待機

---

### ステップ3: GCSマイグレーション実行

**デプロイ完了後、以下のURLにアクセス:**

```
https://mgg-webservice-production.up.railway.app/data/xxxadmin/migrate_images_to_gcs.php
```

**画面の指示に従って:**
1. 実行前確認事項を読む
2. 「実行する」ボタンをクリック
3. 処理完了まで待機（約30秒〜1分）

**期待される結果:**
```
✓ GCS_ENABLED: true
✓ CloudStorageHelper初期化成功
📁 ローカル画像ディレクトリ: /app/data/img/model
📷 検出された画像: 6ファイル

処理中: hokuto4go.jpg
  ✓ GCSアップロード成功
  URL: https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg
  ✓ 機種発見: CR北斗の拳4 (CD: hokuto4go)
  ✓ DB更新完了

... (全画像で同様の処理)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 処理結果サマリー
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
成功: 6件
エラー: 0件
```

---

### ステップ4: 動作確認

#### 4-1. トップページ確認
```
https://mgg-webservice-production.up.railway.app/data/
```

**確認ポイント:**
- 機種画像が全て表示されること
- ブラウザDevToolsでエラーがないこと

#### 4-2. SDK API確認
```
https://mgg-webservice-production.up.railway.app/api/v1/models.php
```

**期待されるレスポンス:**
```json
{
  "success": true,
  "count": 6,
  "models": [
    {
      "id": "hokuto4go",
      "name": "CR北斗の拳4",
      "thumbnail": "https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg",
      ...
    }
  ]
}
```

#### 4-3. 画像URL直接アクセス
```
https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg
```
→ 画像が表示されること

---

## ✅ 成功の確認

### チェックリスト
- [ ] Railway環境変数 4つ設定完了
- [ ] 再デプロイ完了
- [ ] マイグレーションスクリプト実行完了（成功: 6件）
- [ ] トップページで全画像表示
- [ ] SDK APIで完全URL返却
- [ ] GCS URL直接アクセス可能

**全てチェックが付いたら完了です。**

---

## 🔄 今後の運用

### 新規画像アップロード

**管理画面から:**
```
https://mgg-webservice-production.up.railway.app/data/xxxadmin/image_upload.php
```

**自動処理:**
1. ローカルに一時保存
2. GCSに自動アップロード
3. DBに完全URL自動保存

**もう画像消失の問題は発生しません。**

---

## 🐛 トラブルシューティング

### エラー: GCS_ENABLED が false

**原因:**
- Railway環境変数が正しく設定されていない

**解決策:**
1. Railway Dashboard → Variables
2. `GCS_ENABLED` = `true` を確認
3. 再デプロイ

### エラー: CloudStorageHelper初期化失敗

**原因:**
- `GCS_KEY_JSON` の形式が不正

**解決策:**
1. `cat ~/gcs-key.json | tr -d '\n'` で改行削除
2. 出力をコピーして環境変数に再設定
3. 再デプロイ

### 画像が404エラー

**原因:**
- GCSにファイルが存在しない

**解決策:**
1. マイグレーションスクリプトを再実行
2. または個別に画像アップロード画面から再アップロード

---

## 📊 システム構成（完成形）

```
┌─────────────────┐
│  ユーザー        │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│ Railway         │
│ (Webサーバー)    │
│                 │
│ - PHP処理       │
│ - DB接続        │
│ - テンプレート   │──┐
└─────────────────┘  │
         │            │
         │            │ 画像参照
         │            │
         ▼            ▼
┌─────────────────┐  ┌──────────────────┐
│ MySQL           │  │ Google Cloud     │
│                 │  │ Storage (GCS)    │
│ image_list:     │  │                  │
│ "https://..."   │  │ Bucket:          │
│ (完全URL)       │  │ avamodb-net8-    │
└─────────────────┘  │ images           │
                     │                  │
                     │ /models/         │
                     │  - hokuto4go.jpg │
                     │  - zenigata.jpg  │
                     │  - ...           │
                     └──────────────────┘
```

---

## 🎯 根本解決の証明

### Before（問題）
```
DB: image_list = "hokuto4go.jpg"
テンプレート: {%DIR_IMG_MODEL_DIR%}{%IMAGE_LIST%}
実際の出力: "" + "hokuto4go.jpg" = "hokuto4go.jpg"
結果: ❌ 404エラー
```

### After（解決）
```
DB: image_list = "https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg"
テンプレート: {%DIR_IMG_MODEL_DIR%}{%IMAGE_LIST%}
実際の出力: "" + "https://..." = "https://storage.googleapis.com/..."
結果: ✅ 画像表示
```

**GCS完全URL化により、30回以上繰り返した問題が完全に解決されます。**

---

## 📝 完了報告

セットアップ完了後、以下を確認してください：

1. ✅ トップページで全画像表示
2. ✅ ブラウザDevToolsでエラーなし
3. ✅ SDK APIで完全URL返却
4. ✅ 新規アップロードでGCS URL自動保存

**全て確認できたら、この問題は完全に解決されました。**

---

**作成者**: Claude Code
**最終更新**: 2025-11-23
