<?php
/**
 * MeshCentral API 連携
 * Windows 41台をリモート制御するためのAPI
 */

// MeshCentral設定
define('MESHCENTRAL_URL', 'https://meshcentral-uz6d.onrender.com');
define('MESHCENTRAL_USER', '~t:7eLkNoMAbtwTEuFY');
define('MESHCENTRAL_SECRET', 'tPCNs8rp7wA0Y81CcHpN');

/**
 * MeshCentral APIを呼び出す（ログイントークン認証）
 */
function meshcentralAPI($action, $params = []) {
    // URLにログイントークンを追加
    $url = MESHCENTRAL_URL . '/control.ashx?user=' . urlencode(MESHCENTRAL_USER) . '&pass=' . urlencode(MESHCENTRAL_SECRET);

    $data = array_merge(['action' => $action], $params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json'
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($error) {
        return ['error' => $error, 'httpCode' => 0];
    }

    $decoded = json_decode($response, true);
    if ($decoded) {
        return $decoded;
    }

    // JSONでない場合は別の方法を試す
    return meshcentralAPIWithHeader($action, $params);
}

/**
 * ヘッダー認証方式
 */
function meshcentralAPIWithHeader($action, $params = []) {
    $url = MESHCENTRAL_URL . '/api/meshcentral';
    $data = array_merge(['action' => $action], $params);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-meshcentral-user: ' . MESHCENTRAL_USER,
            'x-meshcentral-pass: ' . MESHCENTRAL_SECRET
        ],
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return json_decode($response, true) ?: ['raw' => substr($response, 0, 500), 'httpCode' => $httpCode];
}

/**
 * デバイス一覧を取得
 */
function getDevices() {
    return meshcentralAPI('nodes');
}

/**
 * デバイスにコマンドを実行
 */
function runCommand($nodeId, $command) {
    return meshcentralAPI('runcommand', [
        'nodeids' => [$nodeId],
        'cmd' => $command,
        'type' => 1  // 1 = Command Prompt
    ]);
}

/**
 * デバイスの電源操作
 * $operation: 1=Sleep, 2=Reset, 3=Power Off, 4=Power On (Wake-on-LAN)
 */
function powerAction($nodeId, $operation) {
    return meshcentralAPI('poweraction', [
        'nodeids' => [$nodeId],
        'actiontype' => $operation
    ]);
}

/**
 * 複数デバイスにコマンドを一括実行
 */
function runCommandBatch($nodeIds, $command) {
    return meshcentralAPI('runcommand', [
        'nodeids' => $nodeIds,
        'cmd' => $command,
        'type' => 1
    ]);
}

// APIエンドポイントとして使用する場合
if (isset($_GET['api'])) {
    header('Content-Type: application/json');

    $action = $_GET['api'] ?? '';

    switch ($action) {
        case 'devices':
            echo json_encode(getDevices());
            break;

        case 'run':
            $nodeId = $_POST['node_id'] ?? '';
            $command = $_POST['command'] ?? '';
            if ($nodeId && $command) {
                echo json_encode(runCommand($nodeId, $command));
            } else {
                echo json_encode(['error' => 'node_id and command required']);
            }
            break;

        case 'power':
            $nodeId = $_POST['node_id'] ?? '';
            $operation = intval($_POST['operation'] ?? 0);
            if ($nodeId && $operation) {
                echo json_encode(powerAction($nodeId, $operation));
            } else {
                echo json_encode(['error' => 'node_id and operation required']);
            }
            break;

        case 'batch':
            $nodeIds = json_decode($_POST['node_ids'] ?? '[]', true);
            $command = $_POST['command'] ?? '';
            if ($nodeIds && $command) {
                echo json_encode(runCommandBatch($nodeIds, $command));
            } else {
                echo json_encode(['error' => 'node_ids and command required']);
            }
            break;

        case 'test':
            // API接続テスト
            $result = meshcentralAPI('serverinfo');
            echo json_encode(['status' => 'ok', 'serverinfo' => $result]);
            break;

        default:
            echo json_encode(['error' => 'Unknown action', 'available' => ['devices', 'run', 'power', 'batch', 'test']]);
    }
    exit;
}
?>
