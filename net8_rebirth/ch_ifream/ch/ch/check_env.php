<?php
/**
 * Check Environment Variables
 */

$EXEC_KEY = 'check_env_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

echo "<h1>Environment Variables Check</h1>";

// データベース関連の環境変数
$db_env_vars = [
    'DATABASE_HOST',
    'MYSQLHOST',
    'DB_HOST',
    'DATABASE_NAME',
    'MYSQL_DATABASE',
    'MYSQLDATABASE',
    'DB_NAME',
    'DATABASE_USER',
    'MYSQLUSER',
    'DB_USER',
    'DATABASE_PASSWORD',
    'MYSQL_ROOT_PASSWORD',
    'MYSQLPASSWORD',
    'DB_PASSWORD',
    'MYSQLPORT',
    'DATABASE_URL'
];

echo "<h2>Database Environment Variables</h2>";
echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Variable Name</th><th>Value</th></tr>";

foreach ($db_env_vars as $var) {
    $value = getenv($var);
    if ($value === false) {
        echo "<tr><td>{$var}</td><td style='color:red;'>❌ Not Set</td></tr>";
    } else {
        // パスワードは一部を隠す
        if (strpos($var, 'PASSWORD') !== false && strlen($value) > 4) {
            $display_value = substr($value, 0, 4) . str_repeat('*', strlen($value) - 4);
        } else {
            $display_value = htmlspecialchars($value);
        }
        echo "<tr><td>{$var}</td><td style='color:green;'>{$display_value}</td></tr>";
    }
}
echo "</table>";

// 実際の接続設定を確認
echo "<h2>Actual DB Configuration (from setting.php)</h2>";

require_once('../_etc/require_files.php');

echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
echo "<tr><th>Constant</th><th>Value</th></tr>";

$db_constants = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_DSN'];

foreach ($db_constants as $const) {
    if (defined($const)) {
        $value = constant($const);
        // パスワードとDSNは一部を隠す
        if ($const === 'DB_PASSWORD' && strlen($value) > 4) {
            $display_value = substr($value, 0, 4) . str_repeat('*', strlen($value) - 4);
        } else if ($const === 'DB_DSN') {
            // DSN内のパスワードを隠す
            $display_value = preg_replace('/:[^:@]+@/', ':****@', htmlspecialchars($value));
        } else {
            $display_value = htmlspecialchars($value);
        }
        echo "<tr><td>{$const}</td><td>{$display_value}</td></tr>";
    } else {
        echo "<tr><td>{$const}</td><td style='color:red;'>❌ Not Defined</td></tr>";
    }
}
echo "</table>";

// 接続テスト
echo "<h2>Database Connection Test</h2>";

try {
    $DB = new NetDB();
    echo "<p style='color:green;'>✓ Database connection successful!</p>";

    // テーブル一覧を取得
    $tables = $DB->query("SHOW TABLES");
    $table_count = 0;
    echo "<h3>Tables in Database:</h3>";
    echo "<ul>";
    while ($table = $tables->fetch(PDO::FETCH_NUM)) {
        echo "<li>{$table[0]}</li>";
        $table_count++;
    }
    echo "</ul>";
    echo "<p><strong>Total tables: {$table_count}</strong></p>";

    if ($table_count === 0) {
        echo "<p style='color:red;'>❌ No tables found! Database is empty.</p>";
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Database connection failed!</p>";
    echo "<p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}

// 全環境変数（デバッグ用）
if (isset($_GET['debug'])) {
    echo "<h2>All Environment Variables (Debug Mode)</h2>";
    echo "<pre style='background:#f5f5f5;padding:15px;border:1px solid #ccc;'>";
    print_r($_ENV);
    print_r(getenv());
    echo "</pre>";
}
?>
