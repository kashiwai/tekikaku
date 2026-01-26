<?php
/**
 * NET8 SDK - Client Connection Guide Generator
 * お客様向け個別接続ガイド自動生成
 * Version: 1.0.1
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

// 簡易認証
$admin_password = $_GET['auth'] ?? '';
if ($admin_password !== 'net8_admin_2025') {
    http_response_code(403);
    die('Access Denied');
}

$key_id = (int)($_GET['key_id'] ?? 0);

if (!$key_id) {
    die('Error: key_id is required');
}

try {
    $pdo = get_db_connection();

    // APIキー情報取得
    $stmt = $pdo->prepare("SELECT * FROM api_keys WHERE id = :id");
    $stmt->execute(['id' => $key_id]);
    $key_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$key_data) {
        die('Error: API Key not found');
    }

    // 環境情報
    $is_mock = ($key_data['environment'] === 'test' || $key_data['environment'] === 'staging');
    $env_name = [
        'test' => 'テスト環境',
        'staging' => 'ステージング環境',
        'live' => '本番環境'
    ][$key_data['environment']];

    // クライアント名抽出
    $client_name = preg_replace('/ - (test|staging|live)$/i', '', $key_data['name']);

    // 接続情報
    $base_url = 'https://mgg-webservice-production.up.railway.app';
    $sdk_url = $base_url . '/sdk/net8-sdk-beta.js';

    // マークダウン生成
    header('Content-Type: text/markdown; charset=UTF-8');
    header('Content-Disposition: attachment; filename="NET8_SDK_Connection_Guide_' . urlencode($client_name) . '.md"');

?>
# NET8 Gaming SDK - 接続ガイド

**発行日**: <?php echo date('Y年m月d日'); ?>

**お客様**: <?php echo $client_name; ?>

**環境**: <?php echo $env_name; ?>

<?php if ($is_mock): ?>
**🎉 このAPIキーは実機なしでテスト可能です！**
<?php endif; ?>

---

## 📋 お客様専用の接続情報

### APIキー

```
<?php echo $key_data['key_value']; ?>

```

⚠️ **重要**: このAPIキーは機密情報です。第三者に漏洩しないよう厳重に管理してください。

### 基本情報

| 項目 | 内容 |
|------|------|
| 環境 | <?php echo $env_name; ?> (<?php echo $key_data['environment']; ?>) |
| レート制限 | <?php echo number_format($key_data['rate_limit']); ?> リクエスト/日 |
| 有効期限 | <?php echo $key_data['expires_at'] ? date('Y年m月d日', strtotime($key_data['expires_at'])) : '無期限'; ?> |
| モックモード | <?php echo $is_mock ? '✅ 有効（実機不要）' : '❌ 無効（実機接続）'; ?> |

---

## 🚀 クイックスタート（5分で完了）

### ステップ1: SDKをHTMLに追加

貴社のウェブサイトに以下のコードを追加してください：

```html
<!DOCTYPE html>
<html>
<head>
  <title>Game Integration</title>
  <!-- NET8 SDK読み込み -->
  <script src="<?php echo $sdk_url; ?>"></script>
</head>
<body>
  <!-- ゲーム表示エリア -->
  <div id="game" style="width:800px; height:600px;"></div>

  <script>
    // SDK初期化とゲーム起動（わずか3行！）
    Net8.init('<?php echo $key_data['key_value']; ?>').then(() => {
      const game = Net8.createGame({
        model: 'HOKUTO4GO',  // 北斗の拳
        container: '#game'
      });
      game.start();
    });
  </script>
</body>
</html>
```

### ステップ2: ブラウザで確認

上記HTMLファイルをブラウザで開くだけで、ゲームが起動します。

<?php if ($is_mock): ?>
**✅ テスト環境のため、実機がなくても動作します！**

レスポンスに `"mock": true` が含まれていることを確認してください。
<?php else: ?>
**⚠️ 本番環境のため、実機との接続が必要です。**

実機が接続されていない場合、`NO_AVAILABLE_MACHINE` エラーが返されます。
<?php endif; ?>

---

## 📚 API エンドポイント

### ベースURL

```
<?php echo $base_url; ?>

```

### 1. 認証API

**エンドポイント**: `POST /api/v1/auth.php`

**リクエスト例**:
```bash
curl -X POST <?php echo $base_url; ?>/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey":"<?php echo $key_data['key_value']; ?>"}'
```

**レスポンス例**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "<?php echo $key_data['environment']; ?>"
}
```

### 2. 機種一覧API

**エンドポイント**: `GET /api/v1/models.php`

**リクエスト例**:
```bash
curl "<?php echo $base_url; ?>/api/v1/models.php?apiKey=<?php echo $key_data['key_value']; ?>"
```

**利用可能な機種**:
- `HOKUTO4GO` - 北斗の拳 初号機
- `ZENIGATA01` - 主役は銭形
- `MILLIONGOD01` - ミリオンゴッド4号機

### 3. ゲーム開始API

**エンドポイント**: `POST /api/v1/game_start.php`

**リクエスト例**:
```bash
# 1. まずJWTトークン取得（上記の認証API）
TOKEN="取得したトークン"

# 2. ゲーム開始
curl -X POST <?php echo $base_url; ?>/api/v1/game_start.php \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"modelId":"HOKUTO4GO"}'
```

---

## 🔧 JavaScriptSDK 完全リファレンス

### SDK初期化

```javascript
await Net8.init('<?php echo $key_data['key_value']; ?>', {
  apiUrl: '<?php echo $base_url; ?>'  // オプション
});
```

### 機種一覧取得

```javascript
const models = await Net8.getModels();
console.log(models);
// [{id: 'HOKUTO4GO', name: '北斗の拳 初号機', ...}, ...]
```

### ゲーム作成と起動

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'
});

// イベントリスナー
game.on('ready', () => console.log('準備完了'));
game.on('error', (err) => console.error('エラー:', err));

// 起動
await game.start();
```

### ゲーム終了

```javascript
game.stop();
```

---

## 🎯 サンプルコード集

### シンプル版

```html
<script src="<?php echo $sdk_url; ?>"></script>
<div id="game"></div>
<script>
  Net8.init('<?php echo $key_data['key_value']; ?>').then(() => {
    Net8.createGame({ model: 'HOKUTO4GO', container: '#game' }).start();
  });
</script>
```

### 機種選択UI付き

```html
<!DOCTYPE html>
<html>
<head>
  <script src="<?php echo $sdk_url; ?>"></script>
  <style>
    .model-list { display: flex; gap: 20px; margin: 20px; }
    .model-card {
      padding: 20px;
      border: 2px solid #ddd;
      border-radius: 10px;
      cursor: pointer;
    }
    .model-card:hover { background: #f0f0f0; }
    #game { width: 800px; height: 600px; margin: 20px; }
  </style>
</head>
<body>
  <h1>ゲーム選択</h1>
  <div class="model-list" id="models"></div>
  <div id="game"></div>

  <script>
    let currentGame = null;

    Net8.init('<?php echo $key_data['key_value']; ?>').then(async () => {
      // 機種一覧取得
      const models = await Net8.getModels();

      // UI生成
      models.forEach(model => {
        const card = document.createElement('div');
        card.className = 'model-card';
        card.innerHTML = `<h3>${model.name}</h3><p>${model.category}</p>`;
        card.onclick = () => startGame(model.id);
        document.getElementById('models').appendChild(card);
      });
    });

    async function startGame(modelId) {
      if (currentGame) currentGame.stop();

      currentGame = Net8.createGame({
        model: modelId,
        container: '#game'
      });

      await currentGame.start();
    }
  </script>
</body>
</html>
```

---

## <?php echo $is_mock ? '🧪' : '🏭'; ?> 環境について

### <?php echo $env_name; ?>の特徴

<?php if ($is_mock): ?>
#### モックモード（実機接続不要）

このAPIキーは**モックモード**で動作します。以下の特徴があります：

- ✅ 実機・ハードウェアなしで完全テスト可能
- ✅ インターネット接続のみで即座に開発開始
- ✅ 仮想マシンデータを自動生成
- ✅ 完全なゲームフローを体験可能

#### モックレスポンスの見分け方

APIレスポンスに以下のフィールドが含まれます：

```json
{
  "environment": "<?php echo $key_data['environment']; ?>",
  "mock": true,
  "machineNo": 9999,
  "signalingId": "mock_sig_********"
}
```

#### 本番環境への移行

テスト完了後、本番環境APIキーに切り替えるだけで実機接続に移行できます：

```javascript
// テスト環境
await Net8.init('<?php echo $key_data['key_value']; ?>');

// 本番環境（新しいAPIキーを発行後）
await Net8.init('pk_live_****************');
```

**コードの変更は不要です！** APIキーを変更するだけ。

<?php else: ?>
#### 本番環境（実機接続）

このAPIキーは**本番環境**です。以下の点にご注意ください：

- ⚠️ 物理的なパチンコ・スロット実機との接続が必要
- ⚠️ マシンが利用中の場合、`NO_AVAILABLE_MACHINE` エラーが返されます
- ⚠️ 実際の遅延・接続状況の影響を受けます

#### 本番環境のレスポンス

```json
{
  "environment": "live",
  "mock": false,
  "machineNo": 123,  // 実機のID
  "signalingId": "real_signaling_id",
  "camera": {
    "streamUrl": "rtsp://..."  // 実際のカメラストリーム
  }
}
```
<?php endif; ?>

---

## 🔒 セキュリティ

### APIキーの管理

- **フロントエンドでの使用**: テスト用APIキー（`pk_demo_*`, `pk_staging_*`）のみ
- **本番環境**: サーバーサイドでプロキシ経由でアクセスを推奨
- **環境変数**: `.env` ファイルで管理

**.env ファイル例**:
```
NET8_API_KEY=<?php echo $key_data['key_value']; ?>

NET8_API_URL=<?php echo $base_url; ?>

```

### レート制限

- 上限: **<?php echo number_format($key_data['rate_limit']); ?> リクエスト/日**
- 超過時: `429 Too Many Requests` エラー
- 監視: 管理画面で使用状況確認可能

---

## 🛠️ トラブルシューティング

### Q1: SDKが初期化できない

**症状**: `Net8.init()` でエラー

**解決方法**:
1. APIキーが正しいか確認
2. ネットワーク接続を確認
3. ブラウザコンソールでエラーメッセージを確認

### Q2: ゲームが表示されない

**症状**: `game.start()` 後も画面が空白

**解決方法**:
```html
<!-- コンテナに明示的なサイズ指定 -->
<div id="game" style="width:800px; height:600px; border:1px solid #ccc;"></div>
```

<?php if (!$is_mock): ?>
### Q3: "NO_AVAILABLE_MACHINE" エラー

**症状**: 本番環境でマシン利用不可

**原因**:
- 全マシンが使用中
- マシン登録がない

**解決方法**:
- しばらく待ってから再試行
- NET8サポートに連絡
<?php endif; ?>

---

## 📞 サポート

### ドキュメント

- **ユーザーズマニュアル**: NET8_SDK_USER_MANUAL_v1.01.md
- **技術仕様書**: NET8_SDK_TECHNICAL_MANUAL_v1.01.md
- **API仕様**: GitHub リポジトリ参照

### デモサイト

完全動作デモ: <?php echo $base_url; ?>/sdk/demo.html

### お問い合わせ

技術サポート: NET8開発チーム
Email: support@net8.example.com（仮）

---

## 📈 使用状況の確認

### 管理画面

APIキー使用状況: <?php echo $base_url; ?>/data/xxxadmin/api_keys_manage.php

---

**NET8 Gaming SDK v1.0.1**
© 2025 NET8 Development Team

このガイドは <?php echo $client_name; ?> 様専用です。
第三者への転送・共有はご遠慮ください。

---

発行日: <?php echo date('Y年m月d日 H:i'); ?>

発行ID: <?php echo $key_data['id']; ?>

<?php
} catch (Exception $e) {
    echo "# エラー\n\n";
    echo $e->getMessage();
}
?>
