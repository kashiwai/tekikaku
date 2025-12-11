<?php
/**
 * NET8 Claude Code API - Sales Management
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/sales              - 売上一覧
 * GET  /api/claude/sales/summary      - 売上サマリー
 * GET  /api/claude/sales/by-date      - 日別売上
 * GET  /api/claude/sales/by-model     - 機種別売上
 * GET  /api/claude/sales/by-machine   - 台別売上
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-API-Key');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/helpers/ApiResponse.php';
require_once __DIR__ . '/helpers/ClaudeAuth.php';
require_once __DIR__ . '/../../_etc/require_files.php';

try {
    $pdo = get_db_connection();
    $auth = new ClaudeAuth($pdo);
    $authData = $auth->requireAuth();

    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $pathParts = array_filter(explode('/', $pathInfo));
    $action = array_values($pathParts)[0] ?? null;

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'GET') {
        ApiResponse::methodNotAllowed(['GET']);
    }

    if ($action === null) {
        listSales($pdo);
    } elseif ($action === 'summary') {
        getSalesSummary($pdo);
    } elseif ($action === 'by-date') {
        getSalesByDate($pdo);
    } elseif ($action === 'by-model') {
        getSalesByModel($pdo);
    } elseif ($action === 'by-machine') {
        getSalesByMachine($pdo);
    } else {
        ApiResponse::notFound('エンドポイントが見つかりません');
    }

} catch (Exception $e) {
    error_log('Claude Sales API Error: ' . $e->getMessage());
    ApiResponse::serverError('売上処理中にエラーが発生しました');
}

/**
 * 売上一覧取得
 */
function listSales($pdo) {
    $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
    $perPage = isset($_GET['per_page']) ? min(100, max(1, (int)$_GET['per_page'])) : 20;
    $offset = ($page - 1) * $perPage;

    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $modelNo = $_GET['model_no'] ?? '';
    $machineNo = $_GET['machine_no'] ?? '';

    $where = "gs.start_dt >= :start_date AND gs.start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY)";
    $params = ['start_date' => $startDate, 'end_date' => $endDate];

    if ($modelNo !== '') {
        $where .= " AND dm.model_no = :model_no";
        $params['model_no'] = $modelNo;
    }
    if ($machineNo !== '') {
        $where .= " AND gs.machine_no = :machine_no";
        $params['machine_no'] = $machineNo;
    }

    // カウント
    $countSql = "SELECT COUNT(*)
                 FROM his_gamesession gs
                 LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
                 WHERE {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetchColumn();

    // データ取得
    $sql = "SELECT
                gs.session_no, gs.member_no, gs.machine_no,
                gs.start_dt, gs.end_dt, gs.status,
                gs.bet_point, gs.win_point, gs.play_count,
                dm.machine_cd,
                mo.model_name, mo.category,
                mm.member_cd, mm.member_nickname
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE {$where}
            ORDER BY gs.start_dt DESC
            LIMIT :limit OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 計算追加
    foreach ($sales as &$sale) {
        $sale['profit'] = ($sale['bet_point'] ?? 0) - ($sale['win_point'] ?? 0);
        $sale['category_name'] = $sale['category'] == '1' ? 'パチンコ' : 'スロット';
    }

    ApiResponse::list($sales, $total, $page, $perPage);
}

/**
 * 売上サマリー取得
 */
function getSalesSummary($pdo) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    $params = ['start_date' => $startDate, 'end_date' => $endDate];

    // 全体サマリー
    $sql = "SELECT
                COUNT(*) as total_sessions,
                COUNT(DISTINCT member_no) as unique_members,
                COUNT(DISTINCT machine_no) as machines_used,
                SUM(bet_point) as total_bet,
                SUM(win_point) as total_win,
                SUM(play_count) as total_plays,
                AVG(TIMESTAMPDIFF(MINUTE, start_dt, end_dt)) as avg_session_minutes
            FROM his_gamesession
            WHERE start_dt >= :start_date
              AND start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY)
              AND status = 'completed'";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);

    $summary['total_profit'] = ($summary['total_bet'] ?? 0) - ($summary['total_win'] ?? 0);
    $summary['profit_rate'] = $summary['total_bet'] > 0
        ? round(($summary['total_profit'] / $summary['total_bet']) * 100, 2)
        : 0;

    // カテゴリ別
    $sql = "SELECT
                mo.category,
                COUNT(*) as sessions,
                SUM(gs.bet_point) as bet,
                SUM(gs.win_point) as win
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            WHERE gs.start_dt >= :start_date
              AND gs.start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY)
              AND gs.status = 'completed'
            GROUP BY mo.category";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $byCategory = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($byCategory as &$cat) {
        $cat['category_name'] = $categoryNames[$cat['category']] ?? '不明';
        $cat['profit'] = ($cat['bet'] ?? 0) - ($cat['win'] ?? 0);
    }

    ApiResponse::success([
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'summary' => $summary,
        'by_category' => $byCategory
    ]);
}

/**
 * 日別売上取得
 */
function getSalesByDate($pdo) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');

    $sql = "SELECT
                DATE(start_dt) as date,
                COUNT(*) as sessions,
                COUNT(DISTINCT member_no) as unique_members,
                SUM(bet_point) as bet,
                SUM(win_point) as win,
                SUM(play_count) as plays
            FROM his_gamesession
            WHERE start_dt >= :start_date
              AND start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY)
              AND status = 'completed'
            GROUP BY DATE(start_dt)
            ORDER BY date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['start_date' => $startDate, 'end_date' => $endDate]);
    $dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dailyStats as &$day) {
        $day['profit'] = ($day['bet'] ?? 0) - ($day['win'] ?? 0);
    }

    ApiResponse::success([
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'daily_stats' => $dailyStats
    ]);
}

/**
 * 機種別売上取得
 */
function getSalesByModel($pdo) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $category = $_GET['category'] ?? '';

    $where = "gs.start_dt >= :start_date AND gs.start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY) AND gs.status = 'completed'";
    $params = ['start_date' => $startDate, 'end_date' => $endDate];

    if ($category !== '') {
        $where .= " AND mo.category = :category";
        $params['category'] = $category;
    }

    $sql = "SELECT
                mo.model_no, mo.model_name, mo.category,
                COUNT(*) as sessions,
                COUNT(DISTINCT gs.member_no) as unique_members,
                SUM(gs.bet_point) as bet,
                SUM(gs.win_point) as win,
                SUM(gs.play_count) as plays
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            WHERE {$where}
            GROUP BY mo.model_no, mo.model_name, mo.category
            ORDER BY SUM(gs.bet_point) - SUM(gs.win_point) DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $modelStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($modelStats as &$model) {
        $model['profit'] = ($model['bet'] ?? 0) - ($model['win'] ?? 0);
        $model['category_name'] = $categoryNames[$model['category']] ?? '';
    }

    ApiResponse::success([
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'model_stats' => $modelStats
    ]);
}

/**
 * 台別売上取得
 */
function getSalesByMachine($pdo) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $modelNo = $_GET['model_no'] ?? '';

    $where = "gs.start_dt >= :start_date AND gs.start_dt < DATE_ADD(:end_date, INTERVAL 1 DAY) AND gs.status = 'completed'";
    $params = ['start_date' => $startDate, 'end_date' => $endDate];

    if ($modelNo !== '') {
        $where .= " AND dm.model_no = :model_no";
        $params['model_no'] = $modelNo;
    }

    $sql = "SELECT
                dm.machine_no, dm.machine_cd,
                mo.model_name, mo.category,
                COUNT(*) as sessions,
                COUNT(DISTINCT gs.member_no) as unique_members,
                SUM(gs.bet_point) as bet,
                SUM(gs.win_point) as win,
                SUM(gs.play_count) as plays,
                AVG(TIMESTAMPDIFF(MINUTE, gs.start_dt, gs.end_dt)) as avg_session_minutes
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            WHERE {$where}
            GROUP BY dm.machine_no, dm.machine_cd, mo.model_name, mo.category
            ORDER BY SUM(gs.bet_point) - SUM(gs.win_point) DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $machineStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($machineStats as &$machine) {
        $machine['profit'] = ($machine['bet'] ?? 0) - ($machine['win'] ?? 0);
        $machine['category_name'] = $categoryNames[$machine['category']] ?? '';
    }

    ApiResponse::success([
        'period' => [
            'start_date' => $startDate,
            'end_date' => $endDate
        ],
        'machine_stats' => $machineStats
    ]);
}
