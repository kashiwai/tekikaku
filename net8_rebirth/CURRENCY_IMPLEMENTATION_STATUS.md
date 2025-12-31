# 通貨対応 実装状況レポート

**作成日**: 2025-12-31
**対象**: 中国向けAPI統合

---

## 📊 現在の実装状況

### ✅ 完了している項目

#### 1. iframe埋め込み対応 - **100%完了**
- ✅ CORS設定完了（全オリジン許可）
- ✅ X-Frame-Options削除（iframe埋め込み許可）
- ✅ iOS Safari対応完了
- ✅ テストページ作成済み
  - URL: `https://mgg-webservice-production.up.railway.app/data/xxxadmin/test_iframe_embed.html`

#### 2. 設計ドキュメント - **100%完了**
- ✅ API設計仕様書（CHINA_API_SIMPLIFIED_SPEC.md）
- ✅ ユーザー連携仕様書（CHINA_USER_INTEGRATION_SPEC.md）
- ✅ データベーススキーマ設計
- ✅ 実装チェックリスト

#### 3. 開発ツール - **100%完了**
- ✅ データベース構造確認スクリプト（check_currency_schema.php）
- ✅ iframe埋め込みテストページ（test_iframe_embed.html）

---

## ⚠️ 未完了の項目

### 1. **通貨対応実装** - **0%完了**

#### データベース
- ❌ `sdk_users.currency` カラム未追加
- ❌ `user_balances.currency` カラム未追加
- ❌ `game_sessions.currency` カラム未追加
- ❌ `his_play.currency` カラム未追加
- ❌ `mst_currency` テーブル未作成

#### API実装
- ❌ `game_start.php` - currency パラメータ未対応
- ❌ `game_end.php` - currency レスポンス未対応
- ❌ `currency_helper.php` - 通貨フォーマット関数未作成
- ❌ `balance.php` - 残高照会API未作成

#### テスト
- ❌ 通貨別の動作テスト未実施

---

## 🌍 対応が必要な通貨

### 要件
```
円、元、ドル、台湾ドルの対応
```

### ISO 4217 通貨コード

| 通貨 | コード | 記号 | 小数点以下桁数 |
|------|--------|------|----------------|
| **日本円** | JPY | ¥ | 0 |
| **人民元** | CNY | 元 | 2 |
| **米ドル** | USD | $ | 2 |
| **台湾ドル** | TWD | NT$ | 2 |

**注**: 設計では KRW（韓国ウォン）も含めていましたが、要件に基づき上記4通貨に絞ります。

---

## 📋 実装計画

### Phase 1: データベース準備（1日）

#### 1.1 マイグレーションスクリプト作成

**ファイル**: `/data/xxxadmin/migrate_currency_support.php`

```php
<?php
// 通貨対応のためのデータベースマイグレーション

// 1. sdk_users に currency カラム追加
ALTER TABLE sdk_users
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;

// 2. user_balances に currency カラム追加
ALTER TABLE user_balances
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT '通貨コード (ISO 4217)' AFTER balance;

// 3. game_sessions に currency カラム追加
ALTER TABLE game_sessions
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'セッション時の通貨' AFTER points_consumed;

// 4. his_play に currency カラム追加（構造確認後）
ALTER TABLE his_play
ADD COLUMN currency VARCHAR(3) DEFAULT 'JPY' COMMENT 'プレイ時の通貨';

// 5. mst_currency テーブル作成
CREATE TABLE IF NOT EXISTS mst_currency (
  currency_code VARCHAR(3) PRIMARY KEY COMMENT '通貨コード (ISO 4217)',
  currency_name VARCHAR(100) NOT NULL COMMENT '通貨名',
  currency_symbol VARCHAR(10) COMMENT '通貨記号',
  decimal_places TINYINT DEFAULT 0 COMMENT '小数点以下桁数',
  is_active TINYINT DEFAULT 1 COMMENT '有効フラグ',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通貨マスタ';

// 6. 初期データ投入
INSERT INTO mst_currency (currency_code, currency_name, currency_symbol, decimal_places) VALUES
('JPY', '日本円', '¥', 0),
('CNY', '人民元', '元', 2),
('USD', '米ドル', '$', 2),
('TWD', '台湾ドル', 'NT$', 2);
?>
```

#### 1.2 実行手順

```bash
# 1. スキーマ確認
https://mgg-webservice-production.up.railway.app/data/xxxadmin/check_currency_schema.php

# 2. マイグレーション実行
https://mgg-webservice-production.up.railway.app/data/xxxadmin/migrate_currency_support.php

# 3. 結果確認
再度 check_currency_schema.php で確認
```

---

### Phase 2: API実装（1-2日）

#### 2.1 通貨ヘルパー関数作成

**ファイル**: `/api/v1/helpers/currency_helper.php`

```php
<?php
/**
 * 通貨ヘルパー関数
 */

// 対応通貨リスト
const SUPPORTED_CURRENCIES = ['JPY', 'CNY', 'USD', 'TWD'];

/**
 * 通貨コードが有効かチェック
 */
function isValidCurrency($currency) {
    return in_array($currency, SUPPORTED_CURRENCIES);
}

/**
 * 通貨フォーマット
 */
function formatCurrency($amount, $currency) {
    $symbols = [
        'JPY' => '¥',
        'CNY' => '元',
        'USD' => '$',
        'TWD' => 'NT$'
    ];

    $decimals = [
        'JPY' => 0,
        'CNY' => 2,
        'USD' => 2,
        'TWD' => 2
    ];

    $symbol = $symbols[$currency] ?? '';
    $decimal = $decimals[$currency] ?? 2;

    $formatted = number_format($amount, $decimal);

    // 通貨記号の位置
    if ($currency === 'JPY') {
        return $symbol . $formatted;  // ¥1,000
    } else if ($currency === 'CNY') {
        return $formatted . $symbol;  // 1,000.00元
    } else {
        return $symbol . $formatted;  // $1,000.00 or NT$1,000.00
    }
}

/**
 * 通貨情報取得
 */
function getCurrencyInfo($pdo, $currency) {
    $stmt = $pdo->prepare("
        SELECT currency_code, currency_name, currency_symbol, decimal_places
        FROM mst_currency
        WHERE currency_code = :currency
        AND is_active = 1
    ");

    $stmt->execute(['currency' => $currency]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
```

#### 2.2 game_start.php 修正

**追加箇所**:

```php
// リクエストボディから通貨を取得
$currency = $input['currency'] ?? 'JPY'; // デフォルト: JPY

// 通貨バリデーション
if (!isValidCurrency($currency)) {
    http_response_code(400);
    echo json_encode([
        'error' => 'INVALID_CURRENCY',
        'message' => "Unsupported currency: {$currency}. Supported: JPY, CNY, USD, TWD"
    ]);
    exit;
}

// ユーザー作成時に通貨を保存
$user = getOrCreateUser($pdo, $apiKeyId, $partnerUserId, [
    'currency' => $currency,  // ← 追加
    'email' => $userData['email'] ?? null,
    'username' => $userData['username'] ?? null
]);

// レスポンスに通貨情報を追加
echo json_encode([
    'success' => true,
    'sessionId' => $sessionId,
    'balance' => [
        'amount' => $userBalance['balance'],
        'currency' => $currency,
        'formatted' => formatCurrency($userBalance['balance'], $currency)
    ],
    'gameUrl' => $gameUrl,
    // ...
]);
```

#### 2.3 game_end.php 修正

**追加箇所**:

```php
// セッションから通貨情報を取得
$stmt = $pdo->prepare("
    SELECT currency
    FROM game_sessions
    WHERE session_id = :session_id
");
$stmt->execute(['session_id' => $sessionId]);
$session = $stmt->fetch(PDO::FETCH_ASSOC);
$currency = $session['currency'] ?? 'JPY';

// レスポンスに通貨情報を追加
echo json_encode([
    'success' => true,
    'finalBalance' => [
        'amount' => $finalBalance,
        'currency' => $currency,
        'formatted' => formatCurrency($finalBalance, $currency)
    ],
    'pointsAdded' => $pointsWon,
    // ...
]);
```

#### 2.4 user_helper.php 修正

**getOrCreateUser関数**:

```php
function getOrCreateUser($pdo, $apiKeyId, $partnerUserId, $userData = []) {
    // 既存ユーザー検索
    $stmt = $pdo->prepare("
        SELECT id, partner_user_id, member_no, currency
        FROM sdk_users
        WHERE api_key_id = :api_key_id
        AND partner_user_id = :partner_user_id
    ");

    $user = $stmt->fetch();

    if ($user) {
        return $user;
    }

    // 新規ユーザー作成
    $currency = $userData['currency'] ?? 'JPY';

    $stmt = $pdo->prepare("
        INSERT INTO sdk_users (partner_user_id, api_key_id, member_no, currency, ...)
        VALUES (:partner_user_id, :api_key_id, :member_no, :currency, ...)
    ");

    $stmt->execute([
        'partner_user_id' => $partnerUserId,
        'api_key_id' => $apiKeyId,
        'member_no' => $memberNo,
        'currency' => $currency,  // ← 追加
        // ...
    ]);

    // user_balances にも通貨を保存
    $stmt = $pdo->prepare("
        INSERT INTO user_balances (user_id, balance, currency)
        VALUES (:user_id, :balance, :currency)
    ");

    $stmt->execute([
        'user_id' => $userId,
        'balance' => $initialBalance,
        'currency' => $currency  // ← 追加
    ]);

    return [
        'id' => $userId,
        'partner_user_id' => $partnerUserId,
        'member_no' => $memberNo,
        'currency' => $currency,  // ← 追加
        // ...
    ];
}
```

---

### Phase 3: テスト（0.5日）

#### 3.1 通貨別テスト

```bash
# JPY（日本円）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer API_KEY" \
  -d '{"userId":"test_jpy","modelId":"HOKUTO4GO","initialPoints":1000,"currency":"JPY"}'

# CNY（人民元）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer API_KEY" \
  -d '{"userId":"test_cny","modelId":"HOKUTO4GO","initialPoints":1000,"currency":"CNY"}'

# USD（米ドル）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer API_KEY" \
  -d '{"userId":"test_usd","modelId":"HOKUTO4GO","initialPoints":1000,"currency":"USD"}'

# TWD（台湾ドル）
curl -X POST https://mgg-webservice-production.up.railway.app/api/v1/game_start.php \
  -H "Authorization: Bearer API_KEY" \
  -d '{"userId":"test_twd","modelId":"HOKUTO4GO","initialPoints":1000,"currency":"TWD"}'
```

#### 3.2 期待されるレスポンス

```json
// JPY
{
  "balance": {
    "amount": 900,
    "currency": "JPY",
    "formatted": "¥900"
  }
}

// CNY
{
  "balance": {
    "amount": 900.00,
    "currency": "CNY",
    "formatted": "900.00元"
  }
}

// USD
{
  "balance": {
    "amount": 900.00,
    "currency": "USD",
    "formatted": "$900.00"
  }
}

// TWD
{
  "balance": {
    "amount": 900.00,
    "currency": "TWD",
    "formatted": "NT$900.00"
  }
}
```

---

## 📊 実装進捗

| 項目 | 進捗 | 状態 |
|------|------|------|
| iframe対応 | 100% | ✅ 完了 |
| 設計ドキュメント | 100% | ✅ 完了 |
| データベース設計 | 100% | ✅ 完了 |
| **データベースマイグレーション** | **0%** | ❌ 未着手 |
| **API実装（currency対応）** | **0%** | ❌ 未着手 |
| **通貨フォーマット関数** | **0%** | ❌ 未着手 |
| **テスト** | **0%** | ❌ 未着手 |

---

## ⏱️ 想定スケジュール

| フェーズ | 作業内容 | 期間 | 担当 |
|---------|---------|------|------|
| Phase 1 | データベースマイグレーション | 0.5日 | 開発チーム |
| Phase 2 | API実装 | 1.5日 | 開発チーム |
| Phase 3 | テスト | 0.5日 | 開発チーム + QA |
| **合計** | | **2.5日** | |

---

## 🎯 次のアクション

### 今すぐ実行可能

1. **データベース構造確認**
   ```
   https://mgg-webservice-production.up.railway.app/data/xxxadmin/check_currency_schema.php
   ```

2. **iframe動作確認**
   ```
   https://mgg-webservice-production.up.railway.app/data/xxxadmin/test_iframe_embed.html
   ```

### 実装開始前に必要

1. **承認取得**
   - 通貨対応実装の開始承認
   - スケジュール確認
   - 台湾ドル（TWD）追加の最終確認

2. **マイグレーションスクリプト作成**
   - migrate_currency_support.php
   - ローカルテスト

3. **実装開始**
   - データベースマイグレーション
   - API実装
   - テスト

---

## 💡 重要な確認事項

### ✅ 確認済み

1. **iframe埋め込み**
   - ✅ 全ページでiframe埋め込み可能
   - ✅ CORS設定完了
   - ✅ iOS Safari対応完了

2. **ユーザー連携**
   - ✅ パートナーユーザーID連携の仕組み実装済み
   - ✅ 韓国チームと同じフローで対応可能

### ❌ 未確認・未実装

1. **通貨対応**
   - ❌ データベースに currency カラムなし
   - ❌ API で currency パラメータ未対応
   - ❌ 通貨フォーマット関数なし
   - ❌ 4通貨（JPY, CNY, USD, TWD）のテスト未実施

---

## 📌 まとめ

### 現状

**iframe埋め込み**: ✅ **完全対応済み**
- MGGOでゲーム処理
- KY側はiframe埋め込みのみ
- iframe内外での相互連携なし
- iOS Safari対応完了

**通貨対応**: ❌ **未実装**
- 設計は完了
- 実装は0%
- 必要通貨: JPY, CNY, USD, TWD
- 実装期間: 2.5日

### 推奨アクション

1. ✅ **今すぐ**: check_currency_schema.php でデータベース確認
2. ⏳ **実装開始**: データベースマイグレーション
3. ⏳ **API実装**: currency パラメータ対応
4. ⏳ **テスト**: 4通貨での動作確認
5. ✅ **デプロイ**: Railway本番環境へ

---

**次のステップ**: データベース構造確認スクリプトを実行して、現状を把握してください。
