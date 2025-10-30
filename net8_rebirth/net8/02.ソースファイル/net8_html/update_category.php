<?php
/**
 * Railway MySQL Category Update Script
 *
 * Updates category to 2 (slot) for model_no=1
 */

require_once('./_etc/setting.php');

header('Content-Type: text/html; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASSWORD,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    echo "<h1>Railway MySQL Category Update</h1>";
    echo "<p>DB_HOST: " . DB_HOST . "</p>";
    echo "<p>DB_NAME: " . DB_NAME . "</p>";
    echo "<hr>";

    // Before update
    echo "<h2>Before Update:</h2>";
    $stmt = $pdo->query("SELECT model_no, model_name, category FROM mst_model WHERE model_no = 1");
    $before = $stmt->fetch();
    echo "<pre>";
    print_r($before);
    echo "</pre>";

    // Update
    echo "<h2>Updating category to 2...</h2>";
    $stmt = $pdo->prepare("UPDATE mst_model SET category = 2 WHERE model_no = 1");
    $result = $stmt->execute();

    if ($result) {
        echo "<p style='color: green; font-size: 20px;'>✅ Update successful!</p>";
    } else {
        echo "<p style='color: red; font-size: 20px;'>❌ Update failed!</p>";
    }

    // After update
    echo "<h2>After Update:</h2>";
    $stmt = $pdo->query("SELECT model_no, model_name, category FROM mst_model WHERE model_no = 1");
    $after = $stmt->fetch();
    echo "<pre>";
    print_r($after);
    echo "</pre>";

    echo "<hr>";
    echo "<h2>✅ Verification:</h2>";
    echo "<p>Please test the API again from Windows:</p>";
    echo "<code style='background: #f0f0f0; padding: 10px; display: block;'>";
    echo 'https://mgg-webservice-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC=34-a6-ef-35-73-73&ID=IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=';
    echo "</code>";

    echo "<p>Expected result: <code>\"category\":2</code></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>
