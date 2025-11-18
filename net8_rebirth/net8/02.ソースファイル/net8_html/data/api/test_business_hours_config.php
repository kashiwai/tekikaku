<?php
/**
 * 営業時間設定のDB読み込みテスト
 *
 * get_business_hours_config()関数が正しく動作しているか確認
 *
 * アクセス: https://mgg-webservice-production.up.railway.app/data/api/test_business_hours_config.php
 */

require_once(__DIR__ . '/../../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<html><head><meta charset='UTF-8'><title>営業時間設定テスト</title></head><body>";
echo "<h1>営業時間設定 - DB読み込みテスト</h1>";
echo "<pre>\n";

echo "========================================\n";
echo "営業時間設定のDB読み込み確認\n";
echo "========================================\n\n";

// 1. グローバル変数の確認
echo "【1. グローバル変数 (RUNTIME_CONFIG)】\n";
if (isset($GLOBALS['RUNTIME_CONFIG'])) {
    echo "RUNTIME_CONFIG が存在します:\n";
    foreach ($GLOBALS['RUNTIME_CONFIG'] as $key => $value) {
        echo "  {$key} = {$value}\n";
    }
    echo "\n";
} else {
    echo "⚠️ RUNTIME_CONFIG が存在しません\n\n";
}

// 2. get_business_hours_config() 関数のテスト
echo "【2. get_business_hours_config() 関数のテスト】\n";
if (function_exists('get_business_hours_config')) {
    echo "get_business_hours_config() 関数が存在します ✅\n\n";

    $openTime = get_business_hours_config('GLOBAL_OPEN_TIME');
    $closeTime = get_business_hours_config('GLOBAL_CLOSE_TIME');
    $refTime = get_business_hours_config('REFERENCE_TIME');

    echo "  GLOBAL_OPEN_TIME  = {$openTime}\n";
    echo "  GLOBAL_CLOSE_TIME = {$closeTime}\n";
    echo "  REFERENCE_TIME    = {$refTime}\n\n";

    // 期待値チェック
    if ($openTime === '10:00' && $closeTime === '22:00' && $refTime === '04:00') {
        echo "✅ 営業時間設定が正しくDBから読み込まれています！\n";
    } else {
        echo "⚠️ 営業時間設定が期待値と異なります\n";
        echo "   期待値: 10:00, 22:00, 04:00\n";
        echo "   実際値: {$openTime}, {$closeTime}, {$refTime}\n";
    }
} else {
    echo "❌ get_business_hours_config() 関数が存在しません\n";
}

echo "\n";

// 3. 定数定義の確認（フォールバック）
echo "【3. 定数定義の確認（フォールバック）】\n";
echo "  GLOBAL_OPEN_TIME  = " . (defined('GLOBAL_OPEN_TIME') ? GLOBAL_OPEN_TIME : '未定義') . "\n";
echo "  GLOBAL_CLOSE_TIME = " . (defined('GLOBAL_CLOSE_TIME') ? GLOBAL_CLOSE_TIME : '未定義') . "\n";
echo "  REFERENCE_TIME    = " . (defined('REFERENCE_TIME') ? REFERENCE_TIME : '未定義') . "\n\n";

// 4. データベースから直接確認
echo "【4. データベースから直接確認】\n";
try {
    $db = new NetDB();
    $sql = "SELECT setting_key, setting_val
            FROM mst_setting
            WHERE setting_key IN ('GLOBAL_OPEN_TIME', 'GLOBAL_CLOSE_TIME', 'REFERENCE_TIME')
              AND del_flg = 0
            ORDER BY setting_no";

    $result = $db->query($sql);
    $count = 0;
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "  {$row['setting_key']} = {$row['setting_val']}\n";
    }

    if ($count == 3) {
        echo "\n✅ データベースに営業時間設定が3件存在します\n";
    } else {
        echo "\n⚠️ データベースの営業時間設定が{$count}件です（期待値: 3件）\n";
    }
} catch (Exception $e) {
    echo "❌ データベースエラー: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "テスト完了\n";
echo "========================================\n";

echo "</pre></body></html>";
?>
