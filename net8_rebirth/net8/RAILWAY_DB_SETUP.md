# Railway MySQL 5.7 接続設定ガイド

**作成日**: 2025-10-29
**対象**: Net8パチンコゲームアプリケーション

---

## 📊 構成情報

### MySQL 5.7サービス
- **Private Network**: `meticulous-vitality.railway.internal`
- **Public Domain**: `meticulous-vitality-production-f216.up.railway.app`
- **Port**: `3306`
- **Database**: `net8_dev`
- **User**: `net8user`
- **Password**: `net8pass`

### PHPアプリサービス
- **URL**: https://dockerfileweb-production.up.railway.app
- **接続方法**: Private Network経由（Railway内部）

### PeerJSシグナリングサーバー
- **URL**: https://mgg-signaling-production-c1bd.up.railway.app

---

## 🚀 Railway PHPアプリの環境変数設定

### 手順

#### 1. RailwayダッシュボードでPHPアプリサービスを開く

https://railway.app/ にアクセスして、PHPアプリのサービスを選択

#### 2. "Variables" タブを開く

左サイドバーから **"Variables"** をクリック

#### 3. 以下の環境変数を追加

**"New Variable"** ボタンをクリックして、以下を1つずつ追加：

| Variable Name | Value |
|---------------|-------|
| `DB_HOST` | `meticulous-vitality.railway.internal` |
| `DB_PORT` | `3306` |
| `DB_NAME` | `net8_dev` |
| `DB_USER` | `net8user` |
| `DB_PASSWORD` | `net8pass` |
| `SIGNALING_HOST` | `mgg-signaling-production-c1bd.up.railway.app` |
| `SIGNALING_PORT` | `443` |
| `SIGNALING_SECURE` | `true` |

#### 4. 重要な設定

**Private Network接続を使用**：
- `DB_HOST` には必ず `meticulous-vitality.railway.internal` を使用
- **Public Domainは使わない**（Railway内部では不要）
- Private Networkの方が高速で安全

#### 5. 保存して再デプロイ

1. 環境変数を保存
2. 自動的に再デプロイが開始されます
3. デプロイログでDB接続を確認

---

## 💻 ローカルPC（Windows/Mac）での接続設定

### オプション1: .env.railway を使用（推奨）

#### 手順

1. **ファイルを確認**
   ```
   net8/.env.railway
   ```
   このファイルは既に作成済みです。

2. **PHPアプリで読み込む**

   PHPのDB接続コードで `.env.railway` を読み込むように変更：

   ```php
   <?php
   // 環境に応じて .env ファイルを切り替え
   if (getenv('RAILWAY_ENVIRONMENT')) {
       // Railway環境では環境変数から直接取得
       $db_host = getenv('DB_HOST');
   } else {
       // ローカル環境では .env.railway を読み込む
       $dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '.env.railway');
       $dotenv->load();
       $db_host = $_ENV['DB_HOST'];
   }
   ```

3. **接続テスト**

   ローカルで実行：
   ```bash
   php test_db_connection.php
   ```

### オプション2: .env を上書き（簡易版）

#### 手順

1. **バックアップ作成**
   ```bash
   cp net8/.env net8/.env.local.backup
   ```

2. **.env.railway の内容を .env にコピー**
   ```bash
   cp net8/.env.railway net8/.env
   ```

3. **アプリ起動**

   通常通りPHPアプリを起動すれば、Railway MySQLに接続されます

---

## 🔍 接続確認方法

### PHPでのテストスクリプト

`net8/test_db_connection.php` を作成：

```php
<?php
// Railway MySQL 5.7 接続テスト

// .env.railway を読み込む（Dotenvを使用している場合）
// または直接値を設定
$db_host = 'meticulous-vitality-production-f216.up.railway.app';
$db_port = 3306;
$db_name = 'net8_dev';
$db_user = 'net8user';
$db_pass = 'net8pass';

echo "🔍 Railway MySQL 5.7 接続テスト\n\n";

try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    echo "✅ 接続成功！\n\n";

    // MySQLバージョン確認
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "📊 MySQL Version: {$version}\n\n";

    // テーブル一覧取得
    echo "📋 テーブル一覧:\n";
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "  - {$table}\n";
    }

} catch (PDOException $e) {
    echo "❌ 接続失敗: " . $e->getMessage() . "\n";
    echo "\n接続情報:\n";
    echo "Host: {$db_host}\n";
    echo "Port: {$db_port}\n";
    echo "Database: {$db_name}\n";
    echo "User: {$db_user}\n";
}
?>
```

#### 実行方法

```bash
# ローカルで実行
cd /Users/kotarokashiwai/net8_rebirth/net8/
php test_db_connection.php
```

---

## 🛠️ トラブルシューティング

### 問題1: 「Connection refused」エラー

**原因**: Railway MySQLのPublic Networkingが無効

**解決策**:
1. RailwayダッシュボードでMySQL 5.7サービスを開く
2. Settings → Networking
3. **"Public Networking"** を有効化
4. 再度接続テスト

### 問題2: 「Access denied for user」エラー

**原因**: ユーザー名またはパスワードが間違っている

**解決策**:
1. RailwayダッシュボードでMySQL 5.7サービスのVariablesを確認
2. `MYSQLUSER` と `MYSQLPASSWORD` の値を再確認
3. `.env.railway` の値を更新

### 問題3: 「Unknown database 'net8_dev'」エラー

**原因**: データベースが作成されていない

**解決策**:
1. Railway MySQLに接続
2. データベースを作成：
   ```sql
   CREATE DATABASE net8_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. または、RailwayのMySQL 5.7サービスの`MYSQL_DATABASE`変数を確認

### 問題4: Railway PHPアプリから接続できない

**原因**: Private Networkingが無効、または間違ったホスト名

**解決策**:
1. MySQL 5.7サービスでPrivate Networkingが有効か確認
2. PHPアプリの環境変数で`DB_HOST`が`meticulous-vitality.railway.internal`になっているか確認
3. **Public Domainではなく、Private Network名を使用**

---

## 📝 PHP接続コードの例

### PDOを使用した接続

```php
<?php
// 環境変数から取得
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_name = getenv('DB_NAME') ?: 'net8_dev';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';

$dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    // 接続成功
    error_log("✅ Database connected successfully");

} catch (PDOException $e) {
    error_log("❌ Database connection failed: " . $e->getMessage());
    die("Database connection error");
}
?>
```

### MySQLiを使用した接続

```php
<?php
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('DB_PORT') ?: 3306;
$db_name = getenv('DB_NAME') ?: 'net8_dev';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASSWORD') ?: '';

$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($mysqli->connect_error) {
    error_log("❌ Database connection failed: " . $mysqli->connect_error);
    die("Database connection error");
}

$mysqli->set_charset("utf8mb4");
error_log("✅ Database connected successfully");
?>
```

---

## 🔐 セキュリティ注意事項

### 1. パスワード管理

- ❌ **絶対にGitにコミットしない**
- `.env.railway` を `.gitignore` に追加済み
- Railway環境変数で管理

### 2. Public Networking

- ローカルからのアクセス時のみ使用
- Railway内部（PHPアプリ → MySQL）では **Private Networking** を使用

### 3. ユーザー権限

- `net8user` には必要最小限の権限のみ付与
- ROOT権限は使用しない

---

## 📊 環境別の接続設定まとめ

| 環境 | DB_HOST | 接続方法 | 用途 |
|------|---------|---------|------|
| **Railway PHPアプリ** | `meticulous-vitality.railway.internal` | Private Network | 本番環境 |
| **ローカル Windows/Mac** | `meticulous-vitality-production-f216.up.railway.app` | Public Network | 開発・テスト |
| **ローカル Docker** | `db` | Dockerネットワーク | 完全ローカル開発 |

---

## 🚀 次のステップ

### 1. Railway PHPアプリの環境変数設定（最優先）

上記の手順に従って、RailwayダッシュボードでPHPアプリの環境変数を設定してください。

### 2. ローカルでの接続テスト

`.env.railway` を使用して、ローカルPCからRailway MySQLに接続できるか確認してください。

### 3. データベーススキーマのインポート

MySQL 5.7にテーブル構造とデータをインポート：

```bash
# SQLファイルをインポート
mysql -h meticulous-vitality-production-f216.up.railway.app \
      -P 3306 \
      -u net8user \
      -p \
      net8_dev < dump.sql
```

### 4. アプリケーションの動作確認

- Railwayデプロイ後、PHPアプリが正常にDB接続できるか確認
- エラーログをチェック

---

## 📞 サポート

問題が発生した場合：

1. Railwayのデプロイログを確認
2. PHPのエラーログを確認
3. `.env.railway` の設定値を再確認
4. Private Networkingが有効か確認

---

**作成日**: 2025-10-29
**更新日**: 2025-10-29
**ステータス**: 設定ガイド作成完了
