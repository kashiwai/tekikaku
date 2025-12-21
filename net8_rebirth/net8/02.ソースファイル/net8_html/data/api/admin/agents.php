<?php
/**
 * Admin Agents API
 * GET /api/admin/agents - エージェント一覧
 * GET /api/admin/agents?agentId=XXX - 特定エージェント詳細
 *
 * Net8 dat_machine との連携情報も取得
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/../agent/auth.php';

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 認証
$auth = requireAuth(['admin', 'ai']);

$agentId = $_GET['agentId'] ?? '';

if (!empty($agentId)) {
    // 特定エージェント詳細
    $sql = "SELECT
                a.*,
                dm.machine_no,
                mm.model_name,
                (SELECT COUNT(*) FROM command_queue cq WHERE cq.agent_id = a.agent_id AND cq.status = 'pending') as pending_commands,
                (SELECT COUNT(*) FROM command_queue cq WHERE cq.agent_id = a.agent_id AND cq.status = 'done') as completed_commands
            FROM agents a
            LEFT JOIN dat_machine dm ON CAST(SUBSTRING_INDEX(a.agent_id, '-', -1) AS UNSIGNED) = dm.machine_no
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            WHERE a.agent_id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $agentId]);
    $agent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$agent) {
        http_response_code(404);
        echo json_encode(['error' => 'Agent not found']);
        exit;
    }

    // 最近のコマンド履歴
    $histSql = "SELECT cq.*, cr.output, cr.exit_code
                FROM command_queue cq
                LEFT JOIN command_results cr ON cq.id = cr.command_id
                WHERE cq.agent_id = :id
                ORDER BY cq.created_at DESC
                LIMIT 10";
    $histStmt = $pdo->prepare($histSql);
    $histStmt->execute([':id' => $agentId]);
    $agent['recent_commands'] = $histStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'agent' => $agent
    ]);
} else {
    // エージェント一覧
    $sql = "SELECT
                a.*,
                dm.machine_no,
                mm.model_name,
                TIMESTAMPDIFF(MINUTE, a.last_seen, NOW()) as minutes_since_seen,
                (SELECT COUNT(*) FROM command_queue cq WHERE cq.agent_id = a.agent_id AND cq.status = 'pending') as pending_commands
            FROM agents a
            LEFT JOIN dat_machine dm ON CAST(SUBSTRING_INDEX(a.agent_id, '-', -1) AS UNSIGNED) = dm.machine_no
            LEFT JOIN mst_model mm ON dm.model_no = mm.model_no
            ORDER BY a.agent_id";

    $stmt = $pdo->query($sql);
    $agents = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // オンライン判定（5分以上通信なしはoffline）
    foreach ($agents as &$agent) {
        if ($agent['minutes_since_seen'] !== null && $agent['minutes_since_seen'] > 5) {
            $agent['status'] = 'offline';
        }
    }

    // サマリー
    $onlineCount = count(array_filter($agents, function($a) { return $a['status'] === 'online'; }));
    $offlineCount = count($agents) - $onlineCount;

    echo json_encode([
        'success' => true,
        'summary' => [
            'total' => count($agents),
            'online' => $onlineCount,
            'offline' => $offlineCount
        ],
        'agents' => $agents
    ]);
}
