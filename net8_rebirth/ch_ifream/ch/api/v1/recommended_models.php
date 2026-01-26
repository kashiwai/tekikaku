<?php
/**
 * NET8 SDK API - Recommended Models Endpoint
 * Version: 1.0.0
 * Created: 2025-11-21
 *
 * 残高に応じた推奨機種を返すAPI
 */

header('Content-Type: application/json');

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
require_once('../../_etc/require_files.php');

// 認証ヘッダー確認
$authHeader = '';
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
} elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
    $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
} elseif (function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    if (isset($headers['Authorization'])) {
        $authHeader = $headers['Authorization'];
    }
}

if (empty($authHeader)) {
    http_response_code(401);
    echo json_encode([
        'error' => 'UNAUTHORIZED',
        'message' => 'Authorization header required'
    ]);
    exit;
}

// クエリパラメータ取得
$balance = isset($_GET['balance']) ? (int)$_GET['balance'] : 0;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 3;

// リミット制限
if ($limit > 10) {
    $limit = 10;
}
if ($limit < 1) {
    $limit = 3;
}

try {
    $pdo = get_db_connection();

    // APIキー認証（JWTまたは直接APIキー）
    $apiKeyId = null;

    if (!empty($authHeader) && strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $parts = explode('.', $token);

        // JWT形式の場合（3パート: header.payload.signature）
        if (count($parts) === 3) {
            $payload = json_decode(base64_decode($parts[1]), true);
            if (isset($payload['api_key_id'])) {
                $apiKeyId = $payload['api_key_id'];
            }
        } else {
            // 直接APIキーの場合（pk_demo_12345など）
            $apiKeyStmt = $pdo->prepare("SELECT id FROM api_keys WHERE key_value = :key_value AND is_active = 1");
            $apiKeyStmt->execute(['key_value' => $token]);
            $apiKeyData = $apiKeyStmt->fetch(PDO::FETCH_ASSOC);

            if ($apiKeyData) {
                $apiKeyId = $apiKeyData['id'];
            }
        }
    }

    if (!$apiKeyId) {
        http_response_code(401);
        echo json_encode([
            'error' => 'INVALID_TOKEN',
            'message' => 'Invalid authentication token'
        ]);
        exit;
    }

    // 推奨機種取得クエリ
    // 1. 残高でプレイ可能な機種
    // 2. 空き台がある機種を優先
    // 3. 人気順（ここでは単純にmodel_noの若い順）
    $sql = "SELECT
                mm.model_cd as id,
                mm.model_name as name,
                mm.category,
                mm.image_list as thumbnail,
                mm.maker_no,
                mm.model_no,
                COUNT(dm.machine_no) as total_machines,
                SUM(CASE WHEN lm.assign_flg = 0 OR lm.assign_flg IS NULL THEN 1 ELSE 0 END) as available_machines,
                100 as min_points
            FROM mst_model mm
            LEFT JOIN dat_machine dm ON mm.model_no = dm.model_no AND dm.del_flg = 0
            LEFT JOIN lnk_machine lm ON dm.machine_no = lm.machine_no AND lm.assign_flg = 1
            WHERE mm.del_flg = 0
            GROUP BY mm.model_no, mm.model_cd, mm.model_name, mm.category, mm.image_list, mm.maker_no
            HAVING total_machines > 0
            ORDER BY available_machines DESC, mm.model_no ASC
            LIMIT :limit";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $models = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // カテゴリー変換
    $categoryMap = [
        1 => 'pachinko',
        2 => 'slot'
    ];

    // メーカー名マップ
    $makerMap = [
        1 => 'サミー',
        2 => 'ユニバーサル',
        3 => '平和',
        4 => '三洋',
        5 => 'その他'
    ];

    // レスポンス整形
    $recommendations = array_map(function($model) use ($categoryMap, $makerMap, $balance) {
        $canPlay = $balance >= $model['min_points'];
        $isAvailable = $model['available_machines'] > 0;

        // 画像パス処理: GCS URLの場合はそのまま、相対パスの場合はプレフィックス追加
        $thumbnail = null;
        if ($model['thumbnail']) {
            if (preg_match('/^https?:\/\//', $model['thumbnail'])) {
                // 完全URLの場合はそのまま使用
                $thumbnail = $model['thumbnail'];
            } else {
                // 相対パスの場合はプレフィックス追加
                $thumbnail = '/data/img/model/' . $model['thumbnail'];
            }
        }

        return [
            'id' => $model['id'],
            'name' => $model['name'],
            'category' => $categoryMap[$model['category']] ?? 'unknown',
            'maker' => $makerMap[$model['maker_no']] ?? '不明',
            'thumbnail' => $thumbnail,
            'minPoints' => (int)$model['min_points'],
            'canPlay' => $canPlay,
            'availability' => [
                'total' => (int)$model['total_machines'],
                'available' => (int)$model['available_machines'],
                'isAvailable' => $isAvailable
            ],
            'recommended' => $canPlay && $isAvailable
        ];
    }, $models);

    // レスポンス
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'balance' => $balance,
        'count' => count($recommendations),
        'models' => $recommendations
    ]);

} catch (Exception $e) {
    error_log('Recommended Models API Error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'error' => 'INTERNAL_ERROR',
        'message' => 'Failed to fetch recommended models'
    ]);
}
