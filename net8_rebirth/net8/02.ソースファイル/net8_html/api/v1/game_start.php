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

    // トランザクション状態をクリア（接続直後に確認）
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // 環境判定（JWTまたは直接APIキーから判定）
    $environment = 'test'; // デフォルトはtest
    $apiKeyId = null;
    $userId = null;

    // Authorizationヘッダーからトークン取得
    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        // JWT形式の場合（3パート: header.payload.signature）
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
                $envStmt = $pdo->prepare("SELECT environment FROM api_keys WHERE id = :id AND is_active = 1");
                $envStmt->execute(['id' => $apiKeyId]);
                $envData = $envStmt->fetch(PDO::FETCH_ASSOC);
                if ($envData) {
                    $environment = $envData['environment'];
                }
            }
        } else {
            // 直接APIキーの場合（pk_demo_12345など）
            $apiKeyStmt = $pdo->prepare("SELECT id, environment FROM api_keys WHERE key_value = :key_value AND is_active = 1");
            $apiKeyStmt->execute(['key_value' => $token]);
            $apiKeyData = $apiKeyStmt->fetch(PDO::FETCH_ASSOC);

            if ($apiKeyData) {
                $apiKeyId = $apiKeyData['id'];
                $environment = $apiKeyData['environment'];
            } else {
                http_response_code(401);
                echo json_encode([
                    'error' => 'INVALID_API_KEY',
                    'message' => 'Invalid API key'
                ]);
                exit;
            }
        }
    }

    // ユーザー管理（userIdが提供された場合）
    $userBalance = null;
    $pointsConsumed = 0;
    $gamePrice = 100; // デフォルトのゲーム価格（ポイント）
    $memberNo = null; // NET8側のユーザーID（mst_member.member_no）

    if ($partnerUserId && $apiKeyId) {
        // ユーザーを取得または作成（mst_memberと紐づけ）
        $user = getOrCreateUser($pdo, $apiKeyId, $partnerUserId);
        $userId = $user['id'];
        $memberNo = $user['member_no']; // mst_member.member_noを取得

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
    $onetimeId = 'ot_' . uniqid();

    // トランザクション開始（既存のトランザクションをクリア）
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $pdo->beginTransaction();

    try {
        // 4. マシンを割り当て（本番環境のみ）
        if ($environment === 'production') {
            // lnk_machineに登録（既存システムとの統合）
            $stmt = $pdo->prepare("
                INSERT INTO lnk_machine (machine_no, member_no, onetime_id, assign_flg, start_dt)
                VALUES (:machine_no, :member_no, :onetime_id, 1, NOW())
                ON DUPLICATE KEY UPDATE
                    member_no = :member_no,
                    onetime_id = :onetime_id,
                    assign_flg = 1,
                    start_dt = NOW()
            ");

            $memberNo = 0; // SDK経由の場合は仮のmember_no
            if ($userId) {
                // SDKユーザーに対応する仮想member_noを取得または作成
                $sdkUserStmt = $pdo->prepare("
                    SELECT su.*, ak.partner_name
                    FROM sdk_users su
                    JOIN api_keys ak ON su.api_key_id = ak.id
                    WHERE su.id = :user_id
                ");
                $sdkUserStmt->execute(['user_id' => $userId]);
                $sdkUser = $sdkUserStmt->fetch(PDO::FETCH_ASSOC);

                if ($sdkUser) {
                    $virtualEmail = 'sdk_' . $sdkUser['partner_user_id'] . '@' . $sdkUser['partner_name'] . '.net8.local';

                    $memberStmt = $pdo->prepare("SELECT member_no FROM mst_member WHERE mail = :mail");
                    $memberStmt->execute(['mail' => $virtualEmail]);
                    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$member) {
                        // 仮想メンバーを作成
                        $createMemberStmt = $pdo->prepare("
                            INSERT INTO mst_member (mail, nickname, invite_cd, point, draw_point, member_flg, del_flg)
                            VALUES (:mail, :nickname, :invite_cd, 0, 0, 1, 0)
                        ");
                        $createMemberStmt->execute([
                            'mail' => $virtualEmail,
                            'nickname' => $sdkUser['username'] ?? 'SDK User',
                            'invite_cd' => 'SDK_' . $sdkUser['partner_user_id']
                        ]);
                        $memberNo = $pdo->lastInsertId();
                    } else {
                        $memberNo = $member['member_no'];
                    }
                }
            }

            $stmt->execute([
                'machine_no' => $machine['machine_no'],
                'member_no' => $memberNo,
                'onetime_id' => $onetimeId
            ]);
        }

        // 5. ポイント消費（userIdがある場合のみ）
        if ($userId) {
            // ポイント消費
            $transaction = consumePoints($pdo, $userId, $gamePrice, $sessionId);
            $pointsConsumed = $transaction['amount'];
            $userBalance = getUserBalance($pdo, $userId); // 最新残高を取得
        }

        // 6. ゲームセッションをDBに記録（userIdの有無に関わらず）
        $stmt = $pdo->prepare("
            INSERT INTO game_sessions
            (session_id, user_id, api_key_id, member_no, partner_user_id, machine_no, model_cd, model_name, points_consumed, status, ip_address, user_agent)
            VALUES
            (:session_id, :user_id, :api_key_id, :member_no, :partner_user_id, :machine_no, :model_cd, :model_name, :points_consumed, 'playing', :ip, :user_agent)
        ");

        $stmt->execute([
            'session_id' => $sessionId,
            'user_id' => $userId, // NULLでも記録
            'api_key_id' => $apiKeyId,
            'member_no' => $memberNo, // NET8側のユーザーID
            'partner_user_id' => $partnerUserId, // パートナー側のユーザーID
            'machine_no' => $machine['machine_no'],
            'model_cd' => $model['model_cd'],
            'model_name' => $model['model_name'],
            'points_consumed' => $pointsConsumed,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);

        error_log("✅ Game session created: session_id={$sessionId}, member_no={$memberNo}, partner_user_id={$partnerUserId}");

        // トランザクションコミット
        $pdo->commit();

    } catch (Exception $e) {
        // トランザクションロールバック
        $pdo->rollBack();
        error_log('Game Start Transaction Error: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'error' => 'GAME_START_FAILED',
            'message' => 'Failed to start game: ' . $e->getMessage()
        ]);
        exit;
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
