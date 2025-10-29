# Windows PC 連携用 - Railway完全デプロイ情報

**最終更新**: 2025-10-29 17:50
**作成者**: Claude Code (Mac)
**対象**: Windows PC Claude Code

---

## 🎉 完了状況

### ✅ デプロイ完了したサービス

すべてのサービスが正常に稼働しています：

| サービス名 | URL | ステータス | 用途 |
|-----------|-----|-----------|------|
| **PeerJSシグナリング** | https://mgg-signaling-production-c1bd.up.railway.app/ | ✅ 稼働中 | WebRTCシグナリングサーバー |
| **MySQL 5.7** | meticulous-vitality.railway.internal:3306 | ✅ 稼働中 | データベース |
| **PHPウェブアプリ** | https://mgg-webservice-production.up.railway.app/ | ✅ 稼働中 | Net8パチンコゲーム |

---

## 📊 Railway サービス詳細

### 1. PeerJSシグナリングサーバー (mgg-signaling-production)

**概要:**
- WebRTCのシグナリング用PeerJSサーバー
- Node.js 14 Alpine
- signalingブランチ専用

**設定:**
```yaml
サービス名: mgg-signaling-production
URL: https://mgg-signaling-production-c1bd.up.railway.app/
ブランチ: signaling
Root Directory: net8_rebirth
Dockerfile: net8_rebirth/Dockerfile
ポート: 8080 (内部), 443 (外部HTTPS)
```

**動作確認:**
```bash
curl https://mgg-signaling-production-c1bd.up.railway.app/
# 期待される応答:
{
  "name": "PeerJS Server",
  "description": "A server side element to broker connections between PeerJS clients.",
  "website": "http://peerjs.com/"
}
```

---

### 2. MySQL 5.7 データベース (meticulous-vitality)

**概要:**
- Net8アプリケーション用データベース
- MySQL 5.7.44
- 5つのテーブルが存在

**接続情報 (Private Network - Railway内部通信用):**
```bash
Host: meticulous-vitality.railway.internal
Port: 3306
Database: net8_dev
User: root
Password: DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
```

**接続情報 (Public Network - ローカルPC用):**
```bash
Host: meticulous-vitality-production-f216.up.railway.app
Port: 3306
Database: net8_dev
User: root
Password: DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
```

**既存テーブル:**
- 5つのテーブルが存在（データあり）
- 文字セット: utf8mb4
- 照合順序: utf8mb4_unicode_ci

**動作確認 (Windows PCから):**
```bash
mysql -h meticulous-vitality-production-f216.up.railway.app -P 3306 -u root -pDDjTuVYnwSjaSZNemXWToHQUYLHkCjyy net8_dev
```

---

### 3. PHPウェブアプリ (mgg-webservice-production)

**概要:**
- Net8パチンコゲームのメインアプリケーション
- PHP 7.2 Apache
- mainブランチ専用

**設定:**
```yaml
サービス名: mgg-webservice-production
URL: https://mgg-webservice-production.up.railway.app/
Public URL: mgg-webservice-production.up.railway.app
Private Network: mgg-webservice.railway.internal
ブランチ: main
Root Directory: net8_rebirth
Dockerfile: net8_rebirth/Dockerfile (PHP 7.2 Apache)
ポート: 80 (内部), 443 (外部HTTPS)
```

**環境変数 (Variables):**
```bash
# データベース接続（Private Network使用）
DB_HOST=meticulous-vitality.railway.internal
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=root
DB_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy

# シグナリングサーバー
SIGNALING_HOST=mgg-signaling-production-c1bd.up.railway.app
SIGNALING_PORT=443
SIGNALING_SECURE=true
SIGNALING_KEY=peerjs
SIGNALING_PATH=/
```

**動作確認:**
```bash
# DB接続テスト
curl https://mgg-webservice-production.up.railway.app/test_db_connection.php

# 期待される結果:
✅ 接続成功！
DB_USER: root
MySQLバージョン: 5.7.44-log
```

---

## 🌳 Git ブランチ構成

### 重要な変更点

**2つのブランチで異なる目的を管理:**

```
GitHubリポジトリ: mgg00123mg-prog/mgg001

main ブランチ
├── net8_rebirth/Dockerfile           ← PHP 7.2 Apache用
├── net8_rebirth/net8/Dockerfile.web  ← ソース（mainで使用）
├── net8_rebirth/net8/02.ソースファイル/ ← PHPアプリ
└── railway.toml                      ← Railwayビルド設定

signaling ブランチ
├── net8_rebirth/Dockerfile           ← Node.js 14 Alpine用
├── net8_rebirth/net8/Dockerfile.signaling ← ソース
└── net8_rebirth/net8/01.サーバ構築手順/net8peerjs-server/ ← PeerJSサーバー
```

### ブランチとサービスの対応

| ブランチ | Railwayサービス | Dockerfile | 用途 |
|---------|----------------|-----------|------|
| **main** | mgg-webservice-production | net8_rebirth/Dockerfile (PHP) | PHPウェブアプリ |
| **signaling** | mgg-signaling-production | net8_rebirth/Dockerfile (Node.js) | PeerJSサーバー |

---

## 💻 Windows PCからの接続

### 1. データベース接続（PHPアプリ開発用）

**設定ファイル: `net8/.env.railway`**

```bash
# データベース接続（Public Network - Windows PCから接続）
DB_HOST=meticulous-vitality-production-f216.up.railway.app
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=root
DB_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy

# シグナリングサーバー
SIGNALING_HOST=mgg-signaling-production-c1bd.up.railway.app
SIGNALING_PORT=443
SIGNALING_SECURE=true
SIGNALING_KEY=peerjs
SIGNALING_PATH=/
```

**重要:** Windows PCでローカル開発する場合は、`DB_HOST`をPublic Networkのホスト名に変更してください。

---

### 2. XAMPP/MAMP等でのローカル開発

**DocumentRoot設定:**
```
DocumentRoot: /path/to/net8_rebirth/net8/02.ソースファイル/net8_html
```

**動作確認:**
```
http://localhost/test_db_connection.php
```

期待される結果:
```
✅ 接続成功！
Railway環境: No (ローカル環境)
DB接続: Success
```

---

## 🔧 重要な技術的変更点

### 1. 環境変数の読み取り方法変更

**問題:** `getenv()`がApache環境で動作しない

**解決:** `$_SERVER` → `$_ENV` → `getenv()` の順で読み取り

**変更ファイル:** `net8/02.ソースファイル/net8_html/_etc/setting.php`

```php
// 変更前
define('DB_USER', getenv('DB_USER') ?: 'net8user');

// 変更後
define('DB_USER', $_SERVER['DB_USER'] ?? $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'net8user');
```

この変更により、Railway環境変数が確実に読み込まれるようになりました。

---

### 2. Apache DocumentRoot変更

**問題:** test_db_connection.phpに404エラー

**解決:** DocumentRootを `/var/www/html/data` から `/var/www/html` に変更

**変更ファイル:** `net8/docker/web/apache-config/000-default.conf`

```apache
# 変更前
DocumentRoot /var/www/html/data

# 変更後
DocumentRoot /var/www/html
```

---

### 3. Dockerfileのビルドコンテキスト

**Root Directory:** `net8_rebirth`

**Dockerfile内のCOPYパス:**
```dockerfile
# Root Directoryからの相対パス
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY net8/02.ソースファイル/net8_html /var/www/html
```

---

## 🚀 今後の開発フロー

### PHPアプリの変更をデプロイ

```bash
# 1. mainブランチで作業
git checkout main

# 2. PHPファイルを編集
vim net8/02.ソースファイル/net8_html/...

# 3. コミット
git add .
git commit -m "feat: 新機能追加"

# 4. プッシュ（自動デプロイ）
git push origin main

# 5. Railway自動デプロイ
# → https://mgg-webservice-production.up.railway.app/ に反映
```

---

### PeerJSサーバーの変更をデプロイ

```bash
# 1. signalingブランチに切り替え
git checkout signaling

# 2. PeerJSサーバーファイルを編集
vim net8/01.サーバ構築手順/net8peerjs-server/lib/server.js

# 3. コミット
git add .
git commit -m "fix: PeerJSサーバー修正"

# 4. プッシュ（自動デプロイ）
git push origin signaling

# 5. Railway自動デプロイ
# → https://mgg-signaling-production-c1bd.up.railway.app/ に反映
```

---

## 🐛 トラブルシューティング

### 問題1: Railway環境変数が反映されない

**症状:**
- DB接続エラー
- デフォルト値が使われている

**確認:**
```bash
# Railway Dashboard
mgg-webservice-production → Variables

# 以下が設定されているか確認
DB_USER=root
DB_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
```

**解決:**
1. Variablesタブで環境変数を確認
2. 変更後、手動でRedeployをクリック
3. Deploy Logsで環境変数が読み込まれているか確認

---

### 問題2: Dockerfileビルドが失敗

**症状:**
- RailwayがNixpacksを使用
- "Building with Dockerfile"が表示されない

**確認:**
```bash
# Railway Dashboard
Settings → Build

# 確認項目
Builder: DOCKERFILE
Dockerfile Path: 自動検出
Root Directory: net8_rebirth
```

**解決:**
1. Root Directoryが`net8_rebirth`に設定されているか確認
2. ブランチが正しいか確認（main/signaling）
3. 必要に応じて手動Redeploy

---

### 問題3: DB接続エラー (Access denied)

**症状:**
```
Access denied for user 'net8user'@'...' (using password: YES)
```

**確認:**
```bash
# Railway MySQL Variables
MYSQLUSER=net8user
MYSQLPASS=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
MYSQL_DATABASE=net8_dev

# PHPアプリ Variables
DB_USER=root  ← rootユーザーを使用
DB_PASSWORD=DDjTuVYnwSjaSZNemXWToHQUYLHkCjyy
```

**解決:**
現在は`root`ユーザーで接続しています。
専用ユーザー（net8user）を使いたい場合は、MySQL側でユーザー作成が必要です。

---

### 問題4: test_db_connection.phpが404

**症状:**
```
404 Not Found
```

**確認:**
```bash
# Apache設定
DocumentRoot /var/www/html  ← /var/www/html/dataではない

# Dockerfile
COPY net8/02.ソースファイル/net8_html /var/www/html
```

**解決:**
DocumentRootが`/var/www/html`になっていることを確認。

---

## 📋 チェックリスト（Windows PCでの作業開始前）

### Railway側の確認

- [ ] mgg-webservice-production が稼働中
- [ ] mgg-signaling-production が稼働中
- [ ] MySQL meticulous-vitality が稼働中
- [ ] test_db_connection.phpにアクセスして接続成功を確認

### ローカル環境の確認

- [ ] Gitリポジトリをクローン済み
- [ ] mainブランチにチェックアウト
- [ ] net8/.env.railway ファイルを作成（上記の設定を使用）
- [ ] XAMPP/MAMPでDocumentRootを設定
- [ ] http://localhost/test_db_connection.php で接続テスト

---

## 🔗 重要なURL一覧

### Railway Dashboard
```
https://railway.app/
```

### 本番環境
```
PHPアプリ: https://mgg-webservice-production.up.railway.app/
PeerJS: https://mgg-signaling-production-c1bd.up.railway.app/
DB接続テスト: https://mgg-webservice-production.up.railway.app/test_db_connection.php
```

### GitHub
```
リポジトリ: https://github.com/mgg00123mg-prog/mgg001
mainブランチ: https://github.com/mgg00123mg-prog/mgg001/tree/main
signalingブランチ: https://github.com/mgg00123mg-prog/mgg001/tree/signaling
```

---

## 📞 次のステップ

### Windows PCでの作業

1. **ローカル開発環境セットアップ**
   - XAMPPインストール
   - net8/.env.railway作成
   - DocumentRoot設定

2. **Railway接続確認**
   - test_db_connection.phpでDB接続確認
   - PeerJSサーバー接続確認

3. **開発開始**
   - mainブランチでPHPアプリ開発
   - 変更をコミット＆プッシュ→自動デプロイ

---

## 🎓 学んだ教訓

### 1. Railway環境変数の読み取り
- `getenv()`は信頼性が低い
- `$_SERVER`と`$_ENV`を優先的に使用

### 2. Dockerfileとブランチの分離
- mainブランチ: PHPアプリ用Dockerfile
- signalingブランチ: PeerJSサーバー用Dockerfile
- 混在させない

### 3. Root Directory設定
- RailwayのRoot Directoryを正しく設定
- Dockerfile内のパスはRoot Directoryからの相対パス

### 4. 新サービス作成の重要性
- 古いサービスに設定が残る場合がある
- うまくいかない時は新サービス作成が確実

---

**作成日**: 2025-10-29 17:50
**最終確認**: すべてのサービス正常稼働中 ✅
**次回作業**: Windows PCでのローカル開発環境構築

---

## 🆘 緊急時の連絡先

このドキュメントで解決できない問題が発生した場合：

1. Railway Deploy Logsを確認
2. test_db_connection.phpでDB接続確認
3. GitHub Actionsのログ確認（もしある場合）
4. このドキュメントのトラブルシューティングセクションを参照
