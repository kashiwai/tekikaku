<?php
/**
 * Railway IPアドレス取得スクリプト
 * RailwayのサーバーIPと外部向けIPアドレスを取得
 */

header('Content-Type: text/plain; charset=utf-8');

echo "==========================================\n";
echo "🔍 Railway IPアドレス情報\n";
echo "==========================================\n\n";

// サーバー内部IP
echo "📍 Server IP (内部): " . ($_SERVER['SERVER_ADDR'] ?? 'N/A') . "\n";

// 外部向けIP（RailwayがGCP Cloud SQLに接続する際のIP）
echo "🌐 Outgoing IP (外部): ";
$outgoing_ip = @file_get_contents('https://api.ipify.org');
if ($outgoing_ip) {
    echo $outgoing_ip . "\n";
} else {
    echo "取得失敗\n";
}

echo "\n==========================================\n";
echo "📝 このIPアドレスをGCP Cloud SQLの\n";
echo "   承認済みネットワークに追加してください\n";
echo "==========================================\n";
?>
