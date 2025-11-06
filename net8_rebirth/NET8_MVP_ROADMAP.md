# NET8 SDK - MVP実装ロードマップ（3ヶ月計画）

**作成日**: 2025-11-06
**目標**: 3ヶ月で商用ローンチ可能なSDKを完成させる
**対象**: 開発チーム（フルスタック 1-2名）

---

## 🎯 プロジェクト目標

### MVP（Minimum Viable Product）の定義
```
✅ JavaScript SDK（CDN配信）
✅ 1機種以上が動作
✅ APIキー認証システム
✅ Developer Portal（基本機能）
✅ ドキュメント（最小限）
✅ デモサイト
```

### 成功指標
- **Week 12終了時点で**:
  - 外部企業1社がテスト導入可能
  - デモサイトで全機能が動作
  - APIレスポンスタイム < 200ms
  - SDK読み込み時間 < 1秒

---

## 📅 全体スケジュール

```
Month 1: 基盤構築 + MVP開発
├── Week 1-2: API層の設計・実装
├── Week 3-4: JavaScript SDK Core開発
└── 成果物: 動作するMVP（1機種）

Month 2: 商用化準備
├── Week 5-6: 複数機種対応 + UI強化
├── Week 7-8: Developer Portal + 認証システム
└── 成果物: ベータ版リリース

Month 3: スケールアップ + ローンチ準備
├── Week 9-10: React/Vue Components
├── Week 11-12: 課金システム + 最終調整
└── 成果物: 商用ローンチ
```

---

## 📋 Month 1: 基盤構築（Week 1-4）

### Week 1: API設計 + データベース準備

#### Day 1-2: システム分析
```bash
タスク:
□ 既存PHPコードの分析
  - /net8/02.ソースファイル/net8_html/data/play_v2/
  - WebRTC接続フロー確認
  - ポイント管理システム確認

□ データベーススキーマ確認
  - mst_member（会員）
  - mst_model（機種）
  - dat_machine（マシン）
  - lnk_machine（接続状態）
  - his_play（プレイ履歴）

□ API設計ドキュメント作成
  - エンドポイント一覧
  - リクエスト/レスポンス形式
  - 認証フロー
```

**成果物**:
- `API_DESIGN.md` (50ページ)
- データベースER図

---

#### Day 3-5: API開発環境セットアップ

```bash
タスク:
□ API Gatewayのセットアップ
  # Tyk（オープンソース）を使用
  docker run -p 8080:8080 tykio/tyk-gateway

□ Node.js APIサーバーセットアップ
  mkdir net8-api-server
  cd net8-api-server
  npm init -y
  npm install express cors jsonwebtoken mysql2 dotenv

□ 既存PHPとの統合テスト
  - WebRTC Signaling接続確認
  - データベース接続確認

□ CORS設定
  # 開発環境用
  Access-Control-Allow-Origin: http://localhost:3000

  # 本番環境用（ホワイトリスト）
  許可ドメイン管理システム構築
```

**ディレクトリ構造**:
```
net8_rebirth/
├── api-server/          # 新規作成
│   ├── src/
│   │   ├── routes/
│   │   │   ├── auth.js
│   │   │   ├── games.js
│   │   │   └── machines.js
│   │   ├── middleware/
│   │   │   ├── auth.js
│   │   │   └── rateLimit.js
│   │   ├── services/
│   │   │   ├── gameService.js
│   │   │   └── pointService.js
│   │   └── index.js
│   ├── package.json
│   └── .env
│
└── net8/                # 既存システム
    └── 02.ソースファイル/
```

---

### Week 2: RESTful API実装

#### 必須エンドポイント

```javascript
// 1. 認証API
POST   /api/v1/auth/init
  Request:  { apiKey: 'pk_live_xxxxx' }
  Response: { token: 'jwt_token', expiresIn: 3600 }

// 2. ユーザー情報
GET    /api/v1/user/info
  Headers:  Authorization: Bearer <token>
  Response: { id, nickname, points, level }

// 3. 機種一覧
GET    /api/v1/models
  Response: [{ id, name, category, maker, thumbnail }]

// 4. 機種詳細
GET    /api/v1/models/:modelId
  Response: { id, name, specs, images, description }

// 5. ゲーム開始
POST   /api/v1/game/start
  Request:  { modelId: 'milliongod' }
  Response: { gameId, machineNo, signalingId, iceServers }

// 6. ゲームプレイ（スピン）
POST   /api/v1/game/play
  Request:  { gameId, action: 'spin', bet: 3 }
  Response: { success, credits, bonusTriggered, reels }

// 7. ゲーム終了
POST   /api/v1/game/end
  Request:  { gameId, result }
  Response: { success, finalPoints }

// 8. ポイント追加
POST   /api/v1/points/add
  Request:  { amount, paymentMethod }
  Response: { success, newBalance, transactionId }
```

#### 実装サンプル（Node.js + Express）

```javascript
// api-server/src/routes/games.js
const express = require('express');
const router = express.Router();
const gameService = require('../services/gameService');
const auth = require('../middleware/auth');

// ゲーム開始
router.post('/start', auth, async (req, res) => {
  try {
    const { modelId } = req.body;
    const userId = req.user.id;

    // 1. 利用可能なマシンを検索
    const machine = await gameService.findAvailableMachine(modelId);

    if (!machine) {
      return res.status(404).json({
        error: 'NO_AVAILABLE_MACHINE',
        message: '利用可能なマシンがありません'
      });
    }

    // 2. マシンを予約
    await gameService.reserveMachine(machine.id, userId);

    // 3. WebRTC Signaling情報を生成
    const signalingInfo = {
      signalingId: machine.signaling_id,
      iceServers: [
        { urls: 'stun:stun.l.google.com:19302' }
      ]
    };

    // 4. ゲームセッションを作成
    const gameSession = await gameService.createSession({
      userId,
      machineId: machine.id,
      modelId
    });

    res.json({
      success: true,
      gameId: gameSession.id,
      machineNo: machine.machine_no,
      signalingInfo
    });

  } catch (error) {
    console.error('Game start error:', error);
    res.status(500).json({
      error: 'INTERNAL_ERROR',
      message: error.message
    });
  }
});

// ゲームプレイ（スピン）
router.post('/play', auth, async (req, res) => {
  try {
    const { gameId, action, bet } = req.body;

    // 1. セッション検証
    const session = await gameService.getSession(gameId);
    if (!session || session.userId !== req.user.id) {
      return res.status(403).json({
        error: 'INVALID_SESSION',
        message: '無効なゲームセッションです'
      });
    }

    // 2. ポイント確認
    const requiredPoints = bet * 50; // 1ベット = 50ポイント
    if (req.user.points < requiredPoints) {
      return res.status(400).json({
        error: 'INSUFFICIENT_POINTS',
        message: 'ポイントが不足しています',
        required: requiredPoints,
        current: req.user.points
      });
    }

    // 3. 既存PHPシステムのプレイ処理を呼び出し
    const result = await gameService.executePlay({
      machineNo: session.machineNo,
      bet,
      userId: req.user.id
    });

    // 4. ポイント減算
    await gameService.deductPoints(req.user.id, requiredPoints);

    // 5. プレイ履歴記録
    await gameService.recordPlayHistory({
      userId: req.user.id,
      gameId,
      bet,
      result
    });

    res.json({
      success: true,
      credits: result.credits,
      bonusTriggered: result.bonusTriggered,
      jackpot: result.jackpot,
      reels: result.reels
    });

  } catch (error) {
    console.error('Play error:', error);
    res.status(500).json({
      error: 'PLAY_FAILED',
      message: error.message
    });
  }
});

module.exports = router;
```

---

### Week 3-4: JavaScript SDK Core開発

#### SDK開発環境セットアップ

```bash
# 新規プロジェクト作成
mkdir net8-sdk
cd net8-sdk
npm init -y

# 依存関係インストール
npm install --save-dev \
  rollup \
  @rollup/plugin-node-resolve \
  @rollup/plugin-commonjs \
  @rollup/plugin-babel \
  @rollup/plugin-terser \
  typescript \
  @types/node

# ディレクトリ構造
mkdir -p src/{core,game,ui,network,utils}
```

#### SDK実装（TypeScript）

```typescript
// src/index.ts
export { default as Net8 } from './core/Net8';
export { SlotGame } from './game/SlotGame';
export { PachinkoGame } from './game/PachinkoGame';
export * from './types';

// src/core/Net8.ts
import { APIClient } from '../network/APIClient';
import { SlotGame } from '../game/SlotGame';
import { PachinkoGame } from '../game/PachinkoGame';
import type { GameConfig, InitOptions } from '../types';

class Net8 {
  private static instance: Net8;
  private apiClient: APIClient;
  private apiKey: string = '';
  private initialized: boolean = false;

  private constructor() {
    this.apiClient = new APIClient();
  }

  static getInstance(): Net8 {
    if (!Net8.instance) {
      Net8.instance = new Net8();
    }
    return Net8.instance;
  }

  /**
   * SDK初期化
   */
  init(apiKey: string, options: InitOptions = {}): void {
    if (!apiKey || !apiKey.startsWith('pk_')) {
      throw new Error('Invalid API key');
    }

    this.apiKey = apiKey;
    this.apiClient.setApiKey(apiKey);
    this.apiClient.setBaseURL(
      options.apiUrl || 'https://api.net8.io'
    );

    this.initialized = true;
    console.log('[Net8] SDK initialized');
  }

  /**
   * ゲーム作成
   */
  createGame(config: GameConfig): SlotGame | PachinkoGame {
    if (!this.initialized) {
      throw new Error('SDK not initialized. Call Net8.init() first.');
    }

    const GameClass = config.type === 'slot' ? SlotGame : PachinkoGame;
    return new GameClass(config, this.apiClient);
  }

  /**
   * 機種一覧取得
   */
  async getModels() {
    return this.apiClient.get('/api/v1/models');
  }

  /**
   * ユーザー情報取得
   */
  async getUserInfo() {
    return this.apiClient.get('/api/v1/user/info');
  }

  /**
   * ブラウザサポートチェック
   */
  static isSupported(): boolean {
    return !!(
      navigator.mediaDevices &&
      navigator.mediaDevices.getUserMedia &&
      RTCPeerConnection
    );
  }
}

// シングルトンインスタンスをエクスポート
const net8 = Net8.getInstance();
export default net8;

// グローバルに公開（CDN使用時）
if (typeof window !== 'undefined') {
  (window as any).Net8 = net8;
}
```

```typescript
// src/game/SlotGame.ts
import EventEmitter from 'eventemitter3';
import { APIClient } from '../network/APIClient';
import { WebRTCManager } from '../network/WebRTCManager';
import { GameCanvas } from '../ui/GameCanvas';
import type { GameConfig, SpinResult } from '../types';

export class SlotGame extends EventEmitter {
  private apiClient: APIClient;
  private webrtc: WebRTCManager;
  private canvas: GameCanvas;
  private gameId: string | null = null;

  public model: string;
  public state: 'idle' | 'playing' | 'spinning' | 'result' = 'idle';
  public credits: number = 0;
  public points: number = 0;

  constructor(config: GameConfig, apiClient: APIClient) {
    super();
    this.model = config.model;
    this.apiClient = apiClient;
    this.webrtc = new WebRTCManager();
    this.canvas = new GameCanvas(config.container);
  }

  /**
   * ゲーム開始
   */
  async start(): Promise<void> {
    this.emit('start');

    try {
      // 1. ゲームセッション開始
      const response = await this.apiClient.post('/api/v1/game/start', {
        modelId: this.model
      });

      this.gameId = response.gameId;

      // 2. WebRTC接続
      await this.webrtc.connect(response.signalingInfo);

      // 3. ビデオストリーム表示
      const stream = this.webrtc.getRemoteStream();
      this.canvas.displayStream(stream);

      // 4. 初期ポイント取得
      const userInfo = await this.apiClient.get('/api/v1/user/info');
      this.points = userInfo.points;

      this.state = 'playing';
      this.emit('ready');

    } catch (error) {
      this.emit('error', error);
      throw error;
    }
  }

  /**
   * スピン実行
   */
  async spin(bet: number = 3): Promise<SpinResult> {
    if (this.state !== 'playing') {
      throw new Error('Game not ready');
    }

    this.state = 'spinning';
    this.emit('spin:start', bet);

    try {
      // APIコール
      const result = await this.apiClient.post('/api/v1/game/play', {
        gameId: this.gameId,
        action: 'spin',
        bet
      });

      this.credits = result.credits;
      this.state = 'result';

      this.emit('spin:end', result);

      if (result.credits > 0) {
        this.emit('win', result.credits, result.jackpot);
      }

      // 状態をplayingに戻す
      setTimeout(() => {
        this.state = 'playing';
      }, 2000);

      return result;

    } catch (error) {
      this.state = 'playing';
      this.emit('error', error);
      throw error;
    }
  }

  /**
   * ポイント追加
   */
  async addPoints(amount: number): Promise<void> {
    const result = await this.apiClient.post('/api/v1/points/add', {
      amount
    });

    const oldPoints = this.points;
    this.points = result.newBalance;

    this.emit('points:change', this.points, oldPoints);
  }

  /**
   * ゲーム終了
   */
  async destroy(): Promise<void> {
    if (this.gameId) {
      await this.apiClient.post('/api/v1/game/end', {
        gameId: this.gameId
      });
    }

    this.webrtc.disconnect();
    this.canvas.destroy();
    this.removeAllListeners();
  }
}
```

#### ビルド設定（Rollup）

```javascript
// rollup.config.js
import resolve from '@rollup/plugin-node-resolve';
import commonjs from '@rollup/plugin-commonjs';
import typescript from '@rollup/plugin-typescript';
import { terser } from 'rollup-plugin-terser';

export default [
  // UMD build (CDN用)
  {
    input: 'src/index.ts',
    output: {
      file: 'dist/net8.js',
      format: 'umd',
      name: 'Net8',
      sourcemap: true
    },
    plugins: [
      resolve(),
      commonjs(),
      typescript()
    ]
  },

  // Minified UMD build
  {
    input: 'src/index.ts',
    output: {
      file: 'dist/net8.min.js',
      format: 'umd',
      name: 'Net8',
      sourcemap: true
    },
    plugins: [
      resolve(),
      commonjs(),
      typescript(),
      terser()
    ]
  },

  // ES Module build (npm用)
  {
    input: 'src/index.ts',
    output: {
      file: 'dist/net8.esm.js',
      format: 'esm',
      sourcemap: true
    },
    plugins: [
      resolve(),
      commonjs(),
      typescript()
    ]
  }
];
```

---

## Month 2: 商用化準備（Week 5-8）

### Week 5-6: 複数機種対応 + UI強化

#### タスク一覧

```bash
□ 全機種データのAPI化
  - mst_model テーブルの全レコードをAPI経由で取得可能に
  - 機種画像（リール、詳細画像）のCDN配信
  - 機種スペック情報のJSON化

□ 機種切り替え機能
  - ゲームロビー画面SDK
  - 機種選択UI
  - スムーズな切り替えアニメーション

□ UIコンポーネント強化
  - ポイント表示
  - ベット選択
  - 設定パネル
  - モーダルウィンドウ
```

#### ゲームロビーSDK

```typescript
// src/ui/GameLobby.ts
export class GameLobby {
  private container: HTMLElement;
  private models: Model[] = [];

  constructor(containerId: string) {
    this.container = document.querySelector(containerId)!;
  }

  async render(): Promise<void> {
    // 機種一覧取得
    this.models = await Net8.getModels();

    // HTML生成
    this.container.innerHTML = `
      <div class="net8-lobby">
        <h2>機種を選択してください</h2>
        <div class="net8-model-grid">
          ${this.models.map(model => `
            <div class="net8-model-card" data-model="${model.id}">
              <img src="${model.thumbnail}" alt="${model.name}">
              <h3>${model.name}</h3>
              <p>${model.maker}</p>
            </div>
          `).join('')}
        </div>
      </div>
    `;

    // イベントリスナー
    this.container.querySelectorAll('.net8-model-card').forEach(card => {
      card.addEventListener('click', (e) => {
        const modelId = (e.currentTarget as HTMLElement).dataset.model!;
        this.selectModel(modelId);
      });
    });
  }

  private selectModel(modelId: string): void {
    // カスタムイベント発火
    this.container.dispatchEvent(new CustomEvent('model:select', {
      detail: { modelId }
    }));
  }
}
```

---

### Week 7-8: Developer Portal + 認証システム

#### Developer Portalの機能

```
必須機能:
✅ ユーザー登録・ログイン
✅ APIキー発行・管理
✅ 使用量ダッシュボード
✅ ドキュメント
✅ サンプルコード
✅ サポートチケット

技術スタック:
- Frontend: Next.js + TypeScript + Tailwind CSS
- Backend: Node.js + Express
- Database: PostgreSQL (新規)
- 認証: Auth0 または Supabase Auth
```

#### 実装スコープ（Week 7-8）

```javascript
// Developer Portalのページ構成
/
├── / (トップページ)
├── /login (ログイン)
├── /register (ユーザー登録)
├── /dashboard (ダッシュボード)
│   ├── /dashboard/api-keys (APIキー管理)
│   ├── /dashboard/usage (使用量)
│   ├── /dashboard/billing (課金)
│   └── /dashboard/settings (設定)
├── /docs (ドキュメント)
│   ├── /docs/quickstart
│   ├── /docs/api-reference
│   └── /docs/examples
└── /support (サポート)
```

#### APIキー管理システム

```sql
-- developer_portal.sql
CREATE TABLE api_keys (
  id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id UUID NOT NULL REFERENCES users(id),
  key_type VARCHAR(20) NOT NULL, -- 'public' or 'secret'
  key_value VARCHAR(100) NOT NULL UNIQUE,
  name VARCHAR(100),
  environment VARCHAR(20) NOT NULL, -- 'test' or 'live'
  rate_limit INTEGER DEFAULT 1000,
  is_active BOOLEAN DEFAULT true,
  last_used_at TIMESTAMP,
  created_at TIMESTAMP DEFAULT NOW(),
  expires_at TIMESTAMP
);

CREATE TABLE api_usage (
  id BIGSERIAL PRIMARY KEY,
  api_key_id UUID NOT NULL REFERENCES api_keys(id),
  endpoint VARCHAR(255) NOT NULL,
  method VARCHAR(10) NOT NULL,
  status_code INTEGER,
  response_time_ms INTEGER,
  timestamp TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_api_usage_key_timestamp
  ON api_usage(api_key_id, timestamp DESC);
```

---

## Month 3: スケールアップ（Week 9-12）

### Week 9-10: React/Vue Components

#### React Components

```bash
# 新規プロジェクト
mkdir net8-react
cd net8-react
npm init -y
npm install react react-dom @net8/gaming-sdk
npm install --save-dev @types/react typescript
```

```typescript
// packages/react/src/Net8Provider.tsx
import React, { createContext, useContext, useEffect, useState } from 'react';
import Net8 from '@net8/gaming-sdk';

interface Net8ContextValue {
  initialized: boolean;
  user: any;
}

const Net8Context = createContext<Net8ContextValue>({
  initialized: false,
  user: null
});

export const Net8Provider: React.FC<{
  apiKey: string;
  children: React.ReactNode;
}> = ({ apiKey, children }) => {
  const [initialized, setInitialized] = useState(false);
  const [user, setUser] = useState(null);

  useEffect(() => {
    Net8.init(apiKey);
    setInitialized(true);

    // ユーザー情報取得
    Net8.getUserInfo().then(setUser);
  }, [apiKey]);

  return (
    <Net8Context.Provider value={{ initialized, user }}>
      {children}
    </Net8Context.Provider>
  );
};

export const useNet8 = () => useContext(Net8Context);
```

```typescript
// packages/react/src/SlotMachine.tsx
import React, { useEffect, useRef, useState } from 'react';
import Net8, { SlotGame } from '@net8/gaming-sdk';

interface SlotMachineProps {
  model: string;
  theme?: any;
  onWin?: (credits: number, jackpot: boolean) => void;
  onError?: (error: Error) => void;
}

export const SlotMachine: React.FC<SlotMachineProps> = ({
  model,
  theme,
  onWin,
  onError
}) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const gameRef = useRef<SlotGame | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    if (!containerRef.current) return;

    // ゲーム作成
    const game = Net8.createGame({
      type: 'slot',
      model,
      container: containerRef.current,
      theme
    });

    gameRef.current = game;

    // イベントリスナー
    game.on('ready', () => setLoading(false));
    game.on('win', onWin);
    game.on('error', onError);

    // ゲーム開始
    game.start();

    // クリーンアップ
    return () => {
      game.destroy();
    };
  }, [model]);

  return (
    <div>
      {loading && <div>Loading...</div>}
      <div ref={containerRef} />
    </div>
  );
};
```

---

### Week 11-12: 課金システム + 最終調整

#### Stripe統合

```javascript
// api-server/src/routes/billing.js
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY);

// 従量課金の記録
router.post('/usage', auth, async (req, res) => {
  const { apiKey, endpoint, count } = req.body;

  // Stripe Usage Recordに記録
  await stripe.subscriptionItems.createUsageRecord(
    'si_xxxxxxxx',  // Subscription Item ID
    {
      quantity: count,
      timestamp: Math.floor(Date.now() / 1000)
    }
  );

  res.json({ success: true });
});

// 料金プラン一覧
// 注: 実際の価格設定はAPI提供先が決定します
// NET8はレベニューシェア（売上の25-30%）を受け取るモデルです
router.get('/plans', async (req, res) => {
  // 以下はサンプル実装（提供先が独自に設定）
  const plans = [
    {
      id: 'custom',
      name: 'Custom Plan',
      description: 'API提供先が価格を自由に設定可能',
      revenueShare: 0.25, // NET8への支払い 25%
      limits: {
        monthlyPlays: null, // 制限なし
        rateLimit: null // 制限なし
      }
    }
  ];

  res.json(plans);
});
```

---

## 🚀 デプロイ戦略

### CDN配信（Cloudflare）

```bash
# SDK配信
https://cdn.net8.io/sdk/v1/net8.min.js
https://cdn.net8.io/sdk/v1/net8.js

# バージョン管理
https://cdn.net8.io/sdk/v1.0.0/net8.min.js
https://cdn.net8.io/sdk/v1.0.1/net8.min.js
```

### Railway デプロイ

```yaml
# railway.toml
[build]
builder = "NIXPACKS"

[deploy]
startCommand = "npm start"
restartPolicyType = "always"

[[deploy.healthcheck]]
path = "/health"
interval = 30

[env]
NODE_ENV = "production"
```

---

## 📊 進捗管理

### Week毎のチェックポイント

```
Week 1:  ✓ API設計完了
Week 2:  ✓ 基本APIエンドポイント実装
Week 3:  ✓ SDK Core開発完了
Week 4:  ✓ MVP動作確認（デモサイト）
Week 5:  ✓ 全機種API対応
Week 6:  ✓ ゲームロビー実装
Week 7:  ✓ Developer Portal基本機能
Week 8:  ✓ APIキー管理システム
Week 9:  ✓ React Components完成
Week 10: ✓ Vue Components完成
Week 11: ✓ Stripe統合完了
Week 12: ✓ 商用ローンチ準備完了
```

---

## 🎉 完成！

3ヶ月後、以下が完成します：

```
✅ JavaScript SDK（npm + CDN）
✅ React Components
✅ Vue Components
✅ 全機種対応
✅ Developer Portal
✅ APIキー認証システム
✅ 従量課金システム
✅ 完全なドキュメント
✅ デモサイト
```

**次のステップ**: 実装開始！

---

**前のドキュメント**: [NET8_JAVASCRIPT_SDK_SPEC.md](./NET8_JAVASCRIPT_SDK_SPEC.md)
