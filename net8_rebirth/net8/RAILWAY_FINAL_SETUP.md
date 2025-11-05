# Railway 最終設定手順

## ✅ 完了している作業

- ✅ プロジェクト作成: `mmg2501` (ID: `8d81850a-8a75-4707-8439-4a87062f4927`)
- ✅ MySQL データベース追加済み
- ✅ Dockerfile.signaling サービス作成済み
- ✅ Dockerfile.web サービス作成済み
- ✅ mgg001 サービス作成済み

---

## 🔧 残りの設定（5分で完了）

Railway Dashboardで以下を設定してください。

### Step 1: Railway Dashboardを開く

```bash
railway open
```

または以下のURLを直接開く:
```
https://railway.app/project/8d81850a-8a75-4707-8439-4a87062f4927
```

---

### Step 2: Dockerfile.signaling サービスの設定

#### 2-1. サービスをクリック

プロジェクト画面で「Dockerfile.signaling」をクリック

#### 2-2. Settings → Deploy

1. **Builder**: `Dockerfile` を選択
2. **Dockerfile Path**: `Dockerfile.signaling` と入力

#### 2-3. Variables タブ

以下の環境変数を追加:

```
PORT = 9000
PEERJS_KEY = peerjs
```

#### 2-4. Networking タブ

1. **"Generate Domain" をクリック**
2. 生成されたドメインをメモ（例: `dockerfile-signaling-production.up.railway.app`）

#### 2-5. Deployments タブ

**"Deploy" ボタンをクリック**

---

### Step 3: Dockerfile.web サービスの設定

#### 3-1. サービスをクリック

プロジェクト画面で「Dockerfile.web」をクリック

#### 3-2. Settings → Deploy

1. **Builder**: `Dockerfile` を選択
2. **Dockerfile Path**: `Dockerfile.web` と入力

#### 3-3. Variables タブ

以下の環境変数を追加（正確にコピーしてください）:

```
DATABASE_HOST = ${{MySQL.MYSQLHOST}}
DATABASE_PORT = ${{MySQL.MYSQLPORT}}
DATABASE_USER = ${{MySQL.MYSQLUSER}}
DATABASE_PASSWORD = ${{MySQL.MYSQLPASSWORD}}
DATABASE_NAME = ${{MySQL.MYSQLDATABASE}}
SIGNALING_HOST = ${{Dockerfile.signaling.RAILWAY_PUBLIC_DOMAIN}}
SIGNALING_PORT = 443
```

⚠️ **重要**: `${{サービス名.変数名}}` の形式を正確に入力してください

#### 3-4. Networking タブ

1. **"Generate Domain" をクリック**
2. 生成されたドメインをメモ（例: `dockerfile-web-production.up.railway.app`）

#### 3-5. Deployments タブ

**"Deploy" ボタンをクリック**

---

### Step 4: 不要なサービスの削除（任意）

「mgg001」サービスは不要なので削除できます:

1. mgg001サービスをクリック
2. Settings → Danger → "Remove Service"

---

## ✅ デプロイ確認

### Signaling サーバーの確認

ターミナルで:
```bash
curl https://[signaling-domain]/peerjs
```

**期待される結果**: PeerJSサーバーのレスポンス

### Web サーバーの確認

ブラウザで:
```
https://[web-domain]/server_v2/?MAC=34-a6-ef-35-73-73
```

**期待される結果**:
- カメラアクセス許可ダイアログが表示
- F12コンソールで `peer._socket: WebSocket {...}` が表示

---

## 📊 最終的なURL構成

### Windows側（カメラ配信）
```
https://[web-domain]/server_v2/?MAC=34-a6-ef-35-73-73
```

### Mac側（視聴）
```
https://[web-domain]/play_v2/test_simple.html
```

---

## 🆘 トラブルシューティング

### ビルドエラーが出る場合

**"Error creating build plan with Railpack"**

→ Settings → Deploy で以下を確認:
- Builder が "Dockerfile" に設定されている
- Dockerfile Path が正確（`Dockerfile.signaling` または `Dockerfile.web`）

### 環境変数エラーが出る場合

**"DATABASE_HOST not found"**

→ Variables タブで `${{MySQL.MYSQLHOST}}` の形式を確認
- `${{` と `}}` が正確か
- サービス名（MySQL）が正しいか

---

## 🎯 次のステップ

1. **Railway Dashboardを開く**:
   ```bash
   railway open
   ```

2. **上記の設定を実施**（所要時間: 5分）

3. **デプロイ完了後、URLを確認**

4. **Windows側とMac側で動作確認**

---

**準備完了**: `railway open` を実行して、上記の設定を行ってください！
