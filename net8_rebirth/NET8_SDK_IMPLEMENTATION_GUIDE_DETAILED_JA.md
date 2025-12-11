# NET8 SDK 詳細実装マニュアル（日本語版）

**バージョン**: v1.1.0
**最終更新**: 2025-11-24
**対象者**: パートナー企業の開発者・システムインテグレーター
**ドキュメント種別**: 技術実装ガイド

---

## 📋 目次

1. [概要](#概要)
2. [アーキテクチャ](#アーキテクチャ)
3. [事前準備](#事前準備)
4. [認証システム](#認証システム)
5. [詳細実装手順](#詳細実装手順)
6. [セキュリティ](#セキュリティ)
7. [パフォーマンス最適化](#パフォーマンス最適化)
8. [本番環境移行](#本番環境移行)
9. [運用管理](#運用管理)
10. [ベストプラクティス](#ベストプラクティス)

---

## 概要

### NET8 SDKとは

NET8 SDKは、パチンコ・パチスロ遊技機のリモート操作を実現するためのクラウドベースSDKです。パートナー企業は、このSDKを使用して独自のWebアプリケーションやモバイルアプリから、実機遊技体験を提供できます。

### 主要機能

#### 1. ゲームセッション管理
- **game_start**: ゲーム開始とポイント消費
- **game_end**: ゲーム終了とポイント付与
- **セッション追跡**: リアルタイムでゲーム状態を管理

#### 2. ユーザー管理
- **自動ユーザーリンク**: パートナー側のユーザーIDとNET8ユーザーの自動連携
- **ポイント管理**: ユーザーごとのポイント残高管理
- **プレイ履歴**: 過去のゲーム履歴の取得

#### 3. WebRTC映像配信
- **リアルタイム映像**: 実機カメラからの低遅延ストリーミング
- **PeerJS統合**: 簡単なWebRTC接続確立
- **モック機能**: テスト環境での疑似映像配信

### システム構成図

```
┌─────────────────────────────────────────────────────────────┐
│                    パートナー企業                            │
│  ┌────────────────────────────────────────────────────┐    │
│  │        フロントエンド（Web/Mobile App）             │    │
│  │  - ユーザーログイン                                  │    │
│  │  - ゲーム選択UI                                      │    │
│  │  - WebRTC映像表示                                    │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓ HTTPS/WSS                         │
│  ┌────────────────────────────────────────────────────┐    │
│  │           バックエンドサーバー                       │    │
│  │  - NET8 SDK統合                                     │    │
│  │  - ユーザー認証                                      │    │
│  │  - セッション管理                                    │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                         ↓ API Key認証
┌─────────────────────────────────────────────────────────────┐
│                    NET8 クラウド                             │
│  ┌────────────────────────────────────────────────────┐    │
│  │            NET8 SDK API Gateway                     │    │
│  │  - 認証・認可                                        │    │
│  │  - レート制限                                        │    │
│  │  - ロギング                                          │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │           ゲームセッション管理サービス               │    │
│  │  - ポイント管理                                      │    │
│  │  - セッション追跡                                    │    │
│  │  - トランザクション記録                              │    │
│  └────────────────────────────────────────────────────┘    │
│                         ↓                                    │
│  ┌────────────────────────────────────────────────────┐    │
│  │         WebRTCシグナリングサーバー                   │    │
│  │  - PeerJS Server                                    │    │
│  │  - TURN/STUNサーバー                                │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
                         ↓ WebRTC P2P
┌─────────────────────────────────────────────────────────────┐
│                  実機設置店舗                                │
│  ┌────────────────────────────────────────────────────┐    │
│  │              パチンコ実機                            │    │
│  │  - カメラ映像配信                                    │    │
│  │  - 操作受付                                          │    │
│  └────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────┘
```

### データフロー

#### ゲーム開始フロー
```
1. ユーザーがゲーム開始ボタンをクリック
   ↓
2. パートナーサーバーが game_start API呼び出し
   - API Key認証
   - ユーザー認証
   - ポイント残高確認
   ↓
3. NET8がセッションを作成
   - sessionId発行
   - ポイント消費（例: 100ポイント）
   - 利用可能な実機を割り当て
   ↓
4. WebRTC接続確立
   - シグナリング情報取得
   - PeerJS接続確立
   - 映像ストリーミング開始
   ↓
5. ユーザーがゲームプレイ
```

#### ゲーム終了フロー
```
1. ユーザーがゲーム終了または時間切れ
   ↓
2. パートナーサーバーが game_end API呼び出し
   - sessionId指定
   - 結果（win/lose）
   - 獲得ポイント
   ↓
3. NET8がポイント処理
   - ポイント加算
   - トランザクション記録
   - セッション終了
   ↓
4. WebRTC接続切断
   ↓
5. 結果画面表示
```

---

## アーキテクチャ

### システムアーキテクチャの詳細

#### レイヤー構造

```
┌─────────────────────────────────────────────┐
│      プレゼンテーション層                    │
│  - フロントエンドUI                          │
│  - WebRTC映像表示                            │
│  - ユーザーインタラクション                  │
└─────────────────────────────────────────────┘
                    ↓ HTTPS
┌─────────────────────────────────────────────┐
│         アプリケーション層                   │
│  - ビジネスロジック                          │
│  - セッション管理                            │
│  - ユーザー管理                              │
└─────────────────────────────────────────────┘
                    ↓ REST API
┌─────────────────────────────────────────────┐
│          統合層（NET8 SDK）                 │
│  - API Key認証                              │
│  - リクエスト/レスポンス変換                │
│  - エラーハンドリング                        │
└─────────────────────────────────────────────┘
                    ↓ HTTPS
┌─────────────────────────────────────────────┐
│        NET8 クラウドサービス                 │
│  - API Gateway                              │
│  - ゲームサービス                            │
│  - ユーザーサービス                          │
└─────────────────────────────────────────────┘
                    ↓ SQL/NoSQL
┌─────────────────────────────────────────────┐
│          データ永続化層                      │
│  - MySQL (GCP Cloud SQL)                    │
│  - トランザクションログ                      │
│  - セッション状態                            │
└─────────────────────────────────────────────┘
```

### セキュリティアーキテクチャ

#### 多層防御戦略

```
1. ネットワーク層
   ├─ TLS 1.2+ 強制
   ├─ DDoS保護（Cloudflare）
   └─ IP制限（オプション）

2. 認証層
   ├─ API Key認証（Bearer Token）
   ├─ レート制限（1000 req/hour）
   └─ リクエスト署名（オプション）

3. アプリケーション層
   ├─ 入力検証
   ├─ SQLインジェクション対策
   ├─ XSS対策
   └─ CSRF対策

4. データ層
   ├─ データベース暗号化
   ├─ 最小権限の原則
   └─ 監査ログ
```

---

## 事前準備

### 1. システム要件

#### サーバー要件

**最小要件**:
- **OS**: Linux (Ubuntu 20.04+), macOS, Windows Server 2019+
- **CPU**: 2コア以上
- **メモリ**: 4GB以上
- **ストレージ**: 20GB以上の空き容量
- **ネットワーク**: 100Mbps以上の帯域

**推奨要件**:
- **OS**: Linux (Ubuntu 22.04 LTS)
- **CPU**: 4コア以上
- **メモリ**: 8GB以上
- **ストレージ**: SSD 50GB以上
- **ネットワーク**: 1Gbps以上の帯域

#### ソフトウェア要件

**必須**:
- **プログラミング言語**:
  - Node.js 16.x以上
  - PHP 7.4以上
  - Python 3.8以上
  - Java 11以上
  - Ruby 2.7以上
  - Go 1.18以上
- **データベース**: MySQL 8.0, PostgreSQL 13+, MongoDB 5.0+
- **Webサーバー**: Nginx, Apache, IIS
- **SSL証明書**: Let's Encrypt推奨

**推奨**:
- **コンテナ**: Docker 20.10+, Kubernetes 1.24+
- **CI/CD**: GitHub Actions, GitLab CI, Jenkins
- **監視**: Prometheus, Grafana, Datadog
- **ログ**: ELK Stack, Splunk

#### クライアント要件

**Webブラウザ**:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Opera 76+

**モバイル**:
- iOS 14+ (Safari, Chrome)
- Android 8+ (Chrome, Firefox)

**ネットワーク**:
- 最小: 5Mbps
- 推奨: 10Mbps以上
- WebRTC対応（UDP穴あけ可能）

### 2. APIキーの取得

#### 取得手順

1. **NET8営業窓口への連絡**
   ```
   Email: sales@net8.com
   電話: 03-XXXX-XXXX
   営業時間: 平日 10:00-18:00 (JST)
   ```

2. **契約書の締結**
   - 利用規約の確認
   - NDA（秘密保持契約）の締結
   - 契約書への署名

3. **APIキーの発行**
   - テスト環境キー（`pk_test_xxx`）の即時発行
   - 本番環境キー（`pk_live_xxx`）の審査後発行

4. **セットアップサポート**
   - 技術サポート窓口の案内
   - オンボーディングセッションの予約
   - サンプルコードの提供

#### APIキーの種類

| 種類 | プレフィックス | 用途 | レート制限 | 実機接続 |
|------|---------------|------|-----------|---------|
| デモ環境 | `pk_demo_` | 評価・デモ | 100 req/hour | ❌ モックのみ |
| テスト環境 | `pk_test_` | 開発・テスト | 1000 req/hour | ❌ モックのみ |
| 本番環境 | `pk_live_` | 本番運用 | 10000 req/hour | ✅ 実機接続 |

#### APIキーの管理

**保存場所**:
```bash
# 環境変数（推奨）
export NET8_API_KEY="pk_test_abc123def456..."

# .envファイル
NET8_API_KEY=pk_test_abc123def456...
NET8_API_BASE=https://mgg-webservice-production.up.railway.app

# Secrets Manager（本番推奨）
AWS Secrets Manager
Google Cloud Secret Manager
Azure Key Vault
```

**セキュリティ注意事項**:
- ✅ サーバーサイドでのみ使用
- ✅ 環境変数またはSecrets Managerで管理
- ✅ GitにコミットしないLevel (.gitignore追加)
- ❌ フロントエンドJavaScriptに埋め込まない
- ❌ ログに出力しない
- ❌ 第三者と共有しない

### 3. 開発環境のセットアップ

#### Node.js環境

**プロジェクト初期化**:
```bash
# プロジェクトディレクトリ作成
mkdir my-net8-app
cd my-net8-app

# package.json作成
npm init -y

# 依存パッケージインストール
npm install axios dotenv express peerjs-client

# TypeScript環境（推奨）
npm install --save-dev typescript @types/node @types/express
npx tsc --init
```

**ディレクトリ構造**:
```
my-net8-app/
├── src/
│   ├── config/
│   │   └── net8.config.ts       # NET8設定
│   ├── services/
│   │   ├── net8.service.ts      # NET8 SDKラッパー
│   │   └── webrtc.service.ts    # WebRTC管理
│   ├── controllers/
│   │   └── game.controller.ts   # ゲームコントローラー
│   ├── middleware/
│   │   └── auth.middleware.ts   # 認証ミドルウェア
│   └── app.ts                   # メインアプリ
├── .env                         # 環境変数
├── .env.example                 # 環境変数サンプル
├── .gitignore
├── package.json
└── tsconfig.json
```

**環境変数設定** (`.env`):
```bash
# NET8 SDK設定
NET8_API_KEY=pk_test_abc123def456...
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
NET8_ENVIRONMENT=test

# アプリケーション設定
APP_PORT=3000
APP_ENV=development

# データベース設定
DB_HOST=localhost
DB_PORT=3306
DB_NAME=my_app
DB_USER=app_user
DB_PASSWORD=secure_password

# WebRTC設定
PEERJS_HOST=dockerfilesignaling-production.up.railway.app
PEERJS_PORT=443
PEERJS_SECURE=true
PEERJS_KEY=peerjs
```

#### PHP環境

**Composer初期化**:
```bash
# プロジェクトディレクトリ作成
mkdir my-net8-app
cd my-net8-app

# composer.json作成
composer init

# 依存パッケージインストール
composer require guzzlehttp/guzzle vlucas/phpdotenv monolog/monolog

# 自動読み込み設定
composer dump-autoload
```

**ディレクトリ構造**:
```
my-net8-app/
├── src/
│   ├── Config/
│   │   └── Net8Config.php
│   ├── Services/
│   │   └── Net8Service.php
│   ├── Controllers/
│   │   └── GameController.php
│   └── Middleware/
│       └── AuthMiddleware.php
├── public/
│   └── index.php
├── .env
├── .env.example
├── composer.json
└── composer.lock
```

**環境変数設定** (`.env`):
```bash
NET8_API_KEY=pk_test_abc123def456...
NET8_API_BASE=https://mgg-webservice-production.up.railway.app
```

---

## 認証システム

### API Key認証の詳細

#### 認証フロー

```
1. クライアント → パートナーサーバー
   - ユーザーログイン
   - セッショントークン発行

2. パートナーサーバー → NET8 API
   ┌─────────────────────────────────┐
   │ Authorization: Bearer pk_test_...│
   │ Content-Type: application/json  │
   │                                 │
   │ {                               │
   │   "userId": "user_12345",       │
   │   "modelId": "HOKUTO4GO"        │
   │ }                               │
   └─────────────────────────────────┘
              ↓
3. NET8 API Gateway
   - API Key検証
   - レート制限チェック
   - ユーザー存在確認
              ↓
4. レスポンス
   ┌─────────────────────────────────┐
   │ HTTP/1.1 200 OK                 │
   │ Content-Type: application/json  │
   │                                 │
   │ {                               │
   │   "success": true,              │
   │   "sessionId": "gs_xxx...",     │
   │   "newBalance": 9900            │
   │ }                               │
   └─────────────────────────────────┘
```

#### Authorizationヘッダーの形式

**正しい形式**:
```
Authorization: Bearer pk_test_abc123def456789
```

**間違った形式**:
```
❌ Authorization: pk_test_abc123def456789        # "Bearer "が無い
❌ Authorization: Bearer: pk_test_abc123def456789 # コロンが余分
❌ X-API-Key: pk_test_abc123def456789             # ヘッダー名が違う
```

#### 実装例

**JavaScript/TypeScript**:
```typescript
import axios from 'axios';

const API_BASE = 'https://mgg-webservice-production.up.railway.app';
const API_KEY = process.env.NET8_API_KEY;

const client = axios.create({
  baseURL: API_BASE,
  headers: {
    'Authorization': `Bearer ${API_KEY}`,
    'Content-Type': 'application/json'
  },
  timeout: 30000
});

// リクエスト送信
async function startGame(userId: string, modelId: string) {
  try {
    const response = await client.post('/api/v1/game_start.php', {
      userId,
      modelId
    });
    return response.data;
  } catch (error) {
    console.error('Game start failed:', error);
    throw error;
  }
}
```

**PHP**:
```php
<?php
use GuzzleHttp\Client;

$apiKey = getenv('NET8_API_KEY');
$apiBase = 'https://mgg-webservice-production.up.railway.app';

$client = new Client([
    'base_uri' => $apiBase,
    'headers' => [
        'Authorization' => "Bearer {$apiKey}",
        'Content-Type' => 'application/json'
    ],
    'timeout' => 30
]);

function startGame($userId, $modelId) {
    global $client;

    try {
        $response = $client->post('/api/v1/game_start.php', [
            'json' => [
                'userId' => $userId,
                'modelId' => $modelId
            ]
        ]);

        return json_decode($response->getBody(), true);
    } catch (\Exception $e) {
        error_log("Game start failed: " . $e->getMessage());
        throw $e;
    }
}
?>
```

**Python**:
```python
import os
import requests

API_KEY = os.getenv('NET8_API_KEY')
API_BASE = 'https://mgg-webservice-production.up.railway.app'

headers = {
    'Authorization': f'Bearer {API_KEY}',
    'Content-Type': 'application/json'
}

def start_game(user_id: str, model_id: str):
    try:
        response = requests.post(
            f'{API_BASE}/api/v1/game_start.php',
            headers=headers,
            json={
                'userId': user_id,
                'modelId': model_id
            },
            timeout=30
        )
        response.raise_for_status()
        return response.json()
    except requests.RequestException as e:
        print(f'Game start failed: {e}')
        raise
```

### レート制限

#### 制限内容

| プラン | リクエスト数 | 期間 | 超過時の挙動 |
|--------|------------|------|-------------|
| デモ | 100 | 1時間 | 429 Too Many Requests |
| テスト | 1,000 | 1時間 | 429 Too Many Requests |
| 本番 | 10,000 | 1時間 | 429 Too Many Requests |

#### レート制限ヘッダー

```
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 950
X-RateLimit-Reset: 1700000000
```

#### レート制限エラーのハンドリング

```typescript
async function requestWithRetry<T>(
  fn: () => Promise<T>,
  maxRetries: number = 3
): Promise<T> {
  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn();
    } catch (error: any) {
      if (error.response?.status === 429) {
        const retryAfter = error.response.headers['retry-after'] || Math.pow(2, i);
        console.log(`Rate limited. Retrying after ${retryAfter}s...`);
        await new Promise(resolve => setTimeout(resolve, retryAfter * 1000));
      } else {
        throw error;
      }
    }
  }
  throw new Error('Max retries exceeded');
}

// 使用例
const gameStart = await requestWithRetry(() =>
  client.post('/api/v1/game_start.php', { userId, modelId })
);
```

---

## 詳細実装手順

### Step 1: プロジェクトセットアップ

#### 1-1. 依存パッケージのインストール

**Node.js + TypeScript**:
```bash
npm install axios dotenv peerjs-client
npm install --save-dev @types/node @types/peerjs
```

**package.json**:
```json
{
  "name": "my-net8-app",
  "version": "1.0.0",
  "scripts": {
    "dev": "ts-node src/app.ts",
    "build": "tsc",
    "start": "node dist/app.js"
  },
  "dependencies": {
    "axios": "^1.6.0",
    "dotenv": "^16.3.0",
    "peerjs-client": "^1.5.0",
    "express": "^4.18.0"
  },
  "devDependencies": {
    "@types/node": "^20.0.0",
    "@types/express": "^4.17.0",
    "typescript": "^5.0.0"
  }
}
```

#### 1-2. TypeScript設定

**tsconfig.json**:
```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "commonjs",
    "lib": ["ES2020"],
    "outDir": "./dist",
    "rootDir": "./src",
    "strict": true,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "forceConsistentCasingInFileNames": true,
    "resolveJsonModule": true
  },
  "include": ["src/**/*"],
  "exclude": ["node_modules", "dist"]
}
```

### Step 2: NET8 SDKラッパーの実装

#### 2-1. 設定ファイル

**src/config/net8.config.ts**:
```typescript
import dotenv from 'dotenv';
dotenv.config();

export interface Net8Config {
  apiKey: string;
  apiBase: string;
  environment: 'demo' | 'test' | 'live';
  timeout: number;
  retryAttempts: number;
  peerjsHost: string;
  peerjsPort: number;
  peerjsSecure: boolean;
}

export const net8Config: Net8Config = {
  apiKey: process.env.NET8_API_KEY || '',
  apiBase: process.env.NET8_API_BASE || 'https://mgg-webservice-production.up.railway.app',
  environment: (process.env.NET8_ENVIRONMENT as any) || 'test',
  timeout: parseInt(process.env.NET8_TIMEOUT || '30000'),
  retryAttempts: parseInt(process.env.NET8_RETRY_ATTEMPTS || '3'),
  peerjsHost: process.env.PEERJS_HOST || 'dockerfilesignaling-production.up.railway.app',
  peerjsPort: parseInt(process.env.PEERJS_PORT || '443'),
  peerjsSecure: process.env.PEERJS_SECURE === 'true'
};

// 設定検証
if (!net8Config.apiKey) {
  throw new Error('NET8_API_KEY is required');
}
```

#### 2-2. NET8 SDKサービス

**src/services/net8.service.ts**:
```typescript
import axios, { AxiosInstance, AxiosError } from 'axios';
import { net8Config } from '../config/net8.config';

export interface GameStartRequest {
  userId: string;
  modelId: string;
}

export interface GameStartResponse {
  success: boolean;
  sessionId: string;
  newBalance: number;
  pointsConsumed: number;
  memberNo: number;
  machine: {
    id: number;
    modelId: string;
    modelName: string;
  };
  webrtc: {
    peerId: string;
    signalingServer: string;
    stunServers: string[];
    turnServers: any[];
  };
}

export interface GameEndRequest {
  sessionId: string;
  result: 'win' | 'lose';
  pointsWon: number;
}

export interface GameEndResponse {
  success: boolean;
  newBalance: number;
  transaction: {
    id: number;
    balanceBefore: number;
    balanceAfter: number;
    pointsWon: number;
  };
}

export interface PlayHistoryResponse {
  success: boolean;
  history: Array<{
    sessionId: string;
    modelName: string;
    result: string;
    pointsConsumed: number;
    pointsWon: number;
    netProfit: number;
    startTime: string;
    endTime: string;
    duration: number;
  }>;
  pagination: {
    currentPage: number;
    totalPages: number;
    totalRecords: number;
    recordsPerPage: number;
  };
}

export class Net8Service {
  private client: AxiosInstance;

  constructor() {
    this.client = axios.create({
      baseURL: net8Config.apiBase,
      headers: {
        'Authorization': `Bearer ${net8Config.apiKey}`,
        'Content-Type': 'application/json'
      },
      timeout: net8Config.timeout
    });

    // リクエストインターセプター
    this.client.interceptors.request.use(
      (config) => {
        console.log(`[NET8] ${config.method?.toUpperCase()} ${config.url}`);
        return config;
      },
      (error) => {
        console.error('[NET8] Request error:', error);
        return Promise.reject(error);
      }
    );

    // レスポンスインターセプター
    this.client.interceptors.response.use(
      (response) => {
        console.log(`[NET8] Response ${response.status}:`, response.data);
        return response;
      },
      (error: AxiosError) => {
        console.error('[NET8] Response error:', error.response?.data);
        return Promise.reject(error);
      }
    );
  }

  /**
   * ゲーム開始
   */
  async startGame(request: GameStartRequest): Promise<GameStartResponse> {
    try {
      const response = await this.client.post<GameStartResponse>(
        '/api/v1/game_start.php',
        request
      );
      return response.data;
    } catch (error) {
      this.handleError(error, 'Game start failed');
      throw error;
    }
  }

  /**
   * ゲーム終了
   */
  async endGame(request: GameEndRequest): Promise<GameEndResponse> {
    try {
      const response = await this.client.post<GameEndResponse>(
        '/api/v1/game_end.php',
        request
      );
      return response.data;
    } catch (error) {
      this.handleError(error, 'Game end failed');
      throw error;
    }
  }

  /**
   * プレイ履歴取得
   */
  async getPlayHistory(userId: string, page: number = 1): Promise<PlayHistoryResponse> {
    try {
      const response = await this.client.get<PlayHistoryResponse>(
        `/api/v1/play_history.php?userId=${userId}&page=${page}`
      );
      return response.data;
    } catch (error) {
      this.handleError(error, 'Get play history failed');
      throw error;
    }
  }

  /**
   * エラーハンドリング
   */
  private handleError(error: any, context: string): void {
    if (axios.isAxiosError(error)) {
      const statusCode = error.response?.status;
      const errorData = error.response?.data;

      switch (statusCode) {
        case 401:
          console.error(`[NET8] ${context}: Invalid API Key`);
          break;
        case 429:
          console.error(`[NET8] ${context}: Rate limit exceeded`);
          break;
        case 500:
          console.error(`[NET8] ${context}: Internal server error`);
          break;
        default:
          console.error(`[NET8] ${context}:`, errorData);
      }
    } else {
      console.error(`[NET8] ${context}:`, error);
    }
  }
}
```

### Step 3: WebRTC統合

#### 3-1. WebRTCサービス

**src/services/webrtc.service.ts**:
```typescript
import Peer, { MediaConnection } from 'peerjs';
import { net8Config } from '../config/net8.config';

export interface WebRTCConfig {
  peerId: string;
  videoElement: HTMLVideoElement;
  onConnected?: () => void;
  onDisconnected?: () => void;
  onError?: (error: Error) => void;
}

export class WebRTCService {
  private peer: Peer | null = null;
  private mediaConnection: MediaConnection | null = null;

  /**
   * WebRTC接続確立
   */
  async connect(config: WebRTCConfig): Promise<void> {
    return new Promise((resolve, reject) => {
      try {
        // Peerインスタンス作成
        this.peer = new Peer({
          host: net8Config.peerjsHost,
          port: net8Config.peerjsPort,
          secure: net8Config.peerjsSecure,
          path: '/peerjs',
          config: {
            iceServers: [
              { urls: 'stun:stun.l.google.com:19302' },
              { urls: 'stun:stun1.l.google.com:19302' }
            ]
          },
          debug: 2 // ログレベル
        });

        this.peer.on('open', (id) => {
          console.log('[WebRTC] Peer connected with ID:', id);

          // 実機へ接続
          this.callMachine(config.peerId, config.videoElement);

          resolve();
          config.onConnected?.();
        });

        this.peer.on('error', (error) => {
          console.error('[WebRTC] Peer error:', error);
          reject(error);
          config.onError?.(error);
        });

        this.peer.on('close', () => {
          console.log('[WebRTC] Peer connection closed');
          config.onDisconnected?.();
        });

      } catch (error) {
        console.error('[WebRTC] Connection failed:', error);
        reject(error);
      }
    });
  }

  /**
   * 実機へ通話接続
   */
  private callMachine(peerId: string, videoElement: HTMLVideoElement): void {
    if (!this.peer) {
      throw new Error('Peer not initialized');
    }

    console.log(`[WebRTC] Calling machine peer: ${peerId}`);

    this.mediaConnection = this.peer.call(peerId, new MediaStream());

    this.mediaConnection.on('stream', (remoteStream) => {
      console.log('[WebRTC] Received remote stream');
      videoElement.srcObject = remoteStream;
      videoElement.play();
    });

    this.mediaConnection.on('close', () => {
      console.log('[WebRTC] Media connection closed');
    });

    this.mediaConnection.on('error', (error) => {
      console.error('[WebRTC] Media connection error:', error);
    });
  }

  /**
   * 接続切断
   */
  disconnect(): void {
    if (this.mediaConnection) {
      this.mediaConnection.close();
      this.mediaConnection = null;
    }

    if (this.peer) {
      this.peer.destroy();
      this.peer = null;
    }

    console.log('[WebRTC] Disconnected');
  }
}
```

### Step 4: 完全な統合例

#### 4-1. Expressサーバー実装

**src/app.ts**:
```typescript
import express, { Request, Response } from 'express';
import { Net8Service } from './services/net8.service';

const app = express();
const net8 = new Net8Service();

app.use(express.json());

// ゲーム開始エンドポイント
app.post('/game/start', async (req: Request, res: Response) => {
  try {
    const { userId, modelId } = req.body;

    // バリデーション
    if (!userId || !modelId) {
      return res.status(400).json({
        error: 'userId and modelId are required'
      });
    }

    // NET8 game_start呼び出し
    const gameStart = await net8.startGame({ userId, modelId });

    res.json(gameStart);
  } catch (error: any) {
    console.error('Game start error:', error);
    res.status(500).json({
      error: error.message
    });
  }
});

// ゲーム終了エンドポイント
app.post('/game/end', async (req: Request, res: Response) => {
  try {
    const { sessionId, result, pointsWon } = req.body;

    // バリデーション
    if (!sessionId || !result) {
      return res.status(400).json({
        error: 'sessionId and result are required'
      });
    }

    // NET8 game_end呼び出し
    const gameEnd = await net8.endGame({ sessionId, result, pointsWon });

    res.json(gameEnd);
  } catch (error: any) {
    console.error('Game end error:', error);
    res.status(500).json({
      error: error.message
    });
  }
});

// プレイ履歴エンドポイント
app.get('/game/history/:userId', async (req: Request, res: Response) => {
  try {
    const { userId } = req.params;
    const page = parseInt(req.query.page as string) || 1;

    const history = await net8.getPlayHistory(userId, page);

    res.json(history);
  } catch (error: any) {
    console.error('Get history error:', error);
    res.status(500).json({
      error: error.message
    });
  }
});

const PORT = process.env.APP_PORT || 3000;
app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});
```

#### 4-2. フロントエンド実装（HTML + JavaScript）

**public/index.html**:
```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 SDK Demo</title>
    <script src="https://cdn.jsdelivr.net/npm/peerjs@1.5.0/dist/peerjs.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        #videoContainer {
            width: 100%;
            max-width: 800px;
            margin: 20px auto;
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }
        #video {
            width: 100%;
            height: auto;
        }
        .controls {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin: 20px 0;
        }
        button {
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background: #4CAF50;
            color: white;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        button.danger {
            background: #f44336;
        }
        .info {
            background: #f0f0f0;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>NET8 SDK Demo</h1>

    <div class="info">
        <div><strong>User ID:</strong> <span id="userId">user_12345</span></div>
        <div><strong>Balance:</strong> <span id="balance">10000</span> ポイント</div>
        <div><strong>Session ID:</strong> <span id="sessionId">-</span></div>
    </div>

    <div class="controls">
        <select id="modelSelect">
            <option value="HOKUTO4GO">北斗の拳</option>
            <option value="ZENIGATA01">銭形</option>
            <option value="REINYAN01">麗-花萌ゆる8人の皇子たち-</option>
        </select>
        <button id="startBtn" onclick="startGame()">ゲーム開始</button>
        <button id="endBtn" onclick="endGame()" disabled>ゲーム終了</button>
    </div>

    <div id="videoContainer">
        <video id="video" autoplay playsinline></video>
    </div>

    <script>
        let currentSessionId = null;
        let peer = null;
        let call = null;

        // ゲーム開始
        async function startGame() {
            const userId = document.getElementById('userId').textContent;
            const modelId = document.getElementById('modelSelect').value;

            document.getElementById('startBtn').disabled = true;

            try {
                // game_start API呼び出し
                const response = await fetch('/game/start', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ userId, modelId })
                });

                const data = await response.json();

                if (data.success) {
                    currentSessionId = data.sessionId;
                    document.getElementById('sessionId').textContent = currentSessionId;
                    document.getElementById('balance').textContent = data.newBalance;

                    // WebRTC接続
                    await connectWebRTC(data.webrtc.peerId);

                    document.getElementById('endBtn').disabled = false;
                    alert('ゲームが開始されました！');
                } else {
                    throw new Error(data.error || 'Game start failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('ゲーム開始に失敗しました: ' + error.message);
                document.getElementById('startBtn').disabled = false;
            }
        }

        // ゲーム終了
        async function endGame() {
            if (!currentSessionId) {
                alert('アクティブなセッションがありません');
                return;
            }

            document.getElementById('endBtn').disabled = true;

            try {
                // 勝敗とポイントを設定（実際はゲームロジックから取得）
                const result = 'win';
                const pointsWon = 500;

                // game_end API呼び出し
                const response = await fetch('/game/end', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sessionId: currentSessionId,
                        result,
                        pointsWon
                    })
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('balance').textContent = data.newBalance;
                    document.getElementById('sessionId').textContent = '-';

                    // WebRTC切断
                    disconnectWebRTC();

                    currentSessionId = null;
                    document.getElementById('startBtn').disabled = false;
                    alert(`ゲーム終了！ 新しい残高: ${data.newBalance}ポイント`);
                } else {
                    throw new Error(data.error || 'Game end failed');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('ゲーム終了に失敗しました: ' + error.message);
                document.getElementById('endBtn').disabled = false;
            }
        }

        // WebRTC接続
        async function connectWebRTC(peerId) {
            return new Promise((resolve, reject) => {
                peer = new Peer({
                    host: 'dockerfilesignaling-production.up.railway.app',
                    port: 443,
                    secure: true,
                    path: '/peerjs'
                });

                peer.on('open', (id) => {
                    console.log('Peer connected:', id);

                    // 実機へ接続
                    call = peer.call(peerId, new MediaStream());

                    call.on('stream', (remoteStream) => {
                        console.log('Received stream');
                        const video = document.getElementById('video');
                        video.srcObject = remoteStream;
                        resolve();
                    });

                    call.on('error', (error) => {
                        console.error('Call error:', error);
                        reject(error);
                    });
                });

                peer.on('error', (error) => {
                    console.error('Peer error:', error);
                    reject(error);
                });
            });
        }

        // WebRTC切断
        function disconnectWebRTC() {
            if (call) {
                call.close();
                call = null;
            }
            if (peer) {
                peer.destroy();
                peer = null;
            }
            const video = document.getElementById('video');
            video.srcObject = null;
        }
    </script>
</body>
</html>
```

---

## セキュリティ

### セキュリティベストプラクティス

#### 1. API Keyの保護

**環境変数を使用**:
```bash
# .env
NET8_API_KEY=pk_live_abc123...

# .gitignore
.env
.env.local
.env.production
```

**Secrets Managerを使用（本番推奨）**:
```typescript
// AWS Secrets Manager
import { SecretsManagerClient, GetSecretValueCommand } from '@aws-sdk/client-secrets-manager';

async function getApiKey() {
  const client = new SecretsManagerClient({ region: 'ap-northeast-1' });
  const response = await client.send(
    new GetSecretValueCommand({ SecretId: 'net8/api-key' })
  );
  return JSON.parse(response.SecretString).apiKey;
}
```

#### 2. HTTPS強制

**Expressミドルウェア**:
```typescript
app.use((req, res, next) => {
  if (req.headers['x-forwarded-proto'] !== 'https' && process.env.NODE_ENV === 'production') {
    return res.redirect(`https://${req.headers.host}${req.url}`);
  }
  next();
});
```

#### 3. CORS設定

**適切なCORS設定**:
```typescript
import cors from 'cors';

app.use(cors({
  origin: ['https://yourdomain.com', 'https://app.yourdomain.com'],
  credentials: true,
  methods: ['GET', 'POST'],
  allowedHeaders: ['Content-Type', 'Authorization']
}));
```

#### 4. 入力検証

**バリデーションミドルウェア**:
```typescript
import { body, validationResult } from 'express-validator';

app.post('/game/start',
  body('userId').isString().notEmpty(),
  body('modelId').isString().notEmpty(),
  (req, res, next) => {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }
    next();
  },
  async (req, res) => {
    // ゲーム開始処理
  }
);
```

#### 5. レート制限（アプリケーション側）

**express-rate-limitを使用**:
```typescript
import rateLimit from 'express-rate-limit';

const limiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15分
  max: 100, // 最大100リクエスト
  message: 'Too many requests from this IP'
});

app.use('/game/', limiter);
```

---

## パフォーマンス最適化

### 1. コネクションプーリング

**データベース接続プール**:
```typescript
import mysql from 'mysql2/promise';

const pool = mysql.createPool({
  host: process.env.DB_HOST,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
  database: process.env.DB_NAME,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
});
```

### 2. キャッシング

**Redisキャッシュ**:
```typescript
import Redis from 'ioredis';

const redis = new Redis({
  host: process.env.REDIS_HOST,
  port: parseInt(process.env.REDIS_PORT || '6379')
});

// ユーザー残高をキャッシュ
async function getUserBalance(userId: string): Promise<number> {
  const cached = await redis.get(`balance:${userId}`);
  if (cached) {
    return parseInt(cached);
  }

  // DBから取得
  const balance = await fetchBalanceFromDB(userId);

  // キャッシュに保存（60秒）
  await redis.setex(`balance:${userId}`, 60, balance.toString());

  return balance;
}
```

### 3. 非同期処理

**バックグラウンドジョブ**:
```typescript
import Bull from 'bull';

const gameQueue = new Bull('game-processing', {
  redis: { host: 'localhost', port: 6379 }
});

// ゲーム終了処理をキューに追加
gameQueue.add('end-game', {
  sessionId,
  result,
  pointsWon
});

// ワーカー
gameQueue.process('end-game', async (job) => {
  const { sessionId, result, pointsWon } = job.data;
  await net8.endGame({ sessionId, result, pointsWon });
});
```

---

## 本番環境移行

### デプロイチェックリスト

#### 環境設定
- [ ] 本番用API Key（pk_live_xxx）を取得
- [ ] 環境変数を本番用に設定
- [ ] HTTPS証明書の設定
- [ ] データベース接続情報の確認

#### セキュリティ
- [ ] API Keyの暗号化保存
- [ ] CORS設定の確認
- [ ] レート制限の設定
- [ ] ログ出力のサニタイズ

#### パフォーマンス
- [ ] コネクションプーリング設定
- [ ] キャッシュ戦略の実装
- [ ] CDN設定

#### 監視
- [ ] エラー監視（Sentry等）
- [ ] パフォーマンス監視
- [ ] ログ集約
- [ ] アラート設定

---

## 運用管理

### ログ管理

**構造化ログ**:
```typescript
import winston from 'winston';

const logger = winston.createLogger({
  level: 'info',
  format: winston.format.json(),
  transports: [
    new winston.transports.File({ filename: 'error.log', level: 'error' }),
    new winston.transports.File({ filename: 'combined.log' })
  ]
});

logger.info('Game started', {
  userId: 'user_123',
  modelId: 'HOKUTO4GO',
  sessionId: 'gs_xxx'
});
```

### エラー監視

**Sentry統合**:
```typescript
import * as Sentry from '@sentry/node';

Sentry.init({
  dsn: process.env.SENTRY_DSN,
  environment: process.env.NODE_ENV
});

app.use(Sentry.Handlers.errorHandler());
```

---

## ベストプラクティス

1. **エラーハンドリング**: すべてのAPI呼び出しをtry-catchで囲む
2. **ログ出力**: 重要なイベントを構造化ログで記録
3. **テスト**: 単体テスト・統合テストを必ず実装
4. **ドキュメント**: コードコメントとREADMEを充実
5. **監視**: 本番環境では必ず監視ツールを導入

---

**© 2025 NET8. All rights reserved.**
