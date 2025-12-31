<?php
/**
 * Currency Helper Functions
 *
 * 通貨関連のユーティリティ関数
 * 対応通貨: JPY, CNY, USD, TWD
 */

/**
 * 対応通貨リスト
 */
const SUPPORTED_CURRENCIES = ['JPY', 'CNY', 'USD', 'TWD'];

/**
 * デフォルト通貨
 */
const DEFAULT_CURRENCY = 'JPY';

/**
 * 通貨情報（ISO 4217準拠）
 */
const CURRENCY_INFO = [
    'JPY' => [
        'name' => '日本円',
        'symbol' => '¥',
        'decimal_places' => 0
    ],
    'CNY' => [
        'name' => '人民元',
        'symbol' => '元',
        'decimal_places' => 2
    ],
    'USD' => [
        'name' => '米ドル',
        'symbol' => '$',
        'decimal_places' => 2
    ],
    'TWD' => [
        'name' => '台湾ドル',
        'symbol' => 'NT$',
        'decimal_places' => 2
    ]
];

/**
 * 通貨コードのバリデーション
 *
 * @param string $currency 通貨コード
 * @return bool 有効な通貨コードの場合true
 */
function validateCurrency($currency) {
    return in_array(strtoupper($currency), SUPPORTED_CURRENCIES, true);
}

/**
 * 通貨コードの正規化（大文字化 + デフォルト値）
 *
 * @param string|null $currency 通貨コード
 * @return string 正規化された通貨コード
 */
function normalizeCurrency($currency) {
    if (empty($currency)) {
        return DEFAULT_CURRENCY;
    }

    $normalized = strtoupper(trim($currency));

    if (!validateCurrency($normalized)) {
        error_log("⚠️ Invalid currency '{$currency}', using default: " . DEFAULT_CURRENCY);
        return DEFAULT_CURRENCY;
    }

    return $normalized;
}

/**
 * 金額を通貨フォーマットで表示
 *
 * @param float|int $amount 金額
 * @param string $currency 通貨コード
 * @return string フォーマットされた金額文字列
 */
function formatCurrency($amount, $currency = DEFAULT_CURRENCY) {
    $currency = normalizeCurrency($currency);
    $info = CURRENCY_INFO[$currency];

    // 小数点以下の桁数に応じてフォーマット
    $formatted = number_format($amount, $info['decimal_places'], '.', ',');

    // 通貨記号の位置
    if ($currency === 'JPY') {
        return $info['symbol'] . $formatted;
    } elseif ($currency === 'USD' || $currency === 'TWD') {
        return $info['symbol'] . $formatted;
    } else { // CNY
        return $formatted . $info['symbol'];
    }
}

/**
 * 通貨情報を取得
 *
 * @param string $currency 通貨コード
 * @return array|null 通貨情報（name, symbol, decimal_places）
 */
function getCurrencyInfo($currency) {
    $currency = normalizeCurrency($currency);
    return CURRENCY_INFO[$currency] ?? null;
}

/**
 * 金額と通貨コードを含むレスポンス用オブジェクトを作成
 *
 * @param float|int $amount 金額
 * @param string $currency 通貨コード
 * @return array ['amount' => float, 'currency' => string, 'formatted' => string]
 */
function createCurrencyResponse($amount, $currency = DEFAULT_CURRENCY) {
    $currency = normalizeCurrency($currency);
    $info = getCurrencyInfo($currency);

    return [
        'amount' => $amount,
        'currency' => $currency,
        'formatted' => formatCurrency($amount, $currency),
        'symbol' => $info['symbol'],
        'name' => $info['name']
    ];
}

/**
 * データベースから通貨情報を取得
 *
 * @param PDO $pdo データベース接続
 * @param string $currency 通貨コード
 * @return array|null データベースの通貨情報
 */
function getCurrencyFromDB($pdo, $currency) {
    try {
        $stmt = $pdo->prepare("
            SELECT currency_code, currency_name, currency_symbol, decimal_places, is_active
            FROM mst_currency
            WHERE currency_code = :currency AND is_active = 1
        ");
        $stmt->execute(['currency' => normalizeCurrency($currency)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("❌ Failed to fetch currency from DB: " . $e->getMessage());
        return null;
    }
}

/**
 * 対応通貨の一覧を取得
 *
 * @return array 対応通貨の配列
 */
function getSupportedCurrencies() {
    return SUPPORTED_CURRENCIES;
}

/**
 * 通貨エラーレスポンスを生成
 *
 * @param string $invalidCurrency 不正な通貨コード
 * @return array エラーレスポンス
 */
function createCurrencyErrorResponse($invalidCurrency) {
    return [
        'error' => 'INVALID_CURRENCY',
        'message' => "Unsupported currency: {$invalidCurrency}",
        'supported_currencies' => SUPPORTED_CURRENCIES
    ];
}
