<?php
/**
 * Admin Command API
 * POST /api/admin/command
 *
 * 管理側・AIがコマンドを登録する
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

// リクエストボディ取得
$input = json_decode(file_get_contents('php://input'), true);

$agentId = $input['agentId'] ?? '';
$command = $input['command'] ?? '';
$priority = intval($input['priority'] ?? 0);

// 複数エージェントへの一括送信
$agentIds = [];
if (!empty($input['agentIds']) && is_array($input['agentIds'])) {
    $agentIds = $input['agentIds'];
} elseif (!empty($agentId)) {
    $agentIds = [$agentId];
} elseif (isset($input['broadcast']) && $input['broadcast'] === true) {
    // 全エージェントに送信
    $stmt = $pdo->query("SELECT agent_id FROM agents WHERE status = 'online'");
    $agentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

if (empty($agentIds)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing agentId, agentIds, or broadcast flag']);
    exit;
}

if (empty($command)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing command']);
    exit;
}

// コマンドのセキュリティチェック（基本的なブラックリスト）
$dangerousPatterns = [
    '/format\s+[a-z]:/i',      // フォーマット
    '/del\s+\/[sfq]/i',        // 強制削除
    '/rm\s+-rf/i',             // Linux rm -rf
    '/:(){ :|:& };:/i',        // Fork bomb
];

foreach ($dangerousPatterns as $pattern) {
    if (preg_match($pattern, $command)) {
        http_response_code(400);
        echo json_encode(['error' => 'Dangerous command rejected']);
        exit;
    }
}

$createdBy = $auth['role'] . ':' . $auth['name'];
$insertedIds = [];

$pdo->beginTransaction();

try {
    $sql = "INSERT INTO command_queue (agent_id, command, priority, created_by)
            VALUES (:agent_id, :command, :priority, :created_by)";
    $stmt = $pdo->prepare($sql);

    foreach ($agentIds as $aid) {
        // エージェント存在確認
        ensureAgent($aid);

        $stmt->execute([
            ':agent_id' => $aid,
            ':command' => $command,
            ':priority' => $priority,
            ':created_by' => $createdBy
        ]);
        $insertedIds[] = [
            'agentId' => $aid,
            'commandId' => $pdo->lastInsertId()
        ];
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Command queued successfully',
        'count' => count($insertedIds),
        'commands' => $insertedIds
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to queue command', 'message' => $e->getMessage()]);
}
