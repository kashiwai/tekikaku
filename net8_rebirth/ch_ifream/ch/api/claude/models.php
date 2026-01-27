<?php
/**
 * NET8 Claude Code API - Models (機種) Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET    /api/claude/models              - 機種一覧
 * POST   /api/claude/models              - 新規登録
 * GET    /api/claude/models/{id}         - 詳細取得
 * PUT    /api/claude/models/{id}         - 更新
 * DELETE /api/claude/models/{id}         - 削除
 * GET    /api/claude/models/{id}/settings - 設定取得
 * PUT    /api/claude/models/{id}/settings - 設定更新
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
    $modelNo = $pathParts[1] ?? null;
    $action = $pathParts[2] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($modelNo === null) {
        if ($method === 'GET') {
            listModels($pdo);
        } elseif ($method === 'POST') {
            createModel($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'POST']);
        }
    } elseif ($action === 'settings') {
        if ($method === 'GET') {
            getModelSettings($pdo, $modelNo);
        } elseif ($method === 'PUT') {
            updateModelSettings($pdo, $modelNo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT']);
        }
    } else {
        if ($method === 'GET') {
            getModel($pdo, $modelNo);
        } elseif ($method === 'PUT') {
            updateModel($pdo, $modelNo);
        } elseif ($method === 'DELETE') {
            deleteModel($pdo, $modelNo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT', 'DELETE']);
        }
    }

} catch (Exception $e) {
    error_log('Claude Models API Error: ' . $e->getMessage());
    ApiResponse::serverError('機種処理中にエラーが発生しました');
}

/**
 * 機種一覧取得
 */
function listModels($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $category = $_GET['category'] ?? '';
    $makerNo = $_GET['maker_no'] ?? '';
    $typeNo = $_GET['type_no'] ?? '';
    $search = $_GET['search'] ?? '';

    $where = "mo.del_flg = 0";
    $params = [];

    if ($category !== '') {
        $where .= " AND mo.category = :category";
        $params['category'] = $category;
    }
    if ($makerNo !== '') {
        $where .= " AND mo.maker_no = :maker_no";
        $params['maker_no'] = $makerNo;
    }
    if ($typeNo !== '') {
        $where .= " AND mo.type_no = :type_no";
        $params['type_no'] = $typeNo;
    }
    if ($search !== '') {
        $where .= " AND (mo.model_name LIKE :search OR mo.model_cd LIKE :search2)";
        $params['search'] = "%{$search}%";
        $params['search2'] = "%{$search}%";
    }

    // カウント
    $countSql = "SELECT COUNT(*) FROM mst_model mo WHERE {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT
                mo.model_no, mo.model_cd, mo.category, mo.model_name, mo.model_roman,
                mo.maker_no, mo.type_no, mo.unit_no,
                mo.image_list, mo.image_detail, mo.image_reel,
                mo.renchan_games, mo.tenjo_games,
                ma.maker_name,
                mt.type_name,
                mu.unit_name,
                COUNT(dm.machine_no) as machine_count
            FROM mst_model mo
            LEFT JOIN mst_maker ma ON ma.maker_no = mo.maker_no AND ma.del_flg = 0
            LEFT JOIN mst_type mt ON mt.type_no = mo.type_no AND mt.del_flg = 0
            LEFT JOIN mst_unit mu ON mu.unit_no = mo.unit_no AND mu.del_flg = 0
            LEFT JOIN dat_machine dm ON dm.model_no = mo.model_no AND dm.del_flg = 0
            WHERE {$where}
            GROUP BY mo.model_no
            ORDER BY mo.model_no DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カテゴリ名追加
    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($models as &$model) {
        $model['category_name'] = $categoryNames[$model['category']] ?? '';
    }

    ApiResponse::list($models, $total, $page, $perPage);
}

/**
 * 機種詳細取得
 */
function getModel($pdo, $modelNo) {
    $sql = "SELECT
                mo.*,
                ma.maker_name, ma.maker_roman,
                mt.type_name, mt.type_roman,
                mu.unit_name, mu.unit_roman
            FROM mst_model mo
            LEFT JOIN mst_maker ma ON ma.maker_no = mo.maker_no
            LEFT JOIN mst_type mt ON mt.type_no = mo.type_no
            LEFT JOIN mst_unit mu ON mu.unit_no = mo.unit_no
            WHERE mo.model_no = :model_no AND mo.del_flg = 0";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        ApiResponse::notFound('機種が見つかりません');
    }

    // 賞球データをデコード
    if (!empty($model['prizeball_data'])) {
        $model['prizeball_data'] = json_decode($model['prizeball_data'], true);
    }
    // レイアウトデータをデコード
    if (!empty($model['layout_data'])) {
        $model['layout_data'] = json_decode($model['layout_data'], true);
    }

    // 紐づく台数
    $sql = "SELECT COUNT(*) FROM dat_machine WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $model['machine_count'] = $stmt->fetchColumn();

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    $model['category_name'] = $categoryNames[$model['category']] ?? '';

    ApiResponse::success($model);
}

/**
 * 機種新規登録
 */
function createModel($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('category', 'カテゴリは必須です')
              ->in('category', ['1', '2'], 'カテゴリは1(パチンコ)または2(スロット)を指定してください')
              ->required('model_cd', '機種コードは必須です')
              ->alphaNum('model_cd', '機種コードは半角英数字で入力してください')
              ->maxLength('model_cd', 20, '機種コードは20文字以内で入力してください')
              ->required('model_name', '機種名は必須です')
              ->maxLength('model_name', 50, '機種名は50文字以内で入力してください')
              ->required('model_roman', '機種名(英語)は必須です')
              ->maxLength('model_roman', 200, '機種名(英語)は200文字以内で入力してください')
              ->required('type_no', 'タイプは必須です')
              ->required('maker_no', 'メーカーは必須です');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    // 機種名重複チェック
    $sql = "SELECT COUNT(*) FROM mst_model WHERE model_name = :name AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['name' => $input['model_name']]);
    if ($stmt->fetchColumn() > 0) {
        ApiResponse::error('この機種名は既に登録されています', 400, 'DUPLICATE_NAME');
    }

    // 賞球データ（パチンコの場合）
    $prizeballData = '';
    if ($input['category'] == '1' && isset($input['prizeball_data'])) {
        $prizeballData = json_encode($input['prizeball_data']);
    }

    // レイアウトデータ
    $layoutData = json_encode([
        'video_portrait' => 0,
        'video_mode' => 4,
        'drum' => 0,
        'bonus_push' => [],
        'version' => $input['board_ver'] ?? 1,
        'hide' => []
    ]);

    $sql = "INSERT INTO mst_model (
                category, model_cd, model_name, model_roman,
                type_no, unit_no, maker_no,
                renchan_games, tenjo_games,
                prizeball_data, layout_data,
                remarks, del_flg, add_dt, upd_dt
            ) VALUES (
                :category, :model_cd, :model_name, :model_roman,
                :type_no, :unit_no, :maker_no,
                :renchan_games, :tenjo_games,
                :prizeball_data, :layout_data,
                :remarks, 0, NOW(), NOW()
            )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'category' => $input['category'],
        'model_cd' => $input['model_cd'],
        'model_name' => $input['model_name'],
        'model_roman' => $input['model_roman'],
        'type_no' => $input['type_no'],
        'unit_no' => $input['unit_no'] ?? null,
        'maker_no' => $input['maker_no'],
        'renchan_games' => $input['renchan_games'] ?? 0,
        'tenjo_games' => $input['tenjo_games'] ?? 9999,
        'prizeball_data' => $prizeballData,
        'layout_data' => $layoutData,
        'remarks' => $input['remarks'] ?? ''
    ]);

    $modelNo = $pdo->lastInsertId();

    // 作成したデータを取得
    getModel($pdo, $modelNo);
}

/**
 * 機種更新
 */
function updateModel($pdo, $modelNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    // 存在チェック
    $sql = "SELECT * FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        ApiResponse::notFound('機種が見つかりません');
    }

    // 紐づく台がある場合はカテゴリ変更不可
    $sql = "SELECT COUNT(*) FROM dat_machine WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $machineCount = $stmt->fetchColumn();

    if ($machineCount > 0 && isset($input['category']) && $input['category'] != $model['category']) {
        ApiResponse::error('台が紐づいているため、カテゴリは変更できません', 400, 'CATEGORY_LOCKED');
    }

    $updates = [];
    $params = ['model_no' => $modelNo];

    $updateFields = [
        'category', 'model_cd', 'model_name', 'model_roman',
        'type_no', 'unit_no', 'maker_no',
        'renchan_games', 'tenjo_games', 'remarks',
        'image_list', 'image_detail', 'image_reel'
    ];

    foreach ($updateFields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[$field] = $input[$field];
        }
    }

    // 賞球データ
    if (isset($input['prizeball_data'])) {
        $updates[] = "prizeball_data = :prizeball_data";
        $params['prizeball_data'] = json_encode($input['prizeball_data']);
    }

    // レイアウトデータ
    if (isset($input['layout_data'])) {
        $updates[] = "layout_data = :layout_data";
        $params['layout_data'] = json_encode($input['layout_data']);
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $updates[] = "upd_dt = NOW()";
    $sql = "UPDATE mst_model SET " . implode(', ', $updates) . " WHERE model_no = :model_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    getModel($pdo, $modelNo);
}

/**
 * 機種削除
 */
function deleteModel($pdo, $modelNo) {
    // 存在チェック
    $sql = "SELECT * FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        ApiResponse::notFound('機種が見つかりません');
    }

    // 紐づく台があれば削除不可
    $sql = "SELECT COUNT(*) FROM dat_machine WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    if ($stmt->fetchColumn() > 0) {
        ApiResponse::error('台が紐づいているため削除できません', 400, 'HAS_MACHINES');
    }

    // 論理削除
    $sql = "UPDATE mst_model SET del_flg = 1, del_dt = NOW() WHERE model_no = :model_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);

    ApiResponse::success(null, '機種を削除しました');
}

/**
 * 機種設定取得
 */
function getModelSettings($pdo, $modelNo) {
    $sql = "SELECT prizeball_data, layout_data, setting_list FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    $model = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$model) {
        ApiResponse::notFound('機種が見つかりません');
    }

    ApiResponse::success([
        'model_no' => $modelNo,
        'prizeball_data' => json_decode($model['prizeball_data'], true),
        'layout_data' => json_decode($model['layout_data'], true),
        'setting_list' => $model['setting_list'] ? explode(',', $model['setting_list']) : []
    ]);
}

/**
 * 機種設定更新
 */
function updateModelSettings($pdo, $modelNo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $sql = "SELECT * FROM mst_model WHERE model_no = :model_no AND del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['model_no' => $modelNo]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('機種が見つかりません');
    }

    $updates = [];
    $params = ['model_no' => $modelNo];

    if (isset($input['prizeball_data'])) {
        $updates[] = "prizeball_data = :prizeball_data";
        $params['prizeball_data'] = json_encode($input['prizeball_data']);
    }
    if (isset($input['layout_data'])) {
        $updates[] = "layout_data = :layout_data";
        $params['layout_data'] = json_encode($input['layout_data']);
    }
    if (isset($input['setting_list'])) {
        $updates[] = "setting_list = :setting_list";
        $params['setting_list'] = is_array($input['setting_list']) ? implode(',', $input['setting_list']) : $input['setting_list'];
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $updates[] = "upd_dt = NOW()";
    $sql = "UPDATE mst_model SET " . implode(', ', $updates) . " WHERE model_no = :model_no";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    getModelSettings($pdo, $modelNo);
}
