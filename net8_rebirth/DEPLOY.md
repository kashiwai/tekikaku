# Net8 デプロイ完全ガイド（全Claude Code共通認識）

**最終更新**: 2025-11-10
**対象**: 複数Claude Code同時開発環境

---

## 🚨 重要：このファイルを必ず読んでから作業開始

複数のClaude Codeインスタンスが同時開発する際、**ディレクトリ構造の誤解**がデプロイ事故を引き起こします。このドキュメントは全員の共通認識を保証します。

---

## 📂 プロジェクト構造（完全版）

### GitHubリポジトリ構造
```
mgg00123mg-prog/mgg001 (GitHubリポジトリルート)
│
└── net8_rebirth/ ← ★ Railway Root Directory（ここが起点！）
    │
    ├── railway.toml ← ✅ 実際に使用される設定ファイル
    ├── Dockerfile ← ✅ 実際に使用されるDockerfile
    ├── .gitignore
    ├── CLAUDE.md ← AI運用5原則
    ├── DEPLOY.md ← このファイル（必読）
    ├── DEPLOYMENT_SUMMARY.md
    │
    └── net8/ ← アプリケーションコード
        ├── README.md
        ├── 01.サーバ構築手順/
        │   └── net8peerjs-server/ ← PeerJS Signaling Server
        │
        ├── 02.ソースファイル/
        │   └── net8_html/ ← ★ PHPアプリケーション本体（本番www）
        │       ├── index.php
        │       ├── _sys/ ← システムクラス
        │       ├── _etc/ ← 設定ファイル
        │       ├── _html/ ← HTMLテンプレート
        │       ├── data/
        │       │   ├── play/ ← プレイ画面エントリー
        │       │   ├── play_v2/ ← WebRTC実装
        │       │   ├── api/ ← APIエンドポイント
        │       │   └── xxxadmin/ ← 管理画面PHP
        │       └── ...
        │
        └── docker/
            └── web/
                ├── php.ini ← PHP設定
                └── apache-config/
                    └── 000-default.conf ← Apache設定
```

### ローカル環境パス
```
/Users/kotarokashiwai/net8_rebirth/ ← ローカル作業ディレクトリ
├── railway.toml
├── Dockerfile
└── net8/
    └── 02.ソースファイル/
        └── net8_html/ ← 編集対象
```

---

## 🎯 Railway設定（確定版）

### 本番環境
- **プロジェクト名**: `mmg2501`
- **サービス名**: `mgg-webservice`
- **環境**: `production`
- **URL**: https://mgg-webservice-production.up.railway.app
- **GitHubリポジトリ**: `mgg00123mg-prog/mgg001`
- **ブランチ**: `main`

### Railway設定詳細
```json
{
  "rootDirectory": "net8_rebirth",
  "configFile": "net8_rebirth/railway.toml",
  "dockerfilePath": "net8_rebirth/Dockerfile"
}
```

### railway.toml（正しい設定）
**場所**: `/Users/kotarokashiwai/net8_rebirth/railway.toml`

```toml
[build]
builder = "DOCKERFILE"
dockerfilePath = "net8_rebirth/Dockerfile"  # ← GitHubリポジトリルートからの絶対パス

[deploy]
runtime = "V2"
numReplicas = 1
sleepApplication = false
useLegacyStacker = false
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10

[deploy.multiRegionConfig."asia-southeast1-eqsg3a"]
numReplicas = 1
```

**重要**: `dockerfilePath` は**Root Directoryからの相対パス**ではなく、**GitHubリポジトリルート（mgg001/）からの絶対パス**です。

---

## 🐳 Dockerfile解説

**場所**: `/Users/kotarokashiwai/net8_rebirth/Dockerfile`

### 重要なパス（Dockerfile内）
```dockerfile
FROM php:7.2-apache

# net8_rebirth/がカレントディレクトリなので、net8/から始まる
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# PHPアプリケーションを /var/www/html にコピー（本番www）
COPY net8/02.ソースファイル/net8_html /var/www/html

# Basic認証ファイル
COPY net8/02.ソースファイル/net8_html/.htpasswd-admin /var/www/html/.htpasswd-admin

# アップロードディレクトリ
RUN mkdir -p /var/www/html/data/uploads \
    && chmod -R 777 /var/www/html/data/uploads

EXPOSE ${PORT:-80}
CMD ["apache2-foreground"]
```

### 本番環境のwwwルート
```
/var/www/html/ ← Apache DocumentRoot（Dockerコンテナ内）
├── index.php
├── data/
│   ├── play/
│   ├── play_v2/
│   ├── api/
│   └── xxxadmin/
├── _html/
├── _sys/
└── _etc/
```

---

## 📝 gitコマンド（デプロイ手順）

### 1. 作業前の確認
```bash
cd /Users/kotarokashiwai/net8_rebirth
pwd  # /Users/kotarokashiwai/net8_rebirth を確認
git status
git branch  # main ブランチにいることを確認
```

### 2. 変更のステージング
```bash
# 特定ファイルのみ
git add net8/02.ソースファイル/net8_html/data/xxxadmin/camera.php

# ディレクトリ全体
git add net8/02.ソースファイル/net8_html/_html/ja/admin/

# 全変更（慎重に！）
git add .
```

### 3. コミット
```bash
git commit -m "fix: camera.html SmartTemplate conversion"
```

### 4. プッシュ（Railway自動デプロイ発動）
```bash
git push origin main
```

### 5. デプロイ確認
```bash
# Railway CLIでログ確認
railway logs

# ステータス確認
railway status
```

---

## ⚠️ よくある間違い（これを防ぐ！）

### ❌ 間違い1: 間違ったディレクトリでgit操作
```bash
# ❌ 悪い例
cd /Users/kotarokashiwai/net8_rebirth/net8
git push  # net8_rebirthがリポジトリルート！
```

**✅ 正しい例**:
```bash
cd /Users/kotarokashiwai/net8_rebirth
git push origin main
```

### ❌ 間違い2: Dockerfileを間違った場所に作成
```bash
# ❌ 悪い例：Claude Codeが勝手に作成しがち
/Users/kotarokashiwai/net8_rebirth/net8/Dockerfile  # 未使用！
/Users/kotarokashiwai/net8_rebirth/net8/railway.toml  # 未使用！
```

**✅ 正しい場所**:
```bash
/Users/kotarokashiwai/net8_rebirth/Dockerfile  # これのみ使用
/Users/kotarokashiwai/net8_rebirth/railway.toml  # これのみ使用
```

### ❌ 間違い3: DockerfileのCOPYパスが間違い
```dockerfile
# ❌ 悪い例（Dockerfileのビルドコンテキストを誤解）
COPY 02.ソースファイル/net8_html /var/www/html  # net8/が抜けている
```

**✅ 正しい例**:
```dockerfile
# Dockerビルドコンテキストは net8_rebirth/ なので net8/ から始める
COPY net8/02.ソースファイル/net8_html /var/www/html
```

---

## 🔐 環境変数（Railway設定）

### データベース接続
```env
DB_HOST=mysql-production-e319.up.railway.app
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=net8user
DB_PASSWORD=Net8Railway2025!
```

### MySQL（Railwayサービス）
```env
MYSQL_ROOT_PASSWORD=Ryujin8MySQL2025!
MYSQL_DATABASE=net8_dev
MYSQL_USER=net8user
MYSQL_PASSWORD=Net8Railway2025!
```

---

## 🚀 複数Claude Code作業時のルール

### 1. 作業開始時
```bash
# 必ず最新を取得
cd /Users/kotarokashiwai/net8_rebirth
git pull origin main

# DEPLOY.mdを確認
cat DEPLOY.md
```

### 2. ファイル編集時
- **編集対象**: `/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/` 配下のみ
- **触らない**: `/Users/kotarokashiwai/net8_rebirth/Dockerfile`（既に完成）
- **触らない**: `/Users/kotarokashiwai/net8_rebirth/railway.toml`（既に完成）

### 3. 新規Dockerfile作成禁止
Claude Codeが「Dockerfileを作成します」と言ったら、**必ず確認**：
```
Q: どこに作成しますか？
A: /Users/kotarokashiwai/net8_rebirth/Dockerfile は既に存在します。
   新規作成は不要です。
```

### 4. デプロイ前の最終確認
```bash
# 変更ファイルを確認
git status

# Dockerfile/railway.tomlが変更されていないか確認
git diff Dockerfile
git diff railway.toml

# 意図しない変更があれば元に戻す
git checkout Dockerfile railway.toml
```

---

## 📊 デプロイフロー図

```
ローカル開発
   ↓
ファイル編集: net8/02.ソースファイル/net8_html/
   ↓
git add .
git commit -m "..."
   ↓
git push origin main
   ↓
GitHub: mgg00123mg-prog/mgg001 (main)
   ↓
Railway検出: net8_rebirth/railway.toml
   ↓
Dockerビルド: net8_rebirth/Dockerfile
   ↓
COPY net8/02.ソースファイル/net8_html → /var/www/html
   ↓
コンテナ起動: Apache + PHP 7.2
   ↓
本番URL: https://mgg-webservice-production.up.railway.app
```

---

## 🆘 トラブルシューティング

### 問題1: Railwayがビルド失敗
**原因**: 間違ったDockerfileを参照
```bash
# 確認
cat railway.toml | grep dockerfilePath

# 正しい値: dockerfilePath = "Dockerfile"
```

### 問題2: ファイルがコピーされない
**原因**: DockerfileのCOPYパスが間違い
```dockerfile
# ❌ 悪い例
COPY 02.ソースファイル/net8_html /var/www/html

# ✅ 正しい例（net8_rebirthがカレント）
COPY net8/02.ソースファイル/net8_html /var/www/html
```

### 問題3: 他のClaude Codeと競合
```bash
# 最新を取得して競合解決
git pull origin main

# 競合ファイルを確認
git status

# 手動マージまたはリセット
git merge --abort  # マージ中止
git reset --hard origin/main  # 強制同期（注意！）
```

---

## ✅ チェックリスト（デプロイ前）

- [ ] `/Users/kotarokashiwai/net8_rebirth` でgit操作している
- [ ] `git status` で変更ファイルを確認済み
- [ ] `Dockerfile` / `railway.toml` を変更していない
- [ ] コミットメッセージが明確
- [ ] `git push origin main` 実行前に深呼吸
- [ ] Railway logsで動作確認予定

---

## 📚 関連ドキュメント

- **CLAUDE.md** - AI運用5原則・開発ルール
- **DEPLOYMENT_SUMMARY.md** - 過去のデプロイ記録
- **net8/README.md** - WebRTCシステムドキュメント
- **net8/WEBRTC_SYSTEM_DOCUMENTATION.md** - 技術詳細

---

## 🎯 まとめ（最重要）

### 覚えるべき3つのこと

1. **GitHubリポジトリ**: `mgg00123mg-prog/mgg001`
2. **Railway Root Directory**: `net8_rebirth`（ここが起点）
3. **編集対象**: `net8/02.ソースファイル/net8_html/`（これのみ）

### やってはいけない3つのこと

1. ❌ `net8/` ディレクトリに `Dockerfile` を作成
2. ❌ `net8/` ディレクトリに `railway.toml` を作成
3. ❌ Root Directory以外でgit push

---

**このドキュメントを全てのClaude Codeインスタンスに共有してください。**

**質問があれば、まずこのファイルを読み直してください。**

---

**End of Document**
