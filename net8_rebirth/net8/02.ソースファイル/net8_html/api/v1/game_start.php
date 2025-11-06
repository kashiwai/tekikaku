<?php
/**
 * NET8 SDK API - Game Start Endpoint
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POSTメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/setting.php');
require_once('../../_lib/SmartDB.php');

// 認証ヘッダー確認（簡易版）
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header required'
    ]);
    exit;
}

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['modelId'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_MODEL_ID',
        'message' => 'Model ID is required'
    ]);
    exit;
}

$modelId = $input['modelId'];

try {
    $db = new SmartDB(DB_DSN);

    // 1. 機種情報を取得
    $modelSql = "SELECT model_no, model_cd, model_name, category
                 FROM mst_model
                 WHERE model_cd = " . $db->conv_sql($modelId, FD_TEXT) . "
                 AND del_flg = 0";

    $model = $db->getRow($modelSql);

    if (!$model) {
        http_response_code(404);
        echo json_encode([
            'error' => 'MODEL_NOT_FOUND',
            'message' => 'Model not found'
        ]);
        exit;
    }

    // 2. 利用可能なマシンを検索
    $machineSql = "SELECT
                    m.machine_no,
                    m.signaling_id,
                    m.camera_no,
                    m.machine_status
                FROM dat_machine m
                WHERE m.model_no = " . $db->conv_sql($model['model_no'], FD_NUM) . "
                AND m.del_flg = 0
                AND m.machine_status = 0
                AND m.end_date >= CURDATE()
                AND NOT EXISTS (
                    SELECT 1 FROM lnk_machine lm
                    WHERE lm.machine_no = m.machine_no
                    AND lm.assign_flg = 1
                )
                LIMIT 1";

    $machine = $db->getRow($machineSql);

    if (!$machine) {
        http_response_code(503);
        echo json_encode([
            'error' => 'NO_AVAILABLE_MACHINE',
            'message' => 'No available machine for this model'
        ]);
        exit;
    }

    // 3. ゲームセッションIDを生成
    $sessionId = 'gs_' . uniqid() . '_' . time();

    // 4. WebRTC Signaling情報
    $signalingInfo = [
        'signalingId' => $machine['signaling_id'],
        'host' => SIGNALING_HOST,
        'port' => SIGNALING_PORT,
        'secure' => SIGNALING_PORT == 443,
        'path' => SIGNALING_PATH,
        'iceServers' => [
            ['urls' => 'stun:stun.l.google.com:19302']
        ]
    ];

    // 5. カメラ情報（WebRTC用）
    $cameraInfo = null;
    if ($machine['camera_no']) {
        $cameraSql = "SELECT camera_url FROM mst_camera
                      WHERE camera_no = " . $db->conv_sql($machine['camera_no'], FD_NUM);
        $camera = $db->getRow($cameraSql);
        if ($camera) {
            $cameraInfo = [
                'cameraNo' => $machine['camera_no'],
                'streamUrl' => $camera['camera_url']
            ];
        }
    }

    // 成功レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'sessionId' => $sessionId,
        'machineNo' => $machine['machine_no'],
        'signalingId' => $machine['signaling_id'],
        'model' => [
            'id' => $model['model_cd'],
            'name' => $model['model_name'],
            'category' => $model['category'] == 1 ? 'pachinko' : 'slot'
        ],
        'signaling' => $signalingInfo,
        'camera' => $cameraInfo,
        'playUrl' => "/data/play_v2/index.php?NO={$machine['machine_no']}"
    ]);

} catch (Exception $e) {
    error_log('Game Start API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to start game: ' . $e->getMessage()
    ]);
}
