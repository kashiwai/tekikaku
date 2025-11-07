# NET8 Gaming SDK - ユーザーズマニュアル v1.01

**最終更新**: 2025-11-07
**対象バージョン**: NET8 SDK v1.0.1-beta

---

## 📖 目次

1. [はじめに](#はじめに)
2. [クイックスタート](#クイックスタート)
3. [環境について](#環境について)
4. [基本的な使い方](#基本的な使い方)
5. [環境の切り替え](#環境の切り替え)
6. [トラブルシューティング](#トラブルシューティング)
7. [サポート](#サポート)

---

## はじめに

NET8 Gaming SDKは、パチンコ・スロットゲームをわずか3行のコードでウェブサイトに統合できる開発者向けツールキットです。

### 主な特徴

- **超簡単統合**: HTMLに3行追加するだけ
- **実機接続不要のテスト**: ステージング環境で完全テスト可能
- **本番環境への簡単切り替え**: APIキーを変更するだけ
- **リアルタイム通信**: WebRTCによる低遅延ストリーミング
- **複数機種対応**: 北斗の拳、銭形、ミリオンゴッドなど

---

## クイックスタート

### ステップ1: SDKをHTMLに追加

```html
<!DOCTYPE html>
<html>
<head>
  <title>My Game Site</title>
  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</head>
<body>
  <!-- ゲーム表示エリア -->
  <div id="game-container" style="width:800px; height:600px;"></div>

  <script>
    // ステップ2: SDK初期化とゲーム起動
    (async function() {
      // テスト環境用APIキーで初期化
      await Net8.init('pk_demo_12345');

      // ゲーム作成
      const game = Net8.createGame({
        model: 'HOKUTO4GO',
        container: '#game-container'
      });

      // ゲーム開始
      await game.start();
    })();
  </script>
</body>
</html>
```

### ステップ2: ブラウザで確認

1. 上記HTMLファイルを保存（例: `game.html`）
2. ブラウザで開く
3. ゲームが自動的にロードされます

**🎉 これだけで完了です！**

---

## 環境について

NET8 SDKは3つの環境をサポートしています：

### 1. **テスト環境（test）**

- **APIキー**: `pk_demo_12345`
- **用途**: 開発・テスト
- **特徴**: 実機不要のモックデータで動作
- **料金**: 無料

**メリット**:
- インターネット接続のみで完全テスト可能
- 実機の準備不要
- 即座に開発開始

**動作**:
- 仮想マシンデータを自動生成
- モックカメラストリーム
- 完全なゲームフロー体験

### 2. **ステージング環境（staging）**

- **APIキー**: `pk_staging_*`（管理者発行）
- **用途**: 本番前の最終確認
- **特徴**: テスト環境と同じモック動作
- **料金**: 無料

### 3. **本番環境（live/production）**

- **APIキー**: `pk_live_abcdef123456`
- **用途**: 実サービス提供
- **特徴**: 実機に接続
- **料金**: 使用量に応じて課金

---

## 基本的な使い方

### SDK初期化

```javascript
// 基本
await Net8.init('YOUR_API_KEY');

// オプション付き
await Net8.init('YOUR_API_KEY', {
  apiUrl: 'https://your-custom-api.com' // カスタムAPI URL
});
```

### 利用可能な機種を取得

```javascript
const models = await Net8.getModels();

console.log(models);
// [
//   {
//     id: 'HOKUTO4GO',
//     name: '北斗の拳 初号機',
//     category: 'slot',
//     thumbnail: '/images/models/hokuto4go.jpg'
//   },
//   ...
// ]
```

### ゲーム作成と起動

```javascript
// ゲームインスタンス作成
const game = Net8.createGame({
  model: 'HOKUTO4GO',           // 機種ID
  container: '#game-container'   // 表示先
});

// イベントリスナー設定
game.on('ready', () => {
  console.log('ゲーム準備完了！');
});

game.on('started', () => {
  console.log('ゲーム開始！');
});

game.on('error', (error) => {
  console.error('エラー:', error);
});

// ゲーム開始
await game.start();
```

### ゲーム終了

```javascript
game.stop();  // ゲームを停止
```

---

## 環境の切り替え

### テスト → 本番への移行

**ステップ1**: 本番用APIキーを取得
1. 管理画面にログイン
2. 「APIキー発行」をクリック
3. 環境で「本番（production）」を選択
4. `pk_live_*` 形式のキーが発行されます

**ステップ2**: コード内のAPIキーを変更

```javascript
// 変更前（テスト環境）
await Net8.init('pk_demo_12345');

// 変更後（本番環境）
await Net8.init('pk_live_abcdef123456');
```

**これだけです！** コードの他の部分は一切変更不要です。

### 環境判定

APIレスポンスで現在の環境を確認できます：

```javascript
const game = Net8.createGame({...});
await game.start();

// レスポンスに環境情報が含まれる
console.log(response.environment);  // 'test' or 'live'
console.log(response.mock);         // true (test) or false (live)
```

---

## トラブルシューティング

### Q1: SDKが初期化できない

**症状**: `Net8.init()` でエラー

**原因**:
- 無効なAPIキー
- ネットワーク接続エラー

**解決方法**:
```javascript
try {
  await Net8.init('YOUR_API_KEY');
} catch (error) {
  console.error('初期化エラー:', error.message);
  // エラーメッセージを確認
}
```

### Q2: ゲームが表示されない

**症状**: `game.start()` 後も画面が空白

**確認事項**:
1. コンテナ要素が存在するか
2. コンテナのサイズが設定されているか
3. ブラウザコンソールにエラーがないか

**解決方法**:
```html
<!-- コンテナに明示的なサイズ指定 -->
<div id="game-container" style="width:800px; height:600px; border:1px solid #ccc;"></div>
```

### Q3: "NO_AVAILABLE_MACHINE" エラー

**症状**: 本番環境で「利用可能なマシンがありません」

**原因**:
- 全マシンが使用中
- マシン登録がない

**解決方法**:
- しばらく待ってから再試行
- サポートに連絡してマシン追加を依頼

**テスト環境では発生しません**（モックマシンが自動生成されます）

### Q4: CORS エラー

**症状**: ブラウザコンソールに "Cross-Origin" エラー

**原因**: ローカルファイル（`file://`）で実行している

**解決方法**:
```bash
# ローカルサーバーを起動
python3 -m http.server 8000

# ブラウザで http://localhost:8000/game.html を開く
```

---

## サポート

### ドキュメント

- **クイックスタート**: NET8_SDK_QUICKSTART.md
- **API仕様書**: NET8_JAVASCRIPT_SDK_SPEC.md
- **詳細マニュアル**: NET8_SDK_TECHNICAL_MANUAL_v1.01.md
- **完成レポート**: NET8_SDK_BETA_COMPLETION_REPORT.md

### デモサイト

完全に動作するデモ:
https://mgg-webservice-production.up.railway.app/sdk/demo.html

### 管理画面

APIキー管理:
https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php

### お問い合わせ

技術サポート: プロジェクト管理者まで

---

## 付録: 完全なサンプルコード

### シンプル版

```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</head>
<body>
  <div id="game"></div>
  <script>
    Net8.init('pk_demo_12345').then(() => {
      const game = Net8.createGame({ model: 'HOKUTO4GO', container: '#game' });
      game.start();
    });
  </script>
</body>
</html>
```

### 高機能版

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <title>NET8 Game Demo</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 20px;
      background: #1a1a2e;
      color: white;
    }
    #game-container {
      width: 100%;
      max-width: 800px;
      height: 600px;
      margin: 20px auto;
      border: 2px solid #667eea;
      border-radius: 10px;
    }
    .controls {
      text-align: center;
      margin: 20px;
    }
    button {
      padding: 15px 30px;
      font-size: 16px;
      background: #667eea;
      color: white;
      border: none;
      border-radius: 10px;
      cursor: pointer;
      margin: 5px;
    }
    button:hover {
      background: #5568d3;
    }
    #status {
      text-align: center;
      padding: 10px;
      background: #2a2a3e;
      border-radius: 5px;
      margin: 10px auto;
      max-width: 800px;
    }
  </style>
</head>
<body>
  <h1 style="text-align: center;">NET8 Gaming Demo</h1>

  <div class="controls">
    <button onclick="loadModels()">機種一覧取得</button>
    <button onclick="startHokuto()">北斗の拳 起動</button>
    <button onclick="startZenigata()">銭形 起動</button>
    <button onclick="stopGame()">ゲーム終了</button>
  </div>

  <div id="status">
    <p id="status-text">SDKを初期化中...</p>
  </div>

  <div id="game-container"></div>

  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
  <script>
    let currentGame = null;

    function updateStatus(message, isError = false) {
      const statusEl = document.getElementById('status-text');
      statusEl.textContent = message;
      statusEl.style.color = isError ? '#ff6b6b' : '#51cf66';
    }

    // SDK初期化
    (async function initSDK() {
      try {
        await Net8.init('pk_demo_12345');
        updateStatus('✅ SDK初期化完了（テスト環境）');
      } catch (error) {
        updateStatus('❌ SDK初期化エラー: ' + error.message, true);
      }
    })();

    // 機種一覧取得
    async function loadModels() {
      try {
        updateStatus('機種一覧を取得中...');
        const models = await Net8.getModels();
        updateStatus(`✅ ${models.length}機種が利用可能: ` +
          models.map(m => m.name).join(', '));
      } catch (error) {
        updateStatus('❌ エラー: ' + error.message, true);
      }
    }

    // ゲーム起動（共通処理）
    async function startGameModel(modelId, modelName) {
      try {
        if (currentGame) {
          currentGame.stop();
        }

        updateStatus(`${modelName}を起動中...`);

        currentGame = Net8.createGame({
          model: modelId,
          container: '#game-container'
        });

        currentGame.on('ready', () => {
          updateStatus(`✅ ${modelName} 準備完了`);
        });

        currentGame.on('started', () => {
          updateStatus(`✅ ${modelName} プレイ中`);
        });

        currentGame.on('error', (err) => {
          updateStatus(`❌ エラー: ${err.message}`, true);
        });

        await currentGame.start();
      } catch (error) {
        updateStatus(`❌ 起動エラー: ${error.message}`, true);
      }
    }

    // 北斗の拳 起動
    function startHokuto() {
      startGameModel('HOKUTO4GO', '北斗の拳 初号機');
    }

    // 銭形 起動
    function startZenigata() {
      startGameModel('ZENIGATA01', '主役は銭形');
    }

    // ゲーム終了
    function stopGame() {
      if (currentGame) {
        currentGame.stop();
        currentGame = null;
        updateStatus('ゲームを終了しました');
      } else {
        updateStatus('⚠️ 実行中のゲームがありません');
      }
    }
  </script>
</body>
</html>
```

---

**NET8 Gaming SDK v1.01**
© 2025 NET8 Development Team

このマニュアルで不明な点がありましたら、詳細マニュアル（NET8_SDK_TECHNICAL_MANUAL_v1.01.md）を参照してください。
