<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "<h1>NET8 Debug Page</h1>";

// Step 1: Check require_files.php
echo "<h2>Step 1: Loading require_files.php</h2>";
try {
    require_once('../_etc/require_files.php');
    echo "✓ require_files.php loaded successfully<br>";
} catch (Exception $e) {
    echo "✗ Error loading require_files.php: " . $e->getMessage() . "<br>";
    exit;
}

// Step 2: Check classes
echo "<h2>Step 2: Checking Classes</h2>";
$classes = ['SmartTemplate', 'SmartDB', 'SmartGeneral', 'SmartSession', 'TemplateUser'];
foreach ($classes as $class) {
    if (class_exists($class)) {
        echo "✓ Class '$class' exists<br>";
    } else {
        echo "✗ Class '$class' NOT found<br>";
    }
}

// Step 3: Check functions
echo "<h2>Step 3: Checking Functions</h2>";
$functions = ['get_self', 'h', 'redirect', 'get_db_connection'];
foreach ($functions as $func) {
    if (function_exists($func)) {
        echo "✓ Function '$func' exists<br>";
    } else {
        echo "✗ Function '$func' NOT found<br>";
    }
}

// Step 4: Check database connection
echo "<h2>Step 4: Database Connection</h2>";
try {
    $pdo = get_db_connection();
    echo "✓ Database connected successfully<br>";
    echo "Database: " . DB_NAME . "@" . DB_HOST . "<br>";

    // Test query
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
    $result = $stmt->fetch();
    echo "✓ Number of tables in database: " . $result['cnt'] . "<br>";
} catch (Exception $e) {
    echo "✗ Database connection error: " . $e->getMessage() . "<br>";
}

// Step 5: Check constants
echo "<h2>Step 5: Checking Constants</h2>";
$constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'SITE_NAME', 'DEBUG_MODE'];
foreach ($constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        echo "✓ Constant '$const' = " . htmlspecialchars($value) . "<br>";
    } else {
        echo "✗ Constant '$const' NOT defined<br>";
    }
}

echo "<h2>All checks complete!</h2>";
?>
