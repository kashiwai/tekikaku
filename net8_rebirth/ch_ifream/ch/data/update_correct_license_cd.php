<?php
/**
 * Update License CD to Correct Value
 */

$EXEC_KEY = 'update_correct_cd_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

require_once('../_etc/require_files.php');

try {
    $DB = new NetDB();

    echo "<h1>Update License CD to Correct Value</h1>";

    $correct_cd = 'f2e419eee66138df5444cecab202fa3001944c772f0dada61288b7142925e5a1';
    $mac = '34-a6-ef-35-73-73';

    echo "<h2>Current Value</h2>";
    $current = $DB->getRow("SELECT mac_address, license_cd FROM mst_cameralist WHERE mac_address = '{$mac}'");

    if ($current) {
        echo "<p><strong>MAC:</strong> {$current['mac_address']}</p>";
        echo "<p><strong>Current CD:</strong> {$current['license_cd']}</p>";
    }

    echo "<h2>Updating to Correct Value</h2>";
    echo "<p><strong>Correct CD:</strong> {$correct_cd}</p>";

    $result = $DB->query("UPDATE mst_cameralist SET license_cd = '{$correct_cd}', upd_no = 1, upd_dt = NOW() WHERE mac_address = '{$mac}'");

    if ($result !== false) {
        echo "<p style='color:green;'>✓ Update successful!</p>";
    } else {
        echo "<p style='color:red;'>❌ Update failed!</p>";
    }

    echo "<h2>Verification</h2>";
    $updated = $DB->getRow("SELECT mac_address, license_cd FROM mst_cameralist WHERE mac_address = '{$mac}'");

    if ($updated) {
        echo "<p><strong>MAC:</strong> {$updated['mac_address']}</p>";
        echo "<p><strong>Updated CD:</strong> {$updated['license_cd']}</p>";

        if ($updated['license_cd'] === $correct_cd) {
            echo "<div style='background:#d4edda;padding:15px;border-radius:5px;margin-top:20px;'>";
            echo "<h3 style='color:green;margin-top:0;'>✅ Success!</h3>";
            echo "<p>License CD has been updated to the correct value.</p>";
            echo "<p><strong>Next step:</strong> Run slotserver.exe again on Windows.</p>";
            echo "</div>";
        } else {
            echo "<p style='color:red;'>❌ Verification failed - values don't match!</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red;'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
