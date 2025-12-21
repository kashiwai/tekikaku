<?php
/**
 * マシン状態報告API
 * 各PCから起動時に報告を受け取る
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// DB接続
require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();
} catch (Exception $e) {
    echo json_encode(['error' => 'DB connection failed']);
    exit;
}

// POST: 状態報告を受信
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $machine_no = intval($input['machine_no'] ?? 0);
    $ip = $input['ip'] ?? '';
    $mac = $input['mac'] ?? '';
    $status = $input['status'] ?? 'online';

    if ($machine_no <= 0) {
        echo json_encode(['error' => 'Invalid machine_no']);
        exit;
    }

    // dat_machineを更新
    $sql = "UPDATE dat_machine SET
            ip_address = :ip,
            mac_address = :mac,
            pc_status = :status,
            last_report = NOW()
            WHERE machine_no = :machine_no";

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute([
        ':ip' => $ip,
        ':mac' => $mac,
        ':status' => $status,
        ':machine_no' => $machine_no
    ]);

    if ($stmt->rowCount() == 0) {
        // レコードがない場合は挿入
        $sql = "INSERT INTO dat_machine (machine_no, ip_address, mac_address, pc_status, last_report)
                VALUES (:machine_no, :ip, :mac, :status, NOW())
                ON DUPLICATE KEY UPDATE
                ip_address = :ip2, mac_address = :mac2, pc_status = :status2, last_report = NOW()";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':machine_no' => $machine_no,
            ':ip' => $ip,
            ':mac' => $mac,
            ':status' => $status,
            ':ip2' => $ip,
            ':mac2' => $mac,
            ':status2' => $status
        ]);
    }

    echo json_encode([
        'success' => true,
        'machine_no' => $machine_no,
        'ip' => $ip,
        'mac' => $mac,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit;
}

// GET: 全マシン状態を取得
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $sql = "SELECT machine_no, ip_address, mac_address, pc_status, last_report,
            TIMESTAMPDIFF(MINUTE, last_report, NOW()) as minutes_ago
            FROM dat_machine
            ORDER BY machine_no";

    $stmt = $pdo->query($sql);
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5分以上報告がないマシンはoffline扱い
    foreach ($machines as &$m) {
        if ($m['minutes_ago'] > 5) {
            $m['pc_status'] = 'offline';
        }
    }

    echo json_encode([
        'success' => true,
        'count' => count($machines),
        'machines' => $machines
    ]);
    exit;
}
