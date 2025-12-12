<?php
/**
 * NET8 Claude Code API - Machines (台) Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET    /api/claude/machines              - 台一覧
 * POST   /api/claude/machines              - 新規登録
 * GET    /api/claude/machines/{id}         - 詳細取得
 * PUT    /api/claude/machines/{id}         - 更新
 * DELETE /api/claude/machines/{id}         - 削除
 * PUT    /api/claude/machines/{id}/status  - 状態変更
 * PUT    /api/claude/machines/{id}/corner  - コーナー配置
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    $machineNo = $pathParts[1] ?? null;
    $action = $pathParts[2] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($machineNo === null) {
        if ($method === 'GET') {
            listMachines($pdo);
        } elseif ($method === 'POST') {
            createMachine($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'POST']);
        }
    } elseif ($action === 'status' && $method === 'PUT') {
        updateMachineStatus($pdo, $machineNo);
    } elseif ($action === 'corner' && $method === 'PUT') {
        updateMachineCorner($pdo, $machineNo);
    } else {
        if ($method === 'GET') {
            getMachine($pdo, $machineNo);
        } elseif ($method === 'PUT') {
            updateMachine($pdo, $machineNo);
        } elseif ($method === 'DELETE') {
            deleteMachine($pdo, $machineNo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT', 'DELETE']);
        }
    }

} catch (Exception $e) {
    error_log('Claude Machines API Error: ' . $e->getMessage());
    // デバッグ用：詳細エラーメッセージを返す
    echo json_encode([
        'success' => false,
        'error' => [
            'message' => $e->getMessage(),
            'code' => 'DEBUG_ERROR',
            'file' => $e->getFile(),
            'line' => $e->getLine()
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 台一覧取得
 */
function listMachines($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $modelNo = $_GET['model_no'] ?? '';
    $status = $_GET['status'] ?? '';
    $cornerNo = $_GET['corner_no'] ?? '';
    $category = $_GET['category'] ?? '';

    $where = "dm.del_flg = 0";
    $params = [];

    if ($modelNo !== '') {
        $where .= " AND dm.model_no = :model_no";
        $params['model_no'] = $modelNo;
    }
    if ($status !== '') {
        $where .= " AND dm.machine_status = :status";
        $params['status'] = $status;
    }
    if ($category !== '') {
        $where .= " AND mo.category = :category";
        $params['category'] = $category;
    }

    // カウント
    $countSql = "SELECT COUNT(*)
                 FROM dat_machine dm
                 LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
                 WHERE {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT
                dm.machine_no, dm.machine_cd, dm.model_no, dm.owner_no,
                dm.camera_no, dm.signaling_id, dm.convert_no,
                dm.release_date, dm.end_date, dm.machine_corner,
                dm.machine_status,
                mo.model_name, mo.model_cd as model_code, mo.category,
                ma.maker_name,
                ow.owner_nickname
            FROM dat_machine dm
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_maker ma ON ma.maker_no = mo.maker_no
            LEFT JOIN mst_owner ow ON ow.owner_no = dm.owner_no
            WHERE {$where}
            ORDER BY dm.machine_no DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ステータス名追加
    $statusNames = ['0' => '準備中', '1' => '稼働中', '2' => 'メンテナンス'];
    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($machines as &$machine) {
        $machine['status_name'] = $statusNames[$machine['machine_status']] ?? '不明';
        $machine['category_name'] = $categoryNames[$machine['category']] ?? '';
        $machine['is_available'] = $machine['machine_status'] == '1';
    }

    ApiResponse::list($machines, $total, $page, $perPage);
}

/**
 * 台詳細取得
 */
function getMachine($pdo, $machineNo) {
    $sql = "SELECT
                dm.*,
                mo.model_name, mo.model_cd, mo.category, mo.image_list,
                ma.maker_name,
                ow.owner_nickname, ow.owner_name,
                mcp.convert_name
            FROM dat_machine dm
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_maker ma ON ma.maker_no = mo.maker_no
            LEFT JOIN mst_owner ow ON ow.owner_no = dm.owner_no
            LEFT JOIN mst_convertPoint mcp ON mcp.convert_no = dm.convert_no
            WHERE dm.machine_no = :machine_no AND dm.del_flg = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        ApiResponse::notFound('台が見つかりません');
    }

    $statusNames = ['0' => '準備中', '1' => '稼働中', '2' => 'メンテナンス'];
    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    $machine['status_name'] = $statusNames[$machine['machine_status']] ?? '不明';
    $machine['category_name'] = $categoryNames[$machine['category']] ?? '';
    $machine['is_available'] = $machine['machine_status'] == '1';

    // コーナー情報取得
    $sql = "SELECT c.corner_no, c.corner_name
            FROM dat_machineCorner mc
            JOIN mst_corner c ON c.corner_no = mc.corner_no
            WHERE mc.machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    $machine['corners'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::success($machine);
}

/**
 * 台新規登録
 */
function createMachine($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('model_no', '機種は必須です')
              ->required('machine_cd', '台コードは必須です');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    // 機種存在チェック
    $sql = "SELECT * FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $input['model_no']]);
    if (!$stmt->fetch()) {
        ApiResponse::error('指定された機種が存在しません', 400, 'MODEL_NOT_FOUND');
    }

    // signaling_idを自動生成（カメラIDベース）
    $signalingId = $input['signaling_id'] ?? ('PEER-' . strtoupper(substr(md5($input['machine_cd']), 0, 8)));

    $sql = "INSERT INTO dat_machine (
                model_no, machine_cd, owner_no, camera_no, signaling_id,
                convert_no, release_date, end_date, machine_corner,
                machine_status, del_flg, add_dt, upd_dt
            ) VALUES (
                :model_no, :machine_cd, :owner_no, :camera_no, :signaling_id,
                :convert_no, :release_date, :end_date, :machine_corner,
                :machine_status, 0, NOW(), NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'model_no' => $input['model_no'],
        'machine_cd' => $input['machine_cd'],
        'owner_no' => $input['owner_no'] ?? null,
        'camera_no' => $input['camera_no'] ?? null,
        'signaling_id' => $signalingId,
        'convert_no' => $input['convert_no'] ?? 1,
        'release_date' => $input['release_date'] ?? date('Y-m-d'),
        'end_date' => $input['end_date'] ?? '2099-12-31',
        'machine_corner' => $input['machine_corner'] ?? null,
        'machine_status' => $input['machine_status'] ?? '0'
    ]);

    $machineNo = $pdo->lastInsertId();

    ApiResponse::success(['machine_no' => $machineNo], '台を登録しました', 201);
}

/**
 * 台更新
 */
function updateMachine($pdo, $machineNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM dat_machine WHERE machine_no = :machine_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('台が見つかりません');
    }

    $updates = [];
    $params = ['machine_no' => $machineNo];

    $updateFields = [
        'model_no', 'machine_cd', 'owner_no', 'camera_no', 'signaling_id',
        'convert_no', 'release_date', 'end_date', 'machine_corner', 'machine_status'
    ];

    foreach ($updateFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[$field] = $input[$field];
        }
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $updates[] = "upd_dt = NOW()";
    $sql = "UPDATE dat_machine SET " . implode(', ', $updates) . " WHERE machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    getMachine($pdo, $machineNo);
}

/**
 * 台削除
 */
function deleteMachine($pdo, $machineNo) {
    $sql = "SELECT * FROM dat_machine WHERE machine_no = :machine_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('台が見つかりません');
    }

    $sql = "UPDATE dat_machine SET del_flg = 1, del_dt = NOW() WHERE machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);

    ApiResponse::success(null, '台を削除しました');
}

/**
 * 台状態変更
 */
function updateMachineStatus($pdo, $machineNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM dat_machine WHERE machine_no = :machine_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    $machine = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$machine) {
        ApiResponse::notFound('台が見つかりません');
    }

    $status = $input['status'] ?? $input['machine_status'] ?? null;
    if ($status === null || !in_array($status, ['0', '1', '2'])) {
        ApiResponse::error('statusは0(準備中), 1(稼働中), 2(メンテナンス)のいずれかを指定してください', 400);
    }

    $sql = "UPDATE dat_machine SET machine_status = :status, upd_dt = NOW() WHERE machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['status' => $status, 'machine_no' => $machineNo]);

    $statusNames = ['0' => '準備中', '1' => '稼働中', '2' => 'メンテナンス'];

    ApiResponse::success([
        'machine_no' => $machineNo,
        'machine_status' => $status,
        'status_name' => $statusNames[$status]
    ], '台の状態を変更しました');
}

/**
 * 台コーナー配置変更
 */
function updateMachineCorner($pdo, $machineNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM dat_machine WHERE machine_no = :machine_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('台が見つかりません');
    }

    $cornerNo = $input['corner_no'] ?? null;

    // 既存のコーナー割り当てを削除
    $sql = "DELETE FROM dat_machineCorner WHERE machine_no = :machine_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['machine_no' => $machineNo]);

    // 新しいコーナーを割り当て
    if ($cornerNo !== null) {
        $sql = "INSERT INTO dat_machineCorner (machine_no, corner_no, add_dt) VALUES (:machine_no, :corner_no, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['machine_no' => $machineNo, 'corner_no' => $cornerNo]);
    }

    ApiResponse::success([
        'machine_no' => $machineNo,
        'corner_no' => $cornerNo
    ], 'コーナー配置を更新しました');
}
