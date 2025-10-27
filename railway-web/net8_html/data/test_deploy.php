<?php
echo "Railway Deploy Test - " . date('Y-m-d H:i:s');
echo "<br>Commit: a006c63";
echo "<br>File exists: update_license.php - " . (file_exists(__DIR__ . '/update_license.php') ? 'YES' : 'NO');
?>
