# 🌐 Google Cloud Storage 画像アップロード設定ガイド

NET8システムで機種画像をGoogle Cloud Storageに保存するための設定手順です。

## 📋 前提条件

- GCPプロジェクト: `avamodb`
- GCPアカウント: `mmz2501@gmail.com`
- Cloud Storage バケット: `avamodb-net8-images`（推奨）

## 🔧 手順1: GCP Cloud Storageバケット作成

### 1.1 Google Cloud Consoleにアクセス
https://console.cloud.google.com/storage

### 1.2 バケットを作成
```
バケット名: avamodb-net8-images
ロケーション: asia-northeast1 (東京)
ストレージクラス: Standard
アクセス制御: 公開（均一）
```

### 1.3 フォルダー構造を作成（オプション）
バケット内に以下のフォルダーを作成：
- `models/` - 機種画像
- `machines/` - 台ごとの画像（将来用）
- `banners/` - バナー画像
- `thumbnails/` - サムネイル（自動生成）

## 🔑 手順2: サービスアカウント作成

### 2.1 IAMページにアクセス
https://console.cloud.google.com/iam-admin/serviceaccounts

### 2.2 サービスアカウントを作成
```
名前: net8-storage-uploader
説明: NET8画像アップロード用サービスアカウント
ロール: Storage Object Admin
```

### 2.3 JSON キーを作成してダウンロード
1. 作成したサービスアカウントをクリック
2. 「鍵」タブ → 「鍵を追加」 → 「新しい鍵を作成」
3. 「JSON」を選択してダウンロード
4. ダウンロードしたファイル名を `gcs-key.json` にリネーム

## 📁 手順3: サービスアカウントキーの配置

### ローカル環境の場合:
```bash
cp gcs-key.json net8/02.ソースファイル/net8_html/_etc/gcs-key.json
```

### Railway環境の場合:
1. Railwayダッシュボードにアクセス
2. プロジェクトを選択
3. 「Variables」タブをクリック
4. 以下の環境変数を追加：

```bash
# Cloud Storage有効化
GCS_ENABLED=true

# GCPプロジェクトID
GCS_PROJECT_ID=avamodb

# バケット名
GCS_BUCKET_NAME=avamodb-net8-images

# サービスアカウントキー（JSON全体を1行にして貼り付け）
GCS_KEY_JSON={"type":"service_account","project_id":"avamodb",...}
```

**または**、サービスアカウントキーをRailwayにアップロード：

```bash
# Railwayダッシュボードで「Files」をクリック
# gcs-key.jsonをアップロード
# 環境変数を追加：
GCS_KEY_FILE=/app/gcs-key.json
```

## 🚀 手順4: Composerで依存関係をインストール

ローカル環境で：
```bash
cd net8_rebirth
composer install
```

Railwayでは自動的にインストールされます。

## ✅ 手順5: 動作確認

### 5.1 画像診断ツールでチェック
https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php

### 5.2 画像アップロードテスト
1. https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php にアクセス
2. 機種を選択
3. 画像ファイルをアップロード
4. 「画像アップロード成功（Cloud Storage）」と表示されればOK

### 5.3 画像表示確認
https://mgg-webservice-production.up.railway.app/

機種一覧で画像が表示されていれば成功です！

## 🔍 トラブルシューティング

### エラー: "GCSアップロードエラー"
- サービスアカウントキーが正しく配置されているか確認
- `GCS_ENABLED=true` が設定されているか確認
- サービスアカウントに「Storage Object Admin」権限があるか確認

### エラー: "Access Denied"
- バケットのアクセス権限を確認
- サービスアカウントにバケットへのアクセス権限を付与

### 画像が表示されない
- データベースの `mst_model.image_list` を確認
- GCS URLが正しく保存されているか確認
- バケットが「公開」設定になっているか確認

## 📝 環境変数一覧

| 変数名 | 必須 | デフォルト値 | 説明 |
|--------|------|--------------|------|
| GCS_ENABLED | ○ | false | Cloud Storage有効化 |
| GCS_PROJECT_ID | ○ | avamodb | GCPプロジェクトID |
| GCS_BUCKET_NAME | ○ | avamodb-net8-images | バケット名 |
| GCS_KEY_FILE | △ | _etc/gcs-key.json | キーファイルパス |
| GCS_KEY_JSON | △ | - | キーJSON（文字列） |

※ GCS_KEY_FILE または GCS_KEY_JSON のいずれか一方が必要

## 💡 ヒント

### 既存画像の移行
既存のローカル画像をCloud Storageに移行する場合：

```php
// 管理画面で実行するスクリプト
require_once('../../_etc/require_files_admin.php');
require_once('../../_sys/CloudStorageHelper.php');

$gcs = new CloudStorageHelper();
$localDir = '../img/model/';
$files = glob($localDir . '*.{jpg,jpeg,png,gif}', GLOB_BRACE);

foreach ($files as $file) {
    $filename = basename($file);
    $url = $gcs->upload($file, 'models', $filename);

    // データベース更新
    // ...
}
```

### コスト管理
- Standard Storage: $0.02/GB/月（東京リージョン）
- ネットワーク転送: 最初の1GBは無料
- 画像を適度に圧縮してコストを削減

---

設定完了後は、画像アップロード時に自動的にCloud Storageに保存され、コンテナ再起動後も画像が消えることはありません！
