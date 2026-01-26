<?php
// 最小限のテストスクリプト
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Test</title></head><body>";
echo "<h1>Step 1: PHP is working</h1>";

try {
    echo "<h2>Step 2: Trying to include require_files_admin.php</h2>";
    require_once('../../_etc/require_files_admin.php');
    echo "<p>✅ require_files_admin.php loaded successfully</p>";

    echo "<h2>Step 3: Checking TemplateAdmin class</h2>";
    $template = new TemplateAdmin();
    echo "<p>✅ TemplateAdmin instance created</p>";

    echo "<h2>Step 4: Checking database connection</h2>";
    $sql = "SELECT COUNT(*) as cnt FROM dat_machine WHERE del_flg = 0";
    $result = $template->DB->query($sql);
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Database query successful. Machine count: {$row['cnt']}</p>";

    echo "<h2>Step 5: Checking template file</h2>";
    $template_path = "../../_html/ja/admin/camera_settings.html";
    if (file_exists($template_path)) {
        echo "<p>✅ Template file exists: $template_path</p>";
    } else {
        echo "<p>❌ Template file NOT found: $template_path</p>";
    }

    echo "<h2>✅ All checks passed!</h2>";
    echo "<p><a href='camera_settings.php'>Try camera_settings.php again</a></p>";

} catch (Exception $e) {
    echo "<h2>❌ Error occurred:</h2>";
    echo "<pre style='background:#ffeeee;padding:20px;'>";
    echo htmlspecialchars($e->getMessage());
    echo "\n\nStack trace:\n";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}

echo "</body></html>";
?>
