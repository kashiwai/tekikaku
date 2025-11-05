# Railway 環境変数更新手順（GCP Cloud SQL接続）

## 🎉 GCP Cloud SQL MySQL 5.7 セットアップ完了！

以下の設定が完了しました：

```
✅ インスタンス: net8-mysql57
✅ バージョン: MySQL 5.7
✅ パブリックIP: 136.116.70.86
✅ データベース: net8_dev (utf8mb4)
✅ ユーザー: net8user
```

---

## 📋 STEP 1: Railway環境変数を更新

### 1-1. Railwayダッシュボードを開く

```
https://railway.app/project/mgg-webservice
```

### 1-2. 環境変数を編集

**Variables** タブを開いて、以下の環境変数を更新してください：

#### 更新する環境変数

```bash
DB_HOST=136.116.70.86
DB_PORT=3306
DB_NAME=net8_dev
DB_USER=net8user
DB_PASSWORD=Net8Railway2025!
```

#### ⚠️ 注意事項

- 既存の `DB_HOST` が `meticulous-vitality.railway.internal` になっている場合は、上書きしてください
- **既存の環境変数は削除せず、値だけを変更**してください

### 1-3. 保存して再デプロイ

環境変数を保存すると、Railwayが自動的に再デプロイします。

**デプロイ完了まで2〜3分待機してください。**

---

## 📋 STEP 2: データベースセットアップ

Railway デプロイ完了後、以下のURLを順番に実行してください：

### 自動化スクリプト（推奨）

```bash
cd /Users/kotarokashiwai/net8_rebirth/net8
./railway_setup_mac.command
```

### または手動で8つのURLを実行

```
1. https://mgg-webservice-production.up.railway.app/test_db_connection.php
   → GCP Cloud SQLへの接続確認

2. https://mgg-webservice-production.up.railway.app/setup_database.php
   → 「実行する」ボタンをクリック（65個のテーブル作成）

3. https://mgg-webservice-production.up.railway.app/data/xxxadmin/insert_sample_members.php
   → サンプル会員データ投入

4. https://mgg-webservice-production.up.railway.app/insert_mac_addresses.php
   → MACアドレス登録

5. https://mgg-webservice-production.up.railway.app/update_category.php
   → カテゴリ更新

6. https://mgg-webservice-production.up.railway.app/insert_hokuto_model.php
   → 北斗モデル登録

7. https://mgg-webservice-production.up.railway.app/register_corner.php
   → コーナー登録

8. https://mgg-webservice-production.up.railway.app/register_camera.php
   → カメラ登録
```

---

## 📋 STEP 3: 動作確認

トップページにアクセス：

```
https://mgg-webservice-production.up.railway.app/
```

### ✅ 成功の確認

- 500エラーが解消されている
- ゲーム画面が正常に表示される
- 台リストが表示される

### ❌ エラーが出る場合

1. Railway デプロイログを確認
2. `test_db_connection.php` でGCP Cloud SQLへの接続を確認
3. GCP Cloud SQLの承認済みネットワーク設定を確認

---

## 🔐 接続情報まとめ

```
ホスト: 136.116.70.86
ポート: 3306
データベース: net8_dev
ユーザー: net8user
パスワード: Net8Railway2025!
rootパスワード: Net8SecurePass2025!
```

---

## 💡 メリット

✅ **データ永続化**: Railwayを再デプロイしてもデータが消えない
✅ **MySQL 5.7**: アプリケーション要件を完全に満たす
✅ **高速**: 専用DBサーバーでパフォーマンス向上
✅ **自動バックアップ**: GCPで自動バックアップ可能

---

## 🆘 トラブルシューティング

### エラー: "Access denied for user 'net8user'"

→ パスワードが正しいか確認してください: `Net8Railway2025!`

### エラー: "Can't connect to MySQL server at '136.116.70.86'"

→ GCP Cloud SQLの承認済みネットワーク設定を確認：

```bash
gcloud sql instances describe net8-mysql57 --format="value(settings.ipConfiguration.authorizedNetworks)"
```

`0.0.0.0/0` になっていればOK。

### テーブルが作成されていない

→ STEP 2-2 の `setup_database.php` で「実行する」ボタンをクリックしたか確認

---

## 📞 完了したら

この手順を完了したら、トップページのスクリーンショットを共有してください！
