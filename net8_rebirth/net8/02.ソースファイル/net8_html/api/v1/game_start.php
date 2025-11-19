<?php
/**
 * NET8 SDK API - Game Start Endpoint
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 */

header('Content-Type: application/json');
// CORS headers are set in .htaccess to avoid duplication

// OPTIONSリクエスト対応 (.htaccessで処理されるが念のため残す)
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
require_once('../../_etc/require_files.php');
require_once(__DIR__ . '/helpers/user_helper.php');

// 認証ヘッダー確認（複数ソース対応）
$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

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
$partnerUserId = $input['userId'] ?? null; // パートナー側のユーザーID（オプション）

try {
    $pdo = get_db_connection();

    // 環境判定（JWTから取得または直接APIキーから判定）
    $environment = 'test'; // デフォルトはtest
    $apiKeyId = null;
    $userId = null;

    // JWT からapi_key_idを取得して環境判定
    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $jwt = substr($authHeader, 7);
        $parts = explode('.', $jwt);
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
                $envStmt = $pdo->prepare("SELECT environment FROM api_keys WHERE id = :id");
                $envStmt->execute(['id' => $apiKeyId]);
                $envData = $envStmt->fetch(PDO::FETCH_ASSOC);
                if ($envData) {
                    $environment = $envData['environment'];
                }
            }
        }
    }

    // ユーザー管理（userIdが提供された場合）
    $userBalance = null;
    $pointsConsumed = 0;
    $gamePrice = 100; // デフォルトのゲーム価格（ポイント）

    if ($partnerUserId && $apiKeyId) {
        // ユーザーを取得または作成
        $user = getOrCreateUser($pdo, $apiKeyId, $partnerUserId);
        $userId = $user['id'];

        // 残高チェック
        $userBalance = getUserBalance($pdo, $userId);

        if (!$userBalance) {
            http_response_code(500);
            echo json_encode([
                'error' => 'BALANCE_NOT_FOUND',
                'message' => 'User balance not found'
            ]);
            exit;
        }

        // 残高不足チェック
        if ($userBalance['balance'] < $gamePrice) {
            http_response_code(402);
            echo json_encode([
                'error' => 'INSUFFICIENT_BALANCE',
                'message' => 'Insufficient points',
                'balance' => $userBalance['balance'],
                'required' => $gamePrice
            ]);
            exit;
        }
    }

    // 1. 機種情報を取得
    $modelSql = "SELECT model_no, model_cd, model_name, category
                 FROM mst_model
                 WHERE model_cd = :model_id
                 AND del_flg = 0";

    $stmt = $pdo->prepare($modelSql);
    $stmt->execute(['model_id' => $modelId]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        http_response_code(404);
        echo json_encode([
            'error' => 'MODEL_NOT_FOUND',
            'message' => 'Model not found'
        ]);
        exit;
    }

    // 2. 利用可能なマシンを検索（環境別処理）
    $machine = null;

    if ($environment === 'test' || $environment === 'staging') {
        // テスト/ステージング環境：モックマシンを生成
        $machine = [
            'machine_no' => 9999,  // モックマシン番号
            'signaling_id' => 'mock_sig_' . substr(md5($modelId), 0, 8),
            'camera_no' => null,
            'machine_status' => 0
        ];
    } else {
        // 本番環境：実機を検索
        $machineSql = "SELECT
                        m.machine_no,
                        m.signaling_id,
                        m.camera_no,
                        m.machine_status
                    FROM dat_machine m
                    WHERE m.model_no = :model_no
                    AND m.del_flg = 0
                    AND m.machine_status = 0
                    AND m.end_date >= CURDATE()
                    AND NOT EXISTS (
                        SELECT 1 FROM lnk_machine lm
                        WHERE lm.machine_no = m.machine_no
                        AND lm.assign_flg = 1
                    )
                    LIMIT 1";

        $stmt = $pdo->prepare($machineSql);
        $stmt->execute(['model_no' => $model['model_no']]);
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine) {
            http_response_code(503);
            echo json_encode([
                'error' => 'NO_AVAILABLE_MACHINE',
                'message' => 'No available machine for this model',
                'environment' => $environment
            ]);
            exit;
        }
    }

    // 3. ゲームセッションIDを生成
    $sessionId = 'gs_' . uniqid() . '_' . time();

    // 4. ゲームセッションをDBに記録
    if ($userId) {
        // ポイント消費
        try {
            $transaction = consumePoints($pdo, $userId, $gamePrice, $sessionId);
            $pointsConsumed = $transaction['amount'];
            $userBalance = getUserBalance($pdo, $userId); // 最新残高を取得
        } catch (Exception $e) {
            http_response_code(402);
            echo json_encode([
                'error' => 'PAYMENT_FAILED',
                'message' => $e->getMessage()
            ]);
            exit;
        }

        // ゲームセッション記録
        $stmt = $pdo->prepare("
            INSERT INTO game_sessions
            (session_id, user_id, api_key_id, machine_no, model_cd, model_name, points_consumed, status, ip_address, user_agent)
            VALUES
            (:session_id, :user_id, :api_key_id, :machine_no, :model_cd, :model_name, :points_consumed, 'playing', :ip, :user_agent)
        ");

        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId,
            'api_key_id' => $apiKeyId,
            'machine_no' => $machine['machine_no'],
            'model_cd' => $model['model_cd'],
            'model_name' => $model['model_name'],
            'points_consumed' => $pointsConsumed,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    // 4. WebRTC Signaling情報（環境別）
    if ($environment === 'test' || $environment === 'staging') {
        // モック環境：テスト用シグナリング情報
        $signalingInfo = [
            'signalingId' => $machine['signaling_id'],
            'host' => 'mock-signaling.net8.test',
            'port' => 443,
            'secure' => true,
            'path' => '/socket.io',
            'iceServers' => [
                ['urls' => 'stun:stun.l.google.com:19302']
            ],
            'mock' => true
        ];
    } else {
        // 本番環境：実際のシグナリング情報
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
    }

    // 5. カメラ情報（WebRTC用・環境別）
    $cameraInfo = null;

    if ($environment === 'test' || $environment === 'staging') {
        // モック環境：テスト用カメラ情報
        $cameraInfo = [
            'cameraNo' => 9999,
            'streamUrl' => 'mock://camera.net8.test/stream/' . $modelId,
            'mock' => true
        ];
    } else {
        // 本番環境：実際のカメラ情報
        if ($machine['camera_no']) {
            $cameraSql = "SELECT camera_url FROM mst_camera
                          WHERE camera_no = :camera_no";
            $stmt = $pdo->prepare($cameraSql);
            $stmt->execute(['camera_no' => $machine['camera_no']]);
            $camera = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($camera) {
                $cameraInfo = [
                    'cameraNo' => $machine['camera_no'],
                    'streamUrl' => $camera['camera_url']
                ];
            }
        }
    }

    // 成功レスポンス（環境情報を追加）
    $response = [
        'success' => true,
        'environment' => $environment,
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
        'playUrl' => "/data/play_v2/index.php?NO={$machine['machine_no']}",
        'mock' => ($environment === 'test' || $environment === 'staging')
    ];

    // ポイント情報を追加（userIdが提供された場合）
    if ($userId && $userBalance) {
        $response['points'] = [
            'consumed' => $pointsConsumed,
            'balance' => $userBalance['balance'],
            'balanceBefore' => $userBalance['balance'] + $pointsConsumed
        ];
        $response['pointsConsumed'] = $pointsConsumed; // SDK互換性のため
    }

    http_response_code(200);
    echo json_encode($response);

} catch (Exception $e) {
    error_log('Game Start API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to start game: ' . $e->getMessage()
    ]);
}
