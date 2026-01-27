# NET8 SDK 完全統合ガイド
Version: 1.1.0
最終更新: 2025-11-21

## 📋 目次
1. [実装完了機能](#実装完了機能)
2. [統合の概要](#統合の概要)
3. [クイックスタート](#クイックスタート)
4. [詳細仕様](#詳細仕様)
5. [イベントリファレンス](#イベントリファレンス)
6. [エラーハンドリング](#エラーハンドリング)
7. [UIコンポーネント](#uiコンポーネント)
8. [トラブルシューティング](#トラブルシューティング)

---

## 実装完了機能

### ✅ Phase 1: リアルタイム通信実装
**ファイル**: `net8/02.ソースファイル/net8_html/data/play_v2/js/view_auth.js`

ゲーム画面（iframe内）から親ウィンドウ（SDK）へのpostMessage通信を実装。

**追加イベント**:
- `game:ready` - ゲーム準備完了
- `game:play` - プレイ開始（クレジット消費）
- `game:win` - 勝利（クレジット増加）
- `game:bonus` - ボーナス当選（BB/RB）
- `game:score` - スコア更新（全ステータス）
- `game:end` - ゲーム終了

```javascript
// 実装例（view_auth.js内）
function notifySDK(eventType, payload) {
    if (window.parent !== window) {
        window.parent.postMessage({
            type: 'game:' + eventType,
            payload: payload
        }, '*');
    }
}
```

### ✅ Phase 2: SDK イベントリスナー強化
**ファイル**: `net8/02.ソースファイル/net8_html/sdk/net8-sdk-beta.js`

SDKにゲーム状態トラッキング機能を追加。

**新機能**:
- リアルタイムゲーム状態管理 (`gameState`)
- セキュアなpostMessage受信（オリジンチェック強化）
- ゲーム状態取得メソッド (`getGameState()`)

```javascript
// ゲーム状態の構造
gameState = {
    credit: 0,          // 現在のクレジット
    playpoint: 0,       // プレイポイント
    drawpoint: 0,       // 抽選ポイント
    bb_count: 0,        // BB回数
    rb_count: 0,        // RB回数
    total_count: 0,     // 合計当選回数
    isPlaying: false,   // ゲーム中フラグ
    lastUpdate: null    // 最終更新時刻
};
```

### ✅ Phase 3: 決済システム統合
**ファイル**: `net8/02.ソースファイル/net8_html/api/v1/game_end.php`

SDKと既存システム（payAPI.php）の完全統合を実現。

**統合内容**:
1. **仮想メンバー作成**: SDKユーザーに対応する`mst_member`エントリを自動生成
   - Email: `sdk_{partner_user_id}@{partner_name}.net8.local`
   - Invite Code: `SDK_{partner_user_id}`

2. **プレイ履歴記録**: `his_play`テーブルへの記録
   - 機種、開始/終了時刻、ポイント消費/獲得
   - BB/RB回数、プレイ回数

3. **機種統計更新**: `dat_machinePlay`テーブルの更新
   - 総プレイ数、BB/RB回数
   - IN/OUTクレジット

4. **マシン解放**: `lnk_machine`テーブルの更新
   - `assign_flg = 0`でマシンを解放

### ✅ Phase 4: ポイント追加機能
**新規ファイル**: `net8/02.ソースファイル/net8_html/api/v1/add_points.php`

ゲームプレイ中のポイント追加APIを実装。

**機能**:
- セッションベースのポイント追加
- トランザクション履歴記録
- 残高リアルタイム更新

**使用例**:
```javascript
// ゲーム中にボーナスポイントを追加
const result = await game.addPoints(500, 'Bonus campaign reward');
console.log(`New balance: ${result.transaction.balanceAfter}`);
```

**SDK メソッド追加**:
```javascript
async addPoints(amount, description = 'Bonus points during gameplay')
```

### ✅ Phase 5: UIコンポーネントライブラリ
**新規ファイル**: `net8/02.ソースファイル/net8_html/sdk/net8-ui-components.js`

再利用可能なUIコンポーネントを提供。

**コンポーネント一覧**:
1. **機種選択UI** (`showMachineSelector()`)
2. **次ゲーム遷移UI** (`showGameTransition()`)
3. **ローディング表示** (`showLoading()`)
4. **エラーメッセージ** (`showError()`)

**使用例**:
```javascript
const ui = new Net8UI({ language: 'ja' });
ui.injectDefaultStyles();

// 機種選択を表示
ui.showMachineSelector(models, (selectedModel) => {
    console.log('Selected:', selectedModel);
});

// ゲーム終了後の遷移UI
ui.showGameTransition(gameResult,
    () => { /* 次のゲーム */ },
    () => { /* 終了 */ }
);
```

### ✅ Phase 6: エラーハンドリング強化
**ファイル**: `net8/02.ソースファイル/net8_html/sdk/net8-sdk-beta.js`

ネットワークエラー、タイムアウト、リトライ機能を実装。

**追加機能**:
1. **タイムアウト付きfetch** (`_fetchWithTimeout()`)
   - デフォルト30秒タイムアウト
   - AbortControllerを使用

2. **リトライロジック** (`_apiCallWithRetry()`)
   - 最大3回のリトライ
   - Exponential backoff (2^n秒)

3. **エラーメッセージ整形** (`_formatErrorMessage()`)
   - ユーザーフレンドリーなエラー表示

**エラーコード対応表**:
| コード | メッセージ |
|--------|-----------|
| UNAUTHORIZED | 認証に失敗しました。APIキーを確認してください。 |
| INVALID_API_KEY | 無効なAPIキーです。 |
| MODEL_NOT_FOUND | 指定された機種が見つかりません。 |
| NO_AVAILABLE_MACHINE | 利用可能な台がありません。 |
| INSUFFICIENT_BALANCE | ポイント残高が不足しています。 |
| SESSION_NOT_FOUND | ゲームセッションが見つかりません。 |

### ✅ Phase 7: demo.html完全版
**ファイル**: `net8/02.ソースファイル/net8_html/sdk/demo.html`

完全な統合デモを実装。

**追加機能**:
- UI Components統合
- addPoints()テストボタン
- getGameState()表示機能
- リアルタイムイベントログ

---

## 統合の概要

### システムアーキテクチャ

```
┌─────────────────────────────────────────────────────────────┐
│                      Partner Website                        │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                NET8 SDK (JavaScript)                   │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │         Game Container (iframe)                 │  │  │
│  │  │  ┌───────────────────────────────────────────┐  │  │  │
│  │  │  │  Net8 Game (play_v2/view_auth.js)        │  │  │  │
│  │  │  │  - WebRTC to physical machine            │  │  │  │
│  │  │  │  - postMessage to parent                 │  │  │  │
│  │  │  └───────────────────────────────────────────┘  │  │  │
│  │  │           ↑ postMessage Events                  │  │  │
│  │  └─────────────────────────────────────────────────┘  │  │
│  │           │                                            │  │
│  │           ↓ Event Handlers                            │  │
│  │  ┌─────────────────────────────────────────────────┐  │  │
│  │  │  Event Listeners & State Management            │  │  │
│  │  │  - ready, play, win, bonus, score, end         │  │  │
│  │  └─────────────────────────────────────────────────┘  │  │
│  └───────────────────────────────────────────────────────┘  │
│                         │                                   │
│                         ↓ API Calls                         │
└─────────────────────────────────────────────────────────────┘
                          │
                          ↓
┌─────────────────────────────────────────────────────────────┐
│                    NET8 Backend APIs                        │
│                                                              │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐     │
│  │  auth.php    │  │game_start.php│  │ game_end.php │     │
│  │  (JWT発行)   │  │(セッション作成) │  │(決済統合)    │     │
│  └──────────────┘  └──────────────┘  └──────────────┘     │
│                                                              │
│  ┌──────────────┐  ┌──────────────────────────────────┐   │
│  │add_points.php│  │ Legacy System Integration        │   │
│  │(ポイント追加) │  │ - his_play (プレイ履歴)           │   │
│  └──────────────┘  │ - dat_machinePlay (機種統計)      │   │
│                     │ - lnk_machine (台管理)            │   │
│                     └──────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

### データフロー

#### 1. ゲーム開始フロー
```
1. Partner calls: Net8.init(apiKey)
   → POST /api/v1/auth.php
   ← JWT token

2. Partner calls: game.start()
   → POST /api/v1/game_start.php
   ← sessionId, machineNo, playUrl

3. SDK loads iframe with playUrl
   → play_v2/index.php?NO={machineNo}

4. Game connects to WebRTC
   → Physical pachinko machine

5. Game sends: postMessage('game:ready')
   → SDK emits 'ready' event
```

#### 2. ゲームプレイフロー
```
1. User plays game
   → WebRTC dataConnection to machine

2. Game events occur:
   - BB当選 → postMessage('game:bonus', {type:'BB'})
   - クレジット増加 → postMessage('game:win', {credit:10})
   - スコア更新 → postMessage('game:score', {playpoint, drawpoint})

3. SDK updates gameState in real-time

4. Partner can call: game.getGameState()
   ← Current game statistics
```

#### 3. ポイント追加フロー
```
1. Partner calls: game.addPoints(amount, description)
   → POST /api/v1/add_points.php

2. API validates session and user

3. Transaction recorded:
   - user_balances テーブル更新
   - point_transactions に記録

4. SDK emits: 'pointsAdded' event
```

#### 4. ゲーム終了フロー
```
1. Game completes
   → postMessage('game:end', {result, pointsWon})

2. SDK calls: POST /api/v1/game_end.php

3. Backend processes:
   - SDK system: game_sessions, point_transactions
   - Legacy system: his_play, dat_machinePlay, lnk_machine

4. SDK emits: 'end' event with full result

5. Partner can show:
   - Next game transition UI
   - Result summary
   - Recommended machines
```

---

## クイックスタート

### 最小限の実装（3行）

```html
<!DOCTYPE html>
<html>
<head>
    <title>My Game Site</title>
</head>
<body>
    <div id="game-container" style="width:100%; height:800px;"></div>

    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
    <script>
        // 1. SDK初期化
        await Net8.init('pk_demo_12345');

        // 2. ゲーム作成
        const game = Net8.createGame({
            model: 'milliongod',
            container: '#game-container',
            userId: 'user123'  // パートナー側のユーザーID（オプション）
        });

        // 3. ゲーム開始
        await game.start();
    </script>
</body>
</html>
```

### 完全な実装例

```javascript
// SDK初期化
await Net8.init('YOUR_API_KEY', {
    apiUrl: 'https://your-api-domain.com'  // オプション
});

// 機種一覧を取得
const models = await Net8.getModels();
console.log('Available models:', models);

// ゲーム作成
const game = Net8.createGame({
    model: 'milliongod',
    container: '#game-container',
    userId: 'user_12345'
});

// イベントリスナー登録
game.on('ready', () => {
    console.log('Game ready!');
});

game.on('play', (data) => {
    console.log('Game started:', data);
});

game.on('win', (data) => {
    console.log('Win!', data);
    // ボーナスポイントを追加
    game.addPoints(100, 'Congratulations bonus!');
});

game.on('bonus', (data) => {
    console.log('Bonus activated:', data);
});

game.on('score', (data) => {
    console.log('Score updated:', data);
    // リアルタイムでゲーム状態を取得
    const state = game.getGameState();
    console.log('Current state:', state);
});

game.on('end', (result) => {
    console.log('Game ended:', result);

    // 次ゲーム遷移UIを表示
    const ui = new Net8UI();
    ui.injectDefaultStyles();
    ui.showGameTransition(result,
        () => { /* 次のゲーム */ },
        () => { /* 終了 */ }
    );
});

game.on('error', (error) => {
    console.error('Game error:', error);
});

// ゲーム開始
await game.start();
```

---

## 詳細仕様

### Net8SDK クラス

#### Methods

##### `init(apiKey, options)`
SDK を初期化します。

**Parameters:**
- `apiKey` (string): APIキー（必須、`pk_`で始まる）
- `options` (object): オプション設定
  - `apiUrl` (string): APIのベースURL（デフォルト: production URL）

**Returns:** Promise<void>

**Example:**
```javascript
await Net8.init('pk_demo_12345', {
    apiUrl: 'https://custom-api-domain.com'
});
```

##### `getModels()`
利用可能な機種一覧を取得します。

**Returns:** Promise<Array>

**Example:**
```javascript
const models = await Net8.getModels();
// [{id: 'milliongod', name: 'ミリオンゴッド', ...}, ...]
```

##### `createGame(config)`
ゲームインスタンスを作成します。

**Parameters:**
- `config` (object):
  - `model` (string): 機種ID（必須）
  - `container` (string|HTMLElement): ゲームコンテナ（必須）
  - `userId` (string): ユーザーID（オプション、ポイント管理に必要）

**Returns:** Net8Game

---

### Net8Game クラス

#### Methods

##### `start()`
ゲームを開始します。

**Returns:** Promise<void>

##### `stop()`
ゲームを手動終了します。

**Returns:** Promise<void>

##### `destroy()`
ゲームインスタンスを破棄します。

**Returns:** void

##### `addPoints(amount, description)`
ゲームプレイ中にポイントを追加します。

**Parameters:**
- `amount` (number): 追加するポイント数（必須、正の整数）
- `description` (string): 追加理由（オプション）

**Returns:** Promise<Object>

**Example:**
```javascript
const result = await game.addPoints(500, 'Bonus campaign');
// { success: true, transaction: {...}, ... }
```

##### `getGameState()`
現在のゲーム状態を取得します。

**Returns:** Object

**Example:**
```javascript
const state = game.getGameState();
// {
//   credit: 150,
//   playpoint: 1000,
//   drawpoint: 500,
//   bb_count: 2,
//   rb_count: 5,
//   total_count: 7,
//   isPlaying: true,
//   lastUpdate: 1700000000000,
//   sessionId: 'gs_...',
//   machineNo: 123
// }
```

##### `on(event, handler)`
イベントリスナーを登録します。

**Parameters:**
- `event` (string): イベント名
- `handler` (function): イベントハンドラー

##### `off(event, handler)`
イベントリスナーを削除します。

---

## イベントリファレンス

### ready
ゲームが準備完了した時に発火します。

**Payload:**
```javascript
{
    timestamp: 1700000000000,
    machineNo: 123
}
```

### play
プレイが開始された時（クレジット消費時）に発火します。

**Payload:**
```javascript
{
    credit: 99,  // 残りクレジット
    action: 'spin'
}
```

### win
勝利した時（クレジット増加時）に発火します。

**Payload:**
```javascript
{
    credit: 101,  // 新しいクレジット
    addCredit: 2  // 追加されたクレジット
}
```

### bonus
ボーナスに当選した時に発火します。

**Payload:**
```javascript
{
    type: 'BB',  // 'BB' or 'RB'
    count: 3,    // BB/RB回数
    totalBonus: 8  // 合計ボーナス回数
}
```

### score
スコアが更新された時に発火します（払い出し完了後）。

**Payload:**
```javascript
{
    credit: 150,
    playpoint: 1000,
    drawpoint: 500,
    bb_count: 2,
    rb_count: 5
}
```

### end
ゲームが終了した時に発火します。

**Payload:**
```javascript
{
    sessionId: 'gs_...',
    result: 'completed',  // 'completed', 'error', 'cancelled'
    pointsConsumed: 100,
    pointsWon: 250,
    netProfit: 150,
    newBalance: 1500,
    transaction: {
        id: 'txn_...',
        amount: 250,
        balanceBefore: 1250,
        balanceAfter: 1500
    }
}
```

### pointsAdded
ポイントが追加された時に発火します。

**Payload:**
```javascript
{
    amount: 500,
    transaction: {
        id: 'txn_...',
        balanceBefore: 1000,
        balanceAfter: 1500
    },
    description: 'Bonus campaign reward'
}
```

### error
エラーが発生した時に発火します。

**Payload:**
```javascript
{
    error: 'INSUFFICIENT_BALANCE',
    message: 'ポイント残高が不足しています。',
    ...errorDetails
}
```

---

## エラーハンドリング

### エラー処理の基本

```javascript
try {
    await game.start();
} catch (error) {
    // SDK内部でフォーマットされたエラー
    console.error('Error:', error.message);

    // UIでエラーを表示
    const ui = new Net8UI();
    ui.showError(error.message, () => {
        // エラーダイアログを閉じた後の処理
    });
}
```

### リトライ設定

SDKは自動的にネットワークエラーをリトライしますが、カスタマイズも可能です。

```javascript
// SDK内部の_apiCallWithRetryメソッドがデフォルトで使用されます
// - 最大3回のリトライ
// - Exponential backoff (1秒、2秒、4秒)
// - タイムアウト: 30秒
```

### タイムアウト処理

```javascript
// fetch呼び出しは自動的に30秒でタイムアウトします
// AbortControllerを使用してキャンセル可能
```

---

## UIコンポーネント

### Net8UI クラス

#### 初期化

```javascript
const ui = new Net8UI({
    language: 'ja',  // 'ja' or 'en'
    theme: 'default',
    container: document.body
});

// デフォルトスタイルを注入
ui.injectDefaultStyles();
```

#### 機種選択UI

```javascript
const models = await Net8.getModels();

ui.showMachineSelector(models, (selectedModel) => {
    console.log('Selected:', selectedModel);
    // ゲームを開始
    startGame(selectedModel.id);
});
```

#### 次ゲーム遷移UI

```javascript
ui.showGameTransition(gameResult,
    // 次のゲーム
    () => {
        console.log('Play again');
        startNextGame();
    },
    // 終了
    () => {
        console.log('Exit');
        cleanup();
    }
);
```

#### ローディング表示

```javascript
const loader = ui.showLoading('ゲームを読み込み中...');

// ローディング完了後に削除
setTimeout(() => {
    loader.remove();
}, 3000);
```

#### エラーメッセージ

```javascript
ui.showError('エラーが発生しました。', () => {
    console.log('Error dialog closed');
});
```

---

## トラブルシューティング

### よくある問題

#### 1. postMessageが受信されない

**原因**: iframeのオリジンチェックに失敗している可能性があります。

**解決方法**:
```javascript
// SDKのセキュリティチェックを確認
// 同一オリジンまたは信頼されたドメインからのメッセージのみ受信
```

#### 2. ゲーム開始時に "No available machine" エラー

**原因**: 実機環境で利用可能な台がない、またはテスト環境設定が不正です。

**解決方法**:
```javascript
// テスト環境では自動的にモックマシンが使用されます
// APIキーの環境設定を確認: pk_test_xxx または pk_demo_xxx
```

#### 3. ポイント追加が失敗する

**原因**: ゲームセッションがactiveでない、またはuserIdが設定されていません。

**解決方法**:
```javascript
// ゲーム作成時にuserIdを指定
const game = Net8.createGame({
    model: 'milliongod',
    container: '#game-container',
    userId: 'user123'  // 必須
});
```

#### 4. タイムアウトエラーが頻発する

**原因**: ネットワークが不安定、またはAPIサーバーが応答していません。

**解決方法**:
- ネットワーク接続を確認
- APIサーバーの稼働状況を確認
- リトライロジックが自動的に動作します（最大3回）

---

## まとめ

### 実装されたフロー

1. **SDK初期化** → JWT認証
2. **機種選択** → モデル一覧取得
3. **ゲーム開始** → マシン割り当て、ポイント消費
4. **リアルタイムプレイ** → WebRTC + postMessage イベント
5. **ポイント追加**（オプション） → ゲーム中のボーナス
6. **ゲーム終了** → 決済処理、履歴記録、機種統計更新
7. **次ゲーム遷移** → UIコンポーネント表示

### 技術スタック

- **Frontend**: JavaScript ES6+, postMessage API
- **Backend**: PHP 7.4+, MySQL 8.0
- **Real-time**: WebRTC, PeerJS
- **Authentication**: JWT
- **Deployment**: Railway, Docker

### サポート

技術的な質問や問題が発生した場合は、Net8開発チームまでお問い合わせください。
