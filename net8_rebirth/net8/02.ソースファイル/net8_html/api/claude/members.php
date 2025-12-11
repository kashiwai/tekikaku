<?php
/**
 * NET8 Claude Code API - Members (会員) Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET    /api/claude/members                    - 会員一覧・検索
 * GET    /api/claude/members/{id}               - 詳細取得
 * PUT    /api/claude/members/{id}               - 更新
 * POST   /api/claude/members/{id}/suspend       - 緊急停止
 * POST   /api/claude/members/{id}/activate      - 停止解除
 * GET    /api/claude/members/{id}/points        - ポイント履歴
 * POST   /api/claude/members/{id}/points        - ポイント付与
 * GET    /api/claude/members/{id}/play-history  - プレイ履歴
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ClaudeAuth.php';
require_once __DIR__ . '/helpers/ApiValidator.php';
require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();
    $auth = new ClaudeAuth($pdo);
    $authData = $auth->requireAuth();

    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $pathInfo));
    $pathParts = array_values($pathParts);
    $memberNo = $pathParts[0] ?? null;
    $action = $pathParts[1] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($memberNo === null) {
        if ($method === 'GET') {
            listMembers($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET']);
        }
    } elseif ($action === 'suspend' && $method === 'POST') {
        suspendMember($pdo, $memberNo);
    } elseif ($action === 'activate' && $method === 'POST') {
        activateMember($pdo, $memberNo);
    } elseif ($action === 'points') {
        if ($method === 'GET') {
            getMemberPointHistory($pdo, $memberNo);
        } elseif ($method === 'POST') {
            grantPoints($pdo, $memberNo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'POST']);
        }
    } elseif ($action === 'play-history' && $method === 'GET') {
        getMemberPlayHistory($pdo, $memberNo);
    } else {
        if ($method === 'GET') {
            getMember($pdo, $memberNo);
        } elseif ($method === 'PUT') {
            updateMember($pdo, $memberNo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT']);
        }
    }

} catch (Exception $e) {
    error_log('Claude Members API Error: ' . $e->getMessage());
    ApiResponse::serverError('会員処理中にエラーが発生しました');
}

/**
 * 会員一覧取得
 */
function listMembers($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? '';

    $where = "mm.del_flg = 0";
    $params = [];

    if ($search !== '') {
        $where .= " AND (mm.member_id LIKE :search OR mm.nickname LIKE :search2 OR mm.mail LIKE :search3)";
        $params['search'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
        $params['search3'] = "%{$search}%";
    }
    if ($status !== '') {
        $where .= " AND mm.status = :status";
        $params['status'] = $status;
    }

    // カウント
    $countSql = "SELECT COUNT(*) FROM mst_member mm WHERE {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT
                mm.member_no, mm.member_id, mm.nickname, mm.mail,
                mm.status, mm.playpoint, mm.total_playpoint,
                mm.login_dt, mm.add_dt
            FROM mst_member mm
            WHERE {$where}
            ORDER BY mm.member_no DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ステータス名追加
    $statusNames = ['0' => '仮登録', '1' => '有効', '2' => '停止', '9' => '退会'];
    foreach ($members as &$member) {
        $member['status_name'] = $statusNames[$member['status']] ?? '不明';
        $member['is_active'] = $member['status'] == '1';
    }

    ApiResponse::list($members, $total, $page, $perPage);
}

/**
 * 会員詳細取得
 */
function getMember($pdo, $memberNo) {
    $sql = "SELECT * FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        ApiResponse::notFound('会員が見つかりません');
    }

    // パスワードは除外
    unset($member['password']);

    $statusNames = ['0' => '仮登録', '1' => '有効', '2' => '停止', '9' => '退会'];
    $member['status_name'] = $statusNames[$member['status']] ?? '不明';
    $member['is_active'] = $member['status'] == '1';

    // プレイ統計
    $sql = "SELECT
                COUNT(*) as play_count,
                SUM(use_point) as total_use_point,
                MAX(play_start) as last_play
            FROM his_play
            WHERE member_no = :member_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member['play_stats'] = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success($member);
}

/**
 * 会員更新
 */
function updateMember($pdo, $memberNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('会員が見つかりません');
    }

    $updates = [];
    $params = ['member_no' => $memberNo];

    $allowedFields = ['nickname', 'mail', 'status', 'playpoint', 'memo'];
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[$field] = $input[$field];
        }
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $updates[] = "upd_dt = NOW()";
    $sql = "UPDATE mst_member SET " . implode(', ', $updates) . " WHERE member_no = :member_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    getMember($pdo, $memberNo);
}

/**
 * 会員緊急停止
 */
function suspendMember($pdo, $memberNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        ApiResponse::notFound('会員が見つかりません');
    }

    if ($member['status'] == '2') {
        ApiResponse::error('既に停止されています', 400, 'ALREADY_SUSPENDED');
    }

    $reason = $input['reason'] ?? '管理者による緊急停止';

    // ステータスを停止に更新
    $sql = "UPDATE mst_member SET status = '2', memo = CONCAT(IFNULL(memo, ''), '\n[', NOW(), '] 緊急停止: ', :reason), upd_dt = NOW() WHERE member_no = :member_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['reason' => $reason, 'member_no' => $memberNo]);

    // 進行中のセッションを強制終了
    $sql = "UPDATE lnk_machine SET assign_flg = 0, upd_dt = NOW() WHERE member_no = :member_no AND assign_flg = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);

    ApiResponse::success([
        'member_no' => $memberNo,
        'status' => '2',
        'status_name' => '停止',
        'reason' => $reason
    ], '会員を緊急停止しました');
}

/**
 * 会員停止解除
 */
function activateMember($pdo, $memberNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        ApiResponse::notFound('会員が見つかりません');
    }

    if ($member['status'] == '1') {
        ApiResponse::error('既に有効です', 400, 'ALREADY_ACTIVE');
    }

    $reason = $input['reason'] ?? '管理者による停止解除';

    $sql = "UPDATE mst_member SET status = '1', memo = CONCAT(IFNULL(memo, ''), '\n[', NOW(), '] 停止解除: ', :reason), upd_dt = NOW() WHERE member_no = :member_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['reason' => $reason, 'member_no' => $memberNo]);

    ApiResponse::success([
        'member_no' => $memberNo,
        'status' => '1',
        'status_name' => '有効',
        'reason' => $reason
    ], '会員の停止を解除しました');
}

/**
 * ポイント履歴取得
 */
function getMemberPointHistory($pdo, $memberNo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    // 会員存在チェック
    $sql = "SELECT playpoint, total_playpoint FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        ApiResponse::notFound('会員が見つかりません');
    }

    // カウント
    $countSql = "SELECT COUNT(*) FROM his_point WHERE member_no = :member_no";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute(['member_no' => $memberNo]);
    $total = $stmt->fetchColumn();

    // 履歴取得
    $sql = "SELECT * FROM his_point WHERE member_no = :member_no ORDER BY add_dt DESC LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('member_no', $memberNo, PDO::PARAM_INT);
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::list([
        'current_point' => $member['playpoint'],
        'total_point' => $member['total_playpoint'],
        'history' => $history
    ], $total, $page, $perPage);
}

/**
 * ポイント付与
 */
function grantPoints($pdo, $memberNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('point', 'ポイントは必須です')
              ->integer('point', 'ポイントは整数で入力してください');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    $point = (int)$input['point'];
    $reason = $input['reason'] ?? '管理者付与';
    $procCd = $input['proc_cd'] ?? '99'; // 99 = 管理者付与

    // 会員取得
    $sql = "SELECT * FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    $member = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$member) {
        ApiResponse::notFound('会員が見つかりません');
    }

    $pdo->beginTransaction();

    try {
        // ポイント更新
        $newPoint = $member['playpoint'] + $point;
        $newTotal = $member['total_playpoint'] + ($point > 0 ? $point : 0);

        $sql = "UPDATE mst_member SET playpoint = :playpoint, total_playpoint = :total_playpoint, upd_dt = NOW() WHERE member_no = :member_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'playpoint' => $newPoint,
            'total_playpoint' => $newTotal,
            'member_no' => $memberNo
        ]);

        // 履歴追加
        $sql = "INSERT INTO his_point (member_no, proc_cd, point, balance, remarks, add_dt) VALUES (:member_no, :proc_cd, :point, :balance, :remarks, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'member_no' => $memberNo,
            'proc_cd' => $procCd,
            'point' => $point,
            'balance' => $newPoint,
            'remarks' => $reason
        ]);

        $pdo->commit();

        ApiResponse::success([
            'member_no' => $memberNo,
            'granted_point' => $point,
            'new_balance' => $newPoint,
            'reason' => $reason
        ], 'ポイントを付与しました');

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * プレイ履歴取得
 */
function getMemberPlayHistory($pdo, $memberNo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    // 会員存在チェック
    $sql = "SELECT member_no FROM mst_member WHERE member_no = :member_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['member_no' => $memberNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('会員が見つかりません');
    }

    // カウント
    $countSql = "SELECT COUNT(*) FROM his_play WHERE member_no = :member_no";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute(['member_no' => $memberNo]);
    $total = $stmt->fetchColumn();

    // 履歴取得
    $sql = "SELECT
                hp.*,
                dm.machine_cd,
                mo.model_name
            FROM his_play hp
            LEFT JOIN dat_machine dm ON dm.machine_no = hp.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            WHERE hp.member_no = :member_no
            ORDER BY hp.play_start DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('member_no', $memberNo, PDO::PARAM_INT);
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::list($history, $total, $page, $perPage);
}
