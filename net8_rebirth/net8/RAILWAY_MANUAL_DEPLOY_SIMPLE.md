# Railway 手動デプロイ - 簡易版

## 🎯 この方法を推奨する理由

APIトークンの認証が困難なため、Railway Dashboardから直接デプロイする方が確実です。

**所要時間**: 15-20分
**必要なもの**: Railwayアカウント（GitHubアカウントでログイン可能）

---

## 📋 ステップバイステップ手順

### Step 1: Railwayにログイン（2分）

1. **https://railway.app/ にアクセス**

2. **"Login with GitHub" をクリック**

3. **GitHubアカウントで認証**

---

### Step 2: 新しいプロジェクトを作成（1分）

1. **"+ New Project" ボタンをクリック**

2. **"Deploy from GitHub repo" を選択**

3. **"Configure GitHub App" をクリック**（初回のみ）
   - "Only select repositories" を選択
   - "mgg00123mg-prog/mgg001" を検索して選択
   - "Save" をクリック

4. **リポジトリを選択**
   - "mgg00123mg-prog/mgg001" をクリック

---

### Step 3: MySQL データベースを追加（2分）

1. **プロジェクト画面で "+ New" をクリック**

2. **"Database" を選択**

3. **"Add MySQL" をクリック**

4. **完了を待つ**
   - MySQLが自動的にプロビジョニングされます
   - 接続情報が自動生成されます

---

### Step 4: PeerJS Signaling サーバーを追加（5分）

1. **プロジェクト画面で "+ New" をクリック**

2. **"Empty Service" を選択**

3. **右上の "Settings" タブをクリック**

4. **"General" セクションで設定**
   ```
   Service Name: net8-signaling
   ```

5. **"Source" セクションで設定**
   - "Connect Repo" をクリック
   - "mgg00123mg-prog/mgg001" を選択
   - Root Directory: (空のまま)

6. **"Deploy" セクションで設定**
   - Builder: Dockerfile
   - Dockerfile Path: `Dockerfile.signaling`
   - Start Command: (空のまま)

7. **"Variables" タブをクリック**
   - "+ New Variable" を2回クリックして以下を追加:
     ```
     PORT = 9000
     PEERJS_KEY = peerjs
     ```

8. **"Networking" タブをクリック**
   - "Generate Domain" ボタンをクリック
   - 生成されたドメインをメモ
     例: `net8-signaling-production.up.railway.app`

9. **"Deployments" タブをクリック**
   - "Deploy" ボタンをクリック
   - ビルドが開始されます（3-5分）

---

### Step 5: Apache/PHP Webサーバーを追加（5分）

1. **プロジェクト画面で "+ New" をクリック**

2. **"Empty Service" を選択**

3. **右上の "Settings" タブをクリック**

4. **"General" セクションで設定**
   ```
   Service Name: net8-web
   ```

5. **"Source" セクションで設定**
   - "Connect Repo" をクリック
   - "mgg00123mg-prog/mgg001" を選択
   - Root Directory: (空のまま)

6. **"Deploy" セクションで設定**
   - Builder: Dockerfile
   - Dockerfile Path: `Dockerfile.web`
   - Start Command: (空のまま)

7. **"Variables" タブをクリック**
   - "+ New Variable" を7回クリックして以下を追加:

   **重要**: `${{ }}` の形式を正確に入力してください

   ```
   DATABASE_HOST = ${{MySQL.MYSQLHOST}}
   DATABASE_PORT = ${{MySQL.MYSQLPORT}}
   DATABASE_USER = ${{MySQL.MYSQLUSER}}
   DATABASE_PASSWORD = ${{MySQL.MYSQLPASSWORD}}
   DATABASE_NAME = ${{MySQL.MYSQLDATABASE}}
   SIGNALING_HOST = ${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
   SIGNALING_PORT = 443
   ```

   ⚠️ **注意**:
   - `${{MySQL.MYSQLHOST}}` は手入力（MySQLサービス名を参照）
   - `${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}` も手入力（Signalingサービス名を参照）

8. **"Networking" タブをクリック**
   - "Generate Domain" ボタンをクリック
   - 生成されたドメインをメモ
     例: `net8-web-production.up.railway.app`

9. **"Deployments" タブをクリック**
   - "Deploy" ボタンをクリック
   - ビルドが開始されます（5-10分）

---

## ✅ デプロイ完了後の確認

### 1. Signalingサーバーの確認

ターミナルで実行:
```bash
curl https://net8-signaling-production.up.railway.app/peerjs
```

**期待される結果**: PeerJSサーバーのレスポンス

---

### 2. Webサーバーの確認

ブラウザで以下にアクセス:
```
https://net8-web-production.up.railway.app/server_v2/?MAC=34-a6-ef-35-73-73
```

**期待される結果**:
- カメラアクセス許可ダイアログが表示される
- F12コンソールで確認:
  ```javascript
  console.log('peer._socket:', _peer._socket);
  console.log('peer.open:', _peer.open);
  ```

**成功の確認**:
```
peer._socket: WebSocket {url: "wss://net8-signaling-production.up.railway.app/peerjs?..."}
peer.open: true
```

---

### 3. Mac側から映像受信テスト

```
https://net8-web-production.up.railway.app/play_v2/test_simple.html
```

Windows側のカメラIDを入力して接続

---

## 📊 最終的なURL構成

**GitHubリポジトリ**:
```
https://github.com/mgg00123mg-prog/mgg001
```

**Railway プロジェクト**:
```
https://railway.app/project/<PROJECT_ID>
```

**Windows側（カメラ配信）**:
```
https://net8-web-production.up.railway.app/server_v2/?MAC=34-a6-ef-35-73-73
```

**Mac側（視聴）**:
```
https://net8-web-production.up.railway.app/play_v2/test_simple.html
```

**シグナリングサーバー**:
```
wss://net8-signaling-production.up.railway.app/peerjs
```

---

## 💰 Railway 料金

**Starter Plan**: $5/月
- 500時間の実行時間
- 3サービス（MySQL, Signaling, Web）で十分

**初回**: $5の無料クレジット付与

---

## 🆘 トラブルシューティング

### Q: デプロイが失敗する

**A**: "Deployments" タブ → "View Logs" でエラーを確認
- Dockerfile Pathが正しいか確認
- 環境変数が正しく設定されているか確認

---

### Q: データベース接続エラー

**A**: 環境変数の参照構文を確認
```
❌ DATABASE_HOST = MySQL.MYSQLHOST
✅ DATABASE_HOST = ${{MySQL.MYSQLHOST}}
```

---

### Q: PeerJS接続エラー

**A**: Signaling サーバーのドメインが正しく設定されているか確認
```
SIGNALING_HOST = ${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
```

---

## 🎯 次のステップ

1. **https://railway.app/ にアクセス**
2. 上記の手順に従ってデプロイ
3. 各サービスのドメインをメモ
4. Windows側のURLを更新
5. 動作確認

---

**推奨**: この手順書を見ながら、一つずつ確実に進めてください

**質問があれば**: デプロイ中のスクリーンショットを共有してください
