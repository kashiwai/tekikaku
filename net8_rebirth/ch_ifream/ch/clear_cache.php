<?php
/**
 * Clear PHP OPcache and restart
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>PHP Cache Clear</h1>";

// Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color: green;'>✅ OPcache cleared</p>";
} else {
    echo "<p style='color: orange;'>⚠️ OPcache not available</p>";
}

// Clear APCu if available
if (function_exists('apcu_clear_cache')) {
    apcu_clear_cache();
    echo "<p style='color: green;'>✅ APCu cache cleared</p>";
} else {
    echo "<p style='color: orange;'>⚠️ APCu not available</p>";
}

echo "<hr>";
echo "<h2>Test API after cache clear:</h2>";
echo "<p><a href='/api/cameraListAPI.php?M=getno&MAC=34-a6-ef-35-73-73&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=' target='_blank'>Click here to test API</a></p>";
echo "<p>Expected: <code>\"category\":2</code></p>";
?>
