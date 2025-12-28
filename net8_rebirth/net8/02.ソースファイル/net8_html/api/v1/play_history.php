<?php
/**
 * NET8 SDK API - Play History Endpoint
 * Version: 1.1.0 (Case 3対応: タイムアウト検知・自動終了機能追加)
 * Created: 2025-11-18
 * Updated: 2025-12-28
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

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
        'success' => false,
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header required'
    ]);
    exit;
}

// クエリパラメータ取得
$userId = $_GET['userId'] ?? null;
$sessionId = $_GET['sessionId'] ?? null; // Case 3: 特定セッション取得
$statusFilter = $_GET['status'] ?? null; // active, completed, timeout
$autoClose = isset($_GET['autoClose']) && $_GET['autoClose'] === 'true'; // Case 3: 自動終了
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
$lang = $_GET['lang'] ?? 'ja'; // 多言語対応: ja/ko/en/zh（デフォルト: ja）

// タイムアウト閾値（分）
$timeoutMinutes = 60;

if ($limit > 100) {
    $limit = 100;
}
if ($limit < 1) {
    $limit = 20;
}

try {
    $pdo = get_db_connection();

    // APIキー認証
    $apiKeyId = null;

    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
            }
        } else {
            $apiKeyStmt = $pdo->prepare("SELECT id FROM api_keys WHERE key_value = :key_value AND is_active = 1");
            $apiKeyStmt->execute(['key_value' => $token]);
            $apiKeyData = $apiKeyStmt->fetch(PDO::FETCH_ASSOC);

            if ($apiKeyData) {
                $apiKeyId = $apiKeyData['id'];
            }
        }
    }

    if (!$apiKeyId) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'INVALID_TOKEN',
            'message' => 'Invalid authentication token'
        ]);
        exit;
    }

    // ユーザーIDを内部IDに変換
    $internalUserId = null;
    if ($userId) {
        $stmt = $pdo->prepare("
            SELECT id
            FROM sdk_users
            WHERE api_key_id = :api_key_id
            AND partner_user_id = :partner_user_id
        ");

        $stmt->execute([
            'api_key_id' => $apiKeyId,
            'partner_user_id' => $userId
        ]);

        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            $internalUserId = $user['id'];
        }
    }

    // クエリビルド（Case 3: タイムアウト検知対応 + 多言語対応）
    // 言語に応じた機種名カラムを決定
    $modelNameColumn = match($lang) {
        'ko' => 'COALESCE(m.model_name_ko, m.model_name_ja, m.model_name)',
        'en' => 'COALESCE(m.model_name_en, m.model_name_ja, m.model_name)',
        'zh' => 'COALESCE(m.model_name_zh, m.model_name_ja, m.model_name)',
        default => 'COALESCE(m.model_name_ja, m.model_name)'
    };

    $sql = "SELECT
                gs.id,
                gs.session_id,
                u.partner_user_id AS user_id,
                gs.machine_no,
                gs.model_cd,
                {$modelNameColumn} as model_name,
                gs.points_consumed,
                gs.reserved_points,
                gs.points_won,
                (gs.points_won - gs.points_consumed) AS net_profit,
                gs.play_duration,
                gs.result,
                gs.status,
                gs.started_at,
                gs.ended_at,
                gs.created_at,
                TIMESTAMPDIFF(MINUTE, gs.started_at, COALESCE(gs.ended_at, NOW())) as elapsed_minutes,
                CASE
                    WHEN gs.ended_at IS NULL AND TIMESTAMPDIFF(MINUTE, gs.started_at, NOW()) > ? THEN 'timeout'
                    WHEN gs.ended_at IS NULL THEN 'active'
                    ELSE gs.status
                END as computed_status
            FROM game_sessions gs
            LEFT JOIN sdk_users u ON gs.user_id = u.id
            LEFT JOIN mst_model m ON gs.model_cd = m.model_cd AND m.del_flg = 0
            WHERE gs.api_key_id = :api_key_id";

    $params = ['api_key_id' => $apiKeyId];
    $types = [':timeout_minutes' => $timeoutMinutes];

    // ユーザーIDフィルター
    if ($internalUserId) {
        $sql .= " AND gs.user_id = :user_id";
        $params['user_id'] = $internalUserId;
    }

    // セッションIDフィルター（Case 3）
    if ($sessionId) {
        $sql .= " AND gs.session_id = :session_id";
        $params['session_id'] = $sessionId;
    }

    // ステータスフィルター（HAVING句で適用）
    $havingClause = '';
    if ($statusFilter) {
        if (!in_array($statusFilter, ['active', 'completed', 'timeout'])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'INVALID_STATUS',
                'message' => 'status must be active, completed, or timeout'
            ]);
            exit;
        }
        $havingClause = " HAVING computed_status = :status_filter";
        $params['status_filter'] = $statusFilter;
    }

    $sql .= $havingClause . " ORDER BY gs.created_at DESC LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);

    // パラメータをバインド
    $stmt->bindValue(':timeout_minutes', $timeoutMinutes, PDO::PARAM_INT);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Case 3: タイムアウトセッションの自動終了
    $autoClosedSessions = [];
    if ($autoClose) {
        foreach ($sessions as &$session) {
            if ($session['computed_status'] === 'timeout') {
                $closeResult = autoCloseSession($pdo, $session['session_id']);
                if ($closeResult) {
                    $session['auto_closed'] = true;
                    $session['auto_close_time'] = date('c');
                    $session['final_balance'] = $closeResult['newBalance'];
                    $autoClosedSessions[] = $session['session_id'];
                }
            }
        }
    }

    // 総件数を取得
    $countSql = "SELECT COUNT(*) as total
                 FROM (
                     SELECT
                         gs.session_id,
                         CASE
                             WHEN gs.ended_at IS NULL AND TIMESTAMPDIFF(MINUTE, gs.started_at, NOW()) > ? THEN 'timeout'
                             WHEN gs.ended_at IS NULL THEN 'active'
                             ELSE gs.status
                         END as computed_status
                     FROM game_sessions gs
                     WHERE gs.api_key_id = :api_key_id";

    $countParams = ['api_key_id' => $apiKeyId];

    if ($internalUserId) {
        $countSql .= " AND gs.user_id = :user_id";
        $countParams['user_id'] = $internalUserId;
    }

    if ($sessionId) {
        $countSql .= " AND gs.session_id = :session_id";
        $countParams['session_id'] = $sessionId;
    }

    $countSql .= ") AS filtered_sessions";

    if ($statusFilter) {
        $countSql .= " WHERE computed_status = :status_filter";
        $countParams['status_filter'] = $statusFilter;
    }

    $stmt = $pdo->prepare($countSql);
    $stmt->bindValue(1, $timeoutMinutes, PDO::PARAM_INT);
    foreach ($countParams as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => $sessions,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'hasMore' => ($offset + $limit) < $total
        ],
        'autoClosedCount' => count($autoClosedSessions),
        'autoClosedSessions' => $autoClosedSessions
    ]);

} catch (Exception $e) {
    error_log('Play History API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to fetch play history'
    ]);
}

/**
 * タイムアウトセッションを自動終了
 */
function autoCloseSession($pdo, $sessionId) {
    try {
        $pdo->beginTransaction();

        // セッション情報取得
        $stmt = $pdo->prepare("
            SELECT user_id, points_consumed, reserved_points, model_cd, machine_no
            FROM game_sessions
            WHERE session_id = ? AND ended_at IS NULL
            FOR UPDATE
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            $pdo->rollBack();
            return null;
        }

        // セッション終了
        $stmt = $pdo->prepare("
            UPDATE game_sessions
            SET ended_at = NOW(), points_won = 0, result = 'timeout', status = 'timeout'
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);

        // 予約ポイントがある場合は返金
        if ($session['reserved_points'] > 0) {
            $stmt = $pdo->prepare("
                UPDATE user_balances
                SET balance = balance + ?
                WHERE user_id = ?
            ");
            $stmt->execute([$session['reserved_points'], $session['user_id']]);

            // mst_member.point 同期
            $stmt = $pdo->prepare("
                UPDATE mst_member
                SET point = point + ?
                WHERE member_no IN (SELECT member_no FROM sdk_users WHERE id = ?)
            ");
            $stmt->execute([$session['reserved_points'], $session['user_id']]);
        }

        // 新しい残高取得
        $stmt = $pdo->prepare("SELECT balance FROM user_balances WHERE user_id = ?");
        $stmt->execute([$session['user_id']]);
        $newBalance = $stmt->fetchColumn();

        $pdo->commit();

        error_log("✅ Auto-closed timeout session: {$sessionId}, refunded: {$session['reserved_points']}");

        return [
            'success' => true,
            'sessionId' => $sessionId,
            'refundedPoints' => $session['reserved_points'],
            'newBalance' => $newBalance
        ];

    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("autoCloseSession error: " . $e->getMessage());
        return null;
    }
}
