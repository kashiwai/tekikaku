<?php
/**
 * NET8 SDK API Keys Setup Script
 * Run this once to create API keys tables and insert demo data
 */

require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>NET8 SDK - Database Setup</h1>";
echo "<pre>";

try {
    // PDO接続取得
    $pdo = get_db_connection();

    echo "✅ Database connection successful\n\n";

    // Read SQL setup file
    $sql_file = __DIR__ . '/setup_api_keys_table.sql';
    $sql = file_get_contents($sql_file);

    if (!$sql) {
        throw new Exception("Failed to read SQL file: $sql_file");
    }

    echo "📄 SQL file loaded: api/setup_api_keys_table.sql\n\n";

    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        function($stmt) {
            return !empty($stmt) && substr($stmt, 0, 2) !== '--';
        }
    );

    echo "🔧 Executing SQL statements...\n";
    echo "Total statements found: " . count($statements) . "\n\n";

    // Execute each statement
    $executed = 0;
    foreach ($statements as $index => $statement) {
        $statement = trim($statement);

        // Skip empty or comment-only statements
        if (empty($statement) || preg_match('/^\s*--/', $statement)) {
            echo "  ⊘ Statement " . ($index + 1) . ": Skipped (comment/empty)\n";
            continue;
        }

        // Skip SELECT statements
        if (preg_match('/^\s*SELECT/i', $statement)) {
            echo "  ⊘ Statement " . ($index + 1) . ": Skipped (SELECT query)\n";
            continue;
        }

        try {
            echo "  → Executing statement " . ($index + 1) . "...\n";
            $affected = $pdo->exec($statement);
            $executed++;

            // Show what was executed
            $first_line = strtok($statement, "\n");
            echo "  ✓ Statement " . ($index + 1) . ": " . substr($first_line, 0, 80) . "... (affected: $affected)\n";

        } catch (PDOException $e) {
            $error_msg = $e->getMessage();

            // Ignore "table already exists" errors
            if (strpos($error_msg, 'already exists') !== false) {
                echo "  ⚠ Statement " . ($index + 1) . ": Table already exists (skipping)\n";
            } else if (strpos($error_msg, 'Duplicate entry') !== false) {
                echo "  ⚠ Statement " . ($index + 1) . ": Duplicate entry (skipping)\n";
            } else {
                echo "  ❌ Statement " . ($index + 1) . " ERROR: " . $error_msg . "\n";
                echo "  SQL: " . substr($statement, 0, 200) . "...\n";
                throw $e;
            }
        }
    }

    echo "\nTotal executed: $executed statements\n";

    echo "\n✅ Setup completed successfully!\n\n";

    // Verify tables
    echo "📊 Verifying tables...\n\n";

    $tables = $pdo->query("SHOW TABLES LIKE 'api_%'")->fetchAll(PDO::FETCH_COLUMN);
    echo "  Created tables: " . implode(', ', $tables) . "\n\n";

    // Show API keys
    echo "🔑 API Keys:\n\n";

    $keys = $pdo->query("SELECT id, key_value, name, environment, is_active FROM api_keys")->fetchAll();

    if (empty($keys)) {
        echo "  ⚠ No API keys found\n";
    } else {
        foreach ($keys as $key) {
            $status = $key['is_active'] ? '✅' : '❌';
            echo "  $status [{$key['environment']}] {$key['key_value']} - {$key['name']}\n";
        }
    }

    echo "\n\n";
    echo "🎉 Setup complete!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test API: https://mgg-webservice-production.up.railway.app/api/v1/models.php\n";
    echo "2. Test Auth: https://mgg-webservice-production.up.railway.app/api/v1/auth.php\n";
    echo "3. Demo Page: https://mgg-webservice-production.up.railway.app/sdk/demo.html\n";
    echo "4. Admin: https://mgg-webservice-production.up.railway.app/data/xxxadmin/api_keys_manage.php\n";

} catch (Exception $e) {
    echo "\n\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>