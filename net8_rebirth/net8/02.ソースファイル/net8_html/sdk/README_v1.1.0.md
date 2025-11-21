# NET8 Gaming SDK v1.1.0 Beta

## 🎮 概要

NET8 Gaming SDKは、わずか3行のコードでオンラインパチンコ・スロットゲームをあなたのウェブサイトに統合できるJavaScript SDKです。

### 🆕 v1.1.0の新機能

- ✅ **ユーザーID連携** - パートナー側のユーザーIDでゲームプレイ管理
- ✅ **ポイント管理** - 自動的なポイント消費・獲得・残高管理
- ✅ **ゲーム終了イベント** - 詳細な結果データを取得
- ✅ **手動終了機能** - `game.stop()` でゲームを終了
- ✅ **プレイ履歴API** - ユーザーのプレイ履歴を取得

---

## 🚀 クイックスタート

### 1. SDKの読み込み

```html
<script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
```

### 2. 初期化とゲーム起動（ユーザーID付き）

```html
<div id="game-container" style="width:800px; height:600px;"></div>

<script>
  (async function() {
    // SDK初期化
    await Net8.init('YOUR_API_KEY');

    // ゲーム作成と起動（ユーザーID付き）
    const game = Net8.createGame({
      model: 'HOKUTO4GO',
      userId: 'your_user_12345',  // ← NEW in v1.1.0
      container: '#game-container'
    });

    // ゲーム終了イベントをリッスン
    game.on('end', (result) => {
      console.log('ゲーム終了！');
      console.log('獲得ポイント:', result.pointsWon);
      console.log('純利益:', result.netProfit);
      console.log('新残高:', result.newBalance);
    });

    await game.start();
  })();
</script>
```

---

## 📦 API リファレンス

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
  - `apiUrl` (string): カスタムAPIエンドポイント

**戻り値**: `Promise<void>`

---

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

**戻り値**: `Promise<Array<Model>>`

---

### Net8.createGame(config)

ゲームインスタンスを作成します。

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'your_user_12345',  // NEW in v1.1.0
  container: '#game-container'
});
```

**パラメータ**:
- `config.model` (string, 必須): ゲーム機種ID
- `config.userId` (string, オプション): パートナー側のユーザーID
- `config.container` (string|HTMLElement, 必須): ゲームを表示するコンテナ

**戻り値**: `Net8Game`

**重要**: `userId` を指定すると、以下の機能が有効になります：
- ポイント自動消費・払い出し
- プレイ履歴の記録
- ユーザー残高管理

---

### game.start()

ゲームを開始します。

```javascript
await game.start();
```

**戻り値**: `Promise<void>`

**処理内容**:
1. バックエンドAPIでゲームセッション作成
2. ユーザーIDが指定されている場合、ポイント消費
3. ゲーム画面をiFrameで表示
4. `ready` および `started` イベントを発火

---

### game.on(event, callback)

ゲームイベントをリッスンします。

```javascript
game.on('ready', () => {
  console.log('ゲーム準備完了！');
});

game.on('started', (data) => {
  console.log('ゲーム開始！消費ポイント:', data.pointsConsumed);
});

game.on('end', (result) => {
  console.log('ゲーム終了！');
  console.log('獲得ポイント:', result.pointsWon);
  console.log('純利益:', result.netProfit);
  console.log('新残高:', result.newBalance);
});

game.on('error', (error) => {
  console.error('エラー:', error);
});
```

**イベント一覧**:

| イベント | 説明 | ペイロード |
|---------|------|----------|
| `ready` | ゲームの準備が完了 | なし |
| `started` | ゲームが開始された（v1.1.0） | `{ sessionId, machineNo, pointsConsumed }` |
| `play` | プレイ中のイベント | 実装依存 |
| `win` | 勝利イベント | 実装依存 |
| `lose` | 敗北イベント | 実装依存 |
| `bonus` | ボーナスイベント | 実装依存 |
| `score` | スコア更新 | 実装依存 |
| `end` | ゲームが終了した（v1.1.0） | `{ sessionId, result, pointsConsumed, pointsWon, netProfit, newBalance }` |
| `error` | エラーが発生 | `Error` オブジェクト |

---

### game.stop() 🆕

ゲームを手動で終了します（v1.1.0で追加）。

```javascript
await game.stop();
```

**戻り値**: `Promise<void>`

**処理内容**:
1. ゲーム終了APIを呼び出し
2. セッションを「キャンセル」として記録
3. リソースをクリーンアップ
4. `end` イベントを発火

---

### game.destroy()

ゲームインスタンスを破棄します。

```javascript
game.destroy();
```

**戻り値**: `void`

**処理内容**:
- イベントリスナーを削除
- iFrameを削除
- メモリをクリーンアップ

**注意**: 通常は `game.stop()` を使用してください。

---

## 🆕 v1.1.0 新機能詳細

### 1. ユーザーID連携

パートナー側のユーザーIDを指定することで、ユーザー単位のゲーム管理が可能になります。

```javascript
const game = Net8.createGame({
  model: 'HOKUTO4GO',
  userId: 'partner_user_12345',  // あなたのシステムのユーザーID
  container: '#game-container'
});
```

**自動処理**:
- 初回プレイ時に自動的にユーザーが登録されます
- デフォルトで10,000ポイントが付与されます
- 以降のプレイでは既存ユーザーとして扱われます

---

### 2. ポイント管理

ゲーム開始時に自動的にポイントが消費され、勝利時に払い出されます。

```javascript
game.on('started', (data) => {
  // ゲーム開始時
  console.log('消費ポイント:', data.pointsConsumed);  // 例: 100
});

game.on('end', (result) => {
  // ゲーム終了時
  console.log('獲得ポイント:', result.pointsWon);      // 例: 350
  console.log('純利益:', result.netProfit);            // 例: 250
  console.log('新残高:', result.newBalance);           // 例: 10150
});
```

**ポイント不足時**:
```javascript
game.on('error', (error) => {
  if (error.message.includes('INSUFFICIENT_BALANCE')) {
    alert('ポイントが不足しています');
  }
});
```

---

### 3. ゲーム終了イベント

ゲーム終了時に詳細な結果データを取得できます。

```javascript
game.on('end', (result) => {
  console.log('セッションID:', result.sessionId);
  console.log('結果:', result.result);  // 'win', 'lose', 'draw', 'cancelled', 'error'
  console.log('消費ポイント:', result.pointsConsumed);
  console.log('獲得ポイント:', result.pointsWon);
  console.log('純利益:', result.netProfit);
  console.log('新残高:', result.newBalance);
});
```

**結果の種類**:
- `win` - 勝利
- `lose` - 敗北
- `draw` - 引き分け
- `cancelled` - キャンセル（手動終了）
- `error` - エラー

---

## 🔑 APIキーの取得

1. [管理画面](https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php)にアクセス
2. 新しいAPIキーを発行
3. テスト環境には `pk_demo_12345` を使用可能

### APIキーの種類

| 種類 | プレフィックス | 用途 |
|-----|--------------|------|
| テスト | `pk_demo_*` | 開発・テスト環境 |
| ステージング | `pk_staging_*` | 検証環境 |
| 本番 | `pk_live_*` | 本番環境 |

---

## 💡 完全な実装例

### 基本的な実装

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
    .stats {
      max-width: 800px;
      margin: 0 auto;
      padding: 20px;
      background: #2a2a3e;
      border-radius: 10px;
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
    button:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }
  </style>
</head>
<body>
  <h1 style="text-align: center;">NET8 Gaming Demo v1.1.0</h1>

  <!-- ユーザー情報 -->
  <div class="stats">
    <h3>👤 ユーザー情報</h3>
    <p>ユーザーID: <strong id="user-id">-</strong></p>
    <p>残高: <strong id="balance">-</strong> ポイント</p>
  </div>

  <!-- コントロール -->
  <div class="controls">
    <button onclick="initSDK()">SDK初期化</button>
    <button onclick="loadModels()">機種一覧取得</button>
    <button onclick="startGame('HOKUTO4GO')">北斗の拳 起動</button>
    <button onclick="startGame('ZENIGATA01')">銭形 起動</button>
    <button onclick="stopGame()" id="stop-btn" disabled>ゲーム終了</button>
  </div>

  <!-- ゲームコンテナ -->
  <div id="game-container"></div>

  <!-- ログ -->
  <div class="stats">
    <h3>📋 ログ:</h3>
    <pre id="log" style="max-height: 300px; overflow-y: auto;"></pre>
  </div>

  <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
  <script>
    let currentGame = null;
    const userId = 'demo_user_' + Math.random().toString(36).substr(2, 9);

    document.getElementById('user-id').textContent = userId;

    function log(message) {
      const logEl = document.getElementById('log');
      const timestamp = new Date().toLocaleTimeString();
      logEl.textContent += `[${timestamp}] ${message}\n`;
      logEl.scrollTop = logEl.scrollHeight;
    }

    async function initSDK() {
      try {
        log('🚀 SDK初期化中...');
        await Net8.init('pk_demo_12345');
        log('✅ SDK初期化完了 (v' + Net8.version + ')');
      } catch (error) {
        log(`❌ エラー: ${error.message}`);
      }
    }

    async function loadModels() {
      try {
        log('📋 機種一覧取得中...');
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
          log('⏹️ 既存のゲームを停止中...');
          await currentGame.stop();
        }

        log(`🎮 ゲーム起動中: ${modelId} (ユーザー: ${userId})`);

        currentGame = Net8.createGame({
          model: modelId,
          userId: userId,  // ユーザーID指定
          container: '#game-container'
        });

        // イベントリスナー設定
        currentGame.on('ready', () => {
          log('✅ ゲーム準備完了');
        });

        currentGame.on('started', (data) => {
          log('🎯 ゲーム開始！');
          log(`   消費ポイント: ${data.pointsConsumed}P`);
          document.getElementById('stop-btn').disabled = false;
        });

        currentGame.on('end', (result) => {
          log('🏁 ゲーム終了！');
          log(`   結果: ${result.result}`);
          log(`   消費: ${result.pointsConsumed}P`);
          log(`   獲得: ${result.pointsWon}P`);
          log(`   純利益: ${result.netProfit}P`);
          log(`   新残高: ${result.newBalance}P`);

          // UI更新
          document.getElementById('balance').textContent = result.newBalance;
          document.getElementById('stop-btn').disabled = true;

          // 結果表示
          const profit = result.netProfit >= 0 ? '+' + result.netProfit : result.netProfit;
          alert(`ゲーム終了！\n\n獲得: ${result.pointsWon}P\n純利益: ${profit}P\n新残高: ${result.newBalance}P`);
        });

        currentGame.on('error', (err) => {
          log(`❌ ゲームエラー: ${err.message}`);
          document.getElementById('stop-btn').disabled = true;
        });

        await currentGame.start();
        log(`✅ ${modelId} 起動成功`);

      } catch (error) {
        log(`❌ エラー: ${error.message}`);
        document.getElementById('stop-btn').disabled = true;
      }
    }

    async function stopGame() {
      if (!currentGame) {
        log('⚠️ ゲームが起動していません');
        return;
      }

      try {
        log('⏹️ ゲーム終了中...');
        await currentGame.stop();
        currentGame = null;
      } catch (error) {
        log(`❌ エラー: ${error.message}`);
      }
    }

    // ページ読み込み時に自動初期化
    window.addEventListener('load', () => {
      log('📄 ページ読み込み完了');
      log('「SDK初期化」ボタンをクリックして開始してください');
    });
  </script>
</body>
</html>
```

---

## 🔒 セキュリティ

### APIキーの管理

- APIキーは環境変数やサーバー側の設定で管理してください
- 公開されるクライアントコードにはテスト用のキー（`pk_demo_*`）のみを使用
- 本番環境では `pk_live_*` キーを使用し、サーバー側でプロキシ経由でアクセス

### iFrame埋め込み許可

v1.1.0では、管理画面でドメインを登録することでiFrame埋め込みを制御できます。

1. [パートナードメイン管理画面](https://net8.jp/data/xxxadmin/partner_domains.php)にアクセス
2. あなたのAPIキーを選択
3. 許可するドメイン（例: `https://your-site.com`）を追加

**登録されていないドメインからの埋め込みは自動的にブロックされます。**

---

## 📚 ドキュメント

- [デプロイガイド](../NET8_SDK_BETA_DEPLOYMENT_GUIDE.md)
- [SDK仕様](../NET8_JAVASCRIPT_SDK_SPEC.md)
- [UXガイド](../NET8_SDK_USER_EXPERIENCE_GUIDE.md)
- [実装完了レポート](../SDK_EXTENSION_IMPLEMENTATION_COMPLETE.md) 🆕

---

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

### "INSUFFICIENT_BALANCE" エラー

ポイント不足です。ユーザーのポイントをチャージしてください。

```javascript
// エラーハンドリング例
game.on('error', (error) => {
  if (error.message.includes('INSUFFICIENT_BALANCE')) {
    alert('ポイントが不足しています。チャージしてください。');
    // チャージページへリダイレクト
    window.location.href = '/charge';
  }
});
```

### "NO_AVAILABLE_MACHINE" エラー

これは正常なレスポンスです。データベースに利用可能なマシンが登録されていない場合に返されます。

---

## 🔄 次ゲーム遷移（Game Transition）

### 概要

v1.1.0では、ゲーム終了後のユーザー体験を向上させる「次ゲーム遷移」機能を提供しています。この機能により、ゲーム終了時に以下を自動で処理できます：

- ✅ ゲーム結果の表示
- ✅ 残高チェック
- ✅ 営業時間チェック
- ✅ 推奨機種の表示
- ✅ 次のゲームへのスムーズな遷移

---

### GameTransitionManager

`Net8.createTransitionManager()` を使用して、次ゲーム遷移マネージャーを作成できます。

```javascript
// 次ゲーム遷移マネージャーを作成
const transitionManager = Net8.createTransitionManager();

// ゲーム終了時の処理
game.on('end', async (data) => {
    const modalData = await transitionManager.handleGameEnd(data, {
        // 推奨機種から選択したとき
        onSelectModel: (modelId) => {
            console.log('次のゲーム:', modelId);
            game.destroy();
            startNewGame(modelId);
        },

        // 全機種を見るを選択したとき
        onViewAll: () => {
            console.log('機種一覧を表示');
            game.destroy();
            showModelSelector();
        },

        // 終了を選択したとき
        onExit: () => {
            console.log('ゲームを終了');
            game.destroy();
            window.location.href = '/';
        },

        // チャージボタンをクリックしたとき
        onCharge: () => {
            console.log('チャージページへ');
            window.location.href = '/charge';
        }
    });

    // modalDataを使ってUIを表示
    showTransitionModal(modalData);
});
```

---

### modalData の構造

`handleGameEnd()` が返す `modalData` には以下の構造があります：

#### 1. 結果表示（通常）

```javascript
{
    type: 'result_with_recommendations',
    title: 'ゲーム終了',
    result: {
        pointsWon: 500,
        pointsConsumed: 100,
        netProfit: 400,
        balance: 10800
    },
    recommendations: [
        {
            id: 'MILLIONGOD01',
            name: 'ミリオンゴッド',
            category: 'slot',
            minPoints: 100,
            canPlay: true,
            availability: {
                total: 5,
                available: 3,
                isAvailable: true
            },
            recommended: true
        },
        // ... 最大3機種
    ],
    actions: [...]
}
```

#### 2. ポイント不足

```javascript
{
    type: 'insufficient_balance',
    title: 'ポイント不足',
    message: 'プレイに必要なポイントが不足しています',
    balance: 50,
    required: 100,
    actions: [
        { label: 'チャージ', type: 'primary', onClick: Function },
        { label: '終了', type: 'secondary', onClick: Function }
    ]
}
```

#### 3. 営業時間外

```javascript
{
    type: 'closed',
    title: '営業時間外',
    message: '現在は営業時間外です\n次回営業時間: 10:00',
    actions: [
        { label: '閉じる', type: 'primary', onClick: Function }
    ]
}
```

---

### UI実装例

`modalData` を使ったUIの実装例：

```javascript
function showTransitionModal(modalData) {
    if (modalData.type === 'result_with_recommendations') {
        // 結果と推奨機種を表示
        const result = modalData.result;
        const recommendations = modalData.recommendations;

        const html = `
            <div class="modal">
                <h2>${modalData.title}</h2>
                <div class="result">
                    <p>獲得ポイント: ${result.pointsWon}pt</p>
                    <p>消費ポイント: ${result.pointsConsumed}pt</p>
                    <p>純利益: ${result.netProfit}pt</p>
                    <p>残高: ${result.balance}pt</p>
                </div>

                <h3>おすすめの機種</h3>
                <div class="recommendations">
                    ${recommendations.map(model => `
                        <div class="model-card" onclick="selectModel('${model.id}')">
                            <h4>${model.name}</h4>
                            <p>最低 ${model.minPoints}pt</p>
                            <p>${model.availability.available}/${model.availability.total}台空き</p>
                        </div>
                    `).join('')}
                </div>

                <button onclick="handleViewAll()">全機種を見る</button>
                <button onclick="closeModal()">終了</button>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', html);
    }
}
```

完全な実装例は [demo.html](./demo.html) を参照してください。

---

### 設定のカスタマイズ

営業時間や最低プレイポイントをカスタマイズできます：

```javascript
const transitionManager = Net8.createTransitionManager();

// 営業時間を設定
transitionManager.setBusinessHours('09:00', '23:00');

// 最低プレイポイントを設定
transitionManager.setMinPlayPoints(200);
```

---

### 推奨機種API

推奨機種は `GET /api/v1/recommended_models.php` から取得されます：

**リクエスト例**:
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
  "https://mgg-webservice-production.up.railway.app/api/v1/recommended_models.php?balance=1000&limit=3"
```

**レスポンス例**:
```json
{
    "success": true,
    "balance": 1000,
    "count": 3,
    "models": [
        {
            "id": "MILLIONGOD01",
            "name": "ミリオンゴッド",
            "category": "slot",
            "minPoints": 100,
            "canPlay": true,
            "availability": {
                "total": 5,
                "available": 3,
                "isAvailable": true
            },
            "recommended": true
        }
    ]
}
```

---

## 🎯 デモページ

完全に動作するデモページ: [https://mgg-webservice-production.up.railway.app/sdk/demo.html](https://mgg-webservice-production.up.railway.app/sdk/demo.html)

---

## 📊 v1.1.0 変更履歴

### 追加機能
- ✅ `createGame()` に `userId` パラメータ追加
- ✅ `game.on('started')` イベント追加
- ✅ `game.on('end')` イベントの詳細データ拡充
- ✅ `game.stop()` メソッド追加
- ✅ 自動ポイント消費・払い出し機能
- ✅ ユーザー自動登録機能

### 変更点
- SDK バージョン: `1.0.1-beta` → `1.1.0-beta`
- ゲーム開始時にポイント消費を実行
- ゲーム終了時にポイント払い出しを実行
- セッション管理の強化

### 互換性
- `userId` パラメータは**オプション**です
- 既存のコード（userId なし）も引き続き動作します
- ただし、ポイント管理機能は `userId` 指定時のみ有効です

---

## 💬 サポート

技術的な質問やバグ報告は、プロジェクト管理者にお問い合わせください。

---

**NET8 Gaming SDK v1.1.0 Beta**
© 2025 NET8 Development Team

**最終更新**: 2025-11-18
