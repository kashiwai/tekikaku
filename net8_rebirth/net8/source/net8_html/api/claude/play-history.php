<?php
/**
 * NET8 Claude Code API - Play History Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/play-history            - プレイ履歴一覧
 * GET  /api/claude/play-history/{id}       - プレイ詳細
 * GET  /api/claude/play-history/active     - アクティブセッション一覧
 * POST /api/claude/play-history/{id}/end   - セッション強制終了
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ClaudeAuth.php';
require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();
    $auth = new ClaudeAuth($pdo);
    $authData = $auth->requireAuth();

    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $pathInfo));
    $pathValues = array_values($pathParts);
    $sessionId = $pathValues[0] ?? null;
    $action = $pathValues[1] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($sessionId === null) {
        if ($method === 'GET') {
            listPlayHistory($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET']);
        }
    } elseif ($sessionId === 'active' && $method === 'GET') {
        getActiveSessions($pdo);
    } elseif ($action === 'end' && $method === 'POST') {
        forceEndSession($pdo, $sessionId);
    } else {
        if ($method === 'GET') {
            getPlayDetail($pdo, $sessionId);
        } else {
            ApiResponse::methodNotAllowed(['GET']);
        }
    }

} catch (Exception $e) {
    error_log('Claude Play History API Error: ' . $e->getMessage());
    ApiResponse::serverError('プレイ履歴処理中にエラーが発生しました');
}

/**
 * プレイ履歴一覧取得
 */
function listPlayHistory($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $memberNo = $_GET['member_no'] ?? '';
    $machineNo = $_GET['machine_no'] ?? '';
    $modelNo = $_GET['model_no'] ?? '';
    $status = $_GET['status'] ?? '';

    $where = "1=1";
    $params = [];

    if ($startDate !== '') {
        $where .= " AND gs.start_dt >= :start_date";
        $params['start_date'] = $startDate;
    }
    if ($endDate !== '') {
        $where .= " AND gs.start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY)";
        $params['end_date'] = $endDate;
    }
    if ($memberNo !== '') {
        $where .= " AND gs.member_no = :member_no";
        $params['member_no'] = $memberNo;
    }
    if ($machineNo !== '') {
        $where .= " AND gs.machine_no = :machine_no";
        $params['machine_no'] = $machineNo;
    }
    if ($modelNo !== '') {
        $where .= " AND dm.model_no = :model_no";
        $params['model_no'] = $modelNo;
    }
    if ($status !== '') {
        $where .= " AND gs.status = :status";
        $params['status'] = $status;
    }

    // カウント
    $countSql = "SELECT COUNT(*)
                 FROM his_gamesession gs
                 LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
                 WHERE {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT
                gs.session_no, gs.member_no, gs.machine_no,
                gs.start_dt, gs.end_dt, gs.status,
                gs.bet_point, gs.win_point, gs.play_count,
                gs.initial_point, gs.final_point,
                dm.machine_cd,
                mo.model_no, mo.model_name, mo.category,
                mm.member_cd, mm.member_nickname
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE {$where}
            ORDER BY gs.start_dt DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $statusNames = ['playing' => 'プレイ中', 'completed' => '完了', 'timeout' => 'タイムアウト', 'error' => 'エラー'];
    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];

    foreach ($history as &$h) {
        $h['status_name'] = $statusNames[$h['status']] ?? '不明';
        $h['category_name'] = $categoryNames[$h['category']] ?? '';
        $h['profit'] = ($h['bet_point'] ?? 0) - ($h['win_point'] ?? 0);
        if ($h['start_dt'] && $h['end_dt']) {
            $start = new DateTime($h['start_dt']);
            $end = new DateTime($h['end_dt']);
            $h['duration_minutes'] = round(($end->getTimestamp() - $start->getTimestamp()) / 60, 1);
        } else {
            $h['duration_minutes'] = null;
        }
    }

    ApiResponse::list($history, $total, $page, $perPage);
}

/**
 * プレイ詳細取得
 */
function getPlayDetail($pdo, $sessionNo) {
    $sql = "SELECT
                gs.*,
                dm.machine_cd, dm.machine_status,
                mo.model_no, mo.model_name, mo.category, mo.image_list,
                mm.member_cd, mm.member_nickname, mm.playpoint as current_point
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE gs.session_no = :session_no";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['session_no' => $sessionNo]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        ApiResponse::notFound('プレイ履歴が見つかりません');
    }

    $statusNames = ['playing' => 'プレイ中', 'completed' => '完了', 'timeout' => 'タイムアウト', 'error' => 'エラー'];
    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];

    $session['status_name'] = $statusNames[$session['status']] ?? '不明';
    $session['category_name'] = $categoryNames[$session['category']] ?? '';
    $session['profit'] = ($session['bet_point'] ?? 0) - ($session['win_point'] ?? 0);

    if ($session['start_dt'] && $session['end_dt']) {
        $start = new DateTime($session['start_dt']);
        $end = new DateTime($session['end_dt']);
        $session['duration_minutes'] = round(($end->getTimestamp() - $start->getTimestamp()) / 60, 1);
    }

    // プレイログ取得（存在する場合）
    $sql = "SELECT * FROM his_gameplay WHERE session_no = :session_no ORDER BY play_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['session_no' => $sessionNo]);
    $session['play_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::success($session);
}

/**
 * アクティブセッション一覧取得
 */
function getActiveSessions($pdo) {
    $sql = "SELECT
                gs.session_no, gs.member_no, gs.machine_no,
                gs.start_dt, gs.status,
                gs.bet_point, gs.win_point, gs.play_count,
                gs.initial_point,
                dm.machine_cd,
                mo.model_name, mo.category,
                mm.member_cd, mm.member_nickname
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE gs.status = 'playing'
            ORDER BY gs.start_dt ASC";

    $stmt = $pdo->query($sql);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];

    foreach ($sessions as &$s) {
        $s['category_name'] = $categoryNames[$s['category']] ?? '';
        if ($s['start_dt']) {
            $start = new DateTime($s['start_dt']);
            $now = new DateTime();
            $s['elapsed_minutes'] = round(($now->getTimestamp() - $start->getTimestamp()) / 60, 1);
        }
    }

    ApiResponse::success([
        'active_count' => count($sessions),
        'sessions' => $sessions
    ]);
}

/**
 * セッション強制終了
 */
function forceEndSession($pdo, $sessionNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT gs.*, mm.playpoint
            FROM his_gamesession gs
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE gs.session_no = :session_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['session_no' => $sessionNo]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$session) {
        ApiResponse::notFound('セッションが見つかりません');
    }

    if ($session['status'] !== 'playing') {
        ApiResponse::error('このセッションは既に終了しています', 400, 'ALREADY_ENDED');
    }

    $reason = $input['reason'] ?? '管理者による強制終了';

    $pdo->beginTransaction();

    try {
        // セッション終了
        $sql = "UPDATE his_gamesession SET
                    status = 'completed',
                    end_dt = NOW(),
                    final_point = :final_point,
                    end_reason = :reason
                WHERE session_no = :session_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'final_point' => $session['playpoint'],
            'reason' => $reason,
            'session_no' => $sessionNo
        ]);

        // 台の割り当て解除
        $sql = "UPDATE dat_machine SET assign_flg = '0', upd_dt = NOW() WHERE machine_no = :machine_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['machine_no' => $session['machine_no']]);

        $pdo->commit();

        ApiResponse::success([
            'session_no' => $sessionNo,
            'member_no' => $session['member_no'],
            'machine_no' => $session['machine_no'],
            'reason' => $reason,
            'ended_at' => date('Y-m-d H:i:s')
        ], 'セッションを強制終了しました');

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
