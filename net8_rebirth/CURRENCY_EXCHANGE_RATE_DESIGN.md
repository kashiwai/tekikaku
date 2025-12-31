# 為替レート取得機能 設計メモ

**作成日**: 2025-12-31
**対応**: Phase 2（将来対応）

---

## 📊 概要

**Phase 1**: 換算なし（1 CNY = 1ポイント、1 USD = 1ポイント）
**Phase 2**: Yahoo Finance APIで為替レート取得 ← 本ドキュメント

---

## 🌐 Yahoo Finance API 使用方法

### エンドポイント

```
https://query1.finance.yahoo.com/v8/finance/chart/{SYMBOL}
```

### 通貨ペアシンボル

| 通貨ペア | シンボル | 説明 |
|---------|---------|------|
| CNY/JPY | CNYJPY=X | 人民元 → 日本円 |
| USD/JPY | USDJPY=X | 米ドル → 日本円 |
| TWD/JPY | TWDJPY=X | 台湾ドル → 日本円 |

### PHP実装例

```php
<?php
/**
 * Yahoo Financeから為替レートを取得
 *
 * @param string $fromCurrency (CNY, USD, TWD)
 * @param string $toCurrency (JPY)
 * @return float|null レート
 */
function getExchangeRate($fromCurrency, $toCurrency = 'JPY') {
    // JPY同士は1.0
    if ($fromCurrency === $toCurrency) {
        return 1.0;
    }

    // Yahoo Finance シンボル
    $symbol = $fromCurrency . $toCurrency . '=X';

    // API URL
    $url = "https://query1.finance.yahoo.com/v8/finance/chart/{$symbol}";

    try {
        $response = file_get_contents($url);
        $data = json_decode($response, true);

        // レート取得
        $rate = $data['chart']['result'][0]['meta']['regularMarketPrice'] ?? null;

        if ($rate) {
            error_log("✅ Exchange rate {$fromCurrency}/{$toCurrency}: {$rate}");
            return (float)$rate;
        }

        error_log("⚠️ Exchange rate not found for {$fromCurrency}/{$toCurrency}");
        return null;

    } catch (Exception $e) {
        error_log("❌ Failed to fetch exchange rate: " . $e->getMessage());
        return null;
    }
}

// 使用例
$cnyToJpy = getExchangeRate('CNY', 'JPY');  // 例: 20.5 (1元 = 20.5円)
$usdToJpy = getExchangeRate('USD', 'JPY');  // 例: 145.2 (1ドル = 145.2円)
$twdToJpy = getExchangeRate('TWD', 'JPY');  // 例: 4.8 (1台湾ドル = 4.8円)
?>
```

---

## 🗃️ データベース設計（Phase 2）

### mst_currency_rates テーブル

```sql
CREATE TABLE IF NOT EXISTS mst_currency_rates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  from_currency VARCHAR(3) NOT NULL COMMENT '元通貨',
  to_currency VARCHAR(3) NOT NULL COMMENT '先通貨（基準: JPY）',
  rate DECIMAL(18,6) NOT NULL COMMENT '換算レート',
  source VARCHAR(50) DEFAULT 'Yahoo Finance' COMMENT 'レート提供元',
  effective_from DATETIME NOT NULL COMMENT '適用開始日時',
  effective_to DATETIME COMMENT '適用終了日時',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY unique_rate (from_currency, to_currency, effective_from),
  INDEX idx_effective (from_currency, to_currency, effective_from, effective_to)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='通貨換算レート';
```

### レート更新スクリプト

```php
<?php
/**
 * 為替レート更新 Cron Job
 *
 * 実行頻度: 1時間ごと
 * URL: /data/xxxadmin/update_exchange_rates.php
 */

$currencies = ['CNY', 'USD', 'TWD'];
$toCurrency = 'JPY';

foreach ($currencies as $fromCurrency) {
    $rate = getExchangeRate($fromCurrency, $toCurrency);

    if ($rate) {
        // 既存レートを無効化
        $pdo->exec("
            UPDATE mst_currency_rates
            SET effective_to = NOW()
            WHERE from_currency = '{$fromCurrency}'
            AND to_currency = '{$toCurrency}'
            AND effective_to IS NULL
        ");

        // 新しいレートを挿入
        $stmt = $pdo->prepare("
            INSERT INTO mst_currency_rates (from_currency, to_currency, rate, source, effective_from)
            VALUES (:from, :to, :rate, 'Yahoo Finance', NOW())
        ");

        $stmt->execute([
            'from' => $fromCurrency,
            'to' => $toCurrency,
            'rate' => $rate
        ]);

        echo "✅ Updated {$fromCurrency}/{$toCurrency}: {$rate}\n";
    }
}
?>
```

---

## 🔄 ポイント換算ロジック（Phase 2）

### 基本方針

**基準通貨**: JPY（日本円）

**換算方法**:
1. ユーザーの通貨でポイントを受け取る
2. JPYに換算してDBに保存
3. 表示時にユーザーの通貨に再換算

### 実装例

```php
<?php
/**
 * ユーザー通貨からJPYに換算
 */
function convertToJPY($amount, $currency) {
    if ($currency === 'JPY') {
        return $amount;
    }

    // 最新レート取得
    $stmt = $pdo->prepare("
        SELECT rate
        FROM mst_currency_rates
        WHERE from_currency = :from
        AND to_currency = 'JPY'
        AND effective_from <= NOW()
        AND (effective_to IS NULL OR effective_to > NOW())
        ORDER BY effective_from DESC
        LIMIT 1
    ");

    $stmt->execute(['from' => $currency]);
    $rateData = $stmt->fetch();

    if (!$rateData) {
        // フォールバック: Yahoo Financeから直接取得
        $rate = getExchangeRate($currency, 'JPY');
        if (!$rate) {
            throw new Exception("Exchange rate not available for {$currency}");
        }
    } else {
        $rate = $rateData['rate'];
    }

    // 換算
    $jpy = $amount * $rate;

    error_log("💱 Convert {$amount} {$currency} → {$jpy} JPY (rate: {$rate})");

    return round($jpy, 2);
}

/**
 * JPYからユーザー通貨に換算
 */
function convertFromJPY($jpy, $currency) {
    if ($currency === 'JPY') {
        return $jpy;
    }

    // 最新レート取得
    $stmt = $pdo->prepare("
        SELECT rate
        FROM mst_currency_rates
        WHERE from_currency = :from
        AND to_currency = 'JPY'
        AND effective_from <= NOW()
        AND (effective_to IS NULL OR effective_to > NOW())
        ORDER BY effective_from DESC
        LIMIT 1
    ");

    $stmt->execute(['from' => $currency]);
    $rateData = $stmt->fetch();

    if (!$rateData) {
        $rate = getExchangeRate($currency, 'JPY');
        if (!$rate) {
            throw new Exception("Exchange rate not available for {$currency}");
        }
    } else {
        $rate = $rateData['rate'];
    }

    // 逆換算
    $amount = $jpy / $rate;

    error_log("💱 Convert {$jpy} JPY → {$amount} {$currency} (rate: {$rate})");

    return round($amount, 2);
}

// 使用例
$cny = 100;  // 100元
$jpy = convertToJPY($cny, 'CNY');  // → 約2,050円（レート20.5の場合）

$jpy = 10000;  // 10,000円
$usd = convertFromJPY($jpy, 'USD');  // → 約68.87ドル（レート145.2の場合）
?>
```

---

## 📊 game_start.php での使用（Phase 2）

```php
<?php
// リクエストから通貨とポイントを取得
$currency = $input['currency'] ?? 'JPY';
$initialPoints = $input['initialPoints'] ?? 0;

// ユーザー通貨からJPYに換算して保存
$pointsInJPY = convertToJPY($initialPoints, $currency);

// user_balances に JPY で保存
$stmt = $pdo->prepare("
    INSERT INTO user_balances (user_id, balance, currency)
    VALUES (:user_id, :balance, 'JPY')
    ON DUPLICATE KEY UPDATE
    balance = balance + :balance
");

$stmt->execute([
    'user_id' => $userId,
    'balance' => $pointsInJPY  // JPYで保存
]);

// レスポンス時はユーザー通貨に再換算
$balanceInJPY = getUserBalance($pdo, $userId)['balance'];
$balanceInUserCurrency = convertFromJPY($balanceInJPY, $currency);

echo json_encode([
    'balance' => [
        'amount' => $balanceInUserCurrency,
        'currency' => $currency,
        'formatted' => formatCurrency($balanceInUserCurrency, $currency)
    ]
]);
?>
```

---

## ⚠️ 注意事項

### 1. レート変動リスク

- ゲーム中にレートが変動する可能性
- 解決策: セッション開始時のレートを game_sessions に保存

### 2. キャッシュ戦略

```php
// レートキャッシュ（1時間）
$cacheKey = "exchange_rate_{$fromCurrency}_{$toCurrency}";
$cachedRate = apcu_fetch($cacheKey);

if ($cachedRate === false) {
    $cachedRate = getExchangeRate($fromCurrency, $toCurrency);
    apcu_store($cacheKey, $cachedRate, 3600);  // 1時間キャッシュ
}

return $cachedRate;
```

### 3. Yahoo Finance API制限

- 無料版は制限あり
- 大量アクセス時はキャッシュ必須
- 代替案: Open Exchange Rates API, Alpha Vantage

---

## 🎯 実装優先度

### Phase 1（現在）- **換算なし**
- ✅ 各通貨でポイントを独立管理
- ✅ 1 CNY = 1ポイント
- ✅ 1 USD = 1ポイント
- ✅ 1 TWD = 1ポイント

### Phase 2（将来）- **換算あり**
- ⏳ Yahoo Finance API統合
- ⏳ mst_currency_rates テーブル
- ⏳ 自動レート更新 Cron
- ⏳ ポイント換算ロジック

---

## 📝 まとめ

**Phase 1**: 今すぐ実装可能（換算なし）
**Phase 2**: Yahoo Finance統合（2-3日の追加開発が必要）

現時点では **Phase 1で進めて、必要に応じてPhase 2を実装** する方針が推奨されます。
