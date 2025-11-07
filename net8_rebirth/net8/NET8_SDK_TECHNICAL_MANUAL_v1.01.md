# NET8 Gaming SDK - 詳細マニュアル（技術仕様書） v1.01

**最終更新**: 2025-11-07
**対象**: 開発者・システム管理者・アーキテクト
**バージョン**: NET8 SDK v1.0.1-beta

---

## 📑 目次

1. [システムアーキテクチャ](#システムアーキテクチャ)
2. [環境別動作仕様](#環境別動作仕様)
3. [API仕様](#api仕様)
4. [データベーススキーマ](#データベーススキーマ)
5. [セキュリティ](#セキュリティ)
6. [デプロイメント](#デプロイメント)
7. [パフォーマンス最適化](#パフォーマンス最適化)
8. [トラブルシューティング](#トラブルシューティング)
9. [変更履歴](#変更履歴)

---

## システムアーキテクチャ

### 全体構成図

```
┌────────────────┐
│   Client       │
│  (Browser)     │
│                │
│  ┌──────────┐  │
│  │ NET8 SDK │  │  ← JavaScript SDK (net8-sdk-beta.js)
│  └──────────┘  │
└───────┬────────┘
        │ HTTPS
        ↓
┌────────────────────────────────────────┐
│         Railway Platform               │
│  ┌──────────────────────────────────┐ │
│  │  Apache + PHP-FPM                │ │
│  │                                  │ │
│  │  ┌────────────┐  ┌────────────┐ │ │
│  │  │  API v1    │  │   WebRTC   │ │ │
│  │  │            │  │  Signaling │ │ │
│  │  │ • auth     │  │            │ │ │
│  │  │ • models   │  │  PeerJS    │ │ │
│  │  │ • game_start│ │  Socket.io │ │ │
│  │  └────────────┘  └────────────┘ │ │
│  └──────────────────────────────────┘ │
└────────────────┬───────────────────────┘
                 │
                 ↓
     ┌───────────────────────┐
     │  GCP Cloud SQL        │
     │  MySQL 8.0            │
     │  (136.116.70.86)      │
     │                       │
     │  • api_keys           │
     │  • api_usage_logs     │
     │  • mst_model          │
     │  • dat_machine        │
     └───────────────────────┘

[本番環境のみ]
        ↓
┌────────────────────┐
│  Physical Machines │
│  (パチンコ実機)     │
│                    │
│  • WebRTC Stream   │
│  • Control Signal  │
└────────────────────┘
```

### コンポーネント

#### 1. **JavaScript SDK (net8-sdk-beta.js)**
- **役割**: クライアントサイドのインターフェース
- **サイズ**: 242行、約10KB（minify前）
- **依存**: なし（Vanilla JavaScript）
- **機能**:
  - API認証
  - 機種一覧取得
  - ゲームインスタンス管理
  - WebRTC接続管理
  - イベントハンドリング

#### 2. **REST API (PHP 7.4+)**
- **役割**: バックエンドロジック・データアクセス
- **フレームワーク**: カスタムPHP
- **主要エンドポイント**:
  - `/api/v1/auth.php` - JWT認証
  - `/api/v1/models.php` - 機種一覧
  - `/api/v1/game_start.php` - ゲーム開始

#### 3. **データベース (MySQL 8.0)**
- **ホスト**: GCP Cloud SQL (136.116.70.86)
- **エンジン**: InnoDB
- **文字セット**: utf8mb4
- **主要テーブル**: 4つ（後述）

#### 4. **WebRTC Signaling Server**
- **実装**: Socket.io + PeerJS
- **役割**: P2P接続確立のシグナリング
- **本番のみ**: テスト環境ではモック

---

## 環境別動作仕様

### v1.01の重要機能：環境分離とモックシステム

NET8 SDK v1.01では、**実機なしで完全テスト可能**なモックシステムを実装しています。

### 環境判定フロー

```
APIリクエスト受信
    ↓
JWTトークン解析
    ↓
api_keys テーブルから environment 取得
    ↓
┌─────────────────────────────┐
│ environment == 'test' or    │
│ environment == 'staging'    │
└─────────────────────────────┘
    │ YES              │ NO
    ↓                  ↓
[モックモード]      [本番モード]
    ↓                  ↓
仮想マシン生成      実機検索
モックデータ返却    実データ返却
```

### 環境別詳細仕様

| 項目 | test | staging | production |
|------|------|---------|------------|
| APIキープレフィックス | `pk_demo_*` | `pk_staging_*` | `pk_live_*` |
| マシン検索 | モック自動生成 | モック自動生成 | データベース検索 |
| machine_no | 9999（固定） | 9999（固定） | 実機ID |
| signaling_id | `mock_sig_*` | `mock_sig_*` | 実機シグナリングID |
| signalingHost | `mock-signaling.net8.test` | `mock-signaling.net8.test` | 実際のホスト |
| cameraStreamUrl | `mock://camera.net8.test/stream/*` | `mock://camera.net8.test/stream/*` | 実際のストリームURL |
| レスポンス mock フラグ | `true` | `true` | `false` |
| 料金 | 無料 | 無料 | 従量課金 |
| 実機接続 | なし | なし | あり |

### モックレスポンス例

```json
{
  "success": true,
  "environment": "test",
  "sessionId": "gs_673ac7d12e4f8_1731844049",
  "machineNo": 9999,
  "signalingId": "mock_sig_8c736521",
  "model": {
    "id": "HOKUTO4GO",
    "name": "北斗の拳　初号機",
    "category": "slot"
  },
  "signaling": {
    "signalingId": "mock_sig_8c736521",
    "host": "mock-signaling.net8.test",
    "port": 443,
    "secure": true,
    "path": "/socket.io",
    "iceServers": [
      { "urls": "stun:stun.l.google.com:19302" }
    ],
    "mock": true
  },
  "camera": {
    "cameraNo": 9999,
    "streamUrl": "mock://camera.net8.test/stream/HOKUTO4GO",
    "mock": true
  },
  "playUrl": "/data/play_v2/index.php?NO=9999",
  "mock": true
}
```

---

## API仕様

### 共通仕様

**ベースURL**: `https://mgg-webservice-production.up.railway.app/api/v1/`

**リクエストヘッダー**:
```
Content-Type: application/json
Authorization: Bearer <JWT_TOKEN>  (auth.php 以外)
```

**エラーレスポンス形式**:
```json
{
  "error": "ERROR_CODE",
  "message": "Human readable error message",
  "environment": "test"  (v1.01以降)
}
```

**HTTPステータスコード**:
- `200 OK` - 成功
- `400 Bad Request` - リクエスト不正
- `401 Unauthorized` - 認証失敗
- `404 Not Found` - リソースが存在しない
- `503 Service Unavailable` - マシン利用不可（本番のみ）
- `500 Internal Server Error` - サーバーエラー

### 1. 認証API

#### `POST /api/v1/auth.php`

**説明**: APIキーを検証してJWTトークンを発行

**リクエスト**:
```json
{
  "apiKey": "pk_demo_12345"
}
```

**レスポンス（成功）**:
```json
{
  "success": true,
  "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
  "expiresIn": 3600,
  "environment": "test"
}
```

**JWTペイロード構造**:
```json
{
  "api_key_id": 1,
  "user_id": null,
  "exp": 1731844049
}
```

**エラーコード**:
- `MISSING_API_KEY` - APIキーが指定されていない
- `INVALID_API_KEY` - 無効または期限切れのAPIキー

**セキュリティ**:
- JWT署名アルゴリズム: HS256
- シークレットキー: `NET8_SECRET_KEY_CHANGE_ME` (本番では変更必須)
- トークン有効期限: 1時間

### 2. 機種一覧API

#### `GET /api/v1/models.php?apiKey=<API_KEY>`

**説明**: 利用可能なゲーム機種の一覧を取得

**クエリパラメータ**:
- `apiKey` (必須): APIキー

**レスポンス**:
```json
{
  "success": true,
  "count": 3,
  "models": [
    {
      "id": "HOKUTO4GO",
      "name": "北斗の拳　初号機",
      "category": "slot",
      "maker": "不明",
      "thumbnail": "/images/models/hokuto4go.jpg",
      "detailImage": "/images/models/3c9dfca43c05b50974ffd07acbe385966efbb230.jpg",
      "specs": {
        "prizeballData": null,
        "layoutData": "{...}"
      }
    }
  ]
}
```

**環境依存**: なし（全環境で同じデータを返す）

### 3. ゲーム開始API

#### `POST /api/v1/game_start.php`

**説明**: ゲームセッションを開始し、マシン情報を返却

**リクエストヘッダー**:
```
Authorization: Bearer <JWT_TOKEN>
Content-Type: application/json
```

**リクエストボディ**:
```json
{
  "modelId": "HOKUTO4GO"
}
```

**レスポンス（成功）**:

前述の「モックレスポンス例」参照

**エラーコード**:
- `UNAUTHORIZED` - Authorizationヘッダーがない
- `MISSING_MODEL_ID` - modelIdが指定されていない
- `MODEL_NOT_FOUND` - 指定された機種が存在しない
- `NO_AVAILABLE_MACHINE` - 利用可能なマシンがない（**本番のみ**）

**重要な環境別動作**:

| 環境 | マシン検索 | エラー発生 |
|------|-----------|----------|
| test | モック生成（常に成功） | なし |
| staging | モック生成（常に成功） | なし |
| production | データベース検索 | NO_AVAILABLE_MACHINE 可能 |

**本番環境のマシン検索SQL**:
```sql
SELECT m.machine_no, m.signaling_id, m.camera_no, m.machine_status
FROM dat_machine m
WHERE m.model_no = :model_no
  AND m.del_flg = 0
  AND m.machine_status = 0
  AND m.end_date >= CURDATE()
  AND NOT EXISTS (
    SELECT 1 FROM lnk_machine lm
    WHERE lm.machine_no = m.machine_no
    AND lm.assign_flg = 1
  )
LIMIT 1
```

---

## データベーススキーマ

### テーブル一覧

1. `api_keys` - APIキー管理
2. `api_usage_logs` - API使用ログ
3. `mst_model` - ゲーム機種マスタ
4. `dat_machine` - 実機情報

### 1. api_keys テーブル

```sql
CREATE TABLE `api_keys` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NULL,
  `key_value` VARCHAR(100) NOT NULL UNIQUE,
  `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
  `name` VARCHAR(100) NULL,
  `environment` VARCHAR(20) NOT NULL DEFAULT 'test',  -- 重要！
  `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000,
  `is_active` TINYINT(4) NOT NULL DEFAULT 1,
  `last_used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_key_value` (`key_value`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**環境フィールド**:
- `test`: テスト環境（モック動作）
- `staging`: ステージング環境（モック動作）
- `live` / `production`: 本番環境（実機接続）

**初期データ**:
```sql
INSERT INTO api_keys (key_value, name, environment, rate_limit, is_active)
VALUES
  ('pk_demo_12345', 'Demo API Key', 'test', 10000, 1),
  ('pk_live_abcdef123456', 'Production API Key 1', 'live', 100000, 1);
```

### 2. api_usage_logs テーブル

```sql
CREATE TABLE `api_usage_logs` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `api_key_id` INT(10) UNSIGNED NOT NULL,
  `endpoint` VARCHAR(255) NOT NULL,
  `method` VARCHAR(10) NOT NULL,
  `status_code` INT(10) UNSIGNED NULL,
  `response_time_ms` INT(10) UNSIGNED NULL,
  `ip_address` VARCHAR(45) NULL,
  `user_agent` VARCHAR(512) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_api_key_id` (`api_key_id`),
  KEY `idx_created_at` (`created_at`),
  FOREIGN KEY (`api_key_id`) REFERENCES `api_keys`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**用途**: API使用状況の記録・分析

### 3. mst_model テーブル

```sql
-- 既存テーブル（詳細は省略）
-- 機種情報を格納
-- 主要カラム: model_no, model_cd, model_name, category
```

### 4. dat_machine テーブル

```sql
-- 既存テーブル（詳細は省略）
-- 実機情報を格納
-- 主要カラム: machine_no, model_no, signaling_id, machine_status, end_date
```

**重要**: test/staging環境ではこのテーブルを使用しません（モックデータを返却）

---

## セキュリティ

### 1. SQL インジェクション対策

**実装**: PDO Prepared Statements

```php
// ❌ 危険（SmartDB + 文字列連結）
$sql = "SELECT * FROM api_keys WHERE key_value = '" . $apiKey . "'";

// ✅ 安全（PDO + Prepared Statement）
$sql = "SELECT * FROM api_keys WHERE key_value = :api_key";
$stmt = $pdo->prepare($sql);
$stmt->execute(['api_key' => $apiKey]);
```

**全APIで実装済み**

### 2. JWT認証

**実装詳細**:
```php
// Header
$header = base64_encode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));

// Payload
$payload = base64_encode(json_encode([
  'api_key_id' => $apiKeyData['id'],
  'user_id' => $apiKeyData['user_id'],
  'exp' => time() + 3600
]));

// Signature
$signature = hash_hmac('sha256', "$header.$payload", 'NET8_SECRET_KEY_CHANGE_ME', true);
$signature = base64_encode($signature);

// JWT Token
$jwt = "$header.$payload.$signature";
```

**セキュリティ推奨事項**:
1. 本番環境では `NET8_SECRET_KEY_CHANGE_ME` を変更
2. 環境変数で管理（`.env` ファイル）
3. 定期的なシークレットキーローテーション

### 3. CORS設定

**Apache設定**:
```apache
Header always set Access-Control-Allow-Origin "*"
Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
Header always set Access-Control-Allow-Headers "Content-Type, Authorization"
```

**.htaccess設定** (`/api/v1/.htaccess`):
```apache
RewriteEngine On
RewriteCond %{HTTP:Authorization} ^(.*)
RewriteRule .* - [e=HTTP_AUTHORIZATION:%1]
```

**用途**: Authorizationヘッダーをphpに渡す

### 4. レート制限

**データベースフィールド**: `api_keys.rate_limit`

**実装状況**: v1.01では未実装（v1.1で実装予定）

**計画**:
- リクエスト数カウント（1時間単位）
- 超過時は `429 Too Many Requests`
- `api_usage_logs` テーブルを活用

---

## デプロイメント

### Railway Platform

**自動デプロイフロー**:
```
GitHub main ブランチ
    ↓ push
Railway 検知
    ↓
Docker Build
    ↓
Apache + PHP-FPM コンテナ起動
    ↓
ヘルスチェック
    ↓
デプロイ完了（約30-60秒）
```

### ディレクトリ構造

```
/var/www/html/
├── api/
│   ├── v1/
│   │   ├── auth.php            # 認証API
│   │   ├── models.php          # 機種一覧API
│   │   ├── game_start.php      # ゲーム開始API (v1.01更新)
│   │   └── .htaccess           # Authorization header設定
│   ├── setup_keys_direct.php   # DBセットアップ
│   └── quick_register_machine.php  # マシン登録（開発用）
├── sdk/
│   ├── net8-sdk-beta.js        # JavaScript SDK
│   ├── demo.html               # デモページ
│   └── README.md               # SDK README
├── _etc/
│   ├── require_files.php       # 共通設定ファイル
│   └── setting.php             # データベース設定
└── data/
    └── (既存システム)
```

### Apache RewriteRule設定

**重要**: `/api/` と `/sdk/` を除外

```apache
# /api/ と /sdk/ を除外
RewriteCond %{REQUEST_URI} !^/api/
RewriteCond %{REQUEST_URI} !^/sdk/
RewriteCond %{REQUEST_URI} !^/data/
# それ以外は /data/ へリダイレクト
RewriteRule ^(.*)$ /data/$1 [L]
```

### 環境変数

**必須設定** (Railway Dashboard):
```
DB_HOST=136.116.70.86
DB_NAME=net8_production
DB_USER=net8_user
DB_PASS=***************
SIGNALING_HOST=signaling.net8.production
SIGNALING_PORT=443
SIGNALING_PATH=/socket.io
NET8_JWT_SECRET=****************  (本番では必ず変更)
```

---

## パフォーマンス最適化

### 1. データベース最適化

**インデックス**:
```sql
-- api_keys
CREATE INDEX idx_key_value ON api_keys(key_value);
CREATE INDEX idx_active ON api_keys(is_active);

-- dat_machine
CREATE INDEX idx_model_status ON dat_machine(model_no, machine_status, end_date);
```

### 2. キャッシング戦略

**v1.1で実装予定**:
- Redis/Memcached for API responses
- CDN for static assets (SDK.js)
- Browser cache headers

**推奨設定**:
```php
// SDK.js (1日キャッシュ)
header('Cache-Control: public, max-age=86400');

// API responses (キャッシュなし)
header('Cache-Control: no-cache, no-store, must-revalidate');
```

### 3. データベース接続プール

**PDO Persistent Connection**:
```php
$pdo = new PDO(
  DB_DSN,
  DB_USER,
  DB_PASS,
  [PDO::ATTR_PERSISTENT => true]
);
```

---

## トラブルシューティング

### 開発者向けデバッグ

#### 1. 環境確認

```javascript
// ブラウザコンソール
const response = await fetch('/api/v1/auth.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ apiKey: 'pk_demo_12345' })
});

const data = await response.json();
console.log('Environment:', data.environment);  // 'test' or 'live'
```

#### 2. モックフラグ確認

```javascript
// game_start レスポンスで確認
const gameData = await startGame('HOKUTO4GO');
console.log('Mock mode:', gameData.mock);       // true or false
console.log('Environment:', gameData.environment);
```

#### 3. PDOエラーログ

**サーバーログ確認**:
```bash
tail -f /var/log/apache2/error.log | grep "Game Start API Error"
```

**PHPエラーログ**:
```php
error_log('Game Start API Error: ' . $e->getMessage());
```

### 管理者向けデバッグ

#### Railway ログ確認

```bash
railway logs --tail
```

#### データベース直接確認

```sql
-- APIキー確認
SELECT id, key_value, name, environment, is_active
FROM api_keys
WHERE is_active = 1;

-- 最近のAPI使用ログ
SELECT endpoint, status_code, created_at
FROM api_usage_logs
ORDER BY created_at DESC
LIMIT 10;

-- 利用可能マシン確認（本番のみ）
SELECT m.machine_no, mm.model_cd, m.machine_status
FROM dat_machine m
JOIN mst_model mm ON m.model_no = mm.model_no
WHERE m.del_flg = 0
  AND m.machine_status = 0
  AND m.end_date >= CURDATE();
```

---

## 変更履歴

### v1.01 (2025-11-07)

**新機能**:
- ✅ ステージング/モックモード実装
  - test/staging環境で実機不要のモックデータ返却
  - モックマシン（machine_no: 9999）自動生成
  - モックシグナリング・カメラ情報
  - レスポンスに `environment` と `mock` フラグ追加

**変更点**:
- `game_start.php`: 環境判定ロジック追加
- APIレスポンスに環境情報追加
- 本番環境のみ実機検索を実行

**メリット**:
- 実機なしで完全なSDKテストが可能
- 開発・テスト環境の構築が簡単
- APIキー変更のみで本番環境へ移行

### v1.00 (2025-11-06)

**初回リリース**:
- 認証API (auth.php)
- 機種一覧API (models.php)
- ゲーム開始API (game_start.php)
- JavaScript SDK (net8-sdk-beta.js)
- PDO prepared statements
- JWT認証
- CORS対応

---

## 付録: 開発環境セットアップ

### ローカル開発環境

```bash
# 1. リポジトリクローン
git clone https://github.com/mgg00123mg-prog/mgg001.git
cd mgg001/net8_rebirth/net8

# 2. Docker起動
docker-compose up -d

# 3. データベースセットアップ
curl http://localhost/api/setup_keys_direct.php

# 4. APIテスト
curl -X POST http://localhost/api/v1/auth.php \
  -H "Content-Type: application/json" \
  -d '{"apiKey":"pk_demo_12345"}'
```

### テストスイート（v1.1実装予定）

```bash
# PHPUnit
composer install
vendor/bin/phpunit tests/

# JavaScript SDK
npm install
npm test
```

---

**NET8 Gaming SDK Technical Manual v1.01**
© 2025 NET8 Development Team

このマニュアルは技術者向けです。エンドユーザー向けには「ユーザーズマニュアル」を参照してください。
