# 中国向け NET8 API 設計仕様書

**作成日**: 2025-12-31
**バージョン**: 1.0.0-draft
**対象環境**: Railway Production (https://mgg-webservice-production.up.railway.app)

---

## 📋 目次

1. [要件整理](#1-要件整理)
2. [現状分析](#2-現状分析)
3. [設計方針](#3-設計方針)
4. [データベース設計](#4-データベース設計)
5. [API設計](#5-api設計)
6. [実装タスク](#6-実装タスク)
7. [テスト計画](#7-テスト計画)
8. [デプロイ手順](#8-デプロイ手順)

---

## 1. 要件整理

### 1.1 ステークホルダー間の合意事項

#### KY側（中国チーム）の想定
- MGGO側でゲーム処理とプレイヤーインタラクション全処理
- KY側は iFrame 埋め込みのみ（iFrame 内外の相互連携なし）
- プレイヤーのゲーム入場時に API 経由で **通貨情報** を MGGO 側へ渡すことが可能

#### MGGO側（開発チーム）の対応方針
- iFrame の課題を **2025年内に対応完了予定**
- KY側は **API によるポイント処理のみ** 対応すればOK
- **新しいAPIドキュメント** を後日提供予定
- iOS端末でのiFrame問題は **修正済み**
- 通貨情報をAPI経由で受け取る方式なら **対応可能**

### 1.2 技術要件

#### 必須対応項目
1. **通貨情報の受け渡し機能**
   - ゲーム開始時に通貨コード（JPY, CNY等）を受け取る
   - 通貨情報をセッションおよびゲームデータに保存
   - 通貨に応じた表示処理

2. **ポイント処理API（通貨対応）**
   - ゲーム開始時のポイント減算（通貨単位考慮）
   - ゲーム終了時のポイント加算（通貨単位考慮）
   - ポイント残高照会（通貨単位表示）
   - 通貨換算処理（将来的に必要な場合）

3. **iFrame対応**
   - クロスオリジン対応
   - セッション管理の確認
   - iOS端末での動作確認

#### オプション項目（将来対応）
- 通貨換算レート管理機能
- 複数通貨での同時プレイ対応
- 通貨別の統計・レポート機能

---

## 2. 現状分析

### 2.1 既存API実装状況

#### game_start.php (v1.0.0-beta)
**場所**: `/net8/02.ソースファイル/net8_html/api/v1/game_start.php`

**主要機能**:
- API認証（JWT または 直接APIキー）
- ユーザー管理（sdk_users, mst_member紐づけ）
- ポイント管理（user_balances, mst_member.point）
- balanceMode対応（add/set）
- consumeImmediately対応
- 多言語対応（ja/ko/en/zh）
- カメラ・台番号の自動割り当て
- ゲームセッション管理（game_sessions）

**現在のパラメータ**:
```json
{
  "modelId": "HOKUTO4GO",
  "userId": "china_user_123",
  "machineNo": 1,
  "initialPoints": 1000,
  "balanceMode": "add",
  "consumeImmediately": true,
  "lang": "zh"
}
```

**不足している項目**:
- ❌ 通貨情報（currency）パラメータ
- ❌ 通貨別の残高管理
- ❌ 通貨換算処理

#### game_end.php (v1.0.0)
**場所**: `/net8/02.ソースファイル/net8_html/api/v1/game_end.php`

**主要機能**:
- ゲームセッション終了処理
- ポイント加算処理
- プレイ履歴記録（his_play）
- 両建て残高管理（sdk_users.balance, mst_member.point）

**現在のパラメータ**:
```json
{
  "sessionId": "sess_abc123",
  "result": "win",
  "pointsWon": 500,
  "resultData": {}
}
```

**不足している項目**:
- ❌ 通貨情報の記録
- ❌ 通貨単位でのポイント計算

### 2.2 データベーススキーマ分析

#### 関連テーブル

**sdk_users** - パートナー側ユーザー管理
```sql
CREATE TABLE sdk_users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  api_key_id INT UNSIGNED,
  partner_user_id VARCHAR(255),
  member_no INT UNSIGNED,  -- mst_member.member_no へのリンク
  balance DECIMAL(15,2) DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE KEY unique_partner_user (api_key_id, partner_user_id)
)
```
**不足**: `currency VARCHAR(3)` カラムなし

**user_balances** - ユーザー残高管理
```sql
CREATE TABLE user_balances (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED,
  balance DECIMAL(15,2) DEFAULT 0,
  created_at DATETIME,
  updated_at DATETIME,
  UNIQUE KEY unique_user (user_id)
)
```
**不足**: `currency VARCHAR(3)` カラムなし

**game_sessions** - ゲームセッション管理
```sql
CREATE TABLE game_sessions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id VARCHAR(100) UNIQUE,
  user_id INT UNSIGNED,
  api_key_id INT UNSIGNED,
  member_no INT UNSIGNED,
  partner_user_id VARCHAR(255),
  machine_no INT,
  model_cd VARCHAR(50),
  points_consumed INT DEFAULT 0,
  status VARCHAR(20),
  started_at DATETIME,
  ended_at DATETIME
)
```
**不足**: `currency VARCHAR(3)` カラムなし

**his_play** - プレイ履歴
```sql
-- 既存テーブル（確認が必要）
```
**不足**: `currency VARCHAR(3)` カラム有無を確認

---

## 3. 設計方針

### 3.1 通貨対応の基本方針

#### ISO 4217 通貨コード使用
- **JPY** - 日本円
- **CNY** - 中国人民元
- **KRW** - 韓国ウォン
- **USD** - 米ドル（将来対応）

#### 通貨情報の保存場所
1. **sdk_users.currency** - ユーザーのデフォルト通貨
2. **game_sessions.currency** - セッション時の通貨
3. **his_play.currency** - プレイ履歴での通貨記録

#### 通貨換算の方針
**Phase 1（現在）**: 換算なし
- 各通貨でポイントを独立管理
- 1 JPY = 1ポイント
- 1 CNY = 1ポイント
- 1 KRW = 1ポイント

**Phase 2（将来）**: 換算機能追加
- `mst_currency_rates` テーブルで換算レート管理
- 基準通貨（JPY）へ換算して内部処理
- 表示時にユーザー通貨へ再換算

### 3.2 後方互換性の維持

#### 既存API動作保証
- `currency` パラメータが **省略された場合** は **JPY** をデフォルト
- 既存の韓国チームAPIは変更なしで動作継続
- 既存データベースレコードは `currency=JPY` として扱う

#### 段階的移行
1. データベーススキーマ追加（ALTER TABLE）
2. API パラメータ追加（オプション）
3. 通貨対応ロジック実装
4. 既存データへのデフォルト値設定

---

## 4. データベース設計

### 4.1 スキーマ変更

#### sdk_users テーブル
```sql
ALTER TABLE sdk_users
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;
```

#### user_balances テーブル
```sql
ALTER TABLE user_balances
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;
```

#### game_sessions テーブル
```sql
ALTER TABLE game_sessions
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'セッション時の通貨' AFTER points_consumed;
```

#### his_play テーブル（確認後）
```sql
-- his_playテーブルの構造を確認してから追加
ALTER TABLE his_play
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'プレイ時の通貨' AFTER [適切なカラム];
```

### 4.2 新規テーブル（将来対応）

#### mst_currency - 通貨マスタ
```sql
CREATE TABLE IF NOT EXISTS mst_currency (
  currency_code VARCHAR(3) PRIMARY KEY COMMENT '通貨コード (ISO 4217)',
  currency_name VARCHAR(100) NOT NULL COMMENT '通貨名',
  currency_symbol VARCHAR(10) COMMENT '通貨記号 (¥, $, 元)',
  decimal_places TINYINT DEFAULT 0 COMMENT '小数点以下桁数',
  is_active TINYINT DEFAULT 1 COMMENT '有効フラグ',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通貨マスタ';

-- 初期データ
INSERT INTO mst_currency (currency_code, currency_name, currency_symbol, decimal_places) VALUES
('JPY', '日本円', '¥', 0),
('CNY', '人民元', '元', 2),
('KRW', '韓国ウォン', '₩', 0),
('USD', '米ドル', '$', 2);
```

#### mst_currency_rates - 換算レートマスタ（将来対応）
```sql
CREATE TABLE IF NOT EXISTS mst_currency_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_currency VARCHAR(3) NOT NULL COMMENT '元通貨',
  to_currency VARCHAR(3) NOT NULL COMMENT '先通貨',
  rate DECIMAL(18,6) NOT NULL COMMENT '換算レート',
  effective_from DATETIME NOT NULL COMMENT '適用開始日時',
  effective_to DATETIME COMMENT '適用終了日時',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_rate (from_currency, to_currency, effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通貨換算レート';
```

---

## 5. API設計

### 5.1 game_start.php 拡張

#### 新規パラメータ
```json
{
  "modelId": "HOKUTO4GO",
  "userId": "china_user_123",
  "initialPoints": 1000,
  "currency": "CNY",  // ← 新規追加（省略時は "JPY"）
  "balanceMode": "add",
  "consumeImmediately": true,
  "lang": "zh"
}
```

#### レスポンス拡張
```json
{
  "success": true,
  "sessionId": "sess_abc123",
  "balance": {
    "amount": 900,
    "currency": "CNY",  // ← 通貨情報を返す
    "formatted": "900元"  // ← フォーマット済み表示
  },
  "gameUrl": "https://...",
  "machineNo": 1,
  "modelName": "北斗の拳",
  "pointsConsumed": 100
}
```

#### 処理フロー変更点
1. `currency` パラメータを受け取る（デフォルト: JPY）
2. ISO 4217 形式の検証（JPY, CNY, KRW, USD のみ許可）
3. `sdk_users.currency` に保存
4. `user_balances.currency` に保存
5. `game_sessions.currency` に保存
6. レスポンスに通貨情報を含める

### 5.2 game_end.php 拡張

#### パラメータ変更なし
```json
{
  "sessionId": "sess_abc123",
  "result": "win",
  "pointsWon": 500
}
```
※セッションから通貨情報を取得するため、リクエストパラメータ追加不要

#### レスポンス拡張
```json
{
  "success": true,
  "finalBalance": {
    "amount": 1400,
    "currency": "CNY",
    "formatted": "1400元"
  },
  "pointsAdded": 500,
  "totalWinnings": 500
}
```

#### 処理フロー変更点
1. `game_sessions.currency` から通貨情報を取得
2. ポイント加算時に通貨を考慮
3. `his_play.currency` に記録
4. レスポンスに通貨情報を含める

### 5.3 新規API: balance.php（残高照会）

**エンドポイント**: `GET /api/v1/balance.php`

#### リクエスト
```http
GET /api/v1/balance.php?userId=china_user_123
Authorization: Bearer {API_KEY}
```

#### レスポンス
```json
{
  "success": true,
  "userId": "china_user_123",
  "balance": {
    "amount": 1400,
    "currency": "CNY",
    "formatted": "1400元"
  },
  "lastUpdated": "2025-12-31T12:00:00Z"
}
```

---

## 6. 実装タスク

### Phase 1: データベース準備（優先度: 高）
- [ ] his_play テーブル構造確認
- [ ] ALTER TABLE スクリプト作成
- [ ] ローカルテスト環境で実行
- [ ] Railway本番環境で実行
- [ ] mst_currency テーブル作成
- [ ] 初期データ投入

### Phase 2: API実装（優先度: 高）
- [ ] game_start.php に currency パラメータ追加
- [ ] game_start.php で通貨バリデーション実装
- [ ] game_start.php でDB保存処理追加
- [ ] game_end.php で通貨情報取得処理追加
- [ ] game_end.php でレスポンス拡張
- [ ] balance.php 新規作成
- [ ] 通貨フォーマット関数実装

### Phase 3: テスト（優先度: 高）
- [ ] 単体テスト（各API）
- [ ] 統合テスト（ゲーム開始→終了フロー）
- [ ] 後方互換性テスト（currency省略時）
- [ ] 多通貨テスト（JPY, CNY, KRW）
- [ ] iOS iFrame動作確認

### Phase 4: ドキュメント（優先度: 中）
- [ ] API仕様書作成（日本語）
- [ ] API仕様書作成（中国語）
- [ ] サンプルコード作成（JavaScript）
- [ ] サンプルコード作成（PHP）
- [ ] エラーコード一覧作成

### Phase 5: デプロイ（優先度: 高）
- [ ] Railway環境でテスト
- [ ] KYチームへAPI仕様共有
- [ ] フィードバック反映
- [ ] 本番デプロイ

---

## 7. テスト計画

### 7.1 単体テスト

#### game_start.php
```bash
# JPY（デフォルト）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer {API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "HOKUTO4GO",
    "userId": "test_user_jpy",
    "initialPoints": 1000
  }'

# CNY（明示指定）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer {API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "HOKUTO4GO",
    "userId": "test_user_cny",
    "initialPoints": 1000,
    "currency": "CNY"
  }'

# 無効な通貨コード（エラー確認）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer {API_KEY}" \
  -H "Content-Type: application/json" \
  -d '{
    "modelId": "HOKUTO4GO",
    "userId": "test_user",
    "initialPoints": 1000,
    "currency": "XXX"
  }'
```

### 7.2 統合テスト

#### シナリオ1: 中国ユーザーがCNYでプレイ
1. game_start.php で currency=CNY, initialPoints=1000
2. セッションID取得
3. game_end.php で result=win, pointsWon=500
4. 残高確認: 1500 CNY

#### シナリオ2: 日本ユーザーがJPYでプレイ（後方互換性）
1. game_start.php で currency省略, initialPoints=1000
2. セッションID取得
3. game_end.php で result=lose, pointsWon=0
4. 残高確認: 900 JPY（100消費）

---

## 8. デプロイ手順

### 8.1 データベースマイグレーション
```bash
# 1. ローカルで実行（テスト）
php /tmp/migrate_currency_support.php

# 2. Railway本番で実行
https://mgg-webservice-production.up.railway.app/data/xxxadmin/migrate_currency_support.php
```

### 8.2 APIコードデプロイ
```bash
# 1. コミット
git add net8/02.ソースファイル/net8_html/api/v1/
git commit -m "feat: Add multi-currency support to game APIs"

# 2. プッシュ（自動デプロイ）
git push origin main
```

### 8.3 動作確認
1. Railway ログ確認
2. テストAPI実行
3. データベース確認

---

## 9. 修正が必要な既存ファイル

### 9.1 APIファイル
- `/net8/02.ソースファイル/net8_html/api/v1/game_start.php` - 通貨パラメータ追加
- `/net8/02.ソースファイル/net8_html/api/v1/game_end.php` - 通貨対応レスポンス
- `/net8/02.ソースファイル/net8_html/api/v1/helpers/user_helper.php` - 残高関数修正

### 9.2 新規作成ファイル
- `/net8/02.ソースファイル/net8_html/api/v1/balance.php` - 残高照会API
- `/net8/02.ソースファイル/net8_html/api/v1/helpers/currency_helper.php` - 通貨関数
- `/net8/02.ソースファイル/net8_html/data/xxxadmin/migrate_currency_support.php` - マイグレーションスクリプト

---

## 10. リスク管理

### 10.1 技術リスク
| リスク | 影響度 | 対策 |
|--------|--------|------|
| 既存API動作への影響 | 高 | 後方互換性テスト徹底 |
| データベース整合性 | 高 | マイグレーション前バックアップ |
| 通貨換算エラー | 中 | Phase 1では換算なし |
| iOS iFrame問題 | 中 | 事前動作確認 |

### 10.2 スケジュールリスク
| マイルストーン | 期限 | リスク |
|----------------|------|--------|
| データベース準備 | 2025-01-03 | 低 |
| API実装 | 2025-01-07 | 中 |
| テスト完了 | 2025-01-10 | 中 |
| 本番デプロイ | 2025-01-15 | 低 |

---

## 11. 次のステップ

1. ✅ 設計仕様書レビュー（本ドキュメント）
2. ⏳ his_playテーブル構造確認
3. ⏳ マイグレーションスクリプト作成
4. ⏳ game_start.php 修正実装
5. ⏳ テスト実行
6. ⏳ KYチームへAPI仕様共有

---

**承認者**: _________________
**承認日**: _________________
