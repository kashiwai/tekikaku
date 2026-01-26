<?php
/*
 * debug_template.php
 * HTMLテンプレートの内容を確認するデバッグスクリプト
 */

header('Content-Type: text/plain; charset=UTF-8');

echo "=== Template Debug Info ===\n\n";

// DIR_HTML_ADMIN の確認
$lang = 'ja';
$template_dir = __DIR__ . '/../../_html/' . $lang . '/admin/';

echo "Template Directory: " . $template_dir . "\n";
echo "Directory Exists: " . (is_dir($template_dir) ? 'YES' : 'NO') . "\n\n";

// camera_settings.html の確認
$camera_settings_file = $template_dir . 'camera_settings.html';
echo "=== camera_settings.html ===\n";
echo "File: " . $camera_settings_file . "\n";
echo "Exists: " . (file_exists($camera_settings_file) ? 'YES' : 'NO') . "\n";

if (file_exists($camera_settings_file)) {
    $content = file_get_contents($camera_settings_file);

    // loop:MACHINE タグを検索
    $machine_loop_count = preg_match_all('/<!--loop:MACHINE-->/', $content, $matches);
    echo "<!--loop:MACHINE--> found: " . $machine_loop_count . " times\n";

    // Smarty {section} タグを検索
    $smarty_section_count = preg_match_all('/\{section/', $content, $matches);
    echo "{section} found: " . $smarty_section_count . " times\n";

    // ファイルサイズ
    echo "File size: " . strlen($content) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($camera_settings_file)) . "\n";
}

echo "\n";

// streaming.html の確認
$streaming_file = $template_dir . 'streaming.html';
echo "=== streaming.html ===\n";
echo "File: " . $streaming_file . "\n";
echo "Exists: " . (file_exists($streaming_file) ? 'YES' : 'NO') . "\n";

if (file_exists($streaming_file)) {
    $content = file_get_contents($streaming_file);

    // loop:STREAMING タグを検索
    $streaming_loop_count = preg_match_all('/<!--loop:STREAMING-->/', $content, $matches);
    echo "<!--loop:STREAMING--> found: " . $streaming_loop_count . " times\n";

    // ファイルサイズ
    echo "File size: " . strlen($content) . " bytes\n";
    echo "Last modified: " . date('Y-m-d H:i:s', filemtime($streaming_file)) . "\n";
}

echo "\n=== End Debug ===\n";
?>
