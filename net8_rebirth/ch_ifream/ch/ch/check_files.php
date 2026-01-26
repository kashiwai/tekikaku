<?php
/**
 * Check Files Existence
 */

$EXEC_KEY = 'check_files_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

echo "<h1>File Existence Check</h1>";

// Check paths
$paths_to_check = [
    '/var/www/html/data/server_v2',
    '/var/www/html/data/server_v2/index.php',
    '/var/www/html/data/server_v2/_html',
    '/var/www/html/data/server_v2/_html/index.html',
    '/var/www/html/data',
    __DIR__,
    __DIR__ . '/../server_v2',
    __DIR__ . '/../server_v2/index.php'
];

echo "<h2>Path Checks</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Path</th><th>Exists</th><th>Type</th></tr>";

foreach ($paths_to_check as $path) {
    $exists = file_exists($path);
    $type = '';
    if ($exists) {
        if (is_dir($path)) {
            $type = 'Directory';
        } elseif (is_file($path)) {
            $type = 'File';
        }
    }

    $color = $exists ? 'green' : 'red';
    $status = $exists ? '✓ Exists' : '❌ Not Found';

    echo "<tr>";
    echo "<td>{$path}</td>";
    echo "<td style='color:{$color};'>{$status}</td>";
    echo "<td>{$type}</td>";
    echo "</tr>";
}
echo "</table>";

// List server_v2 contents if exists
$server_v2_path = '/var/www/html/data/server_v2';
if (is_dir($server_v2_path)) {
    echo "<h2>Contents of {$server_v2_path}</h2>";
    $files = scandir($server_v2_path);
    echo "<ul>";
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $full_path = $server_v2_path . '/' . $file;
        $type = is_dir($full_path) ? '[DIR]' : '[FILE]';
        echo "<li>{$type} {$file}</li>";
    }
    echo "</ul>";
}

// DocumentRoot info
echo "<h2>Server Information</h2>";
echo "<p><strong>DOCUMENT_ROOT:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>SCRIPT_FILENAME:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p><strong>Current __DIR__:</strong> " . __DIR__ . "</p>";
echo "<p><strong>Current __FILE__:</strong> " . __FILE__ . "</p>";

// Apache config check
echo "<h2>Access Test</h2>";
$test_url = 'https://dockerfileweb-production.up.railway.app/server_v2/index.php';
echo "<p><strong>Test URL:</strong> <a href='{$test_url}' target='_blank'>{$test_url}</a></p>";
?>
