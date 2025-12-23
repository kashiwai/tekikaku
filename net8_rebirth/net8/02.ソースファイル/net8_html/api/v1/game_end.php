<?php
/**
 * NET8 SDK API - Game End Endpoint
 * Version: 1.0.0
 * Created: 2025-11-18
 */

header('Content-Type: application/json');

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
require_once('../../_etc/require_files.php');
require_once(__DIR__ . '/helpers/user_helper.php');

// 認証ヘッダー確認
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

if (!isset($input['sessionId'])) {
    http_response_code(400);
    echo json_encode([
        'error' => 'MISSING_SESSION_ID',
        'message' => 'Session ID is required'
    ]);
    exit;
}

$sessionId = $input['sessionId'];
$result = $input['result'] ?? 'completed'; // win, lose, draw, error, cancelled
$pointsWon = isset($input['pointsWon']) ? (int)$input['pointsWon'] : 0;
$resultData = $input['resultData'] ?? [];

// リクエストボディからmember_noを読み取る（両方のキー名をサポート）
$requestMemberNo = null;
if (isset($input['member_no'])) {
    $requestMemberNo = intval($input['member_no']);
    error_log("📝 Received member_no from request body: {$requestMemberNo}");
} elseif (isset($input['memberNo'])) {
    $requestMemberNo = intval($input['memberNo']);
    error_log("📝 Received memberNo from request body (camelCase): {$requestMemberNo}");
}

// デバッグ用: リクエストボディ全体をログ出力
error_log("📋 Raw request input: " . json_encode($input));

// resultDataが配列でない場合は空配列に
if (!is_array($resultData)) {
    $resultData = [];
}

try {
    $pdo = get_db_connection();

    // APIキー認証（JWTまたは直接APIキー）
    $apiKeyId = null;

    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        // JWT形式の場合（3パート: header.payload.signature）
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
            }
        } else {
            // 直接APIキーの場合（pk_demo_12345など）
            $apiKeyStmt = $pdo->prepare("SELECT id FROM api_keys WHERE key_value = :key_value AND is_active = 1");
            $apiKeyStmt->execute(['key_value' => $token]);
            $apiKeyData = $apiKeyStmt->fetch(PDO::FETCH_ASSOC);

            if ($apiKeyData) {
                $apiKeyId = $apiKeyData['id'];
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

    // ゲームセッション情報を取得（member_noとpartner_user_idを含む）
    $stmt = $pdo->prepare("
        SELECT
            id,
            session_id,
            user_id,
            api_key_id,
            member_no,
            partner_user_id,
            machine_no,
            model_cd,
            points_consumed,
            status,
            started_at
        FROM game_sessions
        WHERE session_id = :session_id
    ");

    $stmt->execute(['session_id' => $sessionId]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    error_log("📊 Game ending: session_id={$sessionId}, member_no={$session['member_no']}, partner_user_id={$session['partner_user_id']}, result={$result}");

    if (!$session) {
        http_response_code(404);
        echo json_encode([
            'error' => 'SESSION_NOT_FOUND',
            'message' => 'Game session not found'
        ]);
        exit;
    }

    // APIキーIDの検証（セッションのAPIキーと一致するか）
    if ($apiKeyId && $session['api_key_id'] != $apiKeyId) {
        http_response_code(403);
        echo json_encode([
            'error' => 'API_KEY_MISMATCH',
            'message' => 'API key does not match the session'
        ]);
        exit;
    }

    // 既に終了している場合
    if ($session['status'] === 'completed' || $session['status'] === 'cancelled') {
        http_response_code(409);
        echo json_encode([
            'error' => 'SESSION_ALREADY_ENDED',
            'message' => 'Game session already ended',
            'status' => $session['status']
        ]);
        exit;
    }

    // トランザクション開始（データ整合性確保）
    $pdo->beginTransaction();

    try {
        // プレイ時間を計算
        $startedAt = new DateTime($session['started_at']);
        $endedAt = new DateTime();
        $playDuration = $endedAt->getTimestamp() - $startedAt->getTimestamp();

        // ゲームセッションを更新
        $stmt = $pdo->prepare("
            UPDATE game_sessions
            SET
                ended_at = NOW(),
                status = :status,
                result = :result,
                points_won = :points_won,
                play_duration = :play_duration,
                result_data = :result_data
            WHERE session_id = :session_id
        ");

        $status = ($result === 'error' || $result === 'cancelled') ? $result : 'completed';

        $stmt->execute([
            'status' => $status,
            'result' => $result,
            'points_won' => $pointsWon,
            'play_duration' => $playDuration,
            'result_data' => $resultData ? json_encode($resultData) : null,
            'session_id' => $sessionId
        ]);

        // ポイント払い出し（勝利時・精算時）
        $newBalance = null;
        $transaction = null;

        // リクエストボディのmember_noを優先、なければセッションから取得
        $memberNo = $requestMemberNo ?? $session['member_no'];
        error_log("🔍 Using member_no: {$memberNo} (from " . ($requestMemberNo ? "request body" : "game_sessions") . ")");

        // member_noがNULLの場合、sdk_usersから取得
        if (!$memberNo && $session['user_id']) {
            $sdkUserStmt = $pdo->prepare("SELECT member_no, api_key_id, partner_user_id FROM sdk_users WHERE id = :user_id");
            $sdkUserStmt->execute(['user_id' => $session['user_id']]);
            $sdkUser = $sdkUserStmt->fetch(PDO::FETCH_ASSOC);
            if ($sdkUser && $sdkUser['member_no']) {
                $memberNo = $sdkUser['member_no'];
                error_log("✅ Retrieved member_no from sdk_users: {$memberNo}");
            } elseif ($sdkUser) {
                // member_noがNULLの場合、作成
                $mstMember = getOrCreateMstMember($pdo, $sdkUser['api_key_id'], $sdkUser['partner_user_id']);
                $memberNo = $mstMember['member_no'];
                // sdk_usersも更新
                $updateStmt = $pdo->prepare("UPDATE sdk_users SET member_no = :member_no WHERE id = :user_id");
                $updateStmt->execute(['member_no' => $memberNo, 'user_id' => $session['user_id']]);
                error_log("✅ Created and updated member_no: {$memberNo}");
            }
        }

        if ($session['user_id'] && $pointsWon > 0) {
            // 残高を取得（FOR UPDATE でロック）
            $stmt = $pdo->prepare("
                SELECT balance FROM user_balances WHERE user_id = :user_id FOR UPDATE
            ");
            $stmt->execute(['user_id' => $session['user_id']]);
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$balance) {
                throw new Exception('User balance not found');
            }

            $balanceBefore = $balance['balance'];
            $balanceAfter = $balanceBefore + $pointsWon;

            // 残高を更新（user_balances）
            $stmt = $pdo->prepare("
                UPDATE user_balances
                SET balance = :balance,
                    total_won = total_won + :amount,
                    last_transaction_at = NOW()
                WHERE user_id = :user_id
            ");
            $stmt->execute([
                'balance' => $balanceAfter,
                'amount' => $pointsWon,
                'user_id' => $session['user_id']
            ]);

            // ★ mst_member.point にもポイントを追加（精算時にカメラ側と同期するため）
            if ($memberNo) {
                $stmt = $pdo->prepare("
                    UPDATE mst_member
                    SET point = point + :amount
                    WHERE member_no = :member_no
                ");
                $stmt->execute([
                    'amount' => $pointsWon,
                    'member_no' => $memberNo
                ]);
                error_log("💰 Game end payout to mst_member: member_no={$memberNo}, amount={$pointsWon}");
            }

            // 取引履歴を記録
            $transactionId = 'txn_' . uniqid() . '_' . time();
            $stmt = $pdo->prepare("
                INSERT INTO point_transactions
                (user_id, transaction_id, type, amount, balance_before, balance_after, game_session_id, description)
                VALUES
                (:user_id, :transaction_id, 'payout', :amount, :balance_before, :balance_after, :game_session_id, 'Game win payout')
            ");
            $stmt->execute([
                'user_id' => $session['user_id'],
                'transaction_id' => $transactionId,
                'amount' => $pointsWon,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'game_session_id' => $sessionId
            ]);

            $transaction = [
                'transaction_id' => $transactionId,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'amount' => $pointsWon
            ];

            $newBalance = $balanceAfter;

        } else if ($session['user_id']) {
            // 残高取得のみ
            $userBalance = getUserBalance($pdo, $session['user_id']);
            $newBalance = $userBalance['balance'] ?? null;
        }

        // 既存システムとの統合: his_play, dat_machinePlay, lnk_machine更新
        // SDKユーザーに対応するmst_memberレコードを取得または作成
        $sdkUserStmt = $pdo->prepare("
            SELECT su.*, ak.name as partner_name
            FROM sdk_users su
            JOIN api_keys ak ON su.api_key_id = ak.id
            WHERE su.id = :user_id
        ");
        $sdkUserStmt->execute(['user_id' => $session['user_id']]);
        $sdkUser = $sdkUserStmt->fetch(PDO::FETCH_ASSOC);

        if ($sdkUser) {
            // SDKユーザー用の仮想member_noを生成または取得
            $partnerName = preg_replace('/[^a-zA-Z0-9]/', '', $sdkUser['partner_name'] ?? 'SDK');
            $virtualEmail = 'sdk_' . $sdkUser['partner_user_id'] . '@' . $partnerName . '.net8.local';

            $memberStmt = $pdo->prepare("
                SELECT member_no FROM mst_member WHERE mail = :mail
            ");
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

            // 機種情報を取得
            $modelStmt = $pdo->prepare("
                SELECT dm.owner_no, dm.convert_no, mc.point, mc.credit, mc.draw_point, mm.model_name, mm.category
                FROM dat_machine dm
                LEFT JOIN mst_convertPoint mc ON dm.convert_no = mc.convert_no
                LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
                WHERE dm.machine_no = :machine_no
            ");
            $modelStmt->execute(['machine_no' => $session['machine_no']]);
            $machineData = $modelStmt->fetch(PDO::FETCH_ASSOC);

            if ($machineData) {
                // draw_pointを計算（既存システムのロジックを踏襲）
                $credit = $resultData['credit'] ?? 0;
                $drawPointCalculated = 0;

                if ($credit > 0 && $machineData['credit'] > 0 && $machineData['draw_point'] > 0) {
                    $drawPointCalculated = floor($credit / $machineData['credit']) * $machineData['draw_point'];
                }

                // his_play に記録
                $insertPlayStmt = $pdo->prepare("
                    INSERT INTO his_play (
                        machine_no, start_dt, end_dt, member_no, owner_no, convert_no,
                        point, credit, draw_point, in_point, out_point, in_credit, out_credit,
                        out_draw_point, play_count, bb_count, rb_count, out_action_type
                    ) VALUES (
                        :machine_no, :start_dt, :end_dt, :member_no, :owner_no, :convert_no,
                        :point, :credit, :draw_point, :in_point, :out_point, :in_credit, :out_credit,
                        :out_draw_point, :play_count, :bb_count, :rb_count, 'sdk_end'
                    )
                ");

                $insertPlayStmt->execute([
                    'machine_no' => $session['machine_no'],
                    'start_dt' => $session['started_at'],
                    'end_dt' => date('Y-m-d H:i:s'),
                    'member_no' => $memberNo,
                    'owner_no' => $machineData['owner_no'] ?? 0,
                    'convert_no' => $machineData['convert_no'] ?? 0,
                    'point' => $machineData['point'] ?? 0,
                    'credit' => $machineData['credit'] ?? 0,
                    'draw_point' => $machineData['draw_point'] ?? 0,
                    'in_point' => $session['points_consumed'],
                    'out_point' => $pointsWon,
                    'in_credit' => $resultData['in_credit'] ?? 0,
                    'out_credit' => $credit,
                    'out_draw_point' => $drawPointCalculated,
                    'play_count' => $resultData['play_count'] ?? 0,
                    'bb_count' => $resultData['bb_count'] ?? 0,
                    'rb_count' => $resultData['rb_count'] ?? 0
                ]);

                // dat_machinePlay を更新（機種統計）
                $updateMachinePlayStmt = $pdo->prepare("
                    UPDATE dat_machinePlay
                    SET
                        total_count = total_count + :play_count,
                        bb_count = bb_count + :bb_count,
                        rb_count = rb_count + :rb_count,
                        in_credit = in_credit + :in_credit,
                        out_credit = out_credit + :out_credit,
                        upd_dt = NOW()
                    WHERE machine_no = :machine_no
                ");
                $updateMachinePlayStmt->execute([
                    'play_count' => $resultData['play_count'] ?? 0,
                    'bb_count' => $resultData['bb_count'] ?? 0,
                    'rb_count' => $resultData['rb_count'] ?? 0,
                    'in_credit' => $resultData['in_credit'] ?? 0,
                    'out_credit' => $credit,
                    'machine_no' => $session['machine_no']
                ]);

                // lnk_machine を解放
                $releaseMachineStmt = $pdo->prepare("
                    UPDATE lnk_machine
                    SET assign_flg = 0, member_no = '', onetime_id = '', exit_flg = 0, end_dt = NOW()
                    WHERE machine_no = :machine_no
                ");
                $releaseMachineStmt->execute(['machine_no' => $session['machine_no']]);
            }
        }

        // トランザクションコミット
        $pdo->commit();

        // 成功レスポンス
        $response = [
            'success' => true,
            'sessionId' => $sessionId,
            'memberNo' => $memberNo,
            'result' => $result,
            'pointsConsumed' => $session['points_consumed'],
            'pointsWon' => $pointsWon,
            'netProfit' => $pointsWon - $session['points_consumed'],
            'playDuration' => $playDuration,
            'endedAt' => $endedAt->format('Y-m-d H:i:s')
        ];

        // 残高情報を追加
        if ($newBalance !== null) {
            $response['newBalance'] = $newBalance;
        }

        // 取引情報を追加
        if ($transaction) {
            $response['transaction'] = [
                'id' => $transaction['transaction_id'],
                'amount' => $transaction['amount'],
                'balanceBefore' => $transaction['balance_before'],
                'balanceAfter' => $transaction['balance_after']
            ];
        }

        http_response_code(200);
        echo json_encode($response);

    } catch (Exception $e) {
        // トランザクションロールバック
        $pdo->rollBack();
        error_log('Game End Transaction Error: ' . $e->getMessage());

        http_response_code(500);
        echo json_encode([
            'error' => 'TRANSACTION_FAILED',
            'message' => 'Failed to complete game end transaction: ' . $e->getMessage()
        ]);
        exit;
    }

} catch (Exception $e) {
    error_log('Game End API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to end game: ' . $e->getMessage()
    ]);
}
