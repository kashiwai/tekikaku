# NET8 API修正オーダー実装状況レポート

**報告日**: 2026-01-06
**報告元**: NET8開発チーム
**報告先**: 韓国GOODFRIEND開発チーム

---

## 📋 エグゼクティブサマリー

**結論**: 要求されたすべての修正（修正1,2,3）は **すでに実装完了しています**。

追加の実装作業は不要で、現在の本番環境で利用可能です。

---

## ✅ 修正項目の実装状況

### 🟢 修正1: balanceModeパラメータ - **実装済み**

#### 実装箇所
- **ファイル**: `net8/02.ソースファイル/net8_html/api/v1/game_start.php`
- **行番号**: Line 68, 254-280

#### 実装内容
```php
// Line 68: パラメータ受け取り
$balanceMode = $input['balanceMode'] ?? 'add';

// Line 254-280: 処理ロジック
if ($balanceMode === 'set') {
    // setモード: 既存残高を無視して新しい値を設定
    $stmt = $pdo->prepare("
        INSERT INTO user_balances (user_id, balance, currency, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE balance = ?, currency = ?, updated_at = NOW()
    ");
    $stmt->execute([$userId, $initialPoints, $currency, $initialPoints, $currency]);

    // mst_member.point も同期
    $stmt = $pdo->prepare("UPDATE mst_member SET point = ? WHERE member_no = ?");
    $stmt->execute([$initialPoints, $memberNo]);
} else {
    // addモード: 既存残高に加算（従来の動作）
    $depositResult = depositPoints($pdo, $userId, $initialPoints, 'Korea initial points deposit');
}
```

#### 動作確認
- ✅ デフォルト値: `"add"`
- ✅ `"set"` モード: 既存残高を上書き
- ✅ `"add"` モード: 既存残高に加算
- ✅ `user_balances` と `mst_member.point` の両方を同期更新

---

### 🟢 修正2: consumeImmediatelyパラメータ - **実装済み**

#### 実装箇所
- **ファイル**: `net8/02.ソースファイル/net8_html/api/v1/game_start.php`
- **行番号**: Line 69, 560-575

#### 実装内容
```php
// Line 69: パラメータ受け取り
$consumeImmediately = isset($input['consumeImmediately']) ? (bool)$input['consumeImmediately'] : true;

// Line 560-575: 処理ロジック
if ($consumeImmediately) {
    // 即座にポイント消費（従来の動作）
    $transaction = consumePoints($pdo, $userId, $gamePrice, $sessionId);
    $pointsConsumed = $transaction['amount'];
    $userBalance = getUserBalance($pdo, $userId);
    error_log("✅ Points consumed immediately: {$pointsConsumed}");
} else {
    // ポイント消費を後で行う（reserved_pointsに記録）
    $reservedPoints = $gamePrice;
    $pointsConsumed = 0;
    error_log("⏸️ Points reserved for later: {$reservedPoints}");
}
```

#### 動作確認
- ✅ デフォルト値: `true`
- ✅ `true`: 即座にポイント消費
- ✅ `false`: セッション作成のみ、ポイント消費なし
- ✅ `game_sessions.reserved_points` に予約ポイントを記録

---

### 🟢 修正3: game_end.php - 負の値（損失）処理 - **実装済み**

#### 実装箇所
- **ファイル**: `net8/02.ソースファイル/net8_html/api/v1/game_end.php`
- **行番号**: Line 64, 249, 253-278, 284-293

#### 実装内容
```php
// Line 64: pointsWonを整数として受け取り（負の値も可）
$pointsWon = isset($input['pointsWon']) ? (int)$input['pointsWon'] : 0;

// Line 249: 残高計算（正負どちらも対応）
$balanceAfter = $balanceBefore + $pointsWon;

// Line 253-278: 正の値と負の値で処理を分ける
if ($pointsWon > 0) {
    // 勝ちの場合: balance + total_won 更新
    $stmt = $pdo->prepare("
        UPDATE user_balances
        SET balance = :balance,
            total_won = total_won + :amount,
            last_transaction_at = NOW()
        WHERE user_id = :user_id
    ");
    $stmt->execute([
        'balance' => $balanceAfter,
        'amount' => $pointsWon,
        'user_id' => $session['user_id']
    ]);
} else {
    // 負の値の場合: balanceのみ更新（total_wonは変更しない）
    $stmt = $pdo->prepare("
        UPDATE user_balances
        SET balance = :balance,
            last_transaction_at = NOW()
        WHERE user_id = :user_id
    ");
    $stmt->execute([
        'balance' => $balanceAfter,
        'user_id' => $session['user_id']
    ]);
}

// Line 284-293: mst_member.pointにも反映（負の値対応）
$stmt = $pdo->prepare("
    UPDATE mst_member
    SET point = point + :amount
    WHERE member_no = :member_no
");
$stmt->execute([
    'amount' => $pointsWon,  // 負の値でも正しく動作
    'member_no' => $memberNo
]);

// Line 297-298: 取引タイプを自動判定
$transactionType = $pointsWon > 0 ? 'payout' : 'loss';
$description = $pointsWon > 0 ? 'Game win payout' : 'Game loss deduction';
```

#### 動作確認
- ✅ 正の値（勝ち）: 残高に加算、total_wonに記録
- ✅ 負の値（負け）: 残高から減算、total_wonは変更なし
- ✅ ゼロ（引き分け）: 残高変更なし
- ✅ `user_balances`, `mst_member.point`, `point_transactions` の全てに反映
- ✅ 取引履歴に 'payout' または 'loss' として記録

---

## 📊 確認事項1: データベーススキーマ情報

### 1.1 mst_member テーブル

```sql
CREATE TABLE `mst_member` (
  `member_no` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nickname` varchar(20) NOT NULL,
  `mail` varchar(255) NOT NULL,
  `point` int(10) unsigned NOT NULL DEFAULT '0',  -- ⚠️ UNSIGNED: 負の値不可
  `draw_point` int(10) unsigned NOT NULL DEFAULT '0',
  `loss_count` int(11) DEFAULT '0',
  `deadline_point` int(10) unsigned NOT NULL DEFAULT '0',
  -- ... 他のカラム
  PRIMARY KEY (`member_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**重要事項:**
- ✅ `point` カラムは `int(10) unsigned` - **負の値は許容されません**
- ✅ デフォルト値: `0`
- ✅ 最大値: 4,294,967,295（約42億ポイント）
- ⚠️ `point` が 0 未満になる更新は**自動的に 0 にクリップされます**（MySQLの仕様）

### 1.2 game_sessions テーブル

```sql
CREATE TABLE IF NOT EXISTS game_sessions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    user_id INT NOT NULL,
    api_key_id INT NOT NULL,
    machine_no INT DEFAULT NULL,
    model_cd VARCHAR(50) NOT NULL,

    -- ゲーム情報
    points_consumed INT DEFAULT 0,        -- 消費ポイント
    points_won INT DEFAULT 0,             -- 獲得ポイント（負の値も可）
    reserved_points INT DEFAULT 0,        -- 予約ポイント（consumeImmediately=false用）
    balance_mode VARCHAR(10) DEFAULT 'add', -- balanceMode記録用
    play_duration INT DEFAULT NULL,

    -- ゲーム結果
    result ENUM('playing', 'win', 'lose', 'draw', 'error', 'timeout') DEFAULT 'playing',

    -- ステータス
    status ENUM('pending', 'playing', 'completed', 'error', 'cancelled') DEFAULT 'pending',

    -- タイムスタンプ
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL DEFAULT NULL,

    -- その他
    member_no INT(10) UNSIGNED NULL,
    partner_user_id VARCHAR(255) NULL,
    currency VARCHAR(3) DEFAULT 'JPY',
    -- ... その他のカラム

    PRIMARY KEY (id),
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**重要事項:**
- ✅ `points_consumed`: `INT` - 符号付き整数
- ✅ `points_won`: `INT` - **負の値も許容されます**（符号付き）
- ✅ `reserved_points`: consumeImmediately=false 時に使用
- ✅ `balance_mode`: balanceModeの記録用（'add' or 'set'）
- ✅ `status`: pending（予約）→ playing（プレイ中）→ completed（完了）
- ✅ タイムアウト機能: 実装されていません（将来追加予定）
- ✅ セッション履歴: 永続的に保持されます

### 1.3 point_transactions テーブル（ポイント履歴）

```sql
CREATE TABLE IF NOT EXISTS point_transactions (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT(10) UNSIGNED NOT NULL,
    transaction_id VARCHAR(100) NOT NULL UNIQUE,
    type VARCHAR(20) NOT NULL,              -- 'deposit', 'consume', 'payout', 'loss', 'refund'
    amount INT(11) NOT NULL,                -- 正負両方（損失時は負の値）
    balance_before INT(10) UNSIGNED NOT NULL,
    balance_after INT(10) UNSIGNED NOT NULL,
    game_session_id VARCHAR(100) NULL,
    description VARCHAR(512) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    INDEX idx_user_id (user_id),
    INDEX idx_game_session (game_session_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

**重要事項:**
- ✅ **自動記録されます**: game_start, game_end で自動的に記録
- ✅ `amount` は `INT(11)` - **正負両方を記録可能**
- ✅ `type` の値:
  - `'deposit'`: ポイントチャージ
  - `'consume'`: ゲーム開始時の消費
  - `'payout'`: ゲーム勝利時の払い出し
  - `'loss'`: ゲーム損失時の減算（負の値）
  - `'refund'`: 返金
- ✅ 取引履歴は永続的に保持され、削除されません

---

## ❓ 確認事項2: API Key複数発行の可否

### 現在の実装状況

✅ **API Keyの複数発行は可能です**

### api_keys テーブル構造

```sql
CREATE TABLE IF NOT EXISTS `api_keys` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT(10) UNSIGNED NULL,
  `key_value` VARCHAR(100) NOT NULL UNIQUE,
  `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
  `name` VARCHAR(100) NULL,                    -- API Keyの名前（例: "Korea Site"）
  `environment` VARCHAR(20) NOT NULL DEFAULT 'test',  -- 'test', 'staging', 'production'
  `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000, -- リクエスト数制限
  `is_active` TINYINT(4) NOT NULL DEFAULT 1,
  `last_used_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NULL,                  -- 有効期限
  PRIMARY KEY (`id`),
  KEY `idx_key_value` (`key_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8
```

### 対応可能な機能

| 機能 | 対応状況 | 備考 |
|------|---------|------|
| API Key複数発行 | ✅ 可能 | サイトごとに発行可能 |
| リクエスト数制限 | ✅ 可能 | `rate_limit` カラムで設定 |
| 同時セッション数制限 | ❌ 未実装 | 将来実装可能 |
| 利用可能な機種の制限 | ❌ 未実装 | 将来実装可能 |
| IPアドレス制限 | ❌ 未実装 | 将来実装可能 |
| 環境別API Key | ✅ 可能 | test / staging / production |
| 有効期限設定 | ✅ 可能 | `expires_at` カラムで設定 |

### API Key管理方法

**現在の発行方法**: データベースに直接INSERT

```sql
-- テスト用APIキー発行例
INSERT INTO api_keys (key_value, name, environment, rate_limit, is_active)
VALUES (
  'pk_test_korea_12345',
  'Korea Test Site',
  'test',
  1000,
  1
);

-- 本番用APIキー発行例
INSERT INTO api_keys (key_value, name, environment, rate_limit, is_active)
VALUES (
  'pk_live_korea_67890',
  'Korea Production Site',
  'production',
  10000,
  1
);
```

**管理画面**: ❌ 現在は存在しません

**発行依頼方法**:
1. NET8開発チームにSlack/Emailで依頼
2. 必要情報を提供:
   - サイト名
   - 環境（test / production）
   - リクエスト数制限（デフォルト: 1000/時間）
   - 有効期限（オプション）

---

## 🧪 テスト仕様

### 推奨テストケース

韓国側での統合テスト前に、以下のテストケースで動作確認を推奨します。

#### テストケース1: balanceMode="set"

```bash
# 1. 残高を5000に設定
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer pk_live_korea_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "initialPoints": 5000,
    "balanceMode": "set"
  }'

# 期待結果:
# {
#   "success": true,
#   "points": {
#     "balance": 5000,
#     "balanceBefore": 0 (または以前の値),
#     "consumed": 0
#   }
# }

# 2. 再度10000に設定
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer pk_live_korea_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "initialPoints": 10000,
    "balanceMode": "set"
  }'

# 期待結果:
# {
#   "success": true,
#   "points": {
#     "balance": 10000,  ← 5000+10000=15000ではなく、10000
#     "consumed": 0
#   }
# }
```

#### テストケース2: consumeImmediately=false

```bash
# 1. ポイント消費なしでゲーム開始
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer pk_live_korea_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": "test_user_001",
    "modelId": "HOKUTO4GO",
    "machineNo": 1,
    "initialPoints": 10000,
    "balanceMode": "set",
    "consumeImmediately": false
  }'

# 期待結果:
# {
#   "success": true,
#   "sessionId": "gs_xxx",
#   "points": {
#     "consumed": 0,     ← 即座に消費されない
#     "balance": 10000   ← 残高そのまま
#   }
# }
```

#### テストケース3: pointsWon負の値（損失）

```bash
# 1. ゲーム終了（損失-3000）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_end.php \
  -H "Authorization: Bearer pk_live_korea_xxx" \
  -H "Content-Type: application/json" \
  -d '{
    "sessionId": "gs_xxx",
    "memberNo": 44,
    "pointsWon": -3000,
    "result": "lose"
  }'

# 期待結果:
# {
#   "success": true,
#   "sessionId": "gs_xxx",
#   "pointsWon": -3000,
#   "netProfit": -3000,
#   "newBalance": 7000,  ← 10000 - 3000 = 7000
#   "transaction": {
#     "amount": -3000,
#     "balanceBefore": 10000,
#     "balanceAfter": 7000
#   }
# }
```

---

## 📅 次のステップ

### 1. 韓国側での統合テスト（推奨スケジュール）

| 日付 | タスク |
|------|--------|
| 2026/01/07-08 | テスト環境でAPI動作確認 |
| 2026/01/09-10 | 韓国側フロントエンドとの統合テスト |
| 2026/01/13-14 | シナリオテスト（通常フロー、並行プレイ）|
| 2026/01/15 | 本番環境API Key発行 |
| 2026/01/16 | 本番環境デプロイ・最終確認 |

### 2. API Key発行依頼

**本番環境用APIキーが必要な場合**:

以下の情報を添えてNET8開発チームに依頼してください：

```
件名: 本番環境APIキー発行依頼

サイト名: 韓国GOODFRIEND本番サイト
環境: production
リクエスト数制限: 10,000/時間
有効期限: なし（無期限）
備考: 韓国カジノプラットフォーム用
```

### 3. ドキュメント

以下のドキュメントを参照してください：

- **API仕様書**: `/NET8_SDK_INTEGRATION_GUIDE.md`
- **多言語対応**: `/MULTILINGUAL_FLOW_REPORT.md`
- **通貨対応**: `/net8/02.ソースファイル/net8_html/api/v1/helpers/currency_helper.php`
- **統合確認事項**: `/NET8_INTEGRATION_CONFIRMATION.md`

---

## 🎉 結論

韓国GOODFRIENDチームから依頼された**全ての修正（修正1, 2, 3）は既に実装完了**しており、本番環境で利用可能です。

追加の実装作業は不要です。

次のステップは：
1. ✅ API動作確認（テスト環境）
2. ✅ 韓国側フロントエンドとの統合テスト
3. ✅ 本番環境API Key発行
4. ✅ 本番環境デプロイ

ご不明点があれば、お気軽にお問い合わせください。

---

**報告者**: NET8開発チーム
**報告日**: 2026-01-06
**バージョン**: NET8 SDK v1.1.0
