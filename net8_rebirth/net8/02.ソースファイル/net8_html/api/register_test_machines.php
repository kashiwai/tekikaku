<?php
/**
 * NET8 SDK - Test Machine Registration Script
 * Registers test machines for HOKUTO4GO and ZENIGATA01
 */

require_once(__DIR__ . '/../_etc/require_files.php');

header('Content-Type: text/html; charset=UTF-8');

echo "<h1>NET8 SDK - Test Machine Registration</h1>";
echo "<pre>";

try {
    $pdo = get_db_connection();
    echo "✅ Database connection successful\n\n";

    // Check existing machines
    echo "🔍 Checking existing machines...\n";
    $checkSql = "SELECT COUNT(*) as count FROM dat_machine WHERE del_flg = 0";
    $count = $pdo->query($checkSql)->fetch(PDO::FETCH_ASSOC)['count'];
    echo "   Existing machines: $count\n\n";

    // Get model numbers
    echo "📋 Getting model numbers...\n";
    $models = $pdo->query("SELECT model_no, model_cd, model_name FROM mst_model WHERE del_flg = 0")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($models as $model) {
        echo "   ✓ {$model['model_cd']}: {$model['model_name']} (model_no: {$model['model_no']})\n";
    }
    echo "\n";

    // Prepare machine data
    $machines = [
        [
            'model_cd' => 'HOKUTO4GO',
            'signaling_id' => 'sig_hokuto_001',
            'name' => 'HOKUTO Test Machine 1'
        ],
        [
            'model_cd' => 'HOKUTO4GO',
            'signaling_id' => 'sig_hokuto_002',
            'name' => 'HOKUTO Test Machine 2'
        ],
        [
            'model_cd' => 'ZENIGATA01',
            'signaling_id' => 'sig_zenigata_001',
            'name' => 'ZENIGATA Test Machine 1'
        ]
    ];

    echo "🔧 Registering test machines...\n\n";

    $insertSql = "INSERT INTO dat_machine (
        model_no,
        signaling_id,
        camera_no,
        machine_status,
        end_date,
        del_flg,
        created_at,
        updated_at
    ) VALUES (
        (SELECT model_no FROM mst_model WHERE model_cd = :model_cd AND del_flg = 0 LIMIT 1),
        :signaling_id,
        NULL,
        0,
        DATE_ADD(CURDATE(), INTERVAL 1 YEAR),
        0,
        NOW(),
        NOW()
    )";

    $registered = 0;
    foreach ($machines as $machine) {
        try {
            $stmt = $pdo->prepare($insertSql);
            $stmt->execute([
                'model_cd' => $machine['model_cd'],
                'signaling_id' => $machine['signaling_id']
            ]);

            echo "   ✅ Registered: {$machine['name']} ({$machine['signaling_id']})\n";
            $registered++;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "   ⚠️  Already exists: {$machine['name']} (skipping)\n";
            } else {
                throw $e;
            }
        }
    }

    echo "\n✅ Registration complete! ($registered new machines)\n\n";

    // Verify registration
    echo "📊 Verification:\n\n";
    $verifySql = "SELECT
                    m.machine_no,
                    m.signaling_id,
                    m.machine_status,
                    m.end_date,
                    mm.model_cd,
                    mm.model_name
                FROM dat_machine m
                JOIN mst_model mm ON m.model_no = mm.model_no
                WHERE m.del_flg = 0
                ORDER BY m.machine_no DESC
                LIMIT 10";

    $machines = $pdo->query($verifySql)->fetchAll(PDO::FETCH_ASSOC);

    if (empty($machines)) {
        echo "   ⚠️  No machines found\n";
    } else {
        echo "   Total active machines: " . count($machines) . "\n\n";
        foreach ($machines as $m) {
            $status = $m['machine_status'] == 0 ? '🟢 Available' : '🔴 In Use';
            $end_date = date('Y-m-d', strtotime($m['end_date']));
            echo "   $status Machine #{$m['machine_no']}: {$m['model_cd']} - {$m['signaling_id']} (valid until: $end_date)\n";
        }
    }

    echo "\n\n";
    echo "🎉 Setup complete!\n";
    echo "\n";
    echo "Next steps:\n";
    echo "1. Test game_start API with HOKUTO4GO\n";
    echo "2. Test game_start API with ZENIGATA01\n";
    echo "3. Verify WebRTC signaling connection\n";

} catch (Exception $e) {
    echo "\n\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
