<?php
/**
 * NET8 SDK API - Models List Endpoint
 * Version: 1.0.0-beta
 * Created: 2025-11-06
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// OPTIONSリクエスト対応
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// GETメソッドのみ許可
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// 既存の設定ファイル読み込み
require_once('../../_etc/setting.php');
require_once('../../_lib/SmartDB.php');

try {
    $db = new SmartDB(DB_DSN);

    // 機種一覧を取得
    $sql = "SELECT
                model_no as id,
                model_cd as code,
                model_name as name,
                category,
                maker_no,
                image_list as thumbnail,
                image_detail,
                prizeball_data,
                layout_data
            FROM mst_model
            WHERE del_flg = 0
            ORDER BY model_no";

    $models = $db->getAll($sql);

    // カテゴリー変換
    $categoryMap = [
        1 => 'pachinko',
        2 => 'slot'
    ];

    // レスポンス整形
    $response = array_map(function($model) use ($categoryMap) {
        return [
            'id' => $model['code'],
            'name' => $model['name'],
            'category' => $categoryMap[$model['category']] ?? 'unknown',
            'maker' => getMakerName($model['maker_no']),
            'thumbnail' => $model['thumbnail'] ? "/images/models/{$model['thumbnail']}" : null,
            'detailImage' => $model['image_detail'] ? "/images/models/{$model['image_detail']}" : null,
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
        'models' => $response
    ]);

} catch (Exception $e) {
    error_log('Models API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
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
