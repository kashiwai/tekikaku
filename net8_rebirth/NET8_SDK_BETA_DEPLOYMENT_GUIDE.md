# NET8 SDK Beta版 - 完全デプロイガイド

**作成日**: 2025-11-06
**対象**: 明日までに動作するベータ版を完成させる

---

## 🎯 このガイドの目的

**今すぐ使えるNET8 SDK Beta版**を本番環境（Railway）にデプロイし、デモを動作させる。

---

## 📁 作成済みファイル一覧

### 1. APIエンドポイント（PHP）
```
net8/02.ソースファイル/net8_html/
├── api/
│   ├── v1/
│   │   ├── auth.php           # 認証API
│   │   ├── models.php         # 機種一覧API
│   │   └── game_start.php     # ゲーム開始API
│   └── setup_api_keys_table.sql  # DB セットアップ
```

### 2. SDK（JavaScript）
```
net8/02.ソースファイル/net8_html/sdk/
├── net8-sdk-beta.js   # メインSDK（200行）
└── demo.html          # デモページ
```

### 3. 管理画面
```
net8/02.ソースファイル/net8_html/data/xxxadmin/
└── api_keys_manage.php   # APIキー管理画面
```

---

## 🚀 デプロイ手順（30分以内）

### Step 1: データベースセットアップ（5分）

```bash
# ローカルまたはRailway MySQLに接続
mysql -h 136.116.70.86 -u net8tech001 -p net8_dev

# SQLファイルを実行
source /path/to/net8/02.ソースファイル/net8_html/api/setup_api_keys_table.sql

# 確認
SELECT * FROM api_keys;
```

**結果**:
```
+----+---------+-----------------+-----------+------------------+-------------+------------+-----------+---------------+---------------------+------------+
| id | user_id | key_value       | key_type  | name             | environment | rate_limit | is_active | last_used_at  | created_at          | expires_at |
+----+---------+-----------------+-----------+------------------+-------------+------------+-----------+---------------+---------------------+------------+
|  1 | NULL    | pk_demo_12345   | public    | Demo API Key     | test        |      10000 |         1 | NULL          | 2025-11-06 12:00:00 | NULL       |
+----+---------+-----------------+-----------+------------------+-------------+------------+-----------+---------------+---------------------+------------+
```

---

### Step 2: Railwayにプッシュ（5分）

```bash
cd /Users/kotarokashiwai/net8_rebirth

# 新規ファイルを追加
git add net8/02.ソースファイル/net8_html/api/
git add net8/02.ソースファイル/net8_html/sdk/
git add net8/02.ソースファイル/net8_html/data/xxxadmin/api_keys_manage.php

# コミット
git commit -m "feat: Add NET8 SDK Beta (API + SDK + Demo)

- API endpoints: auth, models, game_start
- JavaScript SDK (net8-sdk-beta.js)
- Demo page with live testing
- API key management system

🤖 Generated with Claude Code

Co-Authored-By: Claude <noreply@anthropic.com>"

# プッシュ
git push origin main
```

**Railwayで自動デプロイ開始**（3-5分）

---

### Step 3: 動作確認（5分）

#### 3-1. APIキー管理画面にアクセス

```
URL: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
```

1. 管理者ログイン
2. APIキー一覧を確認
3. 必要に応じて新規キーを生成

---

#### 3-2. SDKデモページにアクセス

```
URL: https://mgg-webservice-production.up.railway.app/sdk/demo.html
```

**操作手順**:
1. 「1. SDK初期化」ボタンをクリック
2. 「2. 機種一覧を読み込み」をクリック
3. 「3. ゲーム開始（ミリオンゴッド）」をクリック

**期待される結果**:
```
✅ SDK初期化完了！
✅ 12機種を取得しました
✅ ゲーム準備完了！
[ゲーム画面がiframeで表示される]
```

---

### Step 4: APIエンドポイントテスト（5分）

#### 認証API
```bash
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_demo_12345"}'
```

**期待レスポンス**:
```json
{
  "success": true,
  "token": "eyJhbGc....",
  "expiresIn": 3600,
  "environment": "test"
}
```

---

#### 機種一覧API
```bash
curl https://mgg-webservice-production.up.railway.app/api/v1/models.php
```

**期待レスポンス**:
```json
{
  "success": true,
  "count": 12,
  "models": [
    {
      "id": "milliongod",
      "name": "ミリオンゴッド",
      "category": "slot",
      "maker": "ユニバーサル"
    },
    ...
  ]
}
```

---

#### ゲーム開始API
```bash
# まず認証トークンを取得
TOKEN=$(curl -s -X POST https://mgg-webservice-production.up.railway.app/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey": "pk_demo_12345"}' | jq -r '.token')

# ゲーム開始
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"modelId": "milliongod"}'
```

**期待レスポンス**:
```json
{
  "success": true,
  "sessionId": "gs_xxxxxxxxx_1730898765",
  "machineNo": 123,
  "signalingId": "camera001",
  "model": {
    "id": "milliongod",
    "name": "ミリオンゴッド",
    "category": "slot"
  },
  "signaling": {
    "signalingId": "camera001",
    "host": "mgg-signaling-production-c1bd.up.railway.app",
    "port": 443,
    "secure": true,
    "path": "/peerjs"
  },
  "playUrl": "/data/play_v2/index.php?NO=123"
}
```

---

## 📝 顧客への提供方法

### 使い方（3行のコード）

```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</head>
<body>
  <div id="game"></div>

  <script>
    // 1. SDK初期化
    Net8.init('pk_demo_12345');

    // 2. ゲーム作成
    const game = Net8.createGame({
      model: 'milliongod',
      container: '#game'
    });

    // 3. ゲーム開始
    game.start();
  </script>
</body>
</html>
```

**これだけでパチスロゲームが動作します！**

---

## 🔐 APIキー管理

### 新規顧客へのAPIキー発行

1. 管理画面にアクセス
   ```
   https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
   ```

2. 「新規APIキー生成」フォームに入力
   - **キー名**: 顧客名または用途（例: "ABC社 本番環境"）
   - **環境**: test（テスト環境）または live（本番環境）

3. 「APIキーを生成」をクリック

4. 生成されたAPIキーを顧客に提供
   ```
   pk_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6
   ```

5. 顧客がSDKで使用
   ```javascript
   Net8.init('pk_live_a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6');
   ```

---

## 🎨 カスタマイズ例

### 例1: 複数機種対応ロビー

```javascript
Net8.init('pk_demo_12345');

// 機種一覧を取得
const models = await Net8.getModels();

// 機種選択ボタンを動的生成
models.forEach(model => {
  const button = document.createElement('button');
  button.textContent = model.name;
  button.onclick = async () => {
    const game = Net8.createGame({
      model: model.id,
      container: '#game'
    });
    await game.start();
  };
  document.body.appendChild(button);
});
```

---

### 例2: イベントハンドリング

```javascript
const game = Net8.createGame({
  model: 'milliongod',
  container: '#game'
});

// ゲーム準備完了時
game.on('ready', () => {
  console.log('ゲームが開始しました');
  showNotification('ゲーム準備完了！');
});

// エラー発生時
game.on('error', (error) => {
  console.error('エラー:', error);
  showErrorMessage(error.message);
});

game.start();
```

---

## 📊 使用量トラッキング

### 管理画面で確認

```
URL: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
```

**確認できる情報**:
- APIキーごとの使用回数
- 日別リクエスト数
- 平均レスポンスタイム
- 最終使用日時

---

## 🐛 トラブルシューティング

### 問題1: 「Invalid API key」エラー

**原因**: APIキーが間違っているか、無効化されている

**解決策**:
```sql
-- APIキーを確認
SELECT * FROM api_keys WHERE key_value = 'pk_demo_12345';

-- 無効化されている場合は有効化
UPDATE api_keys SET is_active = 1 WHERE key_value = 'pk_demo_12345';
```

---

### 問題2: CORSエラー

**原因**: APIエンドポイントでCORSが有効になっていない

**解決策**:
各APIファイル（auth.php, models.php, game_start.php）の先頭に以下が含まれていることを確認:
```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

### 問題3: ゲームが表示されない

**原因**: マシンが利用可能でない

**解決策**:
```sql
-- 利用可能なマシンを確認
SELECT
  m.machine_no,
  m.model_no,
  m.signaling_id,
  m.machine_status,
  lm.assign_flg
FROM dat_machine m
LEFT JOIN lnk_machine lm ON m.machine_no = lm.machine_no
WHERE m.del_flg = 0
  AND m.machine_status = 0
  AND (lm.assign_flg = 0 OR lm.assign_flg IS NULL);
```

---

## 📈 次のステップ（明日以降）

### Week 2以降の開発
1. **ポイント管理API** (`/api/v1/points/*`)
2. **プレイ実行API** (`/api/v1/game/play`)
3. **React/Vue Components**
4. **Developer Portal（フル版）**
5. **Stripe課金統合**

---

## 🎉 完成！

これで**NET8 SDK Beta版**が完全に動作します。

**デモURL**:
```
https://mgg-webservice-production.up.railway.app/sdk/demo.html
```

**APIキー管理**:
```
https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php
```

---

## 📞 サポート

問題が発生した場合:
1. ブラウザの開発者ツール（F12）でコンソールを確認
2. APIレスポンスのエラーメッセージを確認
3. データベースのapi_usage_logsテーブルを確認

---

**作成日**: 2025-11-06
**バージョン**: 1.0.0-beta
**次回更新**: 実装完了後
