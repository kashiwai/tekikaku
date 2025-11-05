# Railway 最速デプロイ方法

## 🎯 最も簡単で確実な方法

Railway CLIの対話モードの問題を回避し、最速でデプロイします。

**所要時間**: 10分

---

## 📋 Step 1: Railway Dashboardでプロジェクト作成（3分）

### 1-1. Railway Dashboardを開く

```bash
railway open
```

またはブラウザで直接アクセス:
```
https://railway.app/
```

### 1-2. 新しいプロジェクトを作成

1. **"+ New Project" をクリック**
2. **"Empty Project" を選択**
3. **プロジェクト名を確認**（自動的に名前が付きます）

---

## 📋 Step 2: GitHubリポジトリを接続（2分）

プロジェクト画面で：

1. **"+ New" → "GitHub Repo" をクリック**

2. **"Configure GitHub App" をクリック**（初回のみ）
   - "Only select repositories" を選択
   - "mgg00123mg-prog/mgg001" を検索して選択
   - "Save" をクリック

3. **リポジトリを選択**
   - "mgg00123mg-prog/mgg001" をクリック

4. **デプロイ設定**
   - Root Directory: (空のまま)
   - Build Command: (空のまま)

5. **サービス名を変更**（任意）
   - 右上の設定アイコン → "net8-main" など

---

## 📋 Step 3: MySQLデータベースを追加（1分）

プロジェクト画面で：

1. **"+ New" → "Database" → "Add MySQL" をクリック**

2. **自動的にプロビジョニング開始**
   - 数秒で完了します

---

## 📋 Step 4: サービスを追加（4分）

### 4-1. Signaling サーバー

1. **"+ New" → "Empty Service" をクリック**

2. **"Settings" タブで設定**:
   ```
   Service Name: signaling
   ```

3. **"Source" セクション**:
   - Connect Repo → "mgg00123mg-prog/mgg001"

4. **"Deploy" セクション**:
   - Builder: Dockerfile
   - Dockerfile Path: `Dockerfile.signaling`

5. **"Variables" タブ**:
   ```
   PORT = 9000
   PEERJS_KEY = peerjs
   ```

6. **"Networking" タブ**:
   - "Generate Domain" をクリック

### 4-2. Web サーバー

1. **"+ New" → "Empty Service" をクリック**

2. **"Settings" タブで設定**:
   ```
   Service Name: web
   ```

3. **"Source" セクション**:
   - Connect Repo → "mgg00123mg-prog/mgg001"

4. **"Deploy" セクション**:
   - Builder: Dockerfile
   - Dockerfile Path: `Dockerfile.web`

5. **"Variables" タブ**:
   ```
   DATABASE_HOST = ${{MySQL.MYSQLHOST}}
   DATABASE_PORT = ${{MySQL.MYSQLPORT}}
   DATABASE_USER = ${{MySQL.MYSQLUSER}}
   DATABASE_PASSWORD = ${{MySQL.MYSQLPASSWORD}}
   DATABASE_NAME = ${{MySQL.MYSQLDATABASE}}
   SIGNALING_HOST = ${{signaling.RAILWAY_PUBLIC_DOMAIN}}
   SIGNALING_PORT = 443
   ```

6. **"Networking" タブ**:
   - "Generate Domain" をクリック

---

## ✅ デプロイ確認

### Signaling サーバー

```bash
curl https://[signaling-domain]/peerjs
```

### Web サーバー

ブラウザで:
```
https://[web-domain]/server_v2/?MAC=34-a6-ef-35-73-73
```

---

## 🎉 完了！

デプロイが完了したら、Railway Dashboard で各サービスのドメインを確認してください。

Windows側とMac側で新しいURLを使用できます。

---

**今すぐ開始**: `railway open` を実行してブラウザでRailway Dashboardを開いてください！
