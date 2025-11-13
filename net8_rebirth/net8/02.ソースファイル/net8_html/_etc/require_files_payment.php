<?php
/**
 * NET8 Require Files (Payment)
 *
 * 決済機能用共通インクルードファイル
 * プレイページなど決済機能が必要なページから読み込まれる
 */

// 基本のrequire_filesを読み込み
require_once(__DIR__ . '/require_files.php');

// 決済関連の追加ライブラリ読み込み
$lib_dir = __DIR__ . '/../_lib/';
$sys_dir = __DIR__ . '/../_sys/';

// 決済関連ライブラリ（存在する場合のみ読み込み）
$payment_libs = [
    // 決済APIクラス
    $sys_dir . 'payment/gash/SettlementPoint.php',
    // $sys_dir . 'payment/lavy/SettlementPoint.php', // lavyの方は使わない場合はコメントアウト
    // $sys_dir . 'PaymentAPI.php',
    // $sys_dir . 'CreditAPI.php',
    // $sys_dir . 'PointAPI.php',
];

foreach ($payment_libs as $lib) {
    if (file_exists($lib)) {
        require_once($lib);
    }
}

// 決済関連の定数定義（必要に応じて追加）
if (!defined('PAYMENT_ENABLED')) define('PAYMENT_ENABLED', false); // 開発環境では決済無効
if (!defined('PAYMENT_TEST_MODE')) define('PAYMENT_TEST_MODE', true); // テストモード

// 決済ディレクトリのパス定義
if (!defined('PAYMENT_DIR')) define('PAYMENT_DIR', __DIR__ . '/../data/payment/gash/');

?>
