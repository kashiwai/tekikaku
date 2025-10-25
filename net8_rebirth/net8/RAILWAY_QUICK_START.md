# Railway クイックスタートガイド

## ✅ 準備完了

- ✅ GitHubリポジトリへのpush完了
- ✅ Railway用Dockerfileと設定ファイル作成完了
- ✅ デプロイ準備完了

**GitHubリポジトリ**: https://github.com/mgg00123mg-prog/mgg001

---

## 🚀 Railway デプロイ 3ステップ

### Step 1: Railwayアカウント作成（5分）

1. **Railway公式サイトにアクセス**
   ```
   https://railway.app/
   ```

2. **"Login with GitHub" をクリック**
   - GitHubアカウントで認証

3. **リポジトリへのアクセス許可**
   - "Configure GitHub App" をクリック
   - "Only select repositories" を選択
   - "mgg00123mg-prog/mgg001" を選択
   - "Save" をクリック

---

### Step 2: MySQL データベース作成（2分）

1. **"+ New Project" をクリック**

2. **"Provision MySQL" を選択**

3. **データベース名を確認**
   - Variables タブで接続情報を確認
   ```
   MYSQL_HOST
   MYSQL_PORT
   MYSQL_USER
   MYSQL_PASSWORD
   MYSQL_DATABASE
   ```

---

### Step 3: WebサーバーとSignalingサーバーをデプロイ（10分）

#### 3-1. PeerJS Signaling サーバー

1. **同じプロジェクト内で "+ New" → "GitHub Repo" を選択**

2. **"mgg00123mg-prog/mgg001" を選択**

3. **Settings → General**
   ```
   Service Name: net8-signaling
   Root Directory: (空のまま)
   ```

4. **Settings → Deploy**
   ```
   Dockerfile Path: Dockerfile.signaling
   ```

5. **Settings → Variables で環境変数を追加**
   ```
   PORT=9000
   PEERJS_KEY=peerjs
   ```

6. **Settings → Networking**
   - "Generate Domain" をクリック
   - 生成されたドメインをメモ
   - 例: `net8-signaling-production.up.railway.app`

7. **Deployをクリックして再デプロイ**

---

#### 3-2. Apache/PHP Webサーバー

1. **同じプロジェクト内で "+ New" → "GitHub Repo" を選択**

2. **"mgg00123mg-prog/mgg001" を選択**

3. **Settings → General**
   ```
   Service Name: net8-web
   Root Directory: (空のまま)
   ```

4. **Settings → Deploy**
   ```
   Dockerfile Path: Dockerfile.web
   ```

5. **Settings → Variables で環境変数を追加**

   **重要**: 以下の形式で入力（Railwayの参照構文）
   ```
   DATABASE_HOST=${{MySQL.MYSQLHOST}}
   DATABASE_PORT=${{MySQL.MYSQLPORT}}
   DATABASE_USER=${{MySQL.MYSQLUSER}}
   DATABASE_PASSWORD=${{MySQL.MYSQLPASSWORD}}
   DATABASE_NAME=${{MySQL.MYSQLDATABASE}}
   SIGNALING_HOST=${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
   SIGNALING_PORT=443
   ```

6. **Settings → Networking**
   - "Generate Domain" をクリック
   - 生成されたドメインをメモ
   - 例: `net8-web-production.up.railway.app`

7. **Deployをクリックして再デプロイ**

---

## 🎯 デプロイ完了後の確認

### 1. シグナリングサーバーの確認

ターミナルで以下を実行:
```bash
curl https://[net8-signaling のドメイン]/peerjs
```

**成功例**:
```
PeerJS Server is running
```

---

### 2. Webサーバーの確認

ブラウザで以下にアクセス:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

**成功の確認**:
- カメラアクセス許可ダイアログが表示
- F12コンソールで以下を確認:
```javascript
console.log('peer._socket:', _peer._socket);
console.log('peer.open:', _peer.open);
```

**期待される結果**:
```
peer._socket: WebSocket {url: "wss://net8-signaling-production.up.railway.app/peerjs?..."}
peer.open: true
```

---

### 3. Mac側からカメラ映像受信テスト

```
https://[net8-web のドメイン]/play_v2/test_simple.html
```

Windows側のカメラIDを入力して接続

---

## 📊 最終的なURL構成

**Windows側（カメラ配信）**:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

**Mac側（視聴）**:
```
https://[net8-web のドメイン]/play_v2/test_simple.html
```

**シグナリングサーバー**:
```
wss://[net8-signaling のドメイン]/peerjs
```

---

## 💰 料金

**Starter Plan**: $5/月
- 500時間の実行時間
- 3サービス（MySQL, Web, Signaling）で十分

**試用**: クレジットカード登録で $5 分の無料クレジット付与

---

## ✅ チェックリスト

- [ ] Railwayアカウント作成
- [ ] MySQL データベース作成
- [ ] net8-signaling サービスデプロイ
- [ ] net8-signaling ドメイン生成
- [ ] net8-web サービスデプロイ
- [ ] net8-web 環境変数設定
- [ ] net8-web ドメイン生成
- [ ] Windows側でURLアクセステスト
- [ ] カメラ映像配信テスト
- [ ] Mac側で映像受信テスト

---

## 🆘 よくある問題

### Q: デプロイが失敗する

**A**: Logs タブでエラーを確認
```
Railway Dashboard → サービス選択 → Logs
```

### Q: データベース接続エラー

**A**: 環境変数の参照構文を確認
```
❌ DATABASE_HOST=MySQL.MYSQLHOST
✅ DATABASE_HOST=${{MySQL.MYSQLHOST}}
```

### Q: PeerJS 404 エラー

**A**: Signaling サーバーのドメインが正しく設定されているか確認
```
SIGNALING_HOST=${{net8-signaling.RAILWAY_PUBLIC_DOMAIN}}
```

---

**次のステップ**: https://railway.app/ にアクセスしてアカウント作成

**詳細ガイド**: `RAILWAY_DEPLOYMENT.md` を参照
