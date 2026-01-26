<?php
/**
 * Admin Result API
 * GET /api/admin/result?agentId=CAMERA-001-0068
 * GET /api/admin/result?commandId=123
 *
 * 管理側が実行結果を見る
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../../_etc/require_files.php';
require_once __DIR__ . '/../agent/auth.php';

try {
    $pdo = get_db_connection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 認証（admin または ai のみ）
$auth = requireAuth(['admin', 'ai']);

// パラメータ
$agentId = $_GET['agentId'] ?? '';
$commandId = intval($_GET['commandId'] ?? 0);
$limit = min(intval($_GET['limit'] ?? 10), 100);
$offset = intval($_GET['offset'] ?? 0);

// クエリ構築
$where = [];
$params = [];

if (!empty($agentId)) {
    $where[] = "cr.agent_id = :agent_id";
    $params[':agent_id'] = $agentId;
}

if ($commandId > 0) {
    $where[] = "cr.command_id = :command_id";
    $params[':command_id'] = $commandId;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// 結果取得
$sql = "SELECT
            cr.id as result_id,
            cr.command_id,
            cr.agent_id,
            cr.output,
            cr.exit_code,
            cr.execution_time_ms,
            cr.created_at as executed_at,
            cq.command,
            cq.status,
            cq.created_at as queued_at,
            cq.sent_at,
            cq.completed_at
        FROM command_results cr
        LEFT JOIN command_queue cq ON cr.command_id = cq.id
        $whereClause
        ORDER BY cr.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 総件数取得
$countSql = "SELECT COUNT(*) FROM command_results cr $whereClause";
$countStmt = $pdo->prepare($countSql);
foreach ($params as $key => $value) {
    $countStmt->bindValue($key, $value);
}
$countStmt->execute();
$totalCount = $countStmt->fetchColumn();

echo json_encode([
    'success' => true,
    'total' => intval($totalCount),
    'limit' => $limit,
    'offset' => $offset,
    'results' => $results
]);
