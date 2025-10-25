<?php
/**
 * test_game_ui.php
 *
 * Game UI Test Page
 *
 * This script bypasses login for testing purposes only.
 * Creates a test session and redirects to the game page.
 */

session_start();

// Include required files
require_once('../_etc/require_files.php');

// Test user credentials
$TEST_MEMBER_NO = 1;
$TEST_MACHINE_NO = isset($_GET['machine_no']) ? intval($_GET['machine_no']) : 1;

try {
    // Create database instance
    $DB = DBFactory::getInstance();

    // Get test user data
    $sql = "SELECT * FROM mst_member WHERE member_no = ?";
    $result = $DB->select($sql, array($TEST_MEMBER_NO));

    if (empty($result)) {
        die("❌ Test user not found (member_no=$TEST_MEMBER_NO)");
    }

    $userData = $result[0];

    // Create test session (mimicking actual login)
    $_SESSION['UserInfo'] = array(
        'member_no' => $userData['member_no'],
        'nickname' => $userData['nickname'],
        'mail' => $userData['mail'],
        'point' => $userData['point'],
        'state' => $userData['state']
    );

    // Set last play time
    $_SESSION['lastplaytime'] = time() - 10; // 10 seconds ago to avoid reload error

    // Get machine data
    $sql = "SELECT m.*, mo.model_name, mo.category
            FROM dat_machine m
            LEFT JOIN mst_model mo ON m.model_no = mo.model_no
            WHERE m.machine_no = ?";
    $machineResult = $DB->select($sql, array($TEST_MACHINE_NO));

    if (empty($machineResult)) {
        die("❌ Machine not found (machine_no=$TEST_MACHINE_NO)");
    }

    $machineData = $machineResult[0];

    // Display test page
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NET8 Game UI Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-panel {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 10px;
        }
        .info-section {
            margin: 20px 0;
            padding: 15px;
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
            border-radius: 4px;
        }
        .info-section h3 {
            margin-top: 0;
            color: #2e7d32;
        }
        .info-item {
            margin: 8px 0;
            padding: 5px 0;
        }
        .label {
            font-weight: bold;
            color: #555;
            display: inline-block;
            width: 150px;
        }
        .value {
            color: #333;
        }
        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px 5px;
            background: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #45a049;
        }
        .btn-secondary {
            background: #2196F3;
        }
        .btn-secondary:hover {
            background: #0b7dda;
        }
        .status {
            padding: 5px 10px;
            border-radius: 3px;
            display: inline-block;
        }
        .status-ok {
            background: #c8e6c9;
            color: #2e7d32;
        }
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="test-panel">
        <h1>🎮 NET8 Game UI Test</h1>

        <div class="info-section">
            <h3>✅ Session Created</h3>
            <div class="info-item">
                <span class="label">Member No:</span>
                <span class="value"><?php echo $userData['member_no']; ?></span>
            </div>
            <div class="info-item">
                <span class="label">Nickname:</span>
                <span class="value"><?php echo htmlspecialchars($userData['nickname']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Email:</span>
                <span class="value"><?php echo htmlspecialchars($userData['mail']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Points:</span>
                <span class="value"><?php echo number_format($userData['point']); ?> pt</span>
            </div>
        </div>

        <div class="info-section">
            <h3>🎰 Machine Information</h3>
            <div class="info-item">
                <span class="label">Machine No:</span>
                <span class="value"><?php echo $machineData['machine_no']; ?></span>
            </div>
            <div class="info-item">
                <span class="label">Model:</span>
                <span class="value"><?php echo htmlspecialchars($machineData['model_name']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Category:</span>
                <span class="value"><?php echo $machineData['category'] == 1 ? 'パチスロ' : 'パチンコ'; ?></span>
            </div>
            <div class="info-item">
                <span class="label">Status:</span>
                <span class="status status-ok"><?php echo $machineData['machine_status'] == 1 ? 'Active' : 'Inactive'; ?></span>
            </div>
        </div>

        <div class="warning">
            ⚠️ <strong>Note:</strong> This is a test page. Camera connection requires Windows PC to be running.<br>
            The game UI will load, but WebRTC connection will fail until camera client is active.
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <a href="play_v2/?NO=<?php echo $TEST_MACHINE_NO; ?>" class="btn">
                🎮 Launch Game UI (Machine #<?php echo $TEST_MACHINE_NO; ?>)
            </a>
            <a href="test_game_ui.php" class="btn btn-secondary">
                🔄 Refresh Session
            </a>
        </div>

        <div style="margin-top: 30px; padding: 15px; background: #f9f9f9; border-radius: 5px; font-size: 14px;">
            <strong>🔗 Direct URLs:</strong><br>
            <div style="margin-top: 10px;">
                <strong>Local:</strong> <code>http://localhost:8080/test_game_ui.php</code><br>
                <strong>ngrok:</strong> <code>https://aicrypto.ngrok.dev/test_game_ui.php</code>
            </div>
        </div>
    </div>
</body>
</html>
    <?php

} catch (Exception $e) {
    die("❌ Error: " . $e->getMessage());
}
?>
