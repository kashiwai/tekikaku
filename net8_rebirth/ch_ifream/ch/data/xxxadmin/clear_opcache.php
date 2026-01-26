<?php
/**
 * OPcache クリアスクリプト
 *
 * PHPファイルの変更が反映されない場合に実行
 *
 * アクセス URL:
 * https://mgg-webservice-production.up.railway.app/data/xxxadmin/clear_opcache.php
 */

header('Content-Type: text/html; charset=UTF-8');
echo "<html><head><meta charset='UTF-8'><title>OPcache クリア</title></head><body>";
echo "<h1>OPcache クリアツール</h1>";
echo "<hr>";

// OPcache が有効かチェック
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status();

    if ($status !== false) {
        echo "<h2>実行前の OPcache 状態</h2>";
        echo "<pre>";
        echo "有効: はい\n";
        echo "キャッシュ済みスクリプト数: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
        echo "ヒット数: " . $status['opcache_statistics']['hits'] . "\n";
        echo "ミス数: " . $status['opcache_statistics']['misses'] . "\n";
        echo "メモリ使用量: " . number_format($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB\n";
        echo "</pre>";

        // OPcache をリセット
        if (opcache_reset()) {
            echo "<div style='background: #e8f5e9; padding: 20px; border-left: 4px solid #4caf50; margin: 20px 0;'>";
            echo "<h2 style='color: #2e7d32; margin-top: 0;'>✅ OPcache クリア成功</h2>";
            echo "<p>PHPファイルのキャッシュがクリアされました。</p>";
            echo "<p>変更が反映されているはずです。トップページを再読み込みしてください。</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3e0; padding: 20px; border-left: 4px solid #ff9800; margin: 20px 0;'>";
            echo "<h2 style='color: #f57c00; margin-top: 0;'>⚠️ OPcache クリア失敗</h2>";
            echo "<p>権限不足の可能性があります。</p>";
            echo "</div>";
        }

        // クリア後の状態
        $statusAfter = opcache_get_status();
        echo "<h2>実行後の OPcache 状態</h2>";
        echo "<pre>";
        echo "キャッシュ済みスクリプト数: " . $statusAfter['opcache_statistics']['num_cached_scripts'] . "\n";
        echo "</pre>";

    } else {
        echo "<div style='background: #ffebee; padding: 20px; border-left: 4px solid #f44336; margin: 20px 0;'>";
        echo "<h2 style='color: #c62828; margin-top: 0;'>❌ OPcache 無効</h2>";
        echo "<p>OPcacheは有効ですが、ステータス取得に失敗しました。</p>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #e3f2fd; padding: 20px; border-left: 4px solid #2196f3; margin: 20px 0;'>";
    echo "<h2 style='color: #1565c0; margin-top: 0;'>ℹ️ OPcache 未インストール</h2>";
    echo "<p>OPcacheがインストールされていません。キャッシュの問題ではありません。</p>";
    echo "</div>";
}

// PHP情報（デバッグ用）
echo "<hr>";
echo "<h2>PHP設定情報</h2>";
echo "<pre>";
echo "PHP Version: " . phpversion() . "\n";
echo "OPcache: " . (function_exists('opcache_get_status') ? '有効' : '無効') . "\n";
if (function_exists('opcache_get_configuration')) {
    $config = opcache_get_configuration();
    echo "OPcache memory_consumption: " . ($config['directives']['opcache.memory_consumption'] / 1024 / 1024) . " MB\n";
    echo "OPcache max_accelerated_files: " . $config['directives']['opcache.max_accelerated_files'] . "\n";
    echo "OPcache revalidate_freq: " . $config['directives']['opcache.revalidate_freq'] . " seconds\n";
}
echo "</pre>";

echo "</body></html>";
?>
