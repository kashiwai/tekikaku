# Railway GCS環境変数設定ガイド

**作成日**: 2025-11-20
**目的**: Google Cloud Storage統合を有効化

---

## 🚨 重要: 必須の環境変数設定

RailwayダッシュボードでこれらのGCS環境変数を設定する必要があります。

### Railway Dashboard → mgg-webservice-production → Variables

以下の環境変数を追加してください：

```bash
# GCS有効化フラグ
GCS_ENABLED=true

# GCSプロジェクトID
GCS_PROJECT_ID=avamodb

# GCSバケット名
GCS_BUCKET_NAME=avamodb-net8-images

# GCSサービスアカウントキー（JSON形式）
GCS_KEY_JSON={"type":"service_account","project_id":"avamodb",...}
```

---

## 📋 詳細手順

### 1. Railway Dashboardにアクセス

```
https://railway.app/
```

### 2. プロジェクト選択

- **Service**: `mgg-webservice-production`（PHPアプリ）

### 3. 環境変数設定

左サイドバー → **Variables** をクリック

### 4. 各環境変数を追加

#### 変数1: GCS_ENABLED
```
Variable Name: GCS_ENABLED
Value: true
```

#### 変数2: GCS_PROJECT_ID
```
Variable Name: GCS_PROJECT_ID
Value: avamodb
```

#### 変数3: GCS_BUCKET_NAME
```
Variable Name: GCS_BUCKET_NAME
Value: avamodb-net8-images
```

#### 変数4: GCS_KEY_JSON ⚠️ 最重要

**この値を取得する方法**:

##### オプションA: ローカルPCから取得（推奨）

```bash
# ローカルPCのgcs-key.jsonを読み込む
cat ~/gcs-key.json
```

出力されたJSONをコピーして、Railway環境変数に設定します。

##### オプションB: GCPから新しいキーを生成

```bash
# Google Cloud認証
gcloud auth login

# プロジェクト設定
gcloud config set project avamodb

# 新しいJSONキーを生成
gcloud iam service-accounts keys create ~/gcs-key-railway.json \
  --iam-account=net8-storage@avamodb.iam.gserviceaccount.com

# JSONキーの内容を表示
cat ~/gcs-key-railway.json
```

**Railway環境変数に設定**:
```
Variable Name: GCS_KEY_JSON
Value: {"type":"service_account","project_id":"avamodb","private_key_id":"...","private_key":"-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n","client_email":"net8-storage@avamodb.iam.gserviceaccount.com",...}
```

⚠️ **注意**: JSON全体を1行にして貼り付けてください（改行なし）

---

## ✅ 設定確認

### 1. デプロイ完了を待つ（3-5分）

Dockerfileの変更がデプロイされるまで待ちます。

### 2. 診断スクリプトで確認

```bash
# GCS設定を確認
curl -s "https://mgg-webservice-production.up.railway.app/data/api/diagnose_gcs.php" | jq
```

**期待される結果**:
```json
{
  "env_vars": {
    "GCS_ENABLED": "true",
    "GCS_PROJECT_ID": "avamodb",
    "GCS_BUCKET_NAME": "avamodb-net8-images",
    "GCS_KEY_JSON": "✅ SET (2xxx chars)"
  },
  "gcs_init": {
    "success": "✅ Class instantiated",
    "is_enabled": "✅ ENABLED"
  }
}
```

### 3. 画像アップロードテスト

```bash
# 管理画面で画像をアップロード
open https://mgg-webservice-production.up.railway.app/data/xxxadmin/image_upload.php

# 成功メッセージに「Cloud Storage」が含まれることを確認
✅ 画像アップロード成功（Cloud Storage）: hokuto4go.jpg
```

### 4. GCSバケット確認

```bash
# GCSバケットに画像が存在することを確認
gsutil ls gs://avamodb-net8-images/models/

# 期待される結果:
gs://avamodb-net8-images/models/hokuto4go.jpg
gs://avamodb-net8-images/models/milliongod.jpg
...
```

---

## 🧪 トラブルシューティング

### エラー: "Class 'Google\Cloud\Storage\StorageClient' not found"

**原因**: Composerパッケージが未インストール

**解決策**: Dockerfileの修正がデプロイされるまで待つ（本コミットで解決）

---

### エラー: "GCS_KEY_JSON is not valid JSON"

**原因**: JSON形式が正しくない

**解決策**:
1. JSONをフォーマットツールで検証: https://jsonlint.com/
2. 改行を削除して1行にする
3. ダブルクォートをエスケープしない（Railwayが自動処理）

---

### エラー: "GCS初期化エラー"

**原因**: サービスアカウント権限不足

**解決策**:
```bash
# Storage Object Admin ロールを付与
gcloud projects add-iam-policy-binding avamodb \
  --member="serviceAccount:net8-storage@avamodb.iam.gserviceaccount.com" \
  --role="roles/storage.objectAdmin"
```

---

### GCS_ENABLEDがfalseのまま

**原因**: 環境変数が設定されていない、または値が "true" 以外

**解決策**:
```bash
# Railway環境変数を確認
GCS_ENABLED=true  # 小文字のtrueであることを確認
```

---

## 📊 現在のGCS状態

### バケット情報
```
バケット名: avamodb-net8-images
プロジェクトID: avamodb
ロケーション: asia-northeast1
ストレージクラス: STANDARD
アクセス: Public Read（allUsers:objectViewer）
```

### サービスアカウント
```
アカウント名: net8-storage
メールアドレス: net8-storage@avamodb.iam.gserviceaccount.com
ロール: Storage Object Admin
```

### バケット内構造
```
gs://avamodb-net8-images/
├── models/          # 機種画像（mst_model.image_list）
├── banners/         # バナー画像（将来用）
└── machines/        # マシン画像（将来用）
```

---

## 🎯 期待される動作

### 1. 画像アップロード時

```
ユーザーが画像をアップロード
  ↓
ローカル (/data/img/model/) に保存（フォールバック）
  ↓
GCS (gs://avamodb-net8-images/models/) に保存
  ↓
データベース (mst_model.image_list) に GCS URL を保存
  例: https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg
```

### 2. 画像表示時

```
テンプレートが image_list を読み込む
  ↓
GCS URLが設定されている場合:
  → 直接GCSから表示（https://storage.googleapis.com/...）
  ↓
ファイル名のみの場合（旧形式）:
  → ローカルパスから表示（/data/img/model/hokuto4go.jpg）
```

### 3. コンテナ再起動時

```
Railwayがコンテナを再起動
  ↓
ローカルファイル (/data/img/model/) は消失
  ↓
GCS上の画像は永続的に保存されている
  ↓
✅ 画像表示は正常に継続
```

---

## 📚 関連ドキュメント

- **完全記録**: `GCS_IMAGE_SYSTEM_COMPLETE_RECORD.md`
- **クイックリファレンス**: `GCS_QUICK_REFERENCE.md`
- **GCP設定**: `GCP_SETUP.md`

---

## 🔐 セキュリティ注意事項

⚠️ **GCS_KEY_JSON は機密情報です**

- Gitにコミットしない
- .envファイルに保存しない
- Railway環境変数でのみ管理
- 定期的にキーをローテーション

---

**最終更新**: 2025-11-20
**作成者**: Claude Code AI
**ステータス**: 📋 設定待ち
