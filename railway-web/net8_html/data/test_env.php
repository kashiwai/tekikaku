<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== 環境変数チェック ===\n\n";

$env_vars = [
    'DATABASE_HOST',
    'DATABASE_PORT',
    'DATABASE_USER', 
    'DATABASE_PASSWORD',
    'DATABASE_NAME',
    'MYSQLHOST',
    'MYSQLPORT',
    'MYSQLUSER',
    'MYSQLPASSWORD',
    'MYSQLDATABASE'
];

foreach ($env_vars as $var) {
    $value = getenv($var);
    if ($var === 'DATABASE_PASSWORD' || $var === 'MYSQLPASSWORD') {
        echo "$var = " . ($value ? '****' : 'NOT SET') . "\n";
    } else {
        echo "$var = " . ($value ? $value : 'NOT SET') . "\n";
    }
}

echo "\n=== All Environment Variables ===\n";
$all_vars = getenv();
ksort($all_vars);
foreach ($all_vars as $key => $value) {
    if (stripos($key, 'PASSWORD') !== false || stripos($key, 'PASS') !== false || stripos($key, 'SECRET') !== false) {
        echo "$key = ****\n";
    } elseif (stripos($key, 'MYSQL') !== false || stripos($key, 'DATABASE') !== false) {
        echo "$key = $value\n";
    }
}
?>
