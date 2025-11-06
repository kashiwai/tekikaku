# NET8 JavaScript SDK - 完全仕様書

**バージョン**: 1.0.0
**最終更新**: 2025-11-06
**対象者**: フロントエンド開発者

---

## 📚 目次

1. [概要](#概要)
2. [インストール](#インストール)
3. [クイックスタート](#クイックスタート)
4. [アーキテクチャ](#アーキテクチャ)
5. [API リファレンス](#apiリファレンス)
6. [高度な使い方](#高度な使い方)
7. [エラーハンドリング](#エラーハンドリング)
8. [TypeScript対応](#typescript対応)
9. [トラブルシューティング](#トラブルシューティング)

---

## 概要

### NET8 JavaScript SDK とは

NET8パチンコ・スロットゲームシステムをWebサイトに簡単に組み込むためのJavaScript SDKです。

**特徴**:
- 🚀 **3行で導入** - `<script>`タグ + 数行のJavaScriptのみ
- 🎨 **デザイン込み** - 美しいUIがそのまま使える
- 🔌 **プラグ&プレイ** - フレームワーク不要（Vanilla JS）
- 📦 **軽量** - gzip後 45KB（WebRTC通信を含む）
- 🌐 **ブラウザ対応** - Chrome, Firefox, Safari, Edge
- 📱 **レスポンシブ** - PC/スマホ/タブレット対応

### システム要件

```yaml
ブラウザ:
  - Chrome 90+
  - Firefox 88+
  - Safari 14+
  - Edge 90+

JavaScript:
  - ES6+ (ES2015)

ネットワーク:
  - WebRTC対応
  - WebSocket対応
  - HTTPS必須
```

---

## インストール

### 方法1: CDN（推奨 - 最速）

```html
<!-- 本番環境 -->
<script src="https://cdn.net8.io/sdk/v1/net8.min.js"></script>

<!-- 開発環境 -->
<script src="https://cdn.net8.io/sdk/v1/net8.js"></script>
```

### 方法2: NPM

```bash
npm install @net8/gaming-sdk
```

```javascript
import Net8 from '@net8/gaming-sdk';

Net8.init('pk_live_xxxxx');
```

### 方法3: Yarn

```bash
yarn add @net8/gaming-sdk
```

---

## クイックスタート

### 最小限の実装（30秒）

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NET8 Gaming Demo</title>
  <script src="https://cdn.net8.io/sdk/v1/net8.min.js"></script>
</head>
<body>
  <!-- ゲームが表示されるコンテナ -->
  <div id="game"></div>

  <script>
    // 1. SDK初期化
    Net8.init('pk_live_xxxxxxxxxx');

    // 2. ゲーム作成
    const game = Net8.createGame({
      type: 'slot',
      model: 'milliongod',
      container: '#game'
    });

    // 3. ゲーム開始
    game.start();
  </script>
</body>
</html>
```

**これだけで動作します！**

---

## アーキテクチャ

### SDK全体構造

```
@net8/gaming-sdk
├── Core
│   ├── Net8 (main class)
│   ├── Config
│   ├── Auth
│   └── EventEmitter
│
├── Game
│   ├── SlotGame
│   ├── PachinkoGame
│   └── GameBase
│
├── UI
│   ├── GameCanvas
│   ├── ControlPanel
│   ├── PointDisplay
│   └── ModalManager
│
├── Network
│   ├── APIClient
│   ├── WebRTCManager
│   └── WebSocketClient
│
└── Utils
    ├── Logger
    ├── Storage
    └── Validator
```

### データフロー

```
User Action (ボタン押下)
    ↓
SlotGame.spin()
    ↓
APIClient.post('/api/game/play')
    ↓
NET8 Backend (Railway)
    ↓
WebRTC Stream (リアルタイム映像)
    ↓
GameCanvas.render()
    ↓
UI Update (結果表示)
```

---

## API リファレンス

### Net8 (Main Class)

#### `Net8.init(apiKey, options)`

SDKを初期化します。**最初に必ず呼び出す必要があります。**

**パラメータ**:
```typescript
apiKey: string  // APIキー（Developer Portalで取得）
options?: {
  environment?: 'production' | 'staging' | 'development',
  logLevel?: 'debug' | 'info' | 'warn' | 'error',
  apiUrl?: string,  // カスタムAPI URL
  signalingUrl?: string,  // カスタムSignaling URL
  locale?: 'ja' | 'en' | 'zh' | 'ko'
}
```

**戻り値**: `void`

**使用例**:
```javascript
// 基本的な初期化
Net8.init('pk_live_xxxxxxxxxx');

// 詳細設定
Net8.init('pk_live_xxxxxxxxxx', {
  environment: 'production',
  logLevel: 'info',
  locale: 'ja'
});
```

---

#### `Net8.createGame(config)`

ゲームインスタンスを作成します。

**パラメータ**:
```typescript
config: {
  type: 'slot' | 'pachinko',  // ゲームタイプ
  model: string,  // 機種名（'milliongod', 'hokuto' 等）
  container: string | HTMLElement,  // 表示先
  theme?: ThemeOptions,  // テーマカスタマイズ
  points?: PointsConfig,  // ポイント設定
  autoPlay?: boolean,  // 自動プレイ
  soundEnabled?: boolean,  // 音声ON/OFF
  fullscreen?: boolean,  // フルスクリーン
  onReady?: () => void,
  onError?: (error: Error) => void
}
```

**戻り値**: `SlotGame | PachinkoGame`

**使用例**:
```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game-container',

  // カスタマイズ
  theme: {
    primaryColor: '#ff6b6b',
    backgroundColor: '#1a1a1a'
  },

  // ポイント管理
  points: {
    initial: 1000,
    onPurchase: async (amount) => {
      // 独自の決済処理
      return await myPaymentGateway.purchase(amount);
    }
  },

  // コールバック
  onReady: () => {
    console.log('Game ready!');
  },

  onError: (error) => {
    console.error('Game error:', error);
  }
});
```

---

### SlotGame Class

スロットゲームを管理するクラス。

#### プロパティ

```typescript
class SlotGame {
  id: string;  // ゲームID
  model: string;  // 機種名
  state: 'idle' | 'playing' | 'spinning' | 'result';  // 現在の状態
  credits: number;  // 現在のクレジット数
  points: number;  // 現在のポイント数
  isConnected: boolean;  // WebRTC接続状態
}
```

#### メソッド

##### `game.start()`

ゲームを開始します。

```javascript
game.start();
```

**戻り値**: `Promise<void>`

**エラー**:
- `NotConnectedError` - サーバーに接続していない
- `InsufficientPointsError` - ポイント不足

---

##### `game.spin(bet)`

スロットを回します。

**パラメータ**:
```typescript
bet: number  // ベット数（1-3）
```

**戻り値**: `Promise<SpinResult>`

```typescript
interface SpinResult {
  success: boolean;
  credits: number;  // 獲得クレジット
  bonusTriggered: boolean;  // ボーナス突入
  jackpot: boolean;  // ジャックポット
  totalWin: number;  // 合計獲得数
  reels: number[][];  // リール結果
}
```

**使用例**:
```javascript
try {
  const result = await game.spin(3);  // 3ベット

  if (result.jackpot) {
    console.log('🎊 JACKPOT!');
  } else if (result.credits > 0) {
    console.log(`Won ${result.credits} credits!`);
  }
} catch (error) {
  console.error('Spin failed:', error);
}
```

---

##### `game.addPoints(amount, method)`

ポイントを追加します。

**パラメータ**:
```typescript
amount: number  // ポイント数
method?: 'credit_card' | 'bank' | 'external'  // 支払い方法
```

**戻り値**: `Promise<AddPointsResult>`

```typescript
interface AddPointsResult {
  success: boolean;
  points: number;  // 追加後のポイント数
  transactionId: string;
}
```

**使用例**:
```javascript
// 1000ポイント追加
const result = await game.addPoints(1000, 'credit_card');
console.log(`New balance: ${result.points}`);
```

---

##### `game.pause()` / `game.resume()`

ゲームを一時停止/再開します。

```javascript
game.pause();   // 一時停止
game.resume();  // 再開
```

---

##### `game.destroy()`

ゲームを終了し、リソースを解放します。

```javascript
await game.destroy();
```

---

#### イベント

`SlotGame`は`EventEmitter`を継承しており、イベントリスナーを登録できます。

##### `game.on(event, handler)`

イベントリスナーを登録します。

**利用可能なイベント**:

```typescript
// ゲーム開始
game.on('start', () => {
  console.log('Game started');
});

// スピン開始
game.on('spin:start', (bet) => {
  console.log(`Spinning with bet: ${bet}`);
});

// スピン終了
game.on('spin:end', (result) => {
  console.log('Spin result:', result);
});

// 勝利
game.on('win', (credits, jackpot) => {
  console.log(`Won ${credits} credits!`);
  if (jackpot) {
    showJackpotAnimation();
  }
});

// ボーナス突入
game.on('bonus:trigger', () => {
  console.log('Bonus triggered!');
});

// ボーナス終了
game.on('bonus:end', (totalWin) => {
  console.log(`Bonus end. Total win: ${totalWin}`);
});

// ポイント変更
game.on('points:change', (newPoints, oldPoints) => {
  console.log(`Points: ${oldPoints} → ${newPoints}`);
});

// エラー
game.on('error', (error) => {
  console.error('Game error:', error);
});

// 接続状態変更
game.on('connection:change', (connected) => {
  console.log(`Connection: ${connected ? 'ON' : 'OFF'}`);
});
```

**完全な例**:
```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game'
});

// イベントリスナー登録
game.on('start', () => {
  console.log('🎮 Game started');
});

game.on('win', (credits, jackpot) => {
  if (jackpot) {
    showJackpotModal(credits);
  } else {
    showWinNotification(credits);
  }
});

game.on('points:change', (newPoints) => {
  updatePointsDisplay(newPoints);
});

game.on('error', (error) => {
  showErrorToast(error.message);
});

// ゲーム開始
game.start();
```

---

### Net8.getModels()

利用可能な機種一覧を取得します。

**戻り値**: `Promise<Model[]>`

```typescript
interface Model {
  id: string;
  name: string;
  category: 'slot' | 'pachinko';
  maker: string;
  releaseDate: string;
  thumbnail: string;
  description: string;
  specs: {
    maxBet: number;
    paylines: number;
    rtp: number;  // Return to Player (%)
  };
}
```

**使用例**:
```javascript
const models = await Net8.getModels();

models.forEach(model => {
  console.log(`${model.name} (${model.maker})`);
});

// フィルタリング
const slotModels = models.filter(m => m.category === 'slot');
```

---

### Net8.getUserInfo()

現在のユーザー情報を取得します。

**戻り値**: `Promise<UserInfo>`

```typescript
interface UserInfo {
  id: string;
  nickname: string;
  points: number;
  level: number;
  totalPlays: number;
  joinDate: string;
}
```

**使用例**:
```javascript
const user = await Net8.getUserInfo();
console.log(`Welcome, ${user.nickname}!`);
console.log(`Points: ${user.points}`);
```

---

## 高度な使い方

### テーマカスタマイズ

```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game',

  theme: {
    // カラー
    primaryColor: '#ff6b6b',
    secondaryColor: '#4ecdc4',
    backgroundColor: '#1a1a1a',
    textColor: '#ffffff',

    // フォント
    fontFamily: '"Noto Sans JP", sans-serif',
    fontSize: {
      small: '12px',
      medium: '16px',
      large: '24px'
    },

    // ボタン
    button: {
      borderRadius: '8px',
      padding: '12px 24px',
      hoverOpacity: 0.8
    },

    // アニメーション
    animation: {
      duration: 300,
      easing: 'ease-in-out'
    },

    // カスタムCSS
    customCSS: `
      .net8-game-container {
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
      }
    `
  }
});
```

---

### ポイント管理の統合

```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game',

  points: {
    // 初期ポイント
    initial: 1000,

    // ポイント購入時の処理
    onPurchase: async (amount) => {
      // 独自の決済システムと統合
      try {
        const payment = await yourPaymentService.createCharge({
          amount: amount * 10,  // 1ポイント = 10円
          currency: 'JPY',
          description: `NET8 ポイント購入 (${amount}P)`
        });

        if (payment.success) {
          return {
            success: true,
            points: amount,
            transactionId: payment.id
          };
        } else {
          throw new Error('Payment failed');
        }
      } catch (error) {
        console.error('Purchase error:', error);
        return {
          success: false,
          error: error.message
        };
      }
    },

    // ポイント不足時の処理
    onInsufficientPoints: (required, current) => {
      const shortfall = required - current;
      showPurchaseModal({
        message: `ポイントが${shortfall}不足しています`,
        suggested: Math.ceil(shortfall / 100) * 100
      });
    }
  }
});
```

---

### 分析・トラッキング統合

```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game'
});

// Google Analytics統合
game.on('start', () => {
  gtag('event', 'game_start', {
    event_category: 'Game',
    event_label: 'milliongod'
  });
});

game.on('spin:end', (result) => {
  gtag('event', 'game_spin', {
    event_category: 'Game',
    event_label: 'milliongod',
    value: result.credits
  });
});

game.on('win', (credits, jackpot) => {
  gtag('event', 'game_win', {
    event_category: 'Game',
    event_label: jackpot ? 'jackpot' : 'normal',
    value: credits
  });
});

// カスタム分析
game.on('spin:end', (result) => {
  yourAnalytics.track('slot_spin', {
    model: 'milliongod',
    bet: result.bet,
    win: result.credits,
    timestamp: Date.now()
  });
});
```

---

### マルチゲーム管理

```javascript
class GameManager {
  constructor() {
    this.games = new Map();
  }

  async createGame(model, containerId) {
    const game = Net8.createGame({
      type: 'slot',
      model: model,
      container: `#${containerId}`
    });

    await game.start();
    this.games.set(containerId, game);

    return game;
  }

  destroyGame(containerId) {
    const game = this.games.get(containerId);
    if (game) {
      game.destroy();
      this.games.delete(containerId);
    }
  }

  destroyAll() {
    this.games.forEach(game => game.destroy());
    this.games.clear();
  }
}

// 使用例
const manager = new GameManager();

// 複数ゲーム同時表示
await manager.createGame('milliongod', 'game1');
await manager.createGame('hokuto', 'game2');

// ゲーム切り替え
manager.destroyGame('game1');
await manager.createGame('evangelion', 'game1');
```

---

### レスポンシブ対応

```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game',

  // レスポンシブ設定
  responsive: {
    breakpoints: {
      mobile: 768,
      tablet: 1024,
      desktop: 1440
    },

    // デバイス別設定
    mobile: {
      layout: 'vertical',
      controls: 'bottom',
      fontSize: 'small'
    },

    desktop: {
      layout: 'horizontal',
      controls: 'right',
      fontSize: 'large'
    }
  }
});

// ウィンドウリサイズ対応
window.addEventListener('resize', () => {
  game.resize();  // 自動で最適化
});
```

---

## エラーハンドリング

### エラータイプ

```typescript
// 認証エラー
class AuthenticationError extends Error {
  code: 'AUTH_001';
  message: 'Invalid API key';
}

// 接続エラー
class ConnectionError extends Error {
  code: 'CONN_001' | 'CONN_002' | 'CONN_003';
  message: 'WebRTC connection failed' | 'WebSocket timeout' | 'Network error';
}

// ポイント不足
class InsufficientPointsError extends Error {
  code: 'POINT_001';
  required: number;
  current: number;
}

// API エラー
class APIError extends Error {
  code: string;
  statusCode: number;
  response: any;
}
```

### エラーハンドリング例

```javascript
try {
  const game = Net8.createGame({
    type: 'slot',
    model: 'milliongod',
    container: '#game'
  });

  await game.start();
  const result = await game.spin(3);

} catch (error) {
  if (error instanceof AuthenticationError) {
    console.error('認証エラー: APIキーを確認してください');

  } else if (error instanceof ConnectionError) {
    console.error('接続エラー: ネットワークを確認してください');
    showReconnectDialog();

  } else if (error instanceof InsufficientPointsError) {
    console.error(`ポイント不足: ${error.required}P必要（現在${error.current}P）`);
    showPurchaseDialog(error.required - error.current);

  } else if (error instanceof APIError) {
    console.error(`APIエラー (${error.statusCode}): ${error.message}`);

  } else {
    console.error('予期しないエラー:', error);
  }
}
```

---

## TypeScript対応

### 型定義

```typescript
// インストール
npm install @net8/gaming-sdk @types/net8__gaming-sdk

// 使用例
import Net8, {
  SlotGame,
  PachinkoGame,
  GameConfig,
  SpinResult,
  Model,
  UserInfo,
  ThemeOptions
} from '@net8/gaming-sdk';

// 型安全な初期化
Net8.init('pk_live_xxxxx');

// 型付きゲーム作成
const config: GameConfig = {
  type: 'slot',
  model: 'milliongod',
  container: '#game',
  theme: {
    primaryColor: '#ff6b6b'
  }
};

const game: SlotGame = Net8.createGame(config);

// 型付きイベント
game.on('win', (credits: number, jackpot: boolean) => {
  console.log(`Won ${credits} credits!`);
});

// 型付きAPI呼び出し
const result: SpinResult = await game.spin(3);
const models: Model[] = await Net8.getModels();
const user: UserInfo = await Net8.getUserInfo();
```

### 型定義一覧

```typescript
// Game Configuration
interface GameConfig {
  type: 'slot' | 'pachinko';
  model: string;
  container: string | HTMLElement;
  theme?: ThemeOptions;
  points?: PointsConfig;
  autoPlay?: boolean;
  soundEnabled?: boolean;
  fullscreen?: boolean;
  responsive?: ResponsiveConfig;
  onReady?: () => void;
  onError?: (error: Error) => void;
}

// Theme Options
interface ThemeOptions {
  primaryColor?: string;
  secondaryColor?: string;
  backgroundColor?: string;
  textColor?: string;
  fontFamily?: string;
  fontSize?: {
    small?: string;
    medium?: string;
    large?: string;
  };
  button?: ButtonTheme;
  animation?: AnimationTheme;
  customCSS?: string;
}

// Spin Result
interface SpinResult {
  success: boolean;
  credits: number;
  bonusTriggered: boolean;
  jackpot: boolean;
  totalWin: number;
  reels: number[][];
  metadata?: Record<string, any>;
}

// Model
interface Model {
  id: string;
  name: string;
  category: 'slot' | 'pachinko';
  maker: string;
  releaseDate: string;
  thumbnail: string;
  description: string;
  specs: ModelSpecs;
}

// User Info
interface UserInfo {
  id: string;
  nickname: string;
  email?: string;
  points: number;
  level: number;
  totalPlays: number;
  totalWins: number;
  joinDate: string;
  lastLoginDate: string;
}
```

---

## トラブルシューティング

### よくある問題と解決策

#### 1. ゲームが表示されない

**原因**:
- APIキーが間違っている
- コンテナ要素が見つからない
- スクリプトの読み込み順序が間違っている

**解決策**:
```javascript
// コンソールでエラー確認
console.log(Net8);  // Net8オブジェクトが存在するか

// コンテナ確認
const container = document.querySelector('#game');
console.log(container);  // nullでないことを確認

// 初期化確認
Net8.init('pk_live_xxxxx', {
  logLevel: 'debug'  // デバッグログ有効化
});
```

---

#### 2. WebRTC接続が失敗する

**原因**:
- HTTPSでない
- ファイアウォールでブロックされている
- ブラウザがWebRTCに対応していない

**解決策**:
```javascript
// ブラウザチェック
if (!Net8.isSupported()) {
  console.error('このブラウザはサポートされていません');
  showUnsupportedBrowserMessage();
}

// 接続状態監視
game.on('connection:change', (connected) => {
  if (!connected) {
    console.error('接続が切断されました');
    showReconnectDialog();
  }
});

// 手動再接続
try {
  await game.reconnect();
} catch (error) {
  console.error('再接続失敗:', error);
}
```

---

#### 3. パフォーマンスが悪い

**原因**:
- 大量のイベントリスナー
- メモリリーク
- アニメーションの負荷

**解決策**:
```javascript
// イベントリスナーの削除
game.off('spin:end', handler);  // 特定のハンドラを削除
game.off('spin:end');  // すべてのハンドラを削除

// ゲームインスタンスの破棄
await game.destroy();

// パフォーマンス設定
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game',
  performance: {
    reducedAnimations: true,  // アニメーション削減
    lowQuality: false,  // 低画質モード
    frameRate: 30  // フレームレート制限
  }
});
```

---

#### 4. ポイント購入が動作しない

**原因**:
- `onPurchase`コールバックが実装されていない
- 決済処理がエラーを返している

**解決策**:
```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game',
  points: {
    onPurchase: async (amount) => {
      try {
        console.log(`Purchasing ${amount} points...`);

        // 決済処理
        const result = await yourPaymentService.purchase(amount);

        console.log('Purchase result:', result);

        return {
          success: result.success,
          points: amount,
          transactionId: result.transactionId
        };

      } catch (error) {
        console.error('Purchase error:', error);

        // エラーをユーザーに通知
        alert(`購入に失敗しました: ${error.message}`);

        return {
          success: false,
          error: error.message
        };
      }
    }
  }
});
```

---

### デバッグモード

```javascript
// デバッグモード有効化
Net8.init('pk_live_xxxxx', {
  logLevel: 'debug',
  environment: 'development'
});

// すべてのイベントをログ出力
game.onAny((event, ...args) => {
  console.log(`[${event}]`, ...args);
});

// パフォーマンス測定
game.on('spin:start', () => {
  performance.mark('spin-start');
});

game.on('spin:end', () => {
  performance.mark('spin-end');
  performance.measure('spin', 'spin-start', 'spin-end');

  const measure = performance.getEntriesByName('spin')[0];
  console.log(`Spin duration: ${measure.duration}ms`);
});
```

---

## サンプルコード集

### 完全な実装例

```html
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NET8 Gaming - Full Example</title>
  <script src="https://cdn.net8.io/sdk/v1/net8.min.js"></script>
  <style>
    body {
      margin: 0;
      padding: 20px;
      font-family: 'Noto Sans JP', sans-serif;
      background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
      color: #fff;
    }

    .container {
      max-width: 1200px;
      margin: 0 auto;
    }

    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .points-display {
      font-size: 24px;
      font-weight: bold;
      padding: 15px 30px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 10px;
    }

    #game-container {
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .controls {
      margin-top: 20px;
      display: flex;
      gap: 10px;
    }

    button {
      padding: 12px 24px;
      font-size: 16px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s;
    }

    button:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
    }

    .btn-secondary {
      background: rgba(255, 255, 255, 0.1);
      color: white;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>🎰 NET8 Gaming</h1>
      <div class="points-display" id="points">
        ポイント: <span id="points-value">0</span>P
      </div>
    </div>

    <div id="game-container"></div>

    <div class="controls">
      <button class="btn-primary" id="btn-spin">スピン</button>
      <button class="btn-secondary" id="btn-add-points">ポイント追加</button>
      <button class="btn-secondary" id="btn-change-model">機種変更</button>
    </div>
  </div>

  <script>
    // SDK初期化
    Net8.init('pk_live_xxxxxxxxxx', {
      logLevel: 'info',
      locale: 'ja'
    });

    // ゲーム作成
    const game = Net8.createGame({
      type: 'slot',
      model: 'milliongod',
      container: '#game-container',

      theme: {
        primaryColor: '#667eea',
        backgroundColor: '#1a1a2e'
      },

      points: {
        initial: 1000,
        onPurchase: async (amount) => {
          // デモ用の簡易決済
          return new Promise((resolve) => {
            setTimeout(() => {
              resolve({
                success: true,
                points: amount,
                transactionId: `demo_${Date.now()}`
              });
            }, 1000);
          });
        }
      }
    });

    // ポイント表示更新
    function updatePointsDisplay(points) {
      document.getElementById('points-value').textContent = points;
    }

    // イベントリスナー
    game.on('start', () => {
      console.log('🎮 ゲーム開始');
      updatePointsDisplay(game.points);
    });

    game.on('points:change', (newPoints) => {
      updatePointsDisplay(newPoints);
    });

    game.on('win', (credits, jackpot) => {
      if (jackpot) {
        alert(`🎊 ジャックポット！ ${credits}クレジット獲得！`);
      } else if (credits > 0) {
        console.log(`✨ ${credits}クレジット獲得！`);
      }
    });

    game.on('error', (error) => {
      console.error('エラー:', error);
      alert(`エラーが発生しました: ${error.message}`);
    });

    // ボタンイベント
    document.getElementById('btn-spin').addEventListener('click', async () => {
      try {
        const result = await game.spin(3);
        console.log('スピン結果:', result);
      } catch (error) {
        console.error('スピン失敗:', error);
      }
    });

    document.getElementById('btn-add-points').addEventListener('click', async () => {
      const amount = prompt('追加するポイント数を入力:', '1000');
      if (amount) {
        try {
          await game.addPoints(parseInt(amount));
          alert(`${amount}ポイント追加しました`);
        } catch (error) {
          console.error('ポイント追加失敗:', error);
        }
      }
    });

    document.getElementById('btn-change-model').addEventListener('click', async () => {
      // 機種一覧取得
      const models = await Net8.getModels();
      const modelList = models.map(m => `${m.id}: ${m.name}`).join('\n');
      const modelId = prompt(`機種IDを入力:\n${modelList}`, 'hokuto');

      if (modelId) {
        await game.destroy();

        const newGame = Net8.createGame({
          type: 'slot',
          model: modelId,
          container: '#game-container'
        });

        await newGame.start();
      }
    });

    // ゲーム開始
    game.start();
  </script>
</body>
</html>
```

---

## まとめ

NET8 JavaScript SDKを使用すると、わずか数行のコードでパチンコ・スロットゲームをWebサイトに組み込むことができます。

**次のステップ**:
1. [NET8_MVP_ROADMAP.md](./NET8_MVP_ROADMAP.md) を読んで実装計画を確認
2. Developer Portalでアカウント作成（https://developers.net8.io）
3. APIキーを取得して実装開始

**サポート**:
- ドキュメント: https://docs.net8.io
- Discord: https://discord.gg/net8
- Email: support@net8.io

---

**前のドキュメント**: [NET8_SDK_QUICKSTART.md](./NET8_SDK_QUICKSTART.md)
**次のドキュメント**: [NET8_MVP_ROADMAP.md](./NET8_MVP_ROADMAP.md)
