# GCS画像システム - クイックリファレンス

最終更新: 2025年11月15日 20:45 JST

---

## 🚀 状況サマリー

| 項目 | ステータス |
|------|-----------|
| デプロイ | ✅ 完了（稼働中） |
| GCS統合 | ✅ 実装完了 |
| 環境変数 | ✅ 設定済み |
| コード | ✅ コミット済み |
| 動作確認 | ⏳ 次回実施 |

---

## 🎯 次回やること

### 1. GCS動作確認（5分）

```bash
# 画像アップロードページを開く
open https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php

# テスト画像をアップロード
# 成功メッセージに「Cloud Storage」が含まれることを確認

# トップページで画像表示確認
open https://mgg-webservice-production.up.railway.app/
```

### 2. 永続性テスト（5分）

```bash
# コンテナ再起動
railway restart

# 5分待機
sleep 300

# 画像が消えずに表示されることを確認
open https://mgg-webservice-production.up.railway.app/
```

---

## 📁 重要なファイル

| ファイル | パス | 説明 |
|---------|------|------|
| GCS操作クラス | `net8/02.ソースファイル/net8_html/_sys/CloudStorageHelper.php` | アップロード・削除機能 |
| 画像アップロード | `net8/02.ソースファイル/net8_html/data/xxxadmin/image_upload.php` | GCS統合済み |
| 診断ツール | `net8/02.ソースファイル/net8_html/data/xxxadmin/check_images.php` | パス修正機能付き |
| 設定ファイル | `net8/02.ソースファイル/net8_html/_etc/setting.php` | GCS定数定義 |
| 完全記録 | `GCS_IMAGE_SYSTEM_COMPLETE_RECORD.md` | 全詳細情報 |

---

## 🔧 よく使うコマンド

### Railway操作

```bash
# ステータス確認
railway status

# 環境変数確認
railway variables | grep GCS

# ログ確認
railway logs | grep -i "gcs\|error"

# 再起動
railway restart

# デプロイ
railway up
```

### Git操作

```bash
# 最新コミット確認
git log --oneline -5

# プッシュ
git push origin main

# ステータス確認
git status
```

### GCS操作

```bash
# バケット内容確認
gsutil ls gs://avamodb-net8-images/models/

# ファイル削除
gsutil rm gs://avamodb-net8-images/models/filename.jpg

# バケット情報確認
gsutil ls -L -b gs://avamodb-net8-images
```

### データベース確認

```bash
# 接続
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev
# パスワード: CaD?7&Bi+_:`QKb*

# 画像パス確認
SELECT model_cd, model_name, image_list
FROM mst_model
WHERE del_flg = 0
  AND image_list IS NOT NULL
ORDER BY model_no;
```

---

## 🌐 重要URL

| 用途 | URL |
|------|-----|
| トップページ | https://mgg-webservice-production.up.railway.app/ |
| 画像アップロード | https://mgg-webservice-production.up.railway.app/xxxadmin/image_upload.php |
| 画像確認ツール | https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php |
| Railway Dashboard | https://railway.app/project/8d81850a-8a75-4707-8439-4a87062f4927 |
| GCS Console | https://console.cloud.google.com/storage/browser/avamodb-net8-images |

---

## ⚙️ 環境変数（Railway設定済み）

```bash
GCS_ENABLED=true
GCS_PROJECT_ID=avamodb
GCS_BUCKET_NAME=avamodb-net8-images
GCS_KEY_JSON={JSON内容}
```

---

## 🐛 トラブルシューティング

### 画像が「ローカル」でアップロードされる

```bash
# 1. 環境変数確認
railway variables | grep GCS_ENABLED

# 2. Railwayを再起動
railway restart

# 3. ログ確認
railway logs | grep -i gcs
```

### 画像パス重複エラー（404）

```bash
# 診断ツールで修正
open https://mgg-webservice-production.up.railway.app/xxxadmin/check_images.php
# 「🔧 画像パスを修正する」ボタンをクリック
```

### コンテナ再起動後に画像が消える

```bash
# GCSにアップロードされているか確認
gsutil ls gs://avamodb-net8-images/models/

# データベースでGCS URLか確認
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev
SELECT model_cd, image_list FROM mst_model WHERE del_flg = 0;
# GCS URL (https://storage.googleapis.com/...) ならOK
# ファイル名のみ (hokuto4go.jpg) ならGCS未使用
```

---

## 💡 重要な注意点

1. **Railway エフェメラルストレージ**
   - ローカルに保存した画像 → 再起動で消失
   - GCSに保存した画像 → 永続的に保存

2. **画像パス形式**
   - ✅ 正しい: `filename.jpg` または GCS完全URL
   - ❌ 誤り: `img/model/filename.jpg`（パス重複）

3. **GCS URL形式**
   ```
   https://storage.googleapis.com/avamodb-net8-images/models/filename.jpg
   ```

4. **初回テストが重要**
   - まず小さいテスト画像でアップロード確認
   - 成功メッセージで「Cloud Storage」表示を確認
   - コンテナ再起動後も画像が残ることを確認

---

## 📞 緊急時の対応

### GCSが使えない場合の一時対応

1. 既存のGit管理画像を使用（`img/model/` ディレクトリ内）
2. 新規画像は一時的にローカル保存（再起動で消失に注意）
3. GCS問題解決後に、診断ツールで再アップロード

### データベース直接修正が必要な場合

```sql
-- 全機種の画像パスを確認
SELECT model_no, model_cd, model_name, image_list
FROM mst_model
WHERE del_flg = 0
ORDER BY model_no;

-- 特定機種の画像パスを変更
UPDATE mst_model
SET image_list = 'new_path_or_url'
WHERE model_cd = '機種コード'
  AND del_flg = 0;
```

---

## 📚 詳細情報

完全な実装詳細、トラブルシューティング、技術情報は以下を参照：
```
GCS_IMAGE_SYSTEM_COMPLETE_RECORD.md
```

---

**クイックリファレンス終了**
