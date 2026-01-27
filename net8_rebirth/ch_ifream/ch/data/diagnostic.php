<?php
/**
 * Railway Diagnostic Script
 * コンテナ内のファイル構造と権限を確認
 */

header('Content-Type: text/plain; charset=utf-8');

echo "=== NET8 Railway Diagnostic Report ===\n\n";

// 現在のディレクトリ
echo "Current Directory: " . getcwd() . "\n\n";

// DocumentRoot
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "\n\n";

// index.php の存在確認
$indexPath = __DIR__ . '/index.php';
echo "Checking index.php:\n";
echo "  Path: $indexPath\n";
echo "  Exists: " . (file_exists($indexPath) ? 'YES' : 'NO') . "\n";
echo "  Readable: " . (is_readable($indexPath) ? 'YES' : 'NO') . "\n";
echo "  Size: " . (file_exists($indexPath) ? filesize($indexPath) . ' bytes' : 'N/A') . "\n\n";

// ディレクトリ内容
echo "Directory Contents:\n";
$files = scandir(__DIR__);
foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;
    $path = __DIR__ . '/' . $file;
    $perms = substr(sprintf('%o', fileperms($path)), -4);
    $type = is_dir($path) ? 'DIR' : 'FILE';
    echo "  [$type] $file (perms: $perms)\n";
    if ($file === 'index.php') {
        echo "      First 100 chars: " . substr(file_get_contents($path), 0, 100) . "\n";
    }
}

echo "\n=== End of Diagnostic Report ===\n";
