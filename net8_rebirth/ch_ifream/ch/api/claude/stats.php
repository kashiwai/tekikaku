<?php
/**
 * NET8 Claude Code API - Statistics & Dashboard
 * Version: 1.0.0
 * Created: 2025-12-12
 *
 * GET  /api/claude/stats/dashboard     - ダッシュボード概要
 * GET  /api/claude/stats/realtime      - リアルタイム状況
 * GET  /api/claude/stats/members       - 会員統計
 * GET  /api/claude/stats/machines      - 台統計
 * GET  /api/claude/stats/revenue       - 売上統計
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
    $action = array_values($pathParts)[0] ?? 'dashboard';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'GET') {
        ApiResponse::methodNotAllowed(['GET']);
    }

    switch ($action) {
        case 'dashboard':
            getDashboard($pdo);
            break;
        case 'realtime':
            getRealtimeStats($pdo);
            break;
        case 'members':
            getMemberStats($pdo);
            break;
        case 'machines':
            getMachineStats($pdo);
            break;
        case 'revenue':
            getRevenueStats($pdo);
            break;
        default:
            ApiResponse::notFound('統計エンドポイントが見つかりません');
    }

} catch (Exception $e) {
    error_log('Claude Stats API Error: ' . $e->getMessage());
    ApiResponse::serverError('統計処理中にエラーが発生しました');
}

/**
 * ダッシュボード概要
 */
function getDashboard($pdo) {
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $thisMonth = date('Y-m-01');

    // 会員数
    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN DATE(add_dt) = :today THEN 1 ELSE 0 END) as new_today
            FROM mst_member WHERE del_flg = 0";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $today]);
    $members = $stmt->fetch(PDO::FETCH_ASSOC);

    // 台数
    $sql = "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN machine_status = '1' THEN 1 ELSE 0 END) as operational,
                SUM(CASE WHEN assign_flg = '1' THEN 1 ELSE 0 END) as in_use
            FROM dat_machine WHERE del_flg = 0";
    $stmt = $pdo->query($sql);
    $machines = $stmt->fetch(PDO::FETCH_ASSOC);

    // 今日の売上
    $sql = "SELECT
                COUNT(*) as sessions,
                COALESCE(SUM(bet_point), 0) as bet,
                COALESCE(SUM(win_point), 0) as win
            FROM his_gamesession
            WHERE DATE(start_dt) = :today AND status = 'completed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['today' => $today]);
    $todaySales = $stmt->fetch(PDO::FETCH_ASSOC);
    $todaySales['profit'] = $todaySales['bet'] - $todaySales['win'];

    // 昨日の売上（比較用）
    $sql = "SELECT
                COUNT(*) as sessions,
                COALESCE(SUM(bet_point), 0) as bet,
                COALESCE(SUM(win_point), 0) as win
            FROM his_gamesession
            WHERE DATE(start_dt) = :yesterday AND status = 'completed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['yesterday' => $yesterday]);
    $yesterdaySales = $stmt->fetch(PDO::FETCH_ASSOC);
    $yesterdaySales['profit'] = $yesterdaySales['bet'] - $yesterdaySales['win'];

    // 今月の売上
    $sql = "SELECT
                COUNT(*) as sessions,
                COALESCE(SUM(bet_point), 0) as bet,
                COALESCE(SUM(win_point), 0) as win
            FROM his_gamesession
            WHERE start_dt >= :month_start AND status = 'completed'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['month_start' => $thisMonth]);
    $monthSales = $stmt->fetch(PDO::FETCH_ASSOC);
    $monthSales['profit'] = $monthSales['bet'] - $monthSales['win'];

    // アクティブセッション数
    $sql = "SELECT COUNT(*) FROM his_gamesession WHERE status = 'playing'";
    $stmt = $pdo->query($sql);
    $activeSessions = $stmt->fetchColumn();

    ApiResponse::success([
        'timestamp' => date('Y-m-d H:i:s'),
        'members' => $members,
        'machines' => $machines,
        'active_sessions' => (int)$activeSessions,
        'today' => $todaySales,
        'yesterday' => $yesterdaySales,
        'this_month' => $monthSales,
        'comparison' => [
            'sessions_change' => $yesterdaySales['sessions'] > 0
                ? round((($todaySales['sessions'] - $yesterdaySales['sessions']) / $yesterdaySales['sessions']) * 100, 1)
                : null,
            'profit_change' => $yesterdaySales['profit'] != 0
                ? round((($todaySales['profit'] - $yesterdaySales['profit']) / abs($yesterdaySales['profit'])) * 100, 1)
                : null
        ]
    ]);
}

/**
 * リアルタイム状況
 */
function getRealtimeStats($pdo) {
    // アクティブセッション
    $sql = "SELECT
                gs.session_no, gs.member_no, gs.machine_no, gs.start_dt,
                gs.bet_point, gs.win_point, gs.play_count,
                dm.machine_cd,
                mo.model_name, mo.category,
                mm.member_nickname
            FROM his_gamesession gs
            LEFT JOIN dat_machine dm ON dm.machine_no = gs.machine_no
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN mst_member mm ON mm.member_no = gs.member_no
            WHERE gs.status = 'playing'
            ORDER BY gs.start_dt ASC";
    $stmt = $pdo->query($sql);
    $activeSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($activeSessions as &$s) {
        if ($s['start_dt']) {
            $start = new DateTime($s['start_dt']);
            $now = new DateTime();
            $s['elapsed_minutes'] = round(($now->getTimestamp() - $start->getTimestamp()) / 60, 1);
        }
        $s['category_name'] = $s['category'] == '1' ? 'パチンコ' : 'スロット';
    }

    // 台状況サマリー
    $sql = "SELECT
                machine_status,
                COUNT(*) as count
            FROM dat_machine WHERE del_flg = 0
            GROUP BY machine_status";
    $stmt = $pdo->query($sql);
    $machineStatus = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    // 最近のセッション（過去1時間）
    $sql = "SELECT COUNT(*) FROM his_gamesession WHERE start_dt >= DATE_SUB(NOW(), INTERVAL 1 HOUR)";
    $stmt = $pdo->query($sql);
    $recentSessions = $stmt->fetchColumn();

    ApiResponse::success([
        'timestamp' => date('Y-m-d H:i:s'),
        'active_sessions' => [
            'count' => count($activeSessions),
            'sessions' => $activeSessions
        ],
        'machine_status' => [
            'preparing' => (int)($machineStatus['0'] ?? 0),
            'operational' => (int)($machineStatus['1'] ?? 0),
            'maintenance' => (int)($machineStatus['2'] ?? 0)
        ],
        'recent_sessions_1h' => (int)$recentSessions
    ]);
}

/**
 * 会員統計
 */
function getMemberStats($pdo) {
    $days = isset($_GET['days']) ? min(90, max(1, (int)$_GET['days'])) : 30;

    // 会員数推移
    $sql = "SELECT
                DATE(add_dt) as date,
                COUNT(*) as new_members
            FROM mst_member
            WHERE del_flg = 0 AND add_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)
            GROUP BY DATE(add_dt)
            ORDER BY date";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $memberGrowth = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // ステータス別
    $sql = "SELECT
                status,
                COUNT(*) as count
            FROM mst_member WHERE del_flg = 0
            GROUP BY status";
    $stmt = $pdo->query($sql);
    $statusData = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $statusNames = ['0' => '仮登録', '1' => 'アクティブ', '2' => '停止'];

    // アクティブユーザー（過去30日にプレイ）
    $sql = "SELECT COUNT(DISTINCT member_no) FROM his_gamesession WHERE start_dt >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $pdo->query($sql);
    $activeUsers30d = $stmt->fetchColumn();

    // ポイント統計
    $sql = "SELECT
                SUM(playpoint) as total_points,
                AVG(playpoint) as avg_points,
                MAX(playpoint) as max_points
            FROM mst_member WHERE del_flg = 0 AND status = '1'";
    $stmt = $pdo->query($sql);
    $pointStats = $stmt->fetch(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'period_days' => $days,
        'status_breakdown' => [
            'provisional' => (int)($statusData['0'] ?? 0),
            'active' => (int)($statusData['1'] ?? 0),
            'suspended' => (int)($statusData['2'] ?? 0)
        ],
        'active_users_30d' => (int)$activeUsers30d,
        'point_stats' => $pointStats,
        'growth' => $memberGrowth
    ]);
}

/**
 * 台統計
 */
function getMachineStats($pdo) {
    $days = isset($_GET['days']) ? min(90, max(1, (int)$_GET['days'])) : 30;

    // カテゴリ別台数
    $sql = "SELECT
                mo.category,
                COUNT(*) as count,
                SUM(CASE WHEN dm.machine_status = '1' THEN 1 ELSE 0 END) as operational
            FROM dat_machine dm
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            WHERE dm.del_flg = 0
            GROUP BY mo.category";
    $stmt = $pdo->query($sql);
    $categoryData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categoryNames = ['1' => 'パチンコ', '2' => 'スロット'];
    foreach ($categoryData as &$cat) {
        $cat['category_name'] = $categoryNames[$cat['category']] ?? '不明';
    }

    // 稼働率トップ10
    $sql = "SELECT
                dm.machine_no, dm.machine_cd,
                mo.model_name,
                COUNT(gs.session_no) as session_count,
                COALESCE(SUM(gs.bet_point), 0) - COALESCE(SUM(gs.win_point), 0) as profit
            FROM dat_machine dm
            LEFT JOIN mst_model mo ON mo.model_no = dm.model_no
            LEFT JOIN his_gamesession gs ON gs.machine_no = dm.machine_no
                AND gs.start_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)
                AND gs.status = 'completed'
            WHERE dm.del_flg = 0
            GROUP BY dm.machine_no, dm.machine_cd, mo.model_name
            ORDER BY session_count DESC
            LIMIT 10";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $topMachines = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 機種別稼働
    $sql = "SELECT
                mo.model_no, mo.model_name, mo.category,
                COUNT(DISTINCT dm.machine_no) as machine_count,
                COUNT(gs.session_no) as session_count,
                COALESCE(SUM(gs.bet_point), 0) - COALESCE(SUM(gs.win_point), 0) as profit
            FROM mst_model mo
            LEFT JOIN dat_machine dm ON dm.model_no = mo.model_no AND dm.del_flg = 0
            LEFT JOIN his_gamesession gs ON gs.machine_no = dm.machine_no
                AND gs.start_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)
                AND gs.status = 'completed'
            WHERE mo.del_flg = 0
            GROUP BY mo.model_no, mo.model_name, mo.category
            ORDER BY profit DESC
            LIMIT 20";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $modelStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($modelStats as &$m) {
        $m['category_name'] = $categoryNames[$m['category']] ?? '';
    }

    ApiResponse::success([
        'period_days' => $days,
        'by_category' => $categoryData,
        'top_machines' => $topMachines,
        'model_stats' => $modelStats
    ]);
}

/**
 * 売上統計
 */
function getRevenueStats($pdo) {
    $days = isset($_GET['days']) ? min(90, max(1, (int)$_GET['days'])) : 30;

    // 日別売上
    $sql = "SELECT
                DATE(start_dt) as date,
                COUNT(*) as sessions,
                COALESCE(SUM(bet_point), 0) as bet,
                COALESCE(SUM(win_point), 0) as win,
                COUNT(DISTINCT member_no) as unique_members
            FROM his_gamesession
            WHERE start_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND status = 'completed'
            GROUP BY DATE(start_dt)
            ORDER BY date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $dailyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dailyRevenue as &$day) {
        $day['profit'] = $day['bet'] - $day['win'];
    }

    // 期間合計
    $sql = "SELECT
                COUNT(*) as total_sessions,
                COALESCE(SUM(bet_point), 0) as total_bet,
                COALESCE(SUM(win_point), 0) as total_win,
                COUNT(DISTINCT member_no) as unique_members,
                COUNT(DISTINCT machine_no) as machines_used
            FROM his_gamesession
            WHERE start_dt >= DATE_SUB(NOW(), INTERVAL :days DAY)
              AND status = 'completed'";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue('days', $days, PDO::PARAM_INT);
    $stmt->execute();
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $totals['total_profit'] = $totals['total_bet'] - $totals['total_win'];

    // 時間帯別（過去7日）
    $sql = "SELECT
                HOUR(start_dt) as hour,
                COUNT(*) as sessions,
                COALESCE(SUM(bet_point), 0) - COALESCE(SUM(win_point), 0) as profit
            FROM his_gamesession
            WHERE start_dt >= DATE_SUB(NOW(), INTERVAL 7 DAY)
              AND status = 'completed'
            GROUP BY HOUR(start_dt)
            ORDER BY hour";
    $stmt = $pdo->query($sql);
    $hourlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ApiResponse::success([
        'period_days' => $days,
        'totals' => $totals,
        'daily' => $dailyRevenue,
        'hourly_7d' => $hourlyStats
    ]);
}
