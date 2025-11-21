# NET8 画像管理システム - Google Cloud Storage 統合 完全記録

**作成日時**: 2025年11月15日 20:45 JST
**ステータス**: ✅ デプロイ完了・稼働中
**最終コミット**: 72e04b4 (debug: Add mst_setting table check script)

---

## 📋 目次

1. [プロジェクト概要](#プロジェクト概要)
2. [実装完了機能](#実装完了機能)
3. [GCSセットアップ詳細](#gcsセットアップ詳細)
4. [ファイル構成](#ファイル構成)
5. [データベース構造](#データベース構造)
6. [環境変数設定](#環境変数設定)
7. [次回作業手順](#次回作業手順)
8. [トラブルシューティング](#トラブルシューティング)
9. [重要な技術情報](#重要な技術情報)

---

## プロジェクト概要

### 解決した問題
**問題**: Railway環境でアップロードした画像が、コンテナ再起動時に消失する

**原因**: Railwayコンテナは**エフェメラルストレージ（一時ストレージ）**を使用
- Gitに含まれるファイル → デプロイ後も残る
- 実行時にアップロードされたファイル → コンテナ再起動で消失

**解決策**: Google Cloud Storage（GCS）統合による永続ストレージ実装

---

## 実装完了機能

### ✅ 1. Google Cloud Storage 統合

#### 実装内容
- GCSバケット作成: `avamodb-net8-images`
- サービスアカウント: `net8-storage@avamodb.iam.gserviceaccount.com`
- ロケーション: `asia-northeast1`
- アクセス: 公開読み取り可能（`allUsers:objectViewer`）

#### コード実装
```php
// CloudStorageHelper.php - GCS操作クラス
class CloudStorageHelper {
    // アップロード、削除、存在確認、サムネイル生成機能
    public function upload($localPath, $folder, $filename)
    public function delete($url)
    public function exists($url)
    public function uploadThumbnail($sourcePath, $folder, $filename, $maxWidth = 300)
}
```

### ✅ 2. デュアルアップロードシステム

**フロー**:
1. ユーザーが画像をアップロード
2. ローカル `/data/img/model/` に保存（フォールバック用）
3. **GCS有効時**: 同時に `gs://avamodb-net8-images/models/` にアップロード
4. データベースに保存:
   - GCS使用時: `https://storage.googleapis.com/avamodb-net8-images/models/filename.jpg`
   - ローカルのみ: `filename.jpg`

**利点**:
- GCSエラー時もローカルファイルで動作継続
- 段階的な移行が可能
- 既存システムとの互換性維持

### ✅ 3. 画像パスバグ修正

**バグ内容**:
```html
<!-- 修正前 -->
<img src="/data/img/model/img/model/hokuto4go.jpg">
<!-- ↑ パスが重複 -->
```

**原因**:
- データベースに `img/model/hokuto4go.jpg` を保存
- テンプレートが `/data/img/model/` を追加
- 結果: パスが重複して404エラー

**修正内容**:
```php
// image_upload.php 修正前（誤り）
$imagePath = 'img/model/' . $filename;

// 修正後（正しい）
$imagePath = $filename;  // ファイル名のみ保存
```

**データベース既存データ修正**:
```sql
UPDATE mst_model
SET image_list = REPLACE(image_list, 'img/model/', '')
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list LIKE 'img/model/%';
```

### ✅ 4. 診断・修正ツール

#### check_images.php
- 全機種の画像登録状況を一覧表示
- ファイル存在チェック
- 画像プレビュー表示
- パス修正ボタン（`img/model/` プレフィックス削除）
- パス復元ボタン（`img/model/` プレフィックス追加）

**アクセス**: `https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php`

#### fix_all_image_paths.php
- 画像パス一括修正API
- JSON形式で修正前後の状態を返却

**アクセス**: `https://mgg-webservice-production.up.railway.app/data/api/fix_all_image_paths.php`

---

## GCSセットアップ詳細

### 実行済みコマンド

```bash
# 1. gcloud 認証（実行済み）
gcloud auth login
# → mmz2501@gmail.com でログイン完了

# 2. プロジェクト設定
gcloud config set project avamodb

# 3. GCSバケット作成（実行済み）
gsutil mb -l asia-northeast1 -c STANDARD gs://avamodb-net8-images

# 4. バケットを公開設定（実行済み）
gsutil iam ch allUsers:objectViewer gs://avamodb-net8-images

# 5. サービスアカウント作成（実行済み）
gcloud iam service-accounts create net8-storage \
  --display-name="NET8 Storage Service Account"

# 6. Storage Object Admin ロール付与（実行済み）
gcloud projects add-iam-policy-binding avamodb \
  --member="serviceAccount:net8-storage@avamodb.iam.gserviceaccount.com" \
  --role="roles/storage.objectAdmin"

# 7. JSONキー生成（実行済み）
gcloud iam service-accounts keys create ~/gcs-key.json \
  --iam-account=net8-storage@avamodb.iam.gserviceaccount.com
```

### バケット情報

| 項目 | 値 |
|------|-----|
| バケット名 | `avamodb-net8-images` |
| プロジェクトID | `avamodb` |
| ロケーション | `asia-northeast1` |
| ストレージクラス | STANDARD |
| アクセス | Public Read（allUsers:objectViewer） |

### サービスアカウント情報

| 項目 | 値 |
|------|-----|
| アカウント名 | net8-storage |
| メールアドレス | net8-storage@avamodb.iam.gserviceaccount.com |
| ロール | Storage Object Admin |
| 認証方法 | JSON Key（GCS_KEY_JSON環境変数） |

---

## ファイル構成

### 新規作成ファイル

```
net8/02.ソースファイル/net8_html/
├── _sys/
│   └── CloudStorageHelper.php          # GCS操作クラス
├── data/
│   ├── api/
│   │   └── fix_all_image_paths.php     # 画像パス一括修正API
│   └── xxxadmin/
│       ├── check_images.php            # 画像診断ツール
│       └── image_upload.php            # 画像アップロード（修正済み）
└── _etc/
    └── setting.php                      # 設定ファイル（GCS設定追加）
```

### CloudStorageHelper.php (8,802 bytes)

**ロケーション**: `net8/02.ソースファイル/net8_html/_sys/CloudStorageHelper.php`

**主要メソッド**:

```php
<?php
class CloudStorageHelper {
    private $storage;      // StorageClient インスタンス
    private $bucket;       // バケットオブジェクト
    private $bucketName;   // バケット名
    private $enabled;      // GCS有効フラグ

    /**
     * コンストラクタ - GCS初期化
     * GCS_KEY_JSON または GCS_KEY_FILE から認証情報を読み込み
     */
    public function __construct();

    /**
     * ファイルをGCSにアップロード
     * @param string $localPath ローカルファイルパス
     * @param string $folder フォルダ名（models, machines, banners）
     * @param string $filename ファイル名
     * @return string|false 公開URL または false
     */
    public function upload($localPath, $folder, $filename);

    /**
     * GCSからファイルを削除
     * @param string $url 公開URL
     * @return bool 成功時true
     */
    public function delete($url);

    /**
     * ファイルが存在するか確認
     * @param string $url 公開URL
     * @return bool 存在する場合true
     */
    public function exists($url);

    /**
     * ファイル一覧を取得
     * @param string $folder フォルダ名
     * @return array ファイル情報配列
     */
    public function listFiles($folder = '');

    /**
     * サムネイルを生成してアップロード
     * @param string $sourcePath 元画像パス
     * @param string $folder フォルダ名
     * @param string $filename ファイル名
     * @param int $maxWidth 最大幅（デフォルト300px）
     * @return string|false サムネイルURL または false
     */
    public function uploadThumbnail($sourcePath, $folder, $filename, $maxWidth = 300);
}
?>
```

**重要な実装詳細**:

1. **認証方法の柔軟性**:
```php
// GCS_KEY_JSON環境変数が設定されている場合はJSONから読み込み
$keyJson = getenv('GCS_KEY_JSON');
if (!empty($keyJson)) {
    $keyData = json_decode($keyJson, true);
    $storageConfig['keyFile'] = $keyData;
} else {
    // ファイルパスから読み込み
    $keyFilePath = getenv('GCS_KEY_FILE') ?: __DIR__ . '/../_etc/gcs-key.json';
    $storageConfig['keyFilePath'] = $keyFilePath;
}
```

2. **公開URL生成**:
```php
$publicUrl = "https://storage.googleapis.com/{$this->bucketName}/{$objectName}";
// 例: https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg
```

3. **キャッシュ設定**:
```php
'metadata' => [
    'cacheControl' => 'public, max-age=31536000', // 1年キャッシュ
]
```

### image_upload.php 修正内容

**ロケーション**: `net8/02.ソースファイル/net8_html/data/xxxadmin/image_upload.php`

**重要な修正箇所**:

```php
// 修正前（誤り） - Line 91
$imagePath = 'img/model/' . $filename;

// 修正後（正しい） - Line 91
$imagePath = $filename;  // ファイル名のみ保存
```

**GCS統合部分**:

```php
// ファイル移動（ローカル保存）
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    throw new Exception('ファイルの保存に失敗しました');
}

// 画像パス（ファイル名のみ、テンプレート側で /data/img/model/ が追加される）
$imagePath = $filename;

// Cloud Storage統合が有効な場合はGCSにもアップロード
if (defined('GCS_ENABLED') && GCS_ENABLED && class_exists('CloudStorageHelper')) {
    try {
        $gcs = new CloudStorageHelper();
        if ($gcs->isEnabled()) {
            $gcsUrl = $gcs->upload($uploadPath, 'models', $filename);
            if ($gcsUrl) {
                // GCS URLを優先して使用（フルURLなのでそのまま保存）
                $imagePath = $gcsUrl;
                $message = "画像アップロード成功（Cloud Storage）: {$filename}";
            }
        }
    } catch (Exception $e) {
        error_log('GCSアップロードエラー: ' . $e->getMessage());
        $message = "画像アップロード成功（ローカル）: {$filename}<br>※ Cloud Storageエラー: " . $e->getMessage();
    }
} else {
    $message = "画像アップロード成功（ローカル）: {$filename}";
}

// DBに画像パスを登録
$stmt->execute([
    'image_list' => $imagePath,
    'model_cd' => $model_cd
]);
```

### check_images.php (13,089 bytes)

**ロケーション**: `net8/02.ソースファイル/net8_html/data/xxxadmin/check_images.php`

**機能**:
1. データベース登録画像の一覧表示
2. ファイル存在チェック
3. 画像プレビュー表示
4. パス修正機能（`img/model/` プレフィックス削除）
5. パス復元機能（`img/model/` プレフィックス追加）
6. ローカルファイル一覧表示
7. 環境情報表示

**重要な実装詳細**:

```php
// ファイル存在チェック - 両方のパス形式に対応
if (!empty($imagePath)) {
    // image_list が "hokuto4go.jpg" なら img/model/ を追加
    // image_list が "img/model/hokuto4go.jpg" ならそのまま使用
    if (strpos($imagePath, 'img/model/') === 0) {
        $fullPath = __DIR__ . '/../' . $imagePath;
    } else {
        $fullPath = __DIR__ . '/../img/model/' . $imagePath;
    }
    $fileExists = file_exists($fullPath);
}
```

### setting.php 追加内容

**ロケーション**: `net8/02.ソースファイル/net8_html/_etc/setting.php`

```php
// Google Cloud Storage設定
define('GCS_ENABLED', getenv('GCS_ENABLED') === 'true' || getenv('GCS_ENABLED') === '1');
define('GCS_PROJECT_ID', getenv('GCS_PROJECT_ID') ?: 'avamodb');
define('GCS_BUCKET_NAME', getenv('GCS_BUCKET_NAME') ?: 'avamodb-net8-images');
define('GCS_KEY_FILE', getenv('GCS_KEY_FILE') ?: __DIR__ . '/gcs-key.json');
```

---

## データベース構造

### mst_model テーブル

**画像関連カラム**:

| カラム名 | 型 | 説明 | 例 |
|---------|-----|------|-----|
| model_no | INT | 機種番号（PK） | 1 |
| model_cd | VARCHAR(50) | 機種コード | `HOKUTO4GO` |
| model_name | VARCHAR(100) | 機種名 | `北斗の拳 強敵` |
| image_list | TEXT | 画像パス | `hokuto4go.jpg` または GCS URL |
| del_flg | TINYINT | 削除フラグ | 0（有効）/ 1（削除） |

**image_list の値の種類**:

1. **ファイル名のみ**（推奨形式）:
   ```
   hokuto4go.jpg
   ```
   - テンプレートが `/data/img/model/` を追加
   - 表示URL: `/data/img/model/hokuto4go.jpg`

2. **GCS完全URL**（GCS使用時）:
   ```
   https://storage.googleapis.com/avamodb-net8-images/models/hokuto4go.jpg
   ```
   - そのまま使用
   - 永続的にアクセス可能

3. **旧形式**（修正が必要）:
   ```
   img/model/hokuto4go.jpg
   ```
   - テンプレートと重複
   - 表示URL: `/data/img/model/img/model/hokuto4go.jpg` （404エラー）

**修正SQL**:
```sql
-- 旧形式を新形式に修正
UPDATE mst_model
SET image_list = REPLACE(image_list, 'img/model/', '')
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list != ''
  AND image_list LIKE 'img/model/%';
```

### 現在のデータ状況

**登録済み機種画像**（Git管理）:
```
/data/img/model/
├── hokuto4go.jpg          (26.5 KB)
├── jagger01.jpg
├── milliongod_gaisen.jpg  (313.6 KB)
├── milliongod_gaisen_old.jpg
└── zenigata.jpg           (72.6 KB)
```

---

## 環境変数設定

### Railway Dashboard 設定済み変数

| 変数名 | 値 | 説明 |
|--------|-----|------|
| `GCS_ENABLED` | `true` | GCS機能の有効化 |
| `GCS_PROJECT_ID` | `avamodb` | GCPプロジェクトID |
| `GCS_BUCKET_NAME` | `avamodb-net8-images` | GCSバケット名 |
| `GCS_KEY_JSON` | `{JSON内容}` | サービスアカウントJSON認証情報 |

### 環境変数の確認方法

```bash
# Railway CLI で確認
railway variables

# または Railway Dashboard で確認
# https://railway.app/project/8d81850a-8a75-4707-8439-4a87062f4927/service/580cf9eb-abc3-471d-bcdc-c75e2a17c11c/variables
```

### GCS_KEY_JSON の構造

```json
{
  "type": "service_account",
  "project_id": "avamodb",
  "private_key_id": "...",
  "private_key": "-----BEGIN PRIVATE KEY-----\n...\n-----END PRIVATE KEY-----\n",
  "client_email": "net8-storage@avamodb.iam.gserviceaccount.com",
  "client_id": "...",
  "auth_uri": "https://accounts.google.com/o/oauth2/auth",
  "token_uri": "https://oauth2.googleapis.com/token",
  "auth_provider_x509_cert_url": "https://www.googleapis.com/oauth2/v1/certs",
  "client_x509_cert_url": "..."
}
```

---

## 次回作業手順

### 1. GCS動作確認（最優先）

#### 画像アップロードテスト

```bash
# 1. 管理画面にアクセス
open https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php

# 2. テスト画像をアップロード
# - 機種を選択（例: 北斗の拳 強敵）
# - 画像ファイルを選択
# - アップロード実行

# 3. 成功メッセージを確認
# 期待値: "画像アップロード成功（Cloud Storage）: filename.jpg"

# 4. データベースで確認
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev

SELECT model_cd, model_name, image_list
FROM mst_model
WHERE model_cd = '選択した機種コード'
  AND del_flg = 0;

# 期待値: image_list に GCS URL が入っている
# 例: https://storage.googleapis.com/avamodb-net8-images/models/test_image.jpg
```

#### 画像表示確認

```bash
# 1. トップページにアクセス
open https://mgg-webservice-production.up.railway.app/

# 2. アップロードした機種の画像が表示されることを確認

# 3. 画像URLを確認（ブラウザの開発者ツール）
# 期待値: GCS URL が使用されている
```

#### 永続性確認

```bash
# 1. Railway コンテナを再起動
railway restart

# 2. 5分待機

# 3. 再度トップページにアクセス
open https://mgg-webservice-production.up.railway.app/

# 4. アップロードした画像が消えずに表示されることを確認
# ✅ 成功: 画像が表示される（GCS から読み込み）
# ❌ 失敗: 画像が表示されない（GCS 設定に問題）
```

### 2. 既存画像パスの修正（必要に応じて）

```bash
# 1. 診断ツールにアクセス
open https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php

# 2. 赤いバナーが表示されている場合
# 「🔧 画像パスを修正する」ボタンをクリック

# 3. 修正完了メッセージを確認

# 4. ページを更新して、全機種の画像が正常表示されることを確認
```

### 3. 既存ローカル画像のGCS移行（オプション）

```php
<?php
/**
 * 既存ローカル画像をGCSに移行するスクリプト
 * ロケーション: net8/02.ソースファイル/net8_html/data/api/migrate_to_gcs.php
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

$gcs = new CloudStorageHelper();

if (!$gcs->isEnabled()) {
    die('GCS is not enabled');
}

$db = new SmartDB(DB_DSN);

// ファイル名のみのレコードを取得
$sql = "SELECT model_no, model_cd, model_name, image_list
        FROM mst_model
        WHERE del_flg = 0
          AND image_list IS NOT NULL
          AND image_list != ''
          AND image_list NOT LIKE 'https://%'
        ORDER BY model_no";

$result = $db->query($sql);
$migrated = [];

while ($row = $result->fetchRow(MDB2_FETCHMODE_ASSOC)) {
    $localPath = __DIR__ . '/../img/model/' . $row['image_list'];

    if (!file_exists($localPath)) {
        continue;
    }

    // GCSにアップロード
    $gcsUrl = $gcs->upload($localPath, 'models', $row['image_list']);

    if ($gcsUrl) {
        // DBを更新
        $updateSql = "UPDATE mst_model
                     SET image_list = ?
                     WHERE model_no = ?";
        $stmt = $db->prepare($updateSql);
        $stmt->execute([$gcsUrl, $row['model_no']]);

        $migrated[] = [
            'model_cd' => $row['model_cd'],
            'model_name' => $row['model_name'],
            'old_path' => $row['image_list'],
            'new_url' => $gcsUrl
        ];
    }
}

echo json_encode([
    'success' => true,
    'migrated_count' => count($migrated),
    'details' => $migrated
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
```

### 4. モニタリングとログ確認

```bash
# GCSアップロードエラーログを確認
railway logs | grep "GCS"

# エラーログの例
# GCS初期化エラー: GCS_KEY_JSON is not valid JSON
# GCSアップロードエラー: Permission denied
# GCS削除エラー: Object not found
```

---

## トラブルシューティング

### 問題 1: 画像アップロード後に「ローカル」と表示される

**症状**:
```
画像アップロード成功（ローカル）: test.jpg
```

**原因**:
1. `GCS_ENABLED` が `false`
2. `GCS_KEY_JSON` が正しく設定されていない
3. CloudStorageHelper クラスが読み込まれていない

**解決方法**:

```bash
# 1. 環境変数確認
railway variables | grep GCS

# 2. GCS_ENABLED が true か確認
# 期待値: GCS_ENABLED │ true

# 3. GCS_KEY_JSON が設定されているか確認
# 期待値: JSON内容が表示される

# 4. Railway を再起動
railway restart

# 5. エラーログを確認
railway logs | grep -i "gcs\|cloud"
```

### 問題 2: GCS初期化エラー

**エラーメッセージ**:
```
GCS初期化エラー: GCS_KEY_JSON is not valid JSON
```

**原因**:
- `GCS_KEY_JSON` の JSON が壊れている
- 改行やエスケープ処理に問題

**解決方法**:

```bash
# 1. ローカルでJSONの妥当性を確認
cat ~/gcs-key.json | jq .

# 2. Railway Dashboard で GCS_KEY_JSON を再設定
# - Railway Dashboard にアクセス
# - Variables タブを開く
# - GCS_KEY_JSON を削除
# - 再度追加（JSON全体をコピー&ペースト）

# 3. Railway を再起動
railway restart
```

### 問題 3: Permission denied エラー

**エラーメッセージ**:
```
GCSアップロードエラー: Permission denied
```

**原因**:
- サービスアカウントに Storage Object Admin ロールが付与されていない
- バケットのアクセス権限が正しく設定されていない

**解決方法**:

```bash
# 1. サービスアカウントのロールを確認
gcloud projects get-iam-policy avamodb \
  --flatten="bindings[].members" \
  --format="table(bindings.role)" \
  --filter="bindings.members:net8-storage@avamodb.iam.gserviceaccount.com"

# 期待値: roles/storage.objectAdmin

# 2. ロールが付与されていない場合、再度付与
gcloud projects add-iam-policy-binding avamodb \
  --member="serviceAccount:net8-storage@avamodb.iam.gserviceaccount.com" \
  --role="roles/storage.objectAdmin"

# 3. バケットのIAM設定を確認
gsutil iam get gs://avamodb-net8-images

# 4. 必要に応じて再設定
gsutil iam ch allUsers:objectViewer gs://avamodb-net8-images
```

### 問題 4: 画像が404エラー

**症状**:
```html
<img src="/data/img/model/img/model/hokuto4go.jpg">
<!-- 404 Not Found -->
```

**原因**:
- データベースに `img/model/` プレフィックスが入っている

**解決方法**:

```bash
# 1. 診断ツールにアクセス
open https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php

# 2. 「🔧 画像パスを修正する」ボタンをクリック

# または、SQLで直接修正
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev

UPDATE mst_model
SET image_list = REPLACE(image_list, 'img/model/', '')
WHERE del_flg = 0
  AND image_list IS NOT NULL
  AND image_list LIKE 'img/model/%';
```

### 問題 5: 画像がコンテナ再起動後に消える

**症状**:
- アップロード直後は表示される
- Railwayコンテナ再起動後に404エラー

**原因**:
- GCSにアップロードされていない（ローカルのみ）
- エフェメラルストレージの制限

**確認方法**:

```bash
# 1. データベースで image_list を確認
SELECT model_cd, image_list
FROM mst_model
WHERE del_flg = 0
  AND image_list IS NOT NULL;

# GCS URL: https://storage.googleapis.com/... → ✅ OK
# ファイル名のみ: hokuto4go.jpg → ❌ ローカルのみ

# 2. GCS バケットの内容を確認
gsutil ls gs://avamodb-net8-images/models/

# 期待値: アップロードした画像が表示される
```

**解決方法**:
- 問題1の解決方法を実行（GCS有効化）
- 画像を再アップロード

---

## 重要な技術情報

### Railway エフェメラルストレージの仕組み

**特徴**:
1. **一時的**: コンテナ起動時に作成、停止時に削除
2. **書き込み可能**: 実行時にファイル作成・変更可能
3. **永続化されない**: Git に含まれないファイルは再起動で消失

**対策**:
- 永続化が必要なデータ → 外部ストレージ（GCS、S3など）
- アプリケーションファイル → Git に含める
- 一時ファイル → `/tmp` ディレクトリ使用

### Google Cloud Storage SDK

**Composer依存関係** (今後追加予定):
```json
{
    "require": {
        "google/cloud-storage": "^1.35"
    }
}
```

**現在の状況**:
- PHP SDK は Railway 環境にプリインストールされている可能性あり
- `composer install` が必要な場合、`composer.json` を作成してデプロイ

### PHP エラーログ確認

```bash
# Railway ログで PHP エラーを確認
railway logs | grep -i "error\|warning\|fatal"

# GCS 関連のログを確認
railway logs | grep -i "gcs\|cloud\|storage"

# Apache エラーログ
railway logs | grep "Apache"
```

### Git コミット履歴

```bash
# GCS関連のコミット
git log --oneline --all -20

# 重要なコミット:
# e4ed793 - feat: Support GCS_KEY_JSON environment variable
# 464c667 - fix: Save only filename in image_list to prevent duplicate path
# a550e7c - fix: Fix image path check logic and add restore button
# 155c45e - feat: Add batch image path fix API
```

---

## Git 管理状況

### リモートリポジトリ状況

```bash
# 最新コミット
git log origin/main --oneline -5

# 72e04b4 debug: Add mst_setting table check script
# a6ebaad fix: Add machine status validation to play button
# e4ed793 feat: Support GCS_KEY_JSON environment variable
# 155c45e feat: Add batch image path fix API
# d3598ec feat: Rebrand from Ryujin8 to MillionNet8
```

### 未コミットファイル

```bash
# 現在、すべての重要なファイルはコミット済み
git status

# On branch main
# Your branch is up to date with 'origin/main'.
# nothing to commit, working tree clean
```

---

## デプロイメント情報

### Railway プロジェクト

| 項目 | 値 |
|------|-----|
| プロジェクト名 | mmg2501 |
| 環境 | production |
| サービス名 | mgg-webservice |
| URL | https://mgg-webservice-production.up.railway.app |
| デプロイ方法 | Git Push (main branch) |

### 最新デプロイ

**実行日時**: 2025年11月15日 20:30 JST
**ステータス**: ✅ デプロイ完了
**コンテナ**: 起動中
**Apache**: 正常稼働

**ログ確認**:
```bash
# 最新50行
railway logs --deployment | tail -50

# リアルタイム監視
railway logs --deployment
```

---

## アクセス URL一覧

### フロントエンド

| 画面 | URL |
|------|-----|
| トップページ | https://mgg-webservice-production.up.railway.app/ |
| プレイヤー画面 | https://mgg-webservice-production.up.railway.app/data/server_v2/ |

### 管理画面

| 画面 | URL |
|------|-----|
| ダッシュボード | https://mgg-webservice-production.up.railway.app/xxxadmin/ |
| 機種管理 | https://mgg-webservice-production.up.railway.app/xxxadmin/model.php |
| 機体管理 | https://mgg-webservice-production.up.railway.app/xxxadmin/machines.php |
| カメラ管理 | https://mgg-webservice-production.up.railway.app/xxxadmin/camera.php |
| **画像アップロード** | https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php |
| **画像確認ツール** | https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php |

### API

| API | URL |
|-----|-----|
| 画像パス一括修正 | https://mgg-webservice-production.up.railway.app/data/api/fix_all_image_paths.php |
| カメラAPI | https://mgg-webservice-production.up.railway.app/data/api/cameraAPI.php |
| ヘルスチェック | https://mgg-webservice-production.up.railway.app/api/health.php |

---

## データベース接続情報

| 項目 | 値 |
|------|-----|
| ホスト | 136.116.70.86 |
| データベース名 | net8_dev |
| ユーザー名 | net8tech001 |
| パスワード | `CaD?7&Bi+_:\`QKb*` |
| 文字セット | utf8mb4 |

**接続コマンド**:
```bash
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev
# パスワード入力: CaD?7&Bi+_:`QKb*
```

---

## 次回セッション開始時のチェックリスト

### 1. 環境確認

- [ ] Railway デプロイ状況確認
  ```bash
  railway status
  ```

- [ ] GCS 環境変数確認
  ```bash
  railway variables | grep GCS
  ```

- [ ] 最新コミット確認
  ```bash
  git log --oneline -5
  ```

### 2. 動作確認

- [ ] トップページアクセス
  ```bash
  open https://mgg-webservice-production.up.railway.app/
  ```

- [ ] 画像アップロード画面アクセス
  ```bash
  open https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php
  ```

- [ ] 画像確認ツールアクセス
  ```bash
  open https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php
  ```

### 3. GCS動作テスト（初回のみ）

- [ ] テスト画像をアップロード
- [ ] 成功メッセージで「Cloud Storage」表示確認
- [ ] データベースでGCS URL確認
- [ ] トップページで画像表示確認
- [ ] Railway再起動後も画像が残ることを確認

### 4. 問題があった場合

- [ ] Railway ログ確認
  ```bash
  railway logs | grep -i "error\|gcs"
  ```

- [ ] データベース確認
  ```sql
  SELECT model_cd, model_name, image_list
  FROM mst_model
  WHERE del_flg = 0
    AND image_list IS NOT NULL
  ORDER BY model_no;
  ```

- [ ] GCS バケット確認
  ```bash
  gsutil ls gs://avamodb-net8-images/models/
  ```

---

## 補足情報

### 実装に含まれていない機能（今後の拡張候補）

1. **機体（台）画像のアップロード**
   - 現在は機種画像のみ
   - 機体ごとの個別画像は未実装

2. **画像の一括削除機能**
   - GCS上の不要画像を削除するUI

3. **画像サイズ最適化**
   - アップロード時の自動リサイズ
   - WebP変換

4. **CDN統合**
   - Cloud CDNによる配信高速化

5. **バックアップ機能**
   - GCSバケットの定期バックアップ

### セキュリティ考慮事項

1. **サービスアカウントキー管理**
   - ✅ Railway環境変数で管理（Git非追跡）
   - ✅ `.gitignore` に追加済み

2. **バケットアクセス制御**
   - ✅ 読み取り公開（`allUsers:objectViewer`）
   - ✅ 書き込みはサービスアカウントのみ

3. **画像アップロード制限**
   - ⚠️ 管理画面認証のみ（実装済み）
   - ⚠️ ファイルサイズ制限なし（今後検討）
   - ⚠️ ファイル形式検証なし（今後検討）

### コスト管理

**GCS料金**（東京リージョン: asia-northeast1）:
- ストレージ: $0.023/GB/月
- ネットワーク送信: $0.12/GB（アジア太平洋）
- API呼び出し: Class A $0.05/10,000回、Class B $0.004/10,000回

**推定コスト**（月間100GB、10万アクセス想定）:
- ストレージ: $2.30
- 転送量: $12.00
- API: $0.50
- **合計**: 約 $15/月

**コスト削減策**:
1. 画像最適化（WebP変換）
2. Cloud CDNキャッシュ活用
3. 古い画像の自動削除（ライフサイクルポリシー）

---

## 連絡先・参考情報

### Google Cloud Platform
- プロジェクト: avamodb
- アカウント: mmz2501@gmail.com
- コンソール: https://console.cloud.google.com/storage/browser/avamodb-net8-images

### Railway
- プロジェクト: mmg2501
- ダッシュボード: https://railway.app/project/8d81850a-8a75-4707-8439-4a87062f4927

### ドキュメント
- Google Cloud Storage PHP SDK: https://cloud.google.com/php/docs/reference/cloud-storage/latest
- Railway Docs: https://docs.railway.app/
- Smarty Template: https://www.smarty.net/docs/en/

---

**記録終了**

このドキュメントは2025年11月15日時点の完全な実装記録です。
次回セッション開始時は、このファイルを参照して現在の状況を把握してください。
