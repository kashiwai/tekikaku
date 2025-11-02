# Windows PC 引き継ぎ資料（完全版）

## 📊 現在の状況（2025-11-03）

### ✅ 完了済み作業

1. **Railway デプロイ完成**
   - URL: https://mgg-webservice-production.up.railway.app/
   - 状態: 正常稼働中
   - 自動デプロイ: GitHub main ブランチにプッシュで自動デプロイ

2. **GCP Cloud SQL 接続成功**
   - ホスト: 136.116.70.86
   - データベース: net8_dev
   - ユーザー: net8tech001
   - パスワード: `CaD?7&Bi+_:\`QKb*`
   - 接続テスト: ✅ 成功（54テーブル確認済み）

3. **管理画面アクセス設定完了**
   - URL: https://mgg-webservice-production.up.railway.app/data/xxxadmin/
   - Basic認証: `admin` / `admin123`
   - ログイン: `admin` / `admin123`
   - 状態: ✅ ログイン成功確認済み

4. **データベース特殊文字対応**
   - パスワードに含まれる特殊文字（`?`, `:`, `&`, `` ` ``）の処理実装
   - `SmartDB.php`: URLデコード処理追加
   - `setting.php`: URLエンコード処理追加

5. **画像アップロード修正**
   - DB接続情報更新
   - 画像が潰れる問題修正（`object-fit: contain`）

---

## 🎯 次にやること（Windows側で実施）

### 優先度1: 初期データ登録

以下のデータを管理画面から登録する必要があります：

1. **オーナー管理** （現在0件）
   - パス: 管理画面 > オーナー管理
   - 必要項目: オーナー名、連絡先など

2. **機種管理** （現在1件のみ）
   - パス: 管理画面 > 機種管理
   - 必要項目: 機種コード、機種名、メーカー選択
   - メーカーは既に102件登録済み

3. **実機管理**
   - パス: 管理画面 > 実機管理
   - 機種登録後に実施

### 優先度2: Windows PC用設定

Windows PCには以下のプログラムが必要です：

1. **slotserver.exe** - スロットサーバー
2. **keysocket.exe** - キーソケット通信

これらのMACアドレスは既にデータベースに登録済み：
- `34-a6-ef-35-73-73` (slotserver.exe)
- `de-2e-80-43-28-b3` (keysocket.exe)

---

## 📁 プロジェクト構造

```
net8_rebirth/
├── net8/
│   ├── .env.railway                    # ローカル環境変数（GCP接続情報）
│   ├── 02.ソースファイル/
│   │   └── net8_html/                  # メインアプリケーション
│   │       ├── _etc/
│   │       │   ├── setting.php         # DB接続設定（修正済み）
│   │       │   ├── setting_base.php    # サイト基本設定
│   │       │   └── require_files_admin.php  # 管理画面用インクルード
│   │       ├── _lib/
│   │       │   └── SmartDB.php         # DB接続クラス（修正済み）
│   │       ├── data/
│   │       │   ├── xxxadmin/           # 管理画面
│   │       │   │   ├── .htaccess       # Basic認証設定
│   │       │   │   ├── login.php       # ログイン処理
│   │       │   │   ├── image_upload.php # 画像アップロード（修正済み）
│   │       │   │   ├── owner.php       # オーナー管理
│   │       │   │   ├── model.php       # 機種管理
│   │       │   │   └── machines.php    # 実機管理
│   │       │   └── img/
│   │       │       └── model/          # 機種画像保存先
│   │       ├── .htpasswd-admin         # Basic認証パスワード
│   │       └── test_db_connection.php  # DB接続テスト
│   └── docker/
│       └── web/
│           └── apache-config/
├── Dockerfile                          # Dockerイメージ設定
├── railway.toml                        # Railway設定
└── .env.railway                        # ローカルDB接続情報

```

---

## 🔧 環境変数設定

### Railway環境変数（既に設定済み）

```env
DB_HOST=136.116.70.86
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=net8tech001
DB_PASSWORD=CaD?7&Bi+_:`QKb*

SIGNALING_HOST=mgg-signaling-production-c1bd.up.railway.app
SIGNALING_PORT=443
SIGNALING_KEY=peerjs
SIGNALING_PATH=/
SIGNALING_SECURE=true
```

### ローカル環境（.env.railway）

Windows PCでローカルテストする場合は、プロジェクトルートの `net8/.env.railway` ファイルに上記と同じ内容が記載されています。

---

## 🗄️ データベース情報

### 接続情報
- **プロバイダ**: GCP Cloud SQL MySQL 5.7
- **ホスト**: 136.116.70.86:3306
- **データベース**: net8_dev
- **文字セット**: utf8mb4

### 承認済みネットワーク
- `153.243.190.3/32` - ユーザーのPC
- `208.77.246.15/32` - Railway Static IP

### 重要テーブル

| テーブル名 | 用途 | 件数 |
|-----------|------|------|
| mst_admin | 管理者アカウント | 3件 |
| mst_maker | メーカー | 102件 |
| mst_owner | オーナー | 0件 ⚠️ |
| mst_model | 機種 | 1件 ⚠️ |
| dat_machine | 実機 | 未確認 |
| mst_cameralist | MACアドレス | 2件 |

---

## 🔐 認証情報

### 管理画面ログイン

**アクセス手順:**
1. https://mgg-webservice-production.up.railway.app/data/xxxadmin/ にアクセス
2. **Basic認証**（1段階目）:
   - ユーザー名: `admin`
   - パスワード: `admin123`
3. **管理画面ログイン**（2段階目）:
   - ログインID: `admin`
   - パスワード: `admin123`

### 管理者アカウント一覧

| ログインID | 名前 | 権限 | 備考 |
|-----------|------|------|------|
| admin | 管理者 | 9 | 新規作成（推奨） |
| sradmin | SR管理者 | 9 | 既存 |
| spadmin | 管理者 | 9 | 既存 |

---

## 🐛 修正済みの問題

### 1. データベース接続タイムアウト

**問題**: `SQLSTATE[HY000] [2002] Connection timed out`

**原因**:
- PHPファイルのデフォルトDB設定が古い値（`db`, `net8user`, `net8pass`）
- パスワードの特殊文字が正しく処理されていない

**解決策**:
- `setting.php`: デフォルト値を正しい接続情報に更新
- `SmartDB.php`: DSNパース時にURLデコード追加
- `setting.php`: DSN構築時にURLエンコード追加

### 2. 管理画面で「エラーが発生しました」

**問題**: データ登録時にエラー

**原因**: adminアカウントの `add_no`, `upd_no` が未設定

**解決策**: adminアカウントの追加者番号・更新者番号を設定済み

### 3. 画像アップロードで画像が潰れる

**問題**: アップロードした画像がトリミングされる

**原因**: CSS で `object-fit: cover` を使用

**解決策**: `object-fit: contain` に変更

---

## 💻 Windows PC用コマンド

### 1. GitHubからクローン（初回のみ）

```powershell
# リポジトリをクローン
git clone https://github.com/mgg00123mg-prog/mgg001.git
cd mgg001

# ブランチ確認
git branch
# => * main が表示されればOK
```

### 2. 最新コードを取得

```powershell
# 最新コードをpull
git pull origin main
```

### 3. ローカルでPHPサーバー起動（テスト用）

```powershell
# net8_html ディレクトリに移動
cd net8/02.ソースファイル/net8_html

# PHPサーバー起動（ポート8080）
php -S localhost:8080

# ブラウザで確認
# http://localhost:8080/test_db_connection.php
```

### 4. データベース接続テスト

```powershell
# test_db_connection.php にアクセス
# ✅ 接続成功！が表示されればOK
```

### 5. 管理画面アクセステスト

```powershell
# ローカル
http://localhost:8080/data/xxxadmin/

# 本番
https://mgg-webservice-production.up.railway.app/data/xxxadmin/
```

---

## 🔍 トラブルシューティング

### Q1: データベースに接続できない

**確認項目:**
1. Windows PC のIPアドレスが GCP Cloud SQL の承認済みネットワークに追加されているか
2. `.env.railway` ファイルが正しく読み込まれているか
3. ファイアウォールでMySQL（3306）が許可されているか

**解決方法:**
```bash
# Windows PCのパブリックIPを確認
curl ifconfig.me

# GCP Cloud SQL に追加
gcloud sql instances patch net8-mysql57 \
  --authorized-networks=153.243.190.3/32,208.77.246.15/32,<Windows PC IP>/32
```

### Q2: 管理画面で403エラー

**原因**: Basic認証の設定問題

**解決方法:**
1. `.htpasswd-admin` ファイルが存在するか確認
2. `.htaccess` の `AuthUserFile` パスが正しいか確認

### Q3: 画像アップロードできない

**確認項目:**
1. `data/img/model/` ディレクトリの書き込み権限
2. PHP の `upload_max_filesize` 設定（5MB以上）

**解決方法:**
```powershell
# ディレクトリ作成
mkdir net8/02.ソースファイル/net8_html/data/img/model

# 権限確認（Windowsの場合はフォルダプロパティで確認）
```

---

## 📝 開発ワークフロー

### コード修正→デプロイの流れ

1. **ローカルで修正**
   ```powershell
   # ファイル編集
   code net8/02.ソースファイル/net8_html/data/xxxadmin/xxx.php
   ```

2. **ローカルでテスト**
   ```powershell
   php -S localhost:8080
   # http://localhost:8080 で動作確認
   ```

3. **Gitコミット＆プッシュ**
   ```powershell
   git add .
   git commit -m "fix: 修正内容"
   git push origin main
   ```

4. **Railway自動デプロイ**
   - GitHubにプッシュすると自動的にデプロイ開始
   - 約90秒でデプロイ完了
   - https://mgg-webservice-production.up.railway.app/ で確認

---

## 🎯 次のステップ（Windows側でやること）

### ステップ1: 初期データ登録

1. 管理画面にログイン
2. オーナー管理から1件以上登録
3. 機種管理から実際に使う機種を登録
4. 画像アップロードで機種画像を設定
5. 実機管理で台を登録

### ステップ2: Windows PC プログラム設定

1. `slotserver.exe` の設定確認
2. `keysocket.exe` の設定確認
3. MACアドレスが正しく登録されているか確認
4. プログラム起動テスト

### ステップ3: 動作確認

1. トップページでデータ表示確認
2. 実機との通信確認
3. PeerJS接続確認

---

## 📞 重要な連絡先・URL

| 項目 | URL/情報 |
|------|---------|
| 本番サイト | https://mgg-webservice-production.up.railway.app/ |
| 管理画面 | https://mgg-webservice-production.up.railway.app/data/xxxadmin/ |
| DB接続テスト | https://mgg-webservice-production.up.railway.app/test_db_connection.php |
| GitHubリポジトリ | https://github.com/mgg00123mg-prog/mgg001 |
| Railway管理画面 | https://railway.app/ |
| GCP Console | https://console.cloud.google.com/ |

---

## 🚨 注意事項

1. **セキュリティ**
   - `.env.railway` ファイルは絶対にGitにコミットしない
   - パスワードは慎重に扱う
   - 本番環境で不要なファイル（`setup_database.php`等）は削除推奨

2. **データベース**
   - 本番データベースへの直接操作は慎重に
   - バックアップを定期的に取る
   - テスト環境での確認を推奨

3. **デプロイ**
   - main ブランチへのプッシュで自動デプロイされる
   - 重要な変更前は必ずバックアップ
   - ローカルでテスト済みのコードのみプッシュ

---

## 📚 参考資料

### 関連ドキュメント
- `RAILWAY_COMPLETE_SETUP_GUIDE.md` - Railway完全セットアップガイド
- `DB_CONNECTION_COMPLETE.md` - DB接続完了報告
- `GCP_CLOUD_SQL_SETUP.md` - GCP Cloud SQL設定手順

### コード修正履歴
- `SmartDB.php`: URLデコード処理追加（Line 89）
- `setting.php`: URLエンコード処理追加（Line 54）、デフォルト値更新（Line 44-47）
- `image_upload.php`: DB接続情報更新、画像表示修正（Line 14-17, 147）

---

## ✅ 確認チェックリスト

Windows側で作業開始前に以下を確認してください：

- [ ] GitHubから最新コードをclone/pull済み
- [ ] `.env.railway` ファイルが存在し、DB接続情報が正しい
- [ ] ローカルPHPサーバーが起動できる
- [ ] `test_db_connection.php` でDB接続成功を確認
- [ ] 管理画面にログインできる（admin/admin123）
- [ ] Windows PC のIPアドレスを確認済み
- [ ] 必要に応じてGCP Cloud SQL にIP追加済み

---

**作成日**: 2025-11-03
**最終更新**: Mac側での作業完了時点
**次の担当**: Windows PC Claude Code

---

## 🤖 Claude Code 引き継ぎメッセージ

Windows側のClaude Codeへ：

現在、Railway + GCP Cloud SQL 環境が完全に動作している状態です。
管理画面も正常にアクセスでき、データベース接続も成功しています。

次にやるべきことは：
1. 管理画面から初期データ（オーナー、機種、実機）を登録
2. Windows PC用プログラム（slotserver.exe, keysocket.exe）の設定
3. 動作確認

上記のドキュメントを参照しながら作業を進めてください。
不明点があれば、このドキュメントのトラブルシューティングセクションを確認してください。

Good luck! 🚀
