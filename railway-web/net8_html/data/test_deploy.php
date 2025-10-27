<?php
echo "Railway Deploy Test - " . date('Y-m-d H:i:s');
echo "<br>Commit: dcf8638";
echo "<br>File exists: update_license.php - " . (file_exists(__DIR__ . '/update_license.php') ? 'YES' : 'NO');
echo "<br>File exists: import_db.php - " . (file_exists(__DIR__ . '/import_db.php') ? 'YES' : 'NO');
echo "<br>File exists: import_db_v2.php - " . (file_exists(__DIR__ . '/import_db_v2.php') ? 'YES' : 'NO');
echo "<hr>";
echo "<h3>All PHP files in this directory:</h3>";
$files = glob(__DIR__ . '/*.php');
foreach ($files as $file) {
    echo basename($file) . "<br>";
}
?>
