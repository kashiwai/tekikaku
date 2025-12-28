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
$requestedMachineNo = $input['machineNo'] ?? null; // 直接台番号を指定（オプション）
$initialPoints = isset($input['initialPoints']) ? (int)$input['initialPoints'] : 0; // 韓国側からのポイント
$balanceMode = $input['balanceMode'] ?? 'add'; // Case 1: 'add' or 'set'（デフォルト: 'add'）
$consumeImmediately = isset($input['consumeImmediately']) ? (bool)$input['consumeImmediately'] : true; // Case 6: デフォルト true
$lang = $input['lang'] ?? 'ja'; // 多言語対応: ja/ko/en/zh（デフォルト: ja）

try {
    $pdo = get_db_connection();

    // トランザクション状態をクリア（接続直後に確認）
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    // SDK v1.1.0: SDKテーブルの自動作成・修正
    error_log("🔧 Starting SDK tables auto-migration...");
    try {
        // 1. api_keysテーブル
        error_log("Creating api_keys table...");
        $pdo->exec("CREATE TABLE IF NOT EXISTS `api_keys` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(10) UNSIGNED NULL,
          `key_value` VARCHAR(100) NOT NULL UNIQUE,
          `key_type` VARCHAR(20) NOT NULL DEFAULT 'public',
          `name` VARCHAR(100) NULL,
          `environment` VARCHAR(20) NOT NULL DEFAULT 'test',
          `rate_limit` INT(10) UNSIGNED NOT NULL DEFAULT 1000,
          `is_active` TINYINT(4) NOT NULL DEFAULT 1,
          `last_used_at` DATETIME NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `expires_at` DATETIME NULL,
          PRIMARY KEY (`id`),
          KEY `idx_key_value` (`key_value`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 2. sdk_usersテーブル
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sdk_users` (
          `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
          `partner_user_id` VARCHAR(255) NOT NULL,
          `api_key_id` INT(10) UNSIGNED NOT NULL,
          `member_no` INT(10) UNSIGNED NULL,
          `email` VARCHAR(255) NULL,
          `username` VARCHAR(255) NULL,
          `metadata` TEXT NULL,
          `is_active` TINYINT(4) NOT NULL DEFAULT 1,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `idx_partner_user_api` (`partner_user_id`, `api_key_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 3. user_balancesテーブル
        $pdo->exec("CREATE TABLE IF NOT EXISTS `user_balances` (
          `user_id` INT(10) UNSIGNED NOT NULL,
          `balance` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_deposited` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_consumed` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `total_won` INT(10) UNSIGNED NOT NULL DEFAULT 0,
          `last_transaction_at` DATETIME NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 4. point_transactionsテーブル
        $pdo->exec("CREATE TABLE IF NOT EXISTS `point_transactions` (
          `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
          `user_id` INT(10) UNSIGNED NOT NULL,
          `transaction_id` VARCHAR(100) NOT NULL UNIQUE,
          `type` VARCHAR(20) NOT NULL,
          `amount` INT(11) NOT NULL,
          `balance_before` INT(10) UNSIGNED NOT NULL,
          `balance_after` INT(10) UNSIGNED NOT NULL,
          `game_session_id` VARCHAR(100) NULL,
          `description` VARCHAR(512) NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");

        // 5. game_sessionsテーブルのカラム追加
        error_log("Checking game_sessions columns...");
        $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'partner_user_id'");
        if ($stmt->rowCount() === 0) {
            error_log("Adding partner_user_id column to game_sessions...");
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN partner_user_id VARCHAR(255) NULL");
            error_log("✅ partner_user_id column added");
        } else {
            error_log("ℹ️  partner_user_id column already exists");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'member_no'");
        if ($stmt->rowCount() === 0) {
            error_log("Adding member_no column to game_sessions...");
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN member_no INT(10) UNSIGNED NULL");
            error_log("✅ member_no column added");
        } else {
            error_log("ℹ️  member_no column already exists");
        }

        // Case 6対応: reserved_pointsカラム追加
        $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'reserved_points'");
        if ($stmt->rowCount() === 0) {
            error_log("Adding reserved_points column to game_sessions...");
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN reserved_points INT(10) UNSIGNED DEFAULT 0");
            error_log("✅ reserved_points column added");
        } else {
            error_log("ℹ️  reserved_points column already exists");
        }

        // Case 1対応: balance_modeカラム追加
        $stmt = $pdo->query("SHOW COLUMNS FROM game_sessions LIKE 'balance_mode'");
        if ($stmt->rowCount() === 0) {
            error_log("Adding balance_mode column to game_sessions...");
            $pdo->exec("ALTER TABLE game_sessions ADD COLUMN balance_mode VARCHAR(10) DEFAULT 'add'");
            error_log("✅ balance_mode column added");
        } else {
            error_log("ℹ️  balance_mode column already exists");
        }

        error_log("✅ SDK tables auto-migration completed successfully");
    } catch (PDOException $e) {
        error_log("❌ Table migration FAILED: " . $e->getMessage());
        error_log("❌ SQL State: " . $e->getCode());
        error_log("❌ Stack trace: " . $e->getTraceAsString());
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

        // 韓国側からのポイント処理（Case 1: balanceMode対応）
        if ($initialPoints > 0) {
            if ($balanceMode === 'set') {
                // setモード: 既存残高を無視して新しい値を設定
                error_log("💰 Setting balance (set mode): user_id={$userId}, amount={$initialPoints}");
                $stmt = $pdo->prepare("
                    INSERT INTO user_balances (user_id, balance, created_at, updated_at)
                    VALUES (?, ?, NOW(), NOW())
                    ON DUPLICATE KEY UPDATE balance = ?, updated_at = NOW()
                ");
                $stmt->execute([$userId, $initialPoints, $initialPoints]);

                // mst_member.point も同期
                $stmt = $pdo->prepare("UPDATE mst_member SET point = ? WHERE member_no = ?");
                $stmt->execute([$initialPoints, $memberNo]);

                error_log("✅ Balance set to {$initialPoints} (set mode)");
            } else {
                // addモード: 既存残高に加算（従来の動作）
                error_log("💰 Depositing Korea points (add mode): user_id={$userId}, amount={$initialPoints}");
                $depositResult = depositPoints($pdo, $userId, $initialPoints, 'Korea initial points deposit');
                // depositPointsでmember_noが作成/更新された場合、それを使用
                if ($depositResult['member_no']) {
                    $memberNo = $depositResult['member_no'];
                    error_log("✅ Using member_no from deposit: {$memberNo}");
                }
            }
        }

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

        // 残高不足チェック（Case 6: consumeImmediately=trueの場合のみ）
        if ($consumeImmediately && $userBalance['balance'] < $gamePrice) {
            http_response_code(402);
            echo json_encode([
                'error' => 'INSUFFICIENT_BALANCE',
                'message' => 'Insufficient points',
                'balance' => $userBalance['balance'],
                'required' => $gamePrice,
                'initialPointsReceived' => $initialPoints,
                'balanceMode' => $balanceMode
            ]);
            exit;
        }
    }

    // 1. 機種情報を取得（多言語対応）
    // 言語に応じた機種名カラムを決定（PHP 7.4互換）
    if ($lang === 'ko') {
        $modelNameColumn = 'COALESCE(model_name_ko, model_name_ja, model_name)';
    } elseif ($lang === 'en') {
        $modelNameColumn = 'COALESCE(model_name_en, model_name_ja, model_name)';
    } elseif ($lang === 'zh') {
        $modelNameColumn = 'COALESCE(model_name_zh, model_name_ja, model_name)';
    } else {
        $modelNameColumn = 'COALESCE(model_name_ja, model_name)';
    }

    if ($environment === 'test' || $environment === 'staging') {
        // テスト/ステージング環境：モックモデルを使用
        $model = [
            'model_no' => 9999,
            'model_cd' => $modelId,
            'model_name' => 'Test Model - ' . $modelId,
            'category' => 1 // 1=パチンコ, 2=スロット
        ];
        error_log("✅ Test environment: Using mock model for modelId={$modelId}");
    } else {
        // 本番環境：実際のモデルをデータベースから取得（多言語対応）
        $modelSql = "SELECT
                        model_no,
                        model_cd,
                        {$modelNameColumn} as model_name,
                        category
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

        error_log("🌐 Model loaded in {$lang}: {$model['model_name']}");
    }

    // 2. 利用可能なマシンを検索（machineNo指定優先）
    $machine = null;

    if ($requestedMachineNo) {
        // 台番号が直接指定された場合：環境に関係なくその台を使用（最優先）
        error_log("📌 Using requested machine_no: {$requestedMachineNo} (overrides environment: {$environment})");
        $machineSql = "SELECT
                        m.machine_no,
                        m.signaling_id,
                        m.camera_no,
                        m.machine_status
                    FROM dat_machine m
                    WHERE m.machine_no = :machine_no
                    AND m.del_flg = 0
                    LIMIT 1";

        $stmt = $pdo->prepare($machineSql);
        $stmt->execute(['machine_no' => $requestedMachineNo]);
        $machine = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$machine) {
            http_response_code(404);
            echo json_encode([
                'error' => 'MACHINE_NOT_FOUND',
                'message' => 'Specified machine not found',
                'machineNo' => $requestedMachineNo
            ]);
            exit;
        }
        // machineNoが指定された場合は本番モードとして扱う
        $environment = 'production';
    } else if ($environment === 'test' || $environment === 'staging') {
        // テスト/ステージング環境（machineNo未指定時）：モックマシンを生成
        $machine = [
            'machine_no' => 9999,  // モックマシン番号
            'signaling_id' => 'mock_sig_' . substr(md5($modelId), 0, 8),
            'camera_no' => null,
            'machine_status' => 0
        ];
    } else {
        // 本番環境：実機を検索（modelIdベース）
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

            // memberNoが未設定の場合のみ検索（getOrCreateUserで既に取得済みの場合はそれを使用）
            if (!$memberNo && $userId) {
                // SDKユーザーに対応する仮想member_noを取得または作成
                $sdkUserStmt = $pdo->prepare("
                    SELECT su.*, ak.name as partner_name
                    FROM sdk_users su
                    JOIN api_keys ak ON su.api_key_id = ak.id
                    WHERE su.id = :user_id
                ");
                $sdkUserStmt->execute(['user_id' => $userId]);
                $sdkUser = $sdkUserStmt->fetch(PDO::FETCH_ASSOC);

                if ($sdkUser) {
                    $partnerName = preg_replace('/[^a-zA-Z0-9]/', '', $sdkUser['partner_name'] ?? 'SDK');
                    $virtualEmail = 'sdk_' . $sdkUser['partner_user_id'] . '@' . $partnerName . '.net8.local';

                    $memberStmt = $pdo->prepare("SELECT member_no FROM mst_member WHERE mail = :mail");
                    $memberStmt->execute(['mail' => $virtualEmail]);
                    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$member) {
                        // 仮想メンバーを作成（user_helper.phpと同じ形式）
                        $shortUserId = substr($sdkUser['partner_user_id'], -6);
                        $nickname = 'SDK' . $shortUserId;
                        $password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
                        $now = date('Y-m-d H:i:s');

                        $createMemberStmt = $pdo->prepare("
                            INSERT INTO mst_member (
                                nickname,
                                mail,
                                pass,
                                point,
                                draw_point,
                                mail_magazine,
                                tester_flg,
                                agent_flg,
                                black_flg,
                                state,
                                regist_dt,
                                join_dt,
                                add_dt
                            ) VALUES (
                                :nickname,
                                :mail,
                                :pass,
                                0,
                                0,
                                0,
                                0,
                                0,
                                0,
                                1,
                                :now,
                                :now,
                                :now
                            )
                        ");
                        $createMemberStmt->execute([
                            'nickname' => $nickname,
                            'mail' => $virtualEmail,
                            'pass' => $password,
                            'now' => $now
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

        // 5. ポイント消費（Case 6: consumeImmediately対応）
        $reservedPoints = 0;
        if ($userId) {
            if ($consumeImmediately) {
                // 即座にポイント消費（従来の動作）
                $transaction = consumePoints($pdo, $userId, $gamePrice, $sessionId);
                $pointsConsumed = $transaction['amount'];
                $userBalance = getUserBalance($pdo, $userId); // 最新残高を取得
                error_log("✅ Points consumed immediately: {$pointsConsumed}");
            } else {
                // ポイント消費を後で行う（reserved_pointsに記録）
                $reservedPoints = $gamePrice;
                $pointsConsumed = 0;
                error_log("⏸️ Points reserved for later: {$reservedPoints}");
            }
        }

        // 6. ゲームセッションをDBに記録（userIdの有無に関わらず）
        $stmt = $pdo->prepare("
            INSERT INTO game_sessions
            (session_id, user_id, api_key_id, member_no, partner_user_id, machine_no, model_cd, model_name, points_consumed, reserved_points, balance_mode, status, ip_address, user_agent)
            VALUES
            (:session_id, :user_id, :api_key_id, :member_no, :partner_user_id, :machine_no, :model_cd, :model_name, :points_consumed, :reserved_points, :balance_mode, 'playing', :ip, :user_agent)
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
            'reserved_points' => $reservedPoints,
            'balance_mode' => $balanceMode,
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
        // 本番環境：実際のカメラ情報（camera_name = PeerID）
        if ($machine['camera_no']) {
            $cameraSql = "SELECT camera_no, camera_name, camera_mac FROM mst_camera
                          WHERE camera_no = :camera_no AND del_flg = 0";
            $stmt = $pdo->prepare($cameraSql);
            $stmt->execute(['camera_no' => $machine['camera_no']]);
            $camera = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($camera) {
                $cameraInfo = [
                    'cameraNo' => $machine['camera_no'],
                    'peerId' => $camera['camera_name'],      // WebRTC PeerID（カメラのPeer名）
                    'cameraName' => $camera['camera_name'],  // 互換性のため両方
                    'cameraMac' => $camera['camera_mac']     // MACアドレス
                ];
                error_log("✅ Camera info: camera_no={$machine['camera_no']}, peerId={$camera['camera_name']}, mac={$camera['camera_mac']}");
            }
        }
    }

    // 成功レスポンス（環境情報を追加）
    $response = [
        'success' => true,
        'environment' => $environment,
        'sessionId' => $sessionId,
        'machineNo' => $machine['machine_no'],
        'memberNo' => $memberNo, // play_embed用のmember_no
        'signalingId' => $machine['signaling_id'],
        'model' => [
            'id' => $model['model_cd'],
            'name' => $model['model_name'],
            'category' => $model['category'] == 1 ? 'pachinko' : 'slot'
        ],
        'signaling' => $signalingInfo,
        'camera' => $cameraInfo,
        'playUrl' => "/data/play_v2/index.php?NO={$machine['machine_no']}",
        'playEmbedUrl' => "/play_embed/?session_id={$sessionId}&member_no={$memberNo}", // iframe埋め込み用URL
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
