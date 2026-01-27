# NET8 Gaming SDK Beta

## 🎮 概要

NET8 Gaming SDKは、わずか3行のコードでオンラインパチンコ・スロットゲームをあなたのウェブサイトに統合できるJavaScript SDKです。

## 🚀 クイックスタート

### 1. SDKの読み込み

```html
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
```

### 2. 初期化とゲーム起動

```html
<div id="game-container" style="width:800px; height:600px;"></div>

<script>
  (async function() {
    // SDK初期化
    await Net8.init('YOUR_API_KEY');

    // ゲーム作成と起動
    const game = Net8.createGame({
      model: 'HOKUTO4GO',
      container: '#game-container'
    });

    await game.start();
  })();
</script>
```

## 📦 API

### Net8.init(apiKey, options)

SDKを初期化します。

```javascript
await Net8.init('pk_demo_12345', {
  apiUrl: 'https://mgg-webservice-production.up.railway.app' // オプション
});
```

**パラメータ**:
- `apiKey` (string, 必須): あなたのAPIキー
- `options` (object, オプション): 追加設定

**戻り値**: Promise<void>

### Net8.getModels()

利用可能なゲーム機種の一覧を取得します。

```javascript
const models = await Net8.getModels();
console.log(models);
// [
//   { id: 'HOKUTO4GO', name: '北斗の拳 初号機', category: 'slot', ... },
//   { id: 'ZENIGATA01', name: '主役は銭形', category: 'slot', ... },
//   ...
// ]
```

**戻り値**: Promise<Array<Model>>

### Net8.createGame(config)

ゲームインスタンスを作成します。

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  container: '#game-container'
});
```

**パラメータ**:
- `config.model` (string, 必須): ゲーム機種ID
- `config.container` (string|HTMLElement, 必須): ゲームを表示するコンテナ

**戻り値**: Net8Game

### game.start()

ゲームを開始します。

```javascript
await game.start();
```

**戻り値**: Promise<void>

### game.on(event, callback)

ゲームイベントをリッスンします。

```javascript
game.on('ready', () => {
  console.log('ゲーム準備完了！');
});

game.on('error', (error) => {
  console.error('エラー:', error);
});
```

**イベント**:
- `ready`: ゲームの準備が完了
- `started`: ゲームが開始された
- `ended`: ゲームが終了した
- `error`: エラーが発生

## 🔑 APIキーの取得

1. [管理画面](https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php)にアクセス
2. 新しいAPIキーを発行
3. テスト環境には `pk_demo_12345` を使用可能

## 💡 完全な例

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>NET8 Game Demo</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #1a1a2e;
      color: white;
      padding: 20px;
    }
    #game-container {
      width: 100%;
      max-width: 800px;
      height: 600px;
      margin: 20px auto;
      border: 2px solid #667eea;
      border-radius: 10px;
      background: #0f0f1e;
    }
    .controls {
      text-align: center;
      margin: 20px;
    }
    button {
      padding: 15px 30px;
      font-size: 16px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin: 5px;
    }
    button:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>
  <h1 style="text-align: center;">NET8 Gaming Demo</h1>

  <div class="controls">
    <button onclick="initSDK()">SDK初期化</button>
    <button onclick="loadModels()">機種一覧取得</button>
    <button onclick="startGame('HOKUTO4GO')">北斗の拳 起動</button>
    <button onclick="startGame('ZENIGATA01')">銭形 起動</button>
  </div>

  <div id="game-container"></div>

  <div id="output" style="margin: 20px; padding: 20px; background: #2a2a3e; border-radius: 10px;">
    <h3>ログ:</h3>
    <pre id="log"></pre>
  </div>

  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
  <script>
    let currentGame = null;

    function log(message) {
      const logEl = document.getElementById('log');
      const timestamp = new Date().toLocaleTimeString();
      logEl.textContent += `[${timestamp}] ${message}\n`;
    }

    async function initSDK() {
      try {
        log('SDK初期化中...');
        await Net8.init('pk_demo_12345');
        log('✅ SDK初期化完了');
      } catch (error) {
        log(`❌ エラー: ${error.message}`);
      }
    }

    async function loadModels() {
      try {
        log('機種一覧取得中...');
        const models = await Net8.getModels();
        log(`✅ ${models.length}機種取得:`);
        models.forEach(m => log(`   - ${m.name} (${m.id})`));
      } catch (error) {
        log(`❌ エラー: ${error.message}`);
      }
    }

    async function startGame(modelId) {
      try {
        // 既存のゲームを停止
        if (currentGame) {
          log('既存のゲームを停止中...');
          // currentGame.stop(); // 実装されている場合
        }

        log(`ゲーム起動中: ${modelId}`);

        currentGame = Net8.createGame({
          model: modelId,
          container: '#game-container'
        });

        // イベントリスナー設定
        currentGame.on('ready', () => log('✅ ゲーム準備完了'));
        currentGame.on('started', () => log('✅ ゲーム開始'));
        currentGame.on('error', (err) => log(`❌ ゲームエラー: ${err.message}`));

        await currentGame.start();
        log(`✅ ${modelId} 起動成功`);
      } catch (error) {
        log(`❌ エラー: ${error.message}`);
      }
    }

    // ページ読み込み時に自動初期化
    window.addEventListener('load', () => {
      log('ページ読み込み完了');
      log('「SDK初期化」ボタンをクリックして開始してください');
    });
  </script>
</body>
</html>
```

## 🔒 セキュリティ

- APIキーは環境変数やサーバー側の設定で管理してください
- 公開されるクライアントコードにはテスト用のキー（`pk_demo_*`）のみを使用
- 本番環境では `pk_live_*` キーを使用し、サーバー側でプロキシ経由でアクセス

## 📚 ドキュメント

- [デプロイガイド](../NET8_SDK_BETA_DEPLOYMENT_GUIDE.md)
- [クイックスタート](../NET8_SDK_QUICKSTART.md)
- [SDK仕様](../NET8_JAVASCRIPT_SDK_SPEC.md)
- [UXガイド](../NET8_SDK_USER_EXPERIENCE_GUIDE.md)
- [完成レポート](../NET8_SDK_BETA_COMPLETION_REPORT.md)

## 🐛 トラブルシューティング

### SDKが初期化できない

```javascript
// CORSエラーの場合、APIサーバーの設定を確認
// コンソールでエラーメッセージを確認してください
```

### ゲームが起動しない

```javascript
// ブラウザのコンソールでエラーログを確認
// 利用可能な機種IDかどうか確認: await Net8.getModels();
```

### "NO_AVAILABLE_MACHINE" エラー

これは正常なレスポンスです。データベースに利用可能なマシンが登録されていない場合に返されます。

## 🎯 デモページ

完全に動作するデモページ: [https://mgg-webservice-production.up.railway.app/sdk/demo.html](https://mgg-webservice-production.up.railway.app/sdk/demo.html)

## 💬 サポート

技術的な質問やバグ報告は、プロジェクト管理者にお問い合わせください。

---

**NET8 Gaming SDK Beta v1.0.0**
© 2025 NET8 Development Team
