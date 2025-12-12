# NET8 デプロイ構造完全ガイド

---

## 🚨 【重要】このファイルは編集禁止

**⚠️ このDEPLOY.mdは全Claude Code共通の参照用ドキュメントです**

### 絶対ルール
- ✅ **読み込みのみ許可**
- ❌ **編集・上書き・削除は厳禁**
- ❌ Claude Codeによる自動編集禁止

### 編集が必要な場合
1. ユーザーに報告
2. ユーザーの許可を得る
3. ユーザーが承認した場合のみ編集

**このルールを破った場合、複数Claude Code間で混乱が発生します**

---

## 📁 Git リポジトリ構造

```
/Users/kotarokashiwai/  ← Gitルート（ホームディレクトリ全体）
├── railway.toml  ← Railway設定ファイル（rootDirectory指定）
└── net8_rebirth/  ← Railwayのルートディレクトリ
    ├── Dockerfile  ← Railwayが使用するDockerfile
    └── net8/
        ├── 01.サーバ構築手順/
        │   └── net8peerjs-server/  ← PeerJSサーバー
        ├── 02.ソースファイル/
        │   └── net8_html/  ← PHPアプリケーション本体
        │       ├── data/api/  ← API
        │       ├── _html/  ← HTMLテンプレート
        │       └── _etc/  ← 設定ファイル
        ├── source/
        │   └── net8_html/  ← 02.ソースファイルのコピー（日本語パス回避用）
        └── docker/
            ├── web/
            │   ├── php.ini
            │   └── apache-config/
            │       └── 000-default.conf
            └── signaling/
                └── Dockerfile
```

## 🚀 Railway デプロイ構造

### プロジェクト: `mmg2501`

#### サービス1: `mgg-webservice` (PHPアプリ)
- **Gitリポジトリ**: `https://github.com/mgg00123mg-prog/mgg001.git`
- **Railway設定ファイル**: `/Users/kotarokashiwai/railway.toml`
  - `rootDirectory = "net8_rebirth"`
  - `dockerfilePath = "Dockerfile"`
- **ビルドコンテキスト**: `net8_rebirth` ディレクトリ
- **Dockerfile**: `/Users/kotarokashiwai/net8_rebirth/Dockerfile`
- **コピー対象**: `net8/02.ソースファイル/net8_html` → `/var/www/html`
- **URL**: `mgg-webservice-production.up.railway.app`
- **ポート**: 80 (Apache)

#### サービス2: `mgg-signaling` (PeerJS)
- **URL**: `mgg-signaling-production.up.railway.app`
- **ポート**: 443 (HTTPS)

#### サービス3: `mysql`
- MySQL 5.7

## 🗄️ データベース接続

### GCP Cloud SQL
- **ホスト**: `136.116.70.86`
- **DB名**: `net8_dev`
- **ユーザー**: `net8tech001`
- **パスワード**: `Nene11091108!!`
- **ポート**: 3306

## 📤 デプロイフロー

### 方法1: Git Push（推奨）
```bash
# ホームディレクトリで実行
cd /Users/kotarokashiwai

# 変更をコミット
git add net8_rebirth/net8/02.ソースファイル/net8_html/
git commit -m "メッセージ"
git push origin main
```

↓ GitHub Webhook

↓ Railway が自動ビルド・デプロイ

### 方法2: Railway CLI
```bash
cd /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html
railway up
```
※ ビルドコンテキストがホームディレクトリ全体なので注意

## ⚠️ 重要な注意点

### 1. Gitルートの問題
- **現状**: ホームディレクトリ全体がGitリポジトリ
- **問題**: 不要なファイルまでコミット対象になる
- **推奨**: `net8_rebirth/` だけを独立リポジトリにする

### 2. Railway設定の重要ポイント
- **railway.toml**: Gitルート（`/Users/kotarokashiwai/`）に配置
- **rootDirectory**: `net8_rebirth` を指定することで、Railwayはこのディレクトリをルートとして扱う
- **dockerfilePath**: `Dockerfile` （rootDirectoryからの相対パス）

### 3. ビルドコンテキスト
- **設定前**: ホームディレクトリ全体がビルドコンテキスト（問題あり）
- **設定後**: `net8_rebirth` ディレクトリがビルドコンテキスト（正常動作）
- **Dockerfile内のCOPYパス**: `net8/02.ソースファイル/net8_html` （net8_rebirthからの相対パス）

## 🔧 ファイル変更時の手順

### PHPファイルを変更する場合

1. **ファイル編集**
```bash
# 例: userAuthAPI.php を編集
nano /Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php
```

2. **Git コミット**
```bash
cd /Users/kotarokashiwai
git add "net8_rebirth/net8/02.ソースファイル/net8_html/data/api/userAuthAPI.php"
git commit -m "fix: userAuthAPI修正"
git push origin main
```

3. **Railway 自動デプロイ**
- GitHubへのpush後、2-5分で自動デプロイ
- ログ確認: `railway logs`

### Dockerfileを変更する場合

1. **net8_rebirth内のDockerfileを編集**
```bash
nano /Users/kotarokashiwai/net8_rebirth/Dockerfile
```

**重要**: Dockerfile内のCOPYパスは`net8/`から始める（`net8_rebirth/`は不要）
```dockerfile
# 正しい例
COPY net8/02.ソースファイル/net8_html /var/www/html
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini

# 間違った例（動作しません）
COPY net8_rebirth/net8/02.ソースファイル/net8_html /var/www/html
```

2. **コミット＆プッシュ**
```bash
cd /Users/kotarokashiwai
git add net8_rebirth/Dockerfile
git commit -m "chore: Dockerfile更新"
git push origin main
```

## 📊 現在のRailwayサービス一覧

| サービス名 | 種類 | URL | 用途 |
|-----------|------|-----|------|
| mgg-webservice | PHP/Apache | mgg-webservice-production.up.railway.app | メインアプリ |
| mgg-signaling | PeerJS | mgg-signaling-production.up.railway.app | WebRTC シグナリング |
| mysql | MySQL 5.7 | (内部) | データベース |

## 🔑 重要な設定ファイル

### railway.toml（Gitルート: /Users/kotarokashiwai/railway.toml）
```toml
[build]
builder = "DOCKERFILE"
dockerfilePath = "Dockerfile"
rootDirectory = "net8_rebirth"

[deploy]
runtime = "V2"
numReplicas = 1
sleepApplication = false
useLegacyStacker = false
restartPolicyType = "ON_FAILURE"
restartPolicyMaxRetries = 10
```

### Dockerfile内のパス設定例
```dockerfile
# PHP設定ファイル
COPY net8/docker/web/php.ini /usr/local/etc/php/conf.d/custom.ini

# Apache設定
COPY net8/docker/web/apache-config/000-default.conf /etc/apache2/sites-available/000-default.conf

# アプリケーションファイル
COPY net8/02.ソースファイル/net8_html /var/www/html
```

## 📝 デプロイ時のチェックリスト

- [ ] 変更ファイルをコミット
- [ ] `git push origin main` 実行
- [ ] Railway ダッシュボードでビルド確認
- [ ] ログでエラーチェック: `railway logs`
- [ ] 本番URLで動作確認
- [ ] Windows側で接続テスト

---

作成日: 2025-11-10
最終更新: 2025-12-12（Railway rootDirectory設定追加、デプロイ成功確認）
