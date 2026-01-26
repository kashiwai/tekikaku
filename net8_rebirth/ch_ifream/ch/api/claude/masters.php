<?php
/**
 * NET8 Claude Code API - Masters Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/masters/makers       - メーカー一覧
 * POST /api/claude/masters/makers       - メーカー登録
 * GET  /api/claude/masters/types        - 種別一覧
 * GET  /api/claude/masters/units        - 単位一覧
 * GET  /api/claude/masters/corners      - コーナー一覧
 * POST /api/claude/masters/corners      - コーナー登録
 * GET  /api/claude/masters/owners       - オーナー一覧
 * GET  /api/claude/masters/categories   - カテゴリ一覧
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
    $pathValues = array_values($pathParts);
    $resource = $pathValues[0] ?? null;
    $resourceId = $pathValues[1] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    switch ($resource) {
        case 'makers':
            handleMakers($pdo, $method, $resourceId);
            break;
        case 'types':
            handleTypes($pdo, $method);
            break;
        case 'units':
            handleUnits($pdo, $method);
            break;
        case 'corners':
            handleCorners($pdo, $method, $resourceId);
            break;
        case 'owners':
            handleOwners($pdo, $method, $resourceId);
            break;
        case 'categories':
            handleCategories($pdo, $method);
            break;
        default:
            ApiResponse::notFound('マスタリソースが見つかりません');
    }

} catch (Exception $e) {
    error_log('Claude Masters API Error: ' . $e->getMessage());
    ApiResponse::serverError('マスタ処理中にエラーが発生しました');
}

/**
 * メーカー管理
 */
function handleMakers($pdo, $method, $makerId = null) {
    if ($method === 'GET' && $makerId === null) {
        // 一覧
        $sql = "SELECT maker_no, maker_cd, maker_name, sort_no, del_flg FROM mst_maker WHERE del_flg = 0 ORDER BY sort_no, maker_no";
        $stmt = $pdo->query($sql);
        $makers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success($makers);
    } elseif ($method === 'GET' && $makerId !== null) {
        // 詳細
        $sql = "SELECT * FROM mst_maker WHERE maker_no = :maker_no AND del_flg = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['maker_no' => $makerId]);
        $maker = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$maker) {
            ApiResponse::notFound('メーカーが見つかりません');
        }
        ApiResponse::success($maker);
    } elseif ($method === 'POST') {
        // 登録
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $validator = new ApiValidator($input);
        $validator->required('maker_name', 'メーカー名は必須です');
        if (!$validator->validate()) {
            ApiResponse::validationError($validator->getErrors());
        }

        $sql = "INSERT INTO mst_maker (maker_cd, maker_name, sort_no, del_flg, add_dt) VALUES (:maker_cd, :maker_name, :sort_no, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'maker_cd' => $input['maker_cd'] ?? '',
            'maker_name' => $input['maker_name'],
            'sort_no' => $input['sort_no'] ?? 0
        ]);
        $makerId = $pdo->lastInsertId();
        ApiResponse::success(['maker_no' => $makerId], 'メーカーを登録しました', 201);
    } elseif ($method === 'PUT' && $makerId !== null) {
        // 更新
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $updates = [];
        $params = ['maker_no' => $makerId];
        if (isset($input['maker_name'])) {
            $updates[] = "maker_name = :maker_name";
            $params['maker_name'] = $input['maker_name'];
        }
        if (isset($input['maker_cd'])) {
            $updates[] = "maker_cd = :maker_cd";
            $params['maker_cd'] = $input['maker_cd'];
        }
        if (isset($input['sort_no'])) {
            $updates[] = "sort_no = :sort_no";
            $params['sort_no'] = $input['sort_no'];
        }
        if (empty($updates)) {
            ApiResponse::error('更新するフィールドがありません', 400);
        }
        $sql = "UPDATE mst_maker SET " . implode(', ', $updates) . ", upd_dt = NOW() WHERE maker_no = :maker_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        ApiResponse::success(null, 'メーカーを更新しました');
    } elseif ($method === 'DELETE' && $makerId !== null) {
        $sql = "UPDATE mst_maker SET del_flg = 1, del_dt = NOW() WHERE maker_no = :maker_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['maker_no' => $makerId]);
        ApiResponse::success(null, 'メーカーを削除しました');
    } else {
        ApiResponse::methodNotAllowed(['GET', 'POST', 'PUT', 'DELETE']);
    }
}

/**
 * 種別管理
 */
function handleTypes($pdo, $method) {
    if ($method !== 'GET') {
        ApiResponse::methodNotAllowed(['GET']);
    }

    $sql = "SELECT type_no, type_cd, type_name, sort_no FROM mst_type WHERE del_flg = 0 ORDER BY sort_no, type_no";
    $stmt = $pdo->query($sql);
    $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ApiResponse::success($types);
}

/**
 * 単位管理
 */
function handleUnits($pdo, $method) {
    if ($method !== 'GET') {
        ApiResponse::methodNotAllowed(['GET']);
    }

    $sql = "SELECT unit_no, unit_cd, unit_name, sort_no FROM mst_unit WHERE del_flg = 0 ORDER BY sort_no, unit_no";
    $stmt = $pdo->query($sql);
    $units = $stmt->fetchAll(PDO::FETCH_ASSOC);
    ApiResponse::success($units);
}

/**
 * コーナー管理
 */
function handleCorners($pdo, $method, $cornerId = null) {
    if ($method === 'GET' && $cornerId === null) {
        // 一覧
        $sql = "SELECT corner_no, corner_cd, corner_name, category, sort_no, del_flg FROM mst_corner WHERE del_flg = 0 ORDER BY category, sort_no, corner_no";
        $stmt = $pdo->query($sql);
        $corners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
        foreach ($corners as &$corner) {
            $corner['category_name'] = $categoryNames[$corner['category']] ?? '';
        }

        ApiResponse::success($corners);
    } elseif ($method === 'GET' && $cornerId !== null) {
        // 詳細
        $sql = "SELECT * FROM mst_corner WHERE corner_no = :corner_no AND del_flg = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['corner_no' => $cornerId]);
        $corner = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$corner) {
            ApiResponse::notFound('コーナーが見つかりません');
        }
        ApiResponse::success($corner);
    } elseif ($method === 'POST') {
        // 登録
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $validator = new ApiValidator($input);
        $validator->required('corner_name', 'コーナー名は必須です')
                  ->required('category', 'カテゴリは必須です')
                  ->in('category', ['1', '2'], 'カテゴリは1(パチンコ)または2(スロット)を指定してください');
        if (!$validator->validate()) {
            ApiResponse::validationError($validator->getErrors());
        }

        $sql = "INSERT INTO mst_corner (corner_cd, corner_name, category, sort_no, del_flg, add_dt) VALUES (:corner_cd, :corner_name, :category, :sort_no, 0, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'corner_cd' => $input['corner_cd'] ?? '',
            'corner_name' => $input['corner_name'],
            'category' => $input['category'],
            'sort_no' => $input['sort_no'] ?? 0
        ]);
        $cornerId = $pdo->lastInsertId();
        ApiResponse::success(['corner_no' => $cornerId], 'コーナーを登録しました', 201);
    } elseif ($method === 'PUT' && $cornerId !== null) {
        // 更新
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $updates = [];
        $params = ['corner_no' => $cornerId];
        if (isset($input['corner_name'])) {
            $updates[] = "corner_name = :corner_name";
            $params['corner_name'] = $input['corner_name'];
        }
        if (isset($input['corner_cd'])) {
            $updates[] = "corner_cd = :corner_cd";
            $params['corner_cd'] = $input['corner_cd'];
        }
        if (isset($input['category'])) {
            $updates[] = "category = :category";
            $params['category'] = $input['category'];
        }
        if (isset($input['sort_no'])) {
            $updates[] = "sort_no = :sort_no";
            $params['sort_no'] = $input['sort_no'];
        }
        if (empty($updates)) {
            ApiResponse::error('更新するフィールドがありません', 400);
        }
        $sql = "UPDATE mst_corner SET " . implode(', ', $updates) . ", upd_dt = NOW() WHERE corner_no = :corner_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        ApiResponse::success(null, 'コーナーを更新しました');
    } elseif ($method === 'DELETE' && $cornerId !== null) {
        $sql = "UPDATE mst_corner SET del_flg = 1, del_dt = NOW() WHERE corner_no = :corner_no";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['corner_no' => $cornerId]);
        ApiResponse::success(null, 'コーナーを削除しました');
    } else {
        ApiResponse::methodNotAllowed(['GET', 'POST', 'PUT', 'DELETE']);
    }
}

/**
 * オーナー管理
 */
function handleOwners($pdo, $method, $ownerId = null) {
    if ($method === 'GET' && $ownerId === null) {
        // 一覧
        $sql = "SELECT owner_no, owner_cd, owner_name, owner_nickname, mail, tel, status FROM mst_owner WHERE del_flg = 0 ORDER BY owner_no";
        $stmt = $pdo->query($sql);
        $owners = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::success($owners);
    } elseif ($method === 'GET' && $ownerId !== null) {
        // 詳細
        $sql = "SELECT * FROM mst_owner WHERE owner_no = :owner_no AND del_flg = 0";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['owner_no' => $ownerId]);
        $owner = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$owner) {
            ApiResponse::notFound('オーナーが見つかりません');
        }
        ApiResponse::success($owner);
    } else {
        ApiResponse::methodNotAllowed(['GET']);
    }
}

/**
 * カテゴリ一覧
 */
function handleCategories($pdo, $method) {
    if ($method !== 'GET') {
        ApiResponse::methodNotAllowed(['GET']);
    }

    ApiResponse::success([
        ['id' => '1', 'name' => 'パチンコ'],
        ['id' => '2', 'name' => 'スロット']
    ]);
}
