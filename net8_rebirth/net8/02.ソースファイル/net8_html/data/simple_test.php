<?php
/**
 * Simple Test Page
 * Tests basic PHP and database connectivity
 */

// Display all errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>NET8 Simple Test</h1>";
echo "<pre>";

// Test 1: PHP Version
echo "✅ PHP Version: " . phpversion() . "\n";

// Test 2: Session
session_start();
echo "✅ Session started: " . session_id() . "\n";

// Test 3: Database connection
try {
    $db = new PDO('mysql:host=db;dbname=net8_dev', 'net8user', 'net8pass');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected\n";

    // Test query
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM mst_member");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Member count: " . $result['cnt'] . "\n";

    $stmt = $db->query("SELECT COUNT(*) as cnt FROM dat_machine WHERE machine_status = 1");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "✅ Active machines: " . $result['cnt'] . "\n";

} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

// Test 4: File paths
echo "\n📂 Paths:\n";
echo "  Current dir: " . __DIR__ . "\n";
echo "  Document root: " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "  Script name: " . $_SERVER['SCRIPT_NAME'] . "\n";

echo "\n✅ All basic tests passed!\n";
echo "</pre>";

echo '<p><a href="/">← Back to Home</a></p>';
?>
