# CLAUDE.md - korea_net8front プロジェクト開発ガイド

## プロジェクト概要

**プロジェクト名**: korea_net8front
**種別**: Next.js 15 フロントエンドアプリケーション（パチンコ・スロット遊技プラットフォーム）
**主要技術**: Next.js 15, React 19, TypeScript, Tailwind CSS v4, Zustand
**NET8連携**: NET8 SDK v1.1.0 を使用したパチンコ実機遊技システム

---

## NET8 SDK 統合情報

### APIエンドポイント
- **Base URL**: `https://mgg-webservice-production.up.railway.app`
- **API Key**: 環境変数 `NET8_API_KEY` で管理

### 主要API
| API | エンドポイント | 用途 |
|-----|---------------|------|
| ゲーム開始 | `POST /api/v1/game_start.php` | セッション作成・ポイント消費 |
| ゲーム終了 | `POST /api/v1/game_end.php` | セッション終了・ポイント払い出し |
| ポイント追加 | `POST /api/v1/add_points.php` | ユーザーポイント追加 |
| プレイ履歴 | `GET /api/v1/play_history.php` | ゲーム履歴取得 |

### 認証方式
```typescript
headers: {
  'Authorization': `Bearer ${process.env.NET8_API_KEY}`,
  'Content-Type': 'application/json'
}
```

---

## ディレクトリ構造

```
korea_net8front/
├── client-pachinko/           # メインNext.jsアプリ
│   ├── src/
│   │   ├── app/
│   │   │   ├── [locale]/      # App Router（多言語対応: ja/en/ko/zh）
│   │   │   │   ├── games/     # ゲーム関連ページ
│   │   │   │   ├── account/   # アカウント管理
│   │   │   │   └── settings/  # 設定
│   │   │   └── api/           # API Routes
│   │   │       └── game/      # NET8ゲームAPI（実装済み）
│   │   │           ├── start/route.ts
│   │   │           └── end/route.ts
│   │   ├── components/        # UIコンポーネント
│   │   ├── lib/
│   │   │   ├── api/           # APIクライアント
│   │   │   ├── net8/          # NET8 SDK統合（拡張版）
│   │   │   ├── net8.service.ts # NET8サービス（実装済み）
│   │   │   └── fetcher.ts     # 共通フェッチャー
│   │   ├── store/             # Zustand状態管理
│   │   ├── types/
│   │   │   ├── net8.ts        # NET8型定義（実装済み）
│   │   │   └── ...
│   │   ├── hooks/
│   │   │   ├── useNET8Game.ts # NET8ゲームフック（実装済み）
│   │   │   ├── useWebRTC.ts   # WebRTCフック（実装済み）
│   │   │   └── ...
│   │   ├── config/            # 設定ファイル
│   │   ├── validations/       # Zodスキーマ
│   │   └── messages/          # i18n翻訳（ja/en/ko/zh）
│   ├── public/                # 静的ファイル
│   └── .env                   # 環境変数
├── .claude/                   # Claude Code設定
│   ├── settings.json          # 権限・hook設定
│   └── commands/              # カスタムスラッシュコマンド
│       ├── net8-test.md       # NET8接続テスト
│       ├── build-check.md     # ビルドチェック
│       └── create-component.md # コンポーネント作成
└── CLAUDE.md                  # このファイル
```

---

## 開発ルール

### 1. コーディング規約

#### TypeScript
- `strict: true` を維持
- `any`型の使用は禁止（やむを得ない場合は `unknown` を使用）
- インターフェースは `I` プレフィックスなし（例: `GameSession`）
- 型定義は `src/types/` に集約

#### React/Next.js
- Server Components をデフォルトで使用
- Client Components は `"use client"` を明示
- API Routes ではなく Server Actions を優先
- 非同期データ取得は `src/lib/api/` に集約

#### スタイリング
- Tailwind CSS v4 を使用
- カスタムクラスは最小限に
- コンポーネントは `class-variance-authority` で variants 管理

### 2. ファイル命名規則

| 種別 | 命名規則 | 例 |
|------|---------|-----|
| コンポーネント | PascalCase | `GameCard.tsx` |
| フック | camelCase + use | `useGameSession.ts` |
| ユーティリティ | camelCase + .utils | `game.utils.ts` |
| API | camelCase + .api | `net8.api.ts` |
| 型定義 | camelCase + .types | `game.types.ts` |
| スキーマ | camelCase + .schema | `game.schema.ts` |

### 3. Git コミット規約

```
<type>(<scope>): <subject>

type:
- feat: 新機能
- fix: バグ修正
- refactor: リファクタリング
- docs: ドキュメント
- style: フォーマット
- test: テスト
- chore: ビルド・設定

scope: 影響範囲（net8, games, auth, etc.）
```

---

## NET8 SDK 実装状況

### 実装済みコンポーネント

#### 1. NET8Service（src/lib/net8.service.ts）
```typescript
// 既に実装済み - 以下のメソッドが利用可能
- startGame(request: GameStartRequest): Promise<GameStartResponse>
- endGame(request: GameEndRequest): Promise<GameEndResponse>
- addPoints(userId, amount, reason?): Promise<any>
- getPlayHistory(userId, limit, offset): Promise<any>
```

#### 2. API Routes（src/app/api/game/）
- `POST /api/game/start` - ゲーム開始
- `POST /api/game/end` - ゲーム終了

#### 3. React Hooks（src/hooks/）
```typescript
// useNET8Game.ts - ゲームセッション管理
const { loading, error, session, startGame, endGame } = useNET8Game();

// useWebRTC.ts - WebRTC映像接続
const { videoRef } = useWebRTC(signalingInfo);
```

#### 4. 型定義（src/types/net8.ts）
- `GameStartRequest` / `GameStartResponse`
- `GameEndRequest` / `GameEndResponse`
- `GamePlaybackMethods`
- `NET8Error`

### 使用例

```typescript
// コンポーネントでの使用
import { useNET8Game } from '@/hooks/useNET8Game';

function GameComponent() {
  const { loading, session, startGame, endGame } = useNET8Game();

  const handleStart = async () => {
    await startGame('user_123', 'HOKUTO4GO');
  };

  const handleEnd = async () => {
    await endGame('win', 500);
  };

  return (
    <div>
      {session && <p>Session: {session.sessionId}</p>}
      <button onClick={handleStart} disabled={loading}>Start</button>
      <button onClick={handleEnd} disabled={!session}>End</button>
    </div>
  );
}
```

---

## 環境変数

### 必須
```bash
# 既存API
NEXT_PUBLIC_API_URL=http://localhost:10001
NEXT_PUBLIC_NEW_GAMING_AUTHORIZATION=<auth_token>

# NET8 SDK
NET8_API_KEY=pk_demo_12345
NET8_API_BASE_URL=https://mgg-webservice-production.up.railway.app
```

### オプション
```bash
# WebRTC シグナリング
PEERJS_HOST=dockerfilesignaling-production.up.railway.app
PEERJS_PORT=443
PEERJS_SECURE=true
```

---

## よく使うコマンド

```bash
# 開発サーバー起動
npm run dev

# ビルド
npm run build

# Lint
npm run lint

# 型チェック
npx tsc --noEmit
```

---

## トラブルシューティング

### NET8 API エラー

| エラーコード | 原因 | 対処法 |
|------------|------|--------|
| `INVALID_API_KEY` | API Key無効 | .envのNET8_API_KEYを確認 |
| `INSUFFICIENT_BALANCE` | ポイント不足 | add_points APIでポイント追加 |
| `SESSION_NOT_FOUND` | セッション無効 | sessionIdを再確認 |
| `MODEL_NOT_FOUND` | 機種コード無効 | 有効な機種コードを使用 |

### 開発時の注意

1. **API Keyはサーバーサイドのみで使用**
   - `NEXT_PUBLIC_` プレフィックスを付けない
   - Server Components または API Routes で処理

2. **WebRTC接続**
   - HTTPS環境必須（localhost除く）
   - ファイアウォールでUDP許可が必要

---

## 参照ドキュメント

- [NET8 SDK実装マニュアル](/Users/kotarokashiwai/net8_rebirth/NET8_SDK_IMPLEMENTATION_GUIDEV1_1_11.md)
- [NET8 SDK詳細ガイド（日本語）](/Users/kotarokashiwai/net8_rebirth/NET8_SDK_IMPLEMENTATION_GUIDE_DETAILED_JA.md)
