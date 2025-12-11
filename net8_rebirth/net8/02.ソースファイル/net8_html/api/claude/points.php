<?php
/**
 * NET8 Claude Code API - Points Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/points/grant-settings  - ポイント付与設定取得
 * PUT  /api/claude/points/grant-settings  - ポイント付与設定更新
 * POST /api/claude/points/bulk-grant      - 一括ポイント付与
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
    $action = array_values($pathParts)[0] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($action === 'grant-settings') {
        if ($method === 'GET') {
            getGrantSettings($pdo);
        } elseif ($method === 'PUT') {
            updateGrantSettings($pdo);
        } else {
            ApiResponse::methodNotAllowed(['GET', 'PUT']);
        }
    } elseif ($action === 'bulk-grant' && $method === 'POST') {
        bulkGrantPoints($pdo);
    } else {
        ApiResponse::notFound('エンドポイントが見つかりません');
    }

} catch (Exception $e) {
    error_log('Claude Points API Error: ' . $e->getMessage());
    ApiResponse::serverError('ポイント処理中にエラーが発生しました');
}

/**
 * ポイント付与設定取得
 */
function getGrantSettings($pdo) {
    $sql = "SELECT * FROM mst_grantPoint ORDER BY proc_cd";
    $stmt = $pdo->query($sql);
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $procNames = [
        '1' => '新規登録',
        '2' => 'メール認証',
        '3' => 'ログインボーナス',
        '4' => 'デイリーボーナス',
        '5' => '招待ボーナス',
        '99' => '管理者付与'
    ];

    foreach ($settings as &$setting) {
        $setting['proc_name'] = $procNames[$setting['proc_cd']] ?? '不明';
        $setting['has_special'] = !empty($setting['special_start_dt']);
    }

    ApiResponse::success($settings);
}

/**
 * ポイント付与設定更新
 */
function updateGrantSettings($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('proc_cd', 'proc_cdは必須です')
              ->required('point', 'ポイントは必須です')
              ->integer('point', 'ポイントは整数で入力してください');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    $procCd = $input['proc_cd'];

    // 存在チェック
    $sql = "SELECT * FROM mst_grantPoint WHERE proc_cd = :proc_cd";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['proc_cd' => $procCd]);
    if (!$stmt->fetch()) {
        ApiResponse::notFound('設定が見つかりません');
    }

    $updates = [];
    $params = ['proc_cd' => $procCd];

    $fields = ['point', 'limit_days', 'special_point', 'special_start_dt', 'special_end_dt', 'special_limit_days'];
    foreach ($fields as $field) {
        if (isset($input[$field])) {
            $updates[] = "{$field} = :{$field}";
            $params[$field] = $input[$field];
        }
    }

    if (empty($updates)) {
        ApiResponse::error('更新するフィールドがありません', 400);
    }

    $sql = "UPDATE mst_grantPoint SET " . implode(', ', $updates) . " WHERE proc_cd = :proc_cd";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 更新後のデータ取得
    $sql = "SELECT * FROM mst_grantPoint WHERE proc_cd = :proc_cd";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['proc_cd' => $procCd]);
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success($setting, 'ポイント付与設定を更新しました');
}

/**
 * 一括ポイント付与
 */
function bulkGrantPoints($pdo) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];

    $validator = new ApiValidator($input);
    $validator->required('point', 'ポイントは必須です')
              ->integer('point', 'ポイントは整数で入力してください');

    if (!$validator->validate()) {
        ApiResponse::validationError($validator->getErrors());
    }

    $point = (int)$input['point'];
    $reason = $input['reason'] ?? '一括付与';
    $targetType = $input['target_type'] ?? 'all'; // all, active, specific
    $memberNos = $input['member_nos'] ?? [];

    if ($targetType === 'specific' && empty($memberNos)) {
        ApiResponse::error('対象会員を指定してください', 400, 'NO_TARGETS');
    }

    $pdo->beginTransaction();

    try {
        // 対象会員取得
        if ($targetType === 'all') {
            $sql = "SELECT member_no, playpoint, total_playpoint FROM mst_member WHERE del_flg = 0";
            $stmt = $pdo->query($sql);
        } elseif ($targetType === 'active') {
            $sql = "SELECT member_no, playpoint, total_playpoint FROM mst_member WHERE status = '1' AND del_flg = 0";
            $stmt = $pdo->query($sql);
        } else {
            $placeholders = implode(',', array_fill(0, count($memberNos), '?'));
            $sql = "SELECT member_no, playpoint, total_playpoint FROM mst_member WHERE member_no IN ({$placeholders}) AND del_flg = 0";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($memberNos);
        }

        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $successCount = 0;
        $failCount = 0;

        foreach ($members as $member) {
            try {
                $newPoint = $member['playpoint'] + $point;
                $newTotal = $member['total_playpoint'] + ($point > 0 ? $point : 0);

                // ポイント更新
                $sql = "UPDATE mst_member SET playpoint = :playpoint, total_playpoint = :total_playpoint, upd_dt = NOW() WHERE member_no = :member_no";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'playpoint' => $newPoint,
                    'total_playpoint' => $newTotal,
                    'member_no' => $member['member_no']
                ]);

                // 履歴追加
                $sql = "INSERT INTO his_point (member_no, proc_cd, point, balance, remarks, add_dt) VALUES (:member_no, '99', :point, :balance, :remarks, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'member_no' => $member['member_no'],
                    'point' => $point,
                    'balance' => $newPoint,
                    'remarks' => $reason
                ]);

                $successCount++;
            } catch (Exception $e) {
                $failCount++;
            }
        }

        $pdo->commit();

        ApiResponse::success([
            'granted_point' => $point,
            'target_type' => $targetType,
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'reason' => $reason
        ], "一括ポイント付与完了: {$successCount}件成功, {$failCount}件失敗");

    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}
