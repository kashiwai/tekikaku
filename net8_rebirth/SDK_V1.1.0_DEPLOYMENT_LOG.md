# NET8 SDK v1.1.0 デプロイ作業記録

**作業日**: 2025-11-19
**作業者**: Claude Code AI
**最終更新**: 2025-11-19 16:50 JST

---

## ✅ 完了した作業

### 1. SDK v1.1.0 開発完了（2025-11-18）

**バージョン**: 1.0.1-beta → **1.1.0-beta**

**新機能**:
- ✅ **userId連携**: `createGame()`で`userId`パラメータをサポート
- ✅ **ポイント管理**: ゲーム開始時の消費、終了時の払い出し自動処理
- ✅ **ゲーム終了イベント**: `game.on('end', callback)`で詳細な結果取得
- ✅ **手動停止機能**: `game.stop()`でゲームを途中終了可能
- ✅ **プレイ履歴API**: `/api/v1/play_history.php`でセッション履歴取得
- ✅ **セキュリティ強化**: X-Frame-Options動的設定でiFrame埋め込み制御

---

### 2. 実装ファイル（13ファイル、2,919行追加）

#### SDK本体
- `net8/02.ソースファイル/net8_html/sdk/net8-sdk-beta.js` (v1.1.0)

#### ドキュメント
- `sdk/README_v1.1.0.md` - 完全なSDK使用マニュアル
- `sdk/SDK_VERSIONS.md` - バージョン管理とマイグレーションガイド

#### 新規API
- `api/v1/game_end.php` - ゲーム終了とポイント払い出し
- `api/v1/play_history.php` - プレイ履歴取得（ページネーション対応）

#### ヘルパー関数
- `api/v1/helpers/user_helper.php`
  - `getOrCreateUser()` - ユーザー取得/自動作成
  - `getUserBalance()` - 残高取得
  - `consumePoints()` - ポイント消費（トランザクション）
  - `payoutPoints()` - ポイント払い出し
- `api/v1/helpers/frame_security.php`
  - `setFrameSecurityHeaders()` - X-Frame-Options動的設定
  - `extractOriginFromReferer()` - Origin抽出
  - `getApiKeyIdFromMachine()` - マシン番号からAPIキーID取得

#### 管理画面
- `data/xxxadmin/partner_domains.php` - パートナードメイン管理バックエンド
- `_html/ja/admin/partner_domains.html` - 管理画面フロントエンド

#### セキュリティ
- `data/play_v2/frame_security.php` - プレイページ用セキュリティヘッダー

#### データベース
- `sdk_extension_schema.sql` - マイグレーションSQL
- `data/setup_sdk_extension.php` - Webベースセットアップスクリプト

#### レポート
- `SDK_EXTENSION_IMPLEMENTATION_COMPLETE.md` - 実装完了レポート

---

### 3. Railwayデプロイ完了（2025-11-19）

**デプロイURL**: https://mgg-webservice-production.up.railway.app

#### SDK v1.1.0 - ✅ デプロイ済み
```
https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js
```
- バージョン確認: `SDK_VERSION = '1.1.0-beta'`
- 更新日: 2025-11-18

#### 新規API - ✅ デプロイ済み
- `POST /api/v1/game_end.php` - 認証必要（正常動作）
  - レスポンス: `{"error":"UNAUTHORIZED","message":"Authorization header required"}`
- `GET /api/v1/play_history.php` - 認証必要（正常動作）
  - レスポンス: `{"error":"UNAUTHORIZED","message":"Authorization header required"}`

#### 管理画面 - ✅ デプロイ済み
```
https://mgg-webservice-production.up.railway.app/data/xxxadmin/partner_domains.php
```
- .htpasswd保護（401 Unauthorized）

#### セットアップスクリプト - ✅ デプロイ済み
```
https://mgg-webservice-production.up.railway.app/data/setup_sdk_extension.php?key=setup_sdk_2025
```

---

### 4. データベーススキーマ設計完了

必要なテーブル（4つ）とカラム追加（1つ）:

#### 1. sdk_users - パートナーユーザー管理
```sql
CREATE TABLE sdk_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    api_key_id INT NOT NULL,
    partner_user_id VARCHAR(255) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    display_name VARCHAR(255) DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_partner_user (api_key_id, partner_user_id),
    INDEX idx_api_key (api_key_id),
    INDEX idx_email (email),
    INDEX idx_last_login (last_login_at),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 2. user_balances - ポイント残高管理
```sql
CREATE TABLE user_balances (
    user_id INT PRIMARY KEY,
    balance INT NOT NULL DEFAULT 0,
    total_deposited INT NOT NULL DEFAULT 0,
    total_consumed INT NOT NULL DEFAULT 0,
    total_won INT NOT NULL DEFAULT 0,
    total_withdrawn INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CHECK (balance >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 3. point_transactions - ポイント取引履歴
```sql
CREATE TABLE point_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    transaction_id VARCHAR(100) UNIQUE NOT NULL,
    type ENUM('deposit', 'consume', 'payout', 'refund', 'adjust') NOT NULL,
    amount INT NOT NULL,
    balance_before INT NOT NULL,
    balance_after INT NOT NULL,
    game_session_id VARCHAR(255) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_type (type),
    INDEX idx_session (game_session_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 4. game_sessions - ゲームセッション履歴
```sql
CREATE TABLE game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    api_key_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    machine_no INT NOT NULL,
    model_cd VARCHAR(50) NOT NULL,
    model_name VARCHAR(255) DEFAULT NULL,
    points_consumed INT DEFAULT 0,
    points_won INT DEFAULT 0,
    play_duration INT DEFAULT 0,
    result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout', 'cancelled') DEFAULT 'playing',
    status ENUM('playing', 'completed', 'error', 'cancelled') DEFAULT 'playing',
    error_message TEXT DEFAULT NULL,
    metadata JSON DEFAULT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_session (session_id),
    INDEX idx_api_key (api_key_id),
    INDEX idx_user (user_id),
    INDEX idx_machine (machine_no),
    INDEX idx_model (model_cd),
    INDEX idx_status (status),
    INDEX idx_started (started_at),
    INDEX idx_result (result)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### 5. api_keys カラム追加
```sql
ALTER TABLE api_keys
ADD COLUMN allowed_domains JSON DEFAULT NULL
COMMENT 'List of domains allowed to embed iframes (for X-Frame-Options)';
```

---

## ⚠️ 未完了タスク

### 🔴 最優先: データベースマイグレーション実行

**問題**: Railway MySQL にテーブルが作成されていない

**現状**:
- セットアップスクリプト（`setup_sdk_extension.php`）がデプロイ済み
- スクリプト実行は成功するが、テーブルが作成されない
- 成功カウント: 0、スキップ: 0（SQL実行されていない）

**原因仮説**:
1. PDO::exec() が機能していない
2. データベース権限の問題
3. トランザクション未コミット
4. コードキャッシュ（古いバージョンが実行されている）

**対処法**（以下いずれか）:
1. **Railway ダッシュボードから直接SQL実行** ← 推奨
   - Railway Console → MySQL サービス → Data タブ
   - `sdk_extension_schema.sql` の内容を直接実行

2. **ローカルからRailway MySQLに直接接続**
   ```bash
   mysql -h 136.116.70.86 -u net8tech001 -p net8_dev < sdk_extension_schema.sql
   ```

3. **セットアップスクリプトのデバッグ**
   - エラー出力の詳細化
   - PDO::exec() の戻り値確認
   - SQL文の個別実行確認

**必要な作業**:
- [ ] データベースマイグレーション実行
- [ ] テーブル作成確認（4テーブル + 1カラム）
- [ ] 初期データ投入（テストAPIキー、テストユーザー）
- [ ] SDK実動作確認（デモページでゲーム起動）

---

### その他の残タスク（Vercel側 - 別Claudeが担当予定）

以下はパートナーサイト（Vercel）側で実装が必要:

1. **A-2: パートナーAPI連携** - Net8 APIとの統合
2. **A-3: UI上の残高表示** - リアルタイム残高更新
3. **A-5: ローディング表示** - ゲーム起動時のUI
4. **B-4: タイムアウト処理** - 長時間プレイの対応
5. **F-4: 統計情報API** - `/api/v1/stats.php`（ダッシュボード用）
6. **パートナー認証連携実装** - 2段階認証

---

## 技術スタック

### フロントエンド (SDK)
- **言語**: JavaScript (ES6+)
- **バージョン**: 1.1.0-beta
- **配信**: CDN経由（Railway）
- **主要機能**: WebRTC (PeerJS), JWT認証, イベント駆動

### バックエンド
- **言語**: PHP 7.2+
- **フレームワーク**: カスタム（SmartRams）
- **データベース**: MySQL 8.0 (Railway)
- **サーバー**: Apache 2.4.38 (Debian)
- **認証**: JWT Bearer Token

### デプロイ
- **プラットフォーム**: Railway (Docker)
- **リージョン**: asia-southeast1
- **ビルド**: Dockerfile
- **自動デプロイ**: GitHub main ブランチ連携

### セキュリティ
- **X-Frame-Options**: 動的設定（allowed_domains）
- **CSP frame-ancestors**: ドメインベース制御
- **JWT認証**: API全体
- **.htpasswd**: 管理画面保護

---

## ドキュメント

### SDK使用マニュアル
- **最新版**: `sdk/README_v1.1.0.md`
- **旧版**: `sdk/README.md` (v1.0.1)
- **バージョン管理**: `sdk/SDK_VERSIONS.md`

### 実装レポート
- `SDK_EXTENSION_IMPLEMENTATION_COMPLETE.md` - 完全な実装記録

### デモページ
- `sdk/demo.html` - 基本デモ
- `sdk/mock-game.html` - モックゲーム

---

## 使用例

### 基本的な使い方（v1.1.0）

```html
<!DOCTYPE html>
<html lang="ja">
<head>
    <title>NET8 Game</title>
    <script src="https://mgg-webservice-production.up.railway.app/sdk/net8-sdk-beta.js"></script>
</head>
<body>
    <div id="balance">残高: <span id="balance-value">10000</span>P</div>
    <div id="game-container" style="width:800px; height:600px;"></div>

    <script>
        (async function() {
            // 1. SDK初期化
            await Net8.init('pk_live_xxxxx');

            // 2. ゲーム作成（ユーザーID付き）
            const game = Net8.createGame({
                model: 'HOKUTO4GO',
                userId: 'partner_user_12345',  // ← NEW in v1.1.0!
                container: '#game-container'
            });

            // 3. イベントリスナー
            game.on('started', (data) => {
                console.log('ゲーム開始');
                console.log('消費ポイント:', data.pointsConsumed);
            });

            game.on('end', (result) => {
                console.log('ゲーム終了');
                console.log('獲得ポイント:', result.pointsWon);
                console.log('純利益:', result.netProfit);
                console.log('新残高:', result.newBalance);

                // UI更新
                document.getElementById('balance-value').textContent = result.newBalance;
            });

            game.on('error', (error) => {
                console.error('エラー:', error.message);
            });

            // 4. ゲーム開始
            await game.start();
        })();
    </script>
</body>
</html>
```

---

## 最終ゴール

**目的**: 別のClaude（何も知らない状態）が`README_v1.1.0.md`を読んで、Vercel側で完璧にゲームを組み込めるように、Railway側のSDKサービスを完全に動作させること。

**現状**:
- ✅ SDK v1.1.0 実装完了
- ✅ 全APIエンドポイント実装完了
- ✅ Railwayデプロイ完了
- ✅ ドキュメント完備
- ❌ データベースマイグレーション未完了 ← **これが完了すれば全て動作可能**

**次のステップ**:
1. データベースマイグレーション実行
2. テストAPIキー作成
3. テストユーザー作成（初期ポイント10,000付与）
4. SDK実動作確認
5. 別Claudeにドキュメント引き継ぎ

---

## Git履歴

```bash
# コミット履歴
88050ce - feat: SDK v1.1.0 - userId support, point management, and partner domain control
507acfa - feat: Add SDK extension database setup script
0caba41 - fix: Move setup_sdk_extension.php to data directory
ddd2354 - fix: Rewrite setup script to execute SQL statements individually
```

---

**作成者**: Claude Code AI
**プロジェクト**: NET8 SDK v1.1.0
**リポジトリ**: https://github.com/mgg00123mg-prog/mgg001
**本番環境**: https://mgg-webservice-production.up.railway.app
