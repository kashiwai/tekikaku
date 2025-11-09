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
└── net8_rebirth/
    ├── Dockerfile  ← Railwayが使用するDockerfile
    └── net8/
        ├── 01.サーバ構築手順/
        │   └── net8peerjs-server/  ← PeerJSサーバー
        ├── 02.ソースファイル/
        │   └── net8_html/  ← PHPアプリケーション本体
        │       ├── Dockerfile  ← 同じ内容（予備）
        │       ├── data/api/  ← API
        │       ├── _html/  ← HTMLテンプレート
        │       └── _etc/  ← 設定ファイル
        └── docker/
            ├── web/
            │   ├── Dockerfile
            │   ├── php.ini
            │   └── apache-config/
            └── signaling/
                └── Dockerfile
```

## 🚀 Railway デプロイ構造

### プロジェクト: `mmg2501`

#### サービス1: `mgg-webservice` (PHPアプリ)
- **ソースコード**: `/Users/kotarokashiwai` （ホームディレクトリ全体）
- **Dockerfile**: `/Users/kotarokashiwai/net8_rebirth/Dockerfile`
- **ビルドコンテキスト**: ホームディレクトリ全体
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

### 2. Dockerfileの重複
以下のDockerfileは**同じ内容**で重複しています：
- `/Users/kotarokashiwai/net8_rebirth/Dockerfile` ← Railwayが使用
- `/Users/kotarokashiwai/net8_rebirth/net8/02.ソースファイル/net8_html/Dockerfile`

### 3. ビルドコンテキスト
Railwayは `/Users/kotarokashiwai` をビルドコンテキストとして使用するため：
- `COPY net8/02.ソースファイル/net8_html /var/www/html` が正しく動く
- ホームディレクトリ内の全ファイルがビルドコンテキストに含まれる

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

1. **ルートのDockerfileを編集**（これだけでOK）
```bash
nano /Users/kotarokashiwai/net8_rebirth/Dockerfile
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

## 🎯 推奨される改善策

### 問題1: Gitリポジトリ範囲が広すぎる
**現状**: ホームディレクトリ全体
**推奨**: `net8_rebirth/` だけを独立リポジトリに

### 問題2: Dockerfile重複
**現状**: 2箇所に同じ内容
**推奨**: ルートの1つだけ残す

### 問題3: ビルドコンテキストが巨大
**現状**: ホームディレクトリ全体を送信
**推奨**: .dockerignore で除外設定

## 📝 デプロイ時のチェックリスト

- [ ] 変更ファイルをコミット
- [ ] `git push origin main` 実行
- [ ] Railway ダッシュボードでビルド確認
- [ ] ログでエラーチェック: `railway logs`
- [ ] 本番URLで動作確認
- [ ] Windows側で接続テスト

---

作成日: 2025-11-10
最終更新: 2025-11-10
