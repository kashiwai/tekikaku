<?php
/**
 * Agent Pull API
 * GET /api/agent/pull?agentId=CAMERA-001-0068
 *
 * エージェントが定期的にコマンドを取得する
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

require_once __DIR__ . '/../../lib/db.php';
require_once __DIR__ . '/auth.php';

try {
    $pdo = getDbConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// 認証
$auth = requireAuth(['agent', 'admin', 'ai']);

// パラメータ取得
$agentId = $_GET['agentId'] ?? '';

if (empty($agentId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing agentId parameter']);
    exit;
}

// エージェント存在確認・自動登録
ensureAgent($agentId);

// エージェント情報も一緒に更新（IPなど）
if (!empty($_GET['ip']) || !empty($_GET['mac'])) {
    syncWithMachine($agentId, [
        'ip' => $_GET['ip'] ?? null,
        'mac' => $_GET['mac'] ?? null,
        'hostname' => $_GET['hostname'] ?? null
    ]);
}

// 未実行コマンドを取得（優先度高い順、古い順）
$sql = "SELECT id, command, priority, created_at
        FROM command_queue
        WHERE agent_id = :agent_id AND status = 'pending'
        ORDER BY priority DESC, created_at ASC
        LIMIT 1";

$stmt = $pdo->prepare($sql);
$stmt->execute([':agent_id' => $agentId]);
$command = $stmt->fetch(PDO::FETCH_ASSOC);

if ($command) {
    // ステータスを 'sent' に更新
    $updateSql = "UPDATE command_queue SET status = 'sent', sent_at = NOW() WHERE id = :id";
    $pdo->prepare($updateSql)->execute([':id' => $command['id']]);

    echo json_encode([
        'success' => true,
        'commandId' => $command['id'],
        'command' => $command['command'],
        'priority' => $command['priority']
    ]);
} else {
    // コマンドなし
    echo json_encode([
        'success' => true,
        'command' => '',
        'message' => 'No pending commands'
    ]);
}
