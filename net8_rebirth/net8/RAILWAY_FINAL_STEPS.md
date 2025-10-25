# Railway デプロイ - 最終ステップ

## 🎯 現在の状況

✅ GitHubリポジトリへのpush完了
✅ Railway設定ファイル作成完了
✅ デプロイスクリプト準備完了
⏳ Railway APIトークン待ち

---

## 🔑 必要なもの

Railway APIトークン（個人トークン）

**取得URL**: https://railway.com/account/tokens

---

## 📋 Option 1: API経由で全自動デプロイ（推奨）

### Step 1: Railway APIトークンを取得

1. **https://railway.com/account/tokens にアクセス**

2. **"Create New Token" をクリック**

3. **トークン名を入力**
   ```
   net8-auto-deploy
   ```

4. **"Create" をクリック**

5. **生成されたトークンをコピー**

   ⚠️ **重要**: トークンは一度しか表示されません

   **正しいトークンの形式**:
   ```
   rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*...
   ```

   - 先頭が `rw_` で始まる
   - 200文字以上の長い文字列

---

### Step 2: トークンをテスト

```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
./test-railway-token.sh <コピーしたトークン>
```

**成功例**:
```
✅ 認証成功！
👤 ユーザー情報:
{
  "id": "...",
  "name": "...",
  "email": "..."
}
```

---

### Step 3: 全自動デプロイ実行

```bash
./railway-deploy.sh <コピーしたトークン>
```

このスクリプトが自動的に実行する内容：
- ✅ Railway プロジェクト作成
- ✅ MySQL データベース プロビジョニング
- ✅ PeerJS Signaling サーバー作成
- ✅ Apache/PHP Web サーバー作成
- ✅ GitHubリポジトリ連携

---

## 📋 Option 2: Railway Dashboardで手動デプロイ

APIトークン取得が困難な場合は、手動デプロイも可能です。

### 手順

**詳細ガイド**: `RAILWAY_QUICK_START.md` を参照

1. **https://railway.app/ にアクセス**

2. **"+ New Project" をクリック**

3. **"Deploy from GitHub repo" を選択**

4. **"mgg00123mg-prog/mgg001" を選択**

5. **MySQL データベースを追加**
   ```
   + New → Database → Add MySQL
   ```

6. **PeerJS Signaling サーバーを追加**
   ```
   + New → Empty Service
   Name: net8-signaling
   Settings → Deploy → Dockerfile Path: Dockerfile.signaling
   Settings → Variables:
     PORT=9000
     PEERJS_KEY=peerjs
   Settings → Networking → Generate Domain
   ```

7. **Apache/PHP Webサーバーを追加**
   ```
   + New → Empty Service
   Name: net8-web
   Settings → Deploy → Dockerfile Path: Dockerfile.web
   Settings → Variables:
     DATABASE_HOST=${{MySQL.MYSQLHOST}}
     DATABASE_PORT=${{MySQL.MYSQLPORT}}
     DATABASE_USER=${{MySQL.MYSQLUSER}}
     DATABASE_PASSWORD=${{MySQL.MYSQLPASSWORD}}
     DATABASE_NAME=${{MySQL.MYSQLDATABASE}}
     SIGNALING_HOST=${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
     SIGNALING_PORT=443
   Settings → Networking → Generate Domain
   ```

**所要時間**: 約15-20分

---

## ⚠️ よくある誤解

### ❌ これらはAPIトークンではありません

```
7df95f36-062f-4409-83f1-31bc8d58f22f  ← プロジェクトID/アカウントID
89e18cd9-ac80-4e09-bb1b-42eb24a86e5f  ← プロジェクトID/チームID
```

これらはUUID形式で、APIトークンとしては使用できません。

### ✅ 正しいAPIトークン

```
rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*ZvxYYdIfLIGhQN83LRo59g*OJ_RMGezUWjrKheHHLI4kzZWfpaDJeSvVjv1WeuQUzQ0_ZHbrZyHPb3jcdBTY0POw5cBLxw8XLdwNxoc-Onp8w*1756945667239*dda61c80e42193c37e439e42e5dabf2c092c6e4d92c4c10b031fce0907d59bb1*8xxg1hZjw6L6xVwT04krqSKuZRSKh342NUBmmJ-d8Fs
```

- 先頭が `rw_` で始まる
- 200文字以上
- 暗号化されたデータ

---

## 🚀 Railway GraphiQL Playground

APIトークン取得後、GraphiQL Playgroundで操作を確認できます：

**URL**: https://railway.com/graphiql

**使用方法**:
1. "Headers" タブをクリック
2. 以下を入力:
   ```json
   {
     "Authorization": "Bearer <APIトークン>"
   }
   ```
3. GraphQL クエリを実行可能

---

## 📊 デプロイ後の確認

### Windows側（カメラ配信）

新しいURL:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

### Mac側（視聴）

```
https://[net8-web のドメイン]/play_v2/test_simple.html
```

---

## 💰 Railway 料金

**Starter Plan**: $5/月
- 500時間の実行時間
- 初回登録で $5 分の無料クレジット

**試用**: クレジットカード登録のみで開始可能

---

## 🎯 次のアクション

### 最速の方法

1. https://railway.com/account/tokens にアクセス
2. "Create New Token" をクリック
3. 生成されたトークン（`rw_Fe26.2**...`）をコピー
4. こちらに送信

→ 全自動デプロイを実行します

### 手動デプロイ

`RAILWAY_QUICK_START.md` の手順に従って、Railway Dashboardから手動でデプロイ

---

**推奨**: API経由の全自動デプロイ（Option 1）
**所要時間**: トークン取得1分 + デプロイ5分 = 合計6分

**APIトークン取得ページ**: https://railway.com/account/tokens
