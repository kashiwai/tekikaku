<?php
/**
 * list_machines.php
 *
 * 機械（台）リスト取得API
 * 実際の物理的な台の情報と稼働状態を取得
 *
 * @version 1.0.0
 * @date 2025-12-29
 */

// CORS設定
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=UTF-8');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// データベース接続
require_once(__DIR__ . '/../../_etc/db_connect.php');

// 認証チェック（簡易版）
$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? '';

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header is required'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = new DBConnect();
    $pdo = $db->getPDO();

    // パラメータ取得
    $modelId = $_GET['modelId'] ?? null;           // 特定機種でフィルタ
    $status = $_GET['status'] ?? null;             // 'available', 'playing', 'maintenance'
    $availableOnly = $_GET['availableOnly'] ?? 'false';  // 利用可能な台のみ
    $limit = min((int)($_GET['limit'] ?? 100), 1000);
    $offset = (int)($_GET['offset'] ?? 0);
    $lang = $_GET['lang'] ?? 'ja';                 // 多言語対応

    // 言語別の機種名カラム選択
    if ($lang === 'ko') {
        $modelNameColumn = 'COALESCE(m.model_name_ko, m.model_name_ja, m.model_name)';
    } else if ($lang === 'en') {
        $modelNameColumn = 'COALESCE(m.model_name_en, m.model_name_ja, m.model_name)';
    } else if ($lang === 'zh') {
        $modelNameColumn = 'COALESCE(m.model_name_zh, m.model_name_ja, m.model_name)';
    } else {
        $modelNameColumn = 'COALESCE(m.model_name_ja, m.model_name)';
    }

    // 画像ベースURL
    $baseUrl = 'https://mgg-webservice-production.up.railway.app';
    $imageBasePath = '/data/img/model/';

    // ベースクエリ
    $sql = "
        SELECT
            dm.machine_no,
            dm.model_no,
            m.model_id,
            {$modelNameColumn} as model_name,
            m.maker,
            CASE
                WHEN m.type = 1 THEN 'pachinko'
                WHEN m.type = 2 THEN 'slot'
                ELSE 'unknown'
            END as category,
            m.image_list,
            m.image_detail,
            m.image_reel,
            dm.camera_no,
            c.mac_address as camera_mac,
            c.peer_id as camera_peer_id,
            c.status as camera_status,
            lm.assign_flg,
            lm.member_no as current_member_no,
            lm.assign_time,
            CASE
                WHEN dm.machine_status = 0 THEN 'inactive'
                WHEN dm.machine_status = 1 AND lm.assign_flg = 0 THEN 'available'
                WHEN dm.machine_status = 1 AND lm.assign_flg = 1 THEN 'playing'
                WHEN dm.machine_status = 2 THEN 'maintenance'
                ELSE 'unknown'
            END as machine_status,
            dm.last_play_time,
            dm.total_games,
            dm.machine_status as status_code
        FROM dat_machine dm
        LEFT JOIN mst_model m ON dm.model_no = m.model_no
        LEFT JOIN mst_camera c ON dm.camera_no = c.camera_no
        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
        WHERE 1=1
    ";

    $params = [];

    // 機種IDでフィルタ
    if ($modelId) {
        $sql .= " AND m.model_id = :modelId";
        $params[':modelId'] = $modelId;
    }

    // ステータスでフィルタ
    if ($status) {
        if ($status === 'available') {
            $sql .= " AND dm.machine_status = 1 AND lm.assign_flg = 0";
        } else if ($status === 'playing') {
            $sql .= " AND dm.machine_status = 1 AND lm.assign_flg = 1";
        } else if ($status === 'maintenance') {
            $sql .= " AND dm.machine_status = 2";
        } else if ($status === 'inactive') {
            $sql .= " AND dm.machine_status = 0";
        }
    }

    // 利用可能な台のみ（稼働中かつ未割り当て）
    if ($availableOnly === 'true') {
        $sql .= " AND dm.machine_status = 1 AND lm.assign_flg = 0";
    }

    // ソート: 台番号順
    $sql .= " ORDER BY dm.machine_no ASC";

    // カウントクエリ（ページネーション用）
    $countSql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_query";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // ページネーション
    $sql .= " LIMIT :limit OFFSET :offset";
    $params[':limit'] = $limit;
    $params[':offset'] = $offset;

    // クエリ実行
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($key, $value);
        }
    }
    $stmt->execute();
    $machines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // データ整形
    $formattedMachines = [];
    $availableCount = 0;
    $playingCount = 0;

    foreach ($machines as $machine) {
        $machineStatus = $machine['machine_status'];

        if ($machineStatus === 'available') {
            $availableCount++;
        } else if ($machineStatus === 'playing') {
            $playingCount++;
        }

        // 画像URL生成
        $thumbnail = null;
        if ($machine['image_list']) {
            if (preg_match('/^https?:\/\//', $machine['image_list'])) {
                $thumbnail = $machine['image_list'];
            } else {
                $thumbnail = $baseUrl . $imageBasePath . $machine['image_list'];
            }
        }

        $detailImage = null;
        if ($machine['image_detail']) {
            if (preg_match('/^https?:\/\//', $machine['image_detail'])) {
                $detailImage = $machine['image_detail'];
            } else {
                $detailImage = $baseUrl . $imageBasePath . $machine['image_detail'];
            }
        }

        $reelImage = null;
        if ($machine['image_reel']) {
            if (preg_match('/^https?:\/\//', $machine['image_reel'])) {
                $reelImage = $machine['image_reel'];
            } else {
                $reelImage = $baseUrl . $imageBasePath . $machine['image_reel'];
            }
        }

        $formattedMachines[] = [
            'machineNo' => (int)$machine['machine_no'],
            'modelNo' => (int)$machine['model_no'],
            'modelId' => $machine['model_id'],
            'modelName' => $machine['model_name'],
            'maker' => $machine['maker'],
            'category' => $machine['category'],
            'status' => $machineStatus,
            'isAvailable' => $machineStatus === 'available',
            'images' => [
                'thumbnail' => $thumbnail,
                'detail' => $detailImage,
                'reel' => $reelImage
            ],
            'camera' => [
                'cameraNo' => (int)$machine['camera_no'],
                'peerId' => $machine['camera_peer_id'],
                'mac' => $machine['camera_mac'],
                'status' => (int)$machine['camera_status']
            ],
            'currentUser' => $machine['assign_flg'] == 1 ? [
                'memberNo' => (int)$machine['current_member_no'],
                'assignedAt' => $machine['assign_time']
            ] : null,
            'stats' => [
                'totalGames' => (int)$machine['total_games'],
                'lastPlayedAt' => $machine['last_play_time']
            ]
        ];
    }

    // レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'total' => (int)$totalCount,
        'available' => $availableCount,
        'playing' => $playingCount,
        'count' => count($formattedMachines),
        'limit' => $limit,
        'offset' => $offset,
        'hasMore' => ($offset + $limit) < $totalCount,
        'machines' => $formattedMachines,
        'language' => $lang
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log("Database error in list_machines.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'DATABASE_ERROR',
        'message' => 'Database query failed'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in list_machines.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'An unexpected error occurred'
    ], JSON_UNESCAPED_UNICODE);
}
?>
