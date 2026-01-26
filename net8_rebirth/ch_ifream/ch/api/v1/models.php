<?php
/**
 * NET8 SDK API - Models List Endpoint
 *
 * 外部システム（korea_net8front等）からモデル一覧と空き状況を取得
 *
 * Version: 1.1.0
 * Created: 2025-11-06
 * Updated: 2025-12-17 - 利用可能台数、使用中台数、準備中台数を追加
 *
 * レスポンス例:
 * {
 *   "success": true,
 *   "models": [
 *     {
 *       "id": "HKT001",
 *       "modelNo": 1,
 *       "name": "北斗の拳",
 *       "category": "slot",
 *       "totalMachines": 3,
 *       "availableMachines": 2,
 *       "inUseMachines": 1,
 *       "preparingMachines": 0,
 *       "hasAvailable": true,
 *       "status": "available",       // available, full, preparing
 *       "thumbnail": "https://...",
 *       "detailImage": "https://..."
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
$category = $_GET['category'] ?? null;      // slot, pachinko でフィルタ
$onlyAvailable = isset($_GET['onlyAvailable']) && $_GET['onlyAvailable'] === 'true';

try {
    $pdo = get_db_connection();

    // 営業時間チェック
    $today = date("H:i");
    $isOpen = true;
    if (defined('GLOBAL_OPEN_TIME') && defined('GLOBAL_CLOSE_TIME')) {
        $isOpen = (strtotime(GLOBAL_OPEN_TIME.':00') <= strtotime($today.':00') ||
                   strtotime(GLOBAL_CLOSE_TIME.':00') > strtotime($today.':00'));
    }

    // モデル一覧と台数を取得
    $sql = "
        SELECT
            mm.model_no,
            mm.model_cd,
            mm.model_name,
            mm.model_roman,
            mm.category,
            mm.maker_no,
            mm.image_list,
            mm.image_detail,
            mm.image_reel,
            mm.prizeball_data,
            mm.layout_data,
            COUNT(dm.machine_no) as total_machines,
            SUM(CASE
                WHEN dm.machine_status = 1 AND (lm.assign_flg = 0 OR lm.assign_flg IS NULL) AND COALESCE(lm.assign_flg, 0) != 9
                THEN 1 ELSE 0
            END) as available_machines,
            SUM(CASE
                WHEN lm.assign_flg = 1 THEN 1 ELSE 0
            END) as in_use_machines
        FROM mst_model mm
        LEFT JOIN dat_machine dm ON mm.model_no = dm.model_no
            AND dm.del_flg = 0
            AND dm.release_date <= CURDATE()
            AND dm.end_date >= CURDATE()
        LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no
        WHERE mm.del_flg = 0
    ";

    // カテゴリでフィルタ
    if ($category) {
        if ($category === 'slot') {
            $sql .= " AND mm.category = 2";
        } elseif ($category === 'pachinko') {
            $sql .= " AND mm.category = 1";
        }
    }

    $sql .= " GROUP BY mm.model_no, mm.model_cd, mm.model_name, mm.model_roman,
              mm.category, mm.maker_no, mm.image_list, mm.image_detail, mm.image_reel,
              mm.prizeball_data, mm.layout_data";

    // 空きがあるもののみ表示
    if ($onlyAvailable) {
        $sql .= " HAVING available_machines > 0";
    }

    $sql .= " ORDER BY mm.category, mm.model_name";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 画像のベースURL
    $baseUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'mgg-webservice-production.up.railway.app');
    $imageBasePath = '/img/model/';

    // カテゴリー変換
    $categoryMap = [
        1 => 'pachinko',
        2 => 'slot'
    ];

    // レスポンス整形
    $response = array_map(function($model) use ($categoryMap, $baseUrl, $imageBasePath, $isOpen) {
        // 画像パス処理
        $thumbnail = null;
        if ($model['image_list']) {
            if (preg_match('/^https?:\/\//', $model['image_list'])) {
                $thumbnail = $model['image_list'];
            } else {
                $thumbnail = $baseUrl . $imageBasePath . $model['image_list'];
            }
        }

        $detailImage = null;
        if ($model['image_detail']) {
            if (preg_match('/^https?:\/\//', $model['image_detail'])) {
                $detailImage = $model['image_detail'];
            } else {
                $detailImage = $baseUrl . $imageBasePath . $model['image_detail'];
            }
        }

        $reelImage = null;
        if ($model['image_reel']) {
            if (preg_match('/^https?:\/\//', $model['image_reel'])) {
                $reelImage = $model['image_reel'];
            } else {
                $reelImage = $baseUrl . $imageBasePath . $model['image_reel'];
            }
        }

        // 台数計算
        $totalMachines = (int)$model['total_machines'];
        $availableMachines = (int)$model['available_machines'];
        $inUseMachines = (int)$model['in_use_machines'];
        $preparingMachines = $totalMachines - $availableMachines - $inUseMachines;
        if ($preparingMachines < 0) $preparingMachines = 0;

        // 営業時間外は利用可能台数を0にする
        if (!$isOpen) {
            $availableMachines = 0;
        }

        // ステータス判定
        $status = 'preparing';  // デフォルト
        if ($availableMachines > 0) {
            $status = 'available';      // 利用可能
        } elseif ($inUseMachines > 0 && $availableMachines === 0) {
            $status = 'full';           // 満席（全て使用中）
        }

        return [
            'id' => $model['model_cd'] ?: (string)$model['model_no'],
            'modelNo' => (int)$model['model_no'],
            'name' => $model['model_name'],
            'nameEn' => $model['model_roman'],
            'category' => $categoryMap[$model['category']] ?? 'unknown',
            'maker' => getMakerName($model['maker_no']),
            'totalMachines' => $totalMachines,
            'availableMachines' => $availableMachines,
            'inUseMachines' => $inUseMachines,
            'preparingMachines' => $preparingMachines,
            'hasAvailable' => $availableMachines > 0,
            'status' => $status,
            'statusName' => getStatusName($status),
            'thumbnail' => $thumbnail,
            'detailImage' => $detailImage,
            'reelImage' => $reelImage,
            'specs' => [
                'prizeballData' => $model['prizeball_data'],
                'layoutData' => $model['layout_data']
            ]
        ];
    }, $models);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'count' => count($response),
        'isOpen' => $isOpen,
        'timestamp' => date('c'),
        'models' => $response
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    error_log('Models API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to fetch models'
    ]);
}

/**
 * メーカー名取得（簡易版）
 */
function getMakerName($makerNo) {
    $makers = [
        1 => 'サミー',
        2 => 'ユニバーサル',
        3 => '平和',
        4 => '三洋',
        5 => 'その他'
    ];
    return $makers[$makerNo] ?? '不明';
}

/**
 * ステータス名取得
 */
function getStatusName($status) {
    $statusNames = [
        'available' => '利用可能',
        'full' => '満席',
        'preparing' => '準備中',
        'in_use' => '使用中'
    ];
    return $statusNames[$status] ?? '不明';
}
