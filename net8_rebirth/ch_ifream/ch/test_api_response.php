<?php
/**
 * Test API Response
 */

$EXEC_KEY = 'test_api_2025';

if (!isset($_GET['key']) || $_GET['key'] !== $EXEC_KEY) {
    die('Access denied. Invalid key.');
}

require_once('../_etc/require_files.php');

echo "<h1>API Response Test</h1>";

// Windows exeが送信するパラメータをシミュレート
$mac = '34-a6-ef-35-73-73';
$license_id = 'IjhVdlJOSlJFNzhGMFc2eDRHbmFSMFN6UjhUTVJuRHdmSm9IT1wvRFwvSWZ6QT0gdjBxZlo3XC83cDZpTXoxSHNqN25QRkE9PSI=';
$ip = '192.168.1.100';

echo "<h2>Request Parameters</h2>";
echo "<p><strong>MAC:</strong> {$mac}</p>";
echo "<p><strong>License ID:</strong> {$license_id}</p>";
echo "<p><strong>IP:</strong> {$ip}</p>";

echo "<h2>API Call Simulation</h2>";

// APIを直接呼び出し
$url = "https://dockerfileweb-production.up.railway.app/api/cameraListAPI.php?M=getno&MAC={$mac}&ID={$license_id}&IP={$ip}";

echo "<p><strong>URL:</strong> <a href='{$url}' target='_blank'>{$url}</a></p>";

$response = file_get_contents($url);

echo "<h2>API Response</h2>";
echo "<pre style='background:#f5f5f5;padding:15px;border:1px solid #ccc;'>";
echo htmlspecialchars($response);
echo "</pre>";

echo "<h2>JSON Decoded</h2>";
$json = json_decode($response, true);

if ($json) {
    echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($json as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        }
        echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
    }
    echo "</table>";

    // license_cdフィールドの存在確認
    echo "<h2>License Check</h2>";
    if (isset($json['cd'])) {
        echo "<p style='color:red;'>❌ <strong>PROBLEM FOUND:</strong> 'cd' field exists in response!</p>";
        echo "<p><strong>cd value:</strong> {$json['cd']}</p>";
        echo "<p>Windows exe will show NG License error when 'cd' field is present.</p>";
    } else {
        echo "<p style='color:green;'>✓ 'cd' field NOT present (correct for license bypass)</p>";
    }

    if (isset($json['error'])) {
        echo "<p style='color:red;'>❌ <strong>ERROR:</strong> {$json['error']}</p>";
    }

    if (isset($json['machine_no'])) {
        echo "<p style='color:green;'>✓ machine_no found: {$json['machine_no']}</p>";
    }
} else {
    echo "<p style='color:red;'>Failed to decode JSON</p>";
}

// データベース状態確認
echo "<h2>Database Check</h2>";

try {
    $DB = new NetDB();

    // mst_cameralist
    echo "<h3>mst_cameralist</h3>";
    $cameralist = $DB->getRow("SELECT * FROM mst_cameralist WHERE mac_address = '{$mac}'");
    if ($cameralist) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        foreach ($cameralist as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No record found in mst_cameralist</p>";
    }

    // mst_camera
    echo "<h3>mst_camera</h3>";
    $camera = $DB->getRow("SELECT * FROM mst_camera WHERE camera_mac = '{$mac}'");
    if ($camera) {
        echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
        foreach ($camera as $key => $value) {
            echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color:red;'>❌ No record found in mst_camera</p>";
    }

    // dat_machine via camera_no
    if ($camera) {
        echo "<h3>dat_machine (via camera_no={$camera['camera_no']})</h3>";
        $machine = $DB->getRow("SELECT * FROM dat_machine WHERE camera_no = {$camera['camera_no']}");
        if ($machine) {
            echo "<table border='1' cellpadding='5' style='border-collapse:collapse;'>";
            foreach ($machine as $key => $value) {
                if (strlen($value) > 100) {
                    $value = substr($value, 0, 100) . '...';
                }
                echo "<tr><td><strong>{$key}</strong></td><td>{$value}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color:red;'>❌ No record found in dat_machine</p>";
        }
    }

} catch (Exception $e) {
    echo "<p style='color:red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
