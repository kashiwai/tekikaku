<?php
/**
 * File List - Check deployed files
 */
header('Content-Type: text/plain; charset=UTF-8');

echo "デプロイされているPHPファイル一覧:\n";
echo "==========================================\n\n";

$dir = '/var/www/html';
$files = scandir($dir);

foreach ($files as $file) {
    if ($file === '.' || $file === '..') continue;

    $path = $dir . '/' . $file;
    if (is_file($path) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        echo "✓ $file\n";
    }
}

echo "\n==========================================\n";
echo "完了\n";
?>
