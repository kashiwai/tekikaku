# Railway APIトークン取得ガイド

## 🔑 APIトークンの取得方法

Railway CLIまたはAPIを使用するには、有効なAPIトークンが必要です。

---

## Step 1: Railway Dashboardにアクセス

```
https://railway.app/
```

GitHubアカウントでログイン

---

## Step 2: APIトークンを作成

1. **右上のアカウントアイコンをクリック**

2. **"Account Settings" を選択**

3. **左メニューから "Tokens" を選択**

4. **"Create New Token" をクリック**

5. **トークン名を入力**
   ```
   例: net8-deployment
   ```

6. **"Create" をクリック**

7. **生成されたトークンをコピー**
   - ⚠️ **重要**: トークンは一度しか表示されません
   - 形式: `rw_Fe26.2**...` のような長い文字列

---

## Step 3: APIトークンを環境変数に設定

### Mac/Linuxの場合

```bash
export RAILWAY_TOKEN="rw_Fe26.2**..."
```

### または、Railway CLIでログイン

```bash
railway login
```

ブラウザが開き、自動的に認証されます。

---

## 🚀 代替方法: Railway Dashboardで手動デプロイ

APIトークンなしでも、Railway Dashboardから手動でデプロイ可能です。

### 手順

1. **https://railway.app/ にアクセス**

2. **"+ New Project" をクリック**

3. **"Deploy from GitHub repo" を選択**

4. **"mgg00123mg-prog/mgg001" を検索して選択**

5. **以下の3つのサービスを追加**:

   #### A. MySQL データベース
   ```
   Type: Database
   Database: MySQL
   ```

   #### B. PeerJS Signaling サーバー
   ```
   Type: Empty Service
   Name: net8-signaling
   Dockerfile Path: Dockerfile.signaling
   Environment Variables:
     PORT=9000
     PEERJS_KEY=peerjs
   ```

   #### C. Apache/PHP Webサーバー
   ```
   Type: Empty Service
   Name: net8-web
   Dockerfile Path: Dockerfile.web
   Environment Variables:
     DATABASE_HOST=${{MySQL.MYSQLHOST}}
     DATABASE_PORT=${{MySQL.MYSQLPORT}}
     DATABASE_USER=${{MySQL.MYSQLUSER}}
     DATABASE_PASSWORD=${{MySQL.MYSQLPASSWORD}}
     DATABASE_NAME=${{MySQL.MYSQLDATABASE}}
     SIGNALING_HOST=${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
     SIGNALING_PORT=443
   ```

6. **各サービスでドメインを生成**
   - Settings → Networking → "Generate Domain"

---

## ✅ 確認事項

### 提供されたID: `7df95f36-062f-4409-83f1-31bc8d58f22f`

この形式はRailway APIトークンではなく、以下のいずれかの可能性があります：

- ❓ アカウントID
- ❓ プロジェクトID
- ❓ チームID

### 正しいAPIトークンの形式

```
rw_Fe26.2**60120c18bf365c1857d9c1297a52944727878ab9602e34a65ec0a5bff6fc462f*...
```

長い暗号化された文字列です。

---

## 📞 次のステップ

### オプション 1: APIトークンを取得してCLIデプロイ（自動化）

1. 上記の手順でAPIトークンを取得
2. トークンを提供
3. CLI経由で全自動デプロイ実行

### オプション 2: Railway Dashboardで手動デプロイ（簡単）

1. https://railway.app/ にアクセス
2. 上記の手順に従って手動でデプロイ
3. 所要時間: 約15分

---

## 🆘 トラブルシューティング

### Q: APIトークンが見つからない

**A**: Account Settings → Tokens で新規作成してください

### Q: `7df95f36-062f-4409-83f1-31bc8d58f22f` は何？

**A**: これがプロジェクトIDの場合、既存プロジェクトにサービスを追加できます。
Railway Dashboardで該当プロジェクトを開いて確認してください。

---

**推奨**: 最も簡単な方法は、Railway Dashboardから手動でデプロイすることです。
所要時間は約15分で、`RAILWAY_QUICK_START.md` の手順に従ってください。
