# NET8 Gaming SDK - クイックスタートガイド

**最終更新**: 2025-11-06
**ドキュメントバージョン**: 1.0
**対象者**: 開発者（3ヶ月以内にSDKを実装したい方）

---

## 🎯 このドキュメントの目的

NET8パチンコ・スロットゲームシステムを**SDK化**し、外部企業がすぐにサービスを立ち上げられるようにする。

### 提供する価値
- **3行のコードでゲーム導入**（技術的ハードル極小）
- **デザイン込み**（すぐに使える美しいUI）
- **従量課金制**（初期コスト不要）
- **マルチテナント対応**（複数企業同時利用）

---

## 📦 NET8 SDK とは

### コンセプト
```
Stripeの決済SDK ＝ 3行で決済機能を追加
NET8 Gaming SDK ＝ 3行でパチスロゲームを追加
```

### 使用例
```html
<!DOCTYPE html>
<html>
<head>
  <script src="https://cdn.net8.io/sdk/v1/net8.js"></script>
</head>
<body>
  <div id="game"></div>

  <script>
    const game = new Net8.SlotGame({
      apiKey: 'pk_live_xxxxxxxxxxxxx',
      container: '#game',
      model: 'milliongod'
    });
    game.start();
  </script>
</body>
</html>
```

**たったこれだけで**、ミリオンゴッドのスロットゲームが動作します。

---

## 🏗️ システムアーキテクチャ

```
┌─────────────────────────────────────────────────────────┐
│                    顧客企業のWebサイト                    │
│  ┌──────────────────────────────────────────────────┐   │
│  │  <script src="net8-sdk.js"></script>             │   │
│  │  <Net8SlotGame model="milliongod" />             │   │
│  └──────────────────────────────────────────────────┘   │
└────────────────────────┬────────────────────────────────┘
                         │ HTTPS
                         ▼
┌─────────────────────────────────────────────────────────┐
│              NET8 API Gateway (Railway)                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │  認証       │  │ レート制限   │  │  課金       │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
└────────────────────────┬────────────────────────────────┘
                         │
         ┌───────────────┼───────────────┐
         ▼               ▼               ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│  REST API    │ │  GraphQL     │ │  WebSocket   │
│  (PHP/Node)  │ │  (Hasura)    │ │  (PeerJS)    │
└──────────────┘ └──────────────┘ └──────────────┘
         │               │               │
         └───────────────┼───────────────┘
                         ▼
              ┌──────────────────┐
              │  MySQL Database  │
              │  (GCP Cloud SQL) │
              └──────────────────┘
```

---

## 🚀 最速実装パス（3ヶ月計画）

### Phase 1: MVP（Month 1）
**目標**: JavaScript SDK + 1機種で動作

#### Week 1-2: API層の構築
```
✅ 既存PHPシステムの分析完了
□ RESTful API設計
  - /api/v1/machines/list
  - /api/v1/game/start
  - /api/v1/game/play
  - /api/v1/game/result
□ API認証システム（JWT）
□ CORS設定
```

#### Week 3-4: JavaScript SDK開発
```
□ SDK Core開発
  - Net8.init()
  - Net8.SlotGame クラス
  - WebRTC接続管理
□ デザインシステム移植
  - 既存CSSの抽出
  - コンポーネント化
□ テスト環境構築
```

#### 成果物
- `@net8/gaming-sdk` v0.1.0
- デモサイト（1機種のみ）
- 基本ドキュメント

---

### Phase 2: 商用化準備（Month 2）

#### Week 5-6: 複数機種対応
```
□ 全機種データのAPI化
  - ミリオンゴッド
  - 北斗の拳
  - その他10機種
□ 機種切り替え機能
□ ロビー画面SDK
```

#### Week 7-8: Developer Portal
```
□ APIキー発行システム
□ 使用量ダッシュボード
□ ドキュメントサイト
□ サンプルコード集
```

#### 成果物
- `@net8/gaming-sdk` v1.0.0
- Developer Portal (https://developers.net8.io)
- 完全なAPIドキュメント

---

### Phase 3: スケールアップ（Month 3）

#### Week 9-10: フレームワーク対応
```
□ React Components
  - <SlotMachine />
  - <GameLobby />
  - <PointPurchase />
□ Vue 3 Components
□ TypeScript型定義
```

#### Week 11-12: 課金・分析
```
□ Stripe統合（従量課金）
□ 使用量トラッキング
□ Analytics Dashboard
□ セキュリティ監査
```

#### 成果物
- `@net8/react` v1.0.0
- `@net8/vue` v1.0.0
- 商用ローンチ準備完了

---

## 💻 技術スタック選定

### 推奨構成（最速かつ安定）

#### バックエンド
```yaml
API Gateway: Tyk (オープンソース)
  - 認証・認可
  - レート制限
  - 使用量トラッキング

Backend API:
  - 既存PHP（そのまま活用）
  - Node.js（新規エンドポイント）
  - Hasura（GraphQL自動生成）

Database:
  - 既存MySQL（GCP Cloud SQL）
  - Redis（キャッシュ・セッション）

Real-time:
  - PeerJS（既存のSignalingサーバー活用）
  - Socket.io（新規リアルタイム機能）
```

#### フロントエンド（SDK）
```yaml
Core SDK: TypeScript + Rollup
  - ESM + UMD ビルド
  - Tree-shakable
  - 50KB未満（gzip後）

UI Framework: Vanilla JS + Web Components
  - フレームワーク非依存
  - Shadow DOM
  - カスタムブランディング対応

React/Vue Wrappers:
  - Thin wrapper（Core SDKをラップ）
  - Hooks/Composition API
```

#### インフラ
```yaml
Hosting: Railway（既存）
CDN: Cloudflare（SDK配信）
Monitoring: Sentry + Grafana
CI/CD: GitHub Actions
```

---

## 📋 実装チェックリスト

### Month 1（MVP）
- [ ] API設計ドキュメント作成
- [ ] JWT認証システム実装
- [ ] REST APIエンドポイント実装（5本）
- [ ] JavaScript SDK Core開発
- [ ] 1機種（ミリオンゴッド）のSDK対応
- [ ] デモサイト公開

### Month 2（商用化準備）
- [ ] 全機種API対応
- [ ] Developer Portal実装
- [ ] APIキー管理システム
- [ ] 使用量トラッキング実装
- [ ] ドキュメント整備
- [ ] ベータテスト開始

### Month 3（スケールアップ）
- [ ] React/Vue Components開発
- [ ] TypeScript型定義完備
- [ ] Stripe課金統合
- [ ] セキュリティ監査
- [ ] パフォーマンステスト
- [ ] 商用ローンチ

---

## 🎨 SDK使用例（詳細）

### 1. 最小限の実装
```html
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>NET8 Gaming Demo</title>
  <script src="https://cdn.net8.io/sdk/v1/net8.min.js"></script>
</head>
<body>
  <div id="game-container"></div>

  <script>
    // 初期化
    Net8.init('pk_live_xxxxxxxxxx');

    // ゲーム開始
    const game = Net8.createGame({
      type: 'slot',
      model: 'milliongod',
      container: '#game-container'
    });

    game.start();
  </script>
</body>
</html>
```

### 2. カスタマイズ例
```javascript
const game = Net8.createGame({
  type: 'slot',
  model: 'milliongod',
  container: '#game-container',

  // カスタマイズオプション
  theme: {
    primaryColor: '#ff6b6b',
    backgroundColor: '#1a1a1a',
    logo: 'https://yoursite.com/logo.png'
  },

  // ポイント管理
  points: {
    initial: 1000,
    onPurchase: (amount) => {
      // 独自の課金処理
      return yourPaymentService.purchase(amount);
    }
  },

  // イベントハンドラ
  onWin: (credits, jackpot) => {
    console.log(`Win: ${credits} credits`);
    if (jackpot) {
      showJackpotAnimation();
    }
  },

  onGameEnd: (result) => {
    analytics.track('game_end', result);
  }
});

game.start();
```

### 3. React での使用
```jsx
import { Net8Provider, SlotMachine, PointDisplay } from '@net8/react';

function App() {
  return (
    <Net8Provider apiKey="pk_live_xxxxxxxxxx">
      <div className="app">
        <header>
          <h1>My Gaming Site</h1>
          <PointDisplay />
        </header>

        <main>
          <SlotMachine
            model="milliongod"
            theme="dark"
            onWin={(credits) => {
              toast.success(`You won ${credits} credits!`);
            }}
          />
        </main>
      </div>
    </Net8Provider>
  );
}
```

### 4. Vue 3 での使用
```vue
<template>
  <Net8Provider :apiKey="apiKey">
    <div class="game-page">
      <SlotMachine
        model="milliongod"
        :theme="theme"
        @win="handleWin"
      />
    </div>
  </Net8Provider>
</template>

<script setup>
import { Net8Provider, SlotMachine } from '@net8/vue';

const apiKey = 'pk_live_xxxxxxxxxx';
const theme = { primaryColor: '#ff6b6b' };

const handleWin = (credits) => {
  console.log('Won:', credits);
};
</script>
```

---

## 💰 収益モデル

### レベニューシェア方式
```
NET8 SDKは、API提供先が自由に価格設定できるモデルです。

【仕組み】
1. API提供先（パートナー企業）が価格を決定
2. エンドユーザーが提供先に支払い
3. NET8は売上の25-30%をレベニューシェアとして受け取り

【メリット】
✅ 提供先が柔軟に価格設定可能
✅ 市場に合わせた戦略的価格設定
✅ WIN-WINのパートナーシップ

【管理ツール】
- リアルタイム売上ダッシュボード
- レベニューシェア自動計算
- 日次・月次レポート

お問い合わせ: enterprise@net8.io
```

---

## 🔐 セキュリティ設計

### API認証フロー
```
1. 顧客がDeveloper Portalでアカウント作成
   ↓
2. APIキーペア発行
   - Public Key: pk_live_xxxxx（フロントエンド用）
   - Secret Key: sk_live_xxxxx（サーバー用）
   ↓
3. SDKでPublic Keyを使用
   Net8.init('pk_live_xxxxx');
   ↓
4. バックエンドでJWT発行
   - 有効期限: 1時間
   - Refresh Token: 30日
   ↓
5. API呼び出し時に検証
   Authorization: Bearer <JWT>
```

### セキュリティ対策
```
✅ HTTPS必須（TLS 1.3）
✅ JWT認証（RS256署名）
✅ レート制限（1000 req/min）
✅ CORS設定（ホワイトリスト）
✅ SQLインジェクション対策
✅ XSS対策（CSP）
✅ DDoS対策（Cloudflare）
✅ 不正検知システム
```

---

## 📊 使用量トラッキング

### メトリクス収集
```javascript
// SDK内部で自動送信
{
  event: 'game_play',
  apiKey: 'pk_live_xxxxx',
  model: 'milliongod',
  duration: 180, // 秒
  credits: 1000,
  result: 'win',
  timestamp: '2025-11-06T12:00:00Z',
  userId: 'user_12345',
  sessionId: 'session_abc123'
}
```

### ダッシュボード表示項目
```
- 総ゲームプレイ数
- 機種別プレイ数
- ユーザー数（DAU/MAU）
- 収益予測
- エラーレート
- レスポンスタイム
- 現在のプラン使用率
```

---

## 🛠️ 開発環境セットアップ

### 1. リポジトリクローン
```bash
git clone https://github.com/net8/gaming-sdk.git
cd gaming-sdk
```

### 2. 依存関係インストール
```bash
npm install
```

### 3. 環境変数設定
```bash
cp .env.example .env.local
```

```env
# .env.local
NET8_API_URL=https://api.net8.io
NET8_SIGNALING_URL=https://signaling.net8.io
NET8_CDN_URL=https://cdn.net8.io
DATABASE_URL=mysql://user:pass@host:3306/net8_dev
REDIS_URL=redis://localhost:6379
JWT_SECRET=your_jwt_secret_here
STRIPE_SECRET_KEY=sk_test_xxxxx
```

### 4. 開発サーバー起動
```bash
# Backend API
npm run dev:api

# SDK開発サーバー
npm run dev:sdk

# Developer Portal
npm run dev:portal
```

### 5. テスト実行
```bash
# 単体テスト
npm test

# E2Eテスト
npm run test:e2e

# ビジュアルリグレッション
npm run test:visual
```

---

## 📚 次のステップ

このクイックスタートガイドを読んだら：

1. **NET8_JAVASCRIPT_SDK_SPEC.md** を読む
   - JavaScript SDK の完全な仕様
   - 全API リファレンス
   - 詳細な実装例

2. **NET8_MVP_ROADMAP.md** を読む
   - 3ヶ月の詳細実装計画
   - タスク分解
   - リソース配分

3. **実装開始**
   - Phase 1 Week 1から着手
   - 毎週進捗レビュー
   - 3ヶ月後に商用ローンチ

---

## 🤝 サポート

### ドキュメント
- 完全ドキュメント: https://docs.net8.io
- API リファレンス: https://api.net8.io/docs
- GitHub: https://github.com/net8/gaming-sdk

### コミュニティ
- Discord: https://discord.gg/net8
- Stack Overflow: タグ [net8-sdk]
- Twitter: @net8gaming

### 商用サポート
- Email: support@net8.io
- Enterprise: enterprise@net8.io

---

## 📝 変更履歴

| バージョン | 日付 | 変更内容 |
|-----------|------|---------|
| 1.0 | 2025-11-06 | 初版作成 |

---

**次のドキュメント**: [NET8_JAVASCRIPT_SDK_SPEC.md](./NET8_JAVASCRIPT_SDK_SPEC.md)
