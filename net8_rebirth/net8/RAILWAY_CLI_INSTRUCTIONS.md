# Railway CLI デプロイ手順

## 🎯 CLIを使った全自動デプロイ

Railway CLIを使って、コマンドライン経由で全サービスを自動デプロイします。

**所要時間**: 5-10分

---

## 📋 Step 1: Railway CLIにログイン（必須）

ターミナルで以下のコマンドを実行してください：

```bash
railway login
```

**実行すると**:
1. ブラウザが自動的に開きます
2. Railway のログインページが表示されます
3. "Login with GitHub" をクリック
4. GitHubアカウントで認証
5. "Authorize Railway" をクリック
6. ブラウザに "✓ Logged in as <あなたの名前>" と表示されます
7. ターミナルに戻り、"Logged in as <あなたの名前>" が表示されます

---

## 📋 Step 2: 認証確認

ログインが成功したか確認します：

```bash
railway whoami
```

**成功例**:
```
Logged in as: あなたの名前 (あなたのメールアドレス)
```

---

## 📋 Step 3: 自動デプロイ実行

以下のコマンドを実行します：

```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
./railway-cli-deploy.sh
```

**このスクリプトが自動的に実行する内容**:
1. ✅ 認証確認
2. ✅ 新しいプロジェクト作成
3. ✅ MySQL データベース追加
4. ✅ PeerJS Signaling サーバー デプロイ
5. ✅ Apache/PHP Web サーバー デプロイ
6. ✅ 環境変数設定
7. ✅ ドメイン生成

**所要時間**: 5-10分

---

## ✅ デプロイ完了後

スクリプト実行後、以下のコマンドでRailway Dashboardを開きます：

```bash
railway open
```

ブラウザが開き、デプロイされたプロジェクトが表示されます。

---

## 📊 サービス確認

### Signaling サーバーの確認

```bash
# ドメインを確認
railway domain --service net8-signaling

# ログを確認
railway logs --service net8-signaling
```

### Web サーバーの確認

```bash
# ドメインを確認
railway domain --service net8-web

# ログを確認
railway logs --service net8-web
```

---

## 🌐 最終的なURL

デプロイ完了後、以下のコマンドで各サービスのURLを確認できます：

```bash
railway status
```

**Windows側（カメラ配信）**:
```
https://[net8-web のドメイン]/server_v2/?MAC=34-a6-ef-35-73-73
```

**Mac側（視聴）**:
```
https://[net8-web のドメイン]/play_v2/test_simple.html
```

---

## 🆘 トラブルシューティング

### Q: "Cannot login in non-interactive mode" エラー

**A**: ターミナルが対話モードで実行されていません。
- 通常のターミナルアプリで実行してください
- IDEの統合ターミナルではなく、Mac標準のTerminal.appを使用してください

### Q: "Unauthorized" エラー

**A**: ログインが完了していません。
```bash
railway login
```
を実行してブラウザで認証してください。

### Q: デプロイが失敗する

**A**: ログを確認してください
```bash
railway logs
```

---

## 📝 完全な実行例

```bash
# Step 1: ログイン
railway login
# → ブラウザが開き、GitHubで認証

# Step 2: 認証確認
railway whoami
# → Logged in as: あなたの名前

# Step 3: デプロイ実行
cd /Users/kotarokashiwai/net8_rebirth/net8
./railway-cli-deploy.sh
# → 自動デプロイ開始（5-10分）

# Step 4: Dashboard確認
railway open
# → ブラウザでプロジェクトを確認

# Step 5: ドメイン確認
railway status
# → 各サービスのURLを確認
```

---

## 🎯 次のステップ

1. **ターミナルを開く**（Mac標準のTerminal.app）

2. **以下のコマンドを順番に実行**:
   ```bash
   railway login
   railway whoami
   cd /Users/kotarokashiwai/net8_rebirth/net8
   ./railway-cli-deploy.sh
   ```

3. **完了後、URLを確認**:
   ```bash
   railway status
   railway open
   ```

---

**注意**: `railway login` は一度だけ実行すれば、以降は認証情報が保存されます。

**準備完了**: 上記のコマンドを実行してください！
