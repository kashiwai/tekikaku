<?php
/**
 * NET8 SDK API - Machines List Endpoint
 *
 * 外部システム（korea_net8front等）から機種の利用状況を取得
 *
 * Version: 1.0.0
 * Created: 2025-12-17
 *
 * レスポンス例:
 * {
 *   "success": true,
 *   "machines": [
 *     {
 *       "machineNo": 1,
 *       "modelId": "HKT001",
 *       "modelName": "北斗の拳",
 *       "status": "available",       // available, in_use, preparing
 *       "statusName": "利用可能",    // 利用可能, 使用中, 準備中
 *       "canPlay": true,
 *       "images": {
 *         "thumbnail": "https://...",
 *         "detail": "https://...",
 *         "reel": "https://..."
 *       },
 *       "category": "slot",          // slot, pachinko
 *       "cameraId": "camera_001"
 *     }
 *   ]
 * }
 */

header('Content-Type: application/json');

// CORS対応
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/require_files.php');

// オプションパラメータ
$modelId = $_GET['modelId'] ?? null;        // 特定モデルのみ取得
$status = $_GET['status'] ?? null;          // available, in_use, preparing でフィルタ
$category = $_GET['category'] ?? null;      // slot, pachinko でフィルタ

try {
    $pdo = get_db_connection();

    // 営業時間チェック
    $today = date("H:i");
    $isOpen = true; // 基本的に営業中とする（必要に応じて営業時間チェックを追加）
    if (defined('GLOBAL_OPEN_TIME') && defined('GLOBAL_CLOSE_TIME')) {
        $isOpen = (strtotime(GLOBAL_OPEN_TIME.':00') <= strtotime($today.':00') ||
                   strtotime(GLOBAL_CLOSE_TIME.':00') > strtotime($today.':00'));
    }

    // 機種一覧を取得
    $sql = "
        SELECT
            dm.machine_no,
            dm.model_no,
            dm.machine_status,
            mm.model_cd,
            mm.model_name,
            mm.model_roman,
            mm.category,
            mm.image_list,
            mm.image_detail,
            mm.image_reel,
            mc.camera_name,
            COALESCE(lm.assign_flg, 0) as assign_flg,
            lm.member_no as assigned_member
        FROM dat_machine dm
        INNER JOIN mst_model mm ON dm.model_no = mm.model_no
        LEFT JOIN mst_camera mc ON dm.camera_no = mc.camera_no
        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
        WHERE dm.del_flg = 0
        AND mm.del_flg = 0
        AND dm.release_date <= CURDATE()
        AND dm.end_date >= CURDATE()
    ";

    $params = [];

    // モデルIDでフィルタ
    if ($modelId) {
        $sql .= " AND (mm.model_cd = :model_cd OR mm.model_no = :model_no)";
        $params['model_cd'] = $modelId;
        $params['model_no'] = is_numeric($modelId) ? $modelId : 0;
    }

    // カテゴリでフィルタ
    if ($category) {
        if ($category === 'slot') {
            $sql .= " AND mm.category = 2";
        } elseif ($category === 'pachinko') {
            $sql .= " AND mm.category = 1";
        }
    }

    $sql .= " ORDER BY mm.category, dm.machine_no";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 画像のベースURL
    $baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mgg-webservice-production.up.railway.app');
    $imageBasePath = '/img/model/';

    $machines = [];
    foreach ($rows as $row) {
        // ステータス判定
        $assignFlg = (int)$row['assign_flg'];
        $machineStatus = (int)$row['machine_status'];

        // 3つの状態を判定
        $isInUse = ($assignFlg === 1);
        $isLinkMainte = ($assignFlg === 9);
        $isAvailable = ($assignFlg === 0 && $machineStatus === 1 && $isOpen && !$isLinkMainte);
        $isPreparing = !$isInUse && !$isAvailable;

        // ステータス文字列
        if ($isInUse) {
            $statusCode = 'in_use';
            $statusName = '使用中';
        } elseif ($isAvailable) {
            $statusCode = 'available';
            $statusName = '利用可能';
        } else {
            $statusCode = 'preparing';
            $statusName = '準備中';
        }

        // ステータスフィルタ
        if ($status && $status !== $statusCode) {
            continue;
        }

        // 画像URL生成
        $images = [];

        // サムネイル画像
        if (!empty($row['image_list'])) {
            if (preg_match('/^https?:\/\//', $row['image_list'])) {
                $images['thumbnail'] = $row['image_list'];
            } else {
                $images['thumbnail'] = $baseUrl . $imageBasePath . $row['image_list'];
            }
        } else {
            $images['thumbnail'] = null;
        }

        // 詳細画像
        if (!empty($row['image_detail'])) {
            if (preg_match('/^https?:\/\//', $row['image_detail'])) {
                $images['detail'] = $row['image_detail'];
            } else {
                $images['detail'] = $baseUrl . $imageBasePath . $row['image_detail'];
            }
        } else {
            $images['detail'] = null;
        }

        // リール画像
        if (!empty($row['image_reel'])) {
            if (preg_match('/^https?:\/\//', $row['image_reel'])) {
                $images['reel'] = $row['image_reel'];
            } else {
                $images['reel'] = $baseUrl . $imageBasePath . $row['image_reel'];
            }
        } else {
            $images['reel'] = null;
        }

        // カテゴリ名
        $categoryName = ($row['category'] == 1) ? 'pachinko' : 'slot';

        $machines[] = [
            'machineNo' => (int)$row['machine_no'],
            'modelId' => $row['model_cd'] ?: (string)$row['model_no'],
            'modelNo' => (int)$row['model_no'],
            'modelName' => $row['model_name'],
            'modelNameEn' => $row['model_roman'],
            'status' => $statusCode,
            'statusName' => $statusName,
            'canPlay' => $isAvailable,
            'images' => $images,
            'category' => $categoryName,
            'cameraId' => $row['camera_name'] ?: null
        ];
    }

    // レスポンス
    echo json_encode([
        'success' => true,
        'count' => count($machines),
        'isOpen' => $isOpen,
        'timestamp' => date('c'),
        'machines' => $machines
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    error_log("❌ machines API DB error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'DATABASE_ERROR',
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    error_log("❌ machines API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'Internal server error'
    ]);
}
?>
