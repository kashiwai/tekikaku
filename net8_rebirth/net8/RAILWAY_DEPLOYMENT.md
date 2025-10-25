# Railway Deployment Guide - NET8 WebRTC System

## 📋 概要

ngrokの制限を解決するため、NET8システムをRailwayにデプロイします。

**GitHubリポジトリ**: https://github.com/mgg00123mg-prog/mgg001

**デプロイ完了**: ✅ GitHubへのpush完了

---

## 🚀 Railway デプロイ手順

### Step 1: Railwayアカウント作成

1. **Railway公式サイトにアクセス**
   ```
   https://railway.app/
   ```

2. **"Start a New Project" をクリック**

3. **GitHubアカウントでログイン**
   - "Login with GitHub" をクリック
   - GitHubの認証画面で "Authorize Railway" をクリック

4. **リポジトリへのアクセス許可**
   - "Configure GitHub App" をクリック
   - "mgg00123mg-prog/mgg001" リポジトリを選択
   - "Save" をクリック

---

### Step 2: プロジェクト作成

1. **"Deploy from GitHub repo" を選択**

2. **リポジトリを選択**
   - `mgg00123mg-prog/mgg001` を検索して選択

3. **"Deploy Now" をクリック**
   - Railway が自動的にリポジトリを検出

---

### Step 3: 3つのサービスをデプロイ

#### 3.1 MySQLデータベース

1. **"+ New" → "Database" → "Add MySQL" をクリック**

2. **接続情報を確認**
   ```
   MYSQL_HOST: [Railwayが自動生成]
   MYSQL_PORT: [Railwayが自動生成]
   MYSQL_USER: root
   MYSQL_PASSWORD: [Railwayが自動生成]
   MYSQL_DATABASE: [Railwayが自動生成]
   ```

3. **データベース初期化**
   - Railway の MySQL インスタンスに接続
   - `03.DBdump/` フォルダのSQLファイルをインポート

---

#### 3.2 Apache/PHP Webサーバー (net8_html)

1. **"+ New" → "Empty Service" をクリック**

2. **サービス名を設定**
   ```
   Service Name: net8-web
   ```

3. **Settings → Environment で環境変数を設定**
   ```
   DATABASE_HOST=${{MySQL.MYSQL_HOST}}
   DATABASE_PORT=${{MySQL.MYSQL_PORT}}
   DATABASE_USER=${{MySQL.MYSQL_USER}}
   DATABASE_PASSWORD=${{MySQL.MYSQL_PASSWORD}}
   DATABASE_NAME=${{MySQL.MYSQL_DATABASE}}
   SIGNALING_HOST=${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
   SIGNALING_PORT=443
   ```

4. **Settings → Deploy で設定**
   ```
   Root Directory: 02.ソースファイル/net8_html
   Dockerfile Path: (自動検出されるDockerfileを使用)
   ```

5. **公開ドメインを有効化**
   - Settings → Networking → "Generate Domain" をクリック
   - 生成されたドメインをメモ (例: `net8-web-production.up.railway.app`)

---

#### 3.3 PeerJS シグナリングサーバー

1. **"+ New" → "Empty Service" をクリック**

2. **サービス名を設定**
   ```
   Service Name: net8-signaling
   ```

3. **Settings → Environment で環境変数を設定**
   ```
   PORT=9000
   PEERJS_KEY=peerjs
   PEERJS_PATH=/peerjs
   ```

4. **Settings → Deploy で設定**
   ```
   Root Directory: 01.サーバ構築手順/net8peerjs-server
   Dockerfile Path: Dockerfile
   ```

5. **公開ドメインを有効化**
   - Settings → Networking → "Generate Domain" をクリック
   - 生成されたドメインをメモ (例: `net8-signaling-production.up.railway.app`)

---

## 🔧 環境変数設定詳細

### net8-web サービス

| 変数名 | 値 | 説明 |
|--------|-----|------|
| `DATABASE_HOST` | `${{MySQL.MYSQL_HOST}}` | MySQLホスト名（自動） |
| `DATABASE_PORT` | `${{MySQL.MYSQL_PORT}}` | MySQLポート（自動） |
| `DATABASE_USER` | `${{MySQL.MYSQL_USER}}` | MySQLユーザー（自動） |
| `DATABASE_PASSWORD` | `${{MySQL.MYSQL_PASSWORD}}` | MySQLパスワード（自動） |
| `DATABASE_NAME` | `${{MySQL.MYSQL_DATABASE}}` | データベース名（自動） |
| `SIGNALING_HOST` | `${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}` | シグナリングサーバーホスト |
| `SIGNALING_PORT` | `443` | シグナリングサーバーポート |

### net8-signaling サービス

| 変数名 | 値 | 説明 |
|--------|-----|------|
| `PORT` | `9000` | PeerJSサーバーポート |
| `PEERJS_KEY` | `peerjs` | PeerJS APIキー |
| `PEERJS_PATH` | `/peerjs` | PeerJSパス |

---

## 📊 データベース初期化

Railway MySQLにデータをインポート:

1. **ローカルからRailway MySQLに接続**
   ```bash
   mysql -h [RAILWAY_MYSQL_HOST] \
         -P [RAILWAY_MYSQL_PORT] \
         -u root \
         -p[RAILWAY_MYSQL_PASSWORD] \
         [RAILWAY_MYSQL_DATABASE]
   ```

2. **SQLファイルをインポート**
   ```bash
   mysql -h [RAILWAY_MYSQL_HOST] \
         -P [RAILWAY_MYSQL_PORT] \
         -u root \
         -p[RAILWAY_MYSQL_PASSWORD] \
         [RAILWAY_MYSQL_DATABASE] \
         < 03.DBdump/net8_database.sql
   ```

---

## 🌐 Windows側のURL更新

デプロイ完了後、Windows側のアクセスURLを更新:

**変更前（ngrok）**:
```
https://aicrypto.ngrok.dev/server_v2/?MAC=34-a6-ef-35-73-73
```

**変更後（Railway）**:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

例:
```
https://net8-web-production.up.railway.app/server_v2/?MAC=34-a6-ef-35-73-73
```

---

## ✅ デプロイ確認手順

### 1. シグナリングサーバーの確認

```bash
curl https://[net8-signaling のドメイン]/peerjs/peerjs/id
```

**期待される結果**: PeerJSサーバーのレスポンス

### 2. Webサーバーの確認

ブラウザで以下にアクセス:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

**期待される結果**:
- カメラアクセス許可ダイアログが表示
- F12コンソールで `peer._socket: EXISTS ✅` が表示
- `WebSocket State: 1` (OPEN)

### 3. Mac側からカメラ映像受信テスト

```
https://[net8-web のドメイン]/play_v2/test_simple.html
```

カメラIDを入力して接続テスト

---

## 📝 Railwayの利点

### ngrokからの移行メリット

| 項目 | ngrok (無料) | Railway |
|------|-------------|---------|
| 接続安定性 | ❌ 2時間でタイムアウト | ✅ 24/7安定稼働 |
| URL変更 | ❌ 再起動で変わる | ✅ 固定ドメイン |
| 手動再起動 | ❌ 必要 | ✅ 不要 |
| デプロイ自動化 | ❌ なし | ✅ GitHubと連携 |
| データベース | ❌ 別途用意 | ✅ 統合管理 |
| 月額料金 | $0 | $5-20 |

---

## 🎯 次のステップ

1. ✅ GitHubリポジトリにpush完了
2. ⏭️ Railwayアカウント作成
3. ⏭️ 3つのサービスをデプロイ
4. ⏭️ 環境変数設定
5. ⏭️ データベース初期化
6. ⏭️ Windows側URL更新
7. ⏭️ 動作確認テスト

---

## 💰 Railway 料金プラン

**Starter Plan (推奨)**:
- $5/月
- 500時間の実行時間
- 複数サービス可能

**Developer Plan**:
- $20/月
- 無制限実行時間

---

## 🆘 トラブルシューティング

### デプロイが失敗する場合

1. **Dockerファイルの確認**
   - `docker-compose.yml` が正しく配置されているか
   - 各サービスの Dockerfile が存在するか

2. **環境変数の確認**
   - 全ての必要な環境変数が設定されているか
   - `${{サービス名.変数名}}` の参照が正しいか

3. **ログの確認**
   - Railway Dashboard → サービス選択 → "View Logs"
   - エラーメッセージを確認

---

**作成日**: 2025-10-25
**リポジトリ**: https://github.com/mgg00123mg-prog/mgg001
**次のアクション**: Railwayアカウント作成 → https://railway.app/
