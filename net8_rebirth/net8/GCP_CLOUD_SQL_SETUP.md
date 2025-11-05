# GCP Cloud SQL MySQL 5.7 セットアップ手順

## 📋 概要
Railway のMySQL 5.7データ永続化のため、GCP Cloud SQL にMySQL 5.7インスタンスを作成します。

---

## ✅ STEP 1: GCP コンソールでCloud SQLインスタンス作成

### 1-1. Cloud SQLページを開く

以下のURLをブラウザで開いてください：

```
https://console.cloud.google.com/sql/instances?project=avamodb
```

### 1-2. インスタンス作成

1. **「インスタンスを作成」** ボタンをクリック
2. **MySQL** を選択
3. **「MySQL 5.7」** を選択（⚠️ 重要：必ず5.7を選択！）

### 1-3. インスタンス設定

#### 基本情報
- **インスタンスID**: `net8-mysql57`
- **パスワード**: `Net8SecurePass2025!` （後で変更可能）
- **データベースのバージョン**: `MySQL 5.7` （⚠️ 必ず5.7を選択！）
- **リージョン**: `us-central1` （最も安価）

#### 構成オプション（展開して設定）

**マシンの構成**:
- **マシンタイプ**: `db-f1-micro` （共有コア、0.6GB RAM）
  - 月額: 約 $7-10

**ストレージ**:
- **ストレージの種類**: `HDD`
- **ストレージ容量**: `10 GB`
- **自動ストレージ増加**: `オフ`

**接続**:
- **パブリックIP**: ✅ 有効化
- **プライベートIP**: ☐ 無効
- **承認済みネットワーク**:
  - 名前: `Railway`
  - ネットワーク: `0.0.0.0/0` （全IPから接続可能 - 後で制限可能）

**データ保護**:
- **自動バックアップ**: ☐ 無効（コスト削減）
- **高可用性**: ☐ 無効

### 1-4. 作成実行

1. **「インスタンスを作成」** ボタンをクリック
2. 作成完了まで **5〜10分** 待機

---

## ✅ STEP 2: データベースとユーザー作成

### 2-1. インスタンスへの接続確認

作成が完了したら、インスタンス詳細ページで以下を確認：

- **パブリックIPアドレス**: `xx.xx.xx.xx` （メモしてください）
- **接続名**: `avamodb:us-central1:net8-mysql57`

### 2-2. Cloud Shellでデータベース作成

GCPコンソール右上の **Cloud Shell** アイコンをクリックして、以下のコマンドを実行：

```bash
# Cloud SQLに接続
gcloud sql connect net8-mysql57 --user=root --quiet

# パスワード入力: Net8SecurePass2025!
```

接続後、以下のSQLを実行：

```sql
-- データベース作成
CREATE DATABASE net8_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ユーザー作成（Railwayからの接続用）
CREATE USER 'net8user'@'%' IDENTIFIED BY 'Net8Railway2025!';

-- 権限付与
GRANT ALL PRIVILEGES ON net8_dev.* TO 'net8user'@'%';
FLUSH PRIVILEGES;

-- 確認
SHOW DATABASES;
SELECT user, host FROM mysql.user WHERE user='net8user';

-- 接続終了
EXIT;
```

---

## ✅ STEP 3: Railway環境変数を更新

### 3-1. パブリックIPアドレスを確認

GCP Cloud SQLインスタンス詳細ページで **パブリックIPアドレス** をコピー

### 3-2. Railway環境変数を設定

Railwayダッシュボードで以下の環境変数を更新：

```bash
DB_HOST=<GCP Cloud SQLのパブリックIP>
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=net8user
DB_PASSWORD=Net8Railway2025!
```

### 3-3. Railwayを再デプロイ

環境変数を更新後、Railwayが自動で再デプロイします。

---

## ✅ STEP 4: データベースセットアップ

### 4-1. セットアップスクリプト実行

Railwayデプロイ完了後、以下のURLを順番に実行：

```
1. https://mgg-webservice-production.up.railway.app/test_db_connection.php
2. https://mgg-webservice-production.up.railway.app/setup_database.php （実行ボタンをクリック）
3. https://mgg-webservice-production.up.railway.app/data/xxxadmin/insert_sample_members.php
4. https://mgg-webservice-production.up.railway.app/insert_mac_addresses.php
5. https://mgg-webservice-production.up.railway.app/update_category.php
6. https://mgg-webservice-production.up.railway.app/insert_hokuto_model.php
7. https://mgg-webservice-production.up.railway.app/register_corner.php
8. https://mgg-webservice-production.up.railway.app/register_camera.php
```

または、自動化スクリプトを実行：

```bash
./railway_setup_mac.command
```

---

## ✅ STEP 5: 動作確認

トップページにアクセス：

```
https://mgg-webservice-production.up.railway.app/
```

✅ 500エラーが解消され、ゲーム画面が表示されれば成功！

---

## 📊 接続情報まとめ

```
ホスト: <GCP Cloud SQLのパブリックIP>
ポート: 3306
データベース: net8_dev
ユーザー: net8user
パスワード: Net8Railway2025!
rootパスワード: Net8SecurePass2025!
```

---

## 🔒 セキュリティ強化（後で実行可能）

### 承認済みネットワークを制限

現在は `0.0.0.0/0` （全IP許可）になっていますが、Railwayの固定IPのみに制限することを推奨：

1. Cloud SQL インスタンス詳細ページ
2. **「編集」** → **「接続」** → **「承認済みネットワーク」**
3. `0.0.0.0/0` を削除
4. RailwayのIPアドレスを追加（Railwayサポートに問い合わせて取得）

---

## 💰 コスト見積もり

- **db-f1-micro** + **10GB HDD** + **パブリックIP**
- 月額: 約 **$7-10 USD**
- バックアップ無効、高可用性無効で最小コスト

---

## 🆘 トラブルシューティング

### エラー: "Access denied for user"
→ ユーザー作成SQLを再実行

### エラー: "Can't connect to MySQL server"
→ パブリックIPと承認済みネットワーク設定を確認

### データが表示されない
→ STEP 4のセットアップスクリプトを再実行

---

## 📞 次のステップ

この手順書に沿って実行したら、結果を教えてください！
